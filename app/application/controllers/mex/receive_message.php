<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Receive_message extends CI_Controller
{
	public function index($wx_aid)
	{
		$this->load->model('mex/account_model', 'account');
		$account = $this->account->get_token($wx_aid);
		$account = array_merge($account, array ('id'=>$wx_aid));

		$params = array(
			'token' => $account['token'],
			'debug' => FALSE 
		);
		$this->load->library('message_receive', $params);
		$msg_info = $this->message_receive->get_request();
		if ( ! $msg_info)
			exit();

		// 消息存入数据库
		$this->load->model('mex/communication_model','communication');
		// 保存多媒体信息数据
		if ($msg_info['msgtype'] == 'image' OR $msg_info['msgtype'] == 'voice' OR $msg_info['msgtype'] == 'video') {
			$filepath = $this->message_receive->get_wx_media($account, $msg_info);
			$msg_info['picurl'] = $filepath;
		}

		/* 获取发送者信息 */
		$user_info = $this->message_receive->get_wx_user_info($msg_info['fromusername'], $account);
		if($msg_info['msgtype']!='event') { // 非事件信息插入库
			$cmn_info = $this->communication->insert($msg_info, $account);
			if ( ! $cmn_info) exit(); 	// 存储失败
		}

		$cmn_info = isset($cmn_info) ? $cmn_info : array ('id'=>0);
		$this->response($msg_info, $account, $cmn_info, $user_info);

		// 相应之后执行，数据库操作
		// $this->after_response ();
	}

	/* 处理接收的消息 */
	public function response ($msg_info, $account, $cmn_info, $user_info) 
	{
		$params = array (
			'type' 		=> 'wx',
			'msg_info' 	=> $msg_info, 		// 原始消息具体信息
			'account' 	=> $account,		// 接收账号信息
			'cmn_info' 	=> $cmn_info,		// 存储的消息信息
			'user_info' => $user_info		// 发送消息的用户信息
		);

		$this->load->library('message_response', $params);

		$reply_text = $this->message_response->reply();
		
		echo $reply_text;
	}

}
