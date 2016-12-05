<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
class User extends CI_Controller
{

    public function __construct(){
        parent::__construct();
        $this->load->model('h5page-mobile/user_model','user');
    }

    // 会员注册
    public function memRegister(){

        $openid = $this->getOpenId();

        // 通过Openid得到在me_wx_user表中id
        $wx_user_id = $this->user->get_wx_user_id($openid);

        // 如果在那个表中已经存在了则提示已注册
        $bStatus = $this->user->isRegister($openid);
        if($bStatus){
            echo '已注册';
            exit;
        }
        // 将注册的姓名与手机号 添加到me_user表中 返回user_id
        $newData['full_name'] = $this->input->post('name');
        $newData['tel1'] = $this->input->post('phone');
        $user_id = $this->user->insert_me_user($newData);
        // 将user_id与wx_user_id存进me_user_sns_relation表中
        $status = $this->user->insert_user_sns($user_id,$wx_user_id);
        if($status){
            // 如何注册成功，则发送信息
            $ids = $this->input->get_post('id');
            $ids = explode('-', $ids);
            $wx_aid = isset($ids[1]) ? $ids[1] : 0;
            $this->load->library('Wxapi');
            $json = array();
            $json["touser"] = $openid;
            $json["template_id"] = "H-jTzXQtmbiznLn_ljre-cVhjpWzXKIfbqLfTyvEvN0";
            $json["url"] = "#";
            $json["topcolor"] = "#FF0000";
            $json["data"] = array(
                "first"=>array(
                    "value" => "您好".$newData['full_name']."，你已成功注册！",
                    "color" => "#173177"
                ),
                "keyword1" => array(
                    "value" => $newData['tel1'],
                    "color" => "#173177"
                ),
                "keyword2" => array(
                    "value" => date("Ymd",time()),
                    "color" => "#173177"
                ),
                "remark" => array(
                    "value" =>"感谢你的关注",
                    "color" => "#173177"
                )
            );
            $json = json_encode($json);
            $this->wxapi->send_template($wx_aid,$json);
            echo 200;
            exit;
        }else{
            echo '系统繁忙请稍后重试！';
            exit;
        }
    }

    /* 获取用户信息及优惠券信息 */
    public function getUserInfos ()
    {
        $openid = $this->getOpenId();

        if ( ! $openid)
        {
            echo json_encode(array('errmsg'=>'无法获取会员信息！'));
            return ;
        }

        $user_info = $this->db->select('u.full_name AS name, u.tel1 AS phone, usr.created_at')
            ->from('user u')
            ->join('user_sns_relation usr', 'usr.user_id = u.id', 'left')
            ->join('wx_user wu', 'wu.id = usr.wx_user_id', 'left')
            ->where('wu.openid', $openid)
            ->get()->row_array();

        if ( ! $user_info)
        {
            echo json_encode(array('errmsg'=>'请先注册会员！'));
            return ;
        }

        // 获取优惠券信息
        $coupons = array();

        echo json_encode(array('info'=>$user_info, 'coupons'=>$coupons));
        return ;
    }



    /**
     * getOpenId 获取访问用户的openid (通过微信接口)
     *
     * @param //
     * @return $openid string //
     */
    public function getOpenId ()
    {
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
        $returnData = json_decode($returnData, TRUE);
        if ( ! isset($returnData['openid'])) return FALSE;

        return $returnData['openid'];
    }


}