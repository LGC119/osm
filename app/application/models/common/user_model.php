<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class User_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	public function get_users($type, $aid, $limit, $offset)
	{
		if ('weibo' == $type)
		{
			$this->db->select('*')
					 ->from('wb_account_user wau')
					 ->join('wb_user wu', 'wau.user_weibo_id = wu.user_weibo_id')
					 ->where('wau.wb_aid', $aid);                  
		}
		else
		{
			$this->db->select('*')
					 ->from('wx_user wu')
					 ->where('wu.wx_aid', $aid);
		}

		$rs = $this->db->join('user_sns_relation usr', 'usr.wx_user_id = wu.id', 'left')
					   ->join('user u', 'u.id = usr.user_id', 'left')
					   // ->join('user_contact_info uci', 'u.id = uci.user_id', 'left')
					   // ->join('user_data ud', 'u.id = ud.user_id', 'left')
					   // ->join('user_status_info usi', 'u.id = usi.user_id', 'left')
					   ->limit($limit, $offset)
					   ->get()->result_array();
		return $rs;
	}


	public function show_user($type, $id)
	{
		if ('weibo' == $type) 
		{
			$rs = $this->db->select('wu.id AS user_id, wu.location, wu.gender, u.*')
				->from('wb_user wu')
				->join('user_sns_relation usr', 'wu.id = usr.wb_user_id', 'left')
				->join('user u', 'u.id = usr.user_id', 'left')
				->where('wu.id', $id)
				->get()->row_array();
		} else {
			$rs = $this->db->select("wu.id AS user_id, wu.sex AS gender, CONCAT(province, ' ', city) AS location, u.*", FALSE)
				->from('wx_user wu')
				->join('user_sns_relation usr', 'wu.id = usr.wx_user_id', 'left')
				->join('user u', 'u.id = usr.user_id', 'left')
				->where('wu.id', $id)
				->get()->row_array();
		};

		if ($rs) 
		{
			$tags = $this->get_user_tags($id, $type);
			$rs['tags'] = $tags;
			$event_history = $this->get_event_history($id, $type);
			$rs['event_history'] = $event_history;
		}

		return $rs;
	}

	public function edit_user($type, $id, $tablename = '', $data)
	{
		if ('weibo' == $type)
		{
			$where = array('wb_user_id' => $id);
		}
		else
		{
			$where = array('wx_user_id' => $id);
		}
		$rs = $this->get_one('user_sns_relation', $where);
		if (! empty($rs)){
			$user_id = $rs['user_id'];
			$this->db->where('id', $user_id);
				if ($this->db->update('user', $data)){
					return TRUE;
				}else
					return FALSE;
		}else{
			$data = $this->safe_data('user', $data);
			if (empty($data))
				return FALSE;
			$rs = $this->insert('user', $data);
			if (! $rs['status'])
				return FALSE;

			$insert_id = $rs['insert_id'];

			$user_sns_relation_arr = array();
			$user_sns_relation_arr['user_id'] = $insert_id;
			if ('weibo' == $type)
			{
				$user_sns_relation_arr['wb_user_id'] = $id;
			}
			else
			{
				$user_sns_relation_arr['wx_user_id'] = $id;
			}

			if (! $this->db->insert('user_sns_relation', $user_sns_relation_arr))
				return FALSE;

			// if ('user' != $tablename)
			// {
			//     $data['user_id'] = $insert_id;
			//     // $data = $this->safe_data($tablename, $data);
			//     $rs = $this->insert($tablename, $data);
			//     if ($rs['status'])
			//         return TRUE;
			//     else
			//         return FALSE;
			// }
			
		}
	}

	// 用户对应标签
	public function tagid_to_name(){
		$sql = 'SELECT rtag.wx_user_id,GROUP_CONCAT(tag.tag_name) tag_name FROM '.$this->db->dbprefix('rl_wx_user_tag').' rtag
					LEFT JOIN '.$this->db->dbprefix('tag').' tag
						ON rtag.tag_id=tag.id
					GROUP BY wx_user_id';
		$data = $this ->db ->query($sql) ->result_array();
		return $data;
	}

	// 用户对应的组
	public function user_to_group(){
		$sql = "SELECT rgu.wx_user_id,GROUP_CONCAT(g.name) gname FROM ".$this->db->dbprefix("rl_wx_group_user")." rgu
					LEFT JOIN ".$this->db->dbprefix("wx_group")." g
						ON rgu.wx_group_id=g.id
					GROUP BY rgu.wx_user_id";
		return $this->db->query($sql)->result_array();
	}

	/* 获取用户信息交流记录 */
	public function get_communication_history ($user_id, $type) 
	{
		// 
	}

	/* 获取用户标签 */
	public function get_user_tags ($user_id, $type)
	{
		$type = ($type == 'weibo') ? 'wb' : 'wx';

		$tags = $this->db->select('t.id, t.tag_name')
			->from('rl_' . $type . '_user_tag rwut')
			->join('tag t', 't.id = rwut.tag_id')
			->where(array ($type . '_user_id'=>$user_id))
			->order_by('weight', 'desc')
            ->group_by('t.id')
			->get()->result_array();

		return $tags;
	}

	/* 获取用户参与活动记录 */
	public function get_event_history ($user_id, $type) 
	{
		$field_name = ($type == 'weibo') ? 'wb_user_id' : 'wx_user_id';
		$where = array (
			'e.company_id' => $this->session->userdata('company_id'), 
			'ep.participated_at <>' => '0000-00-00 00:00:00', 
			$field_name => $user_id
		);
		$event_history = $this->db->select('e.event_title, e.type, e.start_time, e.end_time, e.created_at, e.status')
			->from('event e')
			->join('event_participant ep', 'ep.event_id = e.id', 'left')
			->where($where)
			->group_by('e.id')
			->get()->result_array();

		return $event_history;
	}
}