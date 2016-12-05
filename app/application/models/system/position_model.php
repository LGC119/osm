<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** position 职位(角色)模型 
**
*/
class Position_model extends ME_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	public function get_list ($company_id) 
	{
		$company_id = intval($company_id);
		$positions = $this->db->select('p.id, name, created_at, created_staff', FALSE)
			->select('GROUP_CONCAT(DISTINCT rpp.permission_id) AS authidstr')
			->select('GROUP_CONCAT(DISTINCT rpm.menu_id) AS menuidstr')
			->from('position p')
			->join('rl_position_permission rpp', 'p.id = rpp.position_id', 'left')
			->join('rl_position_menu rpm', 'p.id = rpm.position_id', 'left')
			->where(array('company_id'=>$company_id, 'is_deleted'=>0))
			->group_by('p.id')
			->get()->result_array();

		if ( ! $positions) return FALSE;
		/* 格式化每个职位的菜单和权限项数据 */
		foreach ($positions as &$position) 
		{
			$position['menuids'] = array ();
			$position['authids'] = array ();

			if ($position['menuidstr']) 
				foreach (explode(',', $position['menuidstr']) as $id) 
					$position['menuids'][intval($id)] = TRUE;

			if ($position['authidstr']) 
				foreach (explode(',', $position['authidstr']) as $id) 
					$position['authids'][intval($id)] = TRUE;

			unset($position['menuidstr'], $position['authidstr']);
		}

		return $positions;
	}

	/*
	** 添加一个职位
	*/
	public function create ($name, $menuids, $authids)
	{
		$name_check = $this->_verify_name(trim($name));
		if ($name_check !== TRUE) return $name_check;

		if (empty($menuids) OR empty($authids)) return '请选择该职位的菜单和权限项！';
		/* 创建职位 */
		$position = array (
			'company_id' => $this->session->userdata('company_id'), 
			'name' => $name, 
			'created_at' => date('Y-m-d H:i:s'), 
			'created_staff' => $this->session->userdata('staff_id'), 
			'is_deleted' => 0
		);
		$this->db->insert('position', $position);

		$position_id = $this->db->insert_id();
		if ( ! $position_id) return '创建职位失败，请稍后尝试！';

		/* 添加职位菜单 */
		$menuids = $this->_get_valid_ids($menuids);
		foreach ($menuids as $id) 
			$rpm[] = array ('position_id' => $position_id, 'menu_id'=>intval($id));
		$this->db->insert_batch('rl_position_menu', $rpm);

		/* 添加职位权限 */
		$authids = $this->_get_valid_ids($authids);
		foreach ($authids as $id) 
			$rpp[] = array ('position_id' => $position_id, 'permission_id'=>intval($id));
		$this->db->insert_batch('rl_position_permission', $rpp);

		return TRUE;
	}

	/*
	** 修改职位信息
	*/
	public function modify ($position_id, $name, $menuids, $authids)
	{
		$name = trim($name);
		$name_check = $this->_verify_name($name, $position_id);
		if ($name_check !== TRUE) return $name_check;

		if (empty($menuids) && empty($authids)) return '请选择该职位的菜单和权限项！';

		$where = array('id'=>$position_id, 'company_id'=>$this->session->userdata('company_id'));
		$position = $this->db->select('name')->where($where)->get('position')->row_array();
		if ( ! $position) return '没有找到您要修改的记录！';

		/* 修改职位名称 */
		if ($position['name'] != $name)
			$this->db->set('name', $name)->where('id', $position_id)->update('position');

		/* 修改职位的菜单 */
		$this->db->where('position_id', $position_id)->delete('rl_position_menu');
		if ($menuids) 
		{
			$menuids = $this->_get_valid_ids($menuids);
			foreach ($menuids as $id) 
				$rpm[] = array ('position_id' => $position_id, 'menu_id'=>$id);
			if ($rpm) $this->db->insert_batch('rl_position_menu', $rpm);
		}

		/* 修改职位的权限 */
		$this->db->where('position_id', $position_id)->delete('rl_position_permission');
		if ($authids) 
		{
			$authids = $this->_get_valid_ids($authids);
			foreach ($authids as $id) 
				$rpp[] = array ('position_id' => $position_id, 'permission_id'=>$id);
			if ($rpp) $this->db->insert_batch('rl_position_permission', $rpp);
		}

	}

	/* 校验用户提交职位名称 */
	private function _verify_name ($name, $pos_id = 0)
	{
		/* 职位名称校验 */
		if ( (mb_strlen($name, 'UTF-8') < 2 OR mb_strlen($name, 'UTF-8') > 20))
			return '请填写职位名称，2~20个字符';

		// 重复命名校验
		$exsit = $this->db->where(array('name'=>trim($name), 'id <>'=>$pos_id))->get('position')->row_array();
			if ($exsit) return '职位 “' . $name . '” 已经存在！';

		return TRUE;
	}

	/* 获取前台提交的菜单项及权限项ID */
	private function _get_valid_ids ($array) 
	{
		$ret = array ();
		foreach ($array as $key => $val) 
			if (intval($key) > 0 && $val == 'true') $ret[] = intval($key);

		return $ret;
	}

	/*
	** 删除职位记录
	*/
	public function delete ($position_id)
	{
		$position_id = intval($position_id);
		$this->db->delete('position', array('id'=>$position_id, 'company_id'=>$this->session->userdata('company_id')));

		if ( ! $this->db->affected_rows()) 
			return '职位不存在或已被删除，请刷新查看！';

		$this->db->delete('rl_position_permission', array('position_id'=>$position_id));
		$this->db->delete('rl_position_menu', array('position_id'=>$position_id));
		return TRUE;
	}

	public function get_position_info ($position_id)
	{
		$position_id = intval($position_id);

		$info = $this->db->select('id')
			->get_where('position', array(
				'company_id'=>$this->session->userdata('company_id'),
				'id'=>$position_id
			))->row_array();
		return $info;
	}
	
	/**
	 * 获取该职位的菜单和权限项
	 * return array (
	 * 		'menu'=>array(menuid=>TRUE, menuid=>TRUE, ...),
	 * 		'auth'=>array(authid=>TRUE, authid=>TRUE, ...)
	 * );
	 **/
	public function get_menu_auth ($position_id, $company_id) 
	{
		$data = $this->db->select('GROUP_CONCAT(DISTINCT rpm.menu_id) AS menuids')
			->select('GROUP_CONCAT(DISTINCT rpp.permission_id) AS authids')
			->from('position p')
			->join('rl_position_menu rpm', 'rpm.position_id = p.id', 'left')
			->join('rl_position_permission rpp', 'rpp.position_id = p.id', 'left')
			->where("rpm.menu_id IN (SELECT menu_id FROM {$this->db->dbprefix('rl_company_menu')} rcm WHERE rcm.company_id = {$company_id})", NULL, FALSE)
			->where("rpp.permission_id IS NULL OR rpp.permission_id IN (SELECT permission_id FROM {$this->db->dbprefix('rl_company_menu')} rcm WHERE rcm.company_id = {$company_id})", NULL, FALSE)
			->where(array('p.id'=>$position_id))
			->get()->row_array();

		if ( ! $data) return FALSE;

		// print_r($this->db->last_query());
		/* 格式化为数组 */
		$ret_arr = array ('menu'=>array(), 'auth'=>array());

		if ($data['menuids']) {
			$menuids = explode(',', $data['menuids']);
			foreach ($menuids as $id) 
				$ret_arr['menu'][intval($id)] = TRUE;
		}

		if ($data['authids']) {
			$authids = explode(',', $data['authids']);
			foreach ($authids as $id) 
				$ret_arr['auth'][intval($id)] = TRUE;
		}

		return ($ret_arr['menu'] OR $ret_arr['auth']) ? $ret_arr : FALSE;
	}


}