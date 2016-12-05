<?php 

/**
 * 微博私信，微信消息接收和自动回复类
 *
 * @author Xu Jian
 */

class Message_response 
{

	private $debug = FALSE; 		// DEBUG 状态 
	private $msg_info; 				// 消息信息数组
	private $account; 				// 接收账号信息 id, company_id
	private $cmn_info; 				// 存储在数据库中的信息 id
	private $user_info; 			// 发送信息用户的信息 id

	public function __construct($params = array()) 
	{
		$this->ci = &get_instance();
		$this->ci->load->config('common/message_response');
        $this->type = $params['type'];
		$this->msg_info 	= $params['msg_info'];
		$this->account 		= $params['account'];
		$this->cmn_info 	= $params['cmn_info'];
		$this->user_info 	= $params['user_info'];
	}

	/**
	 * FUNCTION reply 回复当前的消息
	 * @return reply_text 要回复的字符串
	**/
	public function reply () 
	{
		$msg_types = array('text', 'image', 'voice', 'video', 'location', 'link', 'event');
		if ( ! in_array($this->msg_info['msgtype'], $msg_types)) 
			return 'Unknown Message Type !'; 	// 未知类型，返回空字符串
		$reply_func = $this->msg_info['msgtype'] . '_reply';

		if (method_exists($this, $reply_func)) {
            // 存在特定处理方法
			$reply_msg = $this->$reply_func ();
        }else {
            // 使用预设信息回复
            $reply_msg = $this->ci->config->item('message_response_' . $this->msg_info['msgtype']);
        }
		// 对图文消息地址重编码后发送
		if ($reply_msg['type'] == 'news') {
			$this->ci->load->model('common/link_model', 'link');
			foreach ($reply_msg['news'] as &$news) {
				$news['url'] = $this->ci->link->insert_link(array(
					'company_id' 	=> $this->account['company_id'],
					'aid' 			=> $this->account['id'],
					'openid' 		=> $this->msg_info['fromusername'],
					'cmn_id' 		=> $this->cmn_info['id'],
					'media_id' 		=> $news['id'],
					'url' 			=> $news['content_source_url'],
					'type' 			=> 'wx',
				));
			}
		}
		$reply_text = $this->get_reply_text ($reply_msg);
//        exit;
        /** todo */

		// 自动回复的信息存库
		if ($this->msg_info['msgtype'] == 'text' 
			&& in_array($reply_msg['type'] , array('text', 'image', 'voice', 'video', 'music', 'news'))) {
            if(isset($this->user_info['openid'])){
                $openid = $this->user_info['openid'];
            }else{
                $openid = '';
            }

			$reply_data = array (
				'cmn_id' => $this->cmn_info['id'],
				'openid' => $openid,
				'type' => $reply_msg['type'],
				'created_at' => date('Y-m-d H:i:s'),
				'status' => 1
			);
			$content = isset($reply_msg['news']) ? $reply_msg['news'] : $reply_msg;
			$reply_data['media_id'] = isset($reply_msg['media_id']) ? $reply_msg['media_id'] : 0;
			if ($reply_msg['type'] == 'text')
				$reply_data['content'] = $reply_msg['content'];
			else 
				$reply_data['content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
            if($this->type == 'wb'){
                unset($reply_data['openid']);
                $this->ci->db->insert('wb_communication_reply', $reply_data);
            }else{
                $this->ci->db->insert('wx_communication_reply', $reply_data);
            }
		}

		// if($this->msg_info['msgtype'] == 'location'){
		// 	if($this->msg_info['location_x'] == '' || $this->msg_info['location_y'] ==''){
		// 		$reply_msg = $this->ci->config->item('message_response_' . $this->msg_info['msgtype']);
		// 	}
		// }
		return $reply_text;
	}

	/* 文本消息回复 */
	// 检测是否触发规则？
		// 是：获取规则标签 rule_tags
			// 获取回复信息，是否有为图文信息
				// 是：获取图文标签，修改链接地址
				// 否：直接回复内容
		// 否：账号是否设置有无匹配的回复？
			// 是：返回无匹配内容
			// 否：返回空字符串
	public function text_reply ()
	{

		$this ->ci ->load ->model('mex/rule_model','rule');
		// 根据内容获取规则ID，标签，关键词ID等信息
		$rule_info = $this->ci->rule->get_rule_by_content ($this->msg_info['content'], $this->account['id'],$this->type);
        /** TODO */
		if ($rule_info) // 有关键词匹配的回复
		{ 
			// 关键词和规则命中加1，update wx_communication 中的rule_id, keyword_id
            $ruleTable = $this->type == 'wb' ? 'wb_msg_rule' : 'wx_rule';
            $keywordTable = $this->type == 'wb' ? 'wb_msg_keyword' : 'wx_keyword';
            $communicationTable = $this->type == 'wb' ? 'wb_communication' : 'wx_communication';
			$this->ci->db->set('hits', 'hits+1', FALSE)->where('id',$rule_info['rule_id'])->update($ruleTable);
			$this->ci->db->set('hits', 'hits+1', FALSE)->where('id',$rule_info['id'])->update($keywordTable);
			$this->ci->db->set(array ('rule_id'=>$rule_info['rule_id'], 'keyword_id'=>$rule_info['id']))
				->where('id', $this->cmn_info['id'])
				->update($communicationTable);

			// update 用户的标签
			$this->ci->load->model('common/tag_model', 'tag');
			$this->ci->tag->tag_user($rule_info['tag_ids'], $this->user_info['id'], 'rule', $this->account, $this->type);
			/* 获取回复信息 */
			$reply_msg = $this->ci->rule->get_reply_msg ($rule_info, $this->user_info, $this->account,$this->type);
		}
		else // 无匹配关键词的回复
		{
			/* 修改回复方法：1小时内只能回复一次 */
			$nokeyTxt = $this ->ci ->rule ->select_other($this->account['id']);
			$nokeyTxt1 = $nokeyTxt['nokeyword_reply'];

			/* 一小时内是否回复过相同信息 */
			$time = date('Y-m-d H:i:s', time() - 3600);
			$replied = $this->ci->db->select('wcr.id')
				->from('wx_communication_reply wcr')
				->join('wx_communication wc', 'wcr.cmn_id = wc.id', 'left')
				->where(array('wc.openid'=>$this->msg_info['fromusername'], 'wcr.content'=>$nokeyTxt1, 'wcr.created_at >'=>$time))
				->get()->row_array();
		//	if ($replied OR ! $nokeyTxt1) return array ( 'type'=>'noreply' );

			// 有设置关键词回复，则回复，没有则不回复
			$this ->ci ->load->model("mex/umenu_model","umenu");
			switch ($nokeyTxt['type2_reply']) {
				case 'text':
					$reply_msg = array ( 'type' => 'text', 'content' => $nokeyTxt['nokeyword_reply']);
					break;
				case '':
					// 兼容以前没有这个字段的数据
					$reply_msg = array ( 'type' => 'text', 'content' => $nokeyTxt['nokeyword_reply']);
					break;
				case 'image':
					$mediaInfo = $this ->ci ->umenu ->get_media_info($nokeyTxt['nokeyword_reply'], $this->account['id']);
					$reply_msg = array ( 'type' => 'image', 'media_id' => $mediaInfo['wx_media_id']);
					break;
				case 'news':
					$mediaInfo = $this ->ci ->umenu ->get_media_info($nokeyTxt['nokeyword_reply'], $this->account['id']);
					$reply_msg = array ( 'type' => 'news', 'news' => array( $mediaInfo ));
					break;
				case 'articles':
					$mediaInfo = $this ->ci ->umenu ->get_media_info($nokeyTxt['nokeyword_reply'], $this->account['id']);
					$reply_msg = $mediaInfo;
					break;

				default:
					$reply_msg = array ( 'type' => 'noreply');
					break;
			}
		}
		return $reply_msg;
	}

	/* 语音消息回复 */
	public function event_reply () 
	{
		switch (strtoupper($this->msg_info['event']))
		{
			case 'SUBSCRIBE': # 关注账号
				if (isset($this->msg_info['ticket']))
				{   // 扫描二维码关注，记录关联

					$data['openid'] = $this->msg_info['fromusername'];
					$data['type'] = 1;
					$data['created_at'] = date('Y-m-d H:i:s');
					$data['scene_id'] = substr($this->msg_info['eventkey'], 8); // 去掉二维码参数前缀 qrscene_
					$this->ci->load->model('common/twodcode_model', 'twodcode');
					$this->ci->twodcode->event_scan($data);
				}
				$this->ci->load->model('mex/user_model', 'user');
				$this->ci->user->insert_userinfo($this->msg_info['fromusername'], $this->account['id']);

				$this->ci->load->model('mex/rule_model', 'rule');
				$nokey_reply = $this->ci->rule->select_other($this->account['id']);

				if ( ! empty($nokey_reply['subscribed_reply'])){
					$this ->ci ->load->model("mex/umenu_model","umenu");
					switch($nokey_reply['type_reply']){
						case 'text':
							$reply_msg = array ( 'type' => 'text', 'content' => $nokey_reply['subscribed_reply']);
							break;
						case '':
							// 兼容以前没有这个字段的数据
							$reply_msg = array ( 'type' => 'text', 'content' => $nokey_reply['subscribed_reply']);
							break;
						case 'image':
							$mediaInfo = $this ->ci ->umenu ->get_media_info($nokey_reply['subscribed_reply'], $this->account['id']);
							$reply_msg = array ( 'type' => 'image', 'media_id' => $mediaInfo['wx_media_id']);
							break;
						case 'news':
							$mediaInfo = $this ->ci ->umenu ->get_media_info($nokey_reply['subscribed_reply'], $this->account['id']);
							$reply_msg = array ( 'type' => 'news', 'news' => array( $mediaInfo ));
							break;
						case 'articles':
							$mediaInfo = $this ->ci ->umenu ->get_media_info($nokey_reply['subscribed_reply'], $this->account['id']);
							$reply_msg = $mediaInfo;
							break;

						default:
							$reply_msg = array ( 'type' => 'noreply');
							break;
					}
//					$reply_msg = array ( 'type' => 'text', 'content' => $nokey_reply['subscribed_reply'] );
                }else{
					$reply_msg = array ( 'type' => 'noreply' );
                }
//				file_put_contents('/home/test/liurq2.txt',json_encode($reply_msg));
			break;
			
			case 'UNSUBSCRIBE': # 取消关注
				$this->ci->load->model('mex/user_model','user');
				$this->ci->user->update_subscribe($this->msg_info['fromusername']);
				$reply_msg = array ( 'type' => 'noreply' );
			break;
			
			case 'MASSSENDJOBFINISH': # 群发完成
				$this->msg_info['actual_send_at'] = date('Y-m-d H:i:s', $this->msg_info['createtime']);
				$this->ci->load->model('mex/send_model','send');
				$this->ci->send->do_sendall_update($this->msg_info, $this->msg_info['msgid']);
				$reply_msg = array ( 'type' => 'noreply' );
			break;
			
			case 'SCAN': # 已关注时扫描二维码
				$data['openid'] = $this->msg_info['fromusername'];
				$data['type'] = 2;
				$data['created_at'] = date('Y-m-d H:i:s');
//                $data['scene_id'] = $this->msg_info['eventkey'];
				$data['scene_id'] = str_replace('qrscene_','',$this->msg_info['eventkey']); // 去掉二维码参数前缀 qrscene_
				$this->ci->load->model('common/twodcode_model','twodcode');
//                file_put_contents('/home/test/hh1.txt',json_encode($data));
				$this->ci->twodcode->event_scan($data);
				$reply_msg = array ( 'type' => 'noreply' );
			break;
			
			case 'CLICK': # 菜单点击
                $this->insert_click_menu($this->msg_info);
				$eventkey = $this->msg_info['eventkey'];
				$this ->ci ->load ->model('mex/umenu_model','umenu');
				$clickData = $this ->ci ->umenu ->menu_click($eventkey, $this->account['id']);

				if(strpos($clickData['medias'],',')) 
					$clickArr = explode(',', $clickData['medias']);
				else 
					$clickArr = (array)$clickData['medias'];

				$clickKey = rand(0, count($clickArr) - 1);
				$clickMediaId = $clickArr[$clickKey];
				$mediaInfo = $this ->ci ->umenu ->get_media_info($clickMediaId, $this->account['id']);
				switch($mediaInfo['type'])
				{
					case 'text':
						$reply_msg = array ( 'type' => 'text', 'content' => $mediaInfo['content']);
					break;

					case 'image':
						$reply_msg = array ( 'type' => 'image', 'media_id' => $mediaInfo['wx_media_id']);
					break;

					case 'voice':
						$reply_msg = array ( 'type' => 'voice', 'content' => $mediaInfo['wx_media_id']);
					break;

					case 'news':
						$reply_msg = array ( 'type' => 'news', 'news' => array( $mediaInfo ));
					break;

                    case 'articles':
                        $reply_msg = $mediaInfo;
                        break;

					default:
						$reply_msg = array ( 'type' => 'noreply');
					break;
				}
			break;
			
			case 'VIEW': # 菜单跳转，存储点击信息
                $this->insert_click_menu($this->msg_info);
				$reply_msg = array ( 'type' => 'noreply');
				break;
			
			default: # Unknown...
				$reply_msg = array ( 'type' => 'noreply');
			break;
		}

		return $reply_msg;
	}

	/*地理位置信息回复*/
	public function location_reply(){
		$num = 5;//图文信息的条数
		$shop_aid = $this->msg_info['tousername'];

		//获取公司的门店信息me_h5_locationdata
		$this->ci->load->model('common/shopplace_model','location');
		$shopLocations = $this->ci->location->get_shopplace_by_aid($this->account['id']);

		$rst = $this->get_min_location($shopLocations,$num);//获取最小的距离的地理位置
		// print_r($rst);

		$reply_msg = array('type'=>'location','content'=>$rst);

		//返回一个数组，类型，以及content
		return $reply_msg;
	}

	/* 获取最短距离 */
	public function get_min_location($shopLocations,$num=1){
		$distance = array();
		$location = array();
		$rst = array();
		foreach($shopLocations as $k=>$v){
			$user_location = explode(',',$v['longitude_latitude']);
			$user_X = $user_location[0];
			$user_Y = $user_location[1];
			$distance[$k] = $this->distance(($user_X),($user_Y),($this->msg_info['location_x']),($this->msg_info['location_y']),false);
			$distance[$k] = floor($distance[$k]*100)/100;
			$location[$k]['X'] = $user_X;
			$location[$k]['Y'] = $user_Y;
		}
		asort($distance);
		$temp = count($distance);
		if($temp<$num){
			$num = $temp;
		}
		for($i=0;$i<$num;$i++){
			$index =array_keys($distance)[$i];
			if($distance[$index]<=5){
				$rst[$i]['location'] = $shopLocations[$index];
				$rst[$i]['distance'] = $distance[$index];
				$rst[$i]['x'] = $location[$index]['X'];
				$rst[$i]['y'] = $location[$index]['Y'];
			}
			
		}
		// $rst['index'] = $index;
		// $rst['X'] = $location[$index]['X'];
		// $rst['Y'] = $location[$index]['Y'];
		// $rst['distance'] = $location[$index]['distance'];
		return $rst;
	}

	public function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
	{
	    $pi80 = M_PI / 180;
	    $lat1 *= $pi80;
	    $lng1 *= $pi80;
	    $lat2 *= $pi80;
	    $lng2 *= $pi80;

	    $r = 6372.797; // mean radius of Earth in km
	    $dlat = $lat2 - $lat1;
	    $dlng = $lng2 - $lng1;
	    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	    $km = $r * $c;

	    return ($miles ? ($km * 0.621371192) : $km);
	}


	/* 根据回复信息数组获取回复字符串 */
	public function get_reply_text ($reply_msg) 
	{
		$reply_templates = $this->ci->config->item('reply_templates');
		$reply_text = '';
		$time = time();
		$postfix = '公里';
		switch ($reply_msg['type']) 
		{
			case 'text':
				$reply_text = sprintf($reply_templates['text'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					$reply_msg['content']);
				break;
			case 'location':
				$items = '';
				foreach ($reply_msg['content'] as $location) 
				{
					if($location['distance']<1){
						$location['distance'] = $location['distance']*1000;
						$postfix = "米";
					}
					$items .= sprintf($reply_templates['news_item'], 
						$location['location']['display_name'].' ('.$location['distance'].')'.$postfix, 
						$location['location']['province'].$location['location']['city'].$location['location']['display_address'], 
						"http://st.map.qq.com/api?size=680*360&center=".$location['y'].','.$location['x']."&markers=116.490997,39.913799,red&zoom=16",
						base_url().'index.php/mex/shopplaceinfo/get_info?id='.$location['location']['id']);
				}

				$reply_text = sprintf($reply_templates['news'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					count($reply_msg['content']), 
					$items);
				break;
			case 'image':
				$reply_text = sprintf($reply_templates['image'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					$reply_msg['media_id']);
				break;
			
			case 'voice':
				$reply_text = sprintf($reply_templates['voice'],
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					$reply_msg['media_id']);
				break;
			
			case 'video':
				$reply_text = sprintf($reply_templates['text'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					$reply_msg['media_id'], 
					$reply_msg['title'], 
					$reply_msg['description']);
				break;
			
			case 'music':
				$reply_text = sprintf($reply_templates['text'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					$reply_msg['title'], 
					$reply_msg['description'], 
					$reply_msg['musicurl'], 
					$reply_msg['hqmusicurl'], 
					$reply_msg['thumbmediaid']);
				break;
			
			case 'news':
				$items = '';
				foreach ($reply_msg['news'] as $news) 
				{
					$items .= sprintf($reply_templates['news_item'], 
						$news['title'], 
						$news['digest'], 
						base_url() . '../uploads/images/' . $news['filename'], 
						$news['content_source_url']);
				}

				$reply_text = sprintf($reply_templates['news'], 
					$this->msg_info['fromusername'], 
					$this->msg_info['tousername'], 
					$time, 
					count($reply_msg['news']), 
					$items);

				break;
	            case 'articles':
	                $items = '';
	//                echo '<pre>';
	//                print_r($reply_msg);
	                $this->ci->load->model('mex/media_model', 'media');
	                $articlesMedia = $this ->ci->media ->get_media_all($reply_msg['id']);
	                $reply_msg['articles'] = $this->ci ->media ->get_news_info($articlesMedia[0]['articles']);
	//                print_r($reply_msg['articles']);
	                foreach ($reply_msg['articles'] as $articles)
	                {
	                    $items .= sprintf($reply_templates['news_item'],
	                        $articles['title'],
	                        $articles['digest'],
	                        base_url() . '../uploads/images/' . $articles['filename'],
	                        $articles['content_source_url']);
	                }
	                $reply_text = sprintf($reply_templates['news'],
	                    $this->msg_info['fromusername'],
	                    $this->msg_info['tousername'],
	                    $time,
	                    count($reply_msg['articles']),
	                    $items);
	                break;
				default:
					# code...
					break;
			 };
		 return $reply_text;
	}


	/**
	 * FUNCTION  把每次用户点击的菜单信息和用户信息存入wx_menu_detail表
	**/
    public function insert_click_menu($msg_info){
        $type = strtolower($msg_info['event']);

        $this->ci->db->select('wu.name')
            ->from('wx_umenu wu');
        if($type == 'click'){
            $this->ci->db->where(array('key' => $msg_info['eventkey']));
        }
        if($type == 'view'){
            $url = $msg_info['eventkey'];
            $this->ci->db->where(array('view_url' => $url));
        }

        $rst = $this->ci->db->get()->result_array();
        if($rst[0]['name']){
            $click_time = Date('Y-m-d H:i:s', $msg_info['createtime']);
            $menu_detail = array(
                'wx_aid'        =>   $this->account['id'],
                'openid'        =>   $msg_info['fromusername'],
                'click_time'    =>   $click_time,
                'menu_location' =>   $msg_info['eventkey'],
                'menu_name'     =>   $rst[0]['name']
            );
            $this->ci->db->insert('wx_menu_detail', $menu_detail);
        }
    }
}
