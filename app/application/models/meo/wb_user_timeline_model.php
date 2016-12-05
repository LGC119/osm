<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wb_user_timeline_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 根据筛选参数获取已发布的微博 */
	public function get_filtered_timeline ($p) 
	{
		if (empty($p)) 
			return ;

		/* 获取筛选总量 */
		$this->db->from('wb_user_timeline wut');
		$this->_set_where($p);
		$total = $this->db->get()->num_rows();

		if ( ! $total) 
			return '没有筛选到已发微博';
		
		$page = isset($p['current_page']) ? intval($p['current_page']) : 1;
		$perpage = isset($p['items_per_page']) ? intval($p['items_per_page']) : 20;

		/* 获取筛选微博的ID */
		$this->db->select('wut.id')->from('wb_user_timeline wut');
		$this->_set_where($p);

		if ($page > ceil($total / $perpage)) 
			$this->db->limit($perpage);
		else 
			$this->db->limit($perpage, ($page - 1) * $perpage);

		$ids = $this->db->get()->result_array();
		$filtered_ids = array ();
		foreach ($ids as $id) 
			$filtered_ids[] = $id['id'];

		/* 获取微博数据 */
		$timeline = $this->db->select('wut.id, wut.weibo_id, wut.text, wut.wb_info')
			->select('GROUP_CONCAT(t.tag_name) AS tags, GROUP_CONCAT(t.id) AS tagids', FALSE)
			->from('wb_user_timeline wut')
			->join('rl_wb_user_timeline_tag rwutt', 'rwutt.wb_id = wut.id', 'left')
			->join('tag t', 'rwutt.tag_id = t.id', 'left')
			->where_in('wut.id', $filtered_ids)
			->group_by('wut.id')
			->get()->result_array();

		foreach ($timeline as &$item) {
			$wb = json_decode($item['wb_info'], TRUE);
			if (isset($wb['created_at'])) 
				$wb['created_at'] = date('Y-m-d H:i:s', strtotime($wb['created_at']));
			if (isset($wb['retweeted_status'])) 
				$wb['retweeted_status']['created_at'] = date('Y-m-d H:i:s', strtotime($wb['retweeted_status']['created_at']));
			$item['wb_info'] = $wb;
		}

		// 返回信息
		return array (
			'statuses' => $timeline, 
			'current_page' => $page,
			'items_per_page' => $perpage,
			'total_number' => $total
		);
	}

	/* 筛选参数数据库设定 */
	public function _set_where ($p) 
	{
		/* 当前账号 */
		$this->db->where('wut.wb_aid', $this->session->userdata('wb_aid'));
		/* 关键词 */
		if (isset($p['keyword']) && trim($p['keyword']))
			$this->db->like('wut.text', trim($p['keyword']));

		/* 微博发布时间 */
		if (isset($p['start']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $p['start']))
			$this->db->where('wut.created_at >=', strtotime($p['start']));
		if (isset($p['end']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $p['end']))
			$this->db->where('wut.created_at <=', strtotime($p['end']) + 24 * 3600);

		/* 标签 */
		if (isset($p['tags']) && is_array($p['tags']) && $p['tags']) 
			$this->db->join('rl_wb_user_timeline_tag rwutt', 'rwutt.wb_id = wut.id', 'left')
				->where_in('rwutt.tag_id', $p['tags'])
				->group_by('wut.id');
	}

	public function get_all_timeline_by_tags($tags_arr,$current_page,$items_per_page){
		$limit_begin = ($current_page - 1) * $items_per_page;
		$wb_id_arr = $this->get_wbId_by_tags($tags_arr);
		if (empty($wb_id_arr)) {
			return false;
		}
		$data = $this->db->select('*')
						->from('wb_user_timeline')
						->where_in('id',$wb_id_arr)
						->limit($items_per_page,$limit_begin)
						->group_by("id")
						->get()->result_array();
		return $data;
	}

	private function get_wbId_by_tags($tags_arr){
		foreach ($tags_arr as $key => $tag_id) {
			$arr_1 = array();
			$arr_2 = array();
			$arr_1 = $this->db->select('wb_id')
							->from('rl_wb_user_timeline_tag')
							->where('tag_id',$tag_id)
							->group_by("wb_id")
							->get()->result_array();
			foreach ($arr_1 as $k => $v) {
				$arr_2[] = $v['wb_id'];
			}
			if (!isset($wb_id_arr)) {
				$wb_id_arr = $arr_2;
			}
			$wb_id_arr = array_intersect($wb_id_arr,$arr_2);
		}
		return $wb_id_arr;
	}
	public function get_status_tags($idstr)
	{
		$timeline = $this->db->select('wut.id AS wb_id, wut.is_deleted, wut.me_sent')
			->select('GROUP_CONCAT(t.tag_name) AS tags, GROUP_CONCAT(t.id) AS tagids')
			->from('wb_user_timeline wut')
			->join('rl_wb_user_timeline_tag rwutt', 'wut.id = rwutt.wb_id', 'left')
			->join('tag t', 't.id = rwutt.tag_id', 'left')
			->where('wut.weibo_id', $idstr)
			->group_by('wut.id')
			->get()->row_array();

		return $timeline ? $timeline : FALSE;
	}

	public function delete_user_timeline($wb_id){
		$this->db->where('id',$wb_id);
		$result = $this->db->update('wb_user_timeline',array('is_deleted' => 1));
		return $result;
	}

}