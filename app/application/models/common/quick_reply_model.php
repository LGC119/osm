<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 智库模型
*/
class Quick_reply_model extends ME_Model {

	public function __construct()
	{
		parent::__construct();
	}

	// 添加一条智库记录
	public function add ($p)
	{
		$res = $this->_verify_data($p);
		if (is_string($res)) 
			return $res;

		$data = array(
			'question' => $res['q'],
			'answer' => $res['a'],
			'created_at' => date('Y-m-d H:i:s'),
			'company_id' => $this->session->userdata('company_id')
		);
		$this->db->insert('quick_reply', $data);
		$id = $this->db->insert_id();
		if ($id > 0)
			return array_merge($data, array('id'=>$id));
		else 
			return '添加至数据库失败，请稍后尝试！';
	}

	// 修改一条记录
	public function edit ($id, $p)
	{
		$res = $this->_verify_data($p, $id);
		if (is_string($res)) 
			return $res;

		$data = array(
			'question' => $res['q'], 
			'answer' => $res['a']
		);

		$this->db->set($data)->where('id', $id)->update('quick_reply');

		if ($this->db->affected_rows()) 
			return array_merge($data, array('id'=>$id));
		else 
			return '添加至数据库失败，请稍后尝试！';
	}

	// 校验数据
	private function _verify_data ($p, $id = 0) 
	{
		if ( ! isset($p['q'])) 
			return '请输入智库问题！';
		$question = trim($p['q']);
		$q_len = mb_strlen($question, 'UTF8');
		if ($q_len < 1 OR $q_len > 480) 
			return '问题字数请控制在480个字符之内！';

		if ( ! isset($p['a'])) 
			return '请输入智库问题！';
		$answer = trim($p['a']);
		$a_len = mb_strlen($answer, 'UTF8');
		if ($a_len < 1 OR $a_len > 1800) 
			return '问题字数请控制在1800个字符之内！';

		$where = array (
			'company_id' => $this->session->userdata('company_id'), 
			'question' => $question
		);
		if ($id > 0) $where['id <>'] = intval($id);
		$q_exsit = $this->db->select('id')
			->from('quick_reply')
			->where($where)
			->get()->row_array();
		if ($q_exsit) 
			return '完全相同的问题已经存在，请使用修改功能！';

		// 修改智库条目
		if ($id > 0) 
		{
			$qr = $this->db->select('id, question, answer')
				->from('quick_reply')
				->where(array ('company_id'=>$this->session->userdata('company_id'), 'id'=>$id))
				->get()->row_array();
			if ( ! $qr) 
				return '没有找到要修改的记录！';

			if ($qr['question'] == $question && $qr['answer'] == $answer) 
				return '问题和答案都没有修改！';
		}

		return array (
			'q' => $question, 
			'a' => $answer
		);
	}

}

/* End of file quick_reply_model.php */
/* Location: ./application/models/common/quick_reply_model.php */