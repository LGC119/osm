<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Group extends ME_Controller
{
    private $wx_aid;
    private $company_id;
    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->company_id = $this->session->userdata('company_id');
        $this ->load ->model('mex/group_model','group');
        $this->load->model('mex/user_model','user_model');
    }

	public function index(){
        $this ->group ->groupid_to_name();
    }

    /**
     * 功能：添加分组
     * 参数：
     * name   组名
     * description  描述
     * feature      功能
     */
    public function insert_group(){
        $postData = $this ->input ->post();
        // print_r($postData);
        $data['wx_aid'] = $this ->wx_aid;
        $data['company_id'] = $this ->company_id;
        $data['name']   = $postData['name'];
        $data['description']  = $postData['description'];
        $data['filter_param']  = $postData['filter_param'];
        $data['feature']  = $postData['feature'];
        $data['created_at'] = date("Y-m-d H:i:s",time());
        $data['expires_in'] = $postData['expires_in'] == '2037-08-08' ? '0000-00-00' : $postData['expires_in'];
        $data['members_count']  = intval($postData['members_count']);
        // 数据进数据库
        $group_id = $this ->group ->insert_group($data);
        if (isset($postData['ids'])) {
            $ids_arr = $postData['ids'];
            $status = $this->user_model->user_in_group($ids_arr,$group_id);
        }
        if($group_id){
            $this ->meret(NULL,MERET_OK,'添加成功！');
        }else{
            $this ->meret(NULL,MERET_DBERR,'添加失败');
        }
    }

    // 数据库->分组修改
    public function edit_group(){
        $id  = $this ->input ->post('id');
        $name= $this ->input ->post('name');
        $status = $this ->group ->edit_group($id,$name,$this ->wx_aid);
        if($status){
            $this ->meret(NULL,MERET_OK,'修改成功！');
        }else{
            $this ->meret(NULL,MERET_DBERR,'修改失败！');
        }
    }

    /**
     * 功能：数据库中获取所有用户组
     * 参数：
     */
    public function select_groups(){
        $search['keyword'] = $this ->input ->get('keyword');
        $search['status'] = $this->input->get('status');
        $search['arrange'] = $this->input->get('arrange');
        if ($search['arrange']==1) {
            $search['arrange'] = 'expires_in desc';
        }else{  
            $search['arrange'] = 'expires_in asc';
        }
        $data = $this ->group ->select_groups($this ->wx_aid, $search);
        if(!$data){
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        $this ->meret($data,MERET_OK,'读取成功！');
    }

    //按id获取group
    public function select_group_by_id(){
        $id = $this->input->get('id');
        $data = $this->group->select_group_by_id($id);
        if (!$data[0]) {
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        $this ->meret($data[0],MERET_OK,'读取成功！');
    }

    // 获取所有组，不分页，用于用粉丝管理界面的组选择（目前先只查可用组）
    public function get_all_groups()
    {
        // 传标志位，只查可用组
        $data = $this->group->select_groups(1);
        if(!$data)
        {
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        $this ->meret($data,MERET_OK,'读取成功！');
    }

    //获取用户组统计数据
    public function get_group_statistics(){
        $group_id = $this->input->get('group_id');
        $data = $this->user_model->get_user_data_statistics($group_id);
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                if (isset($data_statistics[$k][$v])) {
                    $data_statistics[$k][$v]++;
                }else{
                    $data_statistics[$k][$v] = 1;
                }
            }
        }
        if(!$data){
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        $this ->meret($data_statistics,MERET_OK,'读取成功！');

    }


}

