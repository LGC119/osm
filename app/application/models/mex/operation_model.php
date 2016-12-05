<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Operation模型 (微信处理)
*/

require_once APPPATH . 'models/common/operation_model.php';
class Operation_model extends OperationBase
{
	
	public function __construct()
	{
		parent::__construct();

		$this->_status_key = 'operation_status';
		$this->_cmu_source = 'wx';
        $this->load->model('mex/media_model','media');
	}

	/* 初始化接口对象 */
	public function initApi () {}

	/* 处理操作前执行动作 */
	public function _before_operation ($cmn_info) 
	{
	}

	/* 处理操作后执行动作 */
	public function _after_action () 
	{
	}

	/* 记录回复内容 */
	public function log_reply ($p, $cmn_info) 
	{
		if (isset($p['media_id']) && $p['media_id']) {
			# 回复多媒体消息
			$type = trim($p['media_type']);
			if ( ! in_array($type, array('news', 'image', 'voice','articles')))
				return '请使用正确的内容回复！';

			$this->db->select('m.type, m.wx_media_id, m.filename')
				->select('md.title, md.large_pic, md.small_pic, md.thumb_media_id, md.content_source_url, md.digest')
				->from('media m')
				->join('media_data md', 'm.id=md.mid', 'left');

            $oldMediaId = $p['media_id'];
			// 图片或语音回复
			if ($type == 'news'){
				$media_info = $this->db->where_in('m.id', $p['media_id'])->get()->result_array();
            }else if($type == 'articles'){
                $mediaData2 = $this ->media ->get_media_all($p['media_id']);
                $p['media_id'] = explode(',',$mediaData2[0]['articles']);
                $medias = array();
                $ori_type = $p['media_type'];
				$media_info = $this->db->where_in('m.id', $p['media_id'])->get()->result_array();
            }else{
				$media_info = $this->db->where('m.id', $p['media_id'])->get()->row_array();
            }

			if ( ! $media_info) 
				return '没有找到您选择的' . $p['media_type'] . '信息！';
            if(isset($ori_type) && $ori_type == 'articles'){
                $p['media_type'] = 'news';
            }

			$data = array (
				'cmn_id' => $cmn_info['id'],
				'openid' => $cmn_info['openid'],
				'type' => $p['media_type'],
				'media_id' => is_array($p['media_id']) ? 0 : intval($p['media_id']),
				'content' => json_encode($media_info, JSON_UNESCAPED_UNICODE), 
				'created_at' => date('Y-m-d H:i:s'),
				'staff_id' => $this->staff['sid'],
				'staff_name' => $this->staff['sname'],
				'status' => 0
			);

			$this->db->insert('wx_communication_reply', $data);
			$reply_id = $this->db->insert_id();

            if(isset($ori_type) && $ori_type == 'articles'){
                $data['type'] = $ori_type;
                $data['media_id'] = $oldMediaId;
            }

			if ($reply_id)
				return array_merge($data, array('id'=>$reply_id));
			else 
				return '记录回复失败，请稍后尝试！';
		} else {
			# 回复文本消息
			$content = trim($p['reply']);
			if ( ! $content) 
				return '请输入回复内容！';
			
			$data = array (
				'cmn_id' => $cmn_info['id'],
				'openid' => $cmn_info['openid'],
				'type' => 'text',
				'media_id' => 0,
				'content' => $content,
				'created_at' => date('Y-m-d H:i:s'),
				'staff_id' => $this->staff['sid'],
				'staff_name' => $this->staff['sname'],
				'status' => 0
			);

			$this->db->insert('wx_communication_reply', $data);
			$reply_id = $this->db->insert_id();

			if ($reply_id) 
				return array_merge($data, array('id'=>$reply_id));
			else 
				return '记录回复失败，请稍后尝试！';
		}
	}

	/*
	** 回复一条信息
	** $reply_id 数据库中的回复ID
	** $reply_info 回复信息数组，定时调用时，只需写明 reply_id (记录表中的ID)
	*/
	public function reply ($reply_id, $reply_info = '') 
	{
		if (empty($reply_info)) {
			$reply_info = $this->db->select('wcr.media_id, wcr.openid, wcr.content, wcr.type, wcr.cmn_id')
				->from('wx_communication_reply wcr')
				->where('id', $reply_id)
				->get()->row_array();
        }

		if ( ! $reply_info) 
			return '没有找到回复内容！';

		/* 使用微信接口回复 type = {text:文本, image:图片, voice:语音, video:视频, music:音乐, news:图文} */
		$content = '';
		switch ($reply_info['type']) {
			case 'text':
				$content .= '{"touser":"%s","msgtype":"text","text": {"content":"%s"}}';
				$content = sprintf($content, $reply_info['openid'], $reply_info['content']);
				break;
			
			case 'image':
				$content .= '{"touser":"%s","msgtype":"image","image":{"media_id":"%s"}}';
				$media_info = json_decode($reply_info['content'], true);
				$content = sprintf($content, $reply_info['openid'], $media_info['wx_media_id']);
				break;
			
			case 'voice':
				$content .= '{"touser":"%s","msgtype":"voice","voice":{"media_id":"%s"}}';
				$media_info = json_decode($reply_info['content'], true);
				$content = sprintf($content, $reply_info['openid'], $media_info['wx_media_id']);
				break;
			
			case 'video':
				$content .= '{"touser":"%s","msgtype":"video","video":{"media_id":"%s","title":"%s","description":"%s"}}';
				$media_info = json_decode($reply_info['content'], true);
				$content = sprintf($content, $reply_info['openid'], $media_info['media_id']);
				break;
			
			case 'music': 
				return 'No music support for the moment, deeply sorry !';
				break;
			
			case 'news':
				$content .= '{"touser":"%s","msgtype":"news","news":{"articles":[%s]}}';
				$media_info = json_decode($reply_info['content'], true);
				# 单条图文回复格式
				$item = '{"title":"%s","description":"%s","url":"%s","picurl":"%s"},';
				$items = '';
				foreach ($media_info as $news) {
					$picurl = base_url() . '../uploads/images/' . $news['filename'];
					$items .= sprintf($item, $news['title'], $news['digest'], $news['content_source_url'], $picurl);
				}
				$items = rtrim($items, ',');
				$content = sprintf($content, $reply_info['openid'], $items);
				break;
            case 'articles':
                $content .= '{"touser":"%s","msgtype":"news","news":{"articles":[%s]}}';
                $articlesMedia = $this ->media ->get_media_all($reply_info['media_id']);
                $media_info = $this ->media ->get_news_info($articlesMedia[0]['articles']);
                $item = '{"title":"%s","description":"%s","url":"%s","picurl":"%s"},';
                $items = '';
                foreach ($media_info as $news) {
                    $picurl = base_url() . '../uploads/images/' . $news['filename'];
                    $items .= sprintf($item, $news['title'], $news['digest'], $news['content_source_url'], $picurl);
                }
                $items = rtrim($items, ',');
                $content = sprintf($content, $reply_info['openid'], $items);
                break;
			default:
				return 'Unknown Reply type !';
				# code...
				break;
		}

		if ($content) {
			$this->load->library('wxapi', $this->session->userdata('wx_aid'));
			$res = $this->wxapi->reply($content);
			if ($res === TRUE) {
				# 回复成功
				$this->db->set('status', 1)
					->where('id', $reply_info['id'])
					->update('wx_communication_reply');
				$this->db->set($this->_status_key, REPLIED)
					->where('id', $reply_info['cmn_id'])
					->update('wx_communication');
				$this->change_status($reply_info['cmn_id'], REPLY, $reply_info['type']);
				return $reply_info;
			} else {
				return $res;
			}
		} else {
			return '没有回复内容！';
		}
	}
}