<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 微博活动 控制器
*/
class Event extends ME_Controller 
{

	public function __construct() 
	{
		parent::__construct();
		$this->load->model('meo/wb_event_model', 'model');
	}

	/* 创建微博活动 */
	public function create () 
	{

		$p = $this->input->post(NULL, TRUE);

		$sendStatus = false;
		if($p['set']['push_mode'] == 3){
			$sendStatus = true;
		}

		if(!isset($p['groups']) || count($p['groups'])<=0){
			$this->meret(NULL, MERET_BADREQUEST, '没有设定推送组，无法创建活动！');
			exit;
		}

		$weiboData = array();
		// 如果是群发的话
		if($sendStatus){
			$groupid = array_keys($p['groups']);
			$groupid = $groupid[0];
			$uids = $this->model->get_uids($groupid);
			$weiboData['touser'] = $uids;
//			echo '<pre>';
//			print_r($p);exit;
			// 文本
			if($p['con']['type'] == 'text'){
				$weiboData['text'] = array(
					"content"=>$p['con']['tModel']
				);
				$weiboData["msgtype"] = "text";
			}else{
			// 图文
				//
				$mediaid = $p['con']['tModel'];
				$this->load->model('mex/media_model', 'media');
				$articles = $this->media->get_media_all($mediaid);
				$mediaData = $this->media->get_news_info($articles[0]['articles']);
				$newMediaData = array();
				if(count($mediaData) > 0){
					foreach($mediaData as $mek=>$mev){
						$newMediaData[$mek] = array(
							"display_name"=>$mev['title'],
							"summary"=>$mev['content'],
							"image"=>base_url()."../uploads/images/".$mev['filename'],
							"url"=>$mev['content_source_url']
						);
					}
				}
				$weiboData['articles'] = $newMediaData;
				$weiboData["msgtype"] = "articles";
			}
			$weiboDataJson = json_encode($weiboData);
//			$token = '';
			$p['set']['content'] = $weiboDataJson;
//			$sendallUrl = "https://m.api.weibo.com/2/messages/sendall.json?access_token=$token";
//			$this->request($sendallUrl,$weiboDataJson,'POST');
		}

		if(!isset($p['set']['interval']) || count($p['set']['interval']) <= 0){
			$p['set']['interval'] = array();
		}
		$event = $this->model->insert_event($p['info'],$p['set']['push_mode']);
		if ( ! $event) {		// 创建活动失败
			$this->meret(NULL, MERET_BADREQUEST, join("\n", $this->model->_errors));
			return ;
		}
        //如果不是群发，则uids没有定义，html页面一直出现undefined
        if(!isset($uids)){
            $uids = '';
        }

		// 活动发布设置
		$wb_info = $this->model->insert_info($event['id'], $p['set'],$uids);
		// 处理标签
		if ( ! empty($p['tags'])) 
			$tags = $this->model->insert_tags($event['id'], $p['tags']);
		// 处理用户组
		if ( ! empty($p['groups']))
			$groups = $this->model->insert_groups($event['id'], $p['groups']);

		/* 发布活动微博内容 */
		if(isset($p['set']) && isset($p['set']['push_mode']) && $p['set']['push_mode'] == 3){
			// 私信群发
			// 群发类型
			// text文字 articles多图文id
			$this->model->sendall_status();
		}else{
			$weibo = $this->model->event_status($wb_info);
		}
		if (isset($weibo) && is_string($weibo))
			$event['error'] = $weibo;

		/* 返回创建活动信息的数组 */
		$this->meret($event);
	}

	/* 活动列表 */
	public function get_list () 
	{
		$p = $this->input->post(NULL, TRUE);
		
		$res = $this->model->get_list($p);

		if (is_string($res))
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
		return ;
	}

	/* 删除活动, [活动] */
	public function delete ($id) 
	{
		// 删除活动，is_deleted置1
		$this->db->where('id', $id)->set('is_deleted', 1)->update('event');

		if ($this->db->affected_rows())
			$this->meret(TRUE);
		else 
			$this->meret(NULL, MERET_SVRERROR, '删除失败，请稍后尝试！');
	}

	/* 停止一项活动 */
	public function stop ($id) 
	{
		// 删除活动，status置2
		$this->db->where('id', $id)->set('status', 2)->update('event');

		if ($this->db->affected_rows())
			$this->meret(TRUE);
		else 
			$this->meret(NULL, MERET_SVRERROR, '操作失败，请稍后尝试！');
	}

	/* 获取活动基本信息 */
	public function detail ($id) 
	{
		$detail = $this->db->select('*')
			->from('event e')
			->where('id', $id)
			->get()->row_array();

		if ($detail) 
			$this->meret($detail);
		else 
			$this->meret($detail, MERET_EMPTY, '没有找到活动信息！');
	}

	/* 获取活动基本信息 */
	public function info ($id) 
	{

		$id = intval($id);
		if ($id < 1) {
			$this->meret($id, MERET_EMPTY, '没有活动信息！');
			return ;
		}

		$info = $this->db->select('e.event_title, e.start_time, e.end_time, e.push_status, e.status')
			->select('ewi.status_id, ewi.rule, ewi.push_mode')
			->select("GROUP_CONCAT(DISTINCT t.tag_name) AS tags", FALSE)
			->select("GROUP_CONCAT(DISTINCT wg.group_name) AS groups", FALSE)
			->from('event e')
			->join('event_wb_info ewi', 'e.id = ewi.event_id', 'left')
			->join('rl_event_wb_group rewg', 'e.id = rewg.event_id', 'left')
			->join('wb_group wg', 'rewg.group_id = wg.id', 'left')
			->join('rl_event_tag ret', 'e.id = ret.event_id', 'left')
			->join('tag t', 'ret.tag_id = t.id', 'left')
			->where('e.id', $id)
			->get()->row_array();

		// print_r($this->db->last_query());
		if ($info) {
			// 获取推送量占比 
			$this->meret($info);
		} else {
			$this->meret($info, MERET_EMPTY, '没有找到活动信息！');
		}
	}

	/* 获取活动的转发记录，从中获取参与者 */
	public function get_event_repost () 
	{
		$id = intval($this->input->get_post('id'));
		$date = date('Y-m-d H:i:s');

		$where = array (
			'e.id' => $id,
			'e.start_time <=' => $date,
			'e.end_time >=' => $date,
			'e.status <=' => 1
		);
		$event = $this->db->select('ewi.status_id, ewi.rule')
			->from('event e')
			->join('event_wb_info ewi', 'e.id = ewi.event_id', 'left')
			->where(array ('e.id'=>$id))
			->get()->row_array();

		if ( ! $event || ! isset($event['status_id'])) 
		{
			return FALSE;
		}

		// else 使用接口获取该条微博转发列表
	}

	/* 活动参与者 */
	public function get_participants ($id)
	{

		$id = intval($id);
		$is_winner = ($this->input->get_post('is_winner') == 1) ? 1 : 0;
		if ( ! $id > 0) {
			$this->meret(NULL, MERET_BADREQUEST, '找不到活动信息！');
			return ;
		}

		$where = array (
			'company_id' => $this->cid,
			'result' => $is_winner,
			'event_id' => $id
		);

		$total_num = $this->db->from('event_participant ep')
			->where($where)->get()->num_rows();

		if ($total_num > 0) {

			$p = $this->input->post();
			$page = intval($p['current_page']) > 0 ? intval($p['current_page']) : 0;
			$perpage = (intval($p['items_per_page']) > 0 && intval($p['items_per_page']) < 20) ? intval($p['items_per_page']) : 15;

			if ($page > ceil($total_num / $perpage)) 
				$this->db->limit($perpage);
			else 
				$this->db->limit($perpage, ($page - 1) * $perpage);

			$participants = $this->db->select('ep.*')
				->select('wu.screen_name, wu.user_weibo_id, wu.location, wu.gender, wu.verified_type, wu.profile_image_url, wu.followers_count, wu.friends_count, wu.statuses_count')
				->from('event_participant ep')
				->join('wb_user wu', 'ep.wb_user_id = wu.id')
				->where($where)
				->order_by('participated_at', 'desc')
				->get()->result_array();

			$data = array (
				'participants' => $participants,
				'current_page' => $page,
				'items_per_page' => $perpage,
				'total_number' => $total_num
			);
			$this->meret($data);
		} else {
			$this->meret(NULL, MERET_EMPTY, '没有任何参与者！');
		}

		return ;
	}

	public function stats ($id) 
	{
		$id = intval($id);
		if ($id < 1) {
			$this->meret($id, MERET_EMPTY, '没有活动信息！');
			return ;
		}

		$stats = $this->model->get_statistics($id);
		if ( ! is_string($stats)) 
			$this->meret($stats);
		else 
			$this->meret(NULL, MERET_BADREQUEST, $stats);
	}

	/* 设置获奖者 */
	public function set_winner ($event_id) 
	{
		$event = $this->_get_valid_event($event_id);
		if (is_string($event)) {
			$this->meret(NULL, MERET_BADREQUEST, $event);
			return ;
		}

		$winner_ids = $this->input->get_post('ids');
		if (is_string($winner_ids)) 
			$winner_ids = explode('_', $winner_ids);

		if ( ! $winner_ids) {
			$this->meret(NULL, MERET_BADREQUEST, '请正确选择参与用户！');
			return ;
		}

		$this->db->set('result', 1)
			->where(array ('event_id'=>$event_id, 'company_id'=>$this->cid))
			->where_in('id', $winner_ids)
			->update('event_participant');

		/* 设定成功，返回 */
		if ($this->db->affected_rows())
			$this->meret(TRUE);
		else 
			$this->meret(NULL, MERET_BADREQUEST, '设定失败');
	}

	/* 取消中奖 */
	public function unset_winner ($event_id) 
	{
		$event = $this->_get_valid_event($event_id);
		if (is_string($event)) {
			$this->meret(NULL, MERET_BADREQUEST, $event);
			return ;
		}

		$winner_ids = $this->input->get_post('ids');
		if (is_string($winner_ids)) 
			$winner_ids = explode('_', $winner_ids);

		if ( ! $winner_ids) {
			$this->meret(NULL, MERET_BADREQUEST, '请正确选择参与用户！');
			return ;
		}

		$this->db->set('result', 0)
			->where(array ('event_id'=>$event_id, 'company_id'=>$this->cid))
			->where_in('id', $winner_ids)
			->update('event_participant');

		/* 设定成功，返回 */
		$this->meret($this->db->affected_rows());
	}

	/* 获取在进行的活动，错误返回错误信息 */
	private function _get_valid_event ($event_id) 
	{
		$event_id = intval($event_id);
		if ( ! $event_id > 0) 
			return '无法获取活动信息！';

		$event = $this->db->select('start_time, end_time, status')
			->from('event e')
			->where(array ('id'=>$event_id, 'is_deleted'=>0, 'company_id'=>$this->cid))
			->get()->row_array();

		if ( ! $event) 
			return '没有找到活动信息！';

		if ($event['status'] != 1) 
			return '活动不在进行状态！';

		$date = date('Y-m-d H:i:s');
		if ($event['start_time'] > $date) 
			return '活动还没有开始！';

		if ($event['end_time'] < $date) 
			return '活动已经结束！';

		return $event;
	}

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

/* End of file event.php */
/* Location: ./application/controllers/meo/event.php */
