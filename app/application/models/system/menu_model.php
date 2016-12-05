<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** position模型 (使用本系统的用户)
**
*/
class Menu_model extends ME_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	/* 获取菜单树 */
	public function get_menu_tree ()
	{
		$this->load->driver('cache');

		/* 获取缓存数据 */
		if ( ! $menu_tree = $this->cache->get('common/menu_tree.cache')) 
			$menu_tree = $this->update_menu_cache();

		return $menu_tree;
	}

	/* 更新菜单数据缓存 */
	public function update_menu_cache () 
	{
		$menus = $this->db->select('m.*')
			->from('rl_company_menu rcm')
			->join('menu m', 'rcm.menu_id = m.id', 'left')
			->where('rcm.company_id', $this->session->userdata('company_id'))
			->get()->result_array();
		if ( ! $menus) return array ();

		/* 构建菜单树，和菜单ID，信息MAPPING */
		$tree = array ();
		$list = array ();
		foreach ($menus as $menu) 
		{
			$id = $menu['id'];
			$pid = $menu['pid'];
			isset($tree[$pid]) ? $tree[$pid][] = $id : $tree[$pid] = array ($id);
			$list[$id] = $menu;
		}

		$menu_tree = array (
			'list' => $list, 
			'tree' => $tree
		);

		$this->load->driver('cache');
		$this->cache->file->save('common/menu_tree.cache', $menu_tree, 1800); // 缓存1小时
		return $menu_tree;
	}

	/* 获取菜单的权限项 */
	public function get_menu_auth ($menuid) 
	{
		// $auths = $this->db->get(0)
	}

}