<?php 

require_once dirname(__FILE__) . '/sdk/Tencent.php';

class Wbapi_tx 
{

	/* 初始化SDK参数 */
	public function __construct($params) 
	{
		extract($params);

		OAuth::init($client_id, $client_secret);
		if (isset($access_token) && isset($openid)) {
			Tencent::init($access_token, $openid);
		}
	}

	/*
	** 绑定腾讯微博 
	** @params $app {包含appid和callbackurl的app数组} 
	** @return 绑定账号的地址
	*/
	public function get_bind_url($app) 
	{
		$CI = &get_instance();
		return OAuth::getAuthorizeURL($app['callbackurl'], 'code', FALSE, $CI->config->base_url() . ',' . $app['id']);
	}

	/* 获取一条微博 */
	public function show ($statusid) 
	{
		$params = array ( 'id' => $statusid );
		$r = Tencent::api('t/show', $params, 'GET');

		return $r['errcode'] ? $this->_return_err($r) : $r;
	}

	/* 获取一条微博的评论 */
	public function comments () {}

	/* 获取一条微博的转发 */
	public function reposts () {}

	/* 发布一条微博 */
	public function update () {}

	/* 上传文件 */
	public function upload () {}

	/* 评论一条微博 */
	public function comment ($status_id, $content) 
	{
		$params = array( 'reid' => $status_id, 'content' => $content );
		$r = Tencent::api('t/comment', $params, 'POST');

		return $r['errcode'] != 0 ? $this->_return_err($r) : array('code'=>200, 'messages'=>'success','com_id'=>$r['data']['id']);
	}

	/* 转发一条微博 */
	public function repost ($status_id, $content) 
	{
		$params = array( 'reid'=>$status_id, 'content'=>$content );
		$r = Tencent::api('t/re_add', $params, 'POST');

		return $r['errcode'] != 0 ? $this->_return_err($r) : array('code'=>200, 'messages'=>'success','rep_id'=>$r['data']['id']);
	}

	/* 评论并转发一条微博 [使用两次接口] */
	public function comment_repost ($status_id, $content) 
	{
		$params = array( 'reid'=>$status_id, 'content'=>$content );
		$cr = Tencent::api('t/comment', $params, 'POST');
		$rr = Tencent::api('t/re_add', $params, 'POST');

		return $rr['errcode'] != 0 ? $this->_return_err($rr) : array('code'=>200, 'messages'=>'success','rep_id'=>$rr['data']['id']);
	}

	/* 回复一条评论 */
	public function reply ($status_id, $content) 
	{
		$params = array(
			'reid'		=> $status_id,
			'content'	=> $content
		);
		$r = Tencent::api('t/reply', $params, 'POST');

		return $r['errcode'] != 0 ? $this->_return_err($r) : array('code'=>200, 'messages'=>'success','rep_id'=>$r['data']['id']);
	}

	/* 删除一条微博 */
	public function destroy () {}

	/* 删除一条评论 */
	public function comment_destroy () {}

	/* @我的微博 */
	public function mentions ($count = 50, $pageflag = 0, $pagetime = 0) 
	{
		$params = array( 
			'pageflag' => $pageflag, 
			'pagetime' => $pagetime, 
			'reqnum' => $count, 
			'lastid' => 0, 
			'type' => 3, 
			'contenttype' => 0
		);
		$r = Tencent::api('statuses/mentions_timeline', $params, 'GET');

		return $r['errcode'] ? $this->_return_err($r) : $r;
	}

	/* 评论我的微博 [腾讯微博没有这个接口] */
	public function comments_to_me () 
	{
		return 'NO API Provided !';
	}

	/* 用户时间轴 */
	public function user_timeline () {}

	/* 用户关注时间轴 */
	public function friends_timeline () {}

	/* 获取粉丝 */
	public function followers ($uid, $cursor = 0 , $count = 50) 
	{
		$params = array( 
			'reqnum' => $count, 
			'startindex' => $cursor, 
			'mode' => 1 
		);
		$r = Tencent::api('friends/fanslist', $params, 'GET');

		return $r['errcode'] ? $this->_return_err($r) : $r;
	}

	/* 获取用户关系 */
	public function users_relation () {}

	/* 获取一个用户信息 */
	public function user_show () {}

	/* 批量获取用户信息 */
	public function user_show_batch () {}

	public function get_tags () {}

	/* 返回腾讯接口错误信息 */
	private function _return_err($result) 
	{
		/* TODO: 腾讯微博接口错误的处理方式 */
		/* 获取微博借口错误信息 */
		$CI = &get_instance();
		$CI->load->config('meo/txweibo_sdk_errors');
		$err_msg = $CI->config->item('txweibo_sdk_errors');

		$me_err_msg = isset($err_msg[$result['errcode']]) ? $err_msg[$result['errcode']] : 'Unknown Error !';

		return array('me_err_code'=>$result['errcode'], 'me_err_msg' => $me_err_msg);
	}

}

/* End of file wbapi_tx.php */
/* Location: ./application/libraries/wbapi_tx.php */