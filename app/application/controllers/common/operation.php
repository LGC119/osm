<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 舆情信息处理 控制器
** 方法参考 : config 文件中的操作代码
** @func CATEGORIZE,   @func ASSIGN, @func SUBMIT, @func REPLY, @func PASS,  @func REBUT,   @func REASSIGN, 
** @func RECATEGORIZE, @func TASK,   @func IGNORE, @func PIN,   @func UNPIN, @func SUSPEND, @func UNIGNORE
*/

class OperationCtrl extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->config('common/operation');
	}

	/* @func CATEGORIZE [分类] */
	public function categorize ($cmn_id, $cats = '') 
	{
		$cmn_id = intval($cmn_id);
		$cat_arr = explode('_', $cats);

		if ( ! $cats OR ! $cat_arr) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有分类！');
			return ;
		}

		$cmn_status = $this->_get_cmn_status($cmn_id);

		if ($cmn_status != -1) {
			if ($cmn_status == SUBMITED OR $cmn_status == REPLIED) {
				$this->meret(NULL, MERET_BADREQUEST, '信息不能被分类，请刷新！');
			} else {
				/* 执行分类操作 */
				$re_categorize = $cmn_status == CATEGORIZED ? TRUE : FALSE;
				$res = $this->model->categorize($cmn_id, $cat_arr, $re_categorize);
				if (is_string($res))
					$this->meret(NULL, MERET_SVRERROR, $res);
				else 
					$this->meret('OK');
			}
		} else {
			$this->meret(NULL, MERET_BADREQUEST, '信息记录不存在！');
		}

		return ;
	}

	/* @func ASSIGN [分配] */
	public function assign () {}

	/* @func SUBMIT [提交回复] {[NOT IN USE]} */
	public function submit () 
	{
		$cmn_id = intval($this->input->get_post('cmn_id'));
		$reply = trim($this->input->get_post('reply'));
		$cmn_status = $this->_get_cmn_status($cmn_id);

		if ( ! $reply) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '请输入回复内容！');
			return ;
		}

		if ($cmn_status != -1) 
		{
			// 执行回复操作
		}
		else 
		{
			$this->meret(NULL, MERET_BADREQUEST, '信息记录不存在！');
		}

		return ;
	}

	/* @func PASS [审核通过] */
	public function pass () {}

	/* @func REBUT [审核不通过] */
	public function rebut () {}

	/* @func REASSIGN [重分配] */
	public function reassign () {}

	/* @func RECATEGORIZE [重分类] */
	public function recategorize () {}

	/* @func TASK [定时发送] */
	public function task () {}

	/* @func IGNORE [忽略] */
	public function ignore ($cmn_id)
	{
		$cmn_id = intval($cmn_id);
		$cmn_status = $this->_get_cmn_status($cmn_id);

		if ($cmn_status != -1) {
			if ($cmn_status == IGNORED) {
				$this->meret(NULL, MERET_BADREQUEST, '信息已被忽略！');
			} else {
				/* 执行忽略操作 */
				$ignore_res = $this->model->change_status($cmn_id, IGNORE);
				if ( ! is_string($ignore_res)) 
					$this->meret(TRUE, MERET_OK);
				else 
					$this->meret(NULL, MERET_SVRERROR, $ignore_res);
			}
		} else {
			$this->meret(NULL, MERET_BADREQUEST, '信息记录不存在！');
		}
	}

	/* @func PIN [置顶] */
	public function pin ($cmn_id) 
	{
		$cmn_id = intval($cmn_id);

		if ($this->model->pin($cmn_id)) 
			return $this->meret(NULL);
		else 
			return $this->meret(NULL, MERET_SVRERROR, '置顶失败！');
	}

	/* @func UNPIN [取消置顶] */
	public function unpin ($cmn_id) 
	{
		$cmn_id = intval($cmn_id);

		if ($this->model->unpin($cmn_id)) 
			return $this->meret(NULL);
		else 
			return $this->meret(NULL, MERET_SVRERROR, '取消置顶失败！');
	}

	/* @func SUSPEND [挂起] */
	public function suspend ($cmn_id) 
	{
		$cmn_id = intval($cmn_id);
		$set_time = trim($this->input->get_post('set_time'));
		$desc = trim($this->input->get_post('desc'));

		$res = $this->model->suspend($cmn_id, $set_time, $desc);

		if ( ! is_string($res)) 
			$this->meret($res);
		else 
			$this->meret(NULL, MERET_BADREQUEST, $res);
	}

	// 修改挂起
	public function change_suspend()
	{
		$post = $this->input->post();
		$res = $this->model->change_suspend($post['id'], $post['set_time'], $post['desc']);

		if ($res === TRUE)
			$this->meret($res);
		else
			$this->meret(NULL, MERET_BADREQUEST, $res);
	}

	/* @func SUSPEND [挂起] */
	public function unsuspend ($sid) 
	{
		$cmn_id = intval($sid);

		$res = $this->model->unsuspend($sid);

		if ( ! is_string($res)) 
			$this->meret($res);
		else 
			$this->meret(NULL, MERET_BADREQUEST, $res);
	}

	/* @func UNIGNORE [取消忽略] */
	public function unignore ($cmn_id) 
	{
		$cmn_id = intval($cmn_id);
		$cmn_status = $this->_get_cmn_status($cmn_id);

		if ($cmn_status != -1) {
			if ($cmn_status != IGNORED) {
				$this->meret(NULL, MERET_BADREQUEST, '信息不是忽略状态！');
			} else {
				/* 执行取消忽略操作 */
				$res = $this->model->change_status($cmn_id, UNIGNORE);
				if ( ! is_string($res)) 
					$this->meret(TRUE, MERET_OK);
				else 
					$this->meret(NULL, MERET_SVRERROR, $res);
			}
		} else {
			$this->meret(NULL, MERET_BADREQUEST, '信息记录不存在！');
		}
	}

}

/* End of file operation.php */
/* Location: ./application/controllers/common/operation.php */