<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 微博 Communication 模型 (舆情)
*/
class Communication_model extends CI_model
{
	
	public function __construct()
	{
		parent::__construct();

		$this->wb_table = $this->db->dbprefix('wb_accounts');

		/* 载入API接口帮助函数 */
		if ( ! function_exists('convert_sina_time')) 
			$this->load->helper('api');

		$this->sid = $this->session->userdata('staff_id');
		$this->cid = $this->session->userdata('company_id');
		$this->aid = $this->session->userdata('wb_aid');
	}

	/**
	** 获取舆情数据
	** @param $g GET参数
	** @param $limit_param 分页参数
	** 		  $limit_param = array('start'=>xx, 'limit'=>xx, 'current_page'=>xxx)
	**/
	public function get_communications($g, $limit_param) 
	{
		extract($limit_param);
		$start = intval($start) > 0 ? intval($start) : 0;
		$limit = (intval($limit) > 0 && intval($limit) < 200) ? intval($limit) : 10;

		$total = $this->get_count($g);
		if ( ! $total > 0) // 总数为 0
			return array();

		$this->db->select('wc.id, wc.type, wc.platform, wb_info, is_top, operation_status AS os, sent_at, staff.name');
		switch ($g['status']) {
			case UNTOUCHED :
				break;
			
			case CATEGORIZED :
				$this->db->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE);
				break;
			
			case REPLIED :
				$this->db->select('sr.staff_name, sr.content AS reply, sr.reply_type, sr.created_at AS reply_time')
					->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE);
				break;
				
			// case IGNORED :
			// 	break;
				
			case SUSPENDING :
				$this->db->select('s.remind_time as rm_time, s.status, s.description as rm_desc, s.id as sid');
				break;

			default:
				break;
		}
		$this->_set_where($g);

		if ($g['type'] == 'keywords') 
			$this->db->select('wk.text AS keyword')->join('wb_keyword wk', 'wk.id = wc.keyword_id', 'left');
		$feeds = $this->db->join('wb_user wu', 'wc.user_weibo_id = wu.user_weibo_id', 'left')
			->limit($limit, $start)
			->order_by('is_top', 'desc') // 降低查询效率，暂弃
			->order_by('sent_at', 'desc')
			->get()->result_array();

		/* 分类名称获取 */
		$date = date('Y-m-d H:i:s');
		$this->load->model('common/category_model', 'category');
		$categories = $this->category->get_quick_cats($this->cid);
		foreach ($feeds as &$val) {
			$val['wb_info'] = json_decode(gzuncompress(base64_decode($val['wb_info'])), TRUE);

			if (isset($val['cate_names']) && $val['cate_names']) {
				$cat_names = array();
				foreach (explode(',', $val['cate_names']) as $v) 
					isset($categories['category'][$v]) && $cat_names[] = $categories['category'][$v]['cat_name'];

				$val['cate_names'] = implode(', ', $cat_names);
			}

			/* 获取挂起任务过期状态 */
			if ($g['status'] == SUSPENDING) 
				$val['rm_expired'] = $val['rm_time'] <= $date;
		}

		$data['feeds'] = $feeds;
		$data['total_number'] = $total;
		$data['perpage'] = $limit;
		$data['page'] = $current_page;

		return $data;
	}

	/* 获取某类型状态总量 */
	public function get_count ($g) 
	{
		if ( ! in_array($g['status'], array(UNTOUCHED, CATEGORIZED, SUBMITED, REPLIED, IGNORED, SUSPENDING))) 
			return 0;

		$key = array_search($g['type'], array('mentions', 'comments', 'keywords', 'messages'));
		if ($key === FALSE) 
			return 0;

		$this->_set_where($g);
		$num = $this->db->select('COUNT(wc.id) AS total_num')->get()->row_array();
		return $num ? $num['total_num'] : 0;
	}

	/* 根据类型和状态设定数据库筛选参数 */
	private function _set_where ($g) 
	{
		/* 共同的SELECT字段 */
		$this->db->from('wb_communication wc');
		$this->db->join('staff','wc.staff_id = staff.id','left');
		switch ($g['status']) {
			case UNTOUCHED :
				break;
			
			case CATEGORIZED :
				$this->db->join('rl_wb_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					->group_by('rwcc.cmn_id');
				break;
			
			case REPLIED :
				$this->db->join('rl_wb_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					->join('staff_reply sr', 'sr.cmn_id = wc.id', 'left')
					->where('sr.result', 1)
					->group_by('rwcc.cmn_id');
				break;
				
			// case IGNORED :
			// 	break;
				
			case SUSPENDING :
				$this->db->join('suspending s', 'wc.id = s.cmn_id', 'left')
					->where('s.staff_id', $this->sid);
				break;

			default:
				break;
		}


		/* 关键词筛选 */
		if (isset($g['keyword']) && $g['keyword'])
			$this->db->like('wc.content', $g['keyword']);
		if (isset($g['fkeyword']) && $g['fkeyword'])
			$this->db->not_like('wc.content', $g['fkeyword']);
		/* 关键词筛选 */
		/* 时间筛选 */
		if (isset($g['start']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $g['start']) && $g['start'] <= date('Y-m-d')) 
			$this->db->where('wc.sent_at >', $g['start'] . ' 00:00:00');
		if (isset($g['end']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $g['end'])) 
			$this->db->where('wc.sent_at <', date('Y-m-d H:i:s', strtotime($g['end']) + 24*3600));
		/* 时间筛选 */

		$key = array_search($g['type'], array('mentions', 'comments', 'keywords', 'messages'));
		$where = array(
			'wc.type' => $key,
			'wc.company_id' => $this->cid,
			'wc.wb_aid' => $this->aid,
			'wc.operation_status' => intval($g['status']),
			'wc.is_deleted' => 0
		);
		if ($key == 2) unset($where['wc.wb_aid']); // 关键字，清除账号ID

		$this->db->where($where);
	}

	/**
	 + FUNCTION insert_message 私信消息入库
	 * 
	 + @param $account array {'wb_aid':xxx,'company_id':xxx}
	 + @param $data array 需要插入的信息的数组
	 + @param $platform ENUM {1:新浪，2:腾讯}
	 * 
	 + @return array(插入数据) OR FALSE
	**/
	public function insert_message ($account, $data, $platform = 1) 
	{
		if ( ! $data OR empty($data))
			return FALSE;

		array_walk_recursive($data, 'convert_sina_time');

		$data['createtime'] = date('Y-m-d H:i:s', $data['createtime']);

		// 私信推送信息，去重检测
		$msg = $this->db->select('id, content')
			->from('wb_communication')
			->where(array('user_weibo_id' => $data['fromusername'], 'sent_at' => $data['createtime']))
			->get()->row_array();

		if ($msg)
			return $msg;

		/* 创建这条私信的微博格式数据信息 */
		$weibo = array(
			'text' => isset($data['content']) ? $data['content'] : '', 
			'created_at' => $data['createtime']
		);
		$user_info = $this->db->select('user_weibo_id, description, registerd_at AS created_at, 
			favourites_count, idstr, followers_count, gender, verified_type, verified, 
			friends_count, location, profile_image_url, screen_name, statuses_count')
			->from('wb_user')
			->where('user_weibo_id', $data['fromusername'])
			->get()->row_array();
		if ( ! $user_info) { // 数据库中没有该用户，使用新浪接口获取该用户数据
			$wbObj = get_wb_api($account);
			$user_info = $wbObj->user_show($data['fromusername']);
			if (isset($user_info['me_err_code'])) {
				$user_info = array ( 'id' => $data['fromusername'], 'screen_name' => 'Unknown User' );
			} else {
				// 将此用户信息存入数据库
				$this->load->model('meo/wb_user_model', 'wb_user');
				$wb_user = $this->wb_user->insert_user($account['id'], $user_info, 'sina');
			}
		}

		$weibo['user'] = $user_info;
		$weibo['data'] = $data;
		$weibo['original_pic'] =  $data['original_pic'];
		$weibo['pic_urls'] =  array(
			array ('thumbnail_pic'=>$data['original_pic'])
		);

		$wb_info = base64_encode(gzcompress(json_encode($weibo, JSON_UNESCAPED_UNICODE), 9));
		$data = array(
			'wb_aid' 		=> $account['id'], 
			'type' 			=> 3, 
			'company_id' 	=> $account['company_id'], 
			'user_weibo_id' => $data['fromusername'], 
			'weibo_id' 		=> str_replace(' ', '', $data['fromusername'] . microtime()), // 必须使用一个唯一的微博ID
			'content' 		=> isset($data['content']) ? $data['content'] : '', 
			'sent_at' 		=> $data['createtime'], 
			'created_at' 	=> date('Y-m-d H:i:s'), 
			'is_top' 		=> 0, 
			'wb_info' 		=> $wb_info, 
			'platform' 		=> $platform, 
			'operation_status' => 0 
		);

		/* 根据关键词自动处理忽略和置顶 */
		/* 暂用字符串数组循环 */
		/* TODO:优化为字符串索引树 */
		if (isset($data['content']) && $data['content']) // 如果是文字消息
		{
			/* 自动关键词设置<自动置顶|自动忽略> */
			$this->load->model('meo/keyword_model', 'keyword');
			$auto_keywords = $this->keyword->get_auto_keywords($account['company_id']);
			$auto_keywords = $auto_keywords['privmsgs'];

			foreach ($auto_keywords['pintop'] as $key) 
				if (strpos($data['content'], $key) !== FALSE) 
					$data['is_top'] = 1;

			/* 在没有命中置顶的情况下，检测是否有命中忽略 */
			if ($data['is_top'] != 1) 
				foreach ($auto_keywords['ignore'] as $key) 
					if (strpos($data['content'], $key) !== FALSE) 
						$data['operation_status'] = 4;
		}

		$this->db->insert('wb_communication', $data);
		$id = $this->db->insert_id();
		return $id ? array_merge($data, array('id' => $id)) : FALSE;
	}

	/**
	 + 插入关键词搜索获取的信息
	 **/
	public function insert_keyword ($account, $data, $keyword_id, $platform = 1) 
	{
		$wb_info = base64_encode(gzcompress(json_encode($data, JSON_UNESCAPED_UNICODE), 9));
		$data = array(
			'wb_aid' 			=> $account['id'], 
			'type' 				=> 2, 
			'company_id' 		=> $account['company_id'], 
			'user_weibo_id' 	=> $data['user']['id'], 
			'status_id' 		=> isset($data['retweeted_status']) ? $data['retweeted_status']['id'] : 0, 
			'weibo_id' 			=> $data['id'], 
			'content' 			=> $data['text'], 
			'sent_at' 			=> $data['created_at'], 
			'is_top' 			=> $data['is_top'], 
			'operation_status' 	=> $data['operation_status'], 
			'created_at' 		=> date('Y-m-d H:i:s'), 
			'wb_info' 			=> $wb_info, 
			'keyword_id' 		=> $keyword_id, 
			'platform' 			=> $platform
		);
		$insert_sql = $this->db->insert_string('wb_communication', $data);
		$insert_sql .= ' ON DUPLICATE KEY UPDATE content=VALUES(content);';
		$this->db->query($insert_sql);
		return $this->db->insert_id() ? TRUE : FALSE;
	}

	/**
	 + 接口返回来的原始数据存储 (评论我的，@我的，etc)
	 + @param $account		获取数据的账号信息，id, company_id
	 + @param $data_arr	接口返回的数据(array)
	 + @param $type		数据类型('comment' | 'mentions')
	 + @param $platform	来源平台('sina' | 'tencent')
	 + 
	 + @process	遍历$data_arr, 获取要插入的数据，以及更新最新交流时间
	 + 			插入数据(communication, wb_user)
	 + 			插入数据(wb_account_user)	[更新最近交流时间]
	 + 
	 + @return 执行结果, 记入日志 (log目录：APPPATH/logs/wb_communication-[Y-m-d].log)
	 **/
	public function insert_batch ($account, $data_arr, $datatype, $taged_statuses, $auto_keywords, $platform = 'sina') 
	{
		if ( ! in_array($datatype, array('mentions', 'comments'))) return FALSE;
		if ( ! in_array($platform, array('sina', 'tencent'))) return FALSE;

		$datatype = $datatype == 'mentions' ? 0 : 1;

		$method = '_get_' . $platform . '_insert';
		$insert_vars = $this->$method($account, $data_arr, $datatype, $taged_statuses, $auto_keywords);

		$this->db->query($insert_vars['communication_sql']);			// 交流表数据
		$this->db->query($insert_vars['user_sql']);						// 用户表数据
		$this->db->query($insert_vars['account_user_sql']);				// 用户关系数据

		/* 带标签微博交互后，更新用户标签 */
		if (isset($insert_vars['user_tags']) && $insert_vars['user_tags']) 
		{
			$this->load->model('common/tag_model', 'tag_model');
			$this->tag_model->tag_weibo_user($insert_vars['user_tags'], $account);
		}

		unset($insert_vars);
		return TRUE;
	}

	/* 新浪接口数据转化为插入字符串[抓取提到我和评论我的微博] */
	private function _get_sina_insert($account, $data_arr, $type, $taged_statuses, $auto_keywords)
	{
		$communication_data = array (); 		// 交流数据
		$weibo_user_data = array (); 			// 微博用户数据
		$account_user_data = array (); 			// 账号和用户关系数据
		$user_tag_data = array (); // 用户标签数组
		$users = array (); // 辅助数组，防止重复
		foreach ($data_arr as $data) 
		{
			/* api_helper函数，转换新浪时间 */
			array_walk_recursive($data, 'convert_sina_time');
			$wb_info = base64_encode(gzcompress(json_encode($data, JSON_UNESCAPED_UNICODE), 9));	// 压缩保留最原始的信息
			// $wb_info = get_sina_wb_info($data);	// api_helper函数 获取精简信息
			$created_at = date('Y-m-d H:i:s'); 
			$user_weibo_id = $data['user']['idstr'];

			// 评论或转发原微博ID
			$status_id = 0;
			if (isset($data['status']) && isset($data['status']['id'])) // 评论微博
				$status_id = number_format($data['status']['id'], 0, '', '');
			else if (isset($data['retweeted_status']) && isset($data['retweeted_status']['id']))  // 转发微博
				$status_id = number_format($data['retweeted_status']['id'], 0, '', '');

			/* 带标签的微博检测 */
			if (array_key_exists($status_id, $taged_statuses)) 
			{
				$user_tag_data[] = array (
					'user_weibo_id'=> $user_weibo_id, 
					'tagids' => $taged_statuses[$status_id]
				);
			}

			preg_match_all( '/^<a[\w\W]*>(.*?)<\/a>/is', $data['source'], $source_match );
			if (isset($source_match[1]) && is_array($source_match[1]))
				$data['source'] = $this->db->escape($source_match[1][0]);

			/* 微博的SQL插入数据 */
			$cmn_data = array (
				'wb_aid' => $account['id'], 
				'type' => $type, 
				'company_id' => $account['company_id'], 
				'user_weibo_id' => $this->db->escape($user_weibo_id), 
				'status_id' => $this->db->escape($status_id), 
				'weibo_id' => $this->db->escape($data['idstr']), 
				'content' => $this->db->escape($data['text']), 
				'sent_at' => $this->db->escape($data['created_at']), 
				'created_at' => $this->db->escape($created_at), 
				'updated_at' => $this->db->escape($created_at), 
				'wb_info' => $this->db->escape($wb_info), 
				'keyword_id' => 0, 
				'source' => $data['source'], 
				'operation_status' => 0, 
				'is_top' => 0, 
				'platform' => 1, 
				'is_deleted' => 0
			);

			/* 根据关键词自动处理忽略和置顶 */
			/* 暂用字符串数组循环 */
			/* TODO:优化为字符串索引树 */
			foreach ($auto_keywords['pintop'] as $key) 
				if (strpos($data['text'], $key) !== FALSE) 
					$cmn_data['is_top'] = 1;

			/* 在没有命中置顶的情况下，检测是否有命中忽略 */
			if ($cmn_data['is_top'] != 1) 
				foreach ($auto_keywords['ignore'] as $key) 
					if (strpos($data['text'], $key) !== FALSE) 
						$cmn_data['operation_status'] = 4;

			$communication_data[] = $cmn_data;

			if (in_array($user_weibo_id, $users)) // 已经有这个用户的数据了
				continue ;

			$user = $data['user'];
			$user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
			$user['gender'] = $user['gender'] == 'm' ? 1 : ($user['gender'] == 'f' ? 2 : 0);
			$relation = $user['following'] == $user['follow_me'] ? ($user['following'] ? 3 : 0) : ($user['following'] ? 2 : 1);
			array_walk_recursive($user, 'insert_convert');

			$weibo_user_data[] = array (
				'user_weibo_id' => $user['id'], 
				'platform' => 1, 
				'idstr' => $user['idstr'], 
				'screen_name' => $user['screen_name'], 
				'name' => $user['name'], 
				'country_code' => 0, 
				'province_code' => $user['province'], 
				'city_code' => $user['city'], 
				'location' => $user['location'], 
				'description' => $user['description'], 
				'url' => $user['url'], 
				'profile_image_url' => $user['profile_image_url'], 
				'profile_url' => $user['profile_url'], 
				'domain' => $user['domain'], 
				'weihao' => $user['weihao'], 
				'gender' => $user['gender'], 
				'followers_count' => $user['followers_count'], 
				'friends_count' => $user['friends_count'], 
				'statuses_count' => $user['statuses_count'], 
				'favourites_count' => $user['favourites_count'], 
				'registerd_at' => $user['created_at'], 
				'geo_enabled' => $user['geo_enabled'], 
				'allow_all_act_msg' => $user['allow_all_act_msg'], 
				'allow_all_comment' => $user['allow_all_comment'], 
				'verified' => $user['verified'], 
				'verified_type' => $user['verified_type'], 
				'verified_reason' => $user['verified_reason'], 
				'avatar_large' => $user['avatar_large'], 
				'avatar_hd' => $user['avatar_hd'], 
				'bi_followers_count' => $user['bi_followers_count'], 
			);

			/* 用户交流时间, 及用户关系 */
			$account_user_data[] = array (
				'user_weibo_id' 	=> $this->db->escape($user_weibo_id), 
				'company_id' 		=> $account['company_id'], 
				'wb_aid' 			=> $account['id'], 
				'last_cmn_time' 	=> $this->db->escape($data['created_at']), 
				'relationship' 		=> $relation, 
				'created_at' 		=> $this->db->escape($created_at)
			);
		}

		$communication_sql = $this->insert_batch_string('wb_communication', $communication_data);
		$user_sql = $this->insert_batch_string('wb_user', $weibo_user_data);
		$account_user_sql = $this->insert_batch_string('wb_account_user', $account_user_data);
		$communication_sql .= ' ON DUPLICATE KEY UPDATE content=VALUES(content),updated_at=VALUES(updated_at);';
		$user_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name), name=VALUES(name), country_code=VALUES(country_code), province_code=VALUES(province_code), city_code=VALUES(city_code), location=VALUES(location), description=VALUES(description), url=VALUES(url), profile_image_url=VALUES(profile_image_url), profile_url=VALUES(profile_url), domain=VALUES(domain), weihao=VALUES(weihao), gender=VALUES(gender), followers_count=VALUES(followers_count), friends_count=VALUES(friends_count), statuses_count=VALUES(statuses_count), favourites_count=VALUES(favourites_count), geo_enabled=VALUES(geo_enabled), allow_all_act_msg=VALUES(allow_all_act_msg), allow_all_comment=VALUES(allow_all_comment), verified=VALUES(verified), verified_type=VALUES(verified_type), verified_reason=VALUES(verified_reason), avatar_large=VALUES(avatar_large), avatar_hd=VALUES(avatar_hd), bi_followers_count=VALUES(bi_followers_count);';
		$account_user_sql .= ' ON DUPLICATE KEY UPDATE last_cmn_time=VALUES(last_cmn_time), relationship=VALUES(relationship);';

		return array(
			'communication_sql' => $communication_sql, 
			'user_sql' => $user_sql, 
			'account_user_sql' => $account_user_sql, 
			'user_tags' => $user_tag_data
		);
	}

	/* 腾讯接口数据转化为插入字符串 */
	private function _get_tencent_insert($account, $data_arr, $type)
	{
		$communication_sql = "INSERT INTO {$this->db->dbprefix('wb_communication')} 
			( `wb_aid`, `type`, `company_id`, `user_weibo_id`, `status_id`, `weibo_id`, 
			`content`, `sent_at`, `created_at`, `updated_at`, `wb_info`, `location`, 
			`keyword_id`, `tags`, `source`, `platform`, `is_deleted` ) VALUES ";

		$user_sql = "INSERT INTO {$this->db->dbprefix('wb_user')} 
			( `user_weibo_id`, `platform`, `idstr`, `screen_name`, `name`, `country_code`, 
			`province_code`, `city_code`, `location`, `description`, `url`, `profile_image_url`, 
			`profile_url`, `domain`, `weihao`, `gender`, `followers_count`, `friends_count`, 
			`statuses_count`, `favourites_count`, `registerd_at`, `geo_enabled`, `allow_all_act_msg`, 
			`allow_all_comment`, `verified`, `verified_type`, `verified_reason`, `avatar_large`, 
			`avatar_hd`, `bi_followers_count` ) VALUES ";

		$account_user = array();
		$account_user_sql = "INSERT INTO {$this->db->dbprefix('wb_account_user')} 
			( `user_weibo_id`, `company_id`, `wb_aid`, `last_cmn_time`, `relationship`, `created_at` ) VALUES ";
		
		$types = array('mentions'=>0,'comments'=>1,'keywords'=>2);
		$type = $types[$type];
		foreach ($data_arr as $data) 
		{
			$data['timestamp'] = date('Y-m-d H:i:s', $data['timestamp']);
			$wb_info = base64_encode(gzcompress(json_encode($data, JSON_UNESCAPED_UNICODE), 9));	// 这句写在最上，保留最原始的信息
			$created_at = date('Y-m-d H:i:s'); 
			$data['text'] = $this->db->escape($data['text']);
			// 评论或转发原微博ID
			$status_id = ($data['source'] && is_array($data['source'])) ? $data['source']['id'] : '';
			/* 微博的SQL插入数据 */
			$communication_sql .= "('{$account['id']}', {$type}, '{$account['company_id']}', '{$data['name']}', '{$data['status']['idstr']}', '{$data['id']}', 
				{$data['text']}, '{$data['timestamp']}', '{$created_at}', '{$created_at}', '{$wb_info}', '', 
				0, '', '{$data['from']}', 2, 0 ), ";
			
			$user_name = $data['name'];
			if ( isset($account_user[$user_name])) /* 防止用户信息重复 */
			{
				if ($type != 2 && $data['timestamp'] > $account_user[$user_name]['last_cmn_time']) 
					$account_user[$user_name]['last_cmn_time'] = $data['timestamp'];
			}
			else 
			{
				$user = array(
					'id'			=> $data['openid'],
					'name'			=> $data['name'],
					'nick'			=> $data['nick'],
					'country'		=> $data['country_code'],
					'province'		=> $data['province_code'],
					'city'			=> $data['city_code'],
					'location'		=> $data['location'],
					'head'			=> $data['head'] ? $data['head'].'/50' : '',
					'head_large'	=> $data['head'] ? $data['head'].'/180' : '',
					'head_hd'		=> $data['head'] ? $data['head'].'/0' : '',
					'verified'		=> $data['isvip'] ? 1 : 0,
					'verified_type'	=> $data['isvip'] ? -2 : 999
				);

				array_walk_recursive($user, 'insert_convert');
				/* 用户的SQL插入数据 */
				$user_sql .= "( {$user['id']}, 2, {$user['id']}, {$user['nick']}, {$user['name']}, {$user['country']}, 
					{$user['province']}, {$user['city']}, {$user['location']}, '', '', {$user['head']}, 
					'', '', '', 0, 0, 0, 
					0, 0, '0000-00-00 00:00:00', 0, 0, 
					0, {$user['verified']}, {$user['verified_type']}, '', {$user['head_large']}, 
					{$user['head_hd']}, 0, {$created_at} ), ";
				/* 用户交流时间, 及用户关系 */
				$type != 2 && $account_user[$user_name] = array('last_cmn_time' => $data['timestamp']);
			}
		}

		$user_created_at = date('Y-m-d H:i:s');
		foreach ($account_user as $key => $val) 
			$account_user_sql .= "( '{$key}', '{$account['company_id']}', '{$account['id']}', '{$val['last_cmn_time']}', 0, '{$user_created_at}'), ";

		unset($data_arr, $user, $account_user);
		$communication_sql = rtrim($communication_sql, ', ');
		$account_user_sql = rtrim($account_user_sql, ', ');
		$user_sql = rtrim($user_sql, ', ');

		$communication_sql .= ' ON DUPLICATE KEY UPDATE content=VALUES(content),updated_at=VALUES(updated_at);';
		$user_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name), name=VALUES(name), country_code=VALUES(country_code), province_code=VALUES(province_code), city_code=VALUES(city_code), location=VALUES(location), profile_image_url=VALUES(profile_image_url), verified=VALUES(verified), verified_type=VALUES(verified_type), avatar_large=VALUES(avatar_large), avatar_hd=VALUES(avatar_hd);';
		$account_user_sql .= ' ON DUPLICATE KEY UPDATE last_cmn_time=VALUES(last_cmn_time);';

		return array(
			'communication_sql' => $communication_sql, 
			'user_sql' => $user_sql, 
			'account_user_sql' => $account_user_sql
		);
	}

	public function get_since_id ($wb_aid) 
	{
		/* 返回数组 */
		$since_arr = array('mentions' => 0, 'comments' => 0);

		$max_weibo_ids = $this->db->select('MAX(weibo_id) AS id, `type`')
			->from('wb_communication')
			->where('wb_aid', $wb_aid)
			->where_in('type', array(0, 1))
			->group_by('type')
			->get()->result_array();

		if ($max_weibo_ids) 
		{
			foreach ($max_weibo_ids as $val) 
			{
				if ($val['type'] == 0) 
					$since_arr['mentions'] = $val['id'];
				else 
					$since_arr['comments'] = $val['id'];
			}
		}

		return $since_arr ;
	}

	/* 获取MySQL批量插入的语句 */
	public function insert_batch_string ($tablename, $data) 
	{
		if (empty($data)) return '';

		$sql = 'INSERT INTO ' . $this->db->dbprefix($tablename) . ' (`' . implode('`, `', array_keys($data[0])) . '`) VALUES ';

		$values = array ();
		foreach ($data as $val) 
			$values[] = '(' . implode(', ', $val) . ')';

		$sql .= implode(', ', $values);

		return $sql;
	}

		 /**
			** 自动分配
			** @param $communication_id 插入的信息id
			**/
		public function wb_auto_allot($communication_id){
			//判断是否有相应权限的csr在线
			$this->load->database();
			$where = array(
					'state'=>1,
					'do_message'=>1,
					'is_deleted'=>0
				);
			$state_on = $this->db->select('id')
						->from('staff')
						->where($where)
						->get()->result_array();
			// var_dump($state_on);
			if(!$state_on){
				//如果没有相应权限的csr在线，把信息放入待分类中
				// $this->db->set('operation_status',0);
				// $this->db->where('id',$communication_id);
				// $this->db->update('wb_communication');
				// if($this->db->affected_rows()){}
					// echo $this->db->last_query();
			}else{
				//如果有相应权限的csr在线的话，判断这个信息八小时之前有没有处理信息
				$openid = $this->db->select('user_weibo_id,created_at')
					->from('wb_communication')
					->where('id',$communication_id)
					->get()->result_array();
				//获取当前信息的时间戳
				// var_dump($openid[0]['created_at']);
				$created_at = strtotime($openid[0]['created_at']);
				// var_dump($created_at);
				//8小时前时间戳
				$eight = 60*60*8;

				$created_at = $created_at - $eight;
				$created_at = date('Y-m-d H:i:s',$created_at);

				// var_dump($created_at);

				//查询在8小时之内有没有人处理过openid为$openid['openid']的信息 
				$sql = "select staff_id from me_wb_communication where user_weibo_id = '{$openid[0]['user_weibo_id']}' and created_at > '{$created_at}' and created_at <'{$openid[0]['created_at']}' and operation_status > 0 order by created_at desc";
				// echo $sql;
				$query = $this->db->query($sql);
				if($query->num_rows()>0){
					$once = $query->result_array();
				}else{
					$once = '';
				}
				// var_dump($once);exit;
				//如果8小时之前有处理的信息查询处理这条信息的人
				if($once && $once[0]['staff_id']>0){
					$sql = "select id from me_staff where id = {$once[0]['staff_id']} and state = 1 and do_message = 1 and is_deleted = 0";
					$query = $this->db->query($sql);
					if($query->num_rows()>0){
						$state = $query->result_array();
					}else{
						$state = '';
					}
					// var_dump($state);exit;
					//判断之前处理这条信息的人是否在线
					if($state && $state[0]['id']>0){
						//把这条信息分配给之前处理这条信息的人
						$sql = "update me_wb_communication set staff_id = {$state[0]['id']},operation_status = 1 where id = {$communication_id}";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
					}else{
						//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						// var_dump($state_on);
						foreach($state_on as $val){
							$sql = "select count(c.id) from me_wb_communication as c where c.staff_id = {$val['id']} and c.operation_status = 1";
							// echo $sql;
							$query = $this->db->query($sql)->row_array();
							if($query){
								$communication_num[] = array('count(c.id)'=>$query['count(c.id)'],'id'=>$val['id']);
							}else{
								$communication_num[] = array('count(c.id)'=>0,'id'=>$val['id']);
							}
						}
						// var_dump($communication_num);
						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						$sql = "update me_wb_communication set staff_id = {$min_num_person},operation_status = 1 where id = {$communication_id} ";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
					}
				}else{
					//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						foreach($state_on as $val){
							$sql = "select count(c.id) from me_wb_communication as c where c.staff_id = {$val['id']} and c.operation_status = 1";
							// echo $sql;
							$query = $this->db->query($sql)->row_array();
							if($query){
								$communication_num[] = array('count(c.id)'=>$query['count(c.id)'],'id'=>$val['id']);
							}else{
								$communication_num[] = array('count(c.id)'=>0,'id'=>$val['id']);
							}
						}
						// var_dump($communication_num);
						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						$sql = "update me_wb_communication set staff_id = {$min_num_person},operation_status = 1 where id = {$communication_id} ";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
				}
			}
		}

}