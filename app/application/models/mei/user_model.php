<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class User_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	public function get_list($params)
	{
		$total_num = $this->_get_count($params);

		if ( ! $total_num) return FALSE;

		$this->db->select('u.*')
			->select('GROUP_CONCAT(wbu.screen_name) AS weibo_users')
			->select('GROUP_CONCAT(wxu.nickname) AS weixin_users')
			->from('user u')
			->join('user_sns_relation usr', 'u.id = usr.user_id', 'left')
			->join('wb_user wbu', 'usr.wb_user_id = wbu.id', 'left')
			->join('wx_user wxu', 'usr.wx_user_id = wxu.id', 'left')
			->where('usr.id IS NOT NULL');

		$this->_set_where($params);

		$offset = 0;
		$limit = $params['items_per_page'];
		if ($total_num > $limit * $params['current_page']) 
			$offset = ($params['current_page'] - 1) * $limit;

		$list = $this->db->limit($limit, $offset)
			->group_by('u.id')
			->get()->result_array();

		return array (
			'total_num' => $total_num, 
			'current_page' => $params['current_page'],
			'items_per_page' => $limit, 
			'list' => $list
		);
	}

	/* 获取用户计数总量 */
	private function _get_count ($params) 
	{
		$this->db->select('COUNT(*) AS num')
			->from('user u')
			->join('user_sns_relation usr', 'u.id = usr.user_id', 'left')
			->where('usr.id IS NOT NULL');

		$this->_set_where($params);
		$num = $this->db->get()->row_array();

		return $num ? $num['num'] : 0;
	}

	private function _set_where ($params) 
	{
		if (isset($params['name']) && $params['name'])
			$this->db->like('u.full_name', $params['name']);
		if (isset($params['tel']) && $params['tel'])
			$this->db->like('u.tel1', $params['tel']);

		if (isset($params['gender']) && $params['gender'] > -1)
			$this->db->where("u.gender = '{$params['gender']}'", NULL, FALSE);
		if (isset($params['blood']) && $params['blood'] > -1)
			$this->db->where('u.blood_type', $params['blood']);
		if (isset($params['constellation']) && $params['constellation'] > -1)
			$this->db->where('u.constellation', $params['constellation']);

		/**
		 * TODO : 修改为以标签关联为主表筛选
		 */
		/* 标签筛选 */
		if (isset($params['tags']) && is_array($params['tags']) && ! empty($params['tags'])) 
		{
			// if
		}

		/**
		 * TODO : 修改为以活动参与者关联为主表筛选
		 */
		/* 活动筛选 */
		if (isset($params['events']) && is_array($params['events']) && ! empty($params['events'])) 
		{
			// 
		}

		return ;
	}

}