<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 系统登录登出控制器
*/
class Pao extends Base_Controller {

	/* 跑一下丢失的已处理工作量 */
	public function index()
	{
		/* 最近一条记录之前的 */
		$last_reply_record = $this->db->select('created_at')
			->where('operation', 3)
			->order_by('id', 'asc')
			->get('wx_operation_history')->row_array();

		$time = $last_reply_record ? $last_reply_record['created_at'] : '0000-00-00 00:00:00';
		var_dump($time);

		$logs = $this->db->select('company_id, staff_id, wx_id, status, time')
			->where(array('directory'=>'mex', 'method'=>'reply'))
			->get('log')->result_array();

		if (count($logs) == 0) return;
		/* 循环处理日志 */
		foreach ($logs as $log) {

			$status = json_decode($log['status'], TRUE);
			$data = $status['data'];
			if ($status['code'] != 200 OR ! isset($data['cmn_id'])) continue;

			$oh = array (
				'company_id' 		=> $log['company_id'], 
				'wx_aid' 			=> $log['wx_id'], 
				'created_at' 		=> $log['time'], 
				'staff_id' 			=> $data['staff_id'], 
				'staff_name' 		=> $data['staff_name'], 
				'cmn_id' 			=> $data['cmn_id'], 
				'assign_status' 	=> 0, 
				'audit_status' 		=> 0, 
				'operation_status' 	=> 3, 
				'operation' 		=> 3, 
				'reason' 			=> $data['type']
			);
			
			$this->db->insert('wx_operation_history', $oh);
		}
	}

}

/* End of file gate.php */
/* Location: ./application/controllers/gate.php */
