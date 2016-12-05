<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 规则相关
*/
class Stats_rule extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->cid = $this->session->userdata('company_id');
	}

	public function top_keywords ()
	{
		$where = array(
			'company_id' => $this->cid,
			'hits !=' => 0
		);
		$rst = $this->db->select('name, hits')
			->from('wx_keyword')
			->where($where)
			->order_by('hits', 'desc')
			->limit(10)
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
			'xtitle' => '规则关键词触发TOP10',
			'ytitle' => '触发量',
			'data_title'=>'关键词'
		);
		$this->meret($data);
	}

}

/* End of file stats_rule.php */
/* Location: ./application/controllers/common/stats_rule.php */