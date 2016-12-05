<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 信息监控 - 舆情分类
*/
class Monitor_category extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取设定了监控的分类列表
	 * @param $type {0:所有，1:超过监控阈值的分类列表}
	 *
	 * @return array ()
	 **/
	public function get_wb_monitored ($type) 
	{
		$categories = $this->_get_monitored($type);
		
		if (is_string($categories)) 
			$this->meret(NULL, MERET_BADREQUEST, $categories);
		else 
			$this->meret($categories);
	}

	/* 获取微信监控分类数据 */
	public function get_wx_monitored ($type) 
	{
		$categories = $this->_get_monitored($type, 'wx');
		
		if (is_string($categories)) 
			$this->meret(NULL, MERET_BADREQUEST, $categories);
		else 
			$this->meret($categories);
	}

	/* 获取监控的分类数据 */
	private function _get_monitored ($type = 0, $source = 'wb') 
	{
		if ($type < 0 OR $type > 1) 
			return '没有找到任何分类！';

		if ( ! in_array($source, array ('wb', 'wx'))) 
			return '请求参数不正确！';

		$where = array (
			'sc.company_id' => $this->cid, 
			'sc.'.$source.'_threshold >' => 0, 
			'sc.parent_id >' => 0
		);

		$categories = $this->db->select('sc.id, sc.cat_name AS sname, pc.cat_name AS pname, sc.'.$source.'_threshold')
			->from('category sc')
			->join('category pc', 'sc.parent_id = pc.id', 'left')
			->where($where)
			->where('pc.cat_name IS NOT NULL', NULL, FALSE)
			->get()->result_array();

		if ( ! $categories) 
			return '没有监控任何分类！';

		/* 获取每个分类的当前的数据总量 */
		foreach ($categories as &$category) 
		{
			$total = $this->db->select('COUNT(id) AS num')
				->from('rl_'.$source.'_communication_category')
				->where('cat_id', $category['id'])
				->get()->row_array();

			$category['total'] = $total ? $total['num'] : 0;
			$percentage = $category['total'] / $category[$source.'_threshold'];
			$category['class'] = $percentage < 1 ? 'orange' : $percentage < 0.9 ? 'green' : 'red';
			$category['percentage'] = number_format($percentage * 100, 2) . '%';
		}
		
		return $categories;
	}

}

/* End of file monitor_category.php */
/* Location: ./application/controllers/meo/monitor_category.php */