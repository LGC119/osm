<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Menu_setting_model extends ME_Model {

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取菜单树
	 **/
	public function get_menu_tree ()
	{
		$this->load->driver('cache');

		/* 获取缓存数据 */
		if ( ! $menu_tree = $this->cache->get('module/menu_tree.cache')) 
			$menu_tree = $this->update_menu_cache();

		return $menu_tree;
	}

	/* 更新菜单数据缓存 */
	public function update_menu_cache () 
	{
		$menus = $this->db->select('*')->get('menu')->result_array();
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
		$this->cache->file->save('module/menu_tree.cache', $menu_tree, 1800); // 缓存1小时
		return $menu_tree;
	}

}