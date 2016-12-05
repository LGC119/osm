<?php 

if (! defined('BASEPATH')) exit('No direct script access allowed');


class H5event_model extends CI_Model{

	public function index($id){
		$select = 'aid,event_title,created_at,type,start_time,end_time,status,push_status,staff_id,h5page_id';
		$from = 'event';
		$where = array('id'=>$id);
		$rs = $this->select($select,$from,$where);

		if(empty($rs)){
			return '查询错误';
		}
		//获取发布信息的人的名字
		$rs['name'] = $this->get_name($rs['staff_id']);

		//获取微博微信的信息
		$rs['wb'] = $this->get_wb($id);
		$rs['wx'] = $this->get_wx($id);
		if(!empty($rs['wb']) && !empty($rs['wx'])){
			$rs['channel'] = '多渠道';
		}else if(!empty($rs['wb'])){
			$rs['channel'] = '微博';
		}else{
			$rs['channel'] = '微信';
		}

		$rs['h5_page'] = $this->get_h5($rs['h5page_id']);
		$rs['h5_page']['html_code'] = json_decode($rs['h5_page']['html_code']);

		return $rs;

	}

	/**
	* 获取发布活动的CSR的名字
	* @param id CSR 的id
	* @return string 
	*/
	public function get_name($id){
		$select = 'name';
		$from = 'staff';
		$where = array('id'=>$id);
		$rs = $this->select($select,$from,$where);
		$name = $rs['name'];
		return $name;
	}

	/**
	* 获取活动微博的信息
	* @param id 活动id
	* @return array
	*/
	public function get_wb($id){
		$select = 'account_id,content,pic_url,pic_name,start_time,needed_weibo_counts';
		$from = 'event_wb_info';
		$where = array('event_id'=>$id);
		$data = $this->select_all($select,$from,$where);

		//一次活动，多篇微博，多个推送账号
		if(!empty($data)){
			foreach($data as $k=>$v){
				$account_id = $v['account_id'];
				$data[$k]['account_name'] = $this->get_wb_name($account_id);
			}	
		}
		

		return $data;
	}

	/**
	* 获取活动微信的信息
	* @param id 活动id
	* @return array
	*/
	public function get_wx($id){
		$select = 'send_id,start_time';
		$from = 'event_wx_info';
		$where = array('event_id'=>$id);
		$data = $this->select_all($select,$from,$where);

		if(!empty($data)){
			//一次活动，多篇微信，多个推送账号
			foreach($data as $k=>$v){
				$account_id = $v['send_id'];
				$data[$k]['account_name'] = $this->get_wx_name($account_id);
			}	
		}
		

		return $data;
	}

	/**
	* 获取微博账户名字
	* @param id 活动的名字
	*/
	public function get_wb_name($id){
		$select = 'screen_name';
		$from = 'wb_account';
		$where = array('id'=>$id);
		$rs = $this->select($select,$from,$where);
		$screen_name = $rs['screen_name'];
		return $screen_name;
	}

	/**
	* 获取微信账户名字
	* @param id 活动的名字
	*/
	public function get_wx_name($id){
		$select = 'nickname';
		$from = 'wx_account';
		$where = array('id'=>$id);
		$rs = $this->select($select,$from,$where);
		$screen_name = $rs['nickname'];
		return $screen_name;
	}

	/**
	* 获取h5页面信息
	* @param id h5页面的id号
	* @return array
	*/
	public function get_h5($id){
		$select = 'title,created_at,html_code,template';
		$from = 'h5_page';
		$where = array('id'=>$id,'is_deleted'=>0);
		$rs = $this->select($select,$from,$where);
		return $rs;
	}

	/**
	* 查询数据，取出来的是一维数组
	* @param $select 要选择的字段
	* @param $from 从那张表里面选出数据
	* @param $where array 关联数组
	*
	* @return array
	*/
	public function select($select,$from,$where){
		$this->db->select($select);
		$this->db->from($from);
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result;
	}

	/**
	* 查询数据,取出来的是二维数组
	* @param $select 要选择的字段
	* @param $from 从那张表里面选出数据
	* @param $where array 关联数组
	*
	* @return array
	*/
	public function select_all($select,$from,$where){
		$this->db->select($select);
		$this->db->from($from);
		$this->db->where($where);
		$result = $this->db->get()->result_array();
		return $result;
	}

}


