<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Media extends ME_Controller
{
    private $wx_aid;
    private $company_id;
    private $staff_id;
    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->company_id = $this->session->userdata('company_id');
        $this ->staff_id = $this->session->userdata('staff_id');
        // 文件上传
        $this ->load ->library('Fileupload');
        // 微信API
        $this ->load ->library('Wxapi');
        // 素材模型
        $this->load->model('mex/media_model','media');
    }

    /**
     * 功能：上传图片到本地
     */
    public function upload_image_local(){
        $size = $this ->input ->post('size');
        $size = $size ? $size : '200';
        $data = $this ->fileupload ->upload_image_local($size);
        if($data['msg']=='success'){
            $data1['filename'] = $data['filename'];
            $data1['type'] = $data['type'];
            $this ->meret($data1,MERET_OK,'上传成功！');
        }else{
            $this ->meret(NULL,MERET_OTHER,'上传失败！');
        }
    }

    /**
     * 功能：上传语音到本地
     */
    public function upload_voice_local($from=''){
        $size = $this ->input ->post('size');
        $size = $size ? $size : '258';
        if(!$from){
            $data = $this ->fileupload ->upload_voice_local($size);
            if($data['msg'] == 'success')
                $this ->meret($data,MERET_OK,'上传成功！');
            else
                $this ->meret(NULL,MERET_OTHER,'上传失败！');
        }else{
            return $this ->fileupload ->upload_voice_local($size);
        }
    }

    /**
     * 功能：上传图片到微信接口，并插入数据库
     * 参数：
     * type 上传类型  默认是Image  可选： image  thumb
     */
    public function upload_wx_image($type='image'){
        $filename = $this ->input ->post('filename');
        $filepath = 'uploads/images/'.$filename;
        $wx_data = $this ->wxapi ->wx_upload_file($type,$filepath);
//        exit;
        if(isset($wx_data['errcode'])){
            $this ->meret($wx_data,MERET_OTHER,'微信上传图片接口失败');
            exit;
        }
        if($wx_data){
            //微信数据
            $data['filename'] = $filename;
            $data['aid'] = $this ->wx_aid;
            $data['created_at'] = date('Y-m-d H:i:s',$wx_data['created_at']);
            $data['type'] = isset($wx_data['type']) ? $wx_data['type'] : '';
            $data['wx_media_id'] = isset($wx_data['media_id']) ? $wx_data['media_id'] : '';
            $data['updated_at'] = date('Y-m-d H:i:s',$wx_data['created_at']+(3*3600*24));
        }
        $status = $this ->media ->insert_media($data);
        $data2['mid'] = $status;
        $data2['title'] = $this ->input ->post('imageTitle');
        $data2['description'] = $this ->input ->post('imageDesc');
        $this ->media ->insert_media_data($data2);
        if($status)
            $this ->meret(NULL,MERET_OK,'上传成功');
        else
            $this ->meret(NULL,MERET_OTHER,'上传失败');
    }

    /**
     * 功能：上传语音
     */
    public function upload_wx_voice(){
        // 把语音描述插入数据库中
//        var_dump($this ->input ->post());
//        var_dump($_FILES);
//        exit;
        $returnData = $this ->upload_voice_local('true');
        if($returnData['msg'] == 'success'){
            // 类别
            $type = 'voice';
            // 上传文件路径
            $filepath = 'uploads/voice/'.$returnData['filename'];
            // 上传语音
            $wx_data = $this ->wxapi ->wx_upload_file($type,$filepath);
            if(isset($wx_data['errcode'])){
                $this ->meret(NULL,MERET_APIERROR,'上传语音接口出错！');
                exit;
            }
            if($wx_data){
                $dataI['filename'] = $returnData['filename'];
                $dataI['company_id'] = $this ->company_id;
                $dataI['aid'] = $this ->wx_aid;
                $dataI['staff_id'] = $this ->staff_id;
                $dataI['created_at'] = date("Y-m-d H:i:s",$wx_data['created_at']);
                $dataI['updated_at'] = date("Y-m-d H:i:s",$wx_data['created_at']+(3 * 3600 *24));
                $dataI['type'] = $wx_data['type'];
                $dataI['wx_media_id'] = $wx_data['media_id'];
                $mediaId = $this ->media ->insert_media($dataI);
                if($mediaId)
                    $this ->meret($mediaId,MERET_OK,'上传语音成功！');
                else
                    $this ->meret(NULL,MERET_DBERR,'保存数据库时失败了！');
            }else{
                $this ->meret(NULL,MERET_APIERROR,'上传微信时失败了！');
                exit;
            }
        }else{
            $this ->meret(NULL,MERET_OTHER,'上传本地失败！');
            exit;
        }
    }

    /**
     * 功能：添加语音描述
     */
    public function post_voice(){
        $desc = $this ->input ->post('voiceDesc');
        $title = $this ->input ->post('voiceTitle');
        $media_id = $this ->input ->post('voiceMediaId');
        $status = $this ->media ->post_voice($desc,$title,$media_id);
        if($status)
            $this ->meret(NULL,MERET_OK,'添加成功');
        else
            $this ->meret(NULL,MERET_OTHER,'添加失败');
    }

    /**
     * 功能：上传图文到微信接口，并插入数据库
     */
    public function upload_wx_news($type='thumb'){
        // 若存在mediaid则为更新
        $mediaid = $this ->input ->post('mediaid');
        $tags = $this ->input ->post('tags');
        $imgname = $this ->input ->post('imgname');
        $filepath = 'uploads/images/'.$imgname;
        // 上传图片素材
        $wx_data = $this ->wxapi ->wx_upload_file($type,$filepath);
        if(isset($wx_data['errcode'])){
            $this ->meret(NULL,MERET_APIERROR,'微信图片上传接口出问题了！');
            exit;
        }
        if($wx_data){
            $data2['thumb_media_id'] = $wx_data['thumb_media_id'];
        }else{
            $this ->meret(NULL,MERET_APIERROR,'微信图片上传接口出问题了！');
            exit;
        }
        // 上传图文素材
        $data2['author'] = $this ->input ->post('author');
        $data2['title'] = $this ->input ->post('title');
        $data2['content_source_url'] = $this ->input ->post('content_source_url');
        $content = $this ->input ->post('content');
        $data2['content'] = str_replace('style=";','style="',$content);
        $data2['content'] = str_replace('&#39;',"'",$content);
        $data2['digest'] = $this ->input ->post('digest');
        $data2['show_cover_pic'] = 0;

        // 添加H5页面
        // 如果不存在跳转Url则生成h5页面并返回url，
        $h5Status = trim($this->input->post('content_source_url'));
        if(!$h5Status){
            // 标题  图片url 内容 添加到me_h5_page表中 返回id
            $html_code = array(
                $data2['title'],
                $filepath,
                $data2['content']
            );
            $h5Arr = array(
                'html_code' => json_encode($html_code),
                'company_id'=>$this->company_id,
                'title'=>$data2['title'],
                'template'=>'104info',
                'created_at'=>date('Y-m-d H:i:s',time())
            );
            $h5Data['h5_page_id'] = $h5Id = $this->media->insert_h5($h5Arr);
            $data2['content_source_url'] = base_url().'index.php/h5page/wxh5_ext/go?id='.$h5Id;
            // 标签 添加到rl_h5_page_tag表中
            foreach($tags as $tagh5V){
                $h5Data['tag_id'] = $tagh5V;
                $this ->media ->rl_h5_tag($h5Data);
            }
        }


        $array = array(
            array(
                'thumb_media_id'        =>     $data2['thumb_media_id'],      // 图文的缩略图
                'author'                =>     $data2['author'],              // 作者
                'title'                 =>     $data2['title'],               // 标题
                'content_source_url'    =>     $data2['content_source_url'],  // 在图文消息页面点击“阅读原文”后的页面
                'content'               =>     $data2['content'],             // 图文消息页面的内容，支持HTML标签
                'digest'                =>     $data2['digest'],              // 图文消息的描述
                'show_cover_pic'        =>     $data2['show_cover_pic']       // 是否显示封面，1为显示，0为不显示
            )
        );
        // 上传图文 微信接口获取数据
        $news_media_id = $this ->wxapi ->wx_upload_news($array);

        if(isset($news_media_id['errcode'])){
            $this ->meret(NULL,MERET_APIERROR,'微信图文上传接口出问题了！');
            exit;
        }
        // 添加media表的数据
        $data3['company_id'] = $this ->company_id;
        $data3['staff_id'] = $this ->staff_id;
        $data3['aid'] = $this ->wx_aid;
        $data3['filename'] = $imgname;
        $data3['wx_media_id'] = $news_media_id['media_id'];
        $data3['type'] = 'news';
        $data3['created_at'] = date("Y-m-d H:i:s");
        $data3['updated_at'] = date("Y-m-d H:i:s", time() + 259200); // 三天之内失效
        // 添加到media表中
        $media_id = $this ->media ->insert_media($data3,$mediaid);
        if($mediaid)
            $data4['media_id'] = $mediaid;
        else
            $data4['media_id'] = $media_id;
        // 将标签与素材绑定
        if($mediaid){
            // 删除该mediaid的标签
            $this ->media ->delete_media_tag($mediaid);
        }
        foreach($tags as $tabV){
            $data4['tag_id'] = $tabV;
            $this ->media ->rl_media_tag($data4);
        }

        // 添加media_data表
        if(!$mediaid){
            $data2['mid'] = $media_id;
        }
        $status = $this ->media ->insert_media_data($data2,$mediaid);


        if($status){
            if($mediaid)
                $this ->meret(NULL,MERET_OK,'修改图文成功！');
            else
                $this ->meret(NULL,MERET_OK,'上传图文成功！');
        }else{
            if($mediaid)
                $this ->meret(NULL,MERET_DBERR,'修改图文失败！');
            else
                $this ->meret(NULL,MERET_DBERR,'上传图文失败！');
        }
    }


    /**
     * 功能：获取图片信息 获取语音信息 获取图文信息
     */
    public function get_media_data(){
        $type = trim($this ->input ->get('type'));

        if ( ! in_array($type, array('image', 'news', 'voice','articles')))
            $type = 'all';

        $data = $this ->media ->get_media_data($type);
        // 多图文，特殊处理
        if($type == 'articles'){
            $articlesData = array();
            foreach($data['data'] as $datav){
                array_push($articlesData, array('mediaid'=>$datav['mediaid'],'data'=>$this ->media ->get_news_info($datav['articles'])));
            }

            $articlesData['sum'] = $data['sum'];
            $articlesData['perpage'] = $data['perpage'];
            $articlesData['page'] = $data['page'];
            $this->meret($articlesData);
            exit;
        }

        if(!$data){
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            return;
        }
        foreach($data['data'] as $k=>$v){
            if($v['type'] != 'voice'){
                $data['data'][$k]['filepath'] = 'uploads/images/'.$v['filename'];
            }else{
                $data['data'][$k]['filepath'] = 'uploads/voice/'.$v['filename'];
            }
        }
        $this ->meret($data,MERET_OK,'获取成功！');
    }

    // 删除
    public function delete()
    {
        $id = $this->input->post('id');
        $where = array(
            'id' => $id
        );
        $rst = $this->media->update_delete_mark('media', $where);
        if ($rst)
        {
            $this ->meret($id,MERET_OK,'删除成功！');
        }
        else
        {
            $this->meret(NULL, MERET_DBERR, '删除失败！');
        }
    }

    // media id对应标签
    public function media_to_tag(){
        $data = $this ->media ->media_to_tag();
        $newData = array();
        foreach($data as $v){
            $newData[$v['media_id']] = array();
            if(strpos($v['tag_id'],',')){
                $tag_id = explode(',',$v['tag_id']);
                $tag_name = explode(',',$v['tag_name']);
                $newData[$v['media_id']]['id'] = $tag_id;
                $newData[$v['media_id']]['name'] = $tag_name;
//                foreach($tag_id as $k1 =>$v1){
//                    $tag['id'] = $v1;
//                    $tag['name'] = $tag_name[$k1];
//                    array_push($newData[$v['media_id']],$tag);
//                }
            }else{
                $newData[$v['media_id']]['id'] = (array)$v['tag_id'];
                $newData[$v['media_id']]['name'] = (array)$v['tag_name'];
//                array_push($newData[$v['media_id']],$tag);
            }
        }
//        var_dump($newData);
        $this ->meret($newData,MERET_OK,'标签读取成功！');
    }

    // 拼接多图文
    public function spell(){
        $data['company_id'] = $this ->company_id;
        $data['staff_id'] = $this ->staff_id;
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $data['updated_at'] = date('Y-m-d H:i:s',time() + 3 * 24 * 3600);
        $data['type'] = 'articles';
        $data['articles'] = $this ->input ->post('mediaIds');
        //  数据插入数据库中 返回插入ID
        $media_id = $this ->media ->spell($data);
        //  通过传过来的ID，拼接，传服务器返回media_id  更新media表，刚插入的那条数据
        $wx_media_id = $this ->upload_wx_newss($data['articles']);
        if(!$wx_media_id){
            $this->meret(NULL,MERET_APIERROR);
            exit;
        }
        $status = $this->media->update_articles_media($media_id,$wx_media_id);
        if($status){
            $this->meret(NULL,MERET_OK);
        }else{
            $this->meret(NULL,MERET_EMPTY);
        }
    }

    /**
     * 功能：拼接单图文组成多图文
     */
    public function upload_wx_newss($articles){
        $array = $this ->media ->get_news_info($articles);
//        echo '<pre>';
//        print_r($array);
        // 上传图文 微信接口获取数据
        $news_media_id = $this ->wxapi ->wx_upload_news($array);
//        print_r($news_media_id);
        if(isset($news_media_id['errcode'])){
            // 上传失败
            return false;
        }
        return $news_media_id['media_id'];
    }

}

