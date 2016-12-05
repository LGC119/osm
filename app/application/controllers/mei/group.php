<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 高级用户组控制器
 */
class Group extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mei/group_model', 'model');
	}

	/* 获取用户组列表 */
	public function get_list () 
	{
		$page = $this->input->get_post('page');
		$perpage = $this->input->get_post('perpage');

		$params = array ();
		$params['page'] = $page > 0 ? $page : 1;
		$params['perpage'] = $perpage > 0 ? $perpage : 12;

		$list = $this->model->get_list($params);

		if (is_array($list))
			$this->meret($list);
		else 
			$this->meret(NULL, MERET_EMPTY, $list);
	}

	/* 获取组成员ID */
	public function get_group_users () 
	{
		$id = $this->input->get_post('group_id');

		// $user_ids = $this->db->select
	}

	/* 将选中用户加入指定组 */
	public function add_user () 
	{
		$user_ids = $this->input->get_post('user_ids');
		$group_id = $this->input->get_post('group_id');

		/* 获取用户ID序列 */
		$res = $this->model->add_user($group_id, $user_ids);

		if ($res === TRUE) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, $res);

		return ;
	}

	/* 创建用户组 */
	public function create () 
	{
		$name = $this->input->get_post('name');
		$desc = $this->input->get_post('desc');

		if ( ! trim($name)) 
		{
			$this->meret('请填写组名称！');
			return ;
		}

		$res = $this->model->create($name, $desc);

		if ($res === TRUE)
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, $res);

		return ;
	}

	/* 修改用户组 */
	public function modify () 
	{
		// 
	}

	/* 删除用户组 */
	public function delete () 
	{
		// 
	}

	/* 获取组用户关联的账号信息 */
	public function get_group_user_info () 
	{
		$id = (int) $this->input->get_post('id');
		if ($id < 1) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '请正确选择一个组！');
			return ;
		}

		/* 获取用户组成员信息 */
		$user_info = $this->model->get_group_user_info($id);

		if (is_string($user_info)) 
			$this->meret(NULL, MERET_EMPTY, $user_info);
		else 
			$this->meret($user_info);

		return ;
	}

}

/* End of file group.php */
/* Location: ./application/controller/mei/group.php */
