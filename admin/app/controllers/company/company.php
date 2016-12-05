<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 品牌用户管理 <平台公司管理> 
 *
 **/
class Company extends ME_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('company/company_model', 'model');
	}

	/**
	 * 获取系统公司列表
	 **/
	public function get_list ()
	{
		$p = $this->input->post(NULL, TRUE);
		$list = $this->model->get_list($p);
		$data = array (
			'list' => $list
		);

		$this->meret($data);
	}

	/* 创建一个公司 */
	public function create () 
	{
		$name = trim($this->input->get_post('name'));

		if ($name == '') 
		{
			$this->meret('请填写公司名称！');
			return ;
		}

		/* 是否重复创建 */
		$name_exsit = $this->db->select('id')
			->where('name', $name)
			->get('company')->row_array();
		if ($name_exsit) 
		{
			$this->meret(NULL, MERET_SVRERROR, '公司名已经存在！');
			return ;
		}

		/* 创建公司 */
		$data = array (
			'name' => $name, 
			'is_available' => 1, 
			'is_deleted' => 0, 
			'allow_wb_account_num' => 20, 
			'allow_wx_account_num' => 20, 
			'allow_users_num' => 100000, 
			'allow_wb_keywords_set_num' => 10, 
			'is_deleted' => 0, 
			'created_at' => date('Y-m-d H:i:s')
		);

		/* 插入数据库 */
		$this->db->insert('company', $data);

		if ($this->db->insert_id()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '插入数据库失败！');
	}

	/* 删除公司 */
	public function delete ($id) 
	{
		$id = (int) $id;

		if ($id < 1) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '找不到要删除公司！');
			return ;
		}

		/* 获取公司信息 */
		$company = $this->db->select('c.id, COUNT(DISTINCT wba.id) AS weibo_accounts_num, COUNT(DISTINCT wxa.id) AS wechat_accounts_num')
			->from('company c')
			->join('wb_account wba', 'c.id = wba.company_id', 'left')
			->join('wx_account wxa', 'c.id = wxa.company_id', 'left')
			->where('wba.is_delete = 0 OR wba.is_delete IS NULL')
			->where('wxa.is_delete = 0 OR wxa.is_delete IS NULL')
			->where('c.id', $id)
			->group_by('c.id')
			->order_by('c.id', 'DESC')
			->get()->row_array();

		if ( ! $company) 
		{
			$this->meret(NULL, MERET_BADREQUEST, '没有找到该公司或已被删除！');
			return ;
		}

		if ($company['weibo_accounts_num'] > 0 OR $company['wechat_accounts_num'] > 0) 
		{
			$this->meret(NULL, MERET_SVRERROR, '该公司有绑定的微博微信账号，为保数据安全，暂时无法删除！');
			return ;
		}

		$this->db->where('id', $id)->delete('company');
		if ($this->db->affected_rows()) 
			$this->meret('OK');
		else 
			$this->meret(NULL, MERET_SVRERROR, '系统繁忙，请稍后尝试！');

		return ;
	}


}