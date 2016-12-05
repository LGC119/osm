<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Communication extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		/* 可使用 $this->model 调用meo/communication里的函数 */
		$this->load->model('meo/communication_model', 'model');
		$this->load->config('common/operation');
	}

	/* 根据请求参数，获取communication信息 */
	public function get ($type, $status) 
	{
		$limit = $this->_get_limit($type, $status);
		$g = $this->input->get(NULL, TRUE);
		$g['type'] = $type;
		$g['status'] = $status;
		$mentions = $this->model->get_communications($g, $limit);

		if ( empty($mentions)) 
			$this->meret(NULL, 204);
		else 
			$this->meret($mentions);
	}

	/* 获取当日微博微信挂起任务列表, 和过期任务的数量 */
	public function get_suspending () 
	{
		$datetime = date('Y-m-d H:i:s');
		$tomorrow = date('Y-m-d H:i:s', strtotime('tomorrow'));

		$tasks = array ();
		/* 过期任务统计 */
		$delayed_where = array (
			'wc.operation_status'=>5, 
			'wc.company_id'=>$this->cid, 
			's.remind_time <='=>$datetime, 
			's.staff_id'=>$this->sid
		);

		$wb_delayed = $this->db->from('wb_communication wc')
			->join('suspending s', 'wc.id = s.cmn_id', 'left')
			->where($delayed_where)->get()->num_rows();

		$wx_delayed = $this->db->from('wx_communication wc')
			->join('suspending s', 'wc.id = s.cmn_id', 'left')
			->where($delayed_where)->get()->num_rows();

		$where = array (
			'wc.operation_status'=>5, 
			'wc.company_id'=>$this->cid, 
			's.remind_time >'=>$datetime, 
			's.remind_time <'=>$tomorrow, 
			's.staff_id' => $this->sid
		);
		/* 今日任务计数统计 */
		$wb_count = $this->db->from('wb_communication wc')
			->join('suspending s', 'wc.id = s.cmn_id', 'left')
			->where($where)->get()->num_rows();
		$wx_count = $this->db->from('wx_communication wc')
			->join('suspending s', 'wc.id = s.cmn_id', 'left')
			->where($where)->get()->num_rows();

		if ($wb_count + $wx_count > 0) { /* 获取距当前时间最近的20条挂起任务 */
			$wb_tasks = $this->db->select('s.id, s.aid, s.remind_time AS rm_time, s.description AS rm_desc, s.cmn_id, s.type')
				->from('wb_communication wc')
				->join('suspending s', 'wc.id = s.cmn_id', 'left')
				->where($where)
				->order_by('s.remind_time', 'ASC')
				->limit(20)->get()->result_array();
			$wx_tasks = $this->db->select('s.id, s.aid, s.remind_time AS rm_time, s.description AS rm_desc, s.cmn_id, s.type')
				->from('wx_communication wc')
				->join('suspending s', 'wc.id = s.cmn_id', 'left')
				->where($where)
				->order_by('s.remind_time', 'ASC')
				->limit(20)->get()->result_array();

			if (count($wb_tasks) > 0)
				$tasks = array_merge($tasks, $wb_tasks);
			if (count($wx_tasks) > 0)
				$tasks = array_merge($tasks, $wx_tasks);
		}

		$data = array (
			'count' => array ('wb'=>$wb_count, 'wx'=>$wx_count),
			'delayed' => array ('wb'=>$wb_delayed, 'wx'=>$wx_delayed),
			'pintops' => $this->get_pintops(), // 待处理的置顶微博微信消息
			'tasks' => $tasks
		);
		$this->meret($data);
	}

	/* 获取置顶的待处理量 */
	public function get_pintops () 
	{
		$where = array ('operation_status'=>0, 'is_top'=>1);

		$wb_pintops = $this->db->where($where)
			->get('wb_communication')->num_rows();

		$wx_pintops = $this->db->where($where)
			->get('wx_communication')->num_rows();

		return array (
			'wb' => $wb_pintops ? $wb_pintops : 0, 
			'wx' => $wx_pintops ? $wx_pintops : 0
		);
	}

	/* 清空垃圾挂起的任务 */
	public function clear_suspending () 
	{
		$this->db->where(array ('wc.operation_status <>'=>5, 's.type'=>'wb'))
			->join('wb_communication wc', 'wc.id = s.cmn_id')
			->delete('suspending s');

		$this->db->where(array ('wc.operation_status <>'=>5, 's.type'=>'wx'))
			->join('wx_communication wc', 'wc.id = s.cmn_id')
			->delete('suspending s');
	}

	private function _get_limit ()
	{
		$page = intval($this->input->get_post('current_page'));
		$perpage = intval($this->input->get_post('items_per_page'));

		$page = $page > 0 ? $page : 0;
		$perpage = ($perpage > 0 && $perpage < 80) ? $perpage : 20;

		/* limit参数 */
		return array('limit' => $perpage, 'start' => ($page - 1) * $perpage, 'current_page' => $page);
	}

}

/* End of file communication.php */
/* Location: ./application/controllers/meo/communication.php */