<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** Permission模型
**
*/
class Permission_model extends ME_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	public function get_list($company_id = NULL)
	{
		$company_id = intval($company_id) ? intval($company_id) : intval($this->session->userdata('company_id'));
		$rst = $this->db->select('p.id, name, title, pid, level, menu_id')
			->from('permission p')
			->join('rl_company_permission rcp', 'p.id = rcp.permission_id', 'left')
			->where('company_id', $company_id)
			->get()->result_array();
		return $rst;
	}

}