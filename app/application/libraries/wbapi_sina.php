<?php 

/*
** FUNCTION LIST
** @get_bind_url  @show             @comments        @reposts        @update 
** @upload        @comment          @repost          @comment_repost @reply 
** @reply_pm      @destroy          @comment_destroy @mentions       @comments_to_me
** @user_timeline @friends_timeline @followers       @users_relation @user_show 
** @user_counts   @get_tags         @querymid        @shorten        @_return_err
** FUNCTION LIST END
*/

require_once APPPATH . 'libraries/sdk/sinasdk.php';

class Wbapi_sina 
{
	public $_oa;		// SaeTOAuthV2 对象
	public $_cl;		// SaeTClientV2 对象

	/* 初始化SDK参数 */
	public function __construct($params) 
	{
		extract($params);
		$access_token = isset($access_token) ? $access_token : NULL;
		$refresh_token = isset($refresh_token) ? $refresh_token : NULL;

		$this->_oa = new SaeTOAuthV2($client_id, $client_secret, $access_token, $refresh_token);
		$this->_cl = new SaeTClientV2($client_id, $client_secret, $access_token, $refresh_token);
	}

	/*
	** 绑定新浪微博 
	** @get_bind_url
	** @params $app {包含appid和callbackurl的app数组} 
	** @return 绑定账号的地址
	*/
	public function get_bind_url($app) 
	{
		$CI = &get_instance();
		return $this->_oa->getAuthorizeURL($app['callbackurl'], 'code', $CI->config->base_url() . ',' . $app['id']);
	}

	/* @show 获取一条微博 */
	public function show ($statusid) 
	{
		$status = $this->_cl->show_status($statusid);

		return $status;
	}

	/* @comments 获取一条微博的评论 */
	public function comments ($sid, $page = 1, $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0) 
	{
		$result = $this->_cl->get_comments_by_sid($sid, $page, $count, $since_id, $max_id, $filter_by_author);

		return $result;
	}

	/* @reposts 获取一条微博的转发 */
	public function reposts ($sid, $page = 1, $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0) 
	{
		$result = $this->_cl->repost_timeline($sid, $page, $count, $since_id, $max_id, $filter_by_author);

		return $result;
	}

	/* @update 发布一条微博 */
	public function update ($status, $lat = NULL, $long = NULL, $annotations = NULL) 
	{
		$result = $this->_cl->update($status, $lat, $long, $annotations);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','weibo_id'=>$result['id'],'data'=>$result);
	}

	/* @upload 上传文件 */
	public function upload ($status, $pic_path, $lat = NULL, $long = NULL) 
	{
		$result = $this->_cl->upload($status, $pic_path, $lat, $long);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','weibo_id'=>$result['id'],'data'=>$result);
	}

	/* @comment 评论一条微博 */
	public function comment ($statusid , $comment , $comment_ori = 0) 
	{
		$result = $this->_cl->send_comment($statusid, $comment, $comment_ori);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','com_id'=>$result['id']);
	}

	/* @repost 转发一条微博 */
	public function repost ($statusid, $text = NULL) 
	{
		$result = $this->_cl->repost($statusid, $text, 0);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','rep_id'=>$result['id'],'data'=>$result);
	}

	/* @comment_repost 评论并转发一条微博 */
	public function comment_repost ($statusid, $text = NULL) 
	{
		$result = $this->_cl->repost($statusid, $text, 1);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','rep_id'=>$result['id'],'data'=>$result);
	}

	/* @reply 回复一条评论 */
	public function reply ($sid, $text, $cid, $without_mention = 0, $comment_ori = 0) 
	{
		$result = $this->_cl->reply($sid, $text, $cid, $without_mention, $comment_ori);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'messages'=>'success','rep_id'=>$result['id'],'data'=>$result);
	}

	/* @reply_message TODO : 回复私信消息，待完善 */
	public function reply_message ($type, $data, $receiver_id, $save_sender_box = 1) 
	{
		$result = $this->_cl->reply_message($type, $data, $receiver_id, $save_sender_box);

		return isset($result['error']) ? $this->_return_err($result) : array('code'=>200, 'rep_id'=>0);
	}

	/* @destroy 删除一条微博 */
	public function destroy ($statusid) 
	{
		$result = $this->_cl->destroy($statusid);

		return (isset($rst['error'])) ? FALSE : TRUE;
	}

	/* @comment_destroy 删除一条评论 */
	public function comment_destroy ($statusid) 
	{
		$result = $this->_cl->comment_destroy($statusid);

		return isset($result['error']) ? FALSE : TRUE;
	}

	/* @mentions @我的微博 */
	public function mentions ($page = 1, $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0, $filter_by_source = 0, $filter_by_type = 0) 
	{
		$mentions = $this->_cl->mentions($page, $count, $since_id, $max_id, $filter_by_author, $filter_by_source, $filter_by_type);
		return isset($mentions['error']) ? $this->_return_err($mentions) : $mentions;
	}

	/* @comments_to_me 评论我的微博 */
	public function comments_to_me ($page = 1 , $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0, $filter_by_source = 0) 
	{
		$comments = $this->_cl->comments_to_me($page, $count, $since_id, $max_id, $filter_by_author, $filter_by_source);
		return isset($comments['error']) ? $this->_return_err($comments) : $comments;
	}

	/* @user_timeline 用户时间轴 */
	public function user_timeline ($uid = NULL , $page = 1 , $count = 50 , $since_id = 0, $max_id = 0, $feature = 0, $trim_user = 0, $base_app = 0) 
	{
		$result = $this->_cl->user_timeline_by_id($uid, $page, $count, $since_id, $max_id, $feature, $trim_user, $base_app);

		return $result;
	}

	/* @friends_timeline 用户关注时间轴 */
	public function friends_timeline ($page = 1, $count = 50, $since_id = 0, $max_id = 0, $base_app = 0, $feature = 0) 
	{
		$result = $this->_cl->home_timeline($page, $count, $since_id, $max_id, $base_app, $feature);

		return $result;
	}

	/* @followers 获取粉丝 */
	public function followers ($uid , $cursor = 0 , $count = 50) 
	{
		$result = $this->_cl->followers_by_id( $uid , $cursor, $count);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* @users_relation 获取用户关系 */
	public function users_relation ($target_id, $source_id = NULL) 
	{
		$result = $this->_cl->is_followed_by_id($target_id, $source_id);

		if (isset($result['error'])) 
			return $this->_return_err($result);

		$followed_by = $result['source']['followed_by'];
		$following = $result['source']['following'];

		return $followed_by == $following ? ($followed_by ? 4:1) : ($followed_by ? 3:2);
	}

	/* @user_show 获取一个用户信息 */
	public function user_show ($uid) 
	{
		$result = $this->_cl->show_user_by_id($uid);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* @user_counts 批量获取用户的粉丝数、关注数、微博数 */
	public function user_counts ($uids)
	{
		$result = $this->_cl->user_counts($uids);

		return $result;
	}

	/* @get_tags 批量获取微博用户的标签 */
	public function get_tags ($uids) 
	{
		$user_tags = $this->_cl->get_tags_batch($uids);

		return $user_tags;
	}

	/* @querymid 获取一条微博信息的MID */
	public  function querymid ($id, $type = 1, $is_batch = 0) 
	{
		$result = $this->_cl->querymid($id, $type, $is_batch);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* @queryid 获取一条微博信息的ID */
	public  function queryid ($mid, $type = 1, $is_batch = 0, $inbox = 0, $isBase62 = 1) 
	{
		$result = $this->_cl->queryid($mid, $type, $is_batch, $inbox, $isBase62);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* @shorten 获取短网址 */
	public function shorten ($url) 
	{
		$result = $this->_cl->shorten($url);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* @push_set_format 设定推送格式 */
	public function push_set_format ($format = 'JSON')
	{
		$result = $this->_cl->push_set_format($format);

		return isset($result['error']) ? $this->_return_err($result) : $result;
	}

	/* 获取OAuth授权链接 */
	public function get_oauth_url ($url) 
	{
		$url = $this->_oa->getAuthorizeURL($url, 'code', NULL, NULL);

		return isset($url['error']) ? $this->_return_err($url) : $url;
	}

	/* @_return_err 返回新浪接口错误信息 */
	private function _return_err($result) 
	{
		/* 获取微博借口错误信息 */
		$CI = &get_instance();
		$CI->load->config('meo/sina_sdk_errors');
		$err_msg = $CI->config->item('sina_sdk_errors');

		if (isset($err_msg[$result['error_code']]))
			return array('me_err_code'=>$result['error_code'], 'me_err_msg'=>$err_msg[$result['error_code']]);
		else 
			return array('me_err_code'=>$result['error_code'], 'me_err_msg'=>$result['error']);
	}

	public function sendall($json,$token=''){
		$url = "https://m.api.weibo.com/2/messages/sendall.json?access_token=$token";
		return $this->request($url,$json,"post");
//		return $this->_cl->sendall($weiboDataJson);
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

/* End of file wbapi_sina.php */
/* Location: ./application/libraries/wbapi_sina.php */