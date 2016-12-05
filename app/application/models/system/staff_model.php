<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Staff模型 (使用本系统的用户)
**
*/
class Staff_model extends ME_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	public function get_all_staffs ($company_id) 
	{
		$company_id = intval($company_id);
		$staffs = $this->db->select('staff.id, staff.name, login_name, position_id, position.name position_name, tel, email, last_login_time, last_login_ip, staff.created_at, login_count ,do_message')
			->from('staff')
			->join('position', 'staff.position_id = position.id', 'left')
			->where(array('staff.company_id'=>$company_id, 'staff.is_deleted'=>0))
			->get()->result_array();

		return $staffs;
	}

	/*
	** 添加一条员工记录
	*/
	public function add_staff ($data)
	{
		/* 验证前台数据 */
		$verify = $this->_verify_data($data);
		if (is_string($verify))
			return $verify;
		/* 验证未通过，返回错误信息 */

		$data = array_merge($data, array(
			'created_at' => date('Y-m-d H:i:s'), 
			'company_id' => $this->session->userdata('company_id')
		));
		$data['password'] = md5($data['password']);
		$staff_id = $this->db->insert('staff', $data);
		return $staff_id ? array_merge($data, array('id'=>$staff_id)) : '服务器忙，请稍后尝试！';
	}

	/*
	** 修改一条员工记录
	*/
	public function edit_staff ($data, $staff_id)
	{
		$staff_id = intval($staff_id);
		if ( ! $staff_id)
			return array('error'=>'员工不存在！');

		/* 验证前台数据 */
		$verify = $this->_verify_data($data, $staff_id);
		if (is_string($verify))
			return $verify;
		if (empty($data['password'])) unset($data['password']);
		/* 验证未通过，返回错误信息 */

		$this->db->where('id', $staff_id)->update('staff', $data);

		return $this->db->affected_rows() ? $data : '服务器忙，请稍后尝试！';
	}

	/* 校验用户提交的表单中的数据 */
	private function _verify_data ($data, $staff_id = 0)
	{
		if ($staff_id > 0) {
			$staff = $this->get_staff_info($staff_id);
			if ( ! $staff)
				return '没有找到该员工信息！';
		}

		/* 登录名校验 */
		if ( ! $staff_id && 
			(mb_strlen($data['login_name'], 'UTF-8') < 3 OR 
			mb_strlen($data['login_name'], 'UTF-8') > 20))
			return '请填写登录名，3~20个字符';

		if ( ! $staff_id) {
			$staff_exsit = $this->db->select('id')
				->from('staff')
				->where('login_name', $data['login_name'])
				->get()->row_array();
			if ($staff_exsit) 
				return '该登陆名已经存在，换一个吧！';
		}

		/* 密码校验[修改时<未填写不验证>] */
		if (( ! $staff_id OR ($staff_id && isset($data['password']))) && 
			(mb_strlen($data['password'], 'UTF-8') < 6 
			OR mb_strlen($data['password'], 'UTF-8') > 40))
			return '请填写密码，6~40个字符';

		/* 姓名校验 */
		if (isset($data['name']) && trim($data['name']) && 
			(mb_strlen($data['name'], 'UTF-8') < 2 OR 
			mb_strlen($data['name'], 'UTF-8') > 20)) // 新增staff
			return '请填写姓名，2~20个字符';

		/* 电话号码校验 */
		if (isset($data['tel']) && trim($data['tel']) && 
			! preg_match('/^1\d{10}$/', $data['tel'])) 
			return '请检查您填写的电话号码格式, 11位数字！';

		/* email校验 */
		if (isset($data['email']) && trim($data['email']) && ! 
			preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $data['email'])) 
			return '请填写正确格式的电子邮件！';

		return TRUE;
	}

	/*
	** 删除一条用户记录
	*/
	public function del_staff ($staff_id)
	{
		$staff_id = intval($staff_id);
		$this->db->delete('staff', array('id'=>$staff_id));
		return $this->db->affected_rows() ? TRUE : FALSE;
	}

	public function get_staff_info ($staff_id)
	{
		$staff_id = intval($staff_id);

		$info = $this->db->query("SELECT s.*, c.id 
			FROM {$this->db->dbprefix('staff')} s 
			LEFT JOIN {$this->db->dbprefix('company')} c 
			ON s.company_id = c.id 
			WHERE s.id = '{$staff_id}'")->row_array();

		return $info;
	}
	
	/* 根据公司获取绑定微博和微信账号信息 */
	public function get_company_accounts ($company_id)
	{
		return array(
			'wb_accounts' => $this->get_wb_accounts($company_id),
			'wx_accounts' => $this->get_wx_accounts($company_id)
		);
	}
	
	/* 根据公司获取绑定微博账号信息 */
	public function get_wb_accounts ($company_id)
	{
		$company_id = intval($company_id) ? intval($company_id) : intval($this->session->userdata('company_id'));

		if ($company_id < 1)
			return array('error'=>'公司信息不正确！');

		/* 微博账号 */
		$wb_accounts = $this->db->select('*')
			->from('wb_account')
			->where(array('company_id'=>$company_id, 'is_delete'=>0))
			->get()->result_array();

		# 获取每个账号的过期状态
		foreach ($wb_accounts as &$account) 
		{
			$update_at = strtotime($account['token_updated_at']);
			$expire_at = $update_at + $account['expires_in'];
			$account['expire_date'] = date('Y-m-d H:i:s', $expire_at);
			$account['expired'] = $expire_at < time() ? TRUE : FALSE;
		}

		return $wb_accounts;
	}
	
	/* 根据公司获取绑定微信账号信息 */
	public function get_wx_accounts ($company_id)
	{
		$company_id = intval($company_id) ? intval($company_id) : intval($this->session->userdata('company_id'));

		if ($company_id < 1)
			return array('error'=>'公司信息不正确！');

		/* 微信账号 */
		$wx_accounts = $this->db->select('a.*, COUNT(u.id) followers_count', FALSE)
			->from('wx_account a')
			->join('wx_user u', 'a.id = u.wx_aid', 'left')
			->where(array('a.company_id'=>$company_id,'a.is_delete'=>'0'))
			->group_by('a.id')
			->get()->result_array();

		return $wx_accounts;
	}

	/*
	** 根据员工获取绑定账号信息
	*/
	public function get_staff_accounts ($staff_id)
	{
		$company = $this->db->select('company_id')
			->from('staff')
			->where('id', $staff_id)
			->get()->row_array();

		if ( ! $company) 
			return array('error'=>'员工或公司的信息不正确！');
		else
			return $this->get_company_accounts($company['company_id']);
	}
	//返回所有在线人员并且有分配权限的人员
	public function get_staffs ($company_id) 
	{
		$company_id = intval($company_id);
		$staffs = $this->db->select('staff.id, staff.name, login_name, position_id, position.name position_name, tel, email, last_login_time, last_login_ip, staff.created_at, login_count')
			->from('staff')
			->join('position', 'staff.position_id = position.id', 'left')
			->where(array('staff.company_id'=>$company_id,'staff.state'=>1,'staff.do_message'=>1, 'staff.is_deleted'=>0))
			->get()->result_array();

		return $staffs;
	}
	public function do_message($id,$do_message){
		if($do_message == 0){
			$this->db->where('id', $id)->update('staff', array('do_message'=>1));
		}else{
			$this->db->where('id', $id)->update('staff', array('do_message'=>0));
		}
	}
}