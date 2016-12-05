<?php 
/**
 * 微博私信，微信消息接收和自动回复类
 *
 * @author Xu Jian
 */

class Message_receive {
	/**
	 * 调试模式，将错误通过文本消息回复显示
	 *
	 * @var boolean
	 */
	private $debug = FALSE;

	/**
	 * 以数组的形式保存私信/微信服务器每次发来的请求
	 *
	 * @var array
	 */
	private $request;

	/**
	 * 初始化，判断此次请求是否为验证请求，并以数组形式保存
	 *
	 * @param string $token 验证信息/私信为app_secret
	 * @param boolean $debug 调试模式，默认为关闭
	 */
	public function __construct($params) 
	{
		$token = $params['token'];
		$debug = $params['debug'];

		/* 当前接收的账号信息, 用于构建微博API对象 */
		if (isset($params['weibo'])) 
			$this->weibo = $params['weibo'];

		/* 校验签名-- */
		if (!$this->validate_signature($token)) 
		{
//			 log_message('error', 'Signature Check Failed !');	// 记录错误日志
			// exit();
		}

		// 网址接入验证
		if (isset($_GET['echostr'])) 
		{
			exit($_GET['echostr']);
		}

		// 获取格式化后的请求xml对象
		$this->get_msg();
	}

	/**
	 * 验证签名
	 * @param $token 验证信息/私信为对应的app_secret
	 */
	protected function validate_signature($token)
	{
		if ( ! (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce'])))
			return FALSE;

		$tmp_arr = array($token, $_GET['timestamp'], $_GET['nonce']);
		sort($tmp_arr, SORT_STRING);
		$tmp_str = sha1(implode($tmp_arr));

		return $tmp_str == $_GET['signature'] ? TRUE : FALSE;
	}
	/** 
	 * 格式化服务器发来的信息数据
	 */
	protected function get_msg()
	{
		$poststr = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? trim($GLOBALS["HTTP_RAW_POST_DATA"]) : '';
		//file_put_contents(APPPATH . '/logs/sub_20160513.log', $poststr , FILE_APPEND);
//		 file_put_contents('/home/test/liurq123.txt',$poststr);
		// 群发成功
//		$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
//			<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
//			<CreateTime>1416213447</CreateTime>
//			<MsgType><![CDATA[event]]></MsgType>
//			<Event><![CDATA[subscribe]]></Event>
//			<EventKey><![CDATA[]]></EventKey>
//			</xml>";
		/* 微信文本 */
//		$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
//				<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
//				<CreateTime>1415335217</CreateTime>
//				<MsgType><![CDATA[text]]></MsgType>
//				<Content><![CDATA[1234]]></Content>
//				<MsgId>6078818470093469123</MsgId>
//				</xml>";
		/*菜单事件*/
		/*$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
				  <FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
				  <CreateTime>1415342596</CreateTime>
				  <MsgType><![CDATA[event]]></MsgType>
				  <Event><![CDATA[CLICK]]></Event>
				  <EventKey><![CDATA[menu2_1]]></EventKey>
				  </xml>";*/
		/*$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
			<FromUserName><![CDATA[oyQlQuHoC6QgRp1Ss92gegn_HxX4]]></FromUserName>
			<CreateTime>1414144008</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[忽略我吧]]></Content>
			<MsgId>6073702266395544133</MsgId>
			</xml>";*/
		/*$poststr = "<xml>
			<ToUserName><![CDATA[3248583600]]></ToUserName>
			<FromUserName><![CDATA[3811256246]]></FromUserName>
			<CreateTime>1407811206</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[abcd]]></Content>
			</xml>";*/
		/*$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
			<FromUserName><![CDATA[oyQlQuHb_mD5Ue2ZswZLKfw_Y3tc]]></FromUserName>
			<CreateTime>1403518282</CreateTime>
			<MsgType><![CDATA[event]]></MsgType>
			<Event><![CDATA[MASSSENDJOBFINISH]]></Event>
			<MsgID>2348081509</MsgID>
			<Status><![CDATA[send success]]></Status>
			<TotalCount>7</TotalCount>
			<FilterCount>1</FilterCount>
			<SentCount>1</SentCount>
			<ErrorCount>0</ErrorCount>
			</xml>";*/

		// 测试文本消息
		/*$poststr="<xml><ToUserName><![CDATA[3248583600]]></ToUserName>
			<FromUserName><![CDATA[1683555092]]></FromUserName>
			<CreateTime>1401941856</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[cc3123123213]]></Content>
			<MsgId>234567890123456</MsgId>
			</xml>";*/

		// 测试图片消息
		/*$poststr = "<xml>
			<ToUserName><![CDATA[gh_aa6085420e38]]></ToUserName>
			<FromUserName><![CDATA[o5B2WjhfSpRdZEV3Jepm9EmrVtVk]]></FromUserName>
			<CreateTime>1375411722</CreateTime>
			<MsgType><![CDATA[image]]></MsgType>
			<PicUrl><![CDATA[this is a url]]></PicUrl>
			<MediaId><![CDATA[12312312323432432423]]></MediaId>
			<MsgId>1234567890123456</MsgId>
			</xml>";*/

		// 测试视频消息
		/*$poststr = "<xml>
			<ToUserName><![CDATA[gh_aa6085420e38]]></ToUserName>
			<FromUserName><![CDATA[o5B2WjhfSpRdZEV3Jepm9EmrVtVk]]></FromUserName>
			<CreateTime>1375411722</CreateTime>
			<MsgType><![CDATA[video]]></MsgType>
			<PicUrl><![CDATA[this is a url]]></PicUrl>
			<MediaId><![CDATA[12312312323432432423]]></MediaId>
			<MsgId>1234567890123456</MsgId>
			</xml>";*/

		//测试关注事件
        //$poststr = "<xml><ToUserName><![CDATA[gh_aa6085420e38]]></ToUserName>
            //<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
            //<CreateTime>1406020247</CreateTime>
            //<MsgType><![CDATA[event]]></MsgType>
            //<Event><![CDATA[subscribe]]></Event>
            //<EventKey><![CDATA[]]></EventKey>
            //</xml>";

		// 测试菜单 点击菜单
        //$poststr="<xml><ToUserName><![CDATA[gh_aa6085420e38]]></ToUserName>
            //<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
            //<CreateTime>1419411722</CreateTime>
            //<MsgType><![CDATA[event]]></MsgType>
            //<Event><![CDATA[CLICK]]></Event>
            //<EventKey><![CDATA[menu1_2]]></EventKey>
            //</xml>";

		// 测试菜单 点击跳转
            //$poststr="<xml>
            //<ToUserName><![CDATA[gh_aa6085420e38]]></ToUserName>
            //<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
            //<CreateTime>1419411722</CreateTime>
            //<MsgType><![CDATA[event]]></MsgType>
            //<Event><![CDATA[VIEW]]></Event>
            //<EventKey><![CDATA[www.baidu.com]]></EventKey>
            //</xml>";

		// 测试二维码
		/*$poststr = "<xml><ToUserName><![CDATA[gh_6704c0700dc7]]></ToUserName>
			<FromUserName><![CDATA[oyQlQuGAN1OGYUDwyeMN5of0m--s]]></FromUserName>
			<CreateTime>1406088561</CreateTime>
			<MsgType><![CDATA[event]]></MsgType>
			<Event><![CDATA[SCAN]]></Event>
			<EventKey><![CDATA[16]]></EventKey>
			<Ticket><![CDATA[gQE68ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL25FTmJfUzdsTWRkSXdUTEdlVzhOAAIEgvvNUwMEAA
			AAAA==]]></Ticket>
			</xml>";*/

		//测试location
		/*$time = time();
		$poststr ="<xml>
			<ToUserName><![CDATA[wxdb814bdb80e938ba]]></ToUserName>
			<FromUserName><![CDATA[oyQlQuPaUEdxCJxEx-QxSApm1xGs]]></FromUserName>
			<CreateTime>1351776360</CreateTime>
			<MsgType><![CDATA[location]]></MsgType>
			<Location_X>39.912762</Location_X>
			<Location_Y>116.481168</Location_Y>
			<Scale>20</Scale>
			<Label><![CDATA[位置de信息]]></Label>
			<MsgId>{$time}</MsgId>
			</xml> ";*/

		// 判断是否是json格式字符串
		if ( ! empty($poststr)) {

			if (strpos($poststr, '{') === 0){ // JSON 格式 <新浪>
				$post_arr = $this->_translate_json($poststr);
			}else if (strpos($poststr, '<') === 0){ // XML 格式 <新浪 or 微信>
				$post_arr = (array) simplexml_load_string($poststr, 'SimpleXMLElement', LIBXML_NOCDATA);
			}

			// 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
			$this->request = array_change_key_case($post_arr, CASE_LOWER);
		} else {
			$this->request = array();
		}
	}

	/** 
	 * 返回接收到的信息数组
	 * @return array
	 */
	public function get_request()
	{
		return $this->request;
	}

	/**
	 * @function _translate_json 
	 * @description 将新浪私信接口的JSON数据，转换为和XML相同的键名数组
	 *
	 * @param $string [接口POST的原始字符串]
	 * @return array()
	**/
	private function _translate_json ($string) 
	{
		if (empty($string))
			return FALSE;

		$json_arr = json_decode($string, TRUE);

		$ret_arr = array (
			'tousername' 	=> $json_arr['receiver_id'],
			'fromusername' 	=> $json_arr['sender_id'],
			'createtime' 	=> strtotime($json_arr['created_at']),
			'msgtype' 		=> $json_arr['type']
		);

		switch ($json_arr['type']) {
			case 'text':
				$ret_arr['content'] = $json_arr['text'];
				break;
			
			case 'image':
			case 'voice':
				$ret_arr['content'] = $json_arr['text'];
				$ret_arr['mediaid'] = $json_arr['data']['tovfid'];
				break;
			
			case 'position':
				$ret_arr['location_x'] = $json_arr['data']['latitude'];
				$ret_arr['location_y'] = $json_arr['data']['longitude'];
				break;
			
			case 'event':
				$ret_arr['event'] = $json_arr['data']['subtype'];
				if (in_array($json_arr['data']['subtype'], array('scan_follow', 'click', 'view'))) 
					$ret_arr['eventkey'] = $json_arr['data']['key'];

				if ($json_arr['data']['subtype'] == 'scan_follow') 
					$ret_arr['ticket'] = $json_arr['data']['ticket'];
				break;
			
			default:
				# code...
				break;
		}

		# 使用接口转成XML格式
		if (isset($this->weibo)) 
		{
			$CI =& get_instance ();
			$CI->load->helper('api');
			$wbObj = get_wb_api($this->weibo);
			$wbObj->push_set_format('XML');
		}

		/* 利用接口转为XML格式 */
		return $ret_arr;
	}

	/* 获取微信消息发送者信息，如数据库中没有则调用接口添加 */
	public function get_wx_user_info ($openid, $account) 
	{
		$CI =& get_instance ();
		$user = $CI->db->select('id, openid')
			->from('wx_user')
			->where('openid', $openid)
			->get()->row_array();

		if ($user) return $user;

		// 通过接口获取用户信息
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$account['access_token']}&openid={$openid}&lang=zh_CN";
		$user_info = json_decode($this->request($url), true);
		// 入库
		$user_id = 0;
		if ( ! isset($user_info['errcode'])) {
			if (isset($user_info['remark'])) 
				unset($user_info['remark']);
			if (isset($user_info['tagid_list'])) 
				unset($user_info['tagid_list']);
			//$user_info['tagid_list'] = json_encode($user_info['tagid_list']);
			$user_info['wx_aid'] = $account['id'];
			$user_info['company_id'] = $account['company_id'];
			$user_info['subscribe_time'] = date('Y-m-d H:i:s', $user_info['subscribe_time']);
			$user_info['created_at'] = date('Y-m-d H:i:s');
		//file_put_contents(APPPATH . '/logs/sub_20160513.log', json_encode($user_info), FILE_APPEND);
			$CI->db->insert('wx_user', $user_info);
			// 返回信息
			$user_id = $CI->db->insert_id();
		//file_put_contents(APPPATH . '/logs/sub_20160513.log', $CI->db->last_query(), FILE_APPEND);
		}

		return $user_id > 0 ? array ('openid'=>$openid, 'id'=>$user_id) : array ('openid'=>$openid, 'id'=>0);
	}

	/* 获取微博消息发送者信息，没有则加入数据库中 */
	public function get_wb_user_info ($user_weibo_id, $account) 
	{
		$CI =& get_instance ();
		// communication中有，待转移
		$user_info = $CI->db->select('id, user_weibo_id')
			->from('wb_user')
			->where('user_weibo_id', $user_weibo_id)
			->get()->row_array();
		if ( ! $user_info) { // 数据库中没有该用户，使用新浪接口获取该用户数据
			$CI->load->helper('api');
			$wbObj = get_wb_api($account);
			$user_info = $wbObj->user_show($user_weibo_id);
			if (isset($user_info['me_err_code'])) {
				$user_info = array ( 'id' => $user_weibo_id, 'screen_name' => 'Unknown User' );
			} else {
				// 将此用户信息存入数据库
				$CI->load->model('meo/wb_user_model', 'wb_user');
				$user_info = $CI->wb_user->insert_user($account['id'], $user_info, 'sina');
			}
		}
		return $user_info;
	}

	/** 
	 * 获取微信多媒体文件 
	 * @return filepath 文件保存路径
	**/
	public function get_wx_media ($account, $msg_info) 
	{
		if ( ! in_array($msg_info['msgtype'], array('image', 'voice', 'video'))) 
			return FALSE;

		// 存储视频或音频文件
		if (isset($msg_info['mediaid'])) {
			if ($msg_info['msgtype'] == 'image') 
				$ext = 'jpg';
			else 
				$ext = isset($msg_info['format']) ? $msg_info['format'] : 'mp4';

			$media_file = $this->curl_get_media($account, $msg_info['mediaid'], $ext, $msg_info['msgtype']);
			if (is_array($media_file)) {
				$account = array_merge($account, $media_file);
				$media_file = $this->curl_get_media($account, $msg_info['mediaid'], $ext, $msg_info['msgtype']);
			} else if ($media_file === FALSE) {
				$media_file = '';
			}
		}

		// 存储视频缩略图
		if (isset($msg_info['thumbmediaid'])) {
			$thumb_file = $this->curl_get_media($account, $msg_info['thumbmediaid'], 'jpg', 'image');
			if (is_array($media_file)) {
				$account = array_merge($account, $media_file);
				$media_file = $this->curl_get_media($account, $msg_info['thumbmediaid'], 'jpg', 'image');
			} else if ($media_file !== FALSE) {
				$media_file .=  '<|>' . $thumb_file;
			}
		}

		return $media_file;
	}

	/**
	 * 获取微信文件并存储！
	 */
	public function curl_get_media ($account, $media_id, $media_ext, $type) 
	{
		$down_url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$account['access_token']}&media_id={$media_id}";
		$file = $this->request($down_url);

		$month = date('Ym');
		$file_dir = '../resources/' . $month . '/' . $type;
		// 创建上传文件夹
		if ( ! file_exists($file_dir)) 
			mkdir($file_dir, 0777, true);

        $file_path =$file_dir . '/' . $media_id;
        if ($media_ext == 'amr') {
            $file_name =$file_path . '.amr';
        } else {
            $file_name =$file_path . '.' . $media_ext;
        }

		/* 1. 接口错误：判断是否AT过期 */
		if (strpos($file, '{') == 0) {
			$ret_msg = json_decode($file, TRUE);
			if (in_array($ret_msg['errcode'], array ('42001', '40001', '40014', '41001'))) {
				// 重新获取Token
				$tokenurl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$account['appid'].'&secret='.$account['secret'];
				$token = json_decode($this->request($tokenurl), true);
				return array('access_token'=>$token['access_token']);
			} else {
				$log_msg = '[' . date('Y-m-d H:i:s') . ']' . $file . "\n";
				file_put_contents(APPPATH . '/logs/weixin_get_media_errors_' . $month . '.log', $log_msg, FILE_APPEND);
				return FALSE;
			}

		/* 2. 存储错误：文件无法保存 */
		} else if ( ! file_put_contents($file_name, $file)) {
			$log_msg = '[' . date('Y-m-d H:i:s') . "]File Saving Failed!\n";
			file_put_contents(APPPATH . '/logs/weixin_get_media_errors_' . $month . '.log', $log_msg, FILE_APPEND);
			return FALSE;

		/* 3. 正常保存 */
		} else {
            if ($media_ext == 'amr') {
                //获取操作系统类型
                $os_type = strtolower(PHP_OS);

                //转换成mp3
                if ($os_type == 'winnt') {
                    $ffmpeg = '"c:\Program Files\WinFF\ffmpeg.exe " -y -i ';
                } else if ($os_type == 'linux') {
                    $ffmpeg = '/usr/bin/ffmpeg -y -i ';
                }
                exec($ffmpeg.$file_name.' '.$file_path.'.mp3');
                // var_dump($ffmpeg.$upload.$filename.'.amr '.$upload.$filename.'.mp3');
                //不删除源文件
                //unlink($file_path.'.amr');
                $file_name = $file_path.'.mp3';
            }
			return substr($file_name, 3);
		}
	}

	/**
	 * 保存微博图片
	 * @return filepath
	 */
	public function get_wb_media($url,$param){
		$file = $this->request($url,$param);
		$month = date('Ym');
		$file_path = '../resources/' . $month . '/image';
		// 创建上传文件夹
		if ( ! file_exists($file_path)){
			mkdir($file_path, 0777, true);
		}
		$filename = $month.time();
		$file_path = $file_path.'/'.$filename.'.jpg';
		if ( ! file_put_contents($file_path, $file)) {
			$log_msg = '[' . date('Y-m-d H:i:s') . "]File Saving Failed!\n";
			file_put_contents(APPPATH . '/logs/weibo_get_media_errors_' . $month . '.log', $log_msg, FILE_APPEND);
			return FALSE;
		} else {
			return substr($file_path, 3);
		}
	}

	/* CURL 请求，Copied from Tecent Weibo API <*_*> */
	public function request( $url , $params = array(), $method = 'GET' , $multi = false, $extheaders = array()){
		if(!function_exists('curl_init')) exit('Need to open the curl extension');
		$method = strtoupper($method);
		$ci = curl_init();
		curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ci, CURLOPT_TIMEOUT, 3);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ci, CURLOPT_HEADER, false);
		$headers = (array)$extheaders;
		switch ($method){
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($params)){
					if($multi)
					{
						foreach($multi as $key => $file)
						{
							$params[$key] = '@' . $file;
						}
						@curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
						$headers[] = 'Expect: ';
					}
					else
					{
						@curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
						$headers[] = 'Expect: ';
						// curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
					}
				}
				break;
			case 'DELETE':
			case 'GET':
				$method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($params))
				{
					$url = $url . (strpos($url, '?') ? '&' : '?')
						. (is_array($params) ? http_build_query($params) : $params);
				}
				break;
		}
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
		curl_setopt($ci, CURLOPT_URL, $url);
		if($headers)
		{
			 curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
		}

		$response = curl_exec($ci);
		curl_close ($ci);
		return $response;
	}

}
