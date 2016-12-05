<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mei/user_model', 'model');
	}

	/* 用户列表 */
	public function get_list () 
	{
		/* 获取筛选参数 */
		$params = $this->_get_filter_params();

		/* 获取用户列表 */
		$list = $this->model->get_list($params);

		if ($list && $list['list']) 
			$this->meret($list);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有筛选到用户！');
	}

	/* 获取用户筛选参数 */
	public function _get_filter_params () 
	{
		$gender 		= $this->input->get_post('gender');
		$blood 			= $this->input->get_post('blood');
		$is_crm_user 	= $this->input->get_post('is_crm_user');
		$constellation 	= $this->input->get_post('constellation');
		$current_page 	= (int) $this->input->get_post('current_page');
		$items_per_page = (int) $this->input->get_post('items_per_page');
		$name 			= trim($this->input->get_post('name'));
		$tel 			= trim($this->input->get_post('tel'));

		$params = array (
			'current_page' => $current_page > 0 ? $current_page : 1, 
			'items_per_page' => $items_per_page > 0 && $items_per_page < 20 ? $items_per_page : 20
		);

		if (in_array($gender, array (0, 1, 2))) 
			$params['gender'] = $gender;

		if (in_array($blood, array (0, 1, 2, 3, 4))) 
			$params['blood'] = $blood;

		if ($is_crm_user == 1) 
			$params['is_crm_user'] = 1;
		
		if ($constellation >= 0 && $constellation <= 12) 
			$params['constellation'] = $constellation;

		if ( ! empty($name)) 
			$params['name'] = $name;
		if ( ! empty($tel)) 
			$params['tel'] = $tel;

		return $params;
	}

}

/* End of file user.php */
/* Location: ./application/controller/mei/user.php */