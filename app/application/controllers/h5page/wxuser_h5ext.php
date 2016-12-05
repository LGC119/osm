<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//用户管理
class Wxuser_h5ext extends CI_Model
{
	private $cid;
	public function __construct()
	{
		parent::__construct();
		$this->cid = $this->session->userdata('company_id');
		$this->wx_id = $this->session->userdata('wx_id');
	}

	//获取用户
	public function get_users()
	{
		//获取实时消息
		//获取公众账号用户名密码
		// $rst = $this->db->select('wxaccount, wx_pass')
		// 				->from('com_weixin')
		// 				->where('id', $this->wx_id)
		// 				->get()->row_array();
		// $token = 'masengine';
		// $rt_msg = $this->get_messages($rst['wxaccount'], $rst['wx_pass'], $token);
		// print_r($rt_msg);
		// exit;
		$order1 = $this->input->get('orderbytime');
		if(!$order1)
		{
			$order = 'ORDER BY id DESC';
		}
		else
		{
			$order = 'ORDER BY id ' . $order1;
		}
		$filter = $this->format_filter();

		$this->load->library( 'pagination' );
		$offset = $this->input->get( 'per_page' );
		$offset = $offset ? $offset : 0;


		//获取分组信息
		$groups = $this->get_groups();

		$config ['per_page'] = 10;
        // 设置url格式
        $config ['page_query_string'] = TRUE;
        $config['first_link'] = '首页';
        $config['first_tag_open'] = '<div class="ui-button ui-state-default">';
        $config['first_tag_close'] = '</div>';
        $config['last_link'] = '末页';
        $config['last_tag_open'] = '<div class="ui-button ui-state-default">';
        $config['last_tag_close'] = '</div>';
        $config['next_link'] = '下一页';
        $config['next_tag_open'] = '<div class="ui-button ui-state-default">';
        $config['next_tag_close'] = '</div>';
        $config['prev_link'] = '上一页';
        $config['prev_tag_open'] = '<div class="ui-button ui-state-default">';
        $config['prev_tag_close'] = '</div>';
        $config['cur_tag_open'] = '<div class="ui-button ui-state-default ui-state-disabled">';
        $config['cur_tag_close'] = '</div>';
        $config['num_tag_open'] = '<div class="ui-button ui-state-default">';
        $config['num_tag_close'] = '</div>';

		if ( $this->input->get('type') != 'matched' ) 
		{	

            $sql = "
                SELECT 
                    t3.*, 
                    pa.readurl,
                    GROUP_CONCAT(t2.name) cate_name, 
                    GROUP_CONCAT(t1.link_cate_hits + t1.rule_cate_hits) cate_count, 
                    GROUP_CONCAT(t2.id) cate_id,
                    GROUP_CONCAT(t2.pid) cate_pid, 
                    GROUP_CONCAT(t2.code) cate_code  
                FROM meo_wx_participants pa
                LEFT JOIN meo_wx_users t3 
                    ON pa.openid = t3.openid
				LEFT JOIN meo_wx_user_cate t1
					ON pa.openid = t1.openid 
				LEFT JOIN meo_wx_cate t2 
					ON t1.cate_id = t2.id
				WHERE t3.company_id = {$this->cid} 
					AND t3.wx_id = {$this->wx_id} 
        {$filter}
				    and pa.info!='noooo'
                    and	pa.activity={$this->session->userdata('ing_activity_id')}
				GROUP BY t1.openid 
				{$order}";

			$users = $this->db->query($sql)->result_array();
        //    echo $this->db->last_query();
         //   die('llll');
			if ( count($users) )
			{
				$users = $this->format_user_cates($users);
			}

			$config['total_rows'] = count($users);
			$this->pagination->initialize( $config );

			if ( count($users) )
			{
				$chunked_users = array_chunk( $users, $config['per_page'] );
				$users = $chunked_users[ floor( $offset / $config['per_page'] ) ];
			}
		} 
		else 
		{	// 获取已匹配用户（有过交流记录的用户）
            $sql = "
                SELECT 
                    t3.*, 
                    pa.readurl,
                    GROUP_CONCAT(t2.name) cate_name, 
                    GROUP_CONCAT(t1.link_cate_hits + t1.rule_cate_hits) cate_count, 
                    GROUP_CONCAT(t2.id) cate_id,
                    GROUP_CONCAT(t2.pid) cate_pid, 
                    GROUP_CONCAT(t2.code) cate_code  
                FROM meo_wx_participants pa
                LEFT JOIN meo_wx_users t3 
                    ON pa.openid = t3.openid
				LEFT JOIN meo_wx_user_cate t1
					ON pa.openid = t1.openid 
				LEFT JOIN meo_wx_cate t2 
					ON t1.cate_id = t2.id
                WHERE 
                    t3.company_id = {$this->cid} 
					AND t3.wx_id = {$this->wx_id} 
                    AND t1.company_id = {$this->cid} 
					AND t1.wx_id = {$this->wx_id}
					AND t1.cate_id = t2.id 
                    AND t1.openid = t3.openid 

				    and pa.info!='noooo'
                    and	pa.activity={$this->session->userdata('ing_activity_id')}
                    
					{$filter}
				GROUP BY t1.openid 
					{$order}";
			$users = $this->db->query($sql)->result_array();

           // echo $this->db->last_query();
           // die('dddd');
			
			// 格式化用户标签
			if( count($users) ) 
			{
				$users = $this->format_user_cates($users);
			} 

			$config['total_rows'] = count($users);
			$this->pagination->initialize( $config );

			if ( count($users) )
			{
				$chunked_users = array_chunk( $users, $config['per_page'] );
				// print_r($chunked_users);
				$users = $chunked_users[ floor( $offset / $config['per_page'] ) ];
			}
			// print_r($users);
			// exit;
			// echo $this->db->last_query();
		}

		$paging = $this->pagination->create_links();

		// 获取标签
		$this->load->model('mex/wxcate');
		$cates = $this->wxcate->getcatelist();

		$count = count($users);
		if( $count ) 
		{
			foreach ($users as &$user) 
			{
				$user['nickname'] = htmlspecialchars_decode($user['nickname']);
				$user['signature'] = htmlspecialchars_decode($user['signature']);
			}

			if($groups == '') 
			{
				$data = array('users'=>$users, 'cates'=>$cates);
			} 
			else 
			{
				$data = array('users'=>$users, 'cates'=>$cates, 'groups'=>$groups);
			}
			return array('code'=>'200', 'data'=>$data, 'count'=>$count, 'paging'=>$paging);
		} 
		else 
		{
			if($groups != '') 
			{
				$data = array('groups'=>$groups, 'cates'=>$cates);
				return array('code'=>'400', 'data'=>$data, 'msg'=>'暂无用户');
			} 
			return array('code'=>'400', 'msg'=>'暂无用户');
		}
	}

	//获取筛选信息
	private function format_filter() 
	{
		$filters = '';
		// 获取筛选字段
		$get = $this->input->get(NULL, TRUE);

		unset($get['method']);
		unset($get['_']);
		unset($get['per_page']);
		unset($get['orderbytime']);
		unset($get['type']);
		unset( $get['consuming'] );
		unset( $get['cates'] );

		if ( count($get) ) 
		{
			if (isset($get['city'])) 
			{
				unset($get['province']);
			}
			

			$filters = ' AND ';
			foreach ($get as $filterBy => $filter) 
			{
				if ($filterBy == 'groupid')
				{
					$filters .= $this->format_group_id_filter($get['groupid']);
				}
				else if ($filterBy == 'city') 
				{
					$filter = mb_substr($filter, 0, -1);
					$filter = $this->db->escape('%' . $filter . '%');
					$filters .= "city LIKE {$filter} AND ";
				} 
				else if ( $filterBy == 'nickname' )
				{
					$filters .= "nickname LIKE '%{$filter}%' ";
				}
				else {
					$filter = $this->db->escape($filter);
					$filters .= "{$filterBy} = {$filter} AND ";
				}
			}
			$filters = rtrim($filters, ' AND ');
		}
		return $filters;
	}
	private function format_group_id_filter($group_id)
	{
		$rst = $this->db->select('group_concat(user_id) user_ids', FALSE)
						->where('group_id', $group_id)
				 		->group_by('group_id')
				 		->get('wx_user_group')
				 		->row_array();
		if ( count($rst) )
		{
			return 't3.id IN (' . $rst['user_ids'] . ') AND ';
		}
		else
		{
			return 't3.id IN (0) AND ';
		}
	}

	// 格式化用户标签
	//private function format_user_cates($users)
	public function format_user_cates($users)
	{
		$index = 0;
		$cate_ids = $this->input->get('cates');
		$cate_ids_arr = explode(',', $cate_ids);
		foreach ($users as &$u) 
		{
			$name_arr = explode(',', $u['cate_name']);
			$count_arr = explode(',', $u['cate_count']);
			$pid_arr = explode(',', $u['cate_pid']);
			$code_arr = explode(',', $u['cate_code']);
			$id_arr = explode(',', $u['cate_id']);

			// 标签筛选标记
			$has_cate = 0;
			

			$u['cates'] = array();

			// 获取父标签信息
			if ( !$u['cate_pid'] )
			{
				$u['consuming'] = 0;
			}
			else
			{
				$sql_pcates = "SELECT * 
				FROM meo_wx_cate 
				WHERE id IN ({$u['cate_pid']})"; 
				$rst_pcates = $this->db->query($sql_pcates)->result_array();
				// 组合标签数组
				$i = 0;
				foreach ($rst_pcates as $pcate) 
				{
					// 购买力计算
					if ($pcate['code'] == 'consuming')
					{
						$u['consuming'] = $this->consuming($code_arr, $count_arr);
						continue;
					}

					// 拼装标签数组
					$n = 0;
					for ($j = 0; $j < count($name_arr); $j++)
					{
						if ( $pid_arr[$j] == $pcate['id'] )
						{
							$u['cates'][$pcate['name']][$n]['tag_name'] = $name_arr[$j];
							$u['cates'][$pcate['name']][$n]['tag_count'] = round($count_arr[$j], 2);
							$u['cates'][$pcate['name']][$n]['tag_id'] = $id_arr[$j];
							++$n;
						}
					}
					++$i;

					if ( isset($u['cates'][$pcate['name']]) )
					{
						usort($u['cates'][$pcate['name']], function($a, $b) {
							if ( $a['tag_count'] < $b['tag_count'] )
							{
								return 1;
							}
							else if ( $a['tag_count'] == $b['tag_count'] )
							{
								return 0;
							}
							else
							{
								return -1;
							}
						});

						switch ($pcate['code']) 
						{
							case 'action':
								$new_arr = array_chunk($u['cates'][$pcate['name']], 3);
								$u['cates'][$pcate['name']] = $new_arr[0];
								break;

							default:
								$new_arr = array_chunk($u['cates'][$pcate['name']], 5);
								$u['cates'][$pcate['name']] = $new_arr[0];
								break;
						}

						// 筛选标签修改筛选标记
						if ($cate_ids)	
						{

							foreach ($u['cates'][$pcate['name']] as $tag) 
							{
								foreach ($cate_ids_arr as $cate_id) 
								{
									if ( $cate_id == $tag['tag_id'] )
									{
										++$has_cate;
									}
								}
								
							}
						}
					}
				}
			}
			// 筛选标签
			if ( $cate_ids && isset($cate_ids_arr) && $has_cate != count($cate_ids_arr) )
			{
				unset($users[$index]);
			}
			else
			{
				unset( $u['cate_name'] );
				unset( $u['cate_count'] );
				unset( $u['cate_id'] );
				unset( $u['cate_pid'] );
				unset( $u['cate_code'] );

				// 筛选购买力
				$u['consuming'] = isset($u['consuming']) ? $u['consuming'] : 0;
				if ( $consuming_filter = $this->input->get('consuming') )
				{
					if ( $u['consuming'] > (string)$consuming_filter[1] || $u['consuming'] < (string)$consuming_filter[0] )
					{
						unset($users[$index]);
					}
				}
			}

			if ( !count($u['cates']) )
			{
				unset($u['cates']);
			}

			++$index;
		}
		return $users;
	}

	// 计算购买力
	private function consuming($code_arr, $count_arr)
	{
		$total_count = 0;
		$consuming = 0;
		$high = $middle = $low = $sensitive = 0;
		for ($i = 0; $i < count($code_arr); $i++)
		{

			switch ($code_arr[$i]) 
			{
				case 'high':
					$total_count += $count_arr[$i];
					$high = 9 * $count_arr[$i];
					break;
				
				case 'middle':
					$total_count += $count_arr[$i];
					$middle = 4 * $count_arr[$i];
					break;

				case 'low':
					$total_count += $count_arr[$i];
					$low = 2 * $count_arr[$i];
					break;

				case 'sensitive':
					$total_count += $count_arr[$i];
					$sensitive = 1 * $count_arr[$i];
					break;
			}
		}

		if( $total_count != 0 ) 
		{
			$consuming = ( $high + $middle + $low + $sensitive ) / $total_count;
		}
		
		return round($consuming, 2);
	}

	public function get_messages($wxaccount_user, $wxaccount_pwd, $token)
	{
		$url = JAVA_URL . 'getMessage.jsp';
		$post_data = array(
			'wxaccount_user'=>$wxaccount_user,
			'wxaccount_pwd'=>$wxaccount_pwd,
			'token'=>$token
		);
		$this->load->library('commen');
		$messages = $this->commen->request_by_curl($url, $post_data);
		return $messages;
	}

	//获取openid用户
	public function get_openid_users()
	{
		$sql = "SELECT t1.openid, GROUP_CONCAT(t2.name) cate, GROUP_CONCAT(t1.num) cate_count 
				FROM meo_wx_user_cate t1, meo_wx_cate t2 
				WHERE t1.company_id = {$this->cid} 
				AND t1.wx_id = {$this->wx_id} 
				AND t1.cate_id = t2.id
				GROUP BY t1.openid";
		$query = $this->db->query($sql);
		$rst = $query->result_array();
		if( count($rst) ) {
			foreach ($rst as &$u) {
				$cate_arr = explode(',', $u['cate']);
				$num_arr = explode(',', $u['cate_count']);
				$u['cate'] = array();
				$count = count($cate_arr);
				$count = ( $count < 5 ) ? $count : 5;
				//生成标签数组
				for( $i = 0; $i < count($cate_arr); ++$i ) {
					$u['cate'][ $cate_arr[$i] ] = $num_arr[$i];
				}
				//排序
				arsort($u['cate']);

				unset( $u['cate_count'] );
			}
			return array('code'=>'200', 'data'=>$rst);
		} else {
			return array('code'=>'400');
		}
	}

	//获取分组信息
	public function get_groups()
	{
		$where = array(
			'company_id'=>$this->cid,
			'wx_id'=>$this->wx_id
		);
		$groups_rst = $this->db->get_where('wx_groups', $where)->result_array();
		$groups = array();
		//将未分组放在首位
		foreach ($groups_rst as $group) {
			// $groups[ $group['id'] ] = $group['name'];
			if( $group['wx_gid'] == 0 ) {
				$groups[ $group['name'] ] = $group['id'];
			}
		}
		foreach ($groups_rst as $group) {
			$groups[ $group['name'] ] = $group['id'];
			if( $group['wx_gid'] == 0 ) {
				continue;
			}
		}
		return $groups;
	}

	// 获取交流过的用户
	public function gettalkeduser()
	{
		$this->load->library('pagination');
		//分页
		$query=$this->db->query("select distinct openid from meo_wx_communication where company_id=2")->result_array();
		$query=$this->db->query("select distinct openid from meo_wx_communication where company_id={$this->session->userdata('company_id')}")->result_array();
		$total=count($query);
		// echo $total;exit;
		$perpage=10;
		$offset=($this->input->get('per_page')-1)*$perpage;
		$config['base_url'] = '';
		$config['total_rows'] = $total;
		$config['per_page'] = $perpage;
		$config['use_page_numbers'] = TRUE;
		$config['page_query_string'] = TRUE;
		$config['first_link'] = '首页';
		$config['first_tag_open'] = '<div class="ui-button ui-state-default loadonepage">';
		$config['first_tag_close'] = '</div>';
		$config['last_link'] = '末页';
		$config['last_tag_open'] = '<div class="ui-button ui-state-default loadonepage">';
		$config['last_tag_close'] = '</div>';
		$config['next_link'] = '下一页';
		$config['next_tag_open'] = '<div class="ui-button ui-state-default loadonepage">';
		$config['next_tag_close'] = '</div>';
		$config['prev_link'] = '上一页';
		$config['prev_tag_open'] = '<div class="ui-button ui-state-default loadonepage">';
		$config['prev_tag_close'] = '</div>';
		$config['cur_tag_open'] = '<div class="ui-button ui-state-default ui-state-disabled loadonepage">';
		$config['cur_tag_close'] = '</div>';
		$config['num_tag_open'] = '<div class="ui-button ui-state-default loadonepage">';
		$config['num_tag_close'] = '</div>';
		$this->pagination->initialize($config);
		// 查询数据
		$query=$this->db->query("select distinct openid from meo_wx_communication where company_id=2 limit {$offset},{$perpage}");
		$query=$this->db->query("select distinct openid from meo_wx_communication where company_id={$this->session->userdata('company_id')} limit {$offset},{$perpage}");
		return array('data'=>$query->result_array(),'pagination'=>$this->pagination->create_links());
	}

	// 获取交流记录
	public function get_openid_commu()
	{
		$openid=$this->input->get('openid');
		$result=$this->db->query("select * from meo_wx_communication where company_id=2 and openid='{$openid}' order by addtime")->result_array();
		$result=$this->db->query("select * from meo_wx_communication where company_id={$this->session->userdata('company_id')} and openid='{$openid}' order by addtime")->result_array();
		$return=array();
		foreach($result as $val){
			$row=array();
			switch($val['ctype']){
				case 'text':
					$row['ctype']='text';
					$row['content']=$val['content'];
					break;
				case 'img':
					$row['ctype']='img';
					$row['content']=$val['content'];
					break;
				case 'loc':
					$row['ctype']='text';
					$tmp=unserialize($val['content']);
					$row['content']='我在 '.$tmp['label'];
					break;
			}
			switch($val['rtype']){
				case 'text':
					$row['rtype']='text';
					$row['reply']=$val['reply'];
					break;
				case 'pic':
					$row['rtype']='pic';
					$tmp=unserialize($val['reply']);
					$row['title']=$tmp['title'];
					$row['desc']=$tmp['desc'];
					$row['pic']=$tmp['pic'];
					break;
			}
			$row['addtime']=$val['addtime'];
			$return[]=$row;
		}
		return $return;
	}

	public function get_gather_commu()
	{
		$fakeid = $this->input->get('fakeid', TRUE);
		$openid = $this->input->get('openid', TRUE);
		$this->db->select('wxaccount')
				 ->from('com_weixin')
				 ->where('id', $this->wx_id);
		$rst = $this->db->get()->row_array();
		$wxaccount = $rst['wxaccount'];

		//查找相应的抓取沟通记录
		// $rst1 = $this->db->get_where( 'wx_commu_gather', array('wxaccount'=>$wxaccount, 'fakeid'=>$fakeid) )->result_array();
		//查找相应的系统保存的沟通记录
		$sql = "SELECT t1.*, t2.nickname, t2.imgurl 
				FROM meo_wx_communication t1, meo_wx_users t2 
				WHERE  t1.openid = t2.openid 
				AND t1.wx_id = {$this->wx_id} 
				AND t1.openid = '{$openid}' 
				ORDER BY t1.addtime DESC";
		$rst2 = $this->db->query($sql)->result_array();
		// $rst2 = $this->db->get_where( 'wx_communication', array('wx_id'=>$this->wx_id, 'openid'=>$openid) )->result_array();
		foreach ($rst2 as &$v) 
		{
			switch ($v['ctype']) 
			{
				case 'voice':
					$v['content'] = '语音';
					break;
				case 'img':
					$v['content'] = '图片';
					break;
				case 'loc':
					$v['content'] = '地点';
					break;
			}
			switch ($v['rtype']) {
				case 'voice':
					$v['reply'] = '语音';
					break;
				case 'pic':
					$v['reply'] = unserialize($v['reply']);
					break;
			}
		}
		if( count($rst2) ) {
			return array('code'=>'200', 'data'=>$rst2);
		} else {
			return array('code'=>'400');
		}
	}

	// 删除记录
	public function delrecord()
	{
		$openid=$this->input->get('openid');
		if($this->db->where("company_id={$this->session->userdata('company_id')} and openid='{$openid}'",null,false)->delete('wx_communication')){
			$return['code']='200';
		}else
			$return['code']='400';
		return $return;
	}

	// 发送实时消息
	public function insert_rt_msg()
	{
		$post = $this->input->post(NULL, TRUE);
		// $post['company_id'] = $this->cid;
		// $post['wx_id'] = $this->wx_id;
		// $post['addtime'] = date( 'Y-m-d H:i:s', time() );
		//如果是群发
		if ( isset( $post['send_obj'] ) ) 
		{
			$where = array(
				'company_id'=>$this->cid,
				'wx_id'=>$this->wx_id
			);

			if ( $post['send_obj'] != 'all' ) 
			{
				$groupid = $post['send_obj'];
				$where['groupid'] = $groupid;
			}
			if ( $post['sex'] != 0 ) 
			{
				$where['sex'] = (int)$post['sex'];
			}

			$this->db->select('fakeid')
					 ->from('wx_users')
					 ->where($where);
			$fakeids_arr = $this->db->get()->result_array();
			$fakeids = '';
			foreach ($fakeids_arr as $fakeid) 
			{
				$fakeids .= $fakeid['fakeid'] . ',';
			}
			$post['fakeids'] = rtrim($fakeids, ',');
			unset( $post['send_obj'] );
			unset( $post['sex'] );
			

		}

		// 如果为定时
		if ( isset($post['timing']) ) 
		{
			$insert = array(
				'company_id'=>$this->cid,
				'wx_id'=>$this->wx_id,
				'fakeid'=>$post['fakeids'],
				'type'=>$post['type'],
				'content'=>$post['content'],
				'addtime'=>date('Y-m-d H:i:s'),
				'sendtime'=>$post['timing']
			);

			$this->db->insert('wx_rt_msg', $insert);
			if ( $this->db->insert_id() )
			{
				return array('code'=>200, 'msg'=>'定时发送添加成功！');
			}
			else 
			{
				return array('code'=>400, 'msg'=>'定时发送添加失败！');
			}
		}
		

		// print_r($post);
		// exit;
		$post['content'] = urlencode( $post['content'] );
		// print_r($post);
		// exit;

		//获取公众账号用户名密码
		$rst = $this->db->select('wxaccount, wx_pass')
						->from('com_weixin')
						->where('id', $this->wx_id)
						->get()->row_array();
		$post['token'] = 'masengine';
		$post['wxaccount_user'] = $rst['wxaccount'];
		$post['wxaccount_pwd'] = $rst['wx_pass'];

		$url = JAVA_URL . 'sendMessage.jsp';

		$this->load->library('commen');
		$rst = $this->commen->request_by_curl($url, $post);
		if( $rst == '1' ) {
			//存入沟通记录?
			return array('code'=>'200', 'msg'=>'发送成功！');
		} else {
			return array('code'=>'400', 'msg'=>'发送失败！');
		}

		// $this->db->insert('wx_rt_msg', $post);
		// $insert_id = $this->db->insert_id();
		// if( $insert_id > 0 ) {
		// 	//存入沟通记录?
		// 	return array('code'=>'200', 'msg'=>'回复成功！');
		// } else {
		// 	return array('code'=>'400', 'msg'=>'回复失败！');
		// }
	}

	//新建分组
	public function new_group()
	{
		$gname = $this->input->post('gname', TRUE);
		$insert = array(
			'company_id'=>$this->cid,
			'wx_id'=>$this->wx_id,
			'name'=>$gname,
			'created_at'=>date('Y-m-d H:i:s', time())
		);
		$this->db->insert('wx_groups', $insert);
		$insert_id = $this->db->insert_id();
		if($insert_id) {
			return array('code'=>'200', 'msg'=>'新建分组成功', 'id'=>$insert_id);
		} else {
			return array('code'=>'400', 'msg'=>'新建分组失败');
		}
	}

	// 编辑组
	public function edit_group()
	{
		$post = $this->input->post(NULL, TRUE);
		// 判断操作者是否是当前登录用户
		if ( !$this->is_valid( $post['gid'], 'wx_groups') )
		{
			return array('code'=>400, 'msg'=>'非法操作');
		}
		$this->db->update( 'wx_groups', array('name'=>$post['gname']), array('id'=>$post['gid']) );
		if( $this->db->affected_rows() )
		{
			return array('code'=>200, 'msg'=>'修改成功');
		}
		else
		{
			return array('code'=>300, 'msg'=>'未做任何修改');
		}
	}

	// 删除分组
	public function delete_group()
	{
		$post = $this->input->post(NULL, TRUE);
		// 判断操作者是否是当前登录用户
		if ( !$this->is_valid( $post['gid'], 'wx_groups') )
		{
			return array('code'=>400, 'msg'=>'非法操作');
		}

		// 将该分组下的用户放入未分组

		// 首先获取该账号未分组id
		$rst = $this->db->select('id')
						->where( array('company_id'=>$this->cid, 'wx_id'=>$this->wx_id, 'wx_gid'=>0) )
						->get('wx_groups')
						->row_array();
		$default_group_id = $rst['id'];

		// 更新相应用户组id为未分组id
		$this->db->update( 'wx_users', array('groupid'=>$default_group_id), array('company_id'=>$this->cid, 'wx_id'=>$this->wx_id, 'groupid'=>$post['gid']) );
		
		// 删除分组id
		$this->db->delete( 'wx_groups', array('id'=>$post['gid']) );
		if ( $this->db->affected_rows() )
		{
			return array('code'=>200, 'msg'=>'删除成功');
		}
		else
		{
			return array('code'=>400, 'msg'=>'删除失败');
		}
	}

	//把选中用户放入分组
	public function into_group()
	{
		$post = $this->input->post(NULL, TRUE);
		foreach ($post['user_ids'] as $user_id) {
			$insert = array(
				'group_id'=>$post['gid'],
				'user_id'=>$user_id
			);
			$this->db->insert('wx_user_group', $insert);
			if (!$this->db->affected_rows())
			{
				return array('code'=>400, '修改分组失败');
			}
		}
		return array('code'=>200, 'msg'=>'修改分组成功');
	}

	//获取抓取到的素材库
	public function get_gather_library()
	{
		$where = array(
			'company_id'=>$this->cid,
			'wx_id'=>$this->wx_id
		);
		$rst = $this->db->get_where('wx_libraries_gather', $where)->result_array();
		foreach ($rst as &$app) {
			$app['appmsgList'] = unserialize( $app['content'] );
			unset( $app['content'] );
		}
		if( count($rst) ) {
			return array('code'=>'200', 'data'=>$rst, 'msg'=>'获取公众平台内容库成功');
		} else {
			return array('code'=>'400', 'msg'=>'暂无内容库');
		}
	}

	//上传至公众平台素材库
	public function insert_gather_library()
	{
		$get = $this->input->get(NULL, TRUE);
		unset( $get['method'] );
		$library = array();
		$library['count'] = count($get);

		//获取需要更新的微信账户密码
        $this->db->select('wxaccount, wx_pass')
                 ->from('com_weixin')
                 ->where('id', $this->wx_id);
        $rst = $this->db->get()->row_array();
        $library['wxaccount_user'] = $rst['wxaccount'];
        $library['wxaccount_pwd'] = $rst['wx_pass'];
        $library['token'] = 'masengine';
        
        //测试token
        // $library['token'] = 'masengine';

		$i = 0;
		foreach ($get as $k => $v) {
			$library[ 'title' . $i ] = urlencode( $v['title'] );
			$library['digest' . $i ] = urlencode( isset( $v['digest'] ) ? $v['sourceurl'] : '' );
			$library['content' . $i ] = urlencode( $v['content'] );
			$library['fileid' . $i ] = urlencode( str_replace( 'resources/',"",$v['img'] ) );
			$library['sourceurl' . $i ] = urlencode( isset( $v['sourceurl'] ) ? $v['sourceurl'] : '' );
			++$i;
		}
		// print_r($library);
		// exit;
		//发送post请求
		$this->load->library('commen');
		$rst = $this->commen->request_by_curl( JAVA_URL . 'uploadMaterial.jsp', $library );
		print_r($rst);
	}

	// 从公众平台获取所有用户资料
	public function gather_all_users()
	{
		$this->load->library('commen');
		$account = $this->commen->get_account($this->wx_id);
		$account['token'] = 'masengine';
		$url = JAVA_URL . 'queryUser.jsp';
		$rst = $this->commen->request_by_curl($url, $account);
		print_r($rst);

	}

	public function is_valid($id, $table)
	{
		$rst = $this->db->select('company_id')
						->where('id', $id)
						->get($table)->row_array();
		if ( $rst['company_id'] == $this->cid )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
