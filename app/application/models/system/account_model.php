<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Account模型 (绑定系统的微博账号)
*/
class Account_model extends CI_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	public function del_wb_account ($account_id)
	{
		$this->db->where('id', $account_id)->delete('wb_accounts');
		return $this->db->affetced_rows() ? TRUE : FALSE;
	}

	public function del_wx_account ($account_id) 
	{
		$this->db->where('id', $account_id)->delete('wx_accounts');
		return $this->db->affetced_rows() ? TRUE : FALSE;
	}

	/* 获取一个账号的OAuth授权信息，用来初始化API或SDK */
	public function get_oa_info ($wb_aid = 0) 
	{
		$wb_aid = $wb_aid ? $wb_aid : $this->session->userdata('wb_aid');

		$oa_info = $this->db->select('wa.id, wa.weibo_id AS openid, wa.access_token, wa.refresh_token, wa.platform, wa.company_id')
			->select('a.appkey AS client_id, a.appskey AS client_secret')
			->from('wb_account wa')
			->join('application a', 'wa.app_id = a.id', 'left')
			->where(array ('wa.id'=>$wb_aid, 'a.is_delete'=>0))
			->get()->row_array();

		return $oa_info;
	}

	public function get_all_apps()
	{
		return $this->db->query("SELECT * 
			FROM {$this->db->dbprefix('application')} 
			WHERE is_delete = 0")->result_array();
	}

	/* 获取新浪微博绑定账号信息 */
	public function get_info_by_weiboid ($weibo_id) 
	{
		$res = $this->db->select('wa.id, wa.weibo_id AS openid, wa.access_token, wa.refresh_token, wa.platform, wa.company_id')
			->select('a.appkey AS client_id, a.appskey AS client_secret')
			->from('wb_account wa')
			->join('application a', 'wa.app_id = a.id')
			->where('wa.weibo_id', $weibo_id)
			->get()->row_array();

		return $res;
	}

    // 绑定微信【第一步】
    public function wx_bind($weixin){
//        var_dump($weixin);exit;
        $data['company_id'] = $weixin['company_id'];
        $data['nickname'] = $weixin['wx_account'];
        $data['token'] = $weixin['wx_token'];
//        $data['is_delete'] = 1;
//        $data['appid'] = $weixin['appid'];
//        $data['secret'] = $weixin['appsecret'];
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $this ->db ->insert('wx_account',$data);
        return $this ->db ->insert_id();

    }
    // 绑定微信【第二步】
    public function wx_bind2($weixin2){
//        $data['is_delete'] = 0;
        $data['appid'] = $weixin2['appid'];
        $data['secret'] = $weixin2['appsecret'];
        // 验证是否能正确获取 access_token
        $this ->load ->library('Wxapi');
        $token = $this ->wxapi ->wx_get_token($data['appid'],$data['secret']);
        if($token){
            // 绑定成功
            $data['access_token'] = $token;
            $data['verified'] = 1;
            // 获取该帐号的用户信息
//            $this->load->model('user/user_model','user');
//            $this->user->insert_user_all();
            return $this ->db ->update('wx_account',$data,array('id'=>$weixin2['id']));
        }else{
            $data['verified'] = 0;
            $this ->db ->update('wx_account',$data,array('id'=>$weixin2['id']));
            return FALSE;
        }
    }

    // 修改图片
    public function update_account($data){
        $data1['head_pic'] = $data['imgname'];
        $data1['nickname'] = $data['wx_nickname'];
        $status = $this ->db ->update('wx_account',$data1,array('id'=>$data['wx_aid']));
        return $status;
    }
    // 获取指定id的一条数据
    public function get_account_find($id){
        $sql = "SELECT id,nickname,appid,secret FROM ".$this ->db ->dbprefix('wx_account')."
                    WHERE id='$id' LIMIT 1";
        $data = $this ->db ->query($sql) ->result_array();
        return $data[0];
    }

    // 解绑
    public function wx_unbind($id){
        $data['is_delete'] = 1;
        return $this ->db ->update('wx_account', $data ,array('id'=>$id));
    }

}