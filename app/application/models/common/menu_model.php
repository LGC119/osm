<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 菜单model
*/
class Menu_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取当前登陆人的菜单项
	 * 获取方法：
	 * 1. 获取最底级菜单项 <非目录>
	 * 2. 获取上次目录菜单
	 * 3. 返回全部数据
	 **/
	public function get_my_menu ()
	{
		$menu_auth = $this->session->userdata('menu_auth');
		$menu_ids = array_unique(array_keys($menu_auth['menu']));

		if ($menu_auth['menu']) {
			$menus = $this->db->select('*')
				->where_in('id', $menu_ids)
				->get('menu')->result_array();
		}

		return $menus;
	}
}

/* End of file menu.php */
/* Location: ./application/controllers/common/menu.php */
