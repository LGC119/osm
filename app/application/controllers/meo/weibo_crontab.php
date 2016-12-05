<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Weibo_Crontab extends ME_Controller {
	public $wbapiObj;       // API 接口对象
	private $_wb_aid;       // 微博账号ID
	private $_oainfo;       // OAuth信息(APP_KEY, APP_SECRET, ACCESS_TOKEN, REFRESH_TOKEN)

	public function __construct($paramsData = '')
	{
		parent::__construct();
		/* 初始信息 */  
		if ($this->input->is_cli_request()) {
			/* 1. [从命令行初始化] */
			$args = array();
			parse_str($paramsData, $args);
			if (empty($args) OR ! isset($args['wb_aid'])) exit('no params given !');
			$this->_wb_aid = $args['wb_aid'];
		} else if ($this->session->userdata('wb_aid')) {
			/* 2. [从Session初始化]*/
			$this->_wb_aid = $this->session->userdata('wb_aid');
		} else if ($this->input->get_post('wb_aid')) {
			/* 3. [从URL初始化] */
			$this->_wb_aid = $this->input->get_post('wb_aid');
		} else {
			/* 4. [初始化失败] */
			exit('no params given !');
		}

		$this->load->helper('api');
		$this->load->model('system/account_model', 'account');
		$this->load->model('meo/communication_model', 'communication');
		$this->_oainfo = $this->account->get_oa_info($this->_wb_aid);
		$this->wbapiObj = get_wb_api($this->_oainfo);
		$this->load->model('meo/wb_send_crontab_model', 'wb_send_crontab');
	}
	public function get_crontab_list()
	{
		$params = $this->input->post(NULL, TRUE);

		$crontabs = $this->wb_send_crontab->get_crontabs($params);
		if (is_string($crontabs)) 
		{
			$this->meret(NULL, MERET_EMPTY, $crontabs);
			return ;
		}

		if (is_array($crontabs['crontabs'])) {
			foreach ($crontabs['crontabs'] as &$val) {
				if ($val['sid']) {
					$repost_rst = $this->wbapiObj->show($val['sid']);
					$val['wb_info'] = $repost_rst;
					$val['wb_info']['created_at'] = date('Y-m-d H:i:s', strtotime($repost_rst['created_at']));
					if (isset($val['retweeted_status']))
						$val['retweeted_status']['created_at'] = date('Y-m-d H:i:s', strtotime($val['retweeted_status']['created_at']));
				}
			}
		}

		$crontabs['account'] = $this->wb_send_crontab->get_account_info($this->_wb_aid);
		$this->meret($crontabs);
	}

	public function edit_crontab()
	{
		$params = $this->input->post(NULL, TRUE);
		$id = intval($params['id']);
		unset($params['id']);
		$fields = $this->wb_send_crontab->list_fields('wb_send_crontab');
		if ($params['pic_path']) {
			$params['pic_path'] = '../'.$params['pic_path'];
		}
		foreach ($params as $key => $val)
		{
			if (!in_array($key, $fields))
			{ 
				unset($params[$key]);
				continue;
			}
			if ('send_at' == $key || 'set_at' == $key)
				$params[$key] = strtotime($val);
		}

		$rs = $this->wb_send_crontab->update('wb_send_crontab', array('id' => $id), $params);
		if ($rs)
		{
			$status = MERET_OK;
		}
		else
		{
			$status = MERET_DBERR;
		}
		$this->meret($rs, $status);
	}

	public function delete_crontab()
	{
		$params = $this->input->post(NULL, TRUE);
		$id = intval($params['id']);
		$rs = $this->wb_send_crontab->delete('wb_send_crontab', array('id' => $id));
		if ($rs)
		{
			$status = MERET_OK;
		}
		else
		{
			$status = MERET_DBERR;
		}
		$this->meret($rs, $status);
	}

}