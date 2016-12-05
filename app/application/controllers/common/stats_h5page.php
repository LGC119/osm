<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - H5页面相关
*/
class Stats_h5page extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->cid = $this->session->userdata('company_id');
	}

	public function top_views ()
	{
		$where = array(
			'h.company_id' => $this->cid
		);

		$rst = $this->db->select('h.title, COUNT(ep.id) num', FALSE)
			->from('h5_page h')
			->join('event e', 'e.h5page_id = h.id')
			->join('event_participant ep', 'e.id = ep.event_id')
			->where($where)
			->group_by('h.id')
			->order_by('num')
			->get()->result_array();

		if (empty($rst)) 
		{
			$this->meret(NULL, MERET_EMPTY, '暂无统计数据！');
			exit;
		}

		$x = $y = array();

		foreach ($rst as $v) 
		{
			$x[] = $v['name'];
			$y[] = intval($v['hits']);
		}

		$data = array(
			'x' => $x,
			'y' => $y,
			'xtitle' => 'H5页面访问TOP10',
			'ytitle' => '触发量',
			'data_title'=>'关键词'
		);
		$this->meret($data);
	}

}

/* End of file stats_h5page.php */
/* Location: ./application/controllers/common/stats_h5page.php */