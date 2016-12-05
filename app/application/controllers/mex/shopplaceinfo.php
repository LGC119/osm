<?php
class Shopplaceinfo extends CI_Controller{
	public function __construct(){
		parent::__construct();

		/* 可使用 $this->model 调用meo/communication里的函数 */
		$this->load->model('mex/shopplaceinfo_model', 'model');
	}
	public function get_info(){
		$id = $this->input->get('id');
		$res = $this->model->get_shopplace_by_id($id);
		if ( ! $res) {
			$data['info'] = '该信息已经被删除！！！';
		} else {
			$data = $res[0];
			$location = explode(',',$data['longitude_latitude']);
			$data['location_x'] = $location[0];
			$data['location_y'] = $location[1];
		}
		// var_dump($data);
		$this->load->view('shop_place_info.php',$data);
	}

	public function get_shopplace_by_id()
	{
		$id = $this->input->get_post('id');
		$res = $this->model->get_shopplace_by_id($id);
	}

}

