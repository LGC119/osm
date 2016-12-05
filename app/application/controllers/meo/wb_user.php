<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 微博用户相关操作
*/
class Wb_user extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('meo/wb_user_model', 'model');
	}

	/* 获取粉丝 */
	public function get_followers ($wb_aid = 0)
	{
		if ( ! $wb_aid) 
			$wb_aid = $this->session->userdata('wb_aid');

		$followers = $this->db->select('u.id')
			->from('wb_account_user au')
			->join('wb_user u', 'au.user_weibo_id=u.user_weibo_id')
			->where('au.wb_aid', $wb_aid)
			->limit(20)
			->get()->result_array();

		foreach ($followers as &$val) 
			$val = $this->model->get_detailed_info($val['id']);

		if ($followers)
			$this->meret($followers);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有粉丝信息！');
	}

	/* 筛选微博用户 */
	public function get_list () 
	{
		$list = $this->model->get_list();

		if ( ! is_string($list))
			$this->meret($list);
		else 
			$this->meret(NULL, MERET_EMPTY, $list); // 错误信息
	}

	public function get_filter_params () 
	{
		$filters = array();

		/* 基本条件 */
		$this->load->config('meo/wb_user');
		$filters['basic'] = $this->config->item('filter_params');
		$this->load->model('system/staff_model');
		$all_accounts = $this->staff_model->get_company_accounts($this->cid);
		$filters['account'] = array();
		if ($all_accounts['wb_accounts']) {
			foreach ($all_accounts['wb_accounts'] as $val) {
				$filters['account'][] = array('key'=>$val['id'], 'val'=>$val['screen_name']);
			}
		}

		$this->meret($filters);
	}

	public function get_one ($id) 
	{
		$id = intval($id);
		$user = $this->model->get_one($id);

		if ( ! is_string($user)) 
			$this->meret($user);
		else 
			$this->meret(NULL, MERET_EMPTY, $user);
	}

	/* 手动用户标签 */
	public function tag_user ($tags) 
	{
		$this->model->tag_user($user_id, $tags);
		$this->meret(TRUE); 
	}

	/* 交流历史 */
	public function communications () 
	{
		$user_id = (int) $this->input->get_post('wb_user_id');
		$user_weibo_id = trim($this->input->get_post('user_weibo_id'));
		$wb_aid = $this->session->userdata('wb_aid');
		$limit = array (
			'page' => $this->input->get_post('current_page'),
			'perpage' => $this->input->get_post('items_per_page')
		);

		if ( ! $user_weibo_id) {
			if ($user_id > 0) {
				$user = $this->db->select('user_weibo_id')
					->from('wb_user')
					->where('id', $user_id)
					->get()->row_array();
				if ($user)
					$user_weibo_id = $user['user_weibo_id'];
			}
		}

		if ( ! $user_weibo_id) {
			$this->meret(NULL, MERET_BADREQUEST, '无法找到该用户！');
			return ;
		}

		$communications = $this->model->communications($user_weibo_id, $wb_aid, $limit);

		if ($communications)
			$this->meret($communications);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有该用户的交流记录！');
	}

	/* 筛选用户页面，沟通记录筛选 */
	public function get_timeline () 
	{
		$p = $this->input->post(NULL, TRUE);

		$key = isset($p['keyword']) ? trim($p['keyword']) : '';
		$aid = $this->session->userdata('wb_aid');
		$where = array ('wb_aid'=>$aid, 'weibo_id <>'=>0);
		$total_num = $this->db->from('wb_user_timeline')
			->where($where)
			->like('text', $key)
			->get()->num_rows();

		$this->db->select('id, weibo_id, text, wb_info, created_at')
			->from('wb_user_timeline')
			->where($where)
			->like('text', $key);

		$page = intval($p['current_page']) > 0 ? intval($p['current_page']) : 0;
		$perpage = (intval($p['items_per_page']) > 0 && intval($p['items_per_page']) < 20) ? intval($p['items_per_page']) : 10;

		if ($page > ceil($total_num / $perpage)) 
			$this->db->limit($perpage);
		else 
			$this->db->limit($perpage, ($page - 1) * $perpage);

		$timeline = $this->db->get()->result_array();

		$ret = array (
			'timeline' => $timeline, 
			'current_page' => $page,
			'items_per_page' => $perpage,
			'total_number' => $total_num
		);

		if ($timeline) 
			$this->meret($ret);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有微博记录！');

		return ;
	}
}

/* End of file wb_user.php */
/* Location: ./application/controllers/meo/wb_user.php */