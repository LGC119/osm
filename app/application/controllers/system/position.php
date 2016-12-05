<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 账号绑定相关控制器
*/
class Position extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('system/position_model', 'model');
		$this->cid = $this->session->userdata('company_id');
		$this->sid = $this->session->userdata('staff_id');
	}
	
	/*
	** 返回所有的绑定账号信息 | 包括当前职位的菜单和权限信息
	*/
	public function index()
	{
		$positions = $this->model->get_list($this->cid);

		if ( ! empty($positions))
		{
			$this->meret($positions);
		}
		else
		{
			$this->meret(NULL, MERET_EMPTY);
		} 
	}

	/* 添加职位 */
	public function create()
	{
		$name = $this->input->get_post('name');
		$menuids = $this->input->get_post('menuids'); 		// 菜单IDs
		$authids = $this->input->get_post('authids'); 		// 权限IDs

		$res = $this->model->create($name, $menuids, $authids);

		if (is_string($res))
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	/* 修改职位信息 */
	public function modify ()
	{
		$id = $this->input->get_post('id');
		$name = $this->input->get_post('name');
		$menuids = $this->input->get_post('menuids'); 		// 菜单IDs
		$authids = $this->input->get_post('authids'); 		// 权限IDs

		$res = $this->model->modify($id, $name, $menuids, $authids);

		if (is_string($res))
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	/* 删除职位，is_delete置1 */
	public function delete ()
	{
		$id = (int) $this->input->get_post('id');
		$position = $this->db->select('GROUP_CONCAT(s.id) AS staff_ids')
			->from('position p')
			->join('staff s', 's.position_id = p.id')
			->where(array ('p.id'=>$id, 's.is_deleted'=>0, 'p.company_id'=>$this->cid))
			->get()->row_array();

		if ( ! $position) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要删除的职位！');
			return ;
		}

		if ( ! empty($position['staff_ids'])) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '该职位下有员工，请先删除员工！');
			return ;
		}

		$res = $this->model->delete($id);
		if (is_string($res))
			$this->meret(NULL, MERET_SVRERROR, $res);
		else 
			$this->meret($res);
	}

	/* 获取职位的菜单和权限项 */
	public function get_menu_auth ($id) 
	{
		$id = (int) $id;
		$menu_auths = $this->model->get_menu_auth ($id);

		if ($menu_auths) 
			$this->meret($menu_auths); 
		else 
			$this->meret(NULL, MERET_EMPTY, '该职位权限菜单为空！');
	}

}

/* End of file account.php */
/* Location: ./application/controllers/sys/account.php */