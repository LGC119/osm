<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Tag_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 获取现有分类数据 */
	public function get_all_tags ($company_id) 
	{
		$rst = $this->db->select('id, tag_name, pid, created_at')
			->from('tag')
			->where('company_id', $company_id)
			->where('is_preset', 0)
			->order_by('id', 'desc')
			->get()->result_array();
		$tags = array();
		foreach ($rst as $k => $tag)
		{
			if ($tag['pid'] == 0)
			{
				$tag['tags'] = array();
				$tags[] = $tag;

				unset($rst[$k]);
			}

		}

		foreach ($tags as $k => $ptag) 
		{
			foreach ($rst as $tag) 
			{
				if ($tag['pid'] == $ptag['id'])
				{
					$tags[$k]['tags'][] = $tag;
				}	
			}
		}
		return $tags;
	}

	/* 获取现有订阅分类数据 */
	public function get_all_tags_sub ($company_id) 
	{
		$rst = $this->db->select('id, tag_name, pid, created_at')
			->from('tag')
			->where('company_id', $company_id)
			->where('is_preset', 1)
			->order_by('id', 'desc')
			->get()->result_array();
		$tags = array();
		foreach ($rst as $k => $tag)
		{
			if ($tag['pid'] == 0)
			{
				$tag['tags'] = array();
				$tags[] = $tag;

				unset($rst[$k]);
			}

		}

		foreach ($tags as $k => $ptag) 
		{
			foreach ($rst as $tag) 
			{
				if ($tag['pid'] == $ptag['id'])
				{
					$tags[$k]['tags'][] = $tag;
				}	
			}
		}
		return $tags;
	}

	public function create($company_id, $staff_id, $pid, $name)
	{
		$pid = intval($pid);
		$name = trim(urldecode($name));

		if ( ! $name) 
			return '请填写标签名称！';

		if (mb_strlen($name) > 10) 
			return '标签名称不能超过10个字符！';

		if ($pid > 0) 
		{
			$parent = $this->db->select('id')->from('tag')->where('id', $pid)->where('is_preset', 0)->get()->row_array();
			if ( ! $parent) 
				return '父类信息不存在，请刷新后重试！';
		}

		$tag = array(
			'company_id' => $company_id,
			'tag_name' => $name,
			'pid' => $pid,
			'created_at' => date('Y-m-d H:i:s'),
			'add_staff_id' => $staff_id
		);
		
		$id = $this->db->insert('tag', $tag);
		if ($id > 0)
			return array_merge($tag, array('id'=>$this->db->insert_id()));
		else 
			return '服务器忙，请稍后尝试！';
	}

	public function create_sub($company_id, $staff_id, $pid, $name)
	{
		$pid = intval($pid);
		$name = trim(urldecode($name));

		if ( ! $name) 
			return '请填写标签名称！';

		if (mb_strlen($name) > 10) 
			return '标签名称不能超过10个字符！';

		if ($pid > 0) 
		{
			$parent = $this->db->select('id')->from('tag')->where('id', $pid)->where('is_preset', 1)->get()->row_array();
			if ( ! $parent) 
				return '父类信息不存在，请刷新后重试！';
		}

		$tag = array(
			'company_id' => $company_id,
			'tag_name' => $name,
			'pid' => $pid,
			'created_at' => date('Y-m-d H:i:s'),
			'add_staff_id' => $staff_id,
			'is_preset' => 1,
		);
		
		$id = $this->db->insert('tag', $tag);
		if ($id > 0)
			return array_merge($tag, array('id'=>$this->db->insert_id()));
		else 
			return '服务器忙，请稍后尝试！';
	}

	public function delete ($id)
	{
		$this->db->delete('tag', array('pid' => $id));
		$this->db->delete('tag', array('id' => $id));
		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	/**
	 + FUNCTION tag_user 给用户上标签
	 + @param tag_ids 	string | array 			标签ID
	 + @param user_id 	int 					用户的 wb_user_id | wx_user_id
	 + @param source 	string 					标签来源 'manual'|'link'|'rule'|'event'|'timeline'
	 + @param account 	array 					账号信息 id | company_id
	 + @param type 		string 					用户类型 'wb'|'wx'
	 + @param plus 		int | decimal(10, 2) 	增量
	 + 
	 + @return TRUE | FALSE
	**/
	public function tag_user ($tag_ids, $user_id, $source, $account, $type, $plus = 1)
	{
		if (is_string($tag_ids))
			$tag_ids = explode('|', $tag_ids);
		if (is_numeric($tag_ids))
			$tag_ids = [$tag_ids];

		/* 手工编辑标签先删除用户的manual TAG数据 */
		if ($source == 'manual') 
			$this->db->where($type . '_user_id', $user_id)->delete('rl_' . $type . '_user_tag');

		if ( ! is_array($tag_ids) 
			OR empty($tag_ids) 
			OR ! in_array($type, ['wb', 'wx'])
			OR ! in_array($source, ['manual', 'link', 'rule', 'event', 'timeline']))
			return FALSE;

		$table = $this->db->dbprefix('rl_' . $type . '_user_tag');
		$field = $source . '_tag_hits';
		$tag_sql = "INSERT INTO {$table} (`{$type}_user_id`, `tag_id`, `company_id`, `{$type}_aid`, `{$field}`) VALUES ";
		foreach ($tag_ids as $tag_id) 
			$tag_sql .= "({$user_id}, {$tag_id}, {$account['company_id']}, {$account['id']}, 1),";

		$tag_sql = rtrim($tag_sql, ',');
		if ($source != 'manual') {
			$tag_sql .= " ON DUPLICATE KEY UPDATE {$field}={$plus}+VALUES({$field});";
		} else {
			$tag_sql .= " ON DUPLICATE KEY UPDATE {$field}=1;";
			$this->strip_manual_tag($user_id, $account['id'], $type);
		}
		
		$this->db->query($tag_sql);

		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	/**
	 * 给微博用户打标签, 在微博和活动时使用
	 * @param $user_tags
	 * @param $account
	 * @param string $type
	 */
	public function tag_weibo_user($user_tags, $account, $type = 'timeline')
	{
		if (count($user_tags) == 0)
			return ;

		foreach ($user_tags as $utag) 
		{
			// 获取wb_user_id, 并绑定标签
			if ( ! isset($utag['id']) || (int) $utag['id'] <= 0) 
			{
				$user = $this->db->select('id')->from('wb_user')
					->where('user_weibo_id', $utag['user_weibo_id'])->get()->row_array();
				if ($user) 
					$utag['id'] = $user['id'];
				else 
					continue ;
			}
			$this->tag_user($utag['tagids'], $utag['id'], $type, $account, 'wb', 1);
		}
	}

	/* 去除用户的标签 */
	public function strip_user_tag ($tag_ids, $user_id) 
	{
		// 
	}

	/* 清除用户人工标签 */
	public function strip_manual_tag ($user_id, $aid, $type) 
	{
		if ($type != 'wb' && $type != 'wx') 
			return FALSE;

		$where = array (
			$type . '_user_id' => $user_id,
			$type . '_aid' => $aid
		);

		/* 直接删除单纯手工的记录 */
		$this->db->where($where)
			->where("link_tag_hits + rule_tag_hits + event_tag_hits = 0.00", NULL, FALSE)
			->delete('rl_' . $type . '_user_tag');

		/* 带有手工的先清空手工记录 */
		$this->db->set('manual_tag_hits', 0)
			->where($where)
			->where("link_tag_hits + rule_tag_hits + event_tag_hits > 0.00", NULL, FALSE)
			->update('rl_' . $type . '_user_tag');
	}

}

/* End of file category.php */
/* Location: ./application/controllers/common/category.php */