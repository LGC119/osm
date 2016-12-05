<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 账号绑定相关控制器
*/
class Menu extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('system/menu_model', 'model');
	}

	/* 获取菜单树 */
	public function get_menu_tree () 
	{
		$menu_tree = $this->model->get_menu_tree();

		if ($menu_tree) 
			$this->meret($menu_tree);
		else 
			$this->meret(NULL, MERET_EMPTY, '菜单数据获取失败！');
	}

	/* 获取菜单的权限项 */
	public function get_menu_auth ($menuid) 
	{
		$menuid = (int) $menuid;

		$auth_acts = $this->db->select('p.*')
			->from('rl_company_permission rcm')
			->join('permission p', 'rcm.permission_id = p.id', 'left')
			->where(array('p.menu_id'=>$menuid, 'rcm.company_id'=>$this->cid))
			->get()->result_array();

		if ($auth_acts) 
			$this->meret($auth_acts);
		else 
			$this->meret(NULL, MERET_EMPTY, '该菜单没有设置该菜单的权限项！');
		return ;
	}

	// public function

}

/* End of file menu.php */
/* Location: ./application/controllers/system/menu.php */