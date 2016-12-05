<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 高级营销活动模型
 */
class Event extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mei/event_model', 'model');
	}

	/* 创建高级营销活动 */
	public function create () 
	{
		$params = $this->input->post(NULL, TRUE);
		$params = $this->_verify_params($params);

		// print_r($params);
		// return ;

		/* 验证失败 */
		if (is_string($params)) 
		{
			$this->meret(NULL, MERET_BADREQUEST, $params);
			return ;
		}

		/* 获取活动组成员信息[如果组成员为空，不进行推送！] */
		$group_members_count = $this->db->select('COUNT(*) AS num')
			->where('group_id', $params['group_id'])
			->get('rl_group_user')->row_array();
		if ( ! $group_members_count OR ! $group_members_count['num'])
		{
			$this->meret(NULL, MERET_BADREQUEST, '所选组没有用户，不能创建活动！');
			return ;
		}

		/* 插入活动基本信息 */
		$event_info = $this->model->insert_event($params['info']);
		if (is_string($event_info)) 
		{
			$this->meret(NULL, MERET_BADREQUEST, $event_info);
			return ;
		}

		/* 插入活动标签信息 */
		if (isset($params['tags']) && is_array($params['tags'])) 
			$this->model->insert_tags($event_info['id'], $params['tags']);

		/* 获取活动微博, 微信推送信息 */
		$push_content['wb'] = $this->model->get_wb_push_text($params['set'], $params['info']['page_id']);
		$push_content['wx'] = $this->model->get_wx_push_text($params['set'], $params['info']['page_id']);

		/* 推送用户信息入库 */
		$participants_info = $this->model->insert_participants($event_info, $params, $push_content);

		$this->meret('OK');
		return ;
	}

	/* 验证提交的活动创建的参数 */
	private function _verify_params ($params) 
	{
		$group_id = isset($params['group_id']) ? (int) $params['group_id'] : 0;
		if ( ! $group_id > 0) return '请选择一个有效的用户组！';
		$params['group_id'] = $group_id;

		/* 基本信息验证 */
		$info = isset($params['info']) ? $params['info'] : '';
		$set = isset($params['set']) ? $params['set'] : '';
		if ( ! is_array($set) OR  ! is_array($info)) return '提交参数不正确！';

		/* 活动信息验证 */
		if ( ! isset($info['name']) OR mb_strlen(trim($info['name']), 'UTF-8') < 2 OR mb_strlen(trim($info['name']), 'UTF-8') > 20)
			return '请填写活动名称，2~20个字符';
		if ( ! isset($info['start']) OR ! preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $info['start']))
			return '请填写活动开始时间！';
		if ( ! isset($info['end']) OR ! preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $info['end']))
			return '请填写活动开始时间！';
		if ( ! isset($info['page_id']) OR ! $info['page_id'] > 0)
			return '请选择一个有效的H5页面！';
		if ( ! isset($info['type']) OR ! in_array($info['type'], array(0, 1, 2, 3, 4))) 
			$info['type'] = 0;
		if ( ! isset($info['industry']) OR ! in_array($info['industry'], array(0, 1, 2, 3))) 
			$info['industry'] = 0;
		/* 活动设置验证 */
		// if ($set['wbIsText'])

		return $params;
	}

	/* 活动列表 */
	public function get_list () 
	{
		/* 获取筛选参数 */
		$type = $this->input->get_post('type');
		$status = $this->input->get_post('status');
		$title = $this->input->get_post('title');

		/* 分页参数 */
		$page = $this->input->get_post('page');
		$perpage = $this->input->get_post('perpage');

		$params = array (
			'page' => $page > 0 ? $page : 1, 
			'perpage' => $perpage > 0 && $perpage <= 20 ? $perpage : 10
		);

		if (in_array($type, array(0, 1, 2, 3))) 
			$params['from'] = $type;
		if (in_array($status, array (1, 2))) 
			$params['status'] = $status;
		if (trim($title)) 
			$params['title'] = $title;

		/* 获取用户列表 */
		$list = $this->model->get_list($params);

		if ($list && $list['events']) 
			$this->meret($list);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有筛选到用户！');
	}
}

/* End of file event.php */
/* Location: ./application/controller/mei/event.php */