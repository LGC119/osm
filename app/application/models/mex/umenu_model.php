<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Umenu_model extends ME_Model
{

    public function __construct(){
        parent::__construct();
        $this ->load ->library('Wxapi');
//        $this ->load ->model('mex/media_model','media');
    }

    public function select_umenu(){

//        $sql = "SELECT id,type,name,`key`,url,concat(path,id) as pathid FROM ".$this ->db ->dbprefix('wx_umenu')."
//                ORDER BY pathid ASC";
        $sql = "SELECT info FROM ".$this ->db ->dbprefix('wx_umenu');
        $data = $this ->db ->query($sql)->result_array();
        return $data[0]['info'];
    }

    // 创建菜单
    public function create_umenu($menuJson){
//        $menuJson = json_encode($menuJson);
//        return $menuJson;
        return $this ->wxapi ->wx_create_menu($menuJson);
    }


    // 获取key 与media id
    public function get_key_medias($wx_aid){
        $sql = 'SELECT `key`,medias FROM '.$this ->db ->dbprefix('wx_umenu').'
                    WHERE wx_aid='.$wx_aid;
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }

    // 通过memdia id获取数据
    public function get_media($keymedia){
        if($keymedia){
        $sql = 'SELECT media.id mediaid,media.type mediatype,media.created_at,media.updated_at,media.wx_media_id,filename,mediadata.* FROM '.$this ->db ->dbprefix('media').' AS media
                    LEFT JOIN '.$this ->db ->dbprefix('media_data').' AS mediadata
                        ON media.id=mediadata.mid
                    WHERE media.id in('.$keymedia.')';
        return $this ->db ->query($sql) ->result_array();
        }else{
            return '';
        }
    }

    // 更新规则
    public function save_rule($key,$medias,$wx_aid,$menu_name){
        $sql = "SELECT id FROM ".$this ->db ->dbprefix('wx_umenu')."
                    WHERE `key`='$key' AND wx_aid='$wx_aid'";
        $status = $this ->db ->query($sql) ->result_array();
        $data['medias'] = $medias;
        $data['name'] = $menu_name;
        $data['type'] = 'click';
        if(!$status){
            $data1['key'] = $key;
            $data1['wx_aid'] = $wx_aid;
            $data1['medias'] = $medias;
            $data1['name'] = $menu_name;
            $data1['type'] = 'click';
            $return = $this ->db ->insert('wx_umenu',$data1);
        }else{
            $return = $this ->db ->update('wx_umenu',$data,array('wx_aid'=>$wx_aid,'key'=>$key));
        }
        return $return;
    }

    // 删除规则
    public function delete_rule($menuKey,$wx_aid){
        return $this ->db ->delete('wx_umenu',array('wx_aid'=>$wx_aid,'key'=>$menuKey));
    }

    // 点击菜单时
    public function menu_click($eventkey,$wx_aid){
        $sql = "SELECT medias FROM ".$this ->db ->dbprefix('wx_umenu')."
                    WHERE `key`='$eventkey' AND wx_aid='$wx_aid'
                    LIMIT 1";
        $data = $this ->db ->query($sql) ->result_array();
        return isset($data[0]) ? $data[0] : '';
    }

    // 通过media id查找内容
    public function get_media_info($media_id,$wx_aid){
        $sql = "SELECT m1.id,m2.content,m1.type,m1.updated_at,m1.wx_media_id,m2.title,m2.digest,m1.filename,m2.content_source_url FROM ".$this ->db ->dbprefix('media')." AS m1
                LEFT JOIN ".$this ->db ->dbprefix('media_data')." AS m2
                ON m1.id=m2.mid
                WHERE m1.id=".$media_id;
        $data = $this ->db ->query($sql) ->result_array();
        // 如果过期了，则重新上传
        if(isset($data[0]['type']) && ($data[0]['type']=='image' || $data[0]['type']=='voice') && ((time()+100) > strtotime($data[0]['updated_at']))){
            $urlType = $data[0]['type'] == 'image' ? 'images' : 'voice';
            $wx_data = $this ->wxapi ->wx_upload_file($data[0]['type'],'uploads/'.$urlType.'/'.$data[0]['filename'],$wx_aid);
            if(!isset($wx_data['errcode'])){
                $upMedia['updated_at'] = date('Y-m-d H:i:s',$wx_data['created_at']+3*3600*24);
                $data[0]['wx_media_id'] = $upMedia['wx_media_id'] = $wx_data['media_id'];
                $this ->db ->update('media',$upMedia,array('id'=>$data[0]['id']));
            }
        }
        return isset($data[0]) ? $data[0] : '';
    }



}
