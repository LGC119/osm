<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 品牌授权管理 <平台公司菜单权限管理> 
 *
 **/
class Autherization extends ME_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('company/autherization_model', 'model');
	}

	/**
	 * 获取公司权限菜单列表
	 **/
	public function get_company_autherizations ()
	{
		// 获取当前所有的公司
		$this->load->model('company/company_model', 'company_model');
		$companies = $this->company_model->get_list(array());

		if ( ! $companies) 
		{
			$this->meret(NULL, MERET_EMPTY, '没有注册公司信息！');
			return ;
		}

		/* 获取每个公司的授权信息 */
		foreach ($companies as &$company) 
			$company = array_merge($company, $this->model->get_company_autherizations($company['id']));

		$data = array ('list'=>$companies);
		$this->meret($data);

		// $autherizations = $this->db->select('')
	}

	/* 获取系统设定的菜单和权限项 */
	public function get_menu_autherizations () 
	{
		$menus = $this->model->get_menu_autherizations();

		$this->meret($menus);
	}

	/* 设定公司的权限菜单项 */
	/**
	 * TODO : 提交菜单项做检测
	 * TODO : 没有子菜单的父级菜单[空节点] -> 删除
	 * TODO : 没有选择上级菜单的子菜单[孤树] -> 删除
	 * TODO :
	 **/
	public function set_autherization () 
	{
		$company_id = (int) $this->input->post('company_id');
		$authids = $this->input->post('authids');
		$menuids = $this->input->post('menuids');

		$company = $this->db->select('id')->where(array('id'=>$company_id, 'is_deleted'=>0))->get('company')->row_array();

		if ( ! $company) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到该公司信息！');
			return ;
		}

		/* 删除公司原有的菜单和权限项 */
		$this->db->where('company_id', $company_id)->delete('rl_company_menu');
		$this->db->where('company_id', $company_id)->delete('rl_company_permission');

		// 要插入的数组
		$auth_arr = $menu_arr = array ();
		if ($authids) {
			foreach (array_unique($authids) as $id) {
				if ($id > 0) 
					$auth_arr[] = array ('company_id'=>$company_id, 'permission_id'=>$id);
			}
		}
		if ($menuids) {
			foreach (array_unique($menuids) as $id) {
				if ($id > 0) 
					$menu_arr[] = array ('company_id'=>$company_id, 'menu_id'=>$id);
			}
		}
		/* 修改菜单和权限项 */
		if ($auth_arr) $this->db->insert_batch('rl_company_permission', $auth_arr);
		if ($menu_arr) $this->db->insert_batch('rl_company_menu', $menu_arr);

		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '修改权限菜单项失败，请稍后尝试！');

		return ;
	}

}