<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 微博接收私信消息 */
class Receive_message extends CI_Controller
{
	public function __construct ()
	{
		parent::__construct();

		$date = date('Y-m-d');
	}

	/* 接收消息主体函数 */
	public function index($weibo_user_id)
	{

		$this->load->model('system/account_model', 'account');
		$account_info = $this->account->get_info_by_weiboid($weibo_user_id);

		if ( ! $account_info) // 没有账号，记录错误信息，退出
            exit();

		/* 正常消息接收流程开始 */
		$this->load->library('message_receive', array(
			'token' => $account_info['client_secret'], 
			'debug' => FALSE, 
			'weibo' => $account_info
		));


		$msg_info = $this->message_receive->get_request();

        if (isset($msg_info['type']) && $msg_info['type'] == 'mention')
            exit();     // 不抓取mention的信息

        /* 获取发送者信息 */
        $user_info = $this->message_receive->get_wb_user_info($msg_info['fromusername'], $account_info);
		
		// 消息存入数据库
		// 用户的信息插入数据库
        if(isset($msg_info['msgtype']) && $msg_info['msgtype'] == 'image'){
            // 获取图片路径
            $param = array(
                'access_token'=> $account_info['access_token'],
                'fid'=>$msg_info['mediaid']
            );
            $imgurl = $this->message_receive->get_wb_media('https://upload.api.weibo.com/2/mss/msget',$param);
            $msg_info['original_pic'] = $imgurl;
        }
		if(isset($msg_info['msgtype']) && $msg_info['msgtype'] != 'event') {
			$this->load->model('meo/communication_model','communication');
            	$cmn_info = $this->communication->insert_message($account_info, $msg_info);
		}

        $cmn_info = isset($cmn_info) ? $cmn_info : array ('id'=>0);
		// 查找相应自动回复规则
        $this->response($msg_info, $account_info, $cmn_info, $user_info);
	}

    /* 处理接收的消息 */
    public function response ($msg_info, $account, $cmn_info, $user_info)
    {
        $params = array (
            'type' 		=> 'wb',
            'msg_info' 	=> $msg_info, 		// 原始消息具体信息
            'account' 	=> $account,		// 接收账号信息
            'cmn_info' 	=> $cmn_info,		// 存储的消息信息
            'user_info' => $user_info		// 发送消息的用户信息
        );

        $this->load->library('message_response', $params);

        $reply_text = $this->message_response->reply();
        // file_put_contents('/home/test/meo.txt',$reply_text);
        $reply_text_obj = simplexml_load_string($reply_text);
        $msgtype = (string)$reply_text_obj->MsgType;
        if($msgtype == 'text'){
            $reply_arr = array(
                "result"      => true,
                "receiver_id" => (string)$reply_text_obj->ToUserName,
                "sender_id"   => (string)$reply_text_obj->FromUserName,
                "type"        => "text",
                "data"        => array(
                    "text" => (string)$reply_text_obj->Content
                )
            );
            $reply_text = $this->_u_json_encode($reply_arr);
        }
        if($msgtype == 'news'){
            $reply_arr = array(
                "result"=> true,
                "receiver_id"=>  (string)$reply_text_obj->ToUserName,
                "sender_id"=>  (string)$reply_text_obj->FromUserName,
                "type"=> "articles",
                "data"=>array(
                    "articles"=>array(

                    )
                )
            );
//            var_dump($reply_text_obj->Articles->item);
            $count = count($reply_text_obj->Articles->item);
            if($count >= 1){
                for($i=0; $i<$count; $i++){
                    // 标题
                    $reply_arr['data']['articles'][$i]['display_name'] = (string)$reply_text_obj->Articles->item[$i]->Title[0];
                    // 文字描述
                    $reply_arr['data']['articles'][$i]['summary'] = (string)$reply_text_obj->Articles->item[$i]->Description[0];
                    // 图片url公网的
                    $reply_arr['data']['articles'][$i]['image'] = (string)$reply_text_obj->Articles->item[$i]->PicUrl[0];
                    // url 点击后跳转的
                    $reply_arr['data']['articles'][$i]['url'] = (string)$reply_text_obj->Articles->item[$i]->Url[0];

                }
            }
//            echo "<pre>";
//            print_r($reply_arr);
            $reply_text = $this->_u_json_encode($reply_arr);
        }
        echo $reply_text;
    }

    // 处理中文字符
    public function _u_json_encode($arr){
        if(phpversion() >='5.4.0'){
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $code = json_encode($arr);
            return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);
        }

    }
}

