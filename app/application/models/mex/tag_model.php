<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Tag_model extends ME_Model
{

    public function __construct(){
        parent::__construct();
        $this ->load ->library('wxapi');
        $this ->load ->model('mex/user_model','user');
    }

    /**
     * ===============================================
     * 粉丝标签模块
     * 1、获取 2、标记 3、取消标记 4、更新标签
     * ===============================================
     */
    // 获取粉丝标签
    public function get_user_tag($data){
        $wx_aid = $data['wx_aid'];
        $where =" wx_aid='$wx_aid'";
        if($data['group_id']){
            $groupid = $data['group_id'];
            $where .=" AND group_id='$groupid'";
        }
        if($data['openid']){
            $openid = $data['openid'];
            $where .=" AND openid='$openid'";
        }
        if($data['topNum']){
            $where .=' limit '.$data['topNum'];
        }
        $sql = 'SELECT wx_user_id,openid,tag_id,tagid_to_name,wx_aid,link_tag_hits,rule_tag_hits,manual_tag_hits,event_tag_hits
                FROM '.$this ->db ->dbprefix('rl_wx_user_tag').'
                '.$where;
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }
    // 粉丝标记标签
    public function user_mark($data){
        $data1['wx_aid'] = $data['wx_aid'];
        $data1['wx_user_id'] = $data['wx_user_id'];
        // 根据openid  获取wx_user_id group_id group_name
        $userData = $this ->user ->select_user_info($data['wx_user_id']);
        $data1['openid'] = $userData['openid'];
        $data1['user_name'] = $userData['nickname'];
        $data1['tag_id'] = $data['tag_id'];
        // 根据tag_id 获取到tag_name
        if(strpos($data['tag_id'],',')){
            $tagData = explode(',',$data['tag_id']);
            $data1['tagid_to_name'] = '{';
            foreach($tagData as $tagV){
                $data1['tagid_to_name'] .= '"'.$tagV.'":"'.$this ->tagid_to_name($tagV).'",';
            }
            $data1['tagid_to_name'] = rtrim($data1['tagid_to_name'],',');
            $data1['tagid_to_name'] .= '}';
        }else{
            $data1['tag_name'] = $this ->tagid_to_name($data1['tag_id']);
        }
        // 查询是否存在，该用户标签信息
        $sql = 'SELECT count(1) AS num FROM '.$this ->db ->dbprefix('rl_wx_user_tag').'
                WHERE wx_user_id="'.$data['wx_user_id'].'"';
        $status = $this ->db ->query($sql) ->result_array();
        if($status['num'] != 0)
            // 更新
            return $this ->db ->update('rl_wx_user_tag',$data1,array('wx_user_id'=>$data['wx_user_id']));
        else
            // 添加
            return $this ->db ->insert('rl_wx_user_tag',$data1);
    }
    // 粉丝取消标记标签
    public function user_unmark($wx_user_id){
        return $this ->db ->delete('rl_wx_user_tag',array('wx_user_id'=>$wx_user_id));
    }
    // 粉丝更新标记标签
//    public function user_updatemark($data){
//        $data1['wx_aid'] = $data['wx_aid'];
//        $data1['wx_user_id'] = $data['wx_user_id'];
//        // 根据openid  获取wx_user_id group_id group_name
//        $userData = $this ->user ->select_user_info($data['wx_user_id']);
//        $data1['openid'] = $userData['openid'];
//        $data1['user_name'] = $userData['nickname'];
//        $data1['tag_id'] = $data['tag_id'];
//        // 根据tag_id 获取到tag_name
//        if(strpos($data['tag_id'],',')){
//            $tagData = explode(',',$data['tag_id']);
//            $data1['tagid_to_name'] = '{';
//            foreach($tagData as $tagV){
//                $data1['tagid_to_name'] .= '"'.$tagV.'":"'.$this ->tagid_to_name($tagV).'",';
//            }
//            $data1['tagid_to_name'] = rtrim($data1['tagid_to_name'],',');
//            $data1['tagid_to_name'] .= '}';
//        }else{
//            $data1['tag_name'] = $this ->tagid_to_name($data1['tag_id']);
//        }
//        $this ->db ->update('rl_wx_user_tag',$data1,array('wx_user_id'));
////        return $this ->db ->insert('rl_wx_user_tag',$data1);
////        $wx_user_id,$tag_id,$wx
//    }

    // 标签id对应名称
    public function tagid_to_name(){
        $sql = 'SELECT id,tag_name FROM '.$this ->db ->dbprefix('tag').'';
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        foreach($data as $v){
            $newData[$v['id']] = $v['tag_name'];
        }
        return $newData;
    }


} 