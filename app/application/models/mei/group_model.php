<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 高级用户组模型
*/
class Group_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 获取高级用户组列表 */
	public function get_list ($params)
	{
		$total = $this->db->select('COUNT(id) AS num')
			->get('user_group')->row_array();

		if ( ! $total OR $total['num'] == 0) return '没有高级组数据！';

		$limit = $params['perpage'];
		$offset = ($total['num'] > $params['page'] * $limit) ? ($params['page'] - 1) * $limit : 0;

		$list = $this->db->select('id, name, description, members_count, created_at')
			->limit($limit, $offset)
			->get('user_group')->result_array();

		if ($list) 
			return array ('total_num'=>$total['num'], 'list'=>$list, 'page'=>$params['page'], 'perpage'=>$limit);
		else 
			return '没有高级组数据！';
	}

	/* 添加用户到组 */
	public function add_user ($group_id, $user_ids) 
	{
		$group_info = $this->db->select('id')
			->from('user_group')
			->where('id', $group_id)
			->get()->row_array();

		if ( ! $group_info) return '没有找到高级组信息！';
		$ids = array_unique($user_ids);

		if (count($ids) > 100000) return '一次插入请不要超过10万条数据！';
		
		/* 分批插入数据库(每批500条数据) */
		$truncked = array_chunk($ids, 500);
		foreach ($truncked as $userids) {
			$insert_sql = "INSERT INTO {$this->db->dbprefix('rl_group_user')} (`group_id`, `user_id`) VALUES ";
			$vals = array ();
			foreach ($userids as $id) 
				$vals[] = '(' . $group_id . ', ' . $id . ')';

			$val_str = implode(', ', $vals);
			$insert_sql .= $val_str . ' ON DUPLICATE KEY UPDATE user_id=user_id;';

			/* 插入数据 */
			$this->db->query($insert_sql);
		}

		$this->_update_members_count($group_id);

		return TRUE;
	}

	/* 创建高级用户组 */
	public function create ($name, $desc) 
	{
		$res = $this->_verify_group($name);
		if ($res !== TRUE) return $res;

		$group = array (
			'name' => trim($name), 
			'description' => trim($desc), 
			'created_at' => date('Y-m-d H:i:s')
		);

		$this->db->insert('user_group', $group);

		return $this->db->insert_id() ? TRUE : '创建用户组失败！';
	}

	/* 获取用户计数总量 */
	public function modify ($id, $name) 
	{
		$group = $this->db->select('name')
			->from('user_group')
			->where('id', $id)
			->get()->row_array();

		if ( ! $group) return '没有找到您要修改的组！';
		if (trim($group['name']) == trim($name)) return '组名称没有改变！';

		$this->db->set('name', trim($name))->where('id', $id)->update('user_group');

		return $this->db->affected_rows();
	}

	public function delete ($params) {}

	/**
	* @获取推送信息
	* @param id group_id
	* @return array
	*/
	public function get_pushinfo($id){
		$userids = $this->get_user_id($id);
		$rs = array();
		foreach ($userids as $key => $value) {
			$rs[] = $this->get_user_info($value['user_id']);
		}
		print_r($rs);
		return $rs;
	}

	/**
	* @
	*/
	private function get_user_id($id){
		$this->db->select('user_id');
		$this->db->from('rl_group_user');
		$this->db->where('group_id',$id);
		$ids = $this->db->get()->result_array();
		return $ids;
	}

	/* 获取用户组成员信息，每次取1W条 */
	public function get_group_user_info ($id) {

		$members_count = $this->db->select('COUNT(*) AS num')
			->from('rl_group_user')
			->where('group_id', $id)
			->get()->row_array();
		if ( ! $members_count OR ! $members_count['num']) return '没有找到您选择组的用户信息！';

		$wb_users_info = array ();
		$wx_users_info = array ();

		// 每次取五千条数据
		$page = ceil($members_count['num'] / 5000);
		for ($i=0; $i < $page; $i++) 
		{ 
			/* 关联微博用户表信息 */
			$userids = $this->db->select('user_id')
				->from('rl_group_user')
				->limit(5000, $i * 5000)
				->get()->result_array();

			$ids_arr = array ();
			foreach ($userids as $val) $ids_arr[] = $val['user_id'];
			unset($userids);

			$wx_account_info = $this->db->select('COUNT(wu.wx_aid) AS num, wu.wx_aid')
				->from('user_sns_relation usr')
				->join('wx_user wu', 'usr.wx_user_id = wu.id', 'left')
				->where_in('usr.user_id', $ids_arr)
				->group_by('wu.wx_aid')
				->get()->result_array();

			$wb_account_info = $this->db->select('COUNT(wau.wb_aid) AS num, wau.wb_aid')
				->from('user_sns_relation usr')
				->join('wb_user wu', 'wu.id = usr.wb_user_id', 'left')
				->join('wb_account_user wau', 'wau.user_weibo_id = wu.user_weibo_id', 'left')
				->where_in('usr.user_id', $ids_arr)
				->get()->result_array();

			if ($wx_account_info) 
			{
				foreach ($wx_account_info as $val) {
					if ($val['num'] <= 0 OR $val['wx_aid'] <= 0) continue;
					if (isset($wx_users_info[$val['wx_aid']]))
						$wx_users_info[$val['wx_aid']] += $val['num'];
					else 
						$wx_users_info[$val['wx_aid']] = $val['num'];
				}
			}

			if ($wb_account_info)
			{
				foreach ($wb_account_info as $val) {
					if ($val['num'] <= 0 OR $val['wx_aid'] <= 0) continue;
					if (isset($wb_users_info[$val['wb_aid']]))
						$wb_users_info[$val['wb_aid']] += $val['num'];
					else 
						$wb_users_info[$val['wb_aid']] = $val['num'];
				}
			}
		}

		if ( ! $wb_users_info && ! $wx_users_info) return '没有找到组用户的账号关联信息！';

		/* 获取关联账号信息 */
		$user_info = array ('wb'=>array(), 'wx'=>array());
		if ($wb_users_info) 
		{
			foreach ($wb_users_info as $key => $val) {
				$account_name = $this->db->select('screen_name')
					->where('id', $key)
					->get('wb_account')->row_array();

				$user_info['wb'][] = array (
					'name' => $account_name ? $account_name['screen_name'] : '', 
					'num' => $val
				);
			}
		}

		if ($wx_users_info) 
		{
			foreach ($wx_users_info as $key => $val) {
				$account_name = $this->db->select('nickname')
					->where('id', $key)
					->get('wx_account')->row_array();

				$user_info['wx'][] = array (
					'name' => $account_name ? $account_name['nickname'] : '', 
					'num' => $val
				);
			}
		}

		return $user_info;
	}

	/* 更新用户组成员总数 */
	private function _update_members_count ($group_id) 
	{
		$num = $this->db->select('COUNT(*) AS num')
			->from('rl_group_user')
			->where('group_id', $group_id)
			->get()->row_array();

		$num = $num ? $num['num'] : 0;
		$this->db->set('members_count', $num)->where('id', $group_id)->update('user_group');
	}

	private function _verify_group ($name, $id = 0) 
	{
		$name = trim($name);

		if (mb_strlen($name, 'UTF-8') < 1 || mb_strlen($name, 'UTF-8') > 20) 
			return '组名称字段请保证在1~20个字符！';

		$name_used = $this->db->select('id')
			->from('user_group')
			->where('name', $name)
			->get()->row_array();

		if ($name_used) return '该组名称已经被使用！';

		return TRUE;
	}

}
