<?php 

/*
** Vdong 微博搜索接口
*/

class Vdong 
{

	private $_token;
	private $_gateway;

	/* 初始化 vDong参数 */
	public function __construct() 
	{
		$this->_token = '2.02f187225e1a77e923fa1bcf0bfab090b';
		$this->_gateway = 'http://api.vdong.cn/weibowom/';
	}

	/* 创建关键字 */
	public function create ($keyword)
	{
		$url = $this->_gateway . 'keyword/create.ashx';
		$post = array (
			'access_token' => $this->_token, 
			'text' => $keyword
		);

		$r = $this->_curl($url, $post);
		return json_decode($r, TRUE);
	}

	/*
	** 获取指定关键词的微博列表
	*/
	public function timeline ($keyword_id, $page = 1, $count = 50, $starttime = NULL, $endtime = NULL, $platform_type = 1)
	{
		$url = $this->_gateway . 'search/timeline.ashx';
		$post = array (
			'access_token' => $this->_token, 
			'keyword_id' => $keyword_id, 
			'starttime' => $starttime,
			'endtime' => $endtime,
			'platform_type' => $platform_type, 
			'page' => $page, 
			'count' => $count
		);

		$r = $this->_curl($url, $post);
		return json_decode($r, TRUE);
	}

	/* 删除一个关键字 */
	public function delete ($keyword_id)
	{
		$url = $this->_gateway . 'keyword/delete.ashx';
		$post = array (
			'access_token' => $this->_token, 
			'id' => $keyword_id
		);

		$r = $this->_curl($url, $post);
		return json_decode($r, TRUE);
	}

	/* 获取关键词列表 */
	public function kw_list () 
	{
		$url = $this->_gateway . 'keyword/list.ashx';
		$post = array (
			'access_token' => $this->_token
		);

		$r = $this->_curl($url, $post);
		return json_decode($r, TRUE);
	}

	/**
	 * 访问一个地址
	 * @param string  $url 访问地址
	 * @param array $post_data 传送值 数组
	 * @param string $method 传值方式 post get
	 * @param boolean $return   是否停止访问的标识
	 */
	private function _curl($url, $post_data = NULL, $method = 'POST', $return = TRUE) 
	{  
		$post_str = '';
		if( $post_data ) {
			foreach ($post_data as $k => $v) {
				$post_str .= $k . '=' . $v . '&';
			}
			$post_str = rtrim($post_str, '&');
		}
		
		// 模拟登陆
		$ch = curl_init();
		$optarr = array(
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,	//文本流形式返回
		);

		if( strtoupper($method) == 'POST' ) {
			$optarr[CURLOPT_POST] = true;
			$optarr[CURLOPT_POSTFIELDS] = $post_str;
		}
		
		if ( ! $return)
		{
			$optarr[CURLOPT_TIMEOUT] = 1;
		}

		curl_setopt_array($ch,$optarr);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $return ? $output : 'OK';
	}

}

/* End of file vdong.php */
/* Location: ./application/libraries/vdong.php */