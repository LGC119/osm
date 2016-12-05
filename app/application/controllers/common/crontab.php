<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * execute crontab job to send weibo
 * params like this
 * "wb_aid=1&ip=127.0.0.1"
 * params are unnecessary
 * if pass wbaid and ip, weibo will send by that wbaid and ip
 * or wend by wbaid from database and 127.0.0.1
 **/

class Crontab extends Base_Controller {

	public function __construct()
	{
		parent::__construct();
		if (! $this->input->is_cli_request())
		{
			$this->_log_info['status'] = 'try execute crontab from web';
			// exit(header('location:'.site_url()));
		}
	}

	public function go(){
		echo 111;
	}
	/* 定时发布微博 && 微博标签绑定 */
	public function send_crontab_status($params = '')
	{
		$args = array();
		parse_str($params, $args);
		
		$wb_aid = !empty($args['wb_aid']) ? intval($args['wb_aid']) : '';
		$_SERVER['REMOTE_ADDR'] = !empty($args['ip']) ? $args['ip'] : '127.0.0.1';

		$this->load->helper('api');
		$this->load->model('system/account_model', 'account');
		$this->load->model('meo/communication_model', 'communication');
		$this->load->model('meo/wb_send_crontab_model', 'wb_send_crontab');

		$crontabs = $this->wb_send_crontab->get_all_crontabs();
		if (empty($crontabs)) return ;
		
		if ($wb_aid)
		{
			$oainfo = $this->account->get_oa_info($wb_aid);
			$wbapiObj = get_wb_api($oainfo);
			$company_id = $oainfo['company_id'];
			foreach ($crontabs as $key => $val)
			{
				$log_status[$key] = $this->_send($crontab_arr, $wbapiObj, $company_id, $wb_aid);
			}
			$this->_log_info['status'] = json_encode($log_status);
		}
		else
		{
			$log_status = array();
			foreach ($crontabs as $key => $crontab_arr)
			{
				$wb_aid = $key;
				$oainfo = $this->account->get_oa_info($wb_aid);
				$company_id = $oainfo['company_id'];
				$wbapiObj = get_wb_api($oainfo);
				$log_status[$key] = $this->_send($crontab_arr, $wbapiObj, $company_id, $wb_aid);
			}
			$this->_log_info['status'] = json_encode($log_status);
		}

	}

	private function _send($crontabs, $wbapiObj, $company_id, $wb_aid)
	{
		$this->load->model('meo/wb_send_crontab_model', 'wb_send_crontab');
		$this->load->model('meo/Wb_User_Timeline_model', 'wb_user_timeline');

		foreach ($crontabs as $key => $val)
		{
			if (99 == $val['type'])
			{
				if (!$val['pic_path'])
				{
					$rs = $wbapiObj->update($val['text']);
				}
				else
				{
					$rs = $wbapiObj->upload($val['text'], $val['pic_path']);
				}
			}
			else if (0 == $val['type'])    // only repost
			{
				$rs = $wbapiObj->repost($val['sid'], $val['text']);
			}
			else if (1 == $val['type'])    // only comment
			{
				$rs = $wbapiObj->comment($val['sid'], $val['text']);
			}
			else if (2 == $val['type'])    // repost & comment
			{
				$rs = $wbapiObj->comment_repost($val['sid'], $val['text']);
			}
			else if (3 == $val['type'])    // reply
			{
				$rs = $wbapiObj->reply($val['sid'], $val['text'], $val['cid']);
			}
			else if (4 == $val['type'])    // private message
			{
				// waiting to complete
				continue;
			}

			if (! isset($rs['error']) && ! isset($rs['me_err_code']))
			{

				$this->wb_send_crontab->update('wb_send_crontab', array('id' => $val['id']), array('is_sent' => 1));
				if (2 == $val['type'] || 99 == $val['type'])
				{
					if (2 == $val['type'])
					{
						$rs = $rs['data'];
					}
					$timeline_arr['company_id'] = $company_id;
					$timeline_arr['wb_aid'] = $wb_aid;
					$timeline_arr['weibo_id'] = $rs['idstr'];
					$timeline_arr['text'] = $val['text'];
					$timeline_arr['is_retweeted'] = !empty($val['sid']) ? 1 : 0;
					$timeline_arr['created_at'] = strtotime($rs['created_at']);
					$timeline_arr['me_sent'] = 1;
					$timeline_arr['wb_info'] = json_encode($rs);

					$return_data = $this->wb_user_timeline->insert('wb_user_timeline', $timeline_arr);
					if (! empty($return_data['insert_id']) && ! empty($val['data']))
					{
						$data_arr = explode('-', $val['data']);
						if ('t' == $data_arr[0])
						{
							$tags = explode('_', $data_arr[1]);
							$this->_bind_wb_tag($return_data['insert_id'], $tags);
						}
					}
				}
				
				$log_status[$val['id']] = 'success';
			}
			else
			{
				if ((time() - $val['send_at']) > 180)
				{
					$this->wb_send_crontab->update('wb_send_crontab', array('id' => $val['id']), array('is_sent' => 3));
				}
				else
				{
					$this->wb_send_crontab->update('wb_send_crontab', array('id' => $val['id']), array('is_sent' => 2));
				}

				$log_status[$val['id']] = isset($rs['error_code']) ? $rs['error_code'] : $rs['me_err_code'];
			}
			sleep(5);    //sleep for 5 seconds or api will return error
		}
		return $log_status;
	}

	private function _bind_wb_tag($wb_id, $tags)
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

	// 获取微博评论，@，和关键字抓取
	public function get_communications () 
	{
		$this->load->model('meo/weibo_crons_model', 'weibo_crons');

		// 获取所有账号的评论，mention，keyword
		$this->weibo_crons->get_communications();

		// 获取关键词[降低频率]
		$min = intval(date('i')); 
		if ($min%30 < 6) // [0-6, 30-36] 两个时段内执行
			$this->weibo_crons->get_keywords(); // 在每个小时，半小时附近执行关键词抓取;
	}

	/* weibo活动用户推送 */
	/* 更新微博活动参与者 */
	public function weibo_event_push () 
	{
		// weibo活动用户推送
		$this->load->model('meo/wb_event_model', 'wb_event');
		$this->wb_event->event_push();
		$this->wb_event->update_participants();
	}

	// 微信群发
	public function wx_do_sendall () 
	{
		$this->load ->model('mex/send_model','send');
		$this->send ->do_sendall();
	}

	// 微信用户入库
	public function wx_do_user_all(){
		$this->load ->model('mex/user_model','user');
		$this->user ->insert_user_all();
	}

	//定时任务
	/**
	** 微信自动分配的定时任务处理
	**/
		public function wx_crontab(){
			$this->load->database();
			
			//获取所有的遗留communication
			$sql = "select id from me_wx_communication where staff_id = 0 and operation_status = 0";
			// echo $sql;exit;
			$query = $this->db->query($sql);
				if($query->num_rows()>0){
					$no_do = $query->result_array();
				}else{
					return '';exit;
				}
			foreach ($no_do as $key => $val) {
				// var_dump($val['id']);
				//调用自动分配方法进行分配
				$this->load ->model('mex/communication_model','wx_allot');
				$this->wx_allot->auto_allot($val['id']);
			}
		}

	/**
	** 自动分配的定时任务处理
	**/
		public function wb_crontab(){
			$this->load->database();
			
			//获取所有的遗留communication
			$sql = "select id from me_wb_communication where staff_id = 0 and operation_status = 0";
			$query = $this->db->query($sql);
				if($query->num_rows()>0){
					$no_do = $query->result_array();
				}
				// var_dump($no_do);
			foreach ($no_do as $key => $val) {
				// var_dump($val['id']);
				//调用自动分配方法进行分配
				var_dump($val['id']);
				$this->load ->model('meo/communication_model','allot');
				$this->allot->wb_auto_allot($val['id']);
			}
		}

	// 兑礼提前一周提醒通知
	public function send_remind_notify(){
		$this->load->model('osm_model', 'osm');
		$this->osm->send_remind_notify();
	}

}