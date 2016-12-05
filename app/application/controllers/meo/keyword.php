<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 舆情关键词设置 控制器
 *
 * cmn_type 值设定
 +---------------------------------------
 *  @我的	评论	关键词	| cmn_type值
 +  0		0		0		| 0
 *  0		0		1		| 1
 +  0		1		0		| 2
 *  0		1		1		| 3
 +  1		0		0		| 4
 *  1		0		1		| 5
 +  1		1		0		| 6
 *  1		1		1		| 7
 +---------------------------------------
 *
 */
class Keyword extends ME_Controller 
{

	public function __construct() 
	{
		parent::__construct();
		$this->load->model('meo/keyword_model', 'model');
		$this->load->config('common/keyword');
		// register_shutdown_function("keyword_model::update_auto_keywords_cache({$this->cid})");
	}

	/** 
	 * @function get_list 获取关键词列表 
	 *
	 * @param $type int 获取关键词的类型
	 * @param $page $_REQUEST 当前页面
	 * @param $items_per_page $_REQUEST 每页显示条数
	 * 
	 * @return array 关键词列表或错误信息
	 */
	public function get_list ($type)
	{
		$type = (int) $type;

		// 获取分页参数
		$page = (int) $this->input->get_post('current_page');
		$perpage = (int) $this->input->get_post('items_per_page');
		$page = $page > 0 ? $page : 1;
		$perpage = $perpage > 0 && $perpage < 20 ? $perpage : 10;

		$where = array ('company_id'=> $this->cid, 'type'=>$type, 'is_deleted'=>0);
		$total_num = $this->db->where($where)->get('wb_keyword')->num_rows();

		if ($page > ceil($total_num / $perpage)) $page = 1;

		$cmn_types_count = $this->config->item('cmn_types_count');
		$list = $this->db->select('id, staff_id, staff_name, text, type, created_at, total_threshold, status')
			->select("LPAD(BIN(`cmn_type`), {$cmn_types_count}, 0) AS cmn_type", FALSE)
			->from('wb_keyword')
			->where($where)
			->limit($perpage, ($page - 1) * $perpage)
			->order_by('created_at', 'DESC')
			->get()->result_array();

		if ($type == 0 && $list) 
		{
			foreach ($list as &$val) 
			{
				$total = $this->db->select('COUNT(id) AS num')
					->where(array('type'=>2, 'keyword_id'=>$val['id']))
					->get('wb_communication')->row_array();

				$val['total'] = $total ? $total['num'] : 0;
				if ($val['total_threshold'] > 0) 
				{
					$percent = $val['total'] / $val['total_threshold'];
					/* -绿灯- | 0.9 | -黄灯- | 1 | -红灯- */
					if ($percent < 0.9) 
						$val['level'] = 'green';
					else if ($percent < 1) 
						$val['level'] = 'orange';
					else 
						$val['level'] = 'red';
				}
			}
		}

		$data = array (
			'list'=>$list, 
			'current_page' => $page,
			'items_per_page' => $perpage,
			'total_number' => $total_num
		);

		if ($list)
			$this->meret($data);
		else 
			$this->meret(NULL, MERET_BADREQUEST, '没有设置关键词！');
		return ;
	}

	/* 添加关键词 */
	public function add () 
	{
		$p = $this->input->post(NULL, TRUE);

		$keyword = $p['keyword'];
		$type = $p['type'];
		$cmn_type = $p['cmn_type'];

		$res = $this->model->add($keyword, $type, $cmn_type);
		$this->model->update_auto_keywords_cache($this->cid);

		if (is_string($res)) 
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);

		$this->model->update_auto_keywords_cache($this->cid);
		return ;
	}

	/* 批量删除关键词操作 */
	public function delete_batch () 
	{
		$ids = $this->input->get_post('ids'); 			// 要删除的关键词ID

		if ( ! is_array($ids)) 
			$ids = array (intval($ids));

		/* 执行批量删除操作 */
		$this->db->where(array('company_id'=>$this->cid, 'type <>'=>0))
			->where_in('id', $ids)
			->set('is_deleted', 1)
			->update('wb_keyword');

		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_BADREQUEST, '删除操作失败，请稍后尝试！');

		$this->model->update_auto_keywords_cache($this->cid);
		return ;
	}

	/* 删除关键词操作 */
	public function delete ($id) 
	{
		$id = isset($id) ? (int) $id : 0;
		if ($id < 1) {
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要删除的关键词！');
			return ;
		}

		$keyword = $this->db->select('vdong_id')
			->from('wb_keyword')
			->where(array ('company_id'=>$this->cid, 'id'=>$id))
			->get()->row_array();

		if ( ! $keyword) {
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要删除的关键词！');
			return ;
		}

		// 删除VDong关键词记录
		if ($keyword['vdong_id'] > 0) 
		{
			$this->load->library('vdong');
			$this->vdong->delete($keyword['vdong_id']);
		}

		/* 删除表中记录 */
		$this->db->where('id', $id)->set('is_deleted', 1)->update('wb_keyword');
		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '无法删除关键词记录，请稍后尝试！');

		$this->model->update_auto_keywords_cache($this->cid);
		return ;
	}

	/* 修改关键词状态 */
	public function change_status ($id)
	{
		$id = (int) $id;
		if ($id < 1) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有该条关键词记录！');
			return ;
		}

		$keyword = $this->db->select('status')
			->from('wb_keyword')
			->where(array ('company_id'=>$this->cid, 'id'=>$id))
			->get()->row_array();

		if ( ! $keyword) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有该关键词记录！');
			return ;
		}

		$status = $keyword['status'] == 1 ? 0 : 1;
		$this->db->set('status', $status)
			->where('id', $id)
			->update('wb_keyword');

		if ($this->db->affected_rows())
			$this->meret($status);
		else 
			$this->meret(NULL, MERET_SVRERROR, '修改关键词状态失败！');

		$this->model->update_auto_keywords_cache($this->cid);
		return ;
	}

	/* 修改监控关键词阈值 */
	public function edit_threshold () 
	{
		$id = (int) $this->input->get_post('id');
		$threshold = (int) $this->input->get_post('threshold');

		if ($id < 1 OR $threshold < 0) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '输入参数不正确！');
			return ;
		}

		$keyword_info = $this->db->select('total_threshold')
			->from('wb_keyword')
			->where(array('type'=>0, 'id'=>$id, 'company_id'=>$this->cid))
			->get()->row_array();

		if ( ! $keyword_info) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到您要修改的关键词信息！');
			return ;
		}

		if ($keyword_info['total_threshold'] == $threshold) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '阈值设置没有变化！');
			return ;
		}

		/* 修改关键词阈值 */
		$this->db->set('total_threshold', $threshold)
			->where('id', $id)
			->update('wb_keyword');

		if ($this->db->affected_rows())
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '修改失败，请稍后尝试！');

		return ;
	}

}

/* End of file keyword.php */
/* Location: ./application/controllers/meo/keyword.php */