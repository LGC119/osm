<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
class User_model extends CI_Model{



    // 通过Openid读取id
    public function get_wx_user_id($openid=0){
        $sql = "SELECT id FROM ".$this->db->dbprefix('wx_user')." WHERE openid='$openid'";
        $data = $this->db->query($sql)->result_array();
        return isset($data[0]['id']) ? (int)$data[0]['id'] : 0;
    }
    // 将注册的姓名与手机号 添加到me_user表中 返回user_id
    public function insert_me_user($data){
        $this->db->insert('me_user',$data);
        return $this->db->insert_id();
    }
    // 将user_id与wx_user_id存进me_user_sns_relation表中
    public function insert_user_sns($user_id,$wx_user_id){
        $data['user_id'] = $user_id;
        $data['wx_user_id'] = $wx_user_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('user_sns_relation',$data);
    }
    // 是否已注册
    public function isRegister($openid){
        $wx_user_id = $this->get_wx_user_id($openid);
        $status = $this->db->select('id')->where('wx_user_id',$wx_user_id)->get('user_sns_relation')->row_array();
        return $status;
    }

} 