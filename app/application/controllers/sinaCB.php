<?php
/**
* @mei 新浪回调地址处理类
* @author  董开顺
* @email jokan163@163.com
* @date 2013-10-31 下午12:51:23 
* @version v1.0 
*/

class sinaCB extends CI_Controller
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
		$g = $this->input->get(); // http://mei.masengine.com/mei/php/index.php/mei/sinaCB/auth
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
			redirect($baseurl . 'index.php/sinaCB/auth?code=' . $g['code'] . '&state=' . $g['state']);
			exit();
		}

		$appinfo = $this->db->where('id',$app_id)->get($this->app_table)->row_array();

		/* 检测应用是否存在 */
		if ( ! $appinfo) exit('App is not found !');		// 应用可能被删除或不存在！

		/* 这段是干嘛的？ */
		if (isset($_SESSION['id']))
		{
			$id = $_SESSION['wx_activity'];
			$logid = $_SESSION['db_log_id'];
			header('Location:' . base_url() . 'index.php/wxh5_ext/authsuccess');
		}
		/* 这段是干嘛的？ */

		
		if (isset($g['code']))
		{
			try
			{
				$keys = array();
				$keys['code'] = $g['code'];
				$keys['redirect_uri'] = $appinfo['callbackurl'];

				require_once dirname(__FILE__) . '/../libraries/sdk/sinasdk.php';
				$o = new SaeTOAuthV2($appinfo['appkey'], $appinfo['appskey']);
				$token = $o->getAccessToken('code', $keys);

				$sina = new SaeTClientV2($appinfo['appkey'], $appinfo['appskey'], $token['access_token']);
				$user_info = $sina->show_user_by_id($token['uid']);

				/* 接口返回错误 */
				if (isset($user_info['error'])) 
					exit("<b>Authorization Failed : </b>{$user_info['error']} [code:{$user_info['error_code']}]");
				/* 接口返回错误end */

				$data = array( 
					'weibo_id' => $user_info['idstr'], 
					'company_id' => $this->cid, 
					'platform' => 1, 
					'access_token' => $token['access_token'], 
					'refresh_token' => '', 
					'screen_name' => $user_info['screen_name'], 
					'friends_count' => $user_info['friends_count'], 
					'followers_count' => $user_info['followers_count'], 
					'statuses_count' => $user_info['statuses_count'], 
					'profile_image_url' => $user_info['profile_image_url'], 
					'expires_in' => $token['expires_in'], 
					'created_at' => date('Y-m-d H:i:s'), 
					'token_updated_at' => date('Y-m-d H:i:s'), 
					'verified_type' => $user_info['verified_type'], 
					'registered_at' => date('Y-m-d H:i:s', strtotime($user_info['created_at'])), 
					'app_id' => $app_id, 
					'app_name' => $appinfo['name'], 
					'is_delete' => 0
				);

				$where = array( "company_id" => $this->cid, "weibo_id" => $token['uid'] );
				$rst = $this->db->get_where($this->authtable, $where)->row_array();
				if ( ! $rst)
					$this->db->insert($this->authtable, $data);
				else 
					$this->db->where($where)->update($this->authtable, array('token_updated_at' => date('Y-m-d H:i:s')));

				// 更新所有当前weibo_id的数据token,expires_in,etc...
				unset($data['company_id'], $data['app_id']);
				$other_company = array( "weibo_id" => $data['weibo_id'], 'app_id' => $app_id );		// 同一个微博账号的同应用授权
				$this->db->where($other_company)->update($this->authtable, $data);

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