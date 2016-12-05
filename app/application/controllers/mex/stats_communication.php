<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微信消息
*/

class Stats_communication extends ME_Controller {

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

        $category_info_number = $this->stats->get_category_info_number($wx_aid, $start_date, $end_date);

        $interact_number = $this->stats->get_interact_number($wx_aid, $start_date, $end_date);

        $data = array(
            'category_info_number' => $category_info_number,
            'interact_number' => $interact_number
            );
        $this->meret($data);
        return;

    }

}

/* End of file stats_communication.php */
/* Location: ./application/controllers/mex/stats_communication.php */
