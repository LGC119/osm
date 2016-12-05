<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Shopplace_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
		$this->shopplace_table = $this->db->dbprefix('h5_locationdata');
	}

	/* 获取现有分类数据 */
	public function get_shopplace_data ($current_page ,$items_per_page,$name) 
	{
		$wx_aid = $this->session->userdata('wx_aid');
		//分页limit
		$limit_begin = ($current_page - 1) * $items_per_page;
		$this->db->order_by('id','desc');
    	if ($name) {
    		$this->db->like('display_name',$name);
    	}
		//获取h5_locationdata表中数据
		$rst['data'] = $this->db->where('wx_aid', $wx_aid)->get($this->shopplace_table,$items_per_page,$limit_begin)->result_array();
		//统计总条数
		if ($name) {
			$count_code = $this->db->like('display_name',$name)->from($this->shopplace_table)->where('wx_aid', $wx_aid)->count_all_results();
		}else{
			$count_code =  $this->db->where('wx_aid', $wx_aid)->count_all_results($this->shopplace_table);
		}
		$rst['count_code'] = $count_code;
		$rst['current_page'] = $current_page;
		$rst['items_per_page'] = $items_per_page;
		return $rst;
	}
	//创建地理位置信息
	public function create($data)
	{
		$result = $this->db->insert($this->shopplace_table, $data);
		$id = $this->db->insert_id();
		if ($result > 0)
			return array_merge($data, array('id'=>$id));
		else 
			return '服务器忙，请稍后尝试！';
	}
	//删除地理位置信息
	public function delete($id)
	{
		$this->db->delete($this->shopplace_table, array('id' => $id));
		if ($this->db->affected_rows())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	//暂停商铺
	public function stop($id)
	{
		$this->db->where('id',$id);
		$this->db->update($this->shopplace_table, array('display' => 0));
		if ($this->db->affected_rows())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	//更新店铺信息
	public function update($data,$id)
	{
		$where = 'id = ' .$id;
		$this->db->where('id',$id);
		$this->db->update($this->shopplace_table,$data);
		$rs = $this->db->affected_rows();
		if ($rs)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	//通过openid 找到店铺的信息
	public function get_shopplace_by_aid($aid)
	{
		$this->db->select('longitude_latitude,display_name,display_address,id,display_tel,province,city');
		$this->db->from('h5_locationdata');
		$this->db->where('wx_aid',$aid);
		$this->db->where('display',1);
		$rst= $this->db->get()->result_array();
		return $rst;
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

/* End of file category.php */
/* Location: ./application/controllers/common/category.php */