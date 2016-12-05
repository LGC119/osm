<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 系统登录登出控制器
*/
class Application extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('system/account_model', 'model');
		// $this->load->config('sys/account');
	}
	
	/*
	** 返回所有的绑定账号信息
	*/
	public function get_all_apps()
	{
		$applications = $this->model->get_all_apps();

		if ($applications) 
			$this->meret($applications,MERET_OK,'OK');
		else 
			$this->meret('',MERET_EMPTY,'没有设置任何应用！');
	}

}

/* End of file account.php */
/* Location: ./application/controllers/sys/account.php */