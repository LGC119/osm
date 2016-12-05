<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Shopplace extends ME_Controller 
{
	private $wx_aid;
	public function __construct()
	{
		parent::__construct();
		$this->wx_aid = $this->session->userdata('wx_aid');
		$this->load->model('common/shopplace_model', 'model');
		$this->load->library('Wxapi');
	}

	/* 获取现有分类数据 */
	public function get_shopplace_data () 
	{
		$params = $this->input->get();
		$current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
		$items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 12;
		$name = isset($params['name']) ? $params['name'] : '';
		$all_codes = $this->model->get_shopplace_data($current_page,$items_per_page,$name);

		if (empty($all_codes['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($all_codes);
		} 
	}

	public function create()
	{
		//接收参数（name，province，city,detail,telephone,location_x,lcation_y）
		$data_post = $this->input->post();
		if (!isset($data_post['name']) ||!isset($data_post['province']) ||!isset($data_post['city']) ||!isset($data_post['detail'])) 
		{
			return $this->meret(NULL, MERET_SVRERROR, '参数填写错误！');
		}
		$name = $data_post['name'];
		$province = $data_post['province'];
		$city = $data_post['city'];
		$detail = $data_post['detail'];
		$telephone = isset($data_post['telephone']) ? $data_post['telephone'] : '';
		$address = $province.$detail;
		if(!empty($data_post['location_x']) && !empty($data_post['location_y'])){
			$location = $data_post['location_x'].','.$data_post['location_y'];
		}else{
			$ak = AK;

			$url="http://api.map.baidu.com/geocoder/v2/?ak={$ak}&output=json&address='{$address}'&city='{$province}'";
			// $url = "http://api.map.baidu.com/geocoder/v2/?ak=r2L95XdDmdMGGi9SlMGvoPZ3&output=json&address=开福乐和城&city=长沙";
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);				// 设置抓取的数据的输出方式 1.文件流 0.直接输出
			$output = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($output,true);

			if($result['status'] == 0 && $result['result'] != ''){
				$arr['lng_lat'] =round($result['result']['location']['lat'],6).','.round($result['result']['location']['lng'],6);
				$arr['display'] = 1;
			}else{
				$arr['lng_lat'] = '';
				$arr['display'] = 0;
			}
			$location = $arr['lng_lat'];
		}
		
		$data = array(
			'display_name'=>$name,
			'wx_aid'=>$this->wx_aid,
			'province'=>$province,
			'city'=>$city,
			'display_address'=>$address,
			'display_tel'=>$telephone,
			// 'longitude_latitude'=>$arr['lng_lat']
			'longitude_latitude'=>$location
			);
		$res = $this->model->create($data);
		if (is_array($res))
			return $this->meret($res);
		else 
			return $this->meret(NULL, MERET_SVRERROR, $res);		// 返回错误信息
	}

	public function delete()
	{
		$params = $this->input->post();
		$id = $params['id'];
		$res = $this->model->delete($id);
		if ($res)
			return $this->meret($id, MERET_OK, '删除成功');
		else 
			return $this->meret($id, MERET_SVRERROR, '删除失败');		// 返回错误信息
	}

	public function stop()
	{
		$params = $this->input->post();
		$id = $params['id'];
		$res = $this->model->stop($id);
		if ($res)
			return $this->meret($id, MERET_OK, '删除成功');
		else 
			return $this->meret($id, MERET_SVRERROR, '删除失败');		// 返回错误信息
	}

	public function update(){
		//接收参数（name，province，city,display_address,telephone,location_x,lcation_y）
		$data_post = $this->input->post();
		if (!isset($data_post['display_name']) ||!isset($data_post['province']) ||!isset($data_post['city']) ||!isset($data_post['display_address']) ||!isset($data_post['id'])) {
			return $this->meret(NULL, MERET_SVRERROR, '');
		}

		$name = $data_post['display_name'];
		$detail = $data_post['display_address'];
		$telephone = $data_post['display_tel'];
		$province = $data_post['province'];
		$address = $province.$detail;
		if(!empty($data_post['location_x'])){
			$location = $data_post['location_x'].','.$data_post['location_y'];
		}else{
			$ak = AK;

			$url="http://api.map.baidu.com/geocoder/v2/?ak={$ak}&output=json&address='{$address}'&city='{$province}'";
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);				// 设置抓取的数据的输出方式 1.文件流 0.直接输出
			$output = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($output,true);

			if($result['status'] == 0 && $result['result'] != ''){
				$arr['lng_lat'] = round($result['result']['location']['lat'],6).','.round($result['result']['location']['lng'],6);
				$arr['display'] = 1;
			}else{
				$arr['lng_lat'] = '';
				$arr['display'] = 0;
			}
			$location = $arr['lng_lat'];
		}
		$id = $data_post['id'];
		$data = array(
			'display_name'=>$name,
			'display_address'=>$detail,
			'display_tel'=>$telephone,
			'longitude_latitude'=>$location
			);
		$res = $this->model->update($data,$id);
		if ($res)
			return $this->meret($res);
		else 
			return $this->meret(NULL, MERET_SVRERROR, $res);		// 返回错误信息
	}

	public function get_shopplace_by_id()
	{
		$id = $this->input->get_post('id');
		$res = $this->model->get_shopplace_by_id($id);
		if(empty($res)){
			$this->meret(NULL, MERET_EMPTY);
		}else{
			$this->meret($res);
		}
	}

	//按code_id获取指定二维码数据
	public function get_code_by_id()
	{
		$params = $this->input->get();
		$code_id = $params['code_id'];
		$data = $this->model->get_code_by_id($code_id);

		if (empty($data))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
	}

	//按code_id获取指定二维码数据
	public function get_user_list()
	{
		$params = $this->input->get();
		$code_id = $params['code_id'];
		$data = $this->model->get_user_list($code_id);

		if (empty($data))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
	}


	//二维码数据过滤
	public function get_list_filter()
	{
		$params = $this->input->get();
		$name = $params['name'];
		$province = $params['province'];
		$data = $this->model->get_list_filter($name,$province);

		if (empty($data['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
	}

}

/* End of file tag.php */
/* Location: ./application/controllers/common/tag.php */