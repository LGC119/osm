<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
class H5event extends ME_Controller{
	public function __construct(){
		parent::__construct();
		$data = $this->load->model('h5page/h5event_model','model');
	}

	/**
	* 获取活动的详细信息 
	* get接收活动的id，获取活动的详细信息
	* @ return meret jsondata
	*/
	public function index(){
		$id = $this->input->post('id');
		$data = $this->model->index($id);
		$h5page['h5_page'] = $data['h5_page'];
		//如果返回的数据是字符串，则为无效数据
		if(is_string($data))
			$this->meret(NULL, MERET_BADREQUEST, $data);
		else{
			$this->meret($data);
		}
			
		return ;
	}
}

