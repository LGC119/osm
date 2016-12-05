<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微博用户
*/
class Stats_user extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('meo/stats_model', 'stats');
	}

	public function index()
	{

		$this->load->helper("common");
		$wb_aid = $this->input->get("wb_aid", TRUE);
		$start = $this->input->get("start", TRUE);
		$end = $this->input->get("end", TRUE);

		$date_arr = make_date_start_before_end($start, $end);
		// 给结束时间增加一天，在sql的时候直接用'<'判断
        // 否则在开始和结束日期不相同的情况下写'<='
        // 当相同的时候得再写一个where判断
		$start_date = $date_arr['start'];
		$end_date = date("Y-m-d", strtotime($date_arr['end']) + 86400);

		$gender_number = $this->get_gender_number($wb_aid, $start_date, $end_date);

		$fans_verified_type = $this->get_fans_verified_type($wb_aid, $start_date, $end_date);

		$location_distribution = $this->get_location_distribution($wb_aid, $start_date, $end_date);

		$fans_increasement = $this->get_fans_increasement($wb_aid, $start_date, $end_date);

		$data = array(
			'gender' => $gender_number,
			'fans_type' => $fans_verified_type,
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

		if (empty($rst))
		{
			$this->meret(NULL, MERET_EMPTY, '暂无统计数据！');
			exit;
		}
		$x = $y = array();

		foreach ($rst as $v)
		{
			$x[] = $v['screen_name'];
			$y[] = intval($v['commu_count']);
		}


		$data = array(
			'x' => $x,
			'y' => $y,
			'xtitle' => '微博交互用户TOP10',
			'ytitle' => '交互量',
			'data_title'=>'交互用户'
		);
		$this->meret($data);
	}

	public function get_gender_number($wb_aid, $start_date, $end_date)
	{
		$gender_number = $this->stats->get_gender_number($wb_aid, $start_date, $end_date);
		return $gender_number;
	}

	public function get_fans_verified_type($wb_aid, $start_date, $end_date)
	{
		$fans_verified_type = $this->stats->get_fans_verified_type($wb_aid, $start_date, $end_date);
		return $fans_verified_type;
	}

	public function get_location_distribution($wb_aid, $start_date, $end_date)
	{
		$location_distribution = $this->stats->get_location_distribution($wb_aid, $start_date, $end_date);
		return $location_distribution;
	}

	public function get_fans_increasement($wb_aids, $start_date, $end_date)
	{
		$fans_increasement = $this->stats->get_fans_increasement($wb_aids, $start_date, $end_date);
		return $fans_increasement;
	}

}

/* End of file stats_user.php */
/* Location: ./application/controllers/meo/stats_user.php */
