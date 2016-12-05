<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class User extends ME_Controller
{
    private $wx_aid;
    public function __construct(){

        parent::__construct();
//        var_dump($_SESSION);
        $this ->wx_aid = $this->session->userdata('wx_aid');
        $this ->load ->model('mex/user_model','user');
    }

	public function index(){
//        var_dump($_SESSION);
//        $this ->user ->insert_user_all();
//        $data = $this ->user ->get_verified();
//        var_dump($data);
//        $access_token = $this ->media ->get_token($this ->wx_aid);
//        $data = $this ->user ->get_userinfo($access_token,'oyQlQuKfLUIW0RJbRq11sg21hr8Q,oyQlQuJ1UPbAqW7MFIxTFkhW6bhk',$this ->wx_aid);
//        echo "<pre>";
//        print_r($data);
	}

    /**
     * 功能：展示用户列表信息
     * 参数：
     * 【可选】nickname   用户昵称
     * 【可选】province   省
     * 【可选】city       城市
     * 【可选】sex        性别
     */
    public function select_user()
    {
        $search['nickname'] = $this ->input ->get('nickname');
        $search['country'] = $this ->input ->get('country');
        $search['province'] = $this ->input ->get('province');
        $search['city'] = $this ->input ->get('city');
        $search['sex'] = $this ->input ->get('sex');
        $search['tags'] = $this->input->get('tags');
        $search['group_send'] = $this->input->get('group_send');
        $search['group_id'] = $this->input->get('group_id');
        $search['wx_aid'] = $this->wx_aid;
        $search['send_id'] = $this->input->get('sendId');
        $search['subscribe_start'] = $this->input->get('sub_start');
        $search['subscribe_end'] = $this->input->get('sub_end');
        $search['communication_start'] = $this->input->get('comm_start');
        $search['communication_end'] = $this->input->get('comm_end');
        //$search['no_communication'] = $this->input->get('no_communication');
        //echo '<pre>';
        //print_r($search);
        $data = $this ->user ->select_user($search);
        
        if(empty($data['users']))
        {
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        // 清除nickname为空的信息
        /*foreach($data['users'] as $k =>$v)
        {
            if(!$v['nickname'])
            {
                $data['users'][$k] = '';
                unset($data['users'][$k]);
            }
        }*/
        $this ->meret($data,MERET_OK,'读取成功！');
    }

    /**
     * 功能：单个用户详细信息
     * 参数：
     * openid  用户的openid
     */
    public function select_user_info(){
        $id = $this ->input ->get('id');
        $data = $this ->user ->select_user_info($id);
        if(!$data){
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');
            exit;
        }
        $this ->meret($data,MERET_OK,'读取成功！');
    }

    /**
     * 功能：用户入组
     * 参数：
     * id       用户id
     * group_id 用户组id
     */
    public function user_in_group(){
        $user_ids = $this ->input ->post('user_ids');
        $group_id = $this ->input ->post('group_id');
        $status = $this ->user ->user_in_group($user_ids,$group_id);
        if($status)
            $this ->meret(NULL,MERET_OK,'操作成功！');
        else
            $this ->meret(NULL,MERET_OTHER,'操作失败！');
    }

    /**
     * 功能：从微信接口获取用户所有信息入库
     */
    public function insert_user_all(){
        $this ->user ->insert_user_all($this ->wx_aid);
    }

    /**
     * 功能：获取符合条件的用户的Openid 供群发使用
     */
    public function get_user_openid(){
        $param['wx_aid'] = $this ->wx_aid;
        $param['count'] = $this ->input ->post('count');
        $param['group'] = $this ->input ->post('group');
        $param['sex'] = $this ->input ->post('sex');
        $param['country'] = $this ->input ->post('country');
        $param['province'] = $this ->input ->post('province');
        $param['city'] = $this ->input ->post('city');
        $param['send_num'] = $this ->input ->post('send_num');
        $data = $this ->user ->get_user_openid($param);
//        echo $data;exit;
        // 获取选中个数
        if(is_int($data)){
            echo $data;
            exit;
        }
        if($data['data']){
            $openidStr = '';
            foreach($data['data'] as $v){
                $openidStr .= '"'.$v['openid'].'",';
            }
            $openidStr = rtrim($openidStr,',');
            if($data['status'] == 'all'){
                $newData['status'] = 'all';
            }else{
                $newData['status'] = 'port';
            }
            $newData['data'] = $openidStr;
            echo json_encode($newData);
//            echo $openidStr;
        }else{
            echo '';
        }
    }

    // 获取符合条件的用户的名字
    public function get_user_info(){
        $param['wx_aid'] = $this ->wx_aid;
        $param['count'] = $this ->input ->post('count');
        $param['group'] = $this ->input ->post('group');
        $param['sex'] = $this ->input ->post('sex');
        $param['country'] = $this ->input ->post('country');
        $param['province'] = $this ->input ->post('province');
        $param['city'] = $this ->input ->post('city');
        $param['send_num'] = $this ->input ->post('send_num');
        $data = $this ->user ->get_user_openid($param);
        $this ->meret($data['data']);
    }

    // 获取粉丝标签
    public function get_user_tag(){
        $data['topNum'] = $this ->input ->get('topNum');
        $data['group_id'] = $this ->input ->get('group_id');
        $data['openid'] = $this ->input ->get('openid');
        $datalist = $this ->tag ->get_user_tag($data);
        if($datalist)
            $this ->meret($datalist,MERET_OK,'读取成功！');
        else
            $this ->meret(NULL,MERET_EMPTY,'数据为空！');

    }
    // 粉丝标记标签
    public function user_mark(){
        $data['wx_aid'] = $this ->wx_aid;
        $data['wx_user_id'] = $this ->input ->post('wx_user_id');
        $data['tag_id'] = $this ->input ->post('tag_id');
        $status = $this ->user ->user_mark($data);
        if($status)
            $this ->meret(NULL,MERET_OK,'更新成功！');
        else
            $this ->meret(NULL,MERET_OTHER,'更新失败');
    }

}

