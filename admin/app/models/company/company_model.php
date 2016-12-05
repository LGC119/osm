<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 功能菜单设定
 *
 **/
class Company_model extends ME_Model {

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取系统公司列表
	 * @param $p 请求参数 ：分页或筛选参数
	 *
	 * @return $list 系统存在的公司列表
	 **/
	public function get_list ($p)
	{
		$list = $this->db->select('c.*, COUNT(DISTINCT wba.id) AS weibo_accounts_num, COUNT(DISTINCT wxa.id) AS wechat_accounts_num')
			->from('company c')
			->join('wb_account wba', 'c.id = wba.company_id', 'left')
			->join('wx_account wxa', 'c.id = wxa.company_id', 'left')
			->where('wba.is_delete = 0 OR wba.is_delete IS NULL')
			->where('wxa.is_delete = 0 OR wxa.is_delete IS NULL')
			->group_by('c.id')
			->order_by('c.id', 'DESC')
			->get()->result_array();

		return $list;
	}

}