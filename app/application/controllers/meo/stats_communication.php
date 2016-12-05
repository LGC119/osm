<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 微博内容
*/

/* TODO: 回复方式统计加上自动回复的数据 */
class Stats_communication extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->load->model('meo/stats_model', 'stats');
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

		// 一、二级分类的数组
		$category_info_number = $this->stats->get_category_info_number($wb_aid, $start_date, $end_date);

		// 所有类型的交互量，所有类型的不同时间段的交互量
		$interact_number = $this->stats->get_interact_number($wb_aid, $start_date, $end_date);

		$data = array(
			'category_info_number' => $category_info_number,
			'interact_number' => $interact_number
			);

		$this->meret($data);
		return;

	}

	/* 分类数据统计 */
	public function category_stats ()
	{
		/* 获取请求的分类值 */
		$cates = trim($this->input->get_post('cates'));

		$cat_arr = explode(',', $cates);

		if (empty($cat_arr)) {
			$this->meret(NULL, MERET_BADREQUEST, '请选择分类！');
			return ;
		}

		$cat_len = count($cat_arr);
		if ($cat_len > 5) {
			$this->meret(NULL, MERET_BADREQUEST, '一次不能选择超过5个分类！');
			return ;
		}

		// 获取选中分类的使用量
		$this->db->from('rl_wb_communication_category');
		for ($i = 0, $empty = TRUE; $i < $cat_len; $i++) {
			$cat = intval($cat_arr[$i]);
			if ($cat > 0) {
				$this->db->where("CONCAT(', ', cate_ids, ',') LIKE '%, {$cat}%,'", NULL, FALSE);
				$empty = FALSE;
			}
		}

		if ( ! $empty) {
			$cat_num = $this->db->get()->num_rows();
			$this->meret($cat_num);
		} else {
			$this->meret(NULL, MERET_BADREQUEST, '分类参数请求不正确！');
		}
	}

	/* 回复方式数据统计 */
	public function reply_type_stats ()
	{
		$stats = $this->db->select('count(*) AS num, reply_type')
			->from('staff_reply')
			->group_by('reply_type')
			->order_by('num', 'desc')
			->get()->result_array();

		if ($stats)
			$this->meret($stats);
		else
			$this->meret(NULL, MERET_EMPTY, '没有统计数据！');
	}

}

/* End of file stats_communication.php */
/* Location: ./application/controllers/meo/stats_communication.php */
