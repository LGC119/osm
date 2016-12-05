<?php
class Shopplaceinfo_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
		$this->shopplace_table = $this->db->dbprefix('h5_locationdata');
	}

	//通过id找到店铺信息
	public function get_shopplace_by_id($id)
	{
		$this->db->select('longitude_latitude,display_name,display_address,id,display_tel,province,city');
		$this->db->from('h5_locationdata');
		$this->db->where('id',$id);
		$rst= $this->db->get()->result_array();
		return $rst;
	}

}