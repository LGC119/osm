<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 关键词
*/
class Stats_keyword extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
	}

    public function index()
    {
        $this->load->model('meo/stats_model', 'stats');
        $this->load->helper("common");

        $start = $this->input->get("start", TRUE);
        $end = $this->input->get("end", TRUE);

        $date_arr = make_date_start_before_end($start, $end);
        // 给结束时间增加一天，在sql的时候直接用'<'判断
        // 否则在开始和结束日期不相同的情况下写'<='
        // 当相同的时候得再写一个where判断
        $start_date = $date_arr['start'];
        $end_date = date("Y-m-d", strtotime($date_arr['end']) + 86400);

        $keyword_rank = $this->stats->get_keyword_rank($start_date, $end_date);

	    if ($keyword_rank)
	        $this->meret($keyword_rank);
	    else
		    $this->meret(NULL, MERET_EMPTY, '没有关键词统计数据！');

        return;
    }

	/* 抓取信息最高的关键词 */
	public function top_keywords ()
	{
		//
	}

}

/* End of file stats_keyword.php */
/* Location: ./application/controllers/meo/stats_keyword.php */
