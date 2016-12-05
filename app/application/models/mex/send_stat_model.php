<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */
class Send_stat_model extends ME_Model{

    // 地区访问量
    public function get_area(){
        $send_id = $this->input->post('send_id');
        $sql = "SELECT sum(num) num,area FROM ".$this->db->dbprefix('send_stat')."
                    WHERE send_id='$send_id'
                    GROUP BY area";
        $data = $this->db->query($sql)->result_array();
//        echo $this->db->last_query();
        return $data;
    }

    // 访问量中的性别比例
    public function get_sex(){
        $send_id = $this->input->post('send_id');
        $sql = "SELECT sum(num) num,sex FROM ".$this->db->dbprefix('send_stat')."
                    WHERE send_id='$send_id'
                    GROUP BY sex";
        $data = $this->db->query($sql)->result_array();
        return $data;
    }

    // 不同时间段的访问量
    public function get_time(){
        $send_id = $this->input->post('send_id');
        $sql = "SELECT sum(num) num,created_at FROM ".$this->db->dbprefix('send_stat')."
                    WHERE send_id='$send_id'
                    GROUP BY created_at";
        $data = $this->db->query($sql)->result_array();
        return $data;
    }

} 