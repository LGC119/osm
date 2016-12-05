<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 微博关键词设置模型 */
class keyword_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 新增监控关键词，使用VDong接口 */
	public function add_vdong_keyword ($name) 
	{
		// 
	}

	/** 
	 * function add 添加关键词 
	 * 
	 * @param $keyword string 关键词
	 * @param $type int 关键词类型 0:舆情监控, 1:自动忽略, 2:自动置顶
	 * @param $cmn_type 关键词适用类型
	 * 
	 * @return 添加的关键词信息 或 错误信息
	 */
	public function add ($keyword, $type, $cmn_type = 0)
	{
		if (mb_strlen($keyword, 'UTF8') < 1 OR mb_strlen($keyword, 'UTF8') > 20) 
			return '分类名称在 0~20 个字符之间！';

		if ( ! in_array($type, array(0, 1, 2))) 
			return '关键词类型出错！';

		$cmn_types_count = $this->config->item('cmn_types_count');
		if ($cmn_type < 0 || $cmn_type >= pow(2, $cmn_types_count)) 
			return '关键词适用范围出错';

		$where = array('text'=>$keyword, 'is_deleted'=>0);
		if ($type == 0) $where['type'] = 0;

		$duplicate = $this->db->select('id') 
			->from('wb_keyword')
			->where($where)
			->get()->row_array();

		if ($duplicate && $duplicate['id']) 
			return '与其他关键词重复或冲突！';

		$staff = $this->db->select('name')
			->where('id', $this->session->userdata('staff_id'))
			->get('staff')->row_array();

		$staff_name = $staff ? $staff['name'] : '';

		$vdong_id = 0;
		$created_at = date('Y-m-d H:i:s');
		if ($type == 0) 
		{
			$this->load->library('vdong');
			$res = $this->vdong->create($keyword);
			if ( ! isset($res['id'])) 
				return $res['message'];

			$keyword = $res['text'];
			$created_at = isset($res['cdate']) ? $res['cdate'] : date('Y-m-d H:i:s');
		}

		$data = array (
			'text' => $keyword, 
			'type' => $type, 
			'cmn_type' => $cmn_type, 
			'staff_id' => $this->session->userdata('staff_id'), 
			'staff_name' => $staff_name, 
			'status' => 1, 
			'company_id' => $this->session->userdata('company_id'), 
			'created_at' => $created_at 
		);

		$this->db->insert('wb_keyword', $data);
		return $data;
	}


	/* 获取系统设定的自动处理关键词 */
	public function get_auto_keywords ($company_id) // 参数 company_id 
	{
		/* Cache解决方案待完善 */
		$this->load->driver('cache');
		$company_id = (int) $company_id; 
		if ($company_id < 1) return FALSE;

		if ( ! $auto_keywords = $this->cache->get('common/'.$company_id.'_auto_keywords.cache'))
			$auto_keywords = $this->update_auto_keywords_cache($company_id);

		return $auto_keywords;
	}

	/* 更新自动设定关键词的缓存 */
	public function update_auto_keywords_cache ($company_id) 
	{
		$where = array (
			'type <>' => 0, 
			'cmn_type >' => 1, 
			'status' => 1, 
			'is_deleted' => 0, 
			'company_id' => $company_id
		);

		$this->load->config('common/keyword');
		$cmn_types = $this->config->item('cmn_types');
		$cmn_types_count = $this->config->item('cmn_types_count');
		$all_keywords = $this->db->select('GROUP_CONCAT(text) AS text, type')
			->select("LPAD(BIN(`cmn_type`), 5, 0) AS cmn_type", FALSE)
			->from('wb_keyword')
			->where($where)
			->group_by('cmn_type, type')
			->get()->result_array();

		foreach ($cmn_types as $val) 
			$$val = array ('ignore'=>array(), 'pintop'=>array());

		/* 获取设定 */
		if ($all_keywords) 
		{
			foreach ($all_keywords as $val) 
			{
				$type = $val['type'] == 1 ? 'ignore' : 'pintop';

				if ($val['cmn_type'][0] == 1)
					$wexinmsg[$type] = array_merge($wexinmsg[$type], explode(',', $val['text']));
				if ($val['cmn_type'][1] == 1)
					$privmsgs[$type] = array_merge($privmsgs[$type], explode(',', $val['text']));
				if ($val['cmn_type'][2] == 1)
					$mentions[$type] = array_merge($mentions[$type], explode(',', $val['text']));
				if ($val['cmn_type'][3] == 1)
					$comments[$type] = array_merge($comments[$type], explode(',', $val['text']));
				if ($val['cmn_type'][4] == 1)
					$keywords[$type] = array_merge($keywords[$type], explode(',', $val['text']));
			}
		}

		$auto_keywords = array ();
		$this->load->driver('cache');
		foreach ($cmn_types as $key => $val) 
			$auto_keywords[$val] = $$val;

		$this->cache->file->save('common/'.$company_id.'_auto_keywords.cache', $auto_keywords, 1800);

		return $auto_keywords;
	}

}