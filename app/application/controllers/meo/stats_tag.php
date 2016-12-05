<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 标签
*/
class Stats_tag extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->load->model('meo/stats_model', 'stats');
		$tag_info = $this->stats->get_tag_info();

		if ($tag_info)
			$this->meret($tag_info);
		else
			$this->meret(NULL, MERET_EMPTY, '没有标签统计数据');

		return;
	}

	/* 获取TOP20的标签 */
	public function top_tags ()
	{
		$limit = intval($this->input->get('limit'));
		if ($limit < 10 OR $limit > 100)
			$limit = 20;
		/* 用户触发量TOP20 */
		$top_tags['trigger'] = $this->db->select("tag_id, t.tag_name, SUM(link_tag_hits + rule_tag_hits + event_tag_hits + timeline_tag_hits) AS num", FALSE)
			->from('rl_wb_user_tag rwut')
			->join('tag t', 't.id = rwut.tag_id')
			->group_by('tag_id')
			->order_by('num', 'DESC')
			->limit(20)
			->get()->result_array();

		/* 统计系统的各个标签的使用量TOP20 */
		$top_tags['usage'] = array();
		/* 需要统计的表 [rl_event_tag, rl_h5_page_tag, rl_media_tag, rl_wb_rule_tag, rl_wb_user_timeline_tag, rl_wx_rule_tag] */
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
		}

		/* 按照num排序，取出前20 */

		if ($top_tags['trigger'] OR $top_tags['usage'])
			$this->meret($top_tags);
		else
			$this->meret(NULL, MERET_EMPTY, '暂时没有标签排名数据！');

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
/* Location: ./application/controllers/meo/stats_tag.php */
