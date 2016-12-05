<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 粉丝信息
*/
class Stats_follower extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->config('meo/stats_follower');
		$this->load->model('meo/stats_follower_model', 'model');
	}

	/* 获取粉丝多维度统计信息数据 */
	public function follower_stats () 
	{

		$p = $this->_get_input();

		if (is_string($p)) {
			$this->meret(NULL, MERET_BADREQUEST, $p);
			return ;
		}

		$stats = $this->model->get_follower_stats($p['wb_aid'], $p['start'], $p['end']);

		if ($stats) 
			$this->meret($stats);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有选定日期内的数据记录！');
	}

	/* 记录粉丝多维度统计数据 [按日记录] */
	public function log_follower_stats ()
	{

		$res = $this->model->log_follower_stats();

		if (is_string($res)) 
			echo $res;
		else 
			echo "更新了[{$res}]条记录！";
	}

	/* 获取粉丝交互活跃时间 */
	public function followers_active_timelime () 
	{
		return array();
	}

	/* 粉丝信息报表导出 */
	public function follower_stats_export () 
	{
		$p = $this->_get_input();

		if (is_string($p)) {
			$this->meret(NULL, MERET_BADREQUEST, $p);
			return ;
		}

		$stats = $this->model->get_follower_stats($p['wb_aid'], $p['start'], $p['end']);

		if ( ! empty($stats)) {
			/* 载入PHPExcel样式配置文件 */
			$this->load->config('common/phpexcel_style');
		} else {
			$this->meret(NULL, MERET_EMPTY, '没有选定日期内的数据记录！');
		}
	}

	private function _get_input () 
	{
		$wb_aid = intval($this->input->get_post('aid'));
		$start	= trim($this->input->get_post('start'));
		$end	= trim($this->input->get_post('end'));

		if ($wb_aid < 1) 
			$wb_aid = $this->session->userdata('wb_aid');

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
			'wb_aid' => $wb_aid, 
			'start' => date('Y-m-d', $start_time), 
			'end' => date('Y-m-d', $end_time)
		);
	}

}

/* End of file stats_follower.php */
/* Location: ./application/controllers/meo/stats_follower.php */