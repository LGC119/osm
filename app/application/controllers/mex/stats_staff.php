<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 工作量
*/

/* TODO:系统的信息分类比例 */
class Stats_staff extends ME_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->model('mex/stats_model', 'stats');
        $this->load->helper("common");

        $start = $this->input->get("start", TRUE);
        $end = $this->input->get("end", TRUE);

        $date_arr = make_date_start_before_end($start, $end);
        // 给结束时间增加一天，在sql的时候直接用'<'判断
        // 否则在开始和结束日期不相同的情况下写'<='
        // 当相同的时候得再写一个where判断
        $start_date = $date_arr['start'];
        $end_date = date("Y-m-d", strtotime($date_arr['end']) + 86400);

        $staff_info = $this->stats->get_staff_info($start_date, $end_date);

        $data = array(
            'staff_info' => $staff_info
            );

        $this->meret($data);
        return;

    }

    /* 单个员工工作量统计 */
    public function staff_operation ($staff_id = 0)
    {
        if ( ! $staff_id)
            $staff_id = intval($this->input->get('staff_id'));
        if ( ! $staff_id)
            $staff_id = $this->sid;

        /* 获取设定日期 */
        $date_range = $this->_get_date();
        if (is_string($date_range)) {
            $this->meret(NULL, MERET_BADREQUEST, $date_range);
            return ;
        }
        /* 获取员工处理数据 */
        $stats = $this->db->select("count(*) AS num, operation, date_format(created_at, '%Y-%m-%d') AS time", FALSE)
            ->from('wb_operation_history')
            ->where('staff_id', $staff_id)
            ->where(" date_format(created_at, '%Y-%m-%d') BETWEEN '{$date_range['start']}' AND '{$date_range['end']}'", NULL, FALSE)
            ->group_by('operation, time')
            ->order_by('time', 'asc')
            ->get()->result_array();

        if ($stats)
            $this->meret($stats);
        else
            $this->meret(NULL, MERET_EMPTY, '没有选定时段内的统计数据！');

        return ;
    }

    /* 单个员工账号的分类信息统计 */
    public function staff_category ($staff_id = 0)
    {
        if ( ! $staff_id)
            $staff_id = intval($this->input->get('staff_id'));
        if ( ! $staff_id)
            $staff_id = $this->sid;

        /* 获取设定日期 */
        $date_range = $this->_get_date();
        if (is_string($date_range)) {
            $this->meret(NULL, MERET_BADREQUEST, $date_range);
            return ;
        }
        $this->load->config('common/operation');
        /* 获取员工分类操作的信息 */
        $stats = $this->db->select("count(*) AS num, reason AS cate, date_format(created_at, '%Y-%m-%d') AS time", FALSE)
            ->from('wb_operation_history')
            ->where(array('staff_id'=>$staff_id, 'operation'=>CATEGORIZE))
            ->where("created_at BETWEEN '{$date_range['start']}' AND '{$date_range['end']}'", NULL, FALSE)
            ->group_by('reason, time')
            ->order_by('time', 'asc')
            ->get()->result_array();

        if ($stats)
            $this->meret($stats);
        else
            $this->meret(NULL, MERET_EMPTY, '没有分类信息统计数据！');

        return ;
    }

    /* 系统所有员工的处理量趋势 */
    public function operation_stats ()
    {
        /* 获取设定日期 */
        $date_range = $this->_get_date();
        if (is_string($date_range)) {
            $this->meret(NULL, MERET_BADREQUEST, $date_range);
            return ;
        }
        /* 获取所有员工处理数据 */
        $stats = $this->db->select("count(*) AS num, operation, date_format(created_at, '%Y-%m-%d') AS time", FALSE)
            ->from('wb_operation_history')
            ->where(" date_format(created_at, '%Y-%m-%d') BETWEEN '{$date_range['start']}' AND '{$date_range['end']}'", NULL, FALSE)
            ->group_by('operation, time')
            ->order_by('time', 'asc')
            ->get()->result_array();

        if ($stats)
            $this->meret($stats);
        else
            $this->meret(NULL, MERET_EMPTY, '没有选定时段内的统计数据！');

        return ;
    }

    /* 系统的信息分类比例 */
    public function category_stats ()
    {
        //
    }

    /* 获取请求的时间区间 */
    private function _get_date ()
    {

        $start  = trim($this->input->get_post('start'));
        $end    = trim($this->input->get_post('end'));

        $date_format = '/^[\d]{4}-[\d]{2}-[\d]{2}$/';

        if ($start == '')
            return '请填写起始日期！';

        if (($start != '' && ! preg_match($date_format, $start)) OR
            ($end != '' && ! preg_match($date_format, $end)))
            return '请填写正确的起止日期格式 eg:[2014-06-18]！';

        if ($end == '')
            $end = $start;

        $today = time();
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        if ($end_time > $today)
            return '时间设定不能大于当前日期！';

        if ($end_time < $start_time)
            return '结束日期不能小于起始日期！';

        return array(
            'start' => $start,
            'end' => $end
        );
    }

}

/* End of file stats_staff.php */
/* Location: ./application/controllers/mex/stats_staff.php */
