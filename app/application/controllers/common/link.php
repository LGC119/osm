<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 用户点击图文链接
*/
class Link extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 用户点击图文消息，更新标签命中量
	 * $_GET参数 {id:url表ID, uid:用户在微博，微信表中的ID}
	 * @return header('Location:{$url}');
	**/
	public function index () 
	{
		$id = intval($_GET['id']);

		$link_info = $this->db->select('aid, company_id, user_id, cmn_id, media_id, url, short_url, type, hits')
			->from('link')
			->where('id', $id)
			->get()->row_array();

		// 没有找到该页面
		if ( ! $link_info) 
		{
			echo "页面不见了( ⊙ o ⊙ )啊！！";
			return ;
		}

		// 更新链接点击量 (link)
		$this->db->set('hits', 'hits + 1', FALSE)->where('id', $id)->update('link');

		// 获取图文的标签 (rl_media_tag)
		$tags = $this->db->select("GROUP_CONCAT(tag_id SEPARATOR ',') AS tag_ids", FALSE)
			->FROM('rl_media_tag')
			->where('media_id', $link_info['media_id'])
			->get()->row_array();

		if ($tags) {
			$tag_ids = explode(',', $tags['tag_ids']);
			// 获取当前用户点击的加权值 weight
			$weight = $this->get_hit_weight ($link_info['cmn_id']);

			// 每个标签加权值记录数据库 (rl_wx_user_tag) | (rl_wb_user_tag) 
			$t = $link_info['type']; # (wb | wx)

			foreach ($tag_ids as $tag_id) {
				$tag_id = intval($tag_id);
				$where = array (
					$t . '_user_id' => $link_info['user_id'],
					'tag_id' => intval($tag_id),
					$t . '_aid' => $link_info['aid']
				);

				// 用户是否已有此标签，有则加成，无则添加
				$has_tag = $this->db->select("'1'", FALSE)
					->from('rl_'.$t.'_user_tag')
					->where($where)
					->get()->row_array();
				if ($has_tag) {

					$this->db->set('link_tag_hits', "link_tag_hits + {$weight}", FALSE)
						->where($where)
						->update('rl_'.$t.'_user_tag');
				} else {
					$this->db->insert('rl_'.$t.'_user_tag', array (
						$t . '_user_id' => $link_info['user_id'],
						'tag_id' 		=> $tag_id, 
						'company_id' 	=> $link_info['company_id'], 
						'link_tag_hits' => $weight, 
						$t . '_aid' 	=> $link_info['aid']
					));
				}
			}
		}

		// 记录每次点击 (link_hit_log)
		$hit_log = array ('link_id'=>$id, 'created_at'=>date('Y-m-d H:i:s'));
		$this->db->insert('link_hit_log', $hit_log);

		// 转向该地址
		if( strpos($link_info['url'],'http://') === FALSE )
			$link_info['url'] = 'http://' . $link_info['url'];

		header('location:' . $link_info['url']);
	}

	/**
	 * 计算本次点击的加权值
	 *
	 * 计算说明：[待补全]
	 * 
	**/
	public function get_hit_weight ($cmn_id) 
	{
		$rst = $this->db->get_where( 'link', array('cmn_id'=>$cmn_id) )->result_array();

		$reply_num = count($rst);
		if ($reply_num > 1) 
			$stepping = round( 1 / ( $reply_num - 1 ), 2 );
		else if ($reply_num == 1) 
			$stepping = 0;

		$all_hits_count = 0; 
		$not_hits_count = 0; 
		$multi_hit_flag = FALSE;

		foreach ($rst as $v) 
		{
			$all_hits_count += $v['hits'];
			if ( $v['hits'] > 1 ) 
				$multi_hit_flag = TRUE;
			if( $v['hits'] == 0 )
				$not_hits_count += 1;
		}

		if( $multi_hit_flag && $not_hits_count > 0 ) 
			$stepping = ( 1 - $stepping * ( $all_hits_count - 1 ) ) / $not_hits_count;

		$weight = 1 + $stepping * ( $not_hits_count - 1 );

		if ( $not_hits_count == $reply_num ) 
			$weight = 2.00;

		return round($weight, 2);
	}

}

/* End of file link.php */
/* Location: ./application/controllers/common/link.php */