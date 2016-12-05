<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 欧诗漫用户、积分相关操作
*/
class Osm extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Osm_api');
        $this->load->helper('url');
        $this->load->model('osm_model', 'model');
        // $this->session->unset_userdata('access_token');
        $this->openId = $this->session->userdata('openId') ? $this->session->userdata('openId') : $this->getOpenId()['openid'];
        // $this->openId = 'obn_fjvfq5qjFEkBwszVTFV9vSOA';
        // $this->wx_aid = 4;
        $this->wx_aid = $this->session->userdata('wx_aid') ? $this->session->userdata('wx_aid') : $this->getOpenId()['wx_aid'];
        // $this->cid = 0;
    }

    //会员注册/绑定页面
    public function reg_bind() {
        $is_member = array();
        $openid_arr = array('openId'=>$this->openId);
        $is_member = $this->osm_api->get_member_info_by_openid($openid_arr);
        if (!isset($is_member['error'])) {
            $this->session->set_userdata(array('msg' => "<p>您的微信账号已经绑定会员！</p>"));
            header('location:success');
            exit();
        }
        $data['id'] = $this->input->get_post('id');
        $data['code'] = $this->input->get_post('code');
        $this->load->view('osm/reg_bind',$data); 
    }

    // 注册页面
    public function register() {
        // 判断是否是会员

        // 获取 openid
        $data['openid'] = !empty($this->openId) ? $this->openId : '';
        // 生成indexcode
        $data['indexcode'] = $this->_create_indexcode();
        // 显示会员注册页面
        $this->load->view('osm/register', $data);
    }

    // 注册动作
    public function do_register() {
        $user_info = $this->input->post();
        $user_info['indexcode'] = (int)$user_info['indexcode'];
        unset($user_info['submit']);
        $rst = $this->osm_api->register($user_info);
         //var_dump($rst);
         //exit;
        if (isset($rst->Obj)) {
            $member_id = $rst->Obj;
            $this->session->set_userdata(array('msg' => '<p>注册成功！您的会员卡号为：' . $rst->Obj . '</p>' . 
                           '<p>您的会员也与当前微信号绑定成功。</p>'));
            $rst = $this->model->update_is_member_by_openid($this->openId,$member_id,1);
            if (!$rst) {
                 $this->session->set_userdata(array('msg' => '<p>注册成功！您的会员卡号为：' . $rst->Obj . '</p>' . 
                           '<p>您的会员与当前微信号绑定失败。</p>'));
            }
            header('location:success');
        } else {
            $err_msg = array('1' => '接口认证失败！',
                             '2' => '验证码错误或超时，请在十分钟内填写获取到的验证码。',
                             '3' => '当前微信账号已经绑定了会员。',
                             '4' => '您已经为线下会员，请直接绑定',
                             '5' => '生成会员卡号失败，请联系客服',
                             '6' => '重复提交数据，请刷新注册页面后重新注册。',
                             '7' => '没有传入微信信息!'
                         );
            $this->session->set_userdata(array('msg' => "<p>注册失败！</p><p>{$err_msg[$rst->MSG]}</p>"));
            header('location:error');
        }
    }

    // 成功页面
    public function success() {
        $data['msg'] = $this->session->userdata('msg');
        $this->load->view('osm/success', $data);
    }

    // 错误页面
    public function error() {
        $data['msg'] = $this->session->userdata('msg');
        $this->load->view('osm/error', $data);
    }

    // 发送手机验证码
    public function send_check_code() {
        $rst = $this->osm_api->send_check_code($this->input->post());
        echo $rst->CreateCheckCodeResult;
    }

    // 获取手机验证码
    public function get_check_code() {
        $rst = $this->osm_api->get_check_code($this->input->get());
        echo '<pre>';
        // var_dump($rst);
        exit;
    }

    // 微信绑定页面
    public function bind_wx() {
        // 获取 openid
        $data['openid'] = !empty($this->openId) ? $this->openId : '';
        // 生成indexcode
        $data['indexcode'] = $this->_create_indexcode();
        // 显示会员绑定微信页面
        $this->load->view('osm/bind_wx', $data);
    }

    // 用户绑定微信
    public function do_bind_wx() {
        $user_info = $this->input->post();
        $user_info['indexcode'] = (int)$user_info['indexcode'];
        unset($user_info['submit']);
        $rst = $this->osm_api->bind_wx($user_info);
        //var_dump($rst->BindMemberResult->MSG);
        //return $rst;
        if (isset($rst->BindMemberResult->Obj)) {
            $member_id = $rst->BindMemberResult->Obj;
            $this->session->set_userdata(array('msg' => '<p>绑定成功！</p>'));
            $rst = $this->model->update_is_member_by_openid($this->openId,$member_id,2);
            if (!$rst) {
                $this->session->set_userdata(array('msg' => '<p>绑定成功！' . $rst->Obj . '</p>' . 
                           '<p>您的会员与当前微信号绑定失败。</p>'));
            }
            header('location:success');
        } else {
            $err_msg = array('1' => '用户名或密码不正确！',
                             '2' => '校验码不正确或已超时！',
                             '3' => '当前微信账号已经绑定了会员！',
                             '4' => '<a href="register">对不起，您还不是会员，请先注册！</a>',
                             '5' => '重复提交数据，请刷新绑定页面后重新绑定！',
                             '6' => '生成会员卡号失败，请联系客服');
            $this->session->set_userdata(array('msg' => "<p>绑定失败！</p><p>{$err_msg[$rst->BindMemberResult->MSG]}</p>"));
            header('location:error');
        }

    }

    // 获取会员信息
    private function is_member($openid) {
        $openid_arr = array('openId'=>$openid);
        $rst = $this->osm_api->get_member_info_by_openid($openid_arr);
        if (isset($rst['error'])) {
            if ($rst['error'] == 3) {
                header('location:reg_bind');
                exit();
            }else{
                $err_msg = array('1' => '用户名或密码不对。',
                                 '2' => '系统错误，请重新查询。');
                $this->session->set_userdata(array('msg' => "<p>查询失败！</p><p>".$err_msg[$rst['error']]."</p>"));
                header('location:error');
                exit();
            }
        }else{
            return true;
        }
    }

    // 会员订阅
    public function get_subscription() {
        //获取company_id
        $cid = $this->model->get_cid_by_wx_aid($this->wx_aid);
        $data['openId'] = !empty($this->openId) ? $this->openId : '';
        // 获取indexcode
        $data['indexcode'] = $this->_create_indexcode();

        $mark = $this->is_member($data['openId']);
        
        $data['sub'] = $this->model->get_all_tags_sub($cid);
        $data['taged'] = $this->model->get_tags_sub_by_openid($cid,$this->openId);
        // var_dump($data['taged']);
        // exit();
        $this->load->view('osm/subscript_list',$data);
    }

    //确认订阅
    public function do_subscription() {
        $groups = array();
        $cid = $this->model->get_cid_by_wx_aid($this->wx_aid);
        $post = $this->input->post();
        if (isset($post['groups'])) {
            $groups = $post['groups'];
            unset($post['groups']);
        }
        $user_info = array();
        $user_info = $this->model->get_user_info($this->openId);
        // var_dump($this->db->last_query());
        // exit();
        if (empty($user_info)) {
            $err_msg = "获取用户信息失败！";
            $this->session->set_userdata(array('msg'=>"<p>{$err_msg}</p>"));
            header('location:error');
            exit();
        }
        //获取当前用户已经订阅的标签
        $taged = array();
        $tag_id_new = array();
        $tag_id_old = array();
        $taged = $this->model->get_tags_sub_by_openid($cid,$this->openId);
        foreach ($taged as $tag_id) {
            $tag_id_old[] = $tag_id['tag_id'];
        }
        //如果是取消订阅就传递 SubscriptCannal
        if(!empty($groups)){
            //把订阅内容用'|'分隔开，形成接口需要格式
            $post['subscribeType'] = implode('|',$groups);
        }else{
            $post['subscribeType'] = 'SubscriptCannal';
        }
        $rst = $this->osm_api->MemberSubscript($post);
        if(isset($rst->MemberSubscriptResult->Obj)){
            //重新订阅
            $delete_rst = $this->model->delete_tags($this->openId);
            // if (!$delete_rst) {
            //     $err_msg = "订阅信息更新失败！";
            //     $this->session->set_userdata(array('msg'=>"<p>{$err_msg}</p>"));
            //     header('location:error');
            //     exit();
            // }

            foreach ($groups as $tag_name) {
                $rl['tag_id'] = $this->model->get_id_by_tag_name($tag_name);
                $tag_id_new[] = $rl['tag_id'];
                $rl['openid'] = $this->openId;
                $rl['company_id'] = $cid;
                $rl['wx_user_id'] = $user_info['id'];
                $rl['wx_aid'] = $this->wx_aid;
                $rl_user_tag_rst = $this->model->insert_rl_tag_user($rl);
                if (!$rl_user_tag_rst) {
                    $err_msg = $tag_name."订阅信息存储失败！";
                    $this->session->set_userdata(array('msg'=>"<p>{$err_msg}</p>"));
                    header('location:error');
                    exit();
                }
            }
            if (count($tag_id_new) == count($tag_id_old) && empty(array_diff($tag_id_new, $tag_id_old))) {
                $this->session->set_userdata(array('msg'=>'<p>您没有变更任何信息！</p>'));
                header('location:error');
                exit();
            }
            if (empty($taged) && $post['subscribeType'] != 'SubscriptCannal') {
                $this->session->set_userdata(array('msg'=>'<p>感谢您的订阅，</p><p>您成功订阅的内容为：'.$post['subscribeType'].'。我们将在30天后赠送积分，期间取消订阅后将不予赠送，并且积分只赠送一次，全部取消后重新订阅将不重复赠送！</p>'));
                header('location:success');
                exit();
            }
            if ($post['subscribeType'] == 'SubscriptCannal') {
                $this->session->set_userdata(array('msg'=>'<p>你成功取消了所有订阅！</p>'));
                header('location:success');
                exit();
            }
            $this->session->set_userdata(array('msg'=>'<p>订阅成功</p><p>您成功订阅的内容为：'.$post['subscribeType'].'</p>'));
            header('location:success');
            exit();
        } else {
            $err_msg = array(
                '1'   => '用户名或密码不正确！',
                '2'   => '微信传输信息不正确！',
                '3'   => '<a href="reg_bind">没有注册或绑定,请先注册或绑定！</a>',
                '4'   => '请不要重复提交数据！',
                '5'   => '没有选择类型！',
                '6'   => '您没有订阅任何内容',
                '-10' => '数据回滚！',
                '-11' => '数据回滚！',
                '-16' => '数据回滚！'
            );
            $this->session->set_userdata(array('msg'=>"<p>操作失败！</p><p>{$err_msg[$rst->MemberSubscriptResult->MSG]}</p>"));
            header('location:error');
        }
    }

    // 获取会员信息
    public function get_member_info_by_openid() {
        // 获取 openid
        $user_info = $this->input->get();
        $rst = $this->osm_api->get_member_info_by_openid($user_info);
        $rst = $this->osm_api->MemberSubscript($user_info);
        return $rst;
        // var_dump($rst);
        // exit;
    }

    // 获取会员积分
    public function get_member_points_by_openid() {
        echo '<pre>';
        $this->is_member($this->openId);//判断用户是否绑定微信。
        $rst = $this->osm_api->get_member_points_by_openid(array('openId'=>$this->openId));
        // var_dump($rst->GetMemberPointsResult->MSG);
        if (isset($rst->GetMemberPointsResult->Obj)) {
            $this->session->set_userdata(array('msg' => '<p>您的会员积分为：' . $rst->GetMemberPointsResult->Obj . '</p>' ));
            header('location:success');
        }else{
            $err_msg = array('1' => '用户名或密码不对。',
                             '2' => '系统错误，请重新查询。',
                             '3' => '没有注册或绑定。');
            $this->session->set_userdata(array('msg' => "<p>查询失败！</p><p>".$err_msg[$rst->GetMemberPointsResult->MSG]."</p>"));
            header('location:error');
        }
    }

    /**
     * 返回数组的维度
     * @param  [type] $arr [description]
     * @return [type]      [description]
     */
    function arrayLevel($arr){
        $al = array(0);
        function aL($arr,&$al,$level=0){
            if(is_array($arr)){
                $level++;
                $al[] = $level;
                foreach($arr as $v){
                    aL($v,$al,$level);
                }
            }
        }
    aL($arr,$al);
    return max($al);
    } 

    // 获取积分兑换礼品列表
    public function get_points_gift() {
        $inExchangeDate = $this->osm_api->get_exchange_date();
        if (!isset($inExchangeDate[0]['curtPreStartDate']) || time() > strtotime($inExchangeDate[0]['curPreEnddate'])) {
            if (isset($inExchangeDate[0]['nextPreStartDate'])) {
                $rst['not_time'] = '不在预约期内，下次预约期为' .date('Y-m-d H:i:s', strtotime($inExchangeDate[0]['nextPreStartDate'])) .'-'. date('Y-m-d H:i:s', strtotime($inExchangeDate[0]['nextPreEnddate']));
            } else {
                $this->session->set_userdata(array('msg' => '<p>' . $rst->AddPointExchangeResult->MSG . '</p>' ));
            }
        }
        // 先判断用户是否是会员//获取openid
        $is_member = $this->is_member($this->openId);
        //获取会员信息
        if($is_member){
            $openID = array('openId'=>$this->openId);
            $osm_user = $this->osm_api->get_member_info_by_openid($openID);
        }
        if($this->arrayLevel($this->osm_api->get_points_gift()) == 1){
            $rst['present'][1] = $this->osm_api->get_points_gift();
            $rst['osm_user'] = $osm_user[0]['memberid'];
        }else{
            $rst['present'] = $this->osm_api->get_points_gift();
            $rst['osm_user'] = $osm_user[0]['memberid'];
        }
        // var_dump($rst);exit;
        $this->load->view('osm/present', $rst);
    }

    public function api_add_template()
    {
        $this->load->library('Wxapi');
        $params = [
            'template_id_short' => 'OPENTM202962124'
        ];
        $params = json_encode($params);
        $rst = $this->wxapi->api_add_template(4, $params);
        var_dump($rst);exit;
    }

    // 兑换礼品
    public function points_exchange() {
        $inExchangeDate = $this->osm_api->get_exchange_date();
        if (!isset($inExchangeDate[0]['curtPreStartDate']) || time() > strtotime($inExchangeDate[0]['curPreEnddate'])) {
            if (isset($inExchangeDate[0]['nextPreStartDate'])) {
                $this->session->set_userdata(array('msg' => '<p>不在预约期内，下次预约期为' . date('Y-m-d H:i:s', strtotime($inExchangeDate[0]['nextPreStartDate'])) .'-'. date('Y-m-d H:i:s', strtotime($inExchangeDate[0]['nextPreEnddate'])).'</p>' ));
            } else {
                $this->session->set_userdata(array('msg' => '<p>' . $rst->AddPointExchangeResult->MSG . '</p>' ));
            }
            return false;
        }
        $this->is_member($this->openId);//判断用户是否绑定微信。
        $data['openId'] = $this->openId;
        $user_info = $this->model->get_user_info($this->openId);
        $data['MemberID'] = $user_info['customer_id'];
        $data['GoodsID'] = $_POST['GoodsID'];
        $data['GoodsType'] = $_POST['GoodsName'];
        $guid = $this->_create_guid();
        $data['GiftUnique'] = $guid;
        setcookie("GiftUnique",$guid,time()+3600);
        $data['Qty'] = (int)$_POST['num'];
        $data['Points'] = (int)$_POST['Points'];
        $data['AddedUser'] = '111111';
        $data['AddedDate'] = date('Y-m-d H:i:s', time());
        $rst = $this->osm_api->points_exchange($data);
        if ($rst->AddPointExchangeResult->IsPass) {
            $rst_msg = $rst->AddPointExchangeResult->MSG;
        // $rst_msg = '积分兑换商品成功!兑换日期为:2016/11/1 0:00:00 到 2016/11/30 0:00:00 兑换柜台: 微信渠道ID为:102696';
            $pos_1 = mb_strpos($rst_msg, '兑换日期为:');
            $pos_2 = mb_strpos($rst_msg, ' 到');
            $pos_3 = mb_strpos($rst_msg, ' 兑换柜台:');
            $pos_4 = mb_strpos($rst_msg, 'ID为:');
            $gift = [
                'user_id' => $user_info['id'],
                'gift_id' => $data['GoodsID'],
                'gift_name' => $data['GoodsType'],
                'gift_poins' => $data['Points'],
                'count' => $data['Qty'],
                'exchange_begin' => date('Y-m-d H:i:s', strtotime(mb_substr($rst_msg, $pos_1+6, $pos_2-$pos_1-6))),
                'exchange_end' => date('Y-m-d H:i:s', strtotime(mb_substr($rst_msg, $pos_2+3, $pos_3-$pos_2-3))),
                'created_at' => date('Y-m-d H:i:s', time()),
                'counter' => mb_substr($rst_msg, $pos_3+7, $pos_4-$pos_3-7),
                'order_id' => mb_substr($rst_msg, $pos_4+4),
            ];
            if (!$this->model->insert_user_gift($gift)) {
                $this->session->set_userdata(array('msg' => '<p>交易失败，请联系客服</p>' ));
                return false;
            }

            $this->session->set_userdata(array('msg' => '<p>' . $rst->AddPointExchangeResult->MSG . '</p>' ));
            return true;
        }else{
            $this->session->set_userdata(array('msg' => '<p>' . $rst->AddPointExchangeResult->MSG . '</p>' ));
            return false;
        }
        // echo json_encode($rst);
        // var_dump($rst);
    }

    //领取礼物成功接口
    public function exchange_success()
    {
        if (isset($_POST['order_id'])) {
            $orderId = $_POST['order_id'];
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '请传入交易ID')))
                ->set_status_header(400);
        }
        // if (isset($_POST['goods_name'])) {
        //     $goodsName = $_POST['goods_name'];
        // } else {
        //     return $this->output
        //         ->set_content_type('application/json')
        //         ->set_output(json_encode(array('msg' => '请传入礼物名称')))
        //         ->set_status_header(400);
        // }
        // if (isset($_POST['goods_count'])) {
        //     $goodsCount = $_POST['goods_count'];
        // } else {
        //     return $this->output
        //         ->set_content_type('application/json')
        //         ->set_output(json_encode(array('msg' => '请传入礼物数量')))
        //         ->set_status_header(400);
        // }
        if (isset($_POST['open_id'])) {
            $openId = $_POST['open_id'];
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '请传入openid')))
                ->set_status_header(400);
        }

        $openid_arr = array('openId'=>$openId);
        $rst = $this->osm_api->get_member_info_by_openid($openid_arr);
        if (isset($rst['error'])) {
            switch ($rst['error']) {
                case 1:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '用户名或密码不对。')))
                        ->set_status_header(401);
                    break;
                
                case 2:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '请传入openid')))
                        ->set_status_header(401);
                    break;
                
                case 3:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '没有找到会员信息，请致电400')))
                        ->set_status_header(401);
                    break;
                
                default:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '系统错误，请重新查询。')))
                        ->set_status_header(401);
                    break;
                    break;
            }
        }
        $order = $this->model->get_order($openId, $orderId);
        if (!$order) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '订单不存在')))
                ->set_status_header(404);
        }
        $update_rst = $this->model->exchange_success($order['id']);
        if (!$update_rst) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '更新订单信息失败，请联系客服')))
                ->set_status_header(500);
        }

        $this->load->library('Wxapi');
        $json = array();
        $json["touser"] = $this->openId;
        $json["template_id"] = "kr2WItccHQ040K5FCB2x5QC8eIqhaY2nNnxOqdB9bhs";
        $json["topcolor"] = "#FF0000";
        $json["data"] = array(
            "first"=>array(
                "value" => "您好，您已成功领取礼物！",
                "color" => "#173177"
            ),
            "keyword1" => array(
                "value" => $order['gift_name'],
                "color" => "#173177"
            ),
            "keyword2" => array(
                "value" => $order['count'],
                "color" => "#173177"
            ),
            "keyword3" => array(
                "value" => date('Y-m-d H:i:s', time()),
                "color" => "#173177"
            ),
            "remark" => array(
                "value" =>"感谢您的使用，请注意查收礼品。",
                "color" => "#173177"
            )
        );
        $json = json_encode($json);
        $rst = $this->wxapi->send_template($this->wx_aid,$json);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('msg' => '兑换成功')))
            ->set_status_header(200);
    }

    public function fns() {
        $rst = $this->osm_api->get_functions();
        var_dump($rst);
    }

    // 生成 indexcode（每次载入页面唯一标示，防止重复提交）
    private function _create_indexcode() {
        $code = mt_rand(10000, 49999) + mt_rand(10000, 49999);
        return $code;
    }

    // 生成 GUID
    private function _create_guid() {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
        return $uuid;
    }

    /**
     * getOpenId 获取访问用户的openid (通过微信接口)
     *
     * @param //
     * @return $openid string //
     */
    public function getOpenId ()
    {
        // return '111111';
        $ids = $this->input->get_post('id');
        $code = $this->input->get_post('code');
        $ids = explode('-', $ids);
        $wx_aid = isset($ids[1]) ? $ids[1] : 0;

        if ( ! $wx_aid) return FALSE;

        /* 获取openid */
        $this->load->library('Wxapi');
        $wx_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $accountData = $this->wxapi->get_appid_secret($wx_aid);
        if ( ! $accountData) return FALSE;

        $param = array(
            'appid'=>$accountData['appid'],
            'secret'=>$accountData['secret'],
            'code'=>$code,
            'grant_type'=>'authorization_code'
        );
        $returnData = $this->wxapi->request($wx_url, $param);
        // var_dump($returnData);
        // exit();
        $returnData = json_decode($returnData, TRUE);
        if (!isset($returnData['openid'])){
            $err_msg = "无法获取用户详细信息！";
            $this->session->set_userdata(array('msg'=>"<p>{$err_msg}</p>"));
            header('location:error');
            exit();
        };
        $returnData['wx_aid'] = $wx_aid;
        $this->session->set_userdata(array('openId'=>$returnData['openid']));
        $this->session->set_userdata(array('wx_aid'=>$returnData['wx_aid']));
        return $returnData;
    }

    public function exchange_detail()
    {
        $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Y-m-d");
        $enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Y-m-d");
        $params = [
            'startdate' => $startdate,
            'enddate' => $enddate,
            'openid' => $this->openId,
        ];
        $detail = $this->osm_api->get_exchange_detail($params);
        $points = $this->osm_api->get_member_points_by_openid(array('openId'=>$this->openId));
        if (isset($points->GetMemberPointsResult->Obj)) {
            $point = $points->GetMemberPointsResult->Obj;
        }
        $data['records'] = $detail;
        if (isset($detail['error'])) {
            $err_msg = array('1' => '用户名或密码不正确！',
                             '2' => '没有兑换明细。',
                             '3' => '请传递开始日期',
                             '4' => '请传递结束日期',
                             '5' => '请传递openid',);
            $data['error'] = $err_msg[$detail['error']];
        }
        $data['startdate'] = $startdate;
        $data['enddate'] = $enddate;
        $data['point'] = $point;
        $this->load->view('osm/exchange_detail',$data); 
    }
}

/* End of file osm.php */
/* Location: ./application/controllers/osm.php */
