<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Send_model extends ME_Model{


    public function __construct(){
        parent::__construct();
        $this ->load ->model('mex/media_model','media');
        $this ->load ->model('mex/group_model','group');
        $this ->load ->library('Wxapi');

    }

    public function index(){

    }

    // 获取群发列表
    public function get_send_list()
    {
        $data = array();
        
        $current_page = $this->input->get_post('page');
        $items_per_page = $this->input->get_post('perpage');

        $current_page = intval($current_page) > 0 ? intval($current_page) : 1;
        $items_per_page = intval($items_per_page) > 0 ? intval($items_per_page) : 5;

        $this->db->select('count(id) sum', false);
        $this->db->from('wx_sendall');
        $this->db->where('company_id', $this->cid);
        $this->db->where('is_delete', 0);
        $rst = $this->db->get()->row_array();
        $total_number = $rst['sum'];


        $this->db->select('sendall.*,event.type eventtype,event.industry');
        $this->db->from('wx_sendall sendall');
        $this->db->join('event_wx_info eventinfo','sendall.id = eventinfo.send_id','left');
        $this->db->join('event event','event.id = eventinfo.event_id','left');
        $this->db->where('sendall.company_id', $this->cid);
        $this->db->where('sendall.is_delete', 0);
        $this->db->group_by('sendall.id');
        $this->db->order_by('sendall.created_at', 'desc');

        if ($current_page > ceil($total_number / $items_per_page)) {
            $this->db->limit($items_per_page);
        } else {
            $this->db->limit($items_per_page, ($current_page - 1) * $items_per_page);
        }

        $rst = $this->db->get()->result_array();
        if (!empty($rst))
        {
            $data['list'] = $rst;
            $data['current_page'] = $current_page;
            $data['items_per_page'] = $items_per_page;
            $data['total_number'] = $total_number;
            return $data;
        }
        else
        {
            return array();
        } 
    }


    // 根据粉丝openid群发的消息，存入数据库【wx_sendall】
    public function send_openid($type,$openids,$value,$exec_time,$wx_aid,$company_id,$mediaid=''){
        switch($type){
            case 'text':
                $json = '{"touser": ['.$openids.'], "msgtype": "text", "text": { "content": "'.$value.'"}}';
                break;
            case 'news':
                $json = '{"touser":['.$openids.'],"mpnews":{"media_id":"'.$value.'"},"msgtype":"mpnews"}';
                break;
            case 'articles':
                $json = '{"touser":['.$openids.'],"mpnews":{"media_id":"'.$value.'"},"msgtype":"mpnews"}';
				$type = 'news';
                break;
            case 'voice':
                $json = '{"touser":['.$openids.'],"voice":{"media_id":"'.$value.'"},"msgtype":"voice"}';
                break;
            case 'image':
                $json = '{"touser":['.$openids.'],"image":{"media_id":"'.$value.'"},"msgtype":"image"}';
                break;
            default:
                $json = '';
                break;
        }
        $data['openids'] = $openids;
        $data['msgtype'] = $type;
        $data['json_data'] = $json;
        $data['wx_aid'] = $wx_aid;
        $data['company_id'] = $company_id;
        if($type == 'text'){
            $data['content'] = $value;
        }else{
            $data['media_id'] = $mediaid;
        }
//        $data['json_data'] = $json;
        $data['exec_time'] = $exec_time;
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $this ->db ->insert('wx_sendall',$data);
        $send_id = $this ->db ->insert_id();
        return $send_id;
    }

    //更新群发的json_data内容
    public function send_openid_update($type,$send_id,$openids,$value,$media_id=''){
        switch($type){
            case 'text':
                $json = '{"touser": ['.$openids.'], "msgtype": "text", "text": { "content": "'.$value.'"}}';
                break;
            case 'news':
                $json = '{"touser":['.$openids.'],"mpnews":{"media_id":"'.$value.'"},"msgtype":"mpnews"}';
                break;
            case 'articles':
                $json = '{"touser":['.$openids.'],"mpnews":{"media_id":"'.$value.'"},"msgtype":"mpnews"}';
                break;
            case 'voice':
                $json = '{"touser":['.$openids.'],"voice":{"media_id":"'.$value.'"},"msgtype":"voice"}';
                break;
            case 'image':
                $json = '{"touser":['.$openids.'],"image":{"media_id":"'.$value.'"},"msgtype":"image"}';
                break;
            default:
                $json = '';
                break;
        }
        if($media_id){
            $this->db->update('wx_sendall',array('json_data'=>$json,'media_id'=>$media_id),array('id'=>$send_id));
        }else{
            $this->db->update('wx_sendall',array('json_data'=>$json),array('id'=>$send_id));
        }

    }

    // 查询当月已群发几条，还剩余几条
    public function select_num($firstday,$lastday){
        $sql = 'SELECT COUNT(1) AS num FROM '.$this ->db ->dbprefix('wx_sendall').'
                WHERE is_delete=0
                AND is_send=1
                AND actual_send_at>"'.$firstday.'"
                AND actual_send_at<"'.$lastday.'"';
        $num = $this ->db ->query($sql)->result_array();
        $data['num'] = (int)$num[0]['num'];
        $data['surplusNum'] = 4 - (int)$data['num'];
        return $data;
    }

    // 查询群发信息
    public function select_sendall_info(){
        $sql = 'SELECT id,send_type,group_name,exec_time,is_send,created_at,totalcount,sentcount
                    FROM '.$this ->db ->dbprefix('wx_sendall').'
                    WHERE is_delete=0';
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }

    // 更改定时
    public function update_send($id,$exec_time){
        $arrId = array(
            'id' =>$id
        );
        $data['exec_time'] = $exec_time;
        return $this ->db ->update('wx_sendall',$data,$arrId);
    }

    // 删除群发
    public function delete_send($id,$data){
        return $this ->db ->update('wx_sendall',$data,array('id'=>$id));
    }

    // crontab -e 判断到了群发的时间就发送
    public function do_sendall(){
        $sql = 'SELECT id,exec_time,json_data,wx_aid,media_id,json_data,msgtype FROM '.$this ->db ->dbprefix('wx_sendall').'
                WHERE is_send=0
                AND is_delete=0
                ORDER BY exec_time ASC';

        $data = $this ->db ->query($sql) ->result_array();
//        var_dump($data);
//        exit;
        foreach($data as $v){
            $nowTime = date('Y-m-d H:i:s',time());
            $maxNowTime = date('Y-m-d H:i:s',time()-6*60);
//            $maxNowTime = date('Y-m-d H:i:s',time()-2*60*60);
            // 当前时间大于发送时间 且 当前时间-6分还小于发送时间 则发送
            if($nowTime >= $v['exec_time'] && $maxNowTime <= $v['exec_time']){
                // 是分组url为sendall   是Openid的url为send
                // 如果是图片或是语音过期则重新上传 并群发
                if(isset($v['msgtype']) && ($v['msgtype'] == 'image' || $v['msgtype'] == 'voice')){
                    $urlType = $v['msgtype'] == 'image' ? 'images' : 'voice';
                    // 通过Media_id读取filename
                    $filename = $this ->media ->get_media_filename($v['media_id']);
                    $filepath = 'uploads/'.$urlType.'/'.$filename['filename'];
//                    file_put_contents('/home/test/ggg2.txt',$filepath);
                    $wx_data = $this ->wxapi ->wx_upload_file($v['msgtype'],$filepath,$v['wx_aid']);
                    if(!isset($wx_data['errcode'])){
                        // 更新media表
                        $upMedia['updated_at'] = date('Y-m-d H:i:s',$wx_data['created_at']+3*3600*24);
                        $upMedia['wx_media_id'] = $wx_data['media_id'];
                        $this ->db ->update('media',$upMedia,array('id'=>$v['media_id']));

                        // 更新sendall表
                        $jsonData = json_decode($v['json_data'],true);
                        $jsonData[$v['msgtype']]['media_id'] = $wx_data['media_id'];
                        $v['json_data']  = $sendData['json_data'] = json_encode($jsonData);
                        $this ->db ->update('wx_sendall',$sendData,array('id'=>$v['id']));

                    }
                }
                // 如果图文过期则重新上传并群发
                if(isset($v['msgtype']) && $v['msgtype'] == 'news'){
                    // 单图文与多图文
                    $mediaType = $this ->media ->get_media_filename($v['media_id']);
                    if(isset($mediaType['type'])){
                        // 单图文
                        if($mediaType['type'] == 'news'){
//                            file_put_contents('/home/test/ggg3.txt',json_encode($mediaType));
                            // 如果过期的话 重新上传 然后更新一下media 与media_data表的media_id
                            // 获取这条图文具体数据
                            $mediaData = $this ->media ->get_media_all($v['media_id']);
                            // 过期
                            if(isset($mediaData[0]['updated_at']) && (time()+100) > strtotime($mediaData[0]['updated_at'])){
                                $newsData['id'] = $mediaData[0]['id'];
                                $newsData['updated_at'] = $mediaData[0]['updated_at'];
                                $newsData['filename'] = $mediaData[0]['filename'];
                                $newsData['thumb_media_id'] = $mediaData[0]['thumb_media_id'];
                                $newsData['author'] = $mediaData[0]['author'];
                                $newsData['title'] = $mediaData[0]['title'];
                                if(strpos($mediaData[0]['content_source_url'],'id=')){
                                    $newUrl = explode('id=',$mediaData[0]['content_source_url']);
									$url = base_url().'index.php/h5page/wxh5_ext/go?id='.$newUrl.'-'.$v['wx_aid'].'-'.$v['id'];
                                    $this->wxapi->return_url($v['wx_aid'],$url);
                                }else{
                                    $newUrl = $mediaData[0]['content_source_url'];
                                }

                                $newsData['content_source_url'] = $newUrl;
                                $newsData['content'] = $mediaData[0]['content'];
                                $newsData['digest'] = $mediaData[0]['digest'];
                                $newsData['show_cover_pic'] = $mediaData[0]['show_cover_pic'];
                                $news_wx_data = $this ->wxapi ->wx_upload_news(array($newsData));
                                // 更新后的图文 media_id
                                if(!isset($news_wx_data['errcode'])){
//                                    $newsData1['wx_media_id'] = $news_wx_data['media_id'];
//                                    $this ->db ->update('media',$newsData1,array('id'=>$v['media_id']));

                                    // 更新sendall表
                                    $newsJsonData = json_decode($v['json_data'],true);
                                    $newsJsonData['mpnews']['media_id'] = $news_wx_data['media_id'];
                                    $v['json_data'] = $newsSendData['json_data'] = json_encode($newsJsonData);
                                    $this ->db ->update('wx_sendall',$newsSendData,array('id'=>$v['id']));
                                }
                            }
                        }
                        // 多图文
                        if($mediaType['type'] == 'articles'){
                            // 重新上传 并且返回新的Media_id 更新media表【多图文】

                            $mediaData = $this ->media ->get_media_all($v['media_id']);
//                            file_put_contents('/home/test/ggg4.txt',json_encode($mediaData));
                            if(isset($mediaData[0]['updated_at']) && (time()+100) > strtotime($mediaData[0]['updated_at'])){
                                $articlesMedia = $this ->media ->get_media_all($v['media_id']);

                                if(isset($articlesMedia[0]['articles']) && $articlesMedia[0]['articles']){
                                    $articlesData = $this ->media ->get_news_info($articlesMedia[0]['articles']);
                                    $news_wx_data = $this ->wxapi ->wx_upload_news($articlesData,$v['id']);
                                }

                                // 更新后的多图文 media_id
                                if($news_wx_data && !isset($news_wx_data['errcode'])){
                                    $newsData['updated_at'] = date('Y-m-d H:i:s',$news_wx_data['created_at']+3*24*3600);
                                    $newsData['wx_media_id'] = $news_wx_data['media_id'];
                                    $this ->db ->update('media',$newsData,array('id'=>$v['media_id']));

                                    // 更新sendall表
                                    $newsJsonData = json_decode($v['json_data'],true);
                                    $newsJsonData['mpnews']['media_id'] = $news_wx_data['media_id'];
                                    $v['json_data'] = $newsSendData['json_data'] = json_encode($newsJsonData);
                                    $this ->db ->update('wx_sendall',$newsSendData,array('id'=>$v['id']));
                                }


                            }
                        }
                    }
                }

                $wx_data = $this ->wxapi ->wx_sendall($v['json_data'],$v['wx_aid']);
                if($wx_data['errcode'] != 0){
                    return FALSE;
                }
                $data1['msg_id'] = $wx_data['msg_id'];
                $data1['is_send'] = 1;
                $this ->db ->update('wx_sendall',$data1,array('id'=>$v['id']));
//                return $status;
                sleep(6);
            }
        }
    }

    // 群发成功返回的信息更新
    public function do_sendall_update($data,$msg_id){
        $sendData['status'] = $data['status'];
        $sendData['totalcount'] = $data['totalcount'];
        $sendData['filtercount'] = $data['filtercount'];
        $sendData['sentcount'] = $data['sentcount'];
        $sendData['errorcount'] = $data['errorcount'];
        $sendData['actual_send_at'] = $data['actual_send_at'];
        // 群发成功时 对用户群发次数进行统计 更新数据
        $this->send_num_update($msg_id);

        return $this ->db ->update('wx_sendall',$sendData,array('msg_id'=>$msg_id));
    }

    // 创建活动
    public function create_event($event,$sendid,$openids){
        $this ->db ->insert('event',$event);
        $event_id = $this ->db ->insert_id();
        $data['company_id'] = $event['company_id'];
        $data['event_id'] = $event_id;
        $data['send_id'] = $sendid;
        $this ->db ->insert('event_wx_info',$data);
		$time = date('Y-m-d H:i:s',time());

		$sql = "SELECT id FROM ".$this->db->dbprefix('wx_user')." WHERE openid in ($openids)";
		$userIds = $this->db->query($sql)->result_array();

		$psql = '';
		foreach($userIds as $userv){
			$psql .= "('".$event['company_id']."','".$event_id."','".$time."','".$userv['id']."'),";
		}
		$psql = rtrim($psql,',');
		if($psql){
			$sql = "INSERT INTO ".$this->db->dbprefix('event_participant')."(`company_id`,`event_id`,`participated_at`,`wx_user_id`)
					VALUES$psql";
			$this->db->query($sql);
		}
//		echo $this->db->last_query();
    }

    // 通过media_id获取 media单条详情数据
    public function get_media_info($media_id){
        $sql = "SELECT media.id mediaid,media.*,md.* FROM ".$this ->db ->dbprefix('media')." media
                    LEFT JOIN ".$this ->db ->dbprefix('media_data')." md
                        ON media.id=md.mid
                    WHERE media.id='$media_id'";
        $data = $this ->db ->query($sql) ->result_array();
        return isset($data[0]) ? $data[0] : '';
    }

    // 更新发送信息
    public function send_num_update($msgid){
        // 群发成功时 对用户群发次数进行统计
        $sql = 'SELECT openid FROM '.$this->db->dbprefix('wx_user');
        $sendOpenId = $this->db->query($sql)->result_array();
        $openidStr = '';
        foreach($sendOpenId as $v){
            $openidStr .= '("'.$v['openid'].'"),';
        }
        $openidStr = trim($openidStr,',');
        $sql = "INSERT IGNORE INTO ".$this->db->dbprefix('wx_send_num')."(`openid`)
                    VALUES".$openidStr;
        $this->db->query($sql);

        $this->db->update('wx_send_num',array('year'=>date("Ym",time())));

        $sql = 'SELECT openids FROM '.$this->db->dbprefix('wx_sendall')." WHERE msg_id='$msgid'";
        $openid = $this->db->query($sql)->result_array();
        $openid = isset($openid[0]['openids']) ? $openid[0]['openids'] : 0;

        $sql = "UPDATE ".$this->db->dbprefix('wx_send_num')." SET new_num=new_num+1
                    WHERE openid in($openid) AND new_num<4";
        $this->db->query($sql);
    }

    // 绑定时将统计信息生成一遍
    public function insert_send_num(){
        $sql = 'SELECT openid FROM '.$this->db->dbprefix('wx_user');
        $sendOpenId = $this->db->query($sql)->result_array();
        $openidStr = '';
        foreach($sendOpenId as $v){
            $openidStr .= '("'.$v['openid'].'"),';
        }
        $openidStr = trim($openidStr,',');
        $sql = "INSERT IGNORE INTO ".$this->db->dbprefix('wx_send_num')."(`openid`)
                    VALUES".$openidStr;
        $this->db->query($sql);
        $this->db->update('wx_send_num',array('year'=>date("Ym",time())));
    }

    // 获取用户对应发送次数
    public function get_send_num(){
        $sql = "SELECT openid,new_num FROM ".$this->db->dbprefix('wx_send_num');
        $data = $this->db->query($sql)->result_array();
        $newData = array();
        foreach($data as $v){
            $newData[$v['openid']] = 4-$v['new_num'];
        }
        return $newData;
    }


    // 通过articles字符串查找所有的数据
    public function get_articles_info($articles){
        $sql = "SELECT media.id mediaid,media.*,md.* FROM ".$this ->db ->dbprefix('media')." media
                    LEFT JOIN ".$this ->db ->dbprefix('media_data')." md
                        ON media.id=md.mid
                    WHERE media.id in($articles)";
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        $articleArr = explode(',',$articles);
        foreach($articleArr as $v){
            foreach($data as $v1){
                if($v == $v1['mediaid']){
                    $newData[] = $v1;
                }
            }
        }
        return $newData;
    }


} 