<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微信自定义菜单点击量统计
*/

class Stats_menu extends ME_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->helper("common");
        $wx_aid = $this->session->userdata('wx_aid');
        $start = $this->input->get("start", TRUE);
        $end = $this->input->get("end", TRUE);

        $date_arr = make_date_start_before_end($start, $end);
        // 给结束时间增加一天，在sql的时候直接用'<'判断
        // 否则在开始和结束日期不相同的情况下写'<='
        // 当相同的时候得再写一个where判断
        $start_date = $date_arr['start'];
        $end_date = date("Y-m-d", strtotime($date_arr['end']) + 86400);

        $detail = $this->get_menu_detail($wx_aid, $start_date, $end_date);

        if ( ! $detail)
            $this->meret(NULL, MERET_EMPTY, '没有自定义菜单点击量统计数据！');
        else
            $this->meret(array('detail' => $detail));

        return;
    }

    /* 获取菜单点击量信息 */
    public function get_menu_detail($wx_aid, $start_date, $end_date)
    {
        $this->db->select('count(wmd.id) AS num, wmd.menu_name')
            ->from('wx_menu_detail wmd')
            ->where(array('wx_aid' => $wx_aid, 'click_time >=' => $start_date, 'click_time <' => $end_date))
            ->group_by('menu_name')
            ->order_by('num', 'desc');
        $detail = $this->db->get()->result_array();
        return $detail;
    }

}

/* End of file stats_rule.php */
/* Location: ./application/controllers/mex/stats_rule.php */
