<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Wb_user 模型 (微博用户)
*/
class Wb_user_model extends CI_model
{
	
	public function __construct()
	{
		parent::__construct();
		$this->wb_aid = $this->session->userdata('wb_aid');
	}

	/* 获取微博用户基本信息 */
	public function get_wb_info ($wb_user_id)
	{
		$wb_user_info = $this->db->select('*')
			->from('wb_user')
			->where('id', $wb_user_id)
			->get()->row_array();

		return $wb_user_info;
	}

	/* 获取用户详细信息，属组、标签 */
	public function get_detailed_info ($wb_user_id) 
	{
		$wb_info = $this->get_wb_info($wb_user_id);
		$group_info = $this->get_user_group($wb_user_id);
		$tag_info = $this->get_user_tag($wb_user_id);

		return array (
			'basic' => $wb_info, 
			'group' => $group_info, 
			'tag' => $tag_info
		);
	}

	/* 获取用户属组, 锁定组 */
	public function get_user_group ($wb_user_id)
	{
		$group_info = $this->db->select("DISTINCT group_id, group_name", FALSE)
			->from('rl_wb_group_user gu')
			->join('wb_group g', 'gu.group_id = g.id', 'LEFT')
			->where('wb_user_id', $wb_user_id)
			->get()->result_array();

		return $group_info;
	}

	/* 手动给用户打标签 */
	public function tag_user ($user_id, $tags) 
	{
		$wb_aid = $this->session->userdata('wb_aid');
		/* 删除所有纯手动标签 */
		$this->db->where(array('wb_user_id'=>$user_id, 'wb_aid'=>$wb_aid))
			->where("SUM(`link_tag_hits`, `rule_tag_hits`, `event_tag_hits`) < 1", NULL, TRUE)
			->delete('rl_wb_user_tag');
		/* 非纯手动标签置手动挡为0 */
		$this->db->set('if_manual', 0)
			->where(array('wb_user_id'=>$user_id, 'wb_aid'=>$wb_aid, 'if_manual'=>1))
			->update('rl_wb_user_tag');

		if ( ! empty($tags)) {
			$insert_tag = "INSERT INTO {$this->db->dbprefix('rl_wb_user_tag')} 
				(`wb_user_id`, `tag_id`, `wb_aid`, `if_manual`) VALUES ";
			foreach ($tags as $tag_id) 
				if (intval($tag_id) > 0)
					$insert_tag .= "({$user_id}, {$tag_id}, {$wb_aid}, 1), ";

			$insert_tag = trim($insert_tag, ', ');
			if (preg_match('/1)$/i', $insert_tag)) // 有数据
				$this->db->query($insert_tag . ' ON DUPLICATE KEY UPDATE if_manual=1');
		}
		return $this->db->affected_rows();
	}

	/* 获取用户标签 */
	public function get_user_tag ($wb_user_id) 
	{
		$tag_info = $this->db->select('tag_id, tag_name')
			->from('rl_wb_user_tag')
			->where('wb_user_id', $wb_user_id)
			->order_by("manual_tag_hits")
			->get()->result_array();

		return $tag_info;
	}

	/* 获取用户交流历史, 过滤了关键字的历史 */
	public function communications ($user_weibo_id, $wb_aid = 0, $limit = array()) 
	{
		if ( ! $wb_aid)
			$wb_aid = $this->session->userdata('wb_aid');

		$page = intval($limit['page']) > 0 ? intval($limit['page']) : 0;
		$perpage = (intval($limit['perpage']) > 0 && intval($limit['perpage']) < 20) ? intval($limit['perpage']) : 10;

		$total = $this->db->from('wb_communication wc')
			->where(array('user_weibo_id'=>$user_weibo_id, 'wb_aid'=>$wb_aid, 'type <>'=>2))
			->get()->num_rows();

		if ($total < 1)
			return '没有交流记录！';

		$offset = $page > ceil($total / $perpage) ? 0 : ($page - 1) * $perpage;
		/* 取入库的用户记录 */
		$communications = $this->db->select("id, content, sent_at, type, weibo_id, wb_info, operation_status AS os")
			->from('wb_communication wc')
			->where(array('user_weibo_id'=>$user_weibo_id, 'wb_aid'=>$wb_aid, 'type <>'=>2))
			->order_by('sent_at', 'DESC')
			->limit($perpage, $offset)
			->get()->result_array();

		foreach ($communications as &$val) {
			if ($val['os'] == 3) { // 获取回复内容
				$val['reply'] = $this->db->select('created_at, reply_type, content, result, staff_id')
					->from('staff_reply sr')
					->where(array('cmn_id'=>$val['id']))
					->get()->result_array();
			}
		}

		return array (
			'feeds' => $communications, 
			'current_page' => $page,
			'items_per_page' => $perpage,
			'total_number' => $total
		);
	}

	/**
	 * 获取微博用户数据
	 * @param $where_param 筛选参数
	 * @param $limit_param 分页参数
	 * 		 $limit_param = array('start'=>xx, 'limit'=>xx)
	**/
	public function get_list ()
	{
		$gp = $this->input->get_post(NULL, TRUE);

		$sum = $this->members_count($gp);

		// 获取用户信息
		$this->db->select("wu.id,wu.user_weibo_id,wu.screen_name,wu.location,wu.profile_image_url")
					->from('wb_user wu')
					->join('wb_account_user wau', 'wu.user_weibo_id = wau.user_weibo_id', 'left');
		$this->_set_where($gp);
		$this->_set_limit($gp,$sum);
		$userData = $this->db->get()->result_array();
//		echo $this ->db->last_query();
//		var_dump($userData);
//		// 获取用户ID数组
		$userIds = array();
		foreach($userData as $userv){
			array_push($userIds,$userv['id']);
		}
//
		// 获取用户标签
		$tagNameData = array();
		if($userIds){
			$this->db->select("ut.wb_user_id,t.tag_name")
				->from('rl_wb_user_tag ut')
				->join('tag t','t.id = ut.tag_id','left')
				->where_in('ut.wb_user_id',$userIds);
			$tagNameData = $this->db->get()->result_array();
//			echo $this->db->last_query();
		}
//
		$newTagNameData = array();
		if(count($tagNameData) > 0){
			foreach($tagNameData as $tagv){
				if(!array_key_exists($tagv['wb_user_id'],$newTagNameData)){
					$newTagNameData[$tagv['wb_user_id']] = '';
				}
				$newTagNameData[$tagv['wb_user_id']] .= $tagv['tag_name'].',';
			}
		}
////		// 获取用户所属组
		$groupNameData =  array();
		if($userIds){
			$this->db->select("gu.wb_user_id,g.group_name")
				->from('rl_wb_group_user gu')
				->join('wb_group g','g.id = gu.group_id','left')
				->where_in('gu.wb_user_id',$userIds);
			$groupNameData = $this->db->get()->result_array();
		}
		$newGroupNameData = array();
		if(count($groupNameData) > 0){
			foreach($groupNameData as $groupv){
				if(!array_key_exists($groupv['wb_user_id'],$newGroupNameData)){
					$newGroupNameData[$groupv['wb_user_id']] = '';
				}
				$newGroupNameData[$groupv['wb_user_id']] .= $groupv['group_name'].',';
			}
		}
		foreach($userData as $userk=>$userv){
			if(array_key_exists($userv['id'],$newTagNameData)){
				$userData[$userk]['tag_name'] =	rtrim($newTagNameData[$userv['id']],',');
			}else{
				$userData[$userk]['tag_name'] = NULL;
			}
			if(array_key_exists($userv['id'],$newGroupNameData)){
				$userData[$userk]['group_name'] = rtrim($newGroupNameData[$userv['id']],',');
			}else{
				$userData[$userk]['group_name'] = NULL;
			}
		}
		$data['users'] = $userData;
		if (empty($data['users']))
		{
			return '没有找到用户！';
		}

		$data['total_number'] = $sum;
		$data['perpage'] = isset($gp['perpage']) && intval($gp['perpage']) > 0 ? intval($gp['perpage']) : 20;
		$data['page'] = isset($gp['perpage']) && intval($gp['page']) > 0 ? intval($gp['page']) : 1;

		return $data;
	}

	/* 数据库筛选 */
	private function _set_where ($gp, $tag = FALSE) 
	{
		// 地区
		if (isset($gp['city']) && $gp['city'])
		{
			$location = $gp['city'];
		} 
		else if (isset($gp['province']) && $gp['province'])
		{
			$location = $gp['province'];
		}
		if (isset($location))
		{
			$this->db->like('location', $location);
		}

		/* 微博身份 */
		if (isset($gp['verify_type_sina']) && in_array($gp['verify_type_sina'], array(-1, 0, 2, 200))){
			if ($gp['verify_type_sina'] == 2) {
				$verified_type = array(2,3,4,5,6,7);
				$this->db->where_in('verified_type', $verified_type);
			}elseif ($gp['verify_type_sina'] == 200) {
				$verified_type = array(200,220);
				$this->db->where_in('verified_type', $verified_type);
			}else{
				$this->db->where('verified_type', $gp['verify_type_sina']);
			}
		}

		/* 性别 */
		if (isset($gp['gender']) && in_array($gp['gender'], array(0, 1, 2)))
			$this->db->where('gender', intval($gp['gender']));

		/* 粉丝量 */
		if (isset($gp['followers_count']) && $gp['followers_count']) {
			$range = explode('-', $gp['followers_count']);
			$min = intval($range[0]) > 0 ? intval($range[0]) : 0;
			$max = isset($range[1]) ? intval($range[1]) : 0;
			if ($max > $min) 
				$this->db->where("followers_count BETWEEN {$min} AND {$max}", NULL, FALSE);
			else 
				$this->db->where('followers_count >', $min);
		}

		/* 微博量 */
		if (isset($gp['statuses_count']) && $gp['statuses_count']) {
			$range = explode('-', $gp['statuses_count']);
			$min = intval($range[0]) > 0 ? intval($range[0]) : 0;
			$max = isset($range[1]) ? intval($range[1]) : 0;
			if ($max > $min) 
				$this->db->where("statuses_count BETWEEN {$min} AND {$max}", NULL, FALSE);
			else 
				$this->db->where('statuses_count >', $min);
		}

		/* 不同账号 */
		if (isset($gp['account']) && intval($gp['account']) > 0)
			$this->db->where('wau.wb_aid', $gp['account']);
		else 
			$this->db->where('wau.wb_aid', $this->session->userdata('wb_aid'));

		/* 地区 */
		if (isset($gp['province']) && intval($gp['province']) > 0) 
			$this->db->where('wu.province_code', intval($gp['province']));

		//用户组id
		if (isset($gp['group_id'])){
			$this->db->join('rl_wb_group_user rwg', 'rwg.wb_user_id = wu.id', 'left');
			$this->db->where('rwg.group_id', intval($gp['group_id']));
		}

		// 筛选tag
		if ($tag) 
			$this->db->join('rl_wb_user_tag rwut', 'rwut.wb_user_id = wu.id', 'left');
		if (isset($gp['tags']) && ! empty($gp['tags'])){
			$this->db->join('rl_wb_user_tag rwut', 'rwut.wb_user_id = wu.id', 'left');
			$this->db->where_in('rwut.tag_id', $gp['tags']);
		}


		// 活动历史
		if (isset($gp['events']) && is_array($gp['events'])) 
		{
			$this->db->join('event_participant ep', 'ep.wb_user_id = wu.id', 'left');
			$this->db->where(array('ep.participated_at <>'=>'0000-00-00 00:00:00'));
			$this->db->where_in('ep.event_id', array_unique($gp['events']));
		}
		
		/* 在筛选参数有交流历史或关键词的情况下，连接交流表 */
		if ((isset($gp['interacts']) && is_array($gp['interacts'])) OR (isset($gp['keywords']) && is_array($gp['keywords'])))
			$this->db->join('wb_communication wc', 'wc.user_weibo_id = wu.user_weibo_id', 'left');

		// 交流历史
		if (isset($gp['interacts']) && is_array($gp['interacts'])) 
			$this->db->where_in('wc.status_id', array_unique($gp['interacts']));

		// 舆情关键词
		if (isset($gp['keywords']) && is_array($gp['keywords'])) 
			$this->db->where(array('wc.type'=>2))->where_in('wc.keyword_id', array_unique($gp['keywords']));

		return ;
	}

	/* 分页设定 [默认每页显示20条] */
	private function _set_limit ($gp, $sum) 
	{
		$page = isset($gp['page']) ? $gp['page'] : 1;
		$perpage = isset($gp['perpage']) ? $gp['perpage'] : 20;

		$page = intval($page) > 0 ? intval($page) : 1;
		$perpage = intval($perpage) > 0 ? intval($perpage) : 20;
		$diff = ceil($sum / $perpage);
		if ($page <= $diff)
			$this->db->limit($perpage, ($page - 1) * $perpage);
		else 
			$this->db->limit($perpage);
	}

	/**
	 * 获取筛选参数的用户总量
	 * @param $filter_params (string | array)
	 * @return 用户总量
	**/
	public function members_count ($filter_params) 
	{
		if (is_string($filter_params)) 
			parse_str($filter_params, $filter_params);
		else if ( ! is_array($filter_params))
			return 0;

		$this->db->from('wb_user wu')
			->select('COUNT(DISTINCT wu.id) count')
			->join('wb_account_user wau', 'wu.user_weibo_id = wau.user_weibo_id', 'left');
		$this->_set_where($filter_params);
		/* 改为选择总数的方式 */
		$rst = $this->db->get()->result_array();
//		echo $this->db->last_query();
		$rst = $rst[0]['count'];
//		echo $this->db->last_query();
		return $rst ? $rst : 0;
	}

	/* 获取筛选参数的用户ID，NAME信息 */
	public function get_user_ids_by_filter ($filter_params) 
	{
		if (is_string($filter_params)) 
			parse_str($filter_params, $filter_params);
		else if ( ! is_array($filter_params))
			return 0;

		$this->db->select('wu.id AS id, wu.screen_name AS name')
			->from('wb_user wu')
			->join('wb_account_user wau', 'wu.user_weibo_id = wau.user_weibo_id', 'left');

		$this->_set_where($filter_params);

		return $this->db->get()->result_array();
	}

	/**
	 * 接口返回来的单条用户数据存储
	 * @param $wb_aid			获取数据的账号ID
	 * @param $data			接口返回的数据(array)
	 * @param $platform		来源平台('sina' | 'tencent')
	 * @param $is_followers	是否通过粉丝接口获取的<会影响用户关系判断>
	 * 
	 * @return 插入的数据的数组, 或FALSE
	**/
	public function insert_user ($wb_aid, $data, $platform = 'sina', $is_followers = FALSE) 
	{
		$wb_user_sql = "INSERT INTO {$this->db->dbprefix('wb_user')} 
			( `user_weibo_id`, `platform`, `idstr`, `screen_name`, `name`, `country_code`, 
			`province_code`, `city_code`, `location`, `description`, `url`, `profile_image_url`, 
			`profile_url`, `domain`, `weihao`, `gender`, `followers_count`, `friends_count`, 
			`statuses_count`, `favourites_count`, `registerd_at`, `geo_enabled`, `allow_all_act_msg`, 
			`allow_all_comment`, `verified`, `verified_type`, `verified_reason`, `avatar_large`, 
			`avatar_hd`, `bi_followers_count` ) VALUES ";

		$account_user_sql = "INSERT INTO {$this->db->dbprefix('wb_account_user')} 
			( `user_weibo_id`, `wb_aid`, `relationship`, `created_at` ) VALUES ";

		$data['gender'] = $data['gender'] == 'm' ? 1 : ($data['gender'] == 'f' ? 2 : 0);
		$data['created_at'] = date('Y-m-d H:i:s', strtotime($data['created_at']));
		$d = $this->_insert_convert($data);
		if ($platform == 'sina') {
			$wb_user_sql .= "({$d['idstr']}, 1, {$d['idstr']}, {$d['screen_name']}, {$d['name']}, 0,
				{$d['province']}, {$d['city']}, {$d['location']}, {$d['description']}, {$d['url']}, {$d['profile_image_url']}, 
				{$d['profile_url']}, {$d['domain']}, {$d['weihao']}, {$d['gender']}, {$d['followers_count']}, {$d['friends_count']}, 
				{$d['statuses_count']}, {$d['favourites_count']}, {$d['created_at']}, {$d['geo_enabled']}, {$d['allow_all_act_msg']}, 
				{$d['allow_all_comment']}, {$d['verified']}, {$d['verified_type']}, {$d['verified_reason']}, {$d['avatar_large']}, 
				{$d['avatar_hd']}, {$d['bi_followers_count']})";
			$time = date('Y-m-d H:i:s');

			if ($is_followers) 
				$relationship = $d['following'] ? 3 : 1;
			else 
				$relationship = $d['following'] == $d['follow_me'] ? ($d['following'] ? 3 : 0) : ($d['following'] ? 2 : 1);

			$account_user_sql .= "( {$d['idstr']}, {$wb_aid}, {$relationship}, '{$time}' )";
		} else {
			$wb_user_sql .= "";
		}

		$wb_user_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name), platform=VALUES(platform), name=VALUES(name), country_code=VALUES(country_code), province_code=VALUES(province_code), city_code=VALUES(city_code), location=VALUES(location), description=VALUES(description), url=VALUES(url), profile_image_url=VALUES(profile_image_url), profile_url=VALUES(profile_url), domain=VALUES(domain), weihao=VALUES(weihao), gender=VALUES(gender), followers_count=VALUES(followers_count), friends_count=VALUES(friends_count), statuses_count=VALUES(statuses_count), favourites_count=VALUES(favourites_count), geo_enabled=VALUES(geo_enabled), allow_all_act_msg=VALUES(allow_all_act_msg), allow_all_comment=VALUES(allow_all_comment), verified=VALUES(verified), verified_type=VALUES(verified_type), verified_reason=VALUES(verified_reason), avatar_large=VALUES(avatar_large), avatar_hd=VALUES(avatar_hd), bi_followers_count=VALUES(bi_followers_count);';
		$account_user_sql .= ' ON DUPLICATE KEY UPDATE relationship=VALUES(relationship);';

		$this->db->query($wb_user_sql);
		$id = $this->db->insert_id();

		if ($id) {
			$this->db->query($account_user_sql);
			return array_merge($d, array('id' => $id));
		} else {
			return FALSE;
		}
	}

	/**
	 * 接口返回来的原始数据存储 (粉丝信息，etc)
	 * @param $wb_aid			获取数据的账号ID
	 * @param $data_arr		接口返回的数据(array)
	 * @param $platform		来源平台('sina' | 'tencent')
	 * @param $is_followers	是否通过粉丝接口获取的<会影响用户关系判断>
	 * 
	 * @return 执行结果, 记入日志 (log目录：APPPATH/logs/wb_user-[Y-m-d].log)
	**/
	public function insert_batch ($wb_aid, $data_arr, $platform, $is_followers = FALSE) 
	{

		/* 日志文件 */
		$date = date('Y-m-d');
		$log = fopen(APPPATH . "/logs/wb_user-[{$date}].log", FOPEN_READ_WRITE_CREATE);

		if ( ! $this->_before_insert($log, $wb_aid, $platform)) {
			fclose($log);
			return FALSE;
		}

		$method = '_get_' . $platform . '_insert';
		$insert_vars = $this->$method($wb_aid, $data_arr, $is_followers);

		$this->db->query($insert_vars['wb_user_sql']);
		$count[0] = $this->db->affected_rows();
		$this->db->query($insert_vars['account_user_sql']);
		$count[1] = $this->db->affected_rows();

		unset($insert_vars);
		$time = date('H:i:s');
		fwrite($log, "[{$time}]: INSERT SUCCESS [wb_aid:{$wb_aid}] <wb_user:[$count[0]], wb_account_user:[$count[1]], >\r\n");

		fclose($log);

		return TRUE;
	}

	/* 新浪接口数据转化为插入字符串 */
	private function _get_sina_insert($wb_aid, $user_arr, $is_followers = FALSE)
	{
		$wb_user_sql = "INSERT INTO {$this->db->dbprefix('wb_user')} 
			( `user_weibo_id`, `platform`, `idstr`, `screen_name`, `name`, `country_code`, 
			`province_code`, `city_code`, `location`, `description`, `url`, `profile_image_url`, 
			`profile_url`, `domain`, `weihao`, `gender`, `followers_count`, `friends_count`, 
			`statuses_count`, `favourites_count`, `registerd_at`, `geo_enabled`, `allow_all_act_msg`, 
			`allow_all_comment`, `verified`, `verified_type`, `verified_reason`, `avatar_large`, 
			`avatar_hd`, `bi_followers_count` ) VALUES ";

		$account_user_sql = "INSERT INTO {$this->db->dbprefix('wb_account_user')} 
			( `user_weibo_id`, `wb_aid`, `relationship`, `created_at` ) VALUES ";

		foreach ($user_arr as $user) 
		{
			$user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
			$user['gender'] = $user['gender'] == 'm' ? 1 : ($user['gender'] == 'f' ? 2 : 0);
			$user = $this->_insert_convert($user);
			$created_at = date('Y-m-d H:i:s');

			$wb_user_sql .= "( {$user['id']}, 1, {$user['idstr']}, {$user['screen_name']}, {$user['name']}, '0', 
				{$user['province']}, {$user['city']}, {$user['location']}, {$user['description']}, {$user['url']}, {$user['profile_image_url']}, 
				{$user['profile_url']}, {$user['domain']}, {$user['weihao']}, {$user['gender']}, {$user['followers_count']}, {$user['friends_count']}, 
				{$user['statuses_count']}, {$user['favourites_count']}, {$user['created_at']}, {$user['geo_enabled']}, {$user['allow_all_act_msg']}, 
				{$user['allow_all_comment']}, {$user['verified']}, {$user['verified_type']}, {$user['verified_reason']}, {$user['avatar_large']}, 
				{$user['avatar_hd']}, {$user['bi_followers_count']} ), ";

			if ($is_followers) 
				$relationship = $user['following'] ? 3 : 1;
			else 
				$relationship = $user['following'] == $user['follow_me'] ? ($user['following'] ? 3 : 0) : ($user['following'] ? 2 : 1);

			$account_user_sql .= "( {$user['id']}, '{$wb_aid}', '{$relationship}', '{$created_at}'), ";
		}

		unset($user_arr);
		$wb_user_sql = rtrim($wb_user_sql, ', ');
		$account_user_sql = rtrim($account_user_sql, ', ');

		$wb_user_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name), platform=VALUES(platform), name=VALUES(name), country_code=VALUES(country_code), province_code=VALUES(province_code), city_code=VALUES(city_code), location=VALUES(location), description=VALUES(description), url=VALUES(url), profile_image_url=VALUES(profile_image_url), profile_url=VALUES(profile_url), domain=VALUES(domain), weihao=VALUES(weihao), gender=VALUES(gender), followers_count=VALUES(followers_count), friends_count=VALUES(friends_count), statuses_count=VALUES(statuses_count), favourites_count=VALUES(favourites_count), geo_enabled=VALUES(geo_enabled), allow_all_act_msg=VALUES(allow_all_act_msg), allow_all_comment=VALUES(allow_all_comment), verified=VALUES(verified), verified_type=VALUES(verified_type), verified_reason=VALUES(verified_reason), avatar_large=VALUES(avatar_large), avatar_hd=VALUES(avatar_hd), bi_followers_count=VALUES(bi_followers_count);';
		$account_user_sql .= ' ON DUPLICATE KEY UPDATE relationship=VALUES(relationship);';

		return array(
			'wb_user_sql' => $wb_user_sql, 
			'account_user_sql' => $account_user_sql
		);
	}

	/* 腾讯接口数据转化为插入字符串 */
	private function _get_tencent_insert($wb_aid, $user_arr, $is_followers = FALSE)
	{
		$wb_user_sql = "INSERT INTO {$this->db->dbprefix('wb_user')} 
			( `user_weibo_id`, `platform`, `idstr`, `screen_name`, `name`, `country_code`, 
			`province_code`, `city_code`, `location`, `description`, `url`, `profile_image_url`, 
			`profile_url`, `domain`, `weihao`, `gender`, `followers_count`, `friends_count`, 
			`statuses_count`, `favourites_count`, `registerd_at`, `geo_enabled`, `allow_all_act_msg`, 
			`allow_all_comment`, `verified`, `verified_type`, `verified_reason`, `avatar_large`, 
			`avatar_hd`, `bi_followers_count` ) VALUES ";

		$account_user_sql = "INSERT INTO {$this->db->dbprefix('wb_account_user')} 
			( `user_weibo_id`, `wb_aid`, `relationship`, `created_at` ) VALUES ";

		foreach ($user_arr as $user) 
		{
			$user = array(
				'name'			=> $user['name'], 
				'id'			=> $user['openid'], 
				'nick'			=> $user['nick'], 
				'head'			=> $user['head'], 
				'sex'			=> $user['sex'], 
				'location'		=> $user['location'], 
				'country'		=> $user['country_code'], 
				'province'		=> $user['province_code'], 
				'city'			=> $user['city_code'], 
				'fansnum'		=> $user['fansnum'], 
				'idolnum'		=> $user['idolnum'], 
				'isidol'		=> $user['isidol'], 
				'isvip'			=> $user['isvip'], 
				'head'			=> $user['head'] ? $user['head'].'/50' : '',
				'head_large'	=> $user['head'] ? $user['head'].'/180' : '',
				'head_hd'		=> $user['head'] ? $user['head'].'/0' : '',
				'verified'		=> $user['isvip'] ? 1 : 0,
				'verified_type'	=> $user['isvip'] ? -2 : 999
			);
			$user = $this->_insert_convert($user);
			$created_at = date('Y-m-d H:i:s');

			$wb_user_sql .= "( {$user['id']}, 2, {$user['id']}, {$user['nick']}, {$user['name']}, {$user['country']}, 
				{$user['province']}, {$user['city']}, {$user['location']}, '', '', {$user['head']}, 
				'', '', '', {$user['sex']}, {$user['fansnum']}, {$user['idolnum']}, 
				0, 0, '0000-00-00 00:00:00', 0, 0, 
				0, {$user['verified']}, {$user['verified_type']}, '', {$user['head_large']}, 
				{$user['head_hd']}, 0 ), ";

			if ($is_followers) 
				$relationship = $user['isidol'] ? 3 : 1;
			else 
				$relationship = 0;
			
			$account_user_sql .= "( {$user['id']}, '{$wb_aid}', '{$relationship}', '{$created_at}'), ";
		}

		unset($user_arr);
		$wb_user_sql = rtrim($wb_user_sql, ', ');
		$account_user_sql = rtrim($account_user_sql, ', ');

		$wb_user_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name), platform=VALUES(platform), name=VALUES(name), country_code=VALUES(country_code), province_code=VALUES(province_code), city_code=VALUES(city_code), location=VALUES(location), description=VALUES(description), url=VALUES(url), profile_image_url=VALUES(profile_image_url), profile_url=VALUES(profile_url), domain=VALUES(domain), weihao=VALUES(weihao), gender=VALUES(gender), followers_count=VALUES(followers_count), friends_count=VALUES(friends_count), statuses_count=VALUES(statuses_count), favourites_count=VALUES(favourites_count), geo_enabled=VALUES(geo_enabled), allow_all_act_msg=VALUES(allow_all_act_msg), allow_all_comment=VALUES(allow_all_comment), verified=VALUES(verified), verified_type=VALUES(verified_type), verified_reason=VALUES(verified_reason), avatar_large=VALUES(avatar_large), avatar_hd=VALUES(avatar_hd), bi_followers_count=VALUES(bi_followers_count);';
		$account_user_sql .= ' ON DUPLICATE KEY UPDATE relationship=VALUES(relationship);';

		return array(
			'wb_user_sql' => $wb_user_sql, 
			'account_user_sql' => $account_user_sql
		);
	}

	/* 插入前数据检测 */
	private function _before_insert ($log, $wb_aid, $platform) 
	{

		$time = date('H:i:s');

		/* 检测平台来源 */
		if ( ! in_array($platform, array('sina', 'tencent'))) 
		{
			fwrite($log, "[{$time}]: INSERT FAILURE <unknown platform '{$platform}'>\r\n");
			return FALSE;
		}

		return TRUE;
	}

	/* 接口数据值插入数据库转换 */
	private function _insert_convert (&$data) 
	{
		if (is_array($data)) {
			array_walk_recursive($data, "Wb_user_model::_insert_convert");
		} else if (is_string($data) OR is_bool($data)) {
			$data = $this->db->escape($data);
		} else if (is_float($data)) {
			$data = $this->db->escape(number_format($data, 0, '', ''));
		}
		return $data;
	}

	// 用户入组
    public function user_in_group($user_ids,$group_id)
    {
        $values = '';
        if (is_array($user_ids))
        {
            foreach($user_ids as $idV){
                $values .= "('$idV','$group_id'),";
            }
            $values = rtrim($values,',');
        }
        else if (strpos($user_ids,','))
        {
            $sId = explode(',',$user_ids);
            foreach($sId as $idV){
                $values .= "('$idV','$group_id'),";
            }
            $values = rtrim($values,',');
        }
        else
        {
            $values = "($user_ids, $group_id)";
        }
        $sql = 'INSERT IGNORE INTO '.$this ->db ->dbprefix('rl_wb_group_user').'(`wb_user_id`,`group_id`)
                VALUES'.$values;
        // echo $sql;
        return $this ->db ->query($sql);
    }

    //获取用户组统计数据
    public function get_user_data_statistics($group_id){
        $this->db->select("u.province_code,u.gender");
        $this->db->from($this ->db ->dbprefix('wb_user')." AS u");
        $this->db->join($this ->db ->dbprefix('rl_wb_group_user')." AS rl","rl.wb_user_id=u.id");
        $this->db->where("rl.group_id",$group_id);
        $data = $this->db->get()->result_array();
        return $data;
    }
}
