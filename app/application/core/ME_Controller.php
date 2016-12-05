<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MasEngine Base Controllers
 *
 * 扩展CI基础Controllers, 添加登陆及权限验证等信息
 *
 * @package 	MasEngine Base
 * @copyright 	Copyright (c) 2014 - 2016, MasEngine, Inc.
 * @since 		Version 3.0
 **/

include 'Base_Controller.php';
class ME_Controller extends Base_Controller
{

	public $cid;		// 公司ID
	public $sid;		// 员工ID

	public function __construct()
	{
		parent::__construct();
		$this->load->config('controller');

		if ($this->session->userdata('staff_id')) {
			$this->cid = $this->session->userdata('company_id');
			$this->sid = $this->session->userdata('staff_id');

			/* 检查操作权限 */
			$operation = array (
				'module' => trim($this->router->fetch_directory(), '/'),
				'controller' => $this->router->fetch_class(),
				'method' => $this->router->fetch_method()
			);
			if ( ! $this->check_auth($operation)) 
			{
				$this->meret(NULL, MERET_UNAUTHORIZED, 'You are NOT authorized to perform this action !');
				exit();
			}
		} else {
			$this->meret(NULL, MERET_UNAUTHORIZED, 'Login Please !');
			exit ;
		}
	}

	/**
	 * 默认的返回函数
	 * 所有controller请使用这个函数返回信息
	 * @param $msg_type (返回状态码)
	 **/
	public function meret($data, $status = MERET_OK, $message = '')
	{
		$return_codes = $this->config->item('return_codes');
		$status = array_key_exists($status, $return_codes) ? $status : MERET_OK;
		$message = $message ? $message : $return_codes[$status];

		$status_arr = json_encode(array(
			'data' => $data,
			'code' => $status,
			'message' => $message,
			'timestamp' => time()
		));

		$this->_log_info['status'] = $status_arr;

		echo $status_arr;

		return ;
	}

	/**
	 * 判断当前用户是否被授权执行某项操作 
	 * @param $operation 操作信息 eg : array ('module'=>'meo', 'controller'=>'weibo', 'method'=>'send_status')
	 * 
	 * @return TRUE | FALSE 是否拥有执行权限
	 **/
	public function check_auth ($operation = array ()) 
	{
		if ( ! isset($operation['module']) OR ! isset($operation['controller']) OR ! isset($operation['method'])) return FALSE;

		$menu_auth = $this->session->userdata('menu_auth');
		$is_authed = $this->db->select('id')
			->where($operation)
			->get('permission')->row_array();

		if ( ! $is_authed) return TRUE; // 不需要授权
		return in_array($is_authed['id'], array_unique(array_keys($menu_auth['auth']))) ? TRUE : FALSE;
	}
	
}

/* End of file ME_Controller.php */
/* Location: ./application/core/ME_Controller.php */