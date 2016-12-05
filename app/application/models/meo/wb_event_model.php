<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wb_event_model extends ME_Model {

	private $_cid;
	private $_sid;
	private $_aid;

	public $_errors;		// 错误信息数组

	public function __construct()
	{
		parent::__construct();

		$this->_cid = $this->session->userdata('company_id');
		$this->_sid = $this->session->userdata('staff_id');
		$this->_aid = $this->session->userdata('wb_aid');
	}

	private function _get_wbapi ($wb_aid) 
	{
		$oa_info = $this->db->select('wa.weibo_id openid, wa.access_token, wa.refresh_token, a.appkey client_id, a.appskey client_secret, wa.platform, wa.company_id')
			->from('wb_account wa')
			->join('application a', 'wa.app_id = a.id', 'left')
			->where(array('wa.id'=>$wb_aid, 'a.is_delete'=>0))
			->get()->row_array();

		if ( ! $oa_info)
			return '设定账号不正确！';

		$this->load->helper('api');
		return get_wb_api($oa_info);
	}

	/* 获取活动列表 */
	public function get_list ($p) 
	{
		$this->db->from('event e');
			// ->join('event_wb_info ewi', 'ewi.event_id=e.id', 'left');
		$this->_set_where($p);
		$total_num = $this->db->get()->num_rows();
		// $res = $this->db->get()->result_array();
		// print_r($res);
		if ($total_num > 0) { 
			if($p['from'] == 3){
				$this->db->from('event e');
				$this->_set_where($p);
			}else if($p['from']==0){
				$this->db->select('e.id, e.event_title, e.detail, e.created_at, e.start_time, e.end_time, e.status, e.type, e.industry')
					->select('ewi.status_id, ewi.rule, ewi.account_id, ewi.push_mode, ewi.push_each, ewi.push_time_range')
					->from('event e')
					->join('event_wb_info ewi', 'ewi.event_id=e.id', 'left');
				$this->_set_where($p);	
			}		

			$page = intval($p['current_page']) > 0 ? intval($p['current_page']) : 0;
			$perpage = (intval($p['items_per_page']) > 0 && intval($p['items_per_page']) < 20) ? intval($p['items_per_page']) : 10;

			if ($page > ceil($total_num / $perpage)) 
				$this->db->limit($perpage);
			else 
				$this->db->limit($perpage, ($page - 1) * $perpage);

			$events = $this->db->order_by('created_at', 'desc')->get()->result_array();
			// print_r($events);
			/* 确定微博活动状态 */
			/* 正常活动时间内为进行中，手动终止除外 */
			foreach ($events as &$event) 
			{
				switch ($event['status']) 
				{
					case 0: 
						$event['status_name'] = '未发布';
						break;
					
					case 1: 
						$time = time();
						$start_time = strtotime($event['start_time']);
						$end_time = strtotime($event['end_time']);
						if ($time < $start_time) 
							$event['status_name'] = '未开始';
						else if ($time > $end_time) 
							$event['status_name'] = '已结束';
						else 
							$event['status_name'] = '进行中';
						break;
					
					case 2: 
						$event['status_name'] = '手动终止';
						break;
					
					default:
						# code...
						break;
				}
			}

			return array (
				'events' => $events, 
				'current_page' => $page,
				'items_per_page' => $perpage,
				'total_number' => $total_num
			);
		} else { 
			return '没有任何活动！';
		}


		if (isset($p['limit']))
		{
			$this->db->limit($p['limit']);
		}

		/* 获取改公司所有活动信息 */
	}

	/* 设定获取活动列表SQL|WHERE */
	public function _set_where ($p) 
	{
		$where = array (
			'e.company_id'	=> $this->session->userdata('company_id'),
			'e.from'	=> $p['from'],
			'is_deleted'	=> 0
		);
		
		if (isset($p['type']) && $p['type'] != -1) 
			$where['e.type'] = intval($p['type']);
		if (isset($p['industry']) && $p['industry'] != -1) 
			$where['e.industry'] = intval($p['industry']);

		$this->db->where($where);

		if (isset($p['status']))
			$this->db->where('status', $p['status']);

		if (isset($p['keyword']) && trim($p['keyword']) != '') {
			$key = trim($p['keyword']);
			$this->db->where("CONCAT(`e`.`event_title`, `e`.`detail`) LIKE '%{$key}%'", NULL, FALSE);
		}

		if (isset($p['start']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $p['start'])) 
			$this->db->where(" `e`.`start_time` >= '{$p['start']}'", NULL, FALSE);
		if (isset($p['end']) && preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $p['end'])) 
			$this->db->where(" `e`.`end_time` <= '{$p['end']}'", NULL, FALSE);

		return ;
	}

	/* 创建微博活动基本信息 */
	public function insert_event ($p,$push_mode='')
	{
		$name = trim($p['name']);
		// $desc = trim($p['content']); /* 暂时没有这个字段 */
		// $rule = isset($p['rule']) && intval($p['rule']) > 0 ? intval($p['rule']) : 0;		// 需要@多少人
		$type = intval($p['type']);
		$industry = intval($p['industry']);

		$name_duplicate = $this->db->select('event_title')
			->from('event')
			->where(array('event_title'=>$name, 'company_id'=>$this->_cid))
			->get()->row_array();

		if ($name_duplicate) {
			$this->_errors[] = '活动名与其他活动重复！';
			return FALSE;
		}

		$date['start'] = date('Y-m-d', strtotime($p['start']));
		$date['end'] = date('Y-m-d', strtotime($p['end']));

		if ($date['start'] < date('Y-m-d')) {
			$this->_errors[] = '起始时间不能小于当前时间！';
			return FALSE;
		}

		if ($date['end'] > date('Y-m-d') && $date['end'] < $date['start']) {
			$this->_errors[] = '结束时间设置不能小于起始时间！';
			return FALSE;
		}

		/* H5页面 */
         $h5_id = isset($p['h5_id']) && intval($p['h5_id']) > 0 ? intval($p['h5_id']) : 0;
         if ($h5_id) 
             $h5_info = $this->db->select('clickurl')
                 ->from('h5_page')
                 ->where(array('id'=>$h5_id, 'is_deleted'=>0))
                 ->get()->row_array();

		$info = array (
			'company_id' 	=> $this->_cid, 
			'aid' 			=> $this->_aid,
			'event_title' 	=> $name,
			// 'rule' 			=> $rule,
			// 'detail' 		=> $desc,
			'created_at' 	=> date('Y-m-d H:i:s'),
			'start_time' 	=> $date['start'],
			'end_time' 		=> $date['end'] . ' 23:59:59',
			'status' 		=> $push_mode == 3 ? 1 : 0,
			'push_status' 	=> 0,
			'from' 			=> 0,		// 活动来源 {0:微博,1:微信,2:双微}
			'staff_id' 		=> $this->_sid,
			'type' 			=> $type,
			'industry' 		=> $industry,
			'h5page_id' 	=> $h5_id
		);

		$this->db->insert('event', $info);
		$id = $this->db->insert_id();

		if ( ! $id or ! $this->db->affected_rows()) {
			$this->_errors[] = '创建活动失败，请稍后尝试！';
			return FALSE;
		}

		return array_merge($info, array('id'=>$id));
	}

	/* 创建微博活动标签 */
	public function insert_tags ($event_id, $tags) 
	{
		$insert_tags = array ();
		$tag_ids = array_unique(array_keys($tags));
		if ( ! empty($tag_ids)) {
			foreach ($tag_ids as $tag_id) {
				$tag_id = intval($tag_id);
				if ($tag_id > 0)
					$insert_tags[] = array ( 'event_id' => $event_id, 'tag_id' => $tag_id );
			}
		}

		if ($insert_tags) 
			$this->db->insert_batch('rl_event_tag', $insert_tags);
		return $this->db->affected_rows();
	}

	/* 创建微博活动目标组 */
	public function insert_groups ($event_id, $groups) 
	{
		$insert_groups = array ();
		$group_ids = array_unique(array_keys($groups));
		if ($group_ids) {
			$this->load->model('meo/wb_group_model', 'wb_group');
			foreach ($group_ids as $group_id) {
				$group_id = intval($group_id);
				if ($group_id > 0) {
					$insert_groups[] = array ( 'event_id' => $event_id, 'group_id' => $group_id );

					/* 获取组用户，插入participants表中 */
					$user_ids = $this->wb_group->get_group_user_ids($group_id);
					if ($user_ids && ! empty($user_ids)) {
						$participants_sql = "INSERT INTO {$this->db->dbprefix('event_participant')} (`company_id`, `event_id`, `wb_user_id`, `screen_name`, `group_id`) VALUES ";
						foreach ($user_ids as $user) 
							$participants_sql .= "({$this->_cid}, {$event_id}, {$user['id']}, {$this->db->escape($user['name'])}, {$group_id}), ";

						$participants_sql = rtrim($participants_sql, ', ');
						$participants_sql .= ' ON DUPLICATE KEY UPDATE screen_name=VALUES(screen_name);';
						$this->db->query($participants_sql);
					}
				}
			}
		}

		if ($insert_groups)
			$this->db->insert_batch('rl_event_wb_group', $insert_groups);

		return $this->db->affected_rows();
	}

	/* 创建活动微博发布基本信息 */
	public function insert_info ($event_id, $wb_info,$uids='')
	{


		$start_time = isset($wb_info['push_start']) ? $wb_info['push_start'] : date('Y-m-d H:i:s');
		if (date('Y-m-d', strtotime($start_time)) < date('Y-m-d')) 
			$start_time = date('Y-m-d');

		if($wb_info['push_mode']){
			$push_mode = $wb_info['push_mode'];
		}else{
			$push_mode = 2;
		}
//		if($push_mode == 3){
//			$uid = $uids[0];
//			$accountid= $this->db->select("wb_aid")
//					->from("wb_account_user")
//					->where("user_weibo_id",$uid)
//					->get()->result_array();
//			$wb_info['account']  = $accountid[0]['wb_aid'];
//
//
//		}
		$push_times = '';
		$times = array();
		if (is_array($wb_info['interval']) && $wb_info['interval'] && count($wb_info['interval']) > 0)
			foreach ($wb_info['interval'] as $time) 
				if ($time < 24 && $time > -1) 
					$times[] = $time;
		if ($times && count($times) > 0)
			$push_times .= implode(',', $times);

		if(!isset($wb_info['account'])){
			$wb_info['account'] = 0;
		}
		if(!isset($wb_info['content'])){
			$wb_info['content'] = '';
		}
		$data = array (
			'event_id' => $event_id,
			'company_id' => $this->_cid, 
			'account_id' => intval($wb_info['account']) > 0 ? intval($wb_info['account']) : $this->_aid, 
			'pic_url' => isset($wb_info['imgurl']) ? $wb_info['imgurl'] : '', 
			// 'pic_name' => $wb_info['pic_name'], 
			'content' => trim($wb_info['content']),
			'start_time' => $start_time, 
			'push_mode' => $push_mode, 
			'push_interval' => $wb_info['interval'] > 0 && $wb_info['interval'] < 60 ? $wb_info['interval'] : 0, 
			'push_time_range' => $push_times
		);

		$this->db->insert('event_wb_info', $data);
		$info_id = $this->db->insert_id();

		return array_merge($data, array('id'=>$info_id));
	}

	/* 获取活动详情<基本信息> */
	public function get_info ($event_id) 
	{
		$event = $this->db->select('e.*, eh.h5')
			->from('event e')
			->join('event_h5')
			->where('id', $event_id)
			->get()->row_array();

		return $event;
	}

	/* 发布活动微博 */
	public function event_status ($wb_info) 
	{
		$wbapi = $this->_get_wbapi($wb_info['account_id']);
		if (is_string($wbapi))
			return $wbapi;

		/* 调用接口，发送微博内容 [尝试三次] */
		for ($i = 0, $failed = TRUE; $failed && $i < 3; $i++) { 
			if (isset($wb_info['pic_url']) && ! empty($wb_info['pic_url']))
				$res = $wbapi->upload($wb_info['content'], '../' . $wb_info['pic_url']);
			else 
				$res = $wbapi->update($wb_info['content']);

			if ( ! isset($res['me_err_code'])) $failed = FALSE;
		}

		if (isset($res['me_err_code']))
			return $res['me_err_msg'];
		else {
			// 保存到wb_user_timeline中，返回微博ID保存到活动信息表中
			/* 更新活动状态和活动微博状态 */
			$this->db->set('status_id', $res['data']['idstr'])->where('id', $wb_info['id'])->update('event_wb_info');
			$this->db->set('status', 1)->where('id', $wb_info['event_id'])->update('event');
		}
	}

	/** 
	 * event_push 推送微博活动，5分钟执行一次
	 * STEP_01 : 获取需要推送的活动 [ 满足条件：正在进行中，设置了组，推送时间在当前的时间内<小时> ]
	 * STEP_02 : 获取活动中未推送的名单 [ 获取一次推送的限制 ]
	 * STEP_03 : 生成评论，并推送！！！ [ @user1, @user2, @user3... ]
	 * @return 执行结果，设定已推送用户状态，推送完设定活动状态
	 */
	public function event_push () 
	{
		$time = date('Y-m-d H:i:s');
        $rst = $this->db->select('e.id, e.from')
            ->from('event e')
            ->where(array('e.status' => 1, 'e.push_status <=' => 0, 'e.start_time <=' => $time, 'e.end_time >=' => $time))
            ->get()->result_array();
        //print_r($rst);
        foreach($rst as $v){
            $type = $v['from'];
            $event_id = $v['id'];
            switch($type)
            {
            //微博活动
            case 0:
                $this->get_event_data($type,$event_id);
                break;
            //微信活动
            case 1:
                break;
            //双微活动
            case 2:
                break;
            //高级活动
            case 3:
                $wx_rst = $this->wx_event_push($type,$event_id);
                $wb_rst = $this->get_event_data($type,$event_id);
                //var_dump($wx_rst);
                //var_dump($wb_rst);
                if($wx_rst == TRUE && $wb_rst == TRUE){
                    $this->db->set('push_status', 1)
                        ->where('id', $v['id'])
                        ->update('event');
                }
                break;
            }
        }
		return TRUE;
	}

	/** 
	 * get_event_data 获取微博推送活动的账号，推送的用户
	 * STEP_01 : 查出未完成的活动
	 * STEP_02 : 判断活动类型，查找所需要的数据 
	 * STEP_03 : 生成评论，并推送！！！ [ @user1, @user2, @user3... ]
	 * @return 执行结果，设定已推送用户状态，推送完设定活动状态
	 */
    private function get_event_data($type,$event_id)
    {
		$time = date('Y-m-d H:i:s');
        //未推送完的活动ID，类型 
        $unpushed_events = $this->db->select('ewi.push_mode, ewi.push_each, ewi.status_id, ewi.account_id,ewi.content')
            ->from('event_wb_info ewi')
            ->where(array('ewi.start_time <=' => $time, 'ewi.event_id' => $event_id))
            ->get()->result_array();
        //echo $this->db->last_query();
        //echo '<pre>';
        //print_r($unpushed_events);

        if ($unpushed_events)
        {
            $this->load->helper('api');
            foreach ($unpushed_events as $event_info) 
            {
                $event_info['from'] = $type;
                $event_info['id'] = $event_id;
                $participants = $this->get_unpushed_participants($event_info['id'], $event_info);
                //echo $this->db->last_query();
                //echo '<pre>';
                //print_r($participants);
                if ($participants)
                {
                    // 在评论或正文中mention尚未推送的参与者
                    $comments = array();
                    foreach ($participants as $val) // 开始推送，在正文或评论中 
                        $comments[$val['wb_user_id']] = '@' . $val['screen_name'];

                    $comment_str = implode(', ', $comments);
                    while (mb_strlen($comment_str, 'UTF8') >= 130) {
                        $comments = array_slice($comments, 1, count($comments), TRUE);	// 保留wb_user_id键名
                        $comment_str = implode(', ', $comments);
                    }
                    //exit;

                    if ($comment_str) {
                         //使用当前账号的发布一条评论
                        $wbObj = $this->get_weibo_api($event_info['account_id']);

                        if ($event_info['push_mode'] == 2){ // 在正文中推送
                            $push_res = $wbObj->update($comment_str);
                        }else if($event_info['push_mode'] == 1){ // 在评论中推送
                            $push_res = $wbObj->comment($event_info['status_id'], $comment_str);
                        }else{
                            foreach($participants as $value){
                                $touser_val[] = $value['user_weibo_id'];
                                $participants = $touser_val;
                            }
                            //print_r($participants);

                            $send_content = json_decode($event_info['content'], TRUE);
                            $send_contents['touser'] = $participants;
                            $send_contents['text'] = $send_content['text'];
                            $send_contents['msgtype'] = $send_content['msgtype'];
                            $content_data = json_encode($send_contents);

                            $access_token = $wbObj->_oa->access_token;
                            //echo $content_data;

                            $rst_sendall = $wbObj->sendall($content_data,$access_token);
                            $push_res = json_decode($rst_sendall,TRUE);
                            //var_dump($push_res);
                        }

                        if ( isset($push_res['result']) && $push_res['result'] == true) 
                        {
                            // 将所有推送用户的推送状态设置为<已推送> 
                            $wb_user_ids = array_keys($comments);
                            $this->db->set('if_pushed', 1)
                                ->where('event_id', $event_info['id'])
                                ->where_in('wb_user_id', $wb_user_ids)
                                ->update('event_participant');
                        }
                    }
                }
                else // 没有未推送的参与者了，把活动推送状态设置为1<推送完成>
                { 
                    //如果是高级活动，那么就只有微博和微信的用户都推送完成，活动才算完成
                    if($type == 3){
                        return true;
                    }else{
                        $this->db->set('push_status', 1)
                            ->where('id', $event_info['id'])
                            ->update('event');
                    }
                }
            }
        }
        else
        {
            if($type == 3){
                //如果是高级活动且活动开始时间还没到，那么不能直接返回true更改活动状态，而是
                //要判断该活动是否开始
                $unpushed_events = $this->db->select('ewi.push_mode, ewi.push_each, ewi.status_id, ewi.account_id,ewi.content')
                    ->from('event_wb_info ewi')
                    ->where(array('ewi.event_id' => $event_id))
                    ->get()->result_array();
                if($unpushed_events){
                    //活动还没有开始
                    return false;
                }else{
                    //高级活动中确实是没有该活动
                    return true;
                }
            }
        }
    }
    

	/**
	 * update_participants 更新活动参与者信息
	 * CRONTAB 执行
	 * STEP01 : 获取还在进行中的活动，获取活动微博ID，和最近一条转发时间<MRRT>
	 * STEP02 : 在wb_communication表中获取转发列表，获取该条微博转发<大于MRRT>
	 * STEP03 : 将用户信息记入event_participants表中
	 * STEP04 : 更新用户标签 !
	 */
	public function update_participants () 
	{
		$events = $this->get_running_events ();

		if ( ! $events) return TRUE;	// 没有在进行的活动，退出
		foreach ($events as $event) 
		{
			$event['rule'] = intval($event['rule']);
			if ($event['rule'] > 0 && $event['rule'] < 20)
				$like = str_repeat('%@_', $event['rule']);
			else 
				$like = '';

			$where = array (
				'wc.status_id' => $event['status_id'], 
				'wc.type' => 0, 
				'wc.wb_aid' => $event['aid'], 
				'wc.platform' => 1, 
				'wc.sent_at >=' => $event['participated_at'] ? $event['participated_at'] : '0000-00-00 00:00:00', 
				'wc.is_deleted' => 0
			);
			$this->db->select('wc.weibo_id, wc.sent_at, wu.id, wu.screen_name')
				->from('wb_communication wc')
				->join('wb_user wu', 'wc.user_weibo_id = wu.user_weibo_id', 'left')
				->where($where);
			if ($like) $this->db->where("wc.content LIKE '{$like}%'", NULL, FALSE);
			$repost_timeline = $this->db->get()->result_array();

			if ( ! $repost_timeline) continue;	// 没有新的转发，退出
			$insert_sql = "INSERT INTO {$this->db->dbprefix('event_participant')} (`company_id`, `event_id`, `participated_at`, `wb_user_id`, `screen_name`, `weibo_id`) VALUES ";
			foreach ($repost_timeline as $repost) # 将转发用户信息写入 event_participants 表中
				$insert_sql .= "('{$event['company_id']}', {$event['id']}, '{$repost['sent_at']}', {$repost['id']}, '{$repost['screen_name']}', '{$repost['weibo_id']}'),";

			$insert_sql = rtrim($insert_sql, ',');
			$insert_sql .= ' ON DUPLICATE KEY UPDATE participated_at=VALUES(participated_at),screen_name=VALUES(screen_name),weibo_id=VALUES(weibo_id);';
			$this->db->query($insert_sql);

			/* 更新参与者标签 */
			if (isset($event['tagids'])) 
			{
				$this->load->model('common/tag_model', 'tag_model');
				$user_tags = array ();
				foreach ($repost_timeline as $val) 
					$user_tags[] = array ('id'=>$val['id'], 'tagids'=>$event['tagids']);
				$this->tag_model->tag_weibo_user($user_tags, $event, 'event');
			}
		}
		return TRUE;
	}

	/* 获取某活动尚未推送的微博用户 */
	public function get_unpushed_participants ($event_id, $event_info = array())
	{
		if (empty($event_info)) {
			$time = date('Y-m-d H:i:s');
			$event_info = $this->db->select('e.id, ewi.push_mode, ewi.push_each')
				->from('event e')
				->join('event_wb_info ewi', 'e.id=ewi.event_id', 'left')
				->where(array('e.id' => $event_id, 'e.status' => 1, 'e.push_status <=' => 0, 'ewi.start_time <=' => $time))
				->get()->row_array();
		}

		if ( ! $event_info)
			return array();

		/* 取出若干未推送用户 */
		if ($event_info['push_each'] > 0 && $event_info['push_each'] < 10)
			$limit = $event_info['push_each'];
		else 
			$limit = 10000;

        if($event_info['from'] == 3)
        {
            $participants = $this->db->select('ep.wb_user_id, ep.wb_event_id, wu.screen_name, wu.user_weibo_id')
                ->from('event_participant ep')
                ->join('wb_user wu', 'ep.wb_user_id=wu.id', 'left')
                /*->join('event_wb_info ewi', 'ewi.id=ep.wb_event_id', 'left')*/
                ->where(array('ep.event_id' => $event_info['id'], 'ep.if_pushed' => 0))
                ->limit($limit)
                ->order_by("LENGTH(wu.screen_name)", 'DESC')
                ->get()->result_array();
        }
        else if($event_info['from'] == 0)
        {
            $participants = $this->db->select('ep.wb_user_id, wu.screen_name')
                ->from('event_participant ep')
                ->join('wb_user wu', 'ep.wb_user_id=wu.id', 'left')
                ->where(array('event_id' => $event_info['id'], 'if_pushed' => 0))
                ->limit($limit)
                ->order_by("LENGTH(wu.screen_name)", 'DESC')
                ->get()->result_array();
        }

        return $participants;
	}

	/* 获取在进行中的活动 */
	public function get_running_events () 
	{
		$date = date('Y-m-d H:i:s');
		$where = array (
			'e.status' 			=> 1, 				// 状态：已发布
			'e.start_time <=' 	=> $date, 			// 开始时间：小于等于当前时间
			'e.end_time >=' 	=> $date, 			// 结束时间：大于等于当前时间
			'e.from' 			=> 0, 				// 来源：微博
			'ewi.status_id <>' 	=> 0 				// 微博ID：不等于默认值0
		);

		/* 同时获取活动绑定的标签 */
		return $this->db->select('e.id, e.company_id, e.aid, ewi.rule, ewi.status_id')
			->select('MAX(ep.participated_at) AS participated_at')
			->select("GROUP_CONCAT(DISTINCT ret.tag_id SEPARATOR '|') AS tagids", FALSE)
			->from('event e')
			->join('event_participant ep', 'e.id = ep.event_id', 'left')
			->join('event_wb_info ewi', 'e.id = ewi.event_id', 'left')
			->join('rl_event_tag ret', 'e.id = ret.event_id', 'left')
			->where($where)
			->order_by('ep.participated_at', 'desc')
			->group_by('e.id')
			->get()->result_array();
	}

	/* 获取账号微博API接口对象，用于评论@活动组用户 */
	public function get_weibo_api ($aid) 
	{
		$where = array ('wa.is_delete' => 0, 'wa.platform' => 1, 'wa.id' => $aid);
		// 判断过期时间
		$account = $this->db->select('a.appkey AS client_id, a.appskey AS client_secret')
			->select('wa.id, wa.weibo_id AS openid, wa.access_token, wa.refresh_token, wa.platform, wa.company_id')
			->from('wb_account wa')
			->join('application a', 'wa.app_id = a.id', 'left')
			->where($where)
			->get()->row_array();

		$wbapiObj = get_wb_api($account);
		return $wbapiObj;
	}

	/* 获取活动统计数据 */
	public function get_statistics ($event_id) 
	{
		$event = $this->db->select('e.id, e.aid, ewi.status_id')
			->from('event e')
			->join('event_wb_info ewi', 'e.id=ewi.event_id', 'left')
			->where('e.id', $event_id)
			->get()->row_array();

		if ( ! $event)
			return '没有找到活动信息！';

		/* 参与时间走势统计 */
		if (isset($event['status_id'])) {
			$timeline = array ();
			$timeline['participates'] = $this->db->query("SELECT COUNT(*) AS sum, DATE_FORMAT(participated_at, '%Y-%m-%d') AS date 
				FROM {$this->db->dbprefix('event_participant')} ep 
				WHERE ep.event_id = {$event_id} 
				AND participated_at <> '0000-00-00 00:00:00' 
				GROUP BY DATE_FORMAT(participated_at, '%Y-%m-%d')")->result_array();

			$timeline['reposts'] = $this->db->query("SELECT COUNT(*) AS sum, DATE_FORMAT(sent_at, '%Y-%m-%d') AS date
				FROM {$this->db->dbprefix('wb_communication')} 
				WHERE `status_id`={$event['status_id']} AND `wb_aid`={$event['aid']} AND `type`=0
				GROUP BY DATE_FORMAT(sent_at, '%Y-%m-%d')")->result_array();

			$timeline['comments'] = $this->db->query("SELECT COUNT(*) AS sum, DATE_FORMAT(sent_at, '%Y-%m-%d') AS date
				FROM {$this->db->dbprefix('wb_communication')} 
				WHERE `status_id`={$event['status_id']} AND `wb_aid`={$event['aid']} AND `type`=1
				GROUP BY DATE_FORMAT(sent_at, '%Y-%m-%d')")->result_array();
		}

		/* 参与用户多维度统计:[推送状态] */
		$pushed = $this->db->select('COUNT(*) AS num, if_pushed')
			->from('event_participant')
			->where('event_id', $event_id)
			->group_by('if_pushed')
			->get()->result_array();

		/* 参与用户多维度统计:[地区] */
		$p_region = $this->db->select('COUNT(*) AS num, wu.province_code')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at <>'=>'0000-00-00 00:00:00'))
			->group_by('wu.province_code')
			->order_by('num', 'DESC')
			->limit(10)
			->get()->result_array();

		/* 参与用户多维度统计:[性别] */
		$p_gender = $this->db->select('COUNT(*) AS num, gender')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at <>'=>'0000-00-00 00:00:00'))
			->group_by('wu.gender')
			->order_by('num', 'DESC')
			->get()->result_array();

		/* 参与用户多维度统计:[身份] */
		$p_vt = $this->db->select('COUNT(*) AS num, wu.verified_type AS vt')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at <>'=>'0000-00-00 00:00:00'))
			->group_by('wu.verified_type')
			->order_by('num', 'DESC')
			->get()->result_array();

		/* 未参与者多维度统计:[地区] */
		$unp_region = $this->db->select('COUNT(*) AS num, wu.province_code')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at ='=>'0000-00-00 00:00:00'))
			->group_by('wu.province_code')
			->order_by('num', 'DESC')
			->limit(10)
			->get()->result_array();

		/* 未参与者多维度统计:[性别] */
		$unp_gender = $this->db->select('COUNT(*) AS num, wu.gender')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at ='=>'0000-00-00 00:00:00'))
			->group_by('wu.gender')
			->order_by('num', 'DESC')
			->get()->result_array();

		/* 未参与者多维度统计:[身份] */
		$unp_vt = $this->db->select('COUNT(*) AS num, wu.verified_type AS vt')
			->from('event_participant ep')
			->join('wb_user wu', 'ep.wb_user_id = wu.id', 'left')
			->where(array ('event_id'=>$event_id, 'participated_at ='=>'0000-00-00 00:00:00'))
			->group_by('wu.verified_type')
			->order_by('num', 'DESC')
			->get()->result_array();

		return array (
			'timeline' => isset($timeline) ? $timeline : NULL, 
			'pushed' => $pushed, 
			'p_region' => $p_region, 
			'p_gender' => $p_gender,
			'p_vt' => $p_vt,
			'unp_region' => $unp_region, 
			'unp_gender' => $unp_gender,
			'unp_vt' => $unp_vt
		);
	}

	public function get_uids($groupid=0){
		$uids = $this->db->select('wu.user_weibo_id uid')
				->from('rl_wb_group_user gu')
				->join('wb_user wu','wu.id=gu.wb_user_id','left')
				->where('gu.group_id',$groupid)
				->get()->result_array();
		$newUids = array();
		foreach($uids as $v){
			array_push($newUids,$v['uid']);
		}
		return $newUids;
	}

	public function sendall_status(){

	}

	/** 
	 * wx_event_push 推送微信活动，5分钟执行一次
	 * STEP_01 : 获取需要推送的活动 [ 满足条件：正在进行中，设置了组，推送时间在当前的时间内<小时> ]
	 * STEP_02 : 获取活动中未推送的名单 [ 获取一次推送的限制 ]
	 * STEP_03 : 生成评论，并推送！！！ [ @user1, @user2, @user3... ]
	 * @return 执行结果，设定已推送用户状态，推送完设定活动状态
	 */
	public function wx_event_push ($type,$event_id) 
	{
		$time = date('Y-m-d H:i:s');
		/* 未推送完的活动ID */
		$unpushed_events = $this->db->select('ws.media_id, ws.content, ws.msgtype, ws.wx_aid')
			->from('event_wx_info ewi')
			->join('wx_sendall ws', 'ewi.send_id=ws.id', 'left')
            ->where(array('ewi.start_time <=' => $time, 'ewi.event_id' => $event_id))
			->get()->result_array();
        //echo $this->db->last_query();
        //echo '<pre>';
        //print_r($unpushed_events);
        //exit;

		if ($unpushed_events)
		{
            $this->load->helper('api');
			foreach ($unpushed_events as $event_info) 
			{
                $event_info['from'] = $type;
                $event_info['id'] = $event_id;
				$participants = $this->get_wx_unpushed_participants($event_info['id'], $event_info);

                //echo $this->db->last_query();
                //print_r($participants);
                //exit;
                if ($participants)
                {
                    $comments = array();
                    foreach($participants as $value){
                        $touser_val[] = $value['openid'];
                        $participants = $touser_val;
                        $comments[$value['wx_user_id']] = $value['nickname'];
                    }
                    $openid = json_encode($participants);
                    $json = $this->send_openid($event_info['msgtype'], $openid, $event_info['content'], $event_info['media_id']);
                    //echo $json;

                    $wxObj = get_wx_api($event_info['wx_aid']);
                    //var_dump($wbObj);
                    $send_rst = $wxObj->wx_sendall($json, $event_info['wx_aid']);
                    //var_dump($send_rst);
                    //echo $send_rst['errcode'];

                    if ( isset($send_rst['errcode']) && $send_rst['errcode'] == 0) 
                    {
                        // 将所有推送用户的推送状态设置为<已推送> 
                        $wx_user_ids = array_keys($comments);
                        $this->db->set('if_pushed', 1)
                            ->where('event_id', $event_info['id'])
                            ->where_in('wx_user_id', $wx_user_ids)
                            ->update('event_participant');
                    }
                }
                else // 没有为推送的参与者了，把活动推送状态设置为1<推送完成>
                { 
                    //如果是高级活动，那么就只有微博和微信的用户都推送完成，活动才算完成
                    if($type == 3){
                        return true;
                    }else{
                        $this->db->set('push_status', 1)
                            ->where('id', $event_info['id'])
                            ->update('event');
                    }
                }
			}
        }
        else
        {
            if($type == 3){
                $unpushed_events = $this->db->select('ws.media_id, ws.content, ws.msgtype, ws.wx_aid')
                    ->from('event_wx_info ewi')
                    ->join('wx_sendall ws', 'ewi.send_id=ws.id', 'left')
                    ->where(array('ewi.event_id' => $event_id))
                    ->get()->result_array();
                if($unpushed_events){
                    return false;
                }else{
                    return true;
                }
            }
        }
	}

	/* 获取某活动尚未推送的微信用户 */
	public function get_wx_unpushed_participants ($event_id, $event_info = array())
	{
		if (empty($event_info)) {
			$time = date('Y-m-d H:i:s');
			$event_info = $this->db->select('e.id, ewi.rule_id, ewi.send_id')
				->from('event e')
				->join('event_wx_info ewi', 'e.id=ewi.event_id', 'left')
				->where(array('e.id' => $event_id, 'e.status' => 1, 'e.push_status <=' => 0, 'ewi.start_time <=' => $time))
				->get()->row_array();
		}

		if ( ! $event_info)
			return array();

        $limit = 10000;

        if($event_info['from'] == 3)
        {
            $participants = $this->db->select('ep.wx_user_id, ep.wx_event_id, wu.nickname, wu.openid')
                ->from('event_participant ep')
                ->join('wx_user wu', 'ep.wx_user_id=wu.id', 'left')
                ->where(array('ep.event_id' => $event_info['id'], 'ep.if_pushed' => 0))
                ->limit($limit)
                ->order_by("LENGTH(wu.nickname)", 'DESC')
                ->get()->result_array();
        }
        else if($event_info['from'] == 2)
        {
            $participants = $this->db->select('ep.wx_user_id, wu.nickname')
                ->from('event_participant ep')
                ->join('wx_user wu', 'ep.wx_user_id=wu.id', 'left')
                ->where(array('ep.event_id' => $event_info['id'], 'ep.if_pushed' => 0))
                ->limit($limit)
                ->order_by("LENGTH(wu.nickname)", 'DESC')
                ->get()->result_array();
        }

        return $participants;
	}

    // 根据粉丝openid群发的消息
    public function send_openid($type,$openids,$value='',$mediaid=''){
        switch($type){
            case 'text':
                $json = '{"touser": '.$openids.', "msgtype": "text", "text": { "content": "'.$value.'"}}';
                break;
            case 'news':
                $json = '{"touser":'.$openids.',"mpnews":{"media_id":"'.$mediaid.'"},"msgtype":"mpnews"}';
                break;
            case 'articles':
                $json = '{"touser":'.$openids.',"mpnews":{"media_id":"'.$mediaid.'"},"msgtype":"mpnews"}';
				$type = 'news';
                break;
            case 'voice':
                $json = '{"touser":'.$openids.',"voice":{"media_id":"'.$mediaid.'"},"msgtype":"voice"}';
                break;
            case 'image':
                $json = '{"touser":'.$openids.',"image":{"media_id":"'.$mediaid.'"},"msgtype":"image"}';
                break;
            default:
                $json = '';
                break;
        }
        return $json;
    }

}
