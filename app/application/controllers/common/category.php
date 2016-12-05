<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Category extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('common/category_model', 'model');
	}

	/* 获取现有分类数据 */
	public function index () 
	{
		$cats = $this->model->get_all_categories($this->cid);

		if (empty($cats))
			$this->meret(NULL, MERET_EMPTY);
		else 
			$this->meret($cats);
	}

	/*
	** 从缓存获取快速分类
	** @return array (
	** 		'category' => array(),		// 分类数据的键值对
	** 		'relation' => array()		// 分类关系的键值对
	** )
	*/
	public function get_quick_cats () 
	{
		$res = $this->model->get_quick_cats($this->cid);

		if (is_string($res)) {
			$this->meret(NULL, MERET_EMPTY, $res);
		} else {
			$this->meret($res);
		}
	}

	public function add_category ()
	{
		$pid = intval($this->input->get_post('pid'));
		$name = trim($this->input->get_post('name'));

		$res = $this->model->add_category($this->cid, $this->sid, $pid, $name);
		if (is_array($res))
			return $this->meret($res);
		else 
			return $this->meret(NULL, MERET_SVRERROR, $res);		// 返回错误信息
	}

	public function edit ($id) 
	{
		$id = intval($id);
		$name = trim($this->input->post('name'));

		$res = $this->model->edit_category($this->cid, $id, $name);

		if (is_string($res))
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);

		return ;
	}

	public function delete ($cat_id) 
	{
		$res = $this->model->del_category($cat_id, $this->cid);

		if (is_string($res))
			$this->meret(NULL, MERET_SVRERROR, $res);
		else 
			$this->meret(NULL);
	}

	/* 修改信息监控警戒值 */
	public function edit_threshold () 
	{
		$id = (int) $this->input->get_post('id');
		$key = $this->input->get_post('key');
		$val = (int) $this->input->get_post('val');

		if ($val < 0) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '请设定一个大于等于零的整数！');
			return ;
		}

		if ($key != 'wb_threshold' && $key != 'wx_threshold') 
		{
			$this->meret(NULL, MERET_BADREQUEST, '请求参数不正确！');
			return ;
		}

		$category = $this->db->select($key)
			->where(array('company_id'=>$this->cid, 'id'=>$id, 'parent_id >'=>0))
			->get('category')->row_array();

		if ( ! $category) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要修改的分类！');
			return ;
		}

		if ($category[$key] == $val) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有修改！');
			return ;
		}

		$this->db->set($key, $val)->where('id', $id)->update('category');
		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '操作失败，请稍后尝试！');

		return ;
	}

}

/* End of file category.php */
/* Location: ./application/controllers/common/category.php */