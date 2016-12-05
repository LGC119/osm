<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wb_Send_Crontab_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	public function get_all_crontabs()
	{
		$rs = $this->db->select('*')
			->from('wb_send_crontab')
			->where_in('is_sent', array(0, 2))
			->where('send_at <= ', time()+60)
			->get()->result_array();

		if (! empty($rs))
		{
			$crontabs = array();
			foreach ($rs as $key => $val)
			{
				$crontabs[$val['wb_aid']][] = $val;
			}
			return $crontabs;
		}
		else
		{
			return FALSE;
		}
	}

	public function get_account_info($wb_aid)
	{
		$rst = $this->db->select('id, weibo_id, screen_name, profile_image_url')
			->get_where('wb_account', array('id'=>$wb_aid))
			->row_array();
		return $rst;
	}

	/* 获取微博定时任务 */
	public function get_crontabs ($params) 
	{
		$this->db->from('wb_send_crontab wsc');
		$this->_set_where($params);
		$total = $this->db->get()->num_rows();

		if ($total == 0) 
			return '没有定时微博任务！';

		$page = intval($params['current_page']) > 0 ? intval($params['current_page']) : 0;
		$perpage = (intval($params['items_per_page']) > 0 && intval($params['items_per_page']) <= 20) ? intval($params['items_per_page']) : 10;

		$this->db->select('wsc.*')
			->select('GROUP_CONCAT(DISTINCT t.tag_name) AS tag_names', FALSE)
			->from('wb_send_crontab wsc')
			->join('rl_wb_user_timeline_tag rwutt', 'wsc.id = rwutt.wb_id', 'left')
			->join('tag t', 'rwutt.tag_id = t.id', 'left')
			->group_by('wsc.id');
		$this->_set_where($params);
		if ($page > ceil($total / $perpage)) 
			$this->db->limit($perpage);
		else 
			$this->db->limit($perpage, ($page - 1) * $perpage);
		$crontabs = $this->db->get()->result_array();

		return array (
			'crontabs' => $crontabs, 
			'current_page' => $page,
			'items_per_page' => $perpage,
			'total_number' => $total
		);
	}

	public function _set_where ($params) 
	{
		/* 设定为当前账号 */
		$this->db->where('wb_aid', $this->session->userdata('wb_aid'));
		/* 根据发送状态筛选 */
		if (isset($params['is_sent']) && in_array($params['is_sent'], array(0, 1, 2, 3))) 
			$this->db->where('wsc.is_sent', $params['is_sent']); 

		/* 根据类型筛选 */
		if (isset($params['type']) && in_array($params['type'], array(0, 1, 2, 3, 4))) 
			$this->db->where('wsc.type', $params['type']); 

		/* 起始时间筛选 */
		if (isset($params['start']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $params['start'])) 
			$this->db->where('wsc.set_at >=', strtotime($params['start']));

		/* 结束时间筛选 */
		if (isset($params['end']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $params['end'])) 
			$this->db->where('wsc.set_at <=', strtotime($params['end'] . ' 23:59:59'));

		if (isset($params['keyword']) && trim($params['keyword'])) 
			$this->db->like('wsc.text', trim($params['keyword']));

		return TRUE;
	}

}