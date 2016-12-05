<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Event_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 活动基本信息入库 */
	public function insert_event ($info) 
	{
		$name = trim($info['name']);

		$name_duplicate = $this->db->select('event_title')
			->from('event')
			->where(array('event_title'=>$name, 'company_id'=>$this->session->userdata('company_id'), 'from'=>3))
			->get()->row_array();

		if ($name_duplicate) return '活动名与其他活动重复！';

		$date['start'] = date('Y-m-d', strtotime($info['start']));
		$date['end'] = date('Y-m-d', strtotime($info['end']));

		if ($date['start'] < date('Y-m-d')) return '起始时间不能小于当前时间！';

		if ($date['end'] > date('Y-m-d') && $date['end'] < $date['start']) return '结束时间设置不能小于起始时间！';

		$info = array (
			'company_id' 	=> $this->session->userdata('company_id'), 
			'aid' 			=> 0,
			'event_title' 	=> $name,
			'created_at' 	=> date('Y-m-d H:i:s'),
			'start_time' 	=> $date['start'] . ' 00:00:00',
			'end_time' 		=> $date['end'] . ' 23:59:59',
			'status' 		=> 1,
			'push_status' 	=> 0,
			'from' 			=> 3,		// 活动来源 {0:微博,1:微信,2:双微}
			'staff_id' 		=> $this->session->userdata('staff_id'),
			'type' 			=> $info['type'],
			'h5page_id' 	=> $info['page_id'],
			'industry' 		=> $info['industry']
		);

		$this->db->insert('event', $info);
		$id = $this->db->insert_id();

		if ( ! $id) return '创建活动失败，请稍后尝试！';

		return array_merge($info, array('id'=>$id));
	}

	/* 活动标签信息入库 */
	public function insert_tags ($event_info, $tags) 
	{
		if (empty($tags) OR ! is_array($tags)) return TRUE;

		$rl_event_tag = array ();
		foreach ($tags as $id => $tag_name) {
			if (intval($id) > 0) 
				$rl_event_tag[] = array ('event_id'=>$event_info['id'], 'tag_id'=>intval($id));
		}

		$this->db->insert_batch('rl_event_tag', $rl_event_tag);
		return TRUE;
	}

	/* 高级活动的推送用户信息入库 */
	/* 可能是最复杂的函数之一 Σ( ° △ °|||)︴ */
	public function insert_participants ($event_info, $params, $push_content) 
	{
		// 分千条获取高级用户的信息
		// for ($i = 0, $more = TRUE; $more; $i++) 
		// {
		/* 微博推送者 */
			$wb_participants = $this->db->select('GROUP_CONCAT(usr.wb_user_id) AS wb_user_ids, wau.wb_aid')
				->from('rl_group_user rgu')
				->join('user u', 'rgu.user_id = u.id', 'left')
				->join('user_sns_relation usr', 'u.id = usr.user_id', 'left')
				->join('wb_user wu', 'usr.wb_user_id = wu.id', 'left')
				->join('wb_account_user wau', 'wu.user_weibo_id = wau.user_weibo_id', 'left')
				->where(array('usr.wb_user_id >'=>0, 'rgu.group_id'=>$params['group_id']))
				->group_by('wau.wb_aid')
				->get()->result_array();
			/* 获取微博推送账号 */
			if ($wb_participants) 
			{
				foreach ($wb_participants as $val) 
				{
					$event_wb_info = array (
						'company_id' => $this->session->userdata('company_id'), 
						'event_id' => $event_info['id'], 
						'account_id' => $val['wb_aid'], 
						'content' => json_encode($push_content['wb']), 
						'push_mode' => 3, 
						'start_time' => $params['set']['push_start'] . ' 00:00:00'
					);

					$this->db->insert('event_wb_info', $event_wb_info);
					$wb_event_id = $this->db->insert_id();

					/* 插入活动参与人信息 */
					$participants = array ();
					foreach (explode(',', $val['wx_user_ids']) as $wb_user_id) 
					{
						// 私信群发不需要存储微博昵称
						// $name = $this->db->select('name')->where('id', $wb_user_id)->get('wb_user')->row_array();
						$participants[] = array (
							'company_id' => $this->session->userdata('company_id'), 
							'event_id' => $event_info['id'], 
							'wb_event_id' => $wb_event_id, 
							'wb_user_id' => $wb_user_id
						);
					}
					$this->db->insert_batch('event_participant', $participants);
				}
			}
		// }
		/* 微信推送者 */
			$wx_participants = $this->db->select('GROUP_CONCAT(usr.wx_user_id) AS wx_user_ids, wu.wx_aid')
				->from('rl_group_user rgu')
				->join('user u', 'rgu.user_id = u.id', 'left')
				->join('user_sns_relation usr', 'u.id = usr.user_id', 'left')
				->join('wx_user wu', 'usr.wx_user_id = wu.id', 'left')
				->where(array('usr.wx_user_id >'=>0, 'rgu.group_id'=>$params['group_id']))
				->get()->result_array();

			if ($wx_participants) 
			{
				foreach ($wx_participants as $val) 
				{
					$send_all = array (
						'content' => $push_content['wx']['text']['content'], 
						'company_id' => $this->session->userdata('company_id'), 
						'wx_aid' => $val['wx_aid'], 
						'openids' => ' ', 
						'msgtype' => $push_content['wx']['msgtype'], 
						'json_data' => json_encode($push_content['wx']), 
						'exec_time' => '0000-00-00 00:00:00', 
						'is_send' => 0, 
						'created_at' => date('Y-m-d H:i:s'), 
						'actual_send_at' => '0000-00-00 00:00:00'
					);

					$this->db->insert('wx_sendall', $send_all);
					$send_id = $this->db->insert_id();
					$event_wx_info = array (
						'company_id' => $this->session->userdata('company_id'), 
						'event_id' => $event_info['id'], 
						'send_id' => $send_id, 
						'start_time' => $params['set']['push_start'] . ' 00:00:00'
					);

					$this->db->insert('event_wx_info', $event_wx_info);
					$wx_event_id = $this->db->insert_id();

					/* 插入活动参与人信息 */
					$participants = array ();
					foreach (explode(',', $val['wx_user_ids']) as $wx_user_id) 
					{
						$participants[] = array (
							'company_id' => $this->session->userdata('company_id'), 
							'event_id' => $event_info['id'], 
							'wx_event_id' => $wx_event_id, 
							'wx_user_id' => $wx_user_id
						);
					}
					$this->db->insert_batch('event_participant', $participants);
				}
			}

			return array ('wb'=>$wb_participants, 'wx'=>$wx_participants);
	}

	/* 获取活动微博推送内容 */
	public function get_wb_push_text ($set, $page_id) 
	{
		$push_content = '';

		switch ($set['wbContentType']) {
			case 'text':
				$push_content .= $set['wbContent'];
				$auth_url = $this->_get_wb_auth_url($page_id);
				// $auth_url = '<a href="' . $auth_url . '">点击查看</a>'; // 无法直接发送链接
				if (strpos($push_content, '{{link}}') === FALSE) 
					$push_content .= ' 活动详情：' . $auth_url;
				else 
					$push_content = str_replace('{{link}}', $auth_url, $push_content);
				break;
			
			default:
				# code...
				break;
		}


		return array (
			"msgtype" => "text", 
			"text" => array ( 
				"content" => $push_content
			)
		);
	}

	/* 获取活动微信推送内容 */
	public function get_wx_push_text ($set, $page_id) 
	{
		$push_content = '';

		switch ($set['wxContentType']) {
			case 'text':
				$push_content .= $set['wxContent'];
				$auth_url = $this->_get_wx_auth_url($page_id);
				// $auth_url = '<a href="' . $auth_url . '">点击查看</a>'; // 无法直接发送链接
				if (strpos($push_content, '{{link}}') === FALSE) 
					$push_content .= ' 活动详情：' . $auth_url;
				else 
					$push_content = str_replace('{{link}}', $auth_url, $push_content);
				break;
			
			default:
				# code...
				break;
		}


		return array (
			"msgtype" => "text", 
			"text" => array ( 
				"content" => $push_content
			)
		);
	}

	/* 获取活动列表 */
	public function get_list($params)
	{
		$total_num = $this->_get_count($params);

		if ( ! $total_num) return FALSE;

		$this->db->select('e.id, e.event_title, e.start_time, e.end_time, e.created_at, e.from')
			->from('event e');

		$this->_set_where($params);

		$offset = 0;
		$limit = $params['perpage'];
		if ($total_num > $limit * ($params['page'] - 1)) 
			$offset = ($params['page'] - 1) * $limit;

		$list = $this->db->limit($limit, $offset)
			->get()->result_array();

		if ($list) 
		{
			foreach ($list as &$event) {
				$date = date('Y-m-d H:i:s');
				if ($event['start_time'] >= $date) 
					$event['status_name'] = '未开始';
				else if ($event['start_time'] < $date && $event['end_time'] > $date) 
					$event['status_name'] = '进行中';
				else if ($event['end_time'] <= $date) 
					$event['status_name'] = '已结束';
			}
		}

		return array (
			'total_num' => $total_num, 
			'page' => $params['page'],
			'perpage' => $limit, 
			'events' => $list
		);
	}

	/* 获取用户计数总量 */
	private function _get_count ($params) 
	{
		$this->db->select('COUNT(*) AS num')
			->from('event e');

		$this->_set_where($params);
		$num = $this->db->get()->row_array();

		return $num ? $num['num'] : 0;
	}

	/* 设定获取活动列表的WHERE */
	private function _set_where ($params) 
	{
		if (isset($params['from']) && in_array($params['from'], array(0, 1, 2, 3))) 
			$this->db->where('e.from', $params['from']);

		/* 进行中的活动 */
		$date = date('Y-m-d H:i:s');
		if (isset($params['status']) && $params['status'] == 1) 
		{
			$this->db->where('e.start_time <=', $date);
			$this->db->where('e.end_time >=', $date);
		}

		if (isset($params['status']) && $params['status'] == 2) 
		{
			$this->db->where('e.start_time <', $date);
		}

		if (isset($params['title']) && trim($params['title'])) 
			$this->db->like('e.title', $params['title']);

		return ;
	}

	/* 获取微博授权链接 */
	private function _get_wb_auth_url ($page_id) 
	{
		$url = base_url() . 'index.php/h5page/wxh5_ext/go?id='.$page_id.'-0-0';
		return $url;
	}

	/* 获取微信授权链接 */
	private function _get_wx_auth_url ($page_id) 
	{
		// $appidArr = $this->get_appid_secret($wx_aid);
		// $appid = $appidArr['appid'];
		// $wx_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
		// $old_url = urlencode($url);
		// $wx_url .= 'appid='.$appid.'&redirect_uri='.$old_url.'&response_type=code&';
		// $wx_url .= 'scope=snsapi_base&state='.rand(0,9).'#wechat_redirect';
		$url = base_url() . 'index.php/h5page/wxh5_ext/go?id='.$page_id.'-0-0';
		return $url;
	}

}