<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Tag extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('common/tag_model', 'model');
	}

	/* 获取现有分类数据 */
	public function index () 
	{
		$tags = $this->model->get_all_tags($this->cid);

		if (empty($tags))
		{
			$this->meret(NULL, MERET_EMPTY);
		}
		else
		{
			$this->meret($tags);
		} 
	}

	public function create()
	{
		$pid = intval($this->input->post('pid'));
		$name = trim($this->input->post('name'));

		$res = $this->model->create($this->cid, $this->sid, $pid, $name);
		if (is_array($res))
			return $this->meret($res);
		else 
			return $this->meret(NULL, MERET_SVRERROR, $res);		// 返回错误信息
	}

	public function delete()
	{
		$id = intval($this->input->post('id'));

		$res = $this->model->delete($id);
		if ($res)
			return $this->meret($id, MERET_OK, '标签删除成功');
		else 
			return $this->meret($id, MERET_SVRERROR, '标签删除失败');		// 返回错误信息
	}

}

/* End of file tag.php */
/* Location: ./application/controllers/common/tag.php */