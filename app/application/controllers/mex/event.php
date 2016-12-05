<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Event extends ME_Controller
{
    private $wx_aid;
    private $staff_id;
    private $company_id;
    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->staff_id = $this->session->userdata('staff_id');;
        $this ->company_id = $this->session->userdata('company_id');;
        $this ->load ->model('mex/event_model','event');
    }

    // 创建活动
    public function create_event(){
        $data['company_id'] = $data2['company_id'] = $this ->company_id;
        $data['staff_id'] = $this ->staff_id;
        $data['aid'] = $this ->wx_aid;
        $data['event_title'] = $this ->input ->post('event_title');
        $data['detail'] = $this ->input ->post('detail');
        $data['start_time'] = $this ->input ->post('start_time');
        $data['end_time'] = $this ->input ->post('end_time');
        $data['from'] = 1;
        $data['type'] = $this ->input ->post('type');
        $data['trade'] = $this ->input ->post('trade');
        $data2['rule_id'] = $this ->input ->post('rule_id');
        $data2['start_time'] = $this ->input ->post('send_time');
        $data3['tag_id'] = $this ->input ->post('tag_id');
        $data3['group_id'] = $this ->input ->post('group_id');
        $status = $this ->event ->create_event($data,$data2,$data3);
        if($status)
            $this ->meret('',200,'创建成功！');
        else
            $this ->meret('',508,'创建失败！');
    }

    // 活动列表
    public function select_event(){
        $data2['wx_aid'] = $this ->wx_aid;
        $data2['name'] = $this ->input ->post('name');
        $data2['tag_id'] = $this ->input ->post('tag_id');
        $data2['type'] = $this ->input ->post('type');
        $data2['trade'] = $this ->input ->post('trade');
        $data2['status'] = $this ->input ->post('status');
        $data = $this ->event ->select_event($data2);
        if($data)
            $this ->meret($data,200,'读取成功！');
        else
            $this ->meret('',204,'数据为空！');
    }

    // 活动详情
    public function select_event_info(){
        $id = $this ->input ->get('id');
        $data = $this ->event ->select_event_info($id);
        if($data)
            $this ->meret($data,200,'读取成功！');
        else
            $this ->meret('',204,'数据为空！');
    }
}

