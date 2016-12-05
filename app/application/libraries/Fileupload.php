<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class CI_Upload
 */
class CI_Fileupload{
    /**
     * 功能：上传文件
     * 参数：
     */
    public function __construct(){

//        $this->db = db('default');
        $this->ci = &get_instance();
        $this->ci->load->helper('url');
    }

    /**
     * 功能：上传图片
     * 参数：
     * $metype     上传到Uploads下的哪个目录  默认：mex    微信mex  微博meo
     */
    public function upload_image_local($size = '200')
    {
        $filename = date('Y-m-d').uniqid();
        $sDate = date('Ym');
        $path_dir = '../uploads/images/'.$sDate.'/';
        if (!is_dir($path_dir)){
            mkdir($path_dir,0777,true);
        }
//        var_dump($_FILES);exit;
        $config['upload_path']=$path_dir;
        $config['allowed_types']="gif|jpg|jpeg|png|bmp";			// 允许的文件格式
        $config['max_size']=$size;                     // 文件的大小 200KB以内
        $field_name = 'Filedata';
        $config['file_name'] = $filename;
        $this->ci->load->library("upload", $config);
//        var_dump($config);
//        var_dump($this ->ci ->upload->do_upload($field_name));exit;
        if ($this ->ci ->upload->do_upload($field_name)){
            $data=$this ->ci ->upload->data();
            $oldpic=$data['full_path'];
            $newpic=date('YmdHis').uniqid().$data['file_ext'];
            $config['source_image'] = $oldpic;
            $config['maintain_ratio'] = TRUE;
            $config['new_image'] =$newpic;
            $config['quality'] ='80%';
            // $config['width'] = 640;
            // $config['height'] = 320;
            $this ->ci ->load ->library('image_lib', $config);
              // 生成缩略图
            if ( $this->ci ->image_lib->resize() ) {
                chmod($oldpic,0666);
                unlink($oldpic);	//删除原图片
                $filename = $sDate.'/'.$newpic;
            }
//            $size=$this->swith_unit($data['file_size']);
            return array('msg'=>'success','filename'=>$filename,'type'=>'image');
        }else{
            return array('msg'=>'error');
        }
    }

    /**
     * 功能：上传语音
     * 参数：
     * return
     */
    function upload_voice_local($size='258'){
        $file_name = date('Y-m-d').uniqid();
        $sDate = date('Ym');
        $path_dir = '../uploads/voice/'.$sDate.'/';

        $config['upload_path']=$path_dir;
        if(!is_dir($path_dir)){
            mkdir($path_dir,0777,true);
        }
        $config['allowed_types']="mp3|amr|mp4";
        $config['max_size']=$size; // 258KB
        $field_name = 'Filedata';
        $config['file_name'] = $file_name;
        $this->ci->load->library("upload",$config);
        if($this ->ci ->upload ->do_upload($field_name)){
            $file=$this ->ci ->upload ->data();
            $data['msg'] = 'success';
            $data['filename']=$sDate.'/'.$file['orig_name'];
        }else{
            $data['msg']='error';
        }
        return $data;
    }

    // KB转MB
    public function swith_unit($size){
        $units = array('KB','MB','GB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
            return round($size, 2).$units[$i];
    }

}