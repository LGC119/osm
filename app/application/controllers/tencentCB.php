<?php
/**
* @mei 腾讯回调地址处理类
* @author  董开顺
* @email jokan163@163.com
* @date 2013-10-31 下午12:51:23 
* @version v1.0 
*/

class tencentCB extends CI_Controller
{
	public $cid;
	public $authtable;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('session');
		
		$this->cid = $this->session->userdata('company_id');
		$this->authtable = $this->db->dbprefix('wb_account');
		$this->app_table = $this->db->dbprefix('application');
	}
	
	public function auth()
	{
		$g = $this->input->get();
		
		//用于授权跳转，正式情况下需要禁用这个** 
		$tag = stristr($g['state'], ',');
		if ($tag === false)
		{
			$baseurl = $g['state']; //不存在"," baseurl=$g['state']
		}
		else
		{
			$arr = explode(',', $g['state']); //存在
			$baseurl = $arr[0]; //处理绑定后插入wb_to_weibo
			$app_id = $arr[1]; //应用id 获取应用的回调地址 和app key, app secret
		}
		
		if ($baseurl != base_url())
		{
			redirect($baseurl . 'index.php/tencentCB/auth?code=' . $g['code'] . '&state=' . $g['state'] );
			exit();
		}

		$appinfo = $this->db->where('id',$app_id)->get($this->app_table)->row_array();
		$this->tencent_wb_akey = $appinfo['appkey'];
		$this->tencent_wb_skey = $appinfo['appskey'];
		$this->tencent_wb_callback_url = $appinfo['callbackurl'];
		
		require dirname(__FILE__) . '/../libraries/sdk/Tencent.php';
		OAuth::init($this->tencent_wb_akey, $this->tencent_wb_skey);
		
		if (isset($_SESSION['id']))
		{
			$id = $_SESSION['wx_activity'];
			$logid = $_SESSION['db_log_id'];
			header('Location:' . base_url() . 'index.php/wxh5_ext/authsuccess');
		}
		
		if (isset($g['code']))
		{
			try
			{
				// 获取token
				$keys = array();
				$keys['code'] = $g['code'];
				$keys['redirect_uri'] = $this->tencent_wb_callback_url;

				// require dirname(__FILE__) . '/../libraries/sdk/Tencent.php';
				$url = OAuth::getAccessToken($keys['code'], $this->tencent_wb_callback_url);
				$r = Http::request($url);
				parse_str($r, $token);
				
				// 授权成功后，获取用户信息写入数据库
				Tencent::init($token['access_token'], $token['openid']);
				
				$user = Tencent::api('user/other_info', array( 'fopenid' => $token['openid'] ), 'GET');

				/* 接口返回错误 */
				if (isset($user['errcode']) && $user['errcode'] > 0) 
					exit("<b>Authorization Failed : </b>{$user['error']} [code:{$user['errcode']}]");
				/* 接口返回错误end */

				$user_info = $user['data'];
				$data = array( 
					'weibo_id' => $user_info['openid'], 
					'company_id' => $this->cid, 
					'platform' => 2, 
					'access_token' => $token['access_token'], 
					'refresh_token' => $token['refresh_token'], 
					'screen_name' => $user_info['nick'], 
					'friends_count' => $user_info['idolnum'], 
					'followers_count' => $user_info['fansnum'], 
					'statuses_count' => $user_info['tweetnum'], 
					'profile_image_url' => $user_info['head'] ? $user_info['head'] . '/50' : '', 
					'expires_in' => $token['expires_in'], 
					'created_at' => date('Y-m-d H:i:s'), 
					'token_updated_at' => date('Y-m-d H:i:s'),
					'verified_type' => $data['isvip'] ? -2 : 999, 
					'registered_at' => date('Y-m-d H:i:s', $user_info['regtime']),
					'app_id' => $app_id,
					'app_name' => $appinfo['name']
				);

				$where = array( "company_id" => $this->cid, "weibo_id" => $token['openid'] );
				$rst = $this->db->get_where($this->authtable, $where)->row_array();
				if ( ! $rst)
					$this->db->insert($this->authtable, $data);
				else 
					$this->db->where($where)->update($this->authtable, array('token_updated_at' => date('Y-m-d H:i:s')));

				// 更新所有当前weibo_id的数据token,expires_in,etc...
				unset($data['company_id'], $data['weibo_id'], $data['app_id']);
				$other_company = array( "weibo_id" => $data['weibo_id'], 'app_id' => $app_id );		// 同一个微博账号的同应用授权
				$this->db->where($other_company)->update($this->authtable, $data);
				
				unset($data['weibo_id']);
				$this->db->where($where)->update($this->authtable, $data);

				// $this->logs->log($data,'sys_settings_wb_account_binding','add',1);
				echo "<script>window.opener.success();window.close();</script>";
			}
			catch (OAuthException $e)
			{
				// $this->logs->log($data,'sys_settings_wb_account_binding','add',0);
				//授权失败到这里
				echo "<script>window.opener.error();</script>";
			}
		}
	}

}