<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 微博接口的调用，获取微博，评论，转发，粉丝，etc
*/
class Weibo extends ME_Controller {

	public $wbapiObj;       // API 接口对象
	private $_wb_aid;       // 微博账号ID
	private $_oainfo;       // OAuth信息(APP_KEY, APP_SECRET, ACCESS_TOKEN, REFRESH_TOKEN)

	public function __construct($paramsData = '')
	{
		parent::__construct();
		/* 初始信息 */  
		if ($this->input->is_cli_request()) {
			/* 1. [从命令行初始化] */
			$args = array();
			parse_str($paramsData, $args);
			if (empty($args) OR ! isset($args['wb_aid'])) exit('no params given !');
			$this->_wb_aid = $args['wb_aid'];
		} else if ($this->session->userdata('wb_aid')) {
			/* 2. [从Session初始化]*/
			$this->_wb_aid = $this->session->userdata('wb_aid');
		} else if ($this->input->get_post('wb_aid')) {
			/* 3. [从URL初始化] */
			$this->_wb_aid = $this->input->get_post('wb_aid');
		} else {
			/* 4. [初始化失败] */
			exit('no params given !');
		}

		$this->load->helper('api');
		$this->load->model('system/account_model', 'account');
		$this->load->model('meo/communication_model', 'communication');
		$this->_oainfo = $this->account->get_oa_info($this->_wb_aid);
		$this->wbapiObj = get_wb_api($this->_oainfo);
	}

	/* API 获取@我的舆情 */
	public function get_mentions () 
	{
		$since_ids = $this->communication->get_since_id($this->_wb_aid);
		$since_id = isset($since_ids['mentions']) ? $since_ids['mentions'] : 0;

		/* 分新浪腾讯处理 */
		if ($this->wbapiObj instanceof Wbapi_sina) // 使用新浪的接口
		{
			for ($page = 1, $next = TRUE; $next; $page++) 
			{
				$mentions = $this->wbapiObj->mentions($page, $count = 50, $since_id);

				/* 接口调用出错 */
				header('Content-Type: text/html; Charset=UTF8');
				if (isset($mentions['error_code']) OR isset($mentions['code'])) 
					exit(json_encode($mentions));

				$next = isset($mentions['next_cursor']) ? $mentions['next_cursor'] : FALSE;

				/* 数据插入 */
				if (isset($mentions['statuses']) && $mentions['statuses'])
					if ( ! $this->communication->insert_batch($this->_oainfo, $mentions['statuses'], 'mentions', 'sina'))
						break;
			}
		}
		else // 使用腾讯的接口
		{
			for ($pageflag = 0, $pagetime = 0, $next = TRUE; $next; $pageflag = 1) 
			{
				$mentions = $this->wbapiObj->mentions($count = 50, $pageflag, $pagetime);
				$next = isset($mentions['data']['hasnext']) && ! $mentions['data']['hasnext'];  // TX 微博的 API, 坑爹[hasnext : 0-有，1-无]

				if (isset($mentions['data']['info']) && $mentions['data']['info']) {
					$last = end($mentions['data']['info']);
					$pagetime = $last['timestamp'];
				}

				/* 数据插入 */
				if (isset($mentions['data']['info']) && $mentions['data']['info'])
					if ( ! $this->communication->insert_batch($this->_oainfo, $mentions['data']['info'], 'mentions', 'tencent'))
						break;
			}
		}

		return ;
	}

	/* 获取评论我的 */
	public function get_comments_to_me () 
	{

		$since_ids = $this->communication->get_since_id($this->_wb_aid);
		$since_id = isset($since_ids['comments']) ? $since_ids['comments'] : 0;

		if ($this->wbapiObj instanceof Wbapi_sina) // 使用新浪的接口
		{
			for ($page = 1, $next = TRUE; $next; $page++) 
			{
				$comments = $this->wbapiObj->comments_to_me($page, $count = 50, $since_id);
				$next = isset($comments['next_cursor']) ? $comments['next_cursor'] : FALSE;

				/* 数据插入 */
				if (isset($comments['comments']) && $comments['comments']) 
					if ( ! $this->communication->insert_batch($this->_oainfo, $comments['comments'], 'comments', 'sina'))
						break;          // 数据插入失败立即停止
			}

			/* 接口调用出错 */
			header('Content-Type: text/html; Charset=UTF8');
			if (isset($comments['error_code']) OR isset($comments['code'])) 
				exit(json_encode($comments));
		}
		else // 腾讯微博暂时还没有这个接口
		{
			exit('没有相关接口！');
		}
	}

	/* 获取微博粉丝 */
	public function get_followers () 
	{
		$account = $this->db->select('weibo_id')
			->from('wb_account')
			->where('id', $this->_wb_aid)
			->get()->row_array();

		header('Content-Type: text/html; Charset=UTF8');
		$this->load->model('meo/wb_user_model', 'wb_user');
		for ($next = TRUE, $cursor = 0; $next != 0; $next = $cursor) 
		{
			$followers = $this->wbapiObj->followers($account['weibo_id'], $cursor, 50);

			if (isset($followers['me_err_code'])) {
				exit(json_encode($followers));
				return ;
			} else {
				// 获取下一页指针
				if ($this->wbapiObj instanceof Wbapi_sina) {
					$cursor = isset($followers['next_cursor']) ? $followers['next_cursor'] : 0;         /* 新浪接口的API */
					if (count($followers['users']) > 0)
						$this->wb_user->insert_batch($this->_wb_aid, $followers['users'], 'sina', TRUE);    /* 入库 */
				} else { 
					$cursor = $followers['data']['hasnext'] == 0 ? $followers['data']['nextstartpos'] : 0;      /* 腾讯接口的API */
					if (count($followers['data']['info']) > 0)
						$this->wb_user->insert_batch($this->_wb_aid, $followers['data']['info'], 'tencent', TRUE);  /* 入库 */
				}
			}
		}
	}

	// tag need format like status_arr['data'] = 't_tagid1_tagid2'
	public function send_status($status_arr = array('pic_path' => '', 'send_at' => '', 'sid' => '', 'text' => '', 'tags' => array()))
	{
		
		$status_arr = $this->input->post(NULL, TRUE);
		// no text || no repost || no pic to upload
		if (!$status_arr['text'] && !$status_arr['sid'] && !$status_arr['pic_path'])
		{
			$this->meret('', MERET_EMPTY, 'necessary info is empty');
			return;
		}
		if (empty($status_arr['send_at']))
		{
			if (empty($status_arr['sid']))
			{
				if (empty($status_arr['pic_path']))
				{
					$rs = $this->wbapiObj->update($status_arr['text']);
				}
				else
				{
					if (strrpos($status_arr['pic_path'], 'http') === false) {
						$rs = $this->wbapiObj->upload($status_arr['text'], '../'.$status_arr['pic_path']);
					}else{
						$rs = $this->wbapiObj->upload($status_arr['text'], $status_arr['pic_path']);
					}
				}
			}
			else
			{
				$rs = $this->wbapiObj->repost($status_arr['sid'], $status_arr['text']);
			}
		}
		else
		{
			$send_at = strtotime($status_arr['send_at']);
			if ($send_at - time() <= 600) {
				$this->meret(NULL, MERET_BADREQUEST, '定时发送时间请至少设定在10分钟之后！');
				return ;
			}
			$status_arr['company_id'] = $this->session->userdata('company_id');
			$status_arr['wb_aid'] = $this->session->userdata('wb_aid');
			$status_arr['type'] = !empty($status_arr['sid']) ? 2 : 99;
			$status_arr['pic_path'] = !empty($status_arr['pic_path']) ? '../'.$status_arr['pic_path'] : '';
			$status_arr['set_at'] = time();
			$status_arr['send_at'] = $send_at;
			$status_arr['staff_id'] = $this->session->userdata('staff_id');
			if (!empty($status_arr['tags']))
			{
				$tags = implode('_', $status_arr['tags']);
				$status_arr['data'] = 't-'.$tags;
			}
			else
			{
				$status_arr['data'] = '';
			}
			unset($status_arr['tags']);
			
			$this->load->model('meo/Wb_Send_Crontab_model', 'wb_send_crontab');
			$rs = $this->wb_send_crontab->insert('wb_send_crontab', $status_arr);

			$this->meret($rs, MERET_OK, '发布成功！');
				return;
		}

		if (!isset($rs['me_err_code']))     // 出错后会返回这个字段
		{
			if (empty($status_arr['send_at']))
			{
				$rs = $rs['data'];
				$timeline_arr['company_id'] = $this->session->userdata('company_id');
				$timeline_arr['wb_aid'] = $this->session->userdata('wb_aid');
				$timeline_arr['weibo_id'] = number_format($rs['id'], 0, '', '');
				$timeline_arr['text'] = $status_arr['text'];
				$timeline_arr['is_retweeted'] = !empty($status_arr['sid']) ? 1 : 0;
				$timeline_arr['created_at'] = strtotime($rs['created_at']);
				$timeline_arr['me_sent'] = 1;
				$timeline_arr['wb_info'] = json_encode($rs);

				$this->load->model('meo/wb_user_timeline_model', 'wb_user_timeline');

				$rs = $this->wb_user_timeline->insert('wb_user_timeline', $timeline_arr);

				if (! empty($rs['insert_id']) && ! empty($status_arr['tags']))
				{
					if ($this->bind_wb_tag($rs['insert_id'], $status_arr['tags']))
					{
						$this->meret($rs, MERET_OK, '发布成功！');
						return;
					}
					else
					{
						$this->meret($rs, MERET_OTHER, 'failed to bind tags with weibo');
						return;
					}
				}
				else
				{
					$this->meret($rs, MERET_OK, '发布成功！');
					return;
				}
			}
			else
			{
				$this->meret($rs, MERET_OK, '发布成功！');
				return;
			}
		}
		else
		{
			$this->meret($rs, MERET_SVRERROR, $rs['me_err_msg']);
		}
	}

	public function bind_wb_tag($wb_id, $tags)
	{
		if (! empty($tags))
		{
			$rl_wb_user_timeline_tag = array();
			foreach ($tags as $tag_id)
			{
				$rl_wb_user_timeline_tag[] = array(
					'wb_id' => $wb_id,
					'tag_id' => $tag_id
					);
			}
			$this->load->model('meo/Wb_User_Timeline_model', 'wb_user_timeline');

			if ($this->wb_user_timeline->insert_batch('rl_wb_user_timeline_tag', $rl_wb_user_timeline_tag))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
	}

	// 获取短链接
	function get_shorturl()
	{
		$url = $this->input->get_post('url', TRUE);
		$rs = $this->wbapiObj->shorten($url);
		if (isset($rs['me_err_code']) && !empty($rs['me_err_code']))
		{
			$this->meret($rs, MERET_APIERROR, $rs['me_err_msg']);
		}
		else
		{
			$this->meret($rs);
		}
	}

	// 根据mid获取原微博
	function get_repost()
	{
		$weibo_url = $this->input->get('weibo_url', TRUE);

		if ( ! preg_match('/^http:\/\/weibo.com\/[\d]+\//', $weibo_url)) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '您输入的微博地址不合法！');
			return ;
		}

		$url = parse_url($weibo_url);
		$path = $url['path'];
		$arr = explode('/', $path);
		if (isset($arr[2]) && !empty($arr[2]))
		{
			$mid = $arr[2];
		}
		else
		{
			$mid = $arr[0];
		}

		$id_rst = $this->wbapiObj->queryid($mid);
		if (-1 == $id_rst['id'])
		{
			$this->meret($id_rst, MERET_EMPTY, 'valid data, please check api param isBase62');
			return;
		}

		if (!empty($id_rst['me_err_code']))
		{
			$this->meret($id_rst, $id_rst['me_err_code'], $id_rst['me_err_msg']);
			return;
		}
		$statusid = $id_rst['id'];

		$repost_rst = $this->wbapiObj->show($statusid);
		if (isset($repost_rst['error_code']) && !empty($repost_rst['error_code']))
		{
			$this->meret($repost_rst, $repost_rst['error_code'], $repost_rst['error']);
			return;
		}

		$repost_rst['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['created_at']));

		$repost_rst['user']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['user']['created_at']));

		if (isset($repost_rst['retweeted_status']))
		{
			$repost_rst['retweeted_status']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['retweeted_status']['created_at']));
			$repost_rst['retweeted_status']['user']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['retweeted_status']['user']['created_at']));
		}

		$this->meret($repost_rst, MERET_OK);
		return;
	}

	// 获取关注人的微博
	public function get_friends_timeline()
	{
		$params = $this->input->get(NULL, TRUE);
		$current_page = isset($params['current_page']) ? $params['current_page'] : 1;
		$items_per_page = isset($params['items_per_page']) ? $params['items_per_page'] : 20;
		// $pageflag = isset($params['pageflag']) ? $params['pageflag'] : 0;
		// $pagetime = isset($params['pagetime']) ? $params['pagetime'] : 0;
		$rs = $this->wbapiObj->friends_timeline($current_page, $items_per_page);
		$rs['current_page'] = $current_page;
		$rs['items_per_page'] = $items_per_page;
		if (isset($rs['error_code']) && !empty($rs['error_code']))
		{
			$this->meret($rs, $rs['error_code'], $rs['error']);
		}
		else
		{
			/* api_helper 函数，递归转换新浪微博时间 */
			array_walk_recursive($rs, 'convert_sina_time');
			$this->meret($rs, MERET_OK, 'success');
		}
		return;
	}

	// 获取已发微博
	public function get_user_timeline()
	{

		$params = $this->input->get(NULL, TRUE);

		$current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
		$items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
		$rs = $this->wbapiObj->user_timeline(NULL, $current_page, $items_per_page);

		if (! isset($rs['error']))
		{
			$this->load->model('meo/Wb_User_Timeline_model', 'wb_user_timeline');
			foreach ($rs['statuses'] as $key => $val)
			{
				$data = $this->wb_user_timeline->get_status_tags($val['idstr']);
				if ($data)
				{
					if ($data['wb_id']) {
						$rs['statuses'][$key]['wb_id'] = intval($data['wb_id']);
					}
					if (isset($data['tags']) && trim($data['tags']))
					{
						$rs['statuses'][$key]['tags'] = $data['tags'];
						$rs['statuses'][$key]['tagids'] = $data['tagids'];
					}
					$rs['statuses'][$key]['me_sent'] = $data['me_sent'];
					$rs['statuses'][$key]['is_deleted'] = $data['is_deleted'];
				}
				$rs['statuses'][$key]['created_at'] = date('Y-m-d H:i:s', strtotime($val['created_at']));
				if (isset($rs['statuses'][$key]['retweeted_status']))
				{
					$rs['statuses'][$key]['retweeted_status']['created_at'] = date('Y-m-d H:i:s', strtotime($rs['statuses'][$key]['retweeted_status']['created_at']));
				}
			}
			$rs['current_page'] = $current_page;
			$rs['items_per_page'] = $items_per_page;
			$status = MERET_OK;
			$msg = 'success';
		}
		else
		{
			$status = MERET_SVRERROR;
			$msg = 'api return error';
		}

		$this->meret($rs, $status, $msg);
		return;
	}

	// 获取经过筛选的已发微博
	public function get_timeline_filter()
	{
		$params = $this->input->post(NULL, TRUE);

		$this->load->model('meo/Wb_User_Timeline_model', 'model');
		$res = $this->model->get_filtered_timeline($params);

		if (is_string($res)) 
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	// 获取微博转发列表
	public function get_status_reposts()
	{
		$params = $this->input->get(NULL, TRUE);

		$sid = $params['sid'];
		$current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
		$items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
		$since_id = isset($params['since_id']) ? $params['since_id'] : 0;
		$max_id = isset($params['since_id']) ? $params['since_id'] : 0;
		$filter_by_author = isset($params['since_id']) ? intval($params['filter_by_author']) : 0;

		$rs = $this->wbapiObj->reposts($sid, $current_page, $items_per_page, $since_id, $max_id, $filter_by_author);

		if (! isset($rs['error']) && empty($rs['error']))
		{
			$this->meret($rs, MERET_OK, 'success');
		}
		else
		{
			$this->meret($rs, $rs['error_code'], $rs['error']);
		}
		return;
	}

	// 获取微博评论列表
	public function get_status_comments()
	{
		$params = $this->input->get(NULL, TRUE);

		$sid = $params['sid'];
		$current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
		$items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
		$since_id = isset($params['since_id']) ? $params['since_id'] : 0;
		$max_id = isset($params['since_id']) ? $params['since_id'] : 0;
		$filter_by_author = isset($params['since_id']) ? intval($params['filter_by_author']) : 0;

		$rs = $this->wbapiObj->comments($sid, $current_page, $items_per_page, $since_id, $max_id, $filter_by_author);

		if (! isset($rs['error']) && empty($rs['error']))
		{
			$this->meret($rs, MERET_OK, 'success');
		}
		else
		{
			$this->meret($rs, $rs['error_code'], $rs['error']);
		}
		return;
	}

	/* 绑定标签，如果绑定标签为空，则清空标签 */
	function bind_sent_tags()
	{
		$weibo_id = $this->input->post('weibo_id', TRUE);
		$tags = $this->input->post('tags', TRUE);
		$wb_aid = $this->session->userdata('wb_aid');

		$this->load->model('meo/wb_user_timeline_model', 'wb_user_timeline');

		$item = $this->db->select('id')
			->from('wb_user_timeline')
			->where(array('weibo_id'=>$weibo_id, 'wb_aid'=>$wb_aid))
			->get()->row_array();

		/* 系统中没有记录 */
		if ( ! $item) {
			$wb_data = $this->wbapiObj->show($weibo_id);
			if (isset($wb_data['error']) && !empty($wb_data['error']))
			{
				// print_r($wb_data);
				// print_r($weibo_id);
				$this->meret(NULL, MERET_APIERROR);
				return;
			}
			$item['company_id'] = $this->session->userdata('company_id');
			$item['wb_aid'] = $this->session->userdata('wb_aid');
			$item['weibo_id'] = $wb_data['idstr'];
			$item['text'] = $wb_data['text'];
			$item['is_retweeted'] = !empty($wb_data['retweeted_status']) ? 1 : 0;
			$item['created_at'] = strtotime($wb_data['created_at']);
			$item['me_sent'] = 0;
			$item['wb_info'] = json_encode($wb_data, JSON_UNESCAPED_UNICODE);
			$sql_rs = $this->wb_user_timeline->insert('wb_user_timeline', $item);
			$item['id'] = $this->db->insert_id();

			if ( ! $item['id']) 
			{
				$this->meret(NULL, MERET_DBERR, '系统繁忙，请稍后尝试！');
				return ;
			}
		}

		$this->db->where(array('wb_id'=>$item['id']))->delete('rl_wb_user_timeline_tag');

		if (is_array($tags) && $tags) // 插入新的标签，为空则不处理
		{
			$item_tags = array();
			foreach ($tags as $tag_id) 
				$item_tags[] = array( 'wb_id' => $item['id'], 'tag_id' => $tag_id );
			$res = $this->wb_user_timeline->insert_batch('rl_wb_user_timeline_tag', $item_tags);
		}

		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '标签没有修改！');
	}

	public function destroy()
	{
		$idstr = $this->input->post('idstr', TRUE);
		
		$result = $this->wbapiObj->destroy($idstr);
		return $result;
		
	}

	public function delete_user_timeline(){
		$params = $this->input->post();
		$wb_id = $params['wb_id'];
		if (isset($params['idstr'])) {
			$idstr = $params['idstr'];
			$data_sina = $this->destroy($idstr);
		}
		$this->load->model('meo/wb_user_timeline_model', 'wb_user_timeline');
		$data_local = $this->wb_user_timeline->delete_user_timeline($wb_id);
		if ($data_local) {
			$this->meret($data_local);
		}elseif ($data_sina) {
			$this->meret($data_sina);
		}else{
			$this->meret(NULL, MERET_DBERR);
		}
		return;
	}

	//通过sid 获取微博内容
	public function show_by_sid($sid = NULl){
		$params = $this->input->get();
		if (isset($params['sid'])) {
			$sid = $params['sid'];
			$repost_rst = $this->wbapiObj->show($sid);
			
			if (isset($repost_rst['error_code']) && !empty($repost_rst['error_code']))
			{
				$this->meret($repost_rst, $repost_rst['error_code'], $repost_rst['error']);
				return;
			}

			$repost_rst['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['created_at']));

			$repost_rst['user']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['user']['created_at']));

			if (isset($repost_rst['retweeted_status']))
			{
				$repost_rst['retweeted_status']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['retweeted_status']['created_at']));
				$repost_rst['retweeted_status']['user']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['retweeted_status']['user']['created_at']));
			}
		}else{
			$repost_rst = '';
		}
		if ($repost_rst)
		{
			$status = MERET_OK;
		}
		else
		{
			$status = MERET_DBERR;
		}
		$this->meret($repost_rst, $status);
	}

}

/* End of file weibo.php */
/* Location: ./application/controllers/meo/weibo.php */