<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Send_stat extends ME_Controller
{

    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->staff_id = $this->session->userdata('staff_id');
        $this ->company_id = $this->session->userdata('company_id');
        $this ->load ->model('mex/send_stat_model','send_stat');
    }

    // 地区访问量
    public function get_area(){
        $data = $this->send_stat->get_area();
        $this->meret($data,MERET_OK);
    }

    // 访问量中的性别比例
    public function get_sex(){
        $data = $this->send_stat->get_sex();
        $this->meret($data,MERET_OK);
    }

    // 不同时间段的访问量
    public function get_time(){
        $data = $this->send_stat->get_time();
        $this->meret($data,MERET_OK);
    }
}