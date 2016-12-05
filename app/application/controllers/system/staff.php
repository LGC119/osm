<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 账号绑定相关控制器
*/
class Staff extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('system/staff_model', 'model');
	}
	
	/*
	** 返回所有的绑定账号信息
	*/
	public function index()
	{
		$staffs = $this->model->get_all_staffs($this->cid);

		if ( ! empty($staffs))
			$this->meret($staffs);
		else 
			$this->meret(NULL, MERET_EMPTY);
	}

	/* 添加员工 */
	public function add_staff ()
	{
		$data = array(
			'name' => trim($this->input->post('name')), 
			'login_name' => trim($this->input->post('login_name')), 
			'password' => trim($this->input->post('password')), 
			'tel' => trim($this->input->post('tel')), 
			'email' => trim($this->input->post('email')),
			'position_id'=> $this->input->post('position_id')
		);

		$res = $this->model->add_staff($data);

		if (is_array($res))
			$this->meret($res);
		else 
			$this->meret(NULL, MERET_SVRERROR, $res);
	}

	/* 修改员工信息 */
	public function edit_staff ()
	{
		$data = array(
			'name' => trim($this->input->post('name')),
			'password' => md5(trim($this->input->post('password'))), 
			'tel' => trim($this->input->post('tel')), 
			'email' => trim($this->input->post('email')), 
			'position_id'=> $this->input->post('position_id')
		);

		$res = $this->model->edit_staff($data, intval($this->input->post('id')));

		if (is_array($res))
			$this->meret($res);
		else 
			$this->meret(NULL, MERET_SVRERROR, $res);
	}

	// 修改个人信息 name email tel
	public function edit_profile ()
	{
		$k = $this->input->post('k');
		$v = trim($this->input->post('v'));
		if ( ! in_array($k, array ('name', 'email', 'tel'))) {
			$this->meret(NULL, MERET_BADREQUEST, '无法修改字段！');
			return ;
		}

		$data = array ($k => $v);
		$staff_id = $this->input->post('id') ? $this->input->post('id') : $this->sid;
		$res = $this->model->edit_staff($data, intval($staff_id));

		if (is_string($res))
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	/* 修改个人密码 */
	public function edit_password () 
	{
		$oldpass = $this->input->post('oldpass');
		$newpass = $this->input->post('newpass');
		$newpassconfirm = $this->input->post('newpassconfirm');

		if ($newpassconfirm != $newpass) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '两次输入的密码不一致，请检查！');
			return ;
		}

		if ($oldpass == $newpass) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '新密码与旧密码相同！');
			return ;
		}

		if (strlen($newpass) < 6 || strlen($newpass) > 40) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '密码长度请控制在 6 ~ 40 个字符之间！');
			return ;
		}

		$staff_info = $this->db->select('password')
			->from('staff')
			->where(array('id'=>$this->sid))
			->get()->row_array();

		if ( ! $staff_info) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '系统没有找到您的账号！');
			return ;
		}

		if ($staff_info['password'] != md5($oldpass)) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '您输入的原始密码不正确！');
			return ;
		}

		$this->db->where('id', $this->sid)->set('password', md5($newpass))->update('staff');

		if ($this->db->affected_rows())
			$this->meret(TRUE);
		else 
			$this->meret(NULL, MERET_SVRERROR, '修改失败，请稍后尝试！');

		return ;
	}

	/* 删除员工，is_delete置1 */
	public function del_staff ()
	{
		$id = $this->input->get_post('id');
		$id = intval($id);
		$res = $this->model->del_staff($id);
		
		if ($res)
			$this->meret($res);
		else 
			$this->meret(NULL, MERET_SVRERROR, $res);
	}

	/* 获取当前登陆的用户信息 */
	public function get_profile () 
	{
		$user = $this->db->select('s.id, s.email, s.login_count, s.login_name, s.name, s.tel')
			->select('p.name AS position_name')
			->from('staff s')
			->join('position p', 'p.id = s.position_id', 'left')
			->where('s.id', $this->sid)
			->get()->row_array();

		if ($user) {
			$user['last_login'] = $this->session->userdata('last_login');
			$user['last_ip'] = $this->session->userdata('last_ip');

			if ($user['last_ip'] == '::1' OR $user['last_ip'] = '127.0.0.1') 
				$user['last_ip'] = '本机';
		}

		// print_r($this->session->all_userdata());
		$this->meret($user);
	}
	//返回所有在线人员并且有分配权限的人员
	public function onlineGetStaffList(){
		$staffs = $this->model->get_staffs($this->cid);

		if ( ! empty($staffs))
			$this->meret($staffs);
		else 
			$this->meret(NULL, MERET_EMPTY);
	}

	//修改人员是否有可分配权限
	public function do_message(){
		$do_message = $this->input->get_post('do_message');
		$id = $this->input->get_post('id');
		$res = $this->model->do_message($id,$do_message);
	}
}

/* End of file account.php */
/* Location: ./application/controllers/sys/account.php */
