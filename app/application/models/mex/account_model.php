<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Account_model extends ME_Model
{
	/**
	 * 获取微信账号公众平台后台设置url时的token
	 * @param  int $id
	 * @return string
	 */
	public function get_token($id)
	{
		$rst = $this->db->select('company_id, token, access_token, appid, secret')->get_where('wx_account', array('id' => $id))->row_array();
		
		return $rst;
	}
}