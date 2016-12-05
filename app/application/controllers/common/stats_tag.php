<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 所有用户的标签
*/
class Stats_tag extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->cid = $this->session->userdata('company_id');
	}

	/* 获取TOP20的标签 */
	public function top_tags () 
	{
		$limit = intval($this->input->get_post('limit'));
		if ($limit < 10 OR $limit > 100 OR !$limit) $limit = 50;

		$top_tags['trigger'] = $this->db->select("t.id, t.tag_name, 
				SUM(
					if(isnull(wbut.link_tag_hits), 0, wbut.link_tag_hits) + 
					if(isnull(wbut.rule_tag_hits), 0, wbut.rule_tag_hits) +
					if(isnull(wbut.event_tag_hits), 0, wbut.event_tag_hits) +
					if(isnull(wxut.link_tag_hits), 0, wxut.link_tag_hits) + 
					if(isnull(wxut.rule_tag_hits), 0, wxut.rule_tag_hits) +
					if(isnull(wxut.event_tag_hits), 0, wxut.event_tag_hits)
				) AS num", FALSE)
			->from('tag t')
			->join('rl_wb_user_tag wbut', 't.id = wbut.tag_id', 'left')
			->join('rl_wx_user_tag wxut', 't.id = wxut.tag_id', 'left')
			->group_by('t.id')
			->order_by('num', 'DESC')
			->where('t.company_id', $this->cid)
			->limit($limit)
			->get()->result_array();



		/*// 统计系统的各个标签的使用量TOP20 
		$top_tags['usage'] = array();
		// 需要统计的表 [rl_event_tag, rl_h5_page_tag, rl_media_tag, rl_wb_rule_tag, rl_wb_user_timeline_tag, rl_wx_rule_tag]
		$tables = array('event', 'h5_page', 'media', 'wb_rule', 'wb_user_timeline', 'wx_rule');
		foreach ($tables as $table) {
			$tags[$table] = $this->db->select("COUNT(*) AS num, tag_id")
				->from('rl_' . $table . '_tag')
				->group_by('tag_id')
				->order_by('num', 'DESC')
				->get()->result_array();
		}

		// $usage_rank = array ();
		if ($tags) {
			foreach ($tags as $table => $tag) {
				if ($tag) {
					foreach ($tag as $v) {
						if ( ! isset($usage_rank[$v['tag_id']])) {
							$usage_rank[$v['tag_id']] = array (
								'num'	 => $v['num'],
								'tag_id' => $v['tag_id'],
								$table	 => $v['num'],
							);
						} else {
							$usage_rank[$v['tag_id']]['num'] += $v['num'];
							$usage_rank[$v['tag_id']][$table] = $v['num'];
						}
					}
				}
			}
		}*/

		// 按照num排序，取出前20
		if ($top_tags['trigger']) 
		{
			// 去掉基数为零的标签
			foreach ($top_tags['trigger'] as $k => $v) 
			{
				if ($v['num'] == 0)
				{
					unset($top_tags['trigger'][$k]);
				}
				/*else
				{
					$top_tags['trigger'][$k]['num'] = intval($top_tags['trigger'][$k]['num']);
				}*/
			}
			$this->meret($top_tags);
		}
		else 
		{
			$this->meret(NULL, MERET_EMPTY, '暂时没有标签排名数据！');
		}

		return ;
	}

	/* 单个标签与用户触发量比值 */
	public function tag_usage () 
	{
		// 
	}

	/* 标签触发权重 */
	public function tag_weight () 
	{
		// 
	}

}

/* End of file stats_tag.php */
/* Location: ./application/controllers/common/stats_tag.php */