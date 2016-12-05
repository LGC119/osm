<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 微信信息处理 */
/**
 * 仅为方便用户权限控制存在，操作方法在::OperationBase::
 */
require_once APPPATH . "controllers/common/operation.php";

class Operation extends OperationCtrl {

	public function __construct()
	{
		parent::__construct();

		/* 默认载入微博操作模型，需要载入微信时，在GET/POST参数中添加x */
		$this->load->model('mex/operation_model', 'model');
		$this->model->initOperator($this->sid);
	}

	public function reply ($cmn_id) 
	{
		$cmn_id = intval($cmn_id);
		if ( ! $cmn_id > 0) {
			$this->meret(NULL, MERET_BADREQUEST, '没有找到这条信息记录！');
			return ;
		}

		$cmn_info = $this->_get_cmn_info ($cmn_id);

		if ( ! $cmn_info) {
			$this->meret(NULL, MERET_BADREQUEST, '没有找到这条信息记录！');
			return ;
		}

		# 超时检测，一分钟接口延迟
		if ((strtotime($cmn_info['created_at']) + 48 * 3600) < (time() - 60)) {
			$this->meret(array($cmn_info['created_at'], date('Y-m-d H:i:s')), MERET_BADREQUEST, '信息已经超过48小时，无法回复！');
			return ;
		}

		$p = $this->input->post(NULL, TRUE);
		
		$reply_info = $this->model->log_reply($p, $cmn_info);
		if (is_string($reply_info)) {
			$this->meret(NULL, MERET_BADREQUEST, $reply_info);
			return ;
		}

		$res = $this->model->reply($reply_info['id'], $reply_info);

		if (is_string($res)) // 系统错误
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else if (isset($res['errcode'])) // API 错误
			$this->meret(NULL, MERET_APIERROR, $res['errmsg']);
		else 
			$this->meret($res);
	}

	/* 获取信息状态 */
	public function _get_cmn_status ($cmn_id)
	{
		$cmn_info = $this->db->select('operation_status AS os')
			->from('wx_communication')
			->where('id', $cmn_id)
			->get()->row_array();

		if ($cmn_info) 
			return $cmn_info['os'];
		else 
			return -1;
	}

	/* 获取信息数据 */
	public function _get_cmn_info ($cmn_id)
	{
		$cmn_info = $this->db->select('id, openid, operation_status AS os, type, msgid, openid, is_deleted, created_at')
			->from('wx_communication')
			->where('id', $cmn_id)
			->get()->row_array();

		return $cmn_info;
	}
	
	public function assign($cmn_id, $staff_id){
        $data = $this->model->wx_assign($cmn_id, $staff_id);
        if(!empty($data)){
            $this->meret($data, 200, '');
        }else{
            $this->meret('', 204, '');
        }
    }

	/*
     ** 重分配
     ** @param $cmn_id 信息id
	*/
	public function reAssign($cmn_id){
        $data = array('operation_status'=>0);
        $this->db->where('id',$cmn_id);
        $rst = $this->db->update('wx_communication',$data);
        if($rst){
            $this->meret('', 200, '');
        }else{
            $this->meret('', 204, '');
        }
    }

}

/* End of file operation.php */
/* Location: ./application/controllers/mex/operation.php */
