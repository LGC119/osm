<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Autherization_model extends ME_Model {

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取公司的权限和菜单项
	 */
	public function get_company_autherizations ($company_id) 
	{
		$company_id = (int) $company_id;
		$menu = $auth = array ();

		$menuidstr = $this->db->select('GROUP_CONCAT(menu_id) AS menu_ids')
			->where('company_id', $company_id)
			->get('rl_company_menu')->row_array();
		if ($menuidstr) 
		{
			foreach (explode(',', $menuidstr['menu_ids']) as $menu_id) 
				$menus[$menu_id] = TRUE;
		}

		$authidstr = $this->db->select('GROUP_CONCAT(permission_id) AS auth_ids')
			->where('company_id', $company_id)
			->get('rl_company_permission')->row_array();
		if ($authidstr) 
		{
			foreach (explode(',', $authidstr['auth_ids']) as $auth_ids) 
				$auths[$auth_ids] = TRUE;
		}

		return array ('menuids' => $menus, 'authids' => $auths);

	}

	/**
	 * 获取系统de菜单和权限项列表
	 **/
	public function get_menu_autherizations ()
	{
		$this->load->driver('cache');

		if ( ! $menus = $this->cache->get('company/menu_autherizations.cache')) 
			$menus = $this->_update_menu_autherizations_cache();

		return $menus;
	}

	/* 更新系统的菜单权限项缓存 */
	private function _update_menu_autherizations_cache () 
	{
		$this->load->model('module/menu_setting_model', 'menu_model');
		$menus = $this->menu_model->get_menu_tree();
		if ( ! $menus) return array ();

		/* 获取每个菜单的权限项 */
		foreach ($menus['list'] as &$menu) 
			$menu['auths'] = $this->db->select('id, title')
				->where('menu_id', $menu['id'])
				->get('permission')->result_array();

		$this->load->driver('cache');
		$this->cache->file->save('company/menu_autherizations.cache', $menus, 1800); // 缓存1小时

		return $menus;
	}

}