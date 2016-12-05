<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 微博信息处理 */
/**
 * 仅为方便用户权限控制存在，操作方法在::OperationBase::
 */
require_once APPPATH . "controllers/common/operation.php";

class Operation extends OperationCtrl {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('meo/operation_model', 'model');
		$this->model->initOperator($this->sid);
	}

	/* @func REPLY [发送] */
	/*
	** 1. 将回复信息存储在数据库中
	** 2. 调用接口回复
	** 3. 根据接口返回，更新回复表记录状态
	*/
	public function reply () 
	{
		$cmn_id = intval($this->input->get_post('cmnId'));
		$content = trim($this->input->get_post('content'));
		$reply_type = trim($this->input->get_post('reply_type'));

		if ($content == '') { 
			$this->meret(NULL, MERET_BADREQUEST, '请输入回复内容！');
			return ;
		}

		$cmn_info = $this->_get_cmn_info($cmn_id);

		if ($cmn_info && $cmn_info['is_deleted'] == 0) 
		{
			/* 是否分类 */
			if ($cmn_info['os'] == UNTOUCHED) {
				$this->meret(NULL, MERET_BADREQUEST, '请先为该条信息分类！');
				return ;
			}

			$staff_info = array (
				'sid'			=> $this->sid, 
				'cid'			=> $this->cid, 
				'wb_aid'		=> $this->session->userdata('wb_aid'), 
				'content'		=> $content, 
				'reply_type'	=> $cmn_info['type'] == 3 ? 'm' : $reply_type // 私信用特定回复
			);
			$reply_info = $this->model->log_reply ($cmn_info, $staff_info); 
			if ( ! is_array($reply_info)) {
				$this->meret(NULL, MERET_SVRERROR, '保存回复失败，请稍后尝试！');
				return ;
			}

			/* 判断是否定时操作 */
			// $set_time = strtotime(trim($this->input->get_post('set_time')));
			// if ($set_time) {
			// 	if ($set_time - time() < 1800) {
			// 		$this->meret(NULL, MERET_BADREQUEST, '设定回复时间需在至少30分钟后！');
			// 		return ;
			// 	}
			// } else {
			// 	$res = $this->model->reply($reply_info['id'], $reply_info);
			// 	if (is_string($res)) 
			// 		$this->meret(NULL, MERET_BADREQUEST, $res);
			// 	else 
			// 		$this->meret($res);
			// }

			$res = $this->model->reply($reply_info['id'], $reply_info);
			if (is_string($res)) 
				$this->meret(NULL, MERET_BADREQUEST, $res);
			else 
				$this->meret($res);
		} 
		else 
		{
			$this->meret(NULL, MERET_BADREQUEST, '信息记录不存在或原记录被删除！');
		}

		return ;
	}

	/* 发送回复表中，指定记录 */
	public function reply_by_id ($id) 
	{
		$id = intval($id);

		$res = $this->model->reply($id);
		
		if (is_string($res)) 
			$this->meret(NULL, MERET_SVRERROR, $res);
		else 
			$this->meret($res);
	}

	/* 获取信息状态 */
	public function _get_cmn_status ($cmn_id)
	{
		$cmn_info = $this->db->select('operation_status AS os')
			->from('wb_communication')
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
		$cmn_info = $this->db->select('id, operation_status AS os, type, user_weibo_id, weibo_id, platform, is_deleted')
			->from('wb_communication')
			->where('id', $cmn_id)
			->get()->row_array();

		return $cmn_info;
	}

    public function assign($cmn_id, $staff_id){
        $data = $this->model->assign($cmn_id, $staff_id);
        if(!empty($data)){
            $this->meret($data, 200, '');
        }else{
            $this->meret('', 204, '');
        }
    }
}

/* End of file operation.php */
/* Location: ./application/controllers/meo/operation.php */
