<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Operation模型 (微博处理)
*/

require_once APPPATH . 'models/common/operation_model.php';
class Operation_model extends OperationBase
{
	
	public function __construct()
	{
		parent::__construct();

		$this->_status_key = 'operation_status';
		$this->_cmu_source = 'wb';
	}

	/* 初始化接口对象 */
	public function initWbapi ($wb_aid = 0) 
	{
		$wb_aid = intval($wb_aid) > 0 ? intval($wb_aid) : $this->session->userdata('wb_aid');

		$this->load->helper('api');
		$this->load->model('system/account_model', 'account');
		$_oainfo = $this->account->get_oa_info($wb_aid);
		$this->_apiObj = get_wb_api($_oainfo);

		unset($this->account);
		return ;
	}

	/* 记录回复信息到 staff_reply 表 */
	public function log_reply ($cmn_info, $staff_info) 
	{
		/* 回复方式检测 */
		if ( ! in_array($staff_info['reply_type'], array('c', 'r', 'cr', 'm'))) 
			return '请选择一种回复方式！';

		switch ($cmn_info['type']) {
			case '0':		// 提到我的, 使用0:repost, 1:comment, 3:comment_repost
			case '2':		// 关键字的, 使用0:repost, 1:comment, 3:comment_repost
				$staff_info['reply_type'] = ($staff_info['reply_type'] == 'r') ? 0 : ($staff_info['reply_type'] == 'c' ? 1 : 3);
				break;

			case '1':		// 评论我的, 使用2:reply接口回复
				$staff_info['reply_type'] = 2;
				break;

			case '3':		// 私信, 使用4:私信接口回复
				$staff_info['reply_type'] = 4;
				$staff_info['content'] = json_encode(array('text'=>$staff_info['content']));
				break;

			default:
				$staff_info['reply_type'] = ($staff_info['reply_type'] == 'r') ? 0 : ($staff_info['reply_type'] == 'c' ? 1 : 3);
				break;
		}

		$staff_reply = array(
			'cmn_id'		=> $cmn_info['id'],
			'created_at'	=> date('Y-m-d H:i:s'),
			'company_id'	=> $staff_info['cid'],
			'staff_id'		=> $staff_info['sid'],
			'staff_name'	=> $this->staff['sname'],
			'wb_aid'		=> $staff_info['wb_aid'],
			'content'		=> $staff_info['content'],
			'user_weibo_id'	=> $cmn_info['user_weibo_id'],
			'reply_type'	=> $staff_info['reply_type'],
			'result'		=> 0,
			'weibo_id'		=> $cmn_info['weibo_id']
		);

		$this->db->insert('staff_reply', $staff_reply);
		$reply_id = $this->db->insert_id();

		if ( ! $reply_id) 
			return '保存回复失败，请稍后尝试！';
		else 
			return $staff_reply + array('id' => $reply_id);
	}

	/*
	** 回复一条信息
	** $reply_id 数据库中的回复ID
	** $reply_info 回复信息数组，定时调用时，只需写明 reply_id (记录表中的ID)
	*/
	public function reply ($reply_id, $reply_info = '') 
	{
		if ( ! $reply_info) 
			$reply_info = $this->db->select('*')
				->from('staff_reply')
				->where('id', intval($reply_id))
				->get()->row_array();

		if ( ! $reply_info) 
			return '请指定回复信息！';

		$reply_type = array ('repost', 'comment', 'reply', 'comment_repost', 'reply_message');
		$func = $reply_type[intval($reply_info['reply_type'])];

		if ($func == 'reply') {
			$cmn_info = $this->db->select('status_id')
				->from('wb_communication')
				->where('id', $reply_info['cmn_id'])
				->get()->row_array();
			if ( ! $reply_info)
				return '消息记录已被删除！';
		}

		$this->initWbapi();
		if ($func == 'reply_message') // 私信回复
			$res = $this->_apiObj->$func('text', $reply_info['content'], $reply_info['user_weibo_id']);
		else if ($func == 'reply') // 评论回复
			$res = $this->_apiObj->$func($cmn_info['status_id'], $reply_info['content'], $reply_info['weibo_id']);
		else // @, 关键词回复
			$res = $this->_apiObj->$func($reply_info['weibo_id'], $reply_info['content']);

		if (isset($res['code']) && $res['code'] == 200) {
			/* 判断返回状态 */
			$weibo_id = isset($res['com_id']) ? $res['com_id'] : $res['rep_id'];
			$weibo_id = number_format($weibo_id, 0, '', ''); // ID格式化为正常字符串
			$this->change_status($reply_info['cmn_id'], REPLY, $reply_info['content']);
			$this->db->set(array('result'=>1, 'weibo_id'=>$weibo_id))->where('id', $reply_info['id'])->update('staff_reply');
			return TRUE;
		} else { 
			$this->db->set('result', 2)->where('id', $reply_info['id'])->update('staff_reply');
			return isset($res['me_err_msg']) ? $res['me_err_msg'] : '发送回复失败，请稍后尝试！';
		}
	}

}