<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 菜单
*/
class Menu extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('common/menu_model', 'model');
	}

	/* 获取当前登陆用户的MENU */
	public function index ()
	{
		$menus = $this->model->get_my_menu();

		if ($menus)
			$this->meret($this->build_tree($menus, 0));
		else
			$this->meret(NULL, MERET_EMPTY, '您的权限菜单为空！');
	}

	/* 无限分类递归格式化菜单 */
	protected function findChild(&$arr, $id)
	{
		$childs = array();
		foreach ($arr as $k => $v)
		{
			if($v['pid'] == $id)
			{
				$childs[] = $v;
			}
		}
		return $childs;
	}

	/* 创建菜单树：根据最底级菜单 */
	protected function build_tree($rows, $root_id)
	{
		$childs = $this->findChild($rows, $root_id);
		if(empty($childs)) return NULL;

		foreach ($childs as $k => $v)
		{
			$rescurTree = $this->build_tree($rows, $v['id']);
			if( NULL != $rescurTree)
			{
				$childs[$k]['cmenu'] = $rescurTree;
			}
		}
		return $childs;
	}



}

/* End of file menu.php */
/* Location: ./application/controllers/common/menu.php */
