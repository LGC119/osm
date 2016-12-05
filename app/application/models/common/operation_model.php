<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Operation模型基类 (处理)
*/
class OperationBase extends ME_model
{
	
	protected $staff; 				// 操作员工信息 {sid:员工ID, sname:员工姓名, cid:公司ID, cname:公司名称}

	protected $_status_key; 		// 'operation_status'
	protected $_cmu_source; 		// 'wb' | 'wx'
	protected $_apiObj; 			// 回复消息使用的API接口对象

	protected $_cmn_info; 			// 交流基本信息 [包含ID，type等]

	public function __construct()
	{
		parent::__construct();
	}

	/* 初始化操作人信息 */
	public function initOperator ($sid)
	{
		$staff = $this->db->select('s.id AS sid, s.name AS sname, c.id AS cid, c.name AS cname')
			->from('staff s')
			->join('company c', 's.company_id = c.id', 'left')
			->where('s.id', $sid)
			->get()->row_array();

		if ($staff) 
			$this->staff = $staff;
		else 
			exit('cannot authorize operator [ID:' . $sid . '] from company [ID:' . $cid . ']');
	}

	/* 初始化接口对象 */
	public function initApi () {}

	/* 修改记录主状态 */
	public function change_status ($cmn_id, $operation, $reason = '') 
	{

		$op_changes = $this->config->item('op_changes');

		if (array_key_exists($operation, $op_changes)) 
		{
			$changes = $op_changes[$operation];
			if (isset($changes['operation_status'])) 
			{
				$this->db->where('id', $cmn_id)
					->set('operation_status', $changes['operation_status'])
					->update($this->_cmu_source . '_communication');
				// if ( ! $this->db->affected_rows())
				// 	return 'operation failed !';
			}
			$cmn = $this->db->select('id, ' . $this->_cmu_source . '_aid, is_deleted')
				->from($this->_cmu_source . '_communication')
				->where(array('id'=>$cmn_id))
				->get()->row_array();

			$op = array('code'=>$operation, 'reason'=>$reason);
			return $this->_log_operation($cmn, $op, $changes);
		}
		else 
		{
			return FALSE;
		}
	}

	/* 记录回复信息：分别实现 */
	public function log_reply () {}

	/* 回复一条信息：分别实现 */
	public function reply () {}

	/* 信息分类函数 */
	public function categorize ($cmn_id, $cat_arr, $re_categorize = FALSE)
	{
		$cat_info = $this->db->select("GROUP_CONCAT(cat_name SEPARATOR ',') AS cn, GROUP_CONCAT(id SEPARATOR ',') AS ci", FALSE)
			->from('category')
			->where(array('parent_id >'=>0, 'company_id'=>$this->staff['cid']))
			->where_in('id', $cat_arr)
			->group_by('company_id')
			->get()->row_array();

		if (count(explode(',', $cat_info['ci'])) < count($cat_arr)) 
			return '选择分类信息不正确！';

		$data = array();
		foreach ($cat_arr as $cat_id) 
			$data[] = array( 'cmn_id' => $cmn_id, 'cat_id' => $cat_id );

		$this->db->where('cmn_id', $cmn_id)->delete('rl_'.$this->_cmu_source.'_communication_category');
		$this->db->insert_batch('rl_' . $this->_cmu_source . '_communication_category', $data);
		$operation = $re_categorize ? RECATEGORIZE : CATEGORIZE; // 分类还是重分类
		if ($this->db->affected_rows()) 
			return $this->model->change_status($cmn_id, $operation, $cat_info['cn']);	// 修改记录状态，并记录分类名称
		else 
			return '操作失败，请稍后尝试！';
	}

	/* 置顶 */
	public function pin ($cmn_id) 
	{
		$this->db->where(array('id'=>$cmn_id))->update($this->_cmu_source . '_communication', array('is_top'=>1));

		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	/* 取消置顶 */
	public function unpin ($cmn_id) 
	{
		$this->db->where(array('id'=>$cmn_id))->update($this->_cmu_source . '_communication', array('is_top'=>0));

		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	/* 挂起 */
	public function suspend ($cmn_id, $set_time, $desc = '')
	{
		$set_time = strtotime($set_time);

		// if ($set_time - time() < 10) 
		if ($set_time - time() < 1800) 
			return '设定时间至少在当前时间30分钟后！';

		$cmn_info = $this->db->select('id, ' . $this->_cmu_source . '_aid, operation_status, is_deleted')
			->from($this->_cmu_source . '_communication')
			->where('id', $cmn_id)
			->get()->row_array();

		if ( ! $cmn_info OR (isset($cmn_info['is_deleted']) && $cmn_info['is_deleted'] == 1)) 
			return '该条微博不存在或已被删除！';
		
		$data = array(
			'aid' 			=> isset($cmn_info['wb_aid']) ? $cmn_info['wb_aid'] : $cmn_info['wx_aid'],
			'company_id' 	=> $this->staff['cid'],
			'staff_id' 		=> $this->staff['sid'],
			'staff_name' 	=> $this->staff['sname'],
			'remind_time' 	=> date('Y-m-d H:i:s', $set_time),
			'description' 	=> addslashes($desc),
			'cmn_id' 		=> $cmn_id, 
			'type' 			=> $this->_cmu_source, 
			'status' 		=> $cmn_info[$this->_status_key]
		);

		$this->db->insert('suspending', $data);

		if ($this->db->affected_rows()) 
		{
			$this->change_status($cmn_id, SUSPEND, date('Y-m-d H:i:s', $set_time));
			return $this->db->affected_rows() ? $data : '挂起失败，请稍后尝试！';
		}
	}

	/* 修改挂起 */
	public function change_suspend ($suspending_id, $set_time, $desc = '') 
	{
		$set_time = strtotime($set_time);

		if ($set_time - time() < 1800) 
			return '设定时间至少在当前时间30分钟后！';

		// $cmn_info = $this->db->select('id, ' . $this->_cmu_source . '_aid, operation_status, is_deleted')
		// 	->from($this->_cmu_source . '_communication')
		// 	->where('id', $cmn_id)
		// 	->get()->row_array();

		// if ( ! $cmn_info OR (isset($cmn_info['is_deleted']) && $cmn_info['is_deleted'] == 1)) 
		// 	return '该条微博不存在或已被删除！';
		
		$data = array(
			'remind_time' 	=> date('Y-m-d H:i:s', $set_time),
			'description' 	=> $desc,
		);
		$this->db->where('id', $suspending_id)->update('suspending', $data);
		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	/* 取消挂起 [sid - 挂起表中的ID] */
	/* 取消消息记录的挂起状态，并返回挂起前状态 */
	public function unsuspend ($sid) 
	{
		$sid = intval($sid);
		$suspending = $this->db->select('aid, cmn_id, status, type')
			->from('suspending')
			->where(array('company_id'=>$this->staff['cid'], 'id'=>$sid))
			->get()->row_array();

		if ( ! $suspending)
			return '没有找到该条记录！';

		// 将记录置为挂起前状态
		$this->db->set($this->_status_key, $suspending['status'])
			->where('id', $suspending['cmn_id'])
			->update($this->_cmu_source . '_communication');

		// $this->db->set('is_deleted', 1)->where(array('id'=>$sid))->update('suspending');

		if ($this->db->affected_rows()) {
			/* 将记录删除，或添加删除字段，将字段置为1 */
			$this->db->where(array('id'=>$sid))->delete('suspending'); 
			if ( ! $this->db->affected_rows()) 
				return '删除记录失败，请再次执行取消提醒操作！';

			$this->_log_operation(array('id'=>$suspending['cmn_id'], $this->_cmu_source . '_aid'=>$suspending['aid']), 
				array('code'=>UNSUSPEND, 'reason'=>$suspending['status']), 
				array('operation_status'=>$suspending['status']));
			return $suspending;
		} else {
			return '删除失败，请稍后尝试！';
		}
	}

    /**
     * 将微博分配给某个员工
     * @param $operator_id 操作人员的id
     * @param $cmn_id 当前微博的id
     * @param $staff_id 分配给哪个员工
     *
     * @return
     */
    public function assign($cmn_id, $staff_id){

		$untouched = $this->db->select($this->_cmu_source.'_aid as aid')
			->from($this->_cmu_source.'_communication')
			->where('id', $cmn_id)
			->get()->row_array();

        $staff = $this->db->select('name')
			->from('staff')
			->where('id', $staff_id)
			->get()->row_array();
        //更新communication表
        //$rs = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$staff_id, 'updated_at'=>date('Y-m-d H:i:s')));
        // $rs = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$staff_id,'operation_status'=>1));
			$rs = $this->wb_random_allot($cmn_id);
        //如果更新成功，记录历史操作
        if($rs){
            $this->_log_operation(array('id'=>$cmn_id, $this->_cmu_source.'_aid'=>$untouched['aid']), array('code'=>ASSIGN, 'reason'=>$staff['name']), array('assign_status'=>1, 'staff_name'=>$this->staff['sname']));
        }else{
            return FALSE;
        }
            return $rs;
    }

	/*
	** 记录操作历史
	** @param $operation	操作信息的数组
	** @param $status		操作信息的数组
	** 		operation = array('code'=>操作代码, 'reason'=>动作原因)
	** 		status = array('assign_status'=>分配状态, 'audit_status'=>审核状态, 'operation_status'=>记录主状态)
	*/
	private function _log_operation ($cmn, $op, $changes) 
	{
		$history = array_merge($changes, array(
			'company_id' => $this->cid, 
			$this->_cmu_source . '_aid' => $cmn[$this->_cmu_source . '_aid'],
			'created_at' => date('Y-m-d H:i:s'),
			'staff_id' => $this->sid,
			'cmn_id' => $cmn['id'],
			'operation' => $op['code'],
			// 'is_deleted' => $cmn['is_deleted'],
			'reason' => $op['reason']
		));

		return $this->db->insert($this->_cmu_source . '_operation_history', $history) ? TRUE : FALSE;
	}

	/**
     * 将微信分配给某个员工
     * @param $operator_id 操作人员的id
     * @param $cmn_id 当前微博的id
     * @param $staff_id 分配给哪个员工
     *
     * @return
     */
    public function wx_assign($cmn_id, $staff_id){

		$untouched = $this->db->select($this->_cmu_source.'_aid as aid')
			->from($this->_cmu_source.'_communication')
			->where('id', $cmn_id)
			->get()->row_array();

        $staff = $this->db->select('name')
			->from('staff')
			->where('id', $staff_id)
			->get()->row_array();
        //更新communication表
        //$rs = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$staff_id, 'updated_at'=>date('Y-m-d H:i:s')));
        $rs = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$staff_id,'operation_status'=>1));
		// $rs = $this->wx_random_allot($cmn_id);
        //如果更新成功，记录历史操作
        if($rs){
            $this->_log_operation(array('id'=>$cmn_id, $this->_cmu_source.'_aid'=>$untouched['aid']), array('code'=>ASSIGN, 'reason'=>$rs), array('assign_status'=>1, 'staff_name'=>$this->staff['sname']));
        }else{
            return FALSE;
        }

            return $rs;
    }

	//随机分配
	public function wx_random_allot($cmn_id){
		//判断是否有相应权限的csr在线
			$this->load->database();
			$where = array(
					'state'=>1,
					'do_message'=>1,
					'is_deleted'=>0
				);
			$state_on = $this->db->select('id')
						->from('staff')
						->where($where)
						->get()->result_array();
			// var_dump($state_on);
		if($state_on == ''){
			return '没有人员在线';
		}else{
			//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						foreach($state_on as $val){
							$sql = "select count(c.id),s.id,s.name from me_wx_communication as c left join me_staff as s on c.staff_id = s.id where s.id = {$val['id']} and c.operation_status = 1";
							// echo $sql;
							$query = $this->db->query($sql);
							if($query->num_rows()>0){
								$communication_num[] = $query->result_array()[0];
							}else{
								$communication_error = '查询错误';
								return $communication;
							}
						}
						// var_dump($communication_num);

						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						if($min_num['id'] == ''){
							$state_on_num = count($state_on);
							$arr_num = mt_rand(0,$state_on_num);
							$res = $this->db->where('id', $cmn_id)->update('wx_communication', array('staff_id'=>$state_on[$arr_num]['id'],'operation_status'=>1));
							return $res;
							exit;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$res = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$min_num_person,'operation_status'=>1));
                        $result = $min_num['name'];
						return $result;
		}
	}

	public function wb_random_allot($cmn_id){
		//判断是否有相应权限的csr在线
			$this->load->database();
			$where = array(
					'state'=>1,
					'do_message'=>1,
					'is_deleted'=>0
				);
			$state_on = $this->db->select('id')
						->from('staff')
						->where($where)
						->get()->result_array();
			// var_dump($state_on);
		if($state_on == ''){
			return '没有人员在线';
		}else{
			//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						foreach($state_on as $val){
							$sql = "select count(c.id),s.id,s.name from me_wb_communication as c left join me_staff as s on c.staff_id = s.id where s.id = {$val['id']} and c.operation_status = 1";
							// echo $sql;
							$query = $this->db->query($sql);
							if($query->num_rows()>0){
								$communication_num[] = $query->result_array()[0];
							}else{
								$communication_error = '查询错误';
								return $communication;
							}
						}
						// var_dump($communication_num);

						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						if($min_num['id'] == ''){
							$state_on_num = count($state_on);
							$arr_num = mt_rand(0,$state_on_num);
							$res = $this->db->where('id', $cmn_id)->update('wb_communication', array('staff_id'=>$state_on[$arr_num]['id'],'operation_status'=>1));
							return $res;
							exit;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$res = $this->db->where('id', $cmn_id)->update($this->_cmu_source.'_communication', array('staff_id'=>$min_num_person,'operation_status'=>1));
                        $result = $min_num['name'];
						return $result;
		}
	}

}
