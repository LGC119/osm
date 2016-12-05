<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Send extends ME_Controller
{
    private $wx_aid;
    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->staff_id = $this->session->userdata('staff_id');
        $this ->company_id = $this->session->userdata('company_id');
        $this ->load ->model('mex/send_model','send');
        $this ->load ->model('mex/media_model','media');
    }
    public function index(){
        $this->send->get_send_num();
    }

	public function test(){
		$this->load->library('wxapi');
		$this->wxapi->test();
	}

    // 获取用户发送次数
    public function get_send_num(){
        $this->meret($this->send->get_send_num(),200);
    }

    // 首次绑定，用户进统计表中
    public function insert_send_num(){
        $this->send->insert_send_num();
    }

    // 获取发送数据的统计访问量
    public function get_send_stat(){

    }

    // 获取群发列表
    public function get_send_list()
    {
        $rst = $this->send->get_send_list();
        // 拼接media_id对应的内容
        foreach($rst['list'] as $k=>$v){
            switch($v['msgtype']){
                case 'text':
                    $rst['list'][$k]['data'] = $v['content'];
                    break;
                case 'image':
                case 'voice':
                case 'news':
                    $data = $this ->send ->get_media_info($v['media_id']);
                    if(!$data){
                        $rst['list'][$k]['data'] = '';
                        break;
                    }
                    if($data['type'] == 'articles'){
                        if($data['articles']){
                            $data = $this ->send ->get_articles_info($data['articles']);
                            $rst['list'][$k]['data'] = $data;
                        }
                    }else{
                        if($data){
                            $rst['list'][$k]['data'] = $data;
                        }
                    }
                    break;
                case 'articles':
                    $data = $this ->send ->get_media_info($v['media_id']);
                    if(!$data){
                        $rst['list'][$k]['data'] = '';
                        break;
                    }
                    if($data['type'] == 'articles'){
                        if($data['articles']){
                            $data = $this ->send ->get_articles_info($data['articles']);
                            $rst['list'][$k]['data'] = $data;
                        }
                    }else{
                        if($data){
                            $rst['list'][$k]['data'] = $data;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        if (empty($rst['list']))
        {
            $this->meret(NULL, MERET_EMPTY, '暂无群发记录！');
        }
        else
        {
            $this->meret($rst);
        }
    }

    protected function _countReceivers($list)
    {
        foreach ($list['list'] as $k => $v) 
        {
            $openids = explode(',', $v['openids']);
            $list['list'][$k]['receivers_num'] = count($openids);
        }
        return $list;
    }

    /**
     * 功能：根据用户openid进行群发，将信息存入wx_sendall表中
     * 参数：
     * openids     以逗号连接的粉丝openid
     * type        发送类型text image voice news
     * value       如果是text则是内容，如果是image voice news则是media_id
     * exec_time   定时发送的时间
     */
    public function send_openid(){
        $openids = $this ->input ->post('openids');
        $type = $this ->input ->post('type');
        $value = $this ->input ->post('value');
        $exec_time = $this ->input ->post('exec_time');
        if(!$exec_time){
            $do_send = TRUE;
            $exec_time = date('Y-m-d H:i:s',time());
        }else{
            $do_send = FALSE;
        }
        $mediaid = '';
        if($type == 'articles'){
            $mediaData2 = $this ->media ->get_media_all($value[0]);
            $value = explode(',',$mediaData2[0]['articles']);
        }
        if($type != 'text'){
            if(count($value) > 1){
                $newsData = $this ->upload_wx_newss($value,$type,$openids,$exec_time,$mediaid);
                $newsData = explode(',',$newsData);
                $value = $newsData[0];
                $mediaid = $newsData[1];
                $send_id = $newsData[2];
                // 更新数据到数据库wx_sendall
                $this ->send ->send_openid_update($type,$send_id,$openids,$value,$mediaid);
                if(!$value){
                    $this ->meret(NULL,MERET_APIERROR,'拼接多图文时出错！');
                    exit;
                }
            }else{
                $value = $value[0];

                // 根据media_id查wx_media_id
                $mediaid = $value;


                // 群发时的$value 就是群发media_id $mediaid是media表id
                $mediaData = $this ->media ->get_media_all($value);
                $newsData['title'] = $mediaData[0]['title'];
                // 保存数据到数据库wx_sendall
                $send_id = $this ->send ->send_openid($type,$openids,'',$exec_time,$this ->wx_aid,$this ->company_id,$mediaid);
                if(strpos($mediaData[0]['content_source_url'],'id=')){
                    $newUrl = explode('id=',$mediaData[0]['content_source_url']);
					$url = base_url().'index.php/h5page/wxh5_ext/go?id='.$newUrl[1].'-'.$this->wx_aid.'-'.$send_id;
                    $newUrl = $this->wxapi->return_url($this->wx_aid,$url);
                }else{
                    $newUrl = $mediaData[0]['content_source_url'];
                }

//echo '<pre>';
//print_r($mediaData);
                $array = array(
                    array(
                        'id'        =>     $mediaData[0]['id'],      // 图文的缩略图
                        'updated_at'        =>     $mediaData[0]['updated_at'],      // 图文的缩略图
                        'thumb_media_id'        =>     $mediaData[0]['thumb_media_id'],      // 图文的缩略图
                        'author'                =>     $mediaData[0]['author'],              // 作者
                        'title'                 =>     $mediaData[0]['title'],               // 标题
                        'content_source_url'    =>     $newUrl,  // 在图文消息页面点击“阅读原文”后的页面
                        'content'               =>     $mediaData[0]['content'],             // 图文消息页面的内容，支持HTML标签
                        'digest'                =>     $mediaData[0]['digest'],              // 图文消息的描述
                        'show_cover_pic'        =>     1      // 是否显示封面，1为显示，0为不显示
                    )
                );
//                var_dump($array);
//                return;
                // 上传图文 微信接口获取数据
                $news_media_id = $this ->wxapi ->wx_upload_news($array);
                $value = $news_media_id['media_id'];
                // 更新数据到数据库wx_sendall
                $this ->send ->send_openid_update($type,$send_id,$openids,$value);
            }
        }else{
        // 保存数据到数据库wx_sendall
        	$send_id = $this ->send ->send_openid($type,$openids,$value,$exec_time,$this ->wx_aid,$this ->company_id,$mediaid);
		}

        if($send_id > 0 ){
            //创建相应的活动
            $event = array();
            $event['event_title'] = $this ->input ->post('event_name');
            $event['type'] = $this ->input ->post('event_type');
            $event['industry'] = $this ->input ->post('event_industry');
            $event['aid'] = $this ->wx_aid;
            $event['staff_id'] = $this ->staff_id;
            $event['company_id'] = $this ->company_id;
            $event['created_at'] = date('Y-m-d H:i:s',time());
            // 来源于微信为1
            $event['from'] = 1;
            $this ->send ->create_event($event,$send_id,$openids);
//			exit;
            //保存成功
            if($do_send){
                // 立即群发
                $this ->do_sendall();
//                $doStatus = $this ->do_sendall();
//                if(!$doStatus){
//                    $this ->meret(NULL,MERET_APIERROR,'发送失败');
//                    exit;
//                }
            }
            $this ->meret(NULL,MERET_OK,'设置成功！');
            exit;
        }else{
            //保存失败
            $this ->meret(NULL,MERET_DBERR,'设置失败');
            exit;
        }
    }

    /**
     * 功能：拼接单图文组成多图文
     */
    public function upload_wx_newss($articles,$type,$openids,$exec_time){
        // 保存数据到数据库wx_sendall
        $send_id = $this ->send ->send_openid($type,$openids,'',$exec_time,$this ->wx_aid,$this ->company_id,'');
        $array = $this ->media ->get_news_info($articles);
        // 上传图文 微信接口获取数据
        $news_media_id = $this ->wxapi ->wx_upload_news($array,$send_id);
        if(isset($news_media_id['errcode'])){
            // 上传失败
            return false;
        }
        $data2['wx_media_id'] = $news_media_id['media_id'];
        $data2['type'] = 'articles';
        $data2['aid'] = $this ->wx_aid;
        $data2['created_at'] = date("Y-m-d H:i:s",$news_media_id['created_at']);
        $data2['updated_at'] = date("Y-m-d H:i:s",$news_media_id['created_at']+(3*3600*24));
        $data2['articles'] = implode(',',$articles);
        // 添加到media表中
        $mediaid = $this ->media ->insert_media($data2);
        return $news_media_id['media_id'].','.$mediaid.','.$send_id;
    }

    /**
     * 功能：查询当月群发的条数，与剩余条数
     * 参数：
     */
    public function select_num(){
        $firstday = date('Y-m-01 00:00:00',time());
        $lastday = date('Y-m-d 23:59:59',strtotime("$firstday + 1 month -1 day"));
        $data = $this ->send ->select_num($firstday,$lastday);
        $this ->meret($data,MERET_OK,'查询成功！');
    }


    /**
     * 功能：更改定时
     * 参数：
     * id         wx_sendall表id
     * exec_time  群发要执行的时间
     */
    public function update_send(){
        $id = $this ->input ->post('send_id');
        $exec_time = $this ->input ->post('exec_time');
        $status = $this ->send ->update_send($id,$exec_time);
        if($status){
            $this ->meret(NULL,MERET_OK,'更改成功！');
            exit;
        }else{
            $this ->meret(NULL,MERET_OTHER,'更改失败！');
            exit;
        }
    }

    /**
     * 功能：删除群发
     * 参数：
     * id 要删除的表id
     */
    public function delete_send(){
        $id = $this ->input ->post('send_id');
        $data['is_delete'] = 1;
        $status = $this ->send ->delete_send($id,$data);
        if($status){
            $this ->meret(NULL,MERET_OK,'删除成功！');
        }else{
            $this ->meret(NULL,MERET_EMPTY,'删除失败');
        }
    }

    /**
     * 功能：定时读取数据库，看是否该执行群发
     * 路径：app/index.php/mex/send/do_send_groupid
     */
    public function do_sendall(){
        return $this ->send ->do_sendall();
    }


	
}


