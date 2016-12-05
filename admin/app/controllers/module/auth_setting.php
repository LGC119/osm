<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Auth_setting extends ME_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('module/auth_setting_model', 'model');
	}

	/**
	 * 获取系统公司列表
	 **/
	public function get_menu_auth ($menu_id)
	{
		$menu_id = (int) $menu_id;

		$auth_acts = $this->db->select('*')
			->from('permission')
			->where('menu_id', $menu_id)
			->get()->result_array();

		if ($auth_acts) 
			$this->meret($auth_acts);
		else 
			$this->meret(NULL, MERET_EMPTY, '该菜单没有设置该菜单的权限项！');

		return ;
	}

	/* 添加权限项 */
	public function add_auth () 
	{
		$p = $this->input->post(NULL, TRUE);
		$res = $this->_verify_post($p);

		if (is_string($res)) 
		{
			$this->meret(NULL, MERET_BADREQUEST, $res);
			return ;
		}

		$menuid = intval($p['menuid']);
		$menu = $this->db->select('id')
			->where('id', $menuid)
			->get('menu')->row_array();

		if ( ! $menu) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '参数不完整！');
			return ;
		}

		// 插入数据库
		$data = array (
			'menu_id' => $p['menuid'], 
			'title' => addslashes(trim($p['title'])), 
			'module' => addslashes(trim($p['module'])), 
			'controller' => addslashes(trim($p['controller'])), 
			'method' => addslashes(trim($p['method']))
		);

		$this->db->insert('permission', $data);
		if ($this->db->insert_id()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '创建失败，数据库不给插入了~~~%>_<%');
	}

	/* 编辑权限项 */
	public function edit_auth () 
	{
		// 
	}

	/* 删除权限项 */
	public function del_auth ($auth_id) 
	{
		// 
	}

	/* 验证输入 */
	private function _verify_post ($p) 
	{
		$arr = array ('title', 'module', 'controller', 'method');
		foreach ($arr as $k) 
		{
			if ( ! isset($p[$k]) || trim($p[$k]) == '') 
				return '请填写完整参数';

			if ($k != 'title' && ! preg_match('/^[a-zA-Z0-9_]+$/', $p[$k])) 
				return '模块，控制器或方法填写请复核规范！';
		}

		/* 检查是否有相同定义 */
		$exsit = $this->db->select('id')
			->where(array('module'=>$p['module'], 'controller'=>$p['controller'], 'method'=>$p['method']))
			->get('permission')->row_array();
		if ($exsit) 
			return '此权限项已经定义过！';

		return TRUE;
	}

}