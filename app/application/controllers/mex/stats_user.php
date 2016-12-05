<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微信用户
*/

class Stats_user extends ME_Controller {

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

		$gender_number = $this->stats->get_wx_user_data($wx_aid, 'sex', $start_date, $end_date, "gender");

		$location_distribution = $this->stats->get_wx_user_data($wx_aid, 'province', $start_date, $end_date, "location");

		$fans_increasement = $this->stats->get_fans_increasement($wx_aid, $start_date, $end_date);

		$data = array(
			'gender' => $gender_number,
			'location_distribution' => $location_distribution,
			'fans_increasement' => $fans_increasement
			);

		$this->meret($data);
		return;

	}

	// 交互最多的前10用户
	public function top_interact_users()
	{
		$rst = $this->stats->top_interact_users();

		if (empty($rst)) {$this->meret(NULL, MERET_EMPTY, '没有统计数据！'); exit;}

		$x = $y = array();

		foreach ($rst as $v)
		{
			$x[] = $v['nickname'];
			$y[] = intval($v['commu_count']);
		}


		$data = array(
			'x' => $x,
			'y' => $y,
			'xtitle' => '微信交互用户TOP10',
			'ytitle' => '交互量',
			'data_title'=>'交互用户'
		);

		$this->meret($data);
	}

}

/* End of file stats_user.php */
/* Location: ./application/controllers/mex/stats_user.php */
