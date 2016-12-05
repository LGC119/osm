<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微信自动回复规则触发
*/

class Stats_rule extends ME_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('mex/stats_model', 'stats');
    }

    public function index()
    {
        $this->load->helper("common");
        $wx_aid = $this->input->get("wx_aid", TRUE);
        $start = $this->input->get("start", TRUE);
        $end = $this->input->get("end", TRUE);

        $date_arr = make_date_start_before_end($start, $end);
        // 给结束时间增加一天，在sql的时候直接用'<'判断
        // 否则在开始和结束日期不相同的情况下写'<='
        // 当相同的时候得再写一个where判断
        $start_date = $date_arr['start'];
        $end_date = date("Y-m-d", strtotime($date_arr['end']) + 86400);

        // $rule_number = $this->stats->get_rule_number($wx_aid, $start_date, $end_date);
        $rule_number = $this->stats->get_wx_rule($wx_aid, $start_date, $end_date);

        $pm_keyword_number = $this->stats->get_pm_keyword_number($wx_aid, $start_date, $end_date);

        if ( ! $rule_number OR ! $pm_keyword_number)
            $this->meret(NULL, MERET_EMPTY, '没有自动回复规则统计数据！');
        else
            $this->meret(array('rule_number' => $rule_number, 'pm_keyword_number' => $pm_keyword_number));

        return;
    }

}

/* End of file stats_rule.php */
/* Location: ./application/controllers/mex/stats_rule.php */
