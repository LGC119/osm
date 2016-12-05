<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Menu_setting extends ME_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('module/menu_setting_model', 'model');
	}

	/**
	 * 获取系统公司列表
	 **/
	public function get_menu_tree ()
	{
		$menu_tree = $this->model->get_menu_tree();

		if ($menu_tree) 
			$this->meret($menu_tree);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有设置系统菜单！');

		return;
	}

	/* 修改菜单内容 */
	public function edit_menu () 
	{
		$id = (int) $this->input->get_post('id');
		$field = trim($this->input->get_post('field'));
		$value = trim($this->input->get_post('value'));

		if ( ! is_string($field) OR ! in_array($field, array ('name', 'icon', 'url'))) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '提交参数错误！');
			return ;
		}

		$menu = $this->db->select($field)
			->where('id', $id)
			->get('menu')->row_array();

		if ( ! $menu) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要修改的菜单！');
			return ;
		}

		if (trim($menu[$field]) == $value) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有做修改！');
			return ;
		}

		$this->db->set($field, $value)->where('id', $id)->update('menu');
		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '修改菜单失败，请稍后尝试！');

		return TRUE;
	}

}