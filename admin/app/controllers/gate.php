<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 管理后台登陆控制器 admin/Gate
 *
 */
class Gate extends CI_Controller {

	function __construct()
	{
		parent::__construct();
	}

	/* 获取一个账号<注册> */
	public function register ($uname, $upass) 
	{
		echo $uname . ' · ' . $upass;

		$salt = $this->get_salt();
		$password = $this->get_hash($upass, $salt);

		echo "<br />";
		echo $salt;
		echo "<br />";
		echo $password;
	}

	/* 登陆方法 */
	public function login ()
	{
		$username = $this->input->get_post('uname'); 
		$password = $this->input->get_post('upass'); 

		$admin = $this->db->select('*')
			->where(array('login_name'=>$username))
			->get('admin')->row_array();

		if ( ! $admin) exit(json_encode(array('err_msg'=>'没有找到该用户！')));

		/* 验证密码是否正确 */
		$password = $this->get_hash($password, $admin['salt']);
		if ($password !== $admin['password']) exit(json_encode(array('err_msg'=>'用户名或密码不正确！')));

		/* 登陆成功 */
		$this->session->set_userdata(array(
			'admin_id' => $admin['id'],
			'admin_name' => $admin['name'],
			'admin_last_login' => $admin['last_login_time'],
			'admin_last_ip' => $admin['last_login_ip']
		));

		/* 修改上次登录时间，IP，总登录次数 */
		$this->db->where('id', $admin['id'])
			->set('last_login_time', date('Y-m-d H:i:s'))
			->set('last_login_ip', $_SERVER['REMOTE_ADDR'])
			->set('login_count', 'login_count+1', FALSE)
			->update('admin');

		echo json_encode(array('err_msg'=>'', 'admin_id'=>$admin['id']));
	}

	/* 退出后台管理系统 */
	public function logout()
	{
		/* 销毁session */
		$this->session->sess_destroy();
		echo json_encode(TRUE);
		
		return ;
	}

	/* 检测是否已经登陆 */
	public function chkLogin () 
	{
		$admin_id = $this->session->userdata('admin_id');

		if ( ! $admin_id) exit('0');

		/* 管理员ID */
		if ($admin_id > 0) 
		{
			echo json_encode(
				array (
					'admin_id' => $admin_id, 
					'admin_name' => $this->session->userdata('admin_name')
				)
			);
		}
	}

	/* 管理员密码加密 */
	private function get_hash ($string, $salt = '') 
	{
		if ($string === '') return FALSE;

		/* 获取散列密文 */
		return sha1(md5(md5($string) . $salt));
	}

	/* 获取随机加密salt */
	private function get_salt ($length = 16) 
	{
		// 随机生成一个
		$dict = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dict_len = strlen($dict);

		$salt = '';

		for ($i = 0; $i < $length; $i++) 
			$salt .= $dict[rand(0, $dict_len - 1)];

		return $salt;
	}

}