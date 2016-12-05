<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class CI_Wxapi
{

    // 接口URL
    private $api_weixin = 'https://api.weixin.qq.com/cgi-bin/';
    private $api_file = 'http://file.api.weixin.qq.com/cgi-bin/';
    private $api_mp = 'https://mp.weixin.qq.com/cgi-bin/';
    private $wx_aid;
    private $num=0;

    public function __construct() {

        $this ->ci = &get_instance();
        $this ->wx_aid = $this->ci->session->userdata('wx_aid');
    }

    public function index(){
    }

    // 数据库中获取access_token
    public function get_token($wx_aid=''){
        if(!$wx_aid)
            $wx_aid = $this ->wx_aid;
        $sql = "SELECT access_token FROM ".$this ->ci ->db ->dbprefix('wx_account')."
                WHERE id=".$wx_aid;
        $data = $this ->ci ->db ->query($sql)->result_array();
        if($data)
            return $data[0]['access_token'];
        else
            return '';
    }

    // 数据库中获取appid与secret
    public function get_appid_secret($wx_aid=''){
        $aid = $wx_aid ? $wx_aid : $this ->wx_aid;
        $sql = "SELECT appid,secret FROM ".$this ->ci ->db ->dbprefix('wx_account')."
                WHERE id=".$aid;
        $data = $this ->ci ->db ->query($sql)->result_array();
        return $data[0];
    }

    // 数据库中更新access_token
    public function update_token($access_token,$wx_aid=''){
        $aid = $wx_aid ? $wx_aid : $this ->wx_aid;
        $sql = "UPDATE ".$this ->ci ->db ->dbprefix('wx_account')." SET access_token='$access_token'
                WHERE id=".$aid;
        return $this ->ci ->db ->query($sql);
    }

    // 从微信服务器获取token
    public function wx_get_token($appid,$secret){
        $wxurl = $this ->api_weixin.'token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        $token = json_decode($this ->request($wxurl), true);
        return $token['access_token'];
    }

    /* 回复微信消息 */
    public function reply ($content) {
        $access_token = $this ->get_token();
        $reply_url = $this->api_weixin.'message/custom/send?access_token=' . $access_token;
        $output = $this->request($reply_url, $content,'POST');
        $wx_data = json_decode($output,true);
        if ($wx_data['errcode'] == 0){
            return TRUE;
        }else{
            // 系统繁忙 只重新上传一次文件
            if($wx_data['errcode'] == -1 && $this->num == 0){
                $this->num++;
                $content = json_decode($content,true);
                $media_id = $content['image']['media_id'];
                $sql = "SELECT filename FROM ".$this->ci->db->dbprefix('media')." WHERE wx_media_id='$media_id' LIMIT 1";
                $filename = $this->ci->db->query($sql)->result_array();
                // 重新上传一次文件
                $filename = 'uploads/images/'.$filename[0]['filename'];
                $wx_data = $this->wx_upload_file('image',$filename,$this->wx_aid);
                if(isset($wx_data['media_id'])){
                    // 更新
                    $mediaId = $wx_data['media_id'];
                    $sql = "UPDATE ".$this->ci->db->dbprefix('media')." SET wx_media_id='$mediaId' WHERE wx_media_id='$media_id'";
                    $this->ci->db->query($sql);
                    // 重拼content
                    $content['image']['media_id'] = $mediaId;
                    $content = json_encode($content);
                }

                // 获取到的 media_id 更新,并重新拼装$content
                return $this ->reply($content);

            }

            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->reply($content);
        }
    }

    /**
     * 功能：创建分组 【暂时不用】
     * 请求方式：post
     */
    public function wx_create_group($name){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'groups/create?access_token='.$access_token;
        $json = '{"group":{"name":"'.$name.'"}}';
    }

    /**
     * 功能：查看所有分组信息 【暂时不用】
     * 请求方式：GET
     */
    public function wx_select_group(){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'groups/get?access_token='.$access_token;
    }

    /**
     * 功能：查看用户所在组 【暂时不用】
     * 请求方式：post
     */
    public function wx_select_user_group(){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'groups/getid?access_token='.$access_token;
    }

    /**
     * 功能：修改分组名 【暂时不用】
     * 请求方式：post
     * 参数：
     */
    public function wx_update_group($id,$name){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'groups/update?access_token='.$access_token;
        $json = '{"group":{"id":"'.$id.'","name":"'.$name.'"}}';
    }

    /**
     * 功能：移动用户分组 【暂时不用】
     * 请求方式：post
     */
    public function wx_user_move_group(){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'groups/members/update?access_token='.$access_token;
    }

    /**
     * 功能：查询用户详细信息
     * 请求方式：GET
     */
    public function wx_get_user_info($openid,$wx_aid=''){

        $access_token = $this ->get_token($wx_aid);
        $wxurl = $this ->api_weixin.'user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $output = $this ->request($wxurl);
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_get_user_info($openid,$wx_aid);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：获取关注者列表
     * 请求方式：GET
     */
    public function wx_get_user_list($wx_aid,$next_openid=''){
        $access_token = $this ->get_token($wx_aid);
        $wxurl = $this ->api_weixin.'user/get?access_token='.$access_token.'&next_openid='.$next_openid;
        $output = $this ->request($wxurl);
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_get_user_list($wx_aid,$next_openid='');

        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：对用户地理位置的处理【暂时不用】
     * 请求方式：刚进公众号自动返回数据
     */
    public function wx_get_position($parameters=''){

    }

    /**
     * 功能：创建菜单
     * 请求方式：POST
     */
    public function wx_create_menu($json){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'menu/create?access_token='.$access_token;
        $output = $this ->request($wxurl,$json,'POST');
        $wx_data = json_decode($output,true);
//        return $wx_data;
        if ($wx_data['errcode'] == 0){
            return TRUE;
        }else{
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_create_menu($json);
        }
    }

    /**
     * 功能：查询菜单
     * 请求方式：GET
     */
    public function wx_select_menu(){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'menu/get?access_token='.$access_token;
        $output = $this ->request($wxurl);
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_select_menu();
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：删除菜单
     * 请求方式：GET
     */
    public function wx_delete_menu(){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'menu/delete?access_token='.$access_token;
        $output = $this ->request($wxurl);
        $wx_data = json_decode($output,true);
        if ($wx_data['errcode'] == 0){
            return TRUE;
        }else{
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_delete_menu();
        }
    }

    /**
     * 功能：菜单事件【暂时不用】
     * 返回数据处理
     */
    public function wx_menu_event($parameters=''){
    }

    /**
     * 功能：上传多媒体文件
     * 请求方式：POST
     */
    public function wx_upload_file($type,$filepath,$wx_aid='')
    {
        $access_token = $this ->get_token($wx_aid);
        $wxurl = $this ->api_file.'media/upload?access_token='.$access_token.'&type='.$type;
        if(phpversion() >= '5.5.0') 
        {
            $file = dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/' . $filepath;
            $media = new CURLFile($file);
            $output = $this->request( $wxurl , array("media" => $media), $method = 'POST');

        }
        else
        {
//            echo '@../'.$filepath;
            $output = $this->request( $wxurl , array("media" => '@../'.$filepath), $method = 'POST');
//            var_dump($output);
        } 

        $wx_data = json_decode($output,true);
//        var_dump($wx_data);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_upload_file($type,$filepath,$wx_aid);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：下载文件
     * 请求方式：GET
     */
    public function wx_download_file($media_id){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_file.'media/get?access_token='.$access_token.'&media_id='.$media_id;
        $output = $this ->request($wxurl);
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_download_file($media_id);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：上传图文素材
     * 请求方式：POST
     */
    public function wx_upload_news($array,$send_id=''){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'media/uploadnews?access_token='.$access_token;
        $articlesStr = array('articles'=>array());
        foreach($array as $newsK =>$newsV){
            // 拼接多图文时若单图文过期
//            var_dump($newsV);
            if(isset($newsV['updated_at'])){
                // 群发时拼接多图文 若单图文过期的话，重新上传并更新
                if((time()+100) > strtotime($newsV['updated_at'])){
                    $ww_data = $this ->wx_upload_file('thumb','uploads/images/'.$newsV['filename']);
//                    var_dump($ww_data);
                    $newwwData2['thumb_media_id'] = $newsV['thumb_media_id'] = $ww_data['thumb_media_id'];
//                    $newwwData1['created_at'] = date('Y-m-d H:i:s',$ww_data['created_at']);
                    $newwwData1['updated_at'] = date('Y-m-d H:i:s',$ww_data['created_at']+3*3600*24);
                    $this ->ci ->db ->update('media',$newwwData1,array('id'=>$newsV['id']));
                    $this ->ci ->db ->update('media_data',$newwwData2,array('mid'=>$newsV['id']));
                }
                unset($newsV['id']);
                unset($newsV['updated_at']);
            }
            if(count($array) > 1){
                if(strpos($newsV['content_source_url'],'id=')){
                    $newUrl = explode('id=',$newsV['content_source_url']);
                    $newUrl = isset($newUrl[1]) ? $newUrl[1] : '';
					$url = base_url().'index.php/h5page/wxh5_ext/go?id='.$newUrl.'-'.$this->wx_aid.'-'.$send_id;
                    $newsV['content_source_url'] = $this->return_url($this->wx_aid,$url);
                }
            }
            $newsV['show_cover_pic'] = 1;
//            if($newsK == 0){
//                $newsV['show_cover_pic'] = 1;
//            }else{
//                $newsV['show_cover_pic'] = 0;
//            }
            $articlesStr['articles'][] = $newsV;
        }
//        echo "<pre>";
//        print_r($articlesStr);exit;
        $articlesStr = $this ->_u_json_encode($articlesStr);
        $output = $this ->request($wxurl,$articlesStr,'POST');
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_upload_news($array);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：
     * 根据分组ID群发【暂时不用】POST
     * 根据openid群发  POST
     */
    public function wx_sendall($json,$wx_aid=''){
        $access_token = $this ->get_token($wx_aid);
        $wxurl = $this ->api_weixin.'message/mass/send?access_token='.$access_token;
        $wx_data = $this ->request($wxurl,$json,'POST');
        $wx_data = json_decode($wx_data,true);
        if ($wx_data['errcode'] == 0){
            return $wx_data;
        }else{
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_sendall($json,$wx_aid);
        }
    }


    /**
     * 功能：删除群发
     * 请求方式：POST
     */
    public function wx_delete_sends($json){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'message/mass/delete?access_token='.$access_token;
        $output = $this ->request($wxurl,$json,'POST');
        $wx_data = json_decode($output,true);
        if ($wx_data['errcode'] == 0){
            return TRUE;
        }else{
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_delete_sends($json);
        }
    }

    /**
     * 功能：创建临时二维码
     * 请求方式：POST
     */
    public function wx_get_qrcode_temp($time){
        $access_token = $this ->get_token();
        $wxurl = $this ->api_weixin.'qrcode/create?access_token='.$access_token;
        $parameters['json'] = '{"expire_seconds": '.$time.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
        $output = $this ->request($wxurl,$parameters['json'],'POST');
        $wx_data = json_decode($output,true);
        if (isset($wx_data['errcode'])){
            $err_return = $this->_return_err($wx_data);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_get_qrcode_temp($time);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：创建永久二维码
     * 请求方式：POST
     */
    public function wx_get_qrcode_permanent($access_token,$wx_aid,$scene_id){
        $wxurl = $this ->api_weixin.'qrcode/create?access_token='.$access_token;
        $params = array (
            'action_name' => 'QR_LIMIT_SCENE', 
            'action_info' => array (
                'scene' => array (
                    'scene_id' => $scene_id
                )
            )
        );

        $res = $this->request($wxurl, json_encode($params), 'POST');
        $wx_data = json_decode($res,true);
        if(isset($wx_data['errcode']) && ($wx_data['errcode']=='42001' || $wx_data['errcode']=='40001'|| $wx_data['errcode']=='40014')){
            $access_token = $this ->re_get_token($wx_aid);
            return $this ->wx_get_qrcode_permanent($access_token,$wx_aid,$scene_id);
        }else{
            return $wx_data;
        }
    }

    /**
     * 功能：获取二维码图片
     * 请求方式：GET
     */
    public function wx_get_qrcode_img($ticket,$scene_id){
        $ticket_en = urlencode($ticket);
        $wxurl = $this ->api_mp.'showqrcode?ticket='.$ticket_en;
        $res = $this->request($wxurl);

        if ( ! $res) return false;

        //将二进制数据存为图片
        $filename = $scene_id.'.jpg';
        $filepath = BASEPATH . '../../resources' . '/twodcode/' . $this->wx_aid . '/';

        if ( ! is_dir($filepath)) mkdir($filepath, 0777, TRUE);

        $file = fopen($filepath . $filename, 'w+');
        if ( ! $file) return false;
        fwrite($file, $res);
        fclose($file);

        return 'resources/twodcode/' . $this->wx_aid . '/' . $filename;
    }

    // 重新获取token
    public function re_get_token($wx_aid=''){
        // 读取数据库的appid 与 secret来获取 access_token
        $aid = $wx_aid ? $wx_aid : $this ->wx_aid;
        $appid_secret = $this ->get_appid_secret($aid);
        $appid = $appid_secret['appid'];
        $secret = $appid_secret['secret'];
        $access_token = $this ->wx_get_token($appid,$secret);
        // 更新数据表wx_account中的access_token
        $this ->update_token($access_token,$wx_aid);
        return $access_token;
    }

    // 授权
    public function return_url($wx_aid,$url){
        $appidArr = $this->get_appid_secret($wx_aid);
        $appid = $appidArr['appid'];
        $wx_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
//        $old_url =urlencode('http://115.29.175.205/me3/app/index.php/h5page/wxh5_ext/go');
        $old_url =urlencode($url);
//        $old_url = 'http://115.29.175.205/me3/app/index.php/h5page/wxh5_ext/go?id='.$id.'-'.$wx_aid.'-'.$send_id;
        $wx_url .= 'appid='.$appid.'&redirect_uri='.$old_url.'&response_type=code&';
//        $wx_url .= 'appid='.$appid.'&redirect_uri='.$old_url.'?id='.$id.'-'.$wx_aid.'-'.$send_id.'&response_type=code&';
        $wx_url .= 'scope=snsapi_base&state='.rand(0,9).'#wechat_redirect';
        return $wx_url;
    }

    // 错误处理函数 
    private function _return_err ($res,$wx_aid=''){
        if (in_array($res['errcode'], array('42001', '40001', '40014', '41001'))) {
            // 重新换取access_token
            if($this->num < 5){
                $this->num ++;
                $access_token = $this ->re_get_token($wx_aid);
                return $access_token;
            }
        }
        // 菜单数据为空
        if(strpos($res['errcode'],'46003')){
            return $data['menu']='';
        }
        return $res;
    }

    /* CURL 请求，Copied from Tecent Weibo API <*_*> */
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

    // 处理中文字符
    public function _u_json_encode($arr){
        if(phpversion() >='5.4.0'){
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $code = json_encode($arr);
            return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);
        }

    }

    public function send_template($wx_aid,$json){
        $access_token = $this ->get_token($wx_aid);
        $wxurl = $this ->api_weixin.'message/template/send?access_token='.$access_token;
        $wx_data = $this ->request($wxurl,$json,'POST');
        $wx_data = json_decode($wx_data,true);
        if ($wx_data['errcode'] == 0){
            return $wx_data;
        }else{
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_sendall($json,$wx_aid);
        }
    }

    public function api_add_template($wx_aid, $json)
    {
        $access_token = $this ->get_token($wx_aid);
        $wxurl = 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='.$access_token;
        $wx_data = $this ->request($wxurl,$json,'POST');
        $wx_data = json_decode($wx_data,true);
        if ($wx_data['errcode'] == 0){
            return $wx_data;
        }else{
            $err_return = $this->_return_err($wx_data,$wx_aid);
            if(isset($err_return['errcode']))
                return $err_return;
            else
                return $this ->wx_sendall($json,$wx_aid);
        }
    }
}