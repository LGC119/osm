<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 智库控制器
*/
class Quick_reply extends ME_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('common/quick_reply_model', 'model');
	}

	public function get_qrs () 
	{
		$data = array();
		
		$current_page = $this->input->get_post('current_page');
        $items_per_page = $this->input->get_post('items_per_page');

        $current_page = intval($current_page) > 0 ? intval($current_page) : 1;
        $items_per_page = intval($items_per_page) > 0 ? intval($items_per_page) : 20;

        $this->db->select('count(id) sum', false);
    	$this->db->from('quick_reply');
		$this->db->where('company_id', $this->cid);
		$this->_set_where();
		$rst = $this->db->get()->row_array();
		$total_number = $rst['sum'];


		$this->db->select('id, question, answer, created_at');
		$this->db->from('quick_reply');
		$this->db->where('company_id', $this->cid);
		$this->_set_where();
		$this->db->order_by('id', 'desc');

		if ($current_page > ceil($total_number / $items_per_page)) {
            $this->db->limit($items_per_page);
        } else {
        	$this->db->limit($items_per_page, ($current_page - 1) * $items_per_page);
        }

		$qrs = $this->db->get()->result_array();
		if ($qrs)
		{
			$data['qrs'] = $qrs;
			$data['current_page'] = $current_page;
			$data['items_per_page'] = $items_per_page;
			$data['total_number'] = $total_number;
			$this->meret($data);
		}
		else
		{
			$this->meret(NULL, MERET_EMPTY, '没有智库记录！');
		} 
	}

	protected function _set_where()
	{
		$keyword = $this->input->get('keyword');
		if ($keyword) 
		{
			$keyword = trim($keyword);
			$this->db->where("CONCAT(question, answer) LIKE '%{$keyword}%'");
		}
	} 

	public function add () 
	{
		$p = $this->input->post(NULL, TRUE);
		$res = $this->model->add($p);

		if (is_string($res)) 
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	public function edit ($id) {
		$p = $this->input->post(NULL, TRUE);
		$res = $this->model->edit($id, $p);

		if (is_string($res)) 
			$this->meret(NULL, MERET_BADREQUEST, $res);
		else 
			$this->meret($res);
	}

	public function delete ($id) {
		$this->db->where('id', $id)->delete('quick_reply');

		if ($this->db->affected_rows()) 
			$this->meret('success');
		else 
			$this->meret(NULL, MERET_BADREQUEST, '删除失败，请刷新检查！');
	}

}

/* End of file quick_reply.php */
/* Location: ./application/controllers/common/quick_reply.php */