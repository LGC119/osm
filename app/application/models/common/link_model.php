<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 图文信息的链接模型
*/
class Link_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * insert_link 插入一条链接信息
	 * @param $data = { company_id:xxx, aid:xxx, user_id:xxx, cmn_id:xxx, media_id:xxx, url:xxx, type:'wb'|'wx'};
	 * @return $link array <插入数据库的信息>
	**/
	public function insert_link ($data)
	{
		$type = $data['type'] == 'wb' ? 'wb' : 'wx';
		if ($type == 'wb')
			$user_id = $this->get_wb_user_id($data['user_weibo_id']);
		else 
			$user_id = $this->get_wx_user_id($data['openid']);

		if ( ! $user_id > 0)
			return $data['url'];

		$link_exsit = $this->db->select('id')
			->from('link')
			->where(array ('aid'=>$data['aid'], 'user_id'=>$user_id, 'media_id'=>$data['media_id'], 'cmn_id'=>$data['cmn_id']))
			->get()->row_array();
		if ($link_exsit) {
			$id = $link_exsit['id'];
		} else {
			$data = array (
				'company_id' 	=> $data['company_id'],
				'aid' 			=> $data['aid'],
				'user_id' 		=> $user_id,
				'cmn_id' 		=> $data['cmn_id'],
				'media_id' 		=> $data['media_id'],
				'url' 			=> $data['url'],
				'short_url' 	=> $this->get_short_url($data['url']),
				'type' 			=> $type,
				'created_at' 	=> date('Y-m-d H:i:s')
			);

			$this->db->insert('link', $data);
			$id = $this->db->insert_id();
		}

		return $id > 0 ? $this->get_link_url($id) : $data['url'];
	}

	// 获取短链的方法
	public function get_short_url ($url) 
	{
		$surl = $url;
		return $surl;
	}

	/* 根据用户openid获取用户在wb_user中的ID */
	public function get_wb_user_id ($user_weibo_id) 
	{
		$user = $this->db->select('id')
			->from('wb_user')
			->where('user_weibo_id', $user_weibo_id)
			->get()->row_array();

		return $user ? $user['id'] : FALSE;
	}

	/* 根据用户openid获取用户在wx_user中的ID */
	public function get_wx_user_id ($open_id) 
	{
		$user = $this->db->select('id')
			->from('wx_user')
			->where('openid', $open_id)
			->get()->row_array();

		return $user ? $user['id'] : FALSE;
	}

	/* 获取链接的访问地址 */
	public function get_link_url ($link_id)
	{
		return base_url() . 'index.php/common/link?id=' . $link_id;
	}

	/* 生成短链 <TODO:测试完善> */
	private function _get_short_url( $url ){
		$base = 'abcdefghijklmnopqrstuvwxyz012345';
		$output = array();
		$md5 = md5($url);
		$arr = str_split($md5, 8);
		foreach($arr as $val){
			$hex=1*('0x'.$val);
			$hex=0x3fffffff & $hex;
			$out='';
			for($i=0;$i<6;++$i){
				$val=0x1f&$hex;
				$out.=$base[$val];
				$hex=$hex>>5;
			}
			$output[]=$out;
		}
		return $output[0];
	}

}

/* End of file link.php */
/* Location: ./application/controllers/common/link.php */