<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Twodcode extends ME_Controller 
{
	private $wx_aid;
	public function __construct()
	{
		parent::__construct();
		$this->wx_aid = $this->session->userdata('wx_aid');
		$this->load->model('common/twodcode_model', 'model');
		$this->load->library('Wxapi');
        return;
	}

	/* 获取现有分类数据 */
	public function get_twodcode_data () 
	{
		$params = $this->input->get();
		$current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
		$items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 12;
		$title = isset($params['title']) ? $params['title'] : '';
		$category = isset($params['category']) ? $params['category'] : '';
		$all_codes = $this->model->get_twodcode_data($current_page,$items_per_page,$title,$category);

		if (empty($all_codes['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($all_codes);
		} 
        return;
	}

	public function create()
	{
		//接收参数（title，category，content）
		$data_post = $this->input->post();
		$title = $data_post['title'];
		$category = $data_post['category'];
		$content = isset($data_post['content']) ? $data_post['content'] : '';
		//获取当前company_id
		$company_id = $this->session->userdata('company_id');
		//获取当前时间
		$created_at = date('Y-m-d H:i:s');
		//获取access_token
        $access_token = $this->model->get_token($this->wx_aid);
        //计算出scene_id
        $max_scene_id = $this->db->select_max('scene_id')->get('wx_2dcode')->row_array();
        $scene_id_max = $max_scene_id ? $max_scene_id['scene_id'] : 0;
        $scene_id = $scene_id_max + 1;
        //获取ticket
		$result = $this->wxapi->wx_get_qrcode_permanent($access_token,$this->wx_aid,$scene_id);
		$ticket = $result['ticket'];
		//获取pic_url
		$pic_url = $this->wxapi->wx_get_qrcode_img($ticket,$scene_id);
		$data = array(
			'title'=>$title,
			'category'=>$category,
			'company_id'=>$company_id,
			'created_at'=>$created_at,
			'scene_id'=>$scene_id,
			'pic_url'=>$pic_url,
			'ticket'=>$ticket,
			'content'=>$content
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
			return $this->meret($id, MERET_OK, '标签删除成功');
		else 
			return $this->meret($id, MERET_SVRERROR, '标签删除失败');		// 返回错误信息
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
        return;
	}

	//按code_id获取指定二维码数据
	public function get_user_list()
	{
		$params = $this->input->get();
		$code_id = $params['code_id'];
		$years_num = $params['years_num'];
		$data = $this->model->get_user_list($code_id,$years_num);

		if (empty($data['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
        return;
	}


	//二维码数据过滤
	public function get_list_filter()
	{
		$params = $this->input->get();
		$title = $params['title'];
		$category = $params['category'];
		$data = $this->model->get_list_filter($title,$category);

		if (empty($data['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
        return;
	}

	//二维码数据过滤
	public function get_code_pic()
	{
		$this->load->helper('download');
		$params = $this->input->get();
		$code_id = intval($params['code_id']);
		$path = $_SERVER["DOCUMENT_ROOT"].'/me3/assets/img/twodcode/'.$code_id.'.jpg';
		$data = file_get_contents($path);//$data可为从数据库读取值

		$name = $code_id.'.jpg';
		$data = force_download($name, $data);
		if (empty($data['data']))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($data);
		} 
        return;
	}

}

/* End of file tag.php */
/* Location: ./application/controllers/common/tag.php */