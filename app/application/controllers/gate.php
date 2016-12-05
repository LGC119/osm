<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 系统登录登出控制器
*/
class Gate extends Base_Controller {

	/*
	** Login to the system
	*/
	public function login()
	{

		$uname = trim($this->input->get_post('uname'));
		$upass = trim($this->input->get_post('upass'));

		if ( ! $uname) {
			echo json_encode(array('error'=>'请输入用户名'));
			return ;
		}
		if ( ! $upass) {
			echo json_encode(array('error'=>'请输入密码'));
			return ;
		}

		/* 获取staff信息 */
		$staff = $this->db->select('id, name, password, last_login_time, last_login_ip, company_id, position_id, do_message')
			->where(array('login_name'=>$uname, 'is_deleted'=>0))
			->get('staff')->row_array();

		if ( ! $staff) {
			echo json_encode(array('error'=>'您输入的用户名不存在'));
			return ;
		}

		/* 对比用户输入的密码 */
		if ($staff['password'] != md5($upass)) {
			echo json_encode(array('error'=>'用户名或密码不正确'));
			return ;
		}

		// 获取员工菜单权限
		$this->load->model('system/position_model', 'position');
		$menu_auth = $this->position->get_menu_auth($staff['position_id'], $staff['company_id']);

		$this->session->set_userdata(array(
			'staff_id' => $staff['id'],
			'staff_name' => $staff['name'],
			'last_login' => $staff['last_login_time'],
			'last_ip' => $staff['last_login_ip'],
			'company_id' => $staff['company_id'],
			'do_message' => $staff['do_message'],
			'menu_auth' => $menu_auth
		));

		/* 修改上次登录时间，IP，总登录次数 */
		$this->db->where('id', $staff['id'])
			->set('last_login_time', date('Y-m-d H:i:s'))
			->set('last_login_ip', $_SERVER['REMOTE_ADDR'])
			->set('login_count', 'login_count+1', FALSE)
			->set('state',1)
			->update('staff');

		echo json_encode(array('error'=>0, 'staff_id'=>$staff['id']));

		return ;
	}

	/*
	** 检验用户登录状态
	*/
	public function has_login()
	{
		if ( ! $this->session->userdata('staff_id'))
		{
			echo json_encode(0);
			return ;
		}

		/* 返回已登录用户信息 */
		$user_info = array (
			'staff_id' => $this->session->userdata('staff_id'),
			'staff_name' => $this->session->userdata('staff_name')
		);

		echo json_encode($user_info);
		return ;
	}

	/*
	** Logout of the system
	*/
	public function logout()
	{	
		//修改staff，员工离线
		$staff_id = $_SESSION['staff_id'];
		$this->db->where('id', $staff_id)
			->set('state',0)
			->update('staff');
		/* 销毁session */
		$this->session->sess_destroy();
		echo json_encode(TRUE);

		return ;
	}

	// request /me3/app/index.php/gate/friend_login/menu
	public function friend_login()
	{
		$uri_arr = explode('/', $this->uri->uri_string());
		$arr = $uri_arr[count($uri_arr) - 1];
		$menu = implode('/', explode('_', $arr));

		/* 获取staff信息 */
		$staff = $this->db->query("SELECT *
			FROM {$this->db->dbprefix('staff')}
			WHERE `login_name` = 'rayda'
			AND is_available = 1 AND is_deleted = 0")->row_array();

		// 获取员工权限
		$this->load->model('system/position_model', 'position');
		$permissions = $this->position->get_position_permission($staff['position_id']);

		$this->session->set_userdata(array(
			'staff_id' => $staff['id'],
			'staff_name' => $staff['name'],
			'last_login' => $staff['last_login_time'],
			'last_ip' => $staff['last_login_ip'],
			'company_id' => $staff['company_id'],
			'permissions'=>$permissions
		));

		/* 修改上次登录时间，IP，总登录次数 */
		$this->db->where('id', $staff['id'])
			->set('last_login_time', date('Y-m-d H:i:s'))
			->set('last_login_ip', $_SERVER['REMOTE_ADDR'])
			->set('login_count', 'login_count+1', FALSE)
			->update('staff');
		$dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
		$url = "http://{$_SERVER['SERVER_NAME']}{$dir}/main.html#/{$menu}";
		header("location:{$url}");
	}
}

/* End of file gate.php */
/* Location: ./application/controllers/gate.php */
