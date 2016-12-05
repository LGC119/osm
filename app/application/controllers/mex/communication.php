<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情 [微信]
*/
class Communication extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		/* 可使用 $this->model 调用meo/communication里的函数 */
		$this->load->model('mex/communication_model', 'model');
		$this->load->config('common/operation');
	}

	/* 根据请求参数，获取communication信息 */
	public function get ($status) 
	{
		// $where = $this->_get_where($status);

		$limit = $this->_get_limit();
        $filter = $this->input->get_post('filterData');
		$mentions = $this->model->get_communications($status, $limit,$filter);

		if ( empty($mentions['feeds'])) 
			$this->meret(NULL, MERET_EMPTY);
		else 
			$this->meret($mentions);
	}

	/*
	** 根据请求参数，获取user信息
	** @param $status 记录状态
	*/
	public function get_user ($status) 
	{
		$limit = $this->_get_limit();
        $filter = $this->input->get_post('order');
		$mentions = $this->model->get_communications_user($status,$limit,$filter);
        
		if ( empty($mentions['users'])) 
			$this->meret(NULL, MERET_EMPTY);
		else 
			$this->meret($mentions);
	}

	private function _get_limit ()
	{

		$page = intval($this->input->get_post('current_page'));
		$perpage = intval($this->input->get_post('items_per_page'));

		$page = $page > 0 ? $page : 0;
		$perpage = ($perpage > 0 && $perpage < 80) ? $perpage : 20;

		/* limit参数 */
		return array('limit' => $perpage, 'start' => ($page - 1) * $perpage, 'current_page' => $page);
	}

	public function cmn_history()
	{
		$limit = $this->_get_limit();
		$history = $this->model->get_cmn_history($limit);

		if ( empty($history)) 
			$this->meret(NULL, MERET_EMPTY);
		else 
			$this->meret($history);
	}

}

/* End of file communication.php */
/* Location: ./application/controllers/meo/communication.php */
