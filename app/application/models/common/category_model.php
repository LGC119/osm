<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 舆情的分类模型
*/
class Category_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/* 获取现有分类数据 */
	public function get_all_categories ($company_id) 
	{
		$cats = $this->db->select('id, cat_name, parent_id, wb_threshold, wx_threshold, created_at')
			->from('category')
			->where('company_id', $company_id)
			->order_by('parent_id', 'ASC')
			->order_by('id', 'ASC')
			->get()->result_array();

		return $cats;
	}

	/*
	** 从缓存获取快速分类
	** @return array()
	** 		'category' => array(),		// 分类名称的键值对
	** 		'relation' => array()		// 分类关系的键值对
	** )
	*/
	public function get_quick_cats ($company_id) 
	{
		/* Cache解决方案待完善 */
		$this->load->driver('cache');

		if ( ! $categories = $this->cache->get('common/'.$company_id.'_categories.cache'))
			$categories = $this->_update_category_cache($company_id);
		
		return $categories;
	}

	/* 更新分类信息数据缓存 */
	private function _update_category_cache ($company_id) 
	{
		$all_cats = $this->get_all_categories($company_id);
		$category = array();
		$relation = array();

		if (empty($all_cats)) return '';

		foreach ($all_cats as $val) {
			$category[$val['id']] = $val;
			isset($relation[$val['parent_id']]) ? $relation[$val['parent_id']][] = $val['id'] : $relation[$val['parent_id']] = array($val['id']);
		}

		$categories = array( 'category' => $category, 'relation' => $relation );
		$this->cache->file->save('common/'.$company_id.'_categories.cache', $categories, 300);

		return $categories;
	}

	public function add_category ($company_id, $staff_id, $pid, $name)
	{
		$pid = intval($pid);
		$name = trim($name);

		$res = $this->_validate_name($name);
		if ($res !== TRUE) return $res;

		if ($pid > 0) 
		{
			$parent = $this->db->select('id')->where('id', $pid)->get('category')->row_array();
			if ( ! $parent) 
				return '父类信息不存在，请刷新后重试！';
		}

		$cat = array(
			'company_id' => $company_id,
			'cat_name' => $name,
			'parent_id' => $pid,
			'created_at' => date('Y-m-d H:i:s'),
			'add_staff_id' => $staff_id
		);
		
		$this->db->insert('category', $cat);
		if ($this->db->insert_id())
			return $cat;
		else 
			return '服务器忙，请稍后尝试！';
	}

	/* 修改分类名称 */
	public function edit_category ($cid, $id, $name) 
	{
		$res = $this->_validate_name($name, $id);
		if ($res !== TRUE) return $res;

		$cate = $this->db->select('cat_name')
			->where(array('company_id'=>$cid, 'id'=>$id))
			->get('category')->row_array();
			
		if ( ! $cate)
			return '没有找到该分类！';

		if ($cate['cat_name'] == $name) 
			return '新分类名不可以和原来的一样好吗！';

		$this->db->where('id', $id)->update('category', array('cat_name'=>$name));

		return $this->db->affected_rows() ? TRUE : '服务器忙，请稍后尝试！';
	}

	private function _validate_name ($name, $id = 0) 
	{
		if ( ! is_string($name))
			return '请提交文字类型名称！';

		if (mb_strlen($name, 'UTF8') < 1 OR mb_strlen($name, 'UTF8') > 20)
			return '分类名称在1~20个字符之内！';

		if ( ! preg_match('/^[a-zA-Z\d\x{0391}-\x{FFE5}]+$/u', $name))
			return '分类名可包含中文、英文和数字，请不要包含特殊字符或空格！';

		$duplicate = $this->db->select('id')
			->where(array('id <>'=>$id, 'cat_name'=>$name))
			->get('category')->row_array();

		if ($duplicate) 
			return '相同名称的分类已经存在！';

		return TRUE;
	}

	/* 删除分类 */
	public function del_category ($cat_id, $cid = 0)
	{
		$cat_id = intval($cat_id);
		if ($cat_id < 1) 
			return '没有找到分类！';
		
		if ( ! $cid) 
			$cid = $this->session->userdata('company_id');

		$this->db->where(array('id'=>$cat_id, 'company_id'=>$cid))
			->or_where('parent_id', $cat_id)
			->delete('category');

		return $this->db->affected_rows() ? TRUE : '删除失败，请刷新后尝试！';
	}

}

/* End of file category.php */
/* Location: ./application/controllers/common/category.php */