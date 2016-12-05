<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 关键词的 cmn_type */
class Weibo_crons_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 获取 get_communications */
	public function get_communications () 
	{
		$accounts = $this->get_all_accounts();

		if ($accounts) 
		{
			$this->load->helper('api');
			$this->load->model('meo/communication_model', 'communication');
			$this->load->model('meo/keyword_model', 'keyword');

			/* 获取带标签的微博 */
			$taged_statuses = array ();
			$res = $this->db->select('wut.weibo_id')
				->select("GROUP_CONCAT(DISTINCT rwutt.tag_id SEPARATOR '|') AS tagids", FALSE)
				->from('wb_user_timeline wut')
				->join('rl_wb_user_timeline_tag rwutt', 'rwutt.wb_id = wut.id', 'left')
				->where(array('is_deleted'=>0, 'weibo_id <>'=>0, 'rwutt.tag_id >'=>0))
				->group_by('wut.weibo_id')
				->get()->result_array();

			if ($res) 
				foreach ($res as $val) 
					$taged_statuses[$val['weibo_id']] = $val['tagids'];

			foreach ($accounts as $account) 
			{
				$wbapiObj = get_wb_api($account);
				$since_ids = $this->communication->get_since_id($account['id']);

				/* 获取关键词设定 <自动置顶和忽略> */
				$auto_keywords = $this->keyword->get_auto_keywords($account['company_id']);

				# 循环操作每个账号，获取数据
				$this->get_mentions($since_ids['mentions'], $wbapiObj, $account, $taged_statuses, $auto_keywords['mentions']);
				$this->get_comments($since_ids['comments'], $wbapiObj, $account, $taged_statuses, $auto_keywords['comments']);
			}
		}
	}

	// 获取微博“提到我的”数据
	/**
	 * @param since_id 抓取的起始微博ID
	 * @param wbapiObj 抓取数据的API对象
	 * @param account 当前的抓取账号的信息
	 * @param taged_statuses 打标签的微博信息 <互动用户打标签>
	 * @param auto_keywords 自动处理关键词
	 * 
	 * @return TRUE : FALSE 抓取数据结果
	 */
	public function get_mentions ($since_id, $wbapiObj, $account, $taged_statuses, $auto_keywords) 
	{
		/* 分新浪腾讯处理 */
		if ($wbapiObj instanceof Wbapi_sina) // 使用新浪的接口
		{
			for ($page = 1, $next = TRUE; $next; $page++) 
			{
				$mentions = $wbapiObj->mentions($page, $count = 50, $since_id);

				/* 接口调用出错 */
				if (isset($mentions['error_code']) OR isset($mentions['code'])) 
					break ;

				$next = isset($mentions['next_cursor']) ? $mentions['next_cursor'] : FALSE;
				/* 数据插入 */
				if (isset($mentions['statuses']) && $mentions['statuses']) 
				{
					$this->communication->insert_batch(
						$account, 
						$mentions['statuses'], 
						'mentions', 
						$taged_statuses, 
						$auto_keywords, 
						'sina');
				}
			}
		}
		else // 使用腾讯的接口
		{
			for ($pageflag = 0, $pagetime = 0, $next = TRUE; $next; $pageflag = 1) 
			{
				$mentions = $wbapiObj->mentions($count = 50, $pageflag, $pagetime);
				$next = isset($mentions['data']['hasnext']) && ! $mentions['data']['hasnext'];  // TX 微博的 API, 坑爹[hasnext : 0-有，1-无]

				if (isset($mentions['data']['info']) && $mentions['data']['info']) {
					$last = end($mentions['data']['info']);
					$pagetime = $last['timestamp'];
				}

				/* 数据插入 */
				$data = $mentions['data'];
				if (isset($data['info']) && $data['info']) 
				{
					$this->communication->insert_batch(
						$account, 
						$data['info'], 
						'mentions', 
						$taged_statuses, 
						$auto_keywords, 
						'tencent');
				}
			}
		}

		return ;
	}

	// 获取微博“评论我的”数据
	/**
	 * @param since_id 抓取的起始微博ID
	 * @param wbapiObj 抓取数据的API对象
	 * @param account 当前的抓取账号的信息
	 * @param taged_statuses 打标签的微博信息 <互动用户打标签>
	 * @param auto_keywords 自动处理关键词
	 * 
	 * @return TRUE : FALSE 抓取数据结果
	 */
	public function get_comments ($since_id, $wbapiObj, $account, $taged_statuses, $auto_keywords) 
	{
		if ($wbapiObj instanceof Wbapi_sina) // 使用新浪的接口
		{
			for ($page = 1, $next = TRUE; $next; $page++) 
			{
				$comments = $wbapiObj->comments_to_me($page, $count = 50, $since_id);

				if (isset($comments['error_code']) OR isset($comments['code'])) 
					break ;

				$next = isset($comments['next_cursor']) ? $comments['next_cursor'] : FALSE;
				/* 数据插入 */
				if (isset($comments['comments']) && $comments['comments']) 
				{
					$this->communication->insert_batch(
						$account, 
						$comments['comments'], 
						'comments', 
						$taged_statuses, 
						$auto_keywords, 
						'sina');
				}
			}
		}
		else // 腾讯微博暂时还没有这个接口
		{
			exit('没有相关接口！');
		}
	}

	// 获取所有的账号
	public function get_all_accounts () 
	{
		// 目前仅限新浪微博账号
		$where = array ('wa.is_delete' => 0, 'wa.platform' => 1);
		// 判断过期时间
		$accounts = $this->db->select('a.appkey AS client_id, a.appskey AS client_secret')
			->select('wa.id, wa.weibo_id AS openid, wa.access_token, wa.refresh_token, wa.platform, wa.company_id')
			->from('wb_account wa')
			->join('application a', 'wa.app_id = a.id', 'left')
			->where($where)
			->get()->result_array();

		return $accounts;
	}

	// since_id 保存在缓存中
	public function get_since_id ($wb_aid) 
	{
		// 看缓存中是否保存了since_id
		// 如果有，返回since_id，没有，数据库查询since_id
	}

	/* 获取活动状态的关键词，独立获取 */
	public function get_valid_keywords () 
	{
		$keywords = $this->db->select('id, company_id, vdong_id, status')
			->from('wb_keyword')
			->where('status', 1)
			->get()->result_array();

		return $keywords;
	}

	public function get_filter_keywords () 
	{
		$keywords = $this->db->select("GROUP_CONCAT(text SEPARATOR '|') AS keywords, company_id", FALSE)
			->from('wb_filter_keyword')
			->where(array('type'=>0, 'vdong_id >'=>0))
			->group_by('company_id')
			->get()->result_array();

		$res = array ();
		foreach ($keywords as $k) 
		{
			$key_arr = explode('|', $k['keywords']);
			if (count($key_arr) > 0) 
				$res[$k['company_id']] = $key_arr;
		}

		return $res;
	}

	/* 获取关键词微博信息，入库 [5min cron]，暂限最同时入库5K条 */
	public function get_keywords () 
	{
		$keywords = $this->get_valid_keywords ();

		if ( ! $keywords)
			return TRUE;

		$this->load->helper('api');
		$this->load->library('vdong');
		$this->load->model('meo/keyword_model', 'keyword');
		$this->load->model('meo/communication_model', 'communication');


		foreach ($keywords as $keyword) 
		{
			$vdong_id = $keyword['vdong_id'];
			if ($vdong_id < 1)
				continue ;

			/* 排除关键词 */
			$filters = isset($fkeywords[$keyword['company_id']]) ? $fkeywords[$keyword['company_id']] : array();

			// 从wb_communication里获取上次抓取的时间戳
			$starttime = $this->db->select('sent_at')
				->from('wb_communication')
				->order_by('sent_at', 'DESC')
				->where(array ('keyword_id'=>$keyword['id'], 'type'=>2))
				->get()->row_array();

			$starttime = $starttime ? strtotime($starttime['sent_at']) : NULL;
			$account = array ('id' => 0, 'company_id' => $keyword['company_id']);

			/* 自动置顶和忽略的关键词 */
			$auto_keywords = $this->keyword->get_auto_keywords($account['company_id']);
			$auto_keywords = $auto_keywords['keywords'];

			/* 最多取5W条 */
			for ($page = 1; $page <= 1000; $page++) 
			{
				$res = $this->vdong->timeline($vdong_id, $page, 50, $starttime);
				if ( ! isset($res['data_list'])) 
					break;
				if (count($res['data_list']) < 1) 
					break;

				foreach ($res['data_list'] as $data) 
				{
					$data['is_top'] = 0;
					$data['operation_status'] = 0;
					if ($this->_contain_filters($data['text'], $auto_keywords['pintop'])) 
						$data['is_top'] = 1;
					if ($data['is_top'] == 0 && $this->_contain_filters($data['text'], $auto_keywords['ignore'])) 
						$data['operation_status'] = 4;

					$data = vdong_convert($data);
					$this->communication->insert_keyword($account, $data, $keyword['id']);
				}
			}
		}
	}

	/**
	 ++ 过滤含有排除关键词的微博
	 ++ TODO: 根据关键字数量，使用更优化的算法来过滤
	 */
	private function _contain_filters ($content, $filters) 
	{
		if ( ! $filters)
			return FALSE;

		foreach ($filters as $f) 
			if (strpos($content, $f) !== FALSE) 
				return TRUE;

		// 关键词创建索引树
		// 判断是否有索引
		// $filters_arr = explode('|', $filters);
		return FALSE;
	}

}