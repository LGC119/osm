<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MasEngine Base Controllers
 *
 * 扩展CI基础Controllers, 添加登陆及权限验证等信息
 *
 * @package		MasEngine Base
 * @copyright	Copyright (c) 2014 - 2016, MasEngine, Inc.
 * @since		Version 3.0
 */

include 'Base_Controller.php';
class ME_Controller extends Base_Controller
{

	public $aid;		// 管理员ID

	public function __construct()
	{
		parent::__construct();

		if ( ! $this->session->userdata('admin_id')) 
		{
			$this->meret(NULL, MERET_UNAUTHORIZED, 'Login Please !');
			exit();
		}
		
		$this->aid = $this->session->userdata('admin_id');
		return ;
	}
	
}

/* End of file ME_Controller.php */
/* Location: ./application/core/ME_Controller.php */