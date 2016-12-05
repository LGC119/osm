<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 账号绑定相关控制器
*/
class Account extends ME_Controller {

    private $wx_aid;
    private $company_id;
	public function __construct()
	{
		parent::__construct();
        $this ->wx_aid = $this ->session ->userdata('wx_aid');
        $this ->company_id = $this ->session ->userdata('company_id');
		$this->load->model('system/account_model', 'model');
	}
    public function index(){
    }
	
	/*
	** 返回所有的绑定账号信息
	*/
	public function get_all_accounts()
	{
		$this->load->model('system/staff_model', 'staff');
		$accounts = $this->staff->get_company_accounts($this->cid);
//        echo "<pre>";
//        print_r($accounts);
		if ( ! empty($accounts['wb_accounts'])) 
		{
			$accounts['currentWb'] = array();
			foreach ($accounts['wb_accounts'] as $wb_account) {
				if ($wb_account['id'] == $this->session->userdata('wb_aid')) {
					$accounts['currentWb'] = $wb_account;
					break;
				}
			}
			if (empty($accounts['currentWb'])) 
				$accounts['currentWb'] = $accounts['wb_accounts'][0];
			$this->session->set_userdata('wb_aid', $accounts['currentWb']['id']);
		}

		if ( ! empty($accounts['wx_accounts'])) 
		{
			$accounts['currentWx'] = array();
			foreach ($accounts['wx_accounts'] as $wx_account) {
				if ($wx_account['id'] == $this->session->userdata('wx_aid')) {
					$accounts['currentWx'] = $wx_account;
					break;
				}
			}
			if (empty($accounts['currentWx'])) 
				$accounts['currentWx'] = $accounts['wx_accounts'][0];
			$this->session->set_userdata('wx_aid', $accounts['currentWx']['id']);
		}

		$this->meret($accounts);
	}

	/* 获取系统绑定的微博账号 */
	public function get_wb_accounts () 
	{
		$this->load->model('system/staff_model', 'staff');
		$accounts = $this->staff->get_wb_accounts($this->cid);

		if ($accounts)
			$this->meret($accounts);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有绑定微博账号！');
	}

	/* 获取系统绑定的微信账号 */
	public function get_wx_accounts () 
	{
		$this->load->model('system/staff_model', 'staff');
		$accounts = $this->staff->get_wx_accounts($this->cid);

		if ($accounts)
			$this->meret($accounts);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有绑定微信账号！');
	}

	/* 返回弹出绑定微博页面的URL */
	public function get_bind_url($app_id) 
	{
		$app_id = intval($app_id);

		$app_info = $this->db->query("SELECT id, platform, callbackurl, appkey AS client_id, appskey AS client_secret 
			FROM {$this->db->dbprefix('application')} 
			WHERE id = '{$app_id}' 
			AND is_delete = 0")->row_array();

		if ($app_info) {
			switch ($app_info['platform']) {
				case 1:
					$this->load->library('wbapi_sina', array('client_id'=>$app_info['client_id'], 'client_secret'=>$app_info['client_secret']));
					$url = $this->wbapi_sina->get_bind_url($app_info);
					$this->meret($url);
					break;
				
				case 2:
					$this->load->library('wbapi_tx', array('client_id'=>$app_info['client_id'], 'client_secret'=>$app_info['client_secret']));
					$url = $this->wbapi_tx->get_bind_url($app_info);
					$this->meret($url);
					break;
				
				default:
					$this->meret(NULL, MERET_BADREQUEST, '未知APP，请刷新页面后重试！');
					break;
			}
		} else {
			$this->meret(NULL, MERET_BADREQUEST, '未知APP，请刷新页面后重试！');
		}
	}

	/* 切换系统账号, 设定系统当前的wb_id, wx_id */
	public function switch_account($type, $account_id)
	{
		$account_id = intval($account_id);
		if ($account_id < 1) {
			$this->meret('', MERET_BADREQUEST, '未知账号 !');
			return ;
		}

		if ($type == 'weibo') {
			$account_info = $this->db->query("SELECT id, weibo_id 
				FROM {$this->db->dbprefix('wb_account')} 
				WHERE id = '{$account_id}' 
				AND company_id = {$this->cid}")->result_array();

			if ( ! $account_info) {
				$this->meret('', MERET_EMPTY, '未知账号类型 !');
				return ;
			} else {
				$this->session->set_userdata(array('wb_aid'=>$account_id));
				$this->meret($account_id);

				return ;
			}
		} else if ($type == 'weixin') {
			$this->session->set_userdata(array('wx_aid'=>$account_id));
			$this->meret($account_id);
			return ;
		} else {
			$this->meret('', MERET_BADREQUEST, '未知账号类型 !');
			return ;
		}
	}

    // 绑定第一步
    public function wx_bind(){
        $weixin = $this ->input ->post('weixin');
        $weixin['company_id'] = $this ->company_id;
//        $base_url = str_replace(array('app','app\/','\/app\/'),'',$base_url);
        $id = $this ->model ->wx_bind($weixin);
        $data['url'] = base_url().'index.php/mex/receive_message/index/'.$id;
        $data['id'] = $id;
        if($id)
            $this ->meret($data,200);
        else
            $this ->meret(NULL,508);
    }
    // 绑定第二步
    public function wx_bind2(){
        $weixin2 = $this ->input ->post('weixin2');
        $status = $this ->model ->wx_bind2($weixin2);
        if($status)
            $this ->meret(NULL,200);
        else
            $this ->meret(NULL,508);
    }

    // 更新
    public function update_account(){
        $data = $this ->input ->post('data');
        $status = $this ->model ->update_account($data);
        if($status)
            $this ->meret(NULL,200);
        else
            $this ->meret(NULL,508);
    }

    // 获取指定id下的一条account数据
    public function get_account_find(){
        $id = $this ->input ->post('id');
        $data =$this ->model ->get_account_find($id);
        $data['url'] = base_url().'index.php/mex/receive_message/index/'.$id;
        $this ->meret($data,MERET_OK);
    }

    // 微博账号解绑
    public function wb_unbind ($id) 
    {
    	$id = intval($id);
    	if ( ! $id > 0) {
    		$this->meret(NULL, MERET_BADREQUEST, '没有找到改账号！');
    		return ;
    	}

    	$this->db->set('is_delete', 1)
    		->where('id', $id)
    		->update('wb_account');

    	if ($this->db->affected_rows())
    		$this->meret(TRUE);
    	else 
    		$this->meret(NULL, MERET_BADREQUEST, '删除失败，请稍后尝试！');
    }
    
    // 解绑
    public function wx_unbind(){
        $id = $this ->input ->post('id');
        $status = $this ->model ->wx_unbind($id);
        if($status)
            $this ->meret(NULL,200);
        else
            $this ->meret(NULL,508);
    }

}


/* End of file account.php */
/* Location: ./application/controllers/sys/account.php */