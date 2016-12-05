<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Auth_setting_model extends ME_Model {

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取系统公司列表
	 **/
	public function get_menu_tree ()
	{
		$this->load->driver('cache');

		/* 获取缓存数据 */
		if ( ! $menu_tree = $this->cache->get('module/menu_tree')) 
			$menu_tree = $this->update_menu_cache();

		return $menu_tree;
	}

}