<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Communication_model extends ME_Model{

	private $company_id;
	private $wx_aid;

	public function __construct() 
	{
		/* 当前用户ID */
		$this->sid = $this->session->userdata('staff_id');

		$this->company_id = $this->session->userdata('company_id');
		$this->wx_aid = $this->session->userdata('wx_aid');
	}


	/*
	** 获取舆情数据
	** @param $status 记录状态
	** @param $limit_param 分页参数
	** 		  $limit_param = array('start'=>xx, 'limit'=>xx, 'current_page'=>xxx)
	*/
	public function get_communications($status, $limit_param,$filter)
	{
        $do_message = $this->session->userdata('do_message');
        $staff_id = $this->session->userdata('staff_id');
        // 搜索条件
        $filter = json_decode($filter,true);
		extract($limit_param);
		$start = intval($start) > 0 ? intval($start) : 0;
		$limit = (intval($limit) > 0 && intval($limit) < 80) ? intval($limit) : 10;

		$total = $this->get_count($status,$filter,$do_message,$staff_id);
		if ( ! $total > 0){
            return array();
        }

		$this->_set_where($status,$filter);

        $this->db->join('wx_user wu', 'wc.openid = wu.openid', 'left')
            ->join('wx_communication_data wcd', 'wc.id=wcd.communication_id', 'left')
            // ->join('rl_wx_group_user rgroup', 'rgroup.wx_user_id=wu.id','left')
            ->limit($limit, $start)
            ->order_by('is_top', 'desc')
            ->group_by('wc.id');

        if($do_message == 1){
            $this->db->where(array('wc.staff_id' => $staff_id));
        }

        //根据对话模式模式传过来的openid，判断排序规则
        if(!empty($filter['user_openid'])){
            $this->db->order_by('created_at', 'asc');
        }else{
            $this->db->order_by('created_at', 'desc');
        }

        $feeds = $this->db->get()->result_array();

		/* 分类名称获取 */
		$date = date('Y-m-d H:i:s');
		$this->load->model('common/category_model', 'category');
		$categories = $this->category->get_quick_cats($this->session->userdata('company_id'));
		foreach ($feeds as &$val) {
			if (isset($val['cate_names']) && $val['cate_names']) {
				$cat_names = array();
				foreach (explode(',', $val['cate_names']) as $v) 
					isset($categories['category'][$v]) && $cat_names[] = $categories['category'][$v]['cat_name'];

				$val['cate_names'] = implode(', ', $cat_names);
			}


			if ($status == SUSPENDING) // 挂起状态获取时间
				$val['rm_expired'] = $val['rm_time'] <= $date;

			if ($status == REPLIED) { // 回复状态获取所有回复内容
				$val['replies'] = $this->db->select('content, media_id, type, staff_id, staff_name, created_at')
					->where('cmn_id', $val['id'])
					->get('wx_communication_reply')->result_array();
			}
		}

        foreach($feeds as $feedk=>$feedv){
            $feeds[$feedk]['content'] = $this->face_analysis($feedv['content']);
        }

        $data['feeds'] = $feeds;
		$data['total_number'] = $total;
		$data['items_per_page'] = $limit;
		$data['current_page'] = $current_page;

		return $data;
	}

	/*
	** 获取用户和用户分页
	** @param $status 记录状态
	** @param $limit_param 分页数据
	** 		  $limit_param = array('start'=>xx, 'limit'=>xx, 'current_page'=>xxx)
	** @param $filter 排序和分页的条件
	*/
	public function get_communications_user($status,$limit_param,$filter)
	{
        $do_message = $this->session->userdata('do_message');
        $staff_id = $this->session->userdata('staff_id');
        //把排序顺序存入session，后面分页时也按照session里存入的规则分页
        if(empty($filter) || $filter == 'sequence'){
            $this->session->set_userdata('filter','sequence');
        }elseif($filter == 'reverse'){
            $this->session->set_userdata('filter','reverse');
        }
		extract($limit_param);
		$start = intval($start) > 0 ? intval($start) : 0;
		$limit = (intval($limit) > 0 && intval($limit) < 80) ? intval($limit) : 10;

        $this->db->select('wc.id, wc.openid, wc.created_at')
            ->select('wu.nickname, wu.headimgurl, wu.id wx_user_id,wu.sex, wu.country, wu.province, wu.city')
            ->from('wx_user wu');

		$this->db->join('wx_communication wc', 'wc.openid = wu.openid', 'inner');

        $other_filter = $this->session->userdata('filter');

        if(($other_filter == 'sequence') || ($other_filter == 'sequence' && $filter == 'page')){
			$this->db->order_by('created_at', 'asc');
        }elseif(($other_filter == 'reverse') || ($other_filter == 'reverse' && $filter == 'page')){
			$this->db->order_by('created_at', 'desc');
        }

		$this->db->where(array(
			'wc.wx_aid' => $this->session->userdata('wx_aid'),
			'wc.operation_status' => intval($status),
			'wc.is_deleted' => 0
		));
        if($do_message == 1){
            $this->db->where(array('wc.staff_id' => $staff_id));
        }

        $users_rst = $this->db->get()->result_array();
        
        //用户数据去重
        $all_users = $this->count_unique($users_rst);
        //把二维数组变为索引数组，这样json_encode之后的顺序不会乱
        $all_users = array_values($all_users);
        //统计数组里数据总量
        $total = count($all_users);
        //由于从数据里查出的用户数据很多是重复的，去重之后只剩一些数据
        //所以不能在从数据库里取值时分页，只能从数组里截取时分页
        $users = array_slice($all_users,$start,$limit);

        $data['users'] = $users;
		$data['total_number'] = $total;
		$data['items_per_page'] = $limit;
		$data['current_page'] = $current_page;
        //echo $this->db->last_query();

		return $data;
	}

	/*
	** 把查出的用户数据去重和计算每个用户所对应的信息条数
	** @param $arr 查出的所有用户数据，包含重复的
	*/
    public function count_unique ($arr)
    {
        $res = array();
        $len = count($arr);

        //要根据 'openid' 去重，定义一个新二维数组，用 'openid' 做为新数组的键值
        $res[$arr[0]['openid']] = $arr[0];
        //让这条数据条数为1
        $res[$arr[0]['openid']]['num'] = 1;
        for($i = 1; $i < $len; $i++){
            //循环判断 $arr 里其他的 'mid' 是否存在 $res 的键值里
            //如果存在就他加一，不存在就让他加入到新定义的数组 $res 里，并让他里面的 num = 1
            if(array_key_exists($arr[$i]['openid'], $res)){
                $res[$arr[$i]['openid']]['num'] += 1;
            }else{
                $res[$arr[$i]['openid']] = $arr[$i];
                $res[$arr[$i]['openid']]['num'] = 1;
            }
        }
        return $res;
    }

	/* 获取某状态总量 */
	public function get_count ($status,$filter,$do_message,$staff_id)
	{
		if ( ! in_array($status, array(UNTOUCHED, CATEGORIZED, SUBMITED, REPLIED, IGNORED, SUSPENDING)))
			return 0;

        $sql = $this->_get_filter_sql($filter);
		$this->db->from('wx_communication wc')
            ->join('wx_user wu', 'wc.openid = wu.openid', 'left')
            ->join('wx_communication_data wcd', 'wc.id=wcd.communication_id', 'left')
			->where(array(
				'wc.wx_aid' => $this->session->userdata('wx_aid'),
				'wc.operation_status' => intval($status),
				'wc.is_deleted' => 0
			))->where($sql,NULL,FALSE);

        if($do_message == 1){
            $this->db->where(array('wc.staff_id' => $staff_id));
        }

        if ($status == REPLIED) 
            $this->db->join('wx_communication_reply wcr', 'wc.id = wcr.cmn_id', 'left')->where('wcr.status', 1);

        if ($status == SUSPENDING) 
        	$this->db->join('suspending s', 's.cmn_id=wc.id', 'left')->where('s.staff_id', $this->sid);

        return $this->db->get()->num_rows();
	}

    // 根据条件拼写sql
    private function _get_filter_sql($filter){
        $country = isset($filter['country']) ? 1 : false;
        $province = isset($filter['province']) ? $filter['province'] : false;
        $city = isset($filter['city']) ? $filter['city'] : false;
        $sex = isset($filter['sex']) ? $filter['sex'] : false;
        $date1 = isset($filter['date1']) ? $filter['date1'] : false;
        $date2 = isset($filter['date2']) ? $filter['date2'] : false;
        $nickname = isset($filter['nickname']) ? $filter['nickname'] : false;
        $user_openid = isset($filter['user_openid']) ? $filter['user_openid'] : false;
        $content = isset($filter['content']) ? $filter['content'] : false;
        $sql = ' 1=1 ';
        if($country){
            $sql .=' AND wu.country="中国" ';
        }
        if($province){
            $sql .=" AND wu.province='$province'";
        }
        if($city){
            $sql .=" AND wu.city='$city'";
        }
        if($sex){
            $sql .=" AND wu.sex='$sex'";
        }
        if($date1){
            if($date2){
                // date1 date2都有
                $date2 = date('Y-m-d H:i:s',(strtotime($date2)+(24*3600-1)));
                $sql .=" AND wc.created_at>='$date1' AND wc.created_at<='$date2'";
            }else{
                // 只存在date1
                $sql .=" AND wc.created_at>='$date1'";
            }
        }else{
            if($date2){
                // 只存在date2
                $date2 = date('Y-m-d H:i:s',(strtotime($date2)+(24*3600-1)));
                $sql .=" AND wc.created_at<='$date2'";
            }
        }
        if($nickname){
            $sql .=" AND wu.nickname LIKE '%$nickname%'";
        }
        if($user_openid){
            $sql .=" AND wu.openid='$user_openid'";
        }
        if($content){
            $sql .=" AND wcd.content LIKE '%$content%'";
        }
        return $sql;
    }

	/* 设定数据库筛选参数 */
	private function _set_where ($status,$filter)
	{
        $sql = $this->_get_filter_sql($filter);
        $this->db->select('wc.id, wc.openid, wc.type, wc.msgid, wc.created_at, wc.is_top, wc.operation_status')
        	->select('wu.nickname, wu.headimgurl, wu.id wx_user_id,wu.sex, wu.country, wu.province, wu.city')
        	->select('wcd.content, wcd.picurl, wcd.media_id, wcd.format, wcd.thumbmediaId, wcd.location_x, wcd.location_y, wcd.scale, wcd.label, wcd.title, wcd.description, wcd.url');
		switch ($status) {
			case UNTOUCHED :
				$this->db->where($sql, NULL, FALSE) // ->select('GROUP_CONCAT(`wx_group_id`) group_id')
					->from('wx_communication wc');
				break;

			case CATEGORIZED :
				$this->db->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE)
					->select('staff.name')
                    ->where($sql, NULL, FALSE)
					->from('wx_communication wc')
					->join('rl_wx_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					->join('staff','wc.staff_id = staff.id','left')
					->group_by('rwcc.cmn_id');
				break;
			
			case REPLIED :
				$this->db->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE)
					->select('staff.name')
                    ->where($sql, NULL, FALSE)
					->from('wx_communication wc')
					->join('rl_wx_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					->join('staff','wc.staff_id = staff.id','left')
					->group_by('rwcc.cmn_id');
				break;
				
			// case IGNORED :
			// 	break;
				
			case SUSPENDING :
				$this->db->select('s.remind_time rm_time, s.description AS rm_desc, s.status AS status, s.id AS sid')
					->select('staff.name')
					->where($sql, NULL, FALSE)
					# ->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE)
					->from('wx_communication wc')
					->join('staff','wc.staff_id = staff.id','left')
					# ->join('rl_wx_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					->join('suspending s', 'wc.id = s.cmn_id', 'left')
					->where(array ('s.staff_id'=>$this->sid));
					# ->group_by('rwcc.cmn_id');
				break;

			default:
				$this->db->select('staff.name')
					->where($sql, NULL, FALSE)
					# ->select("GROUP_CONCAT(`rwcc`.`cat_id`) AS cate_names", FALSE)
					->from('wx_communication wc')
					->join('staff','wc.staff_id = staff.id','left');
					# ->join('rl_wx_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
					# ->group_by('rwcc.cmn_id');
				break;
		}

		$this->db->where(array(
			'wc.wx_aid' => $this->session->userdata('wx_aid'),
			'wc.operation_status' => intval($status),
			'wc.is_deleted' => 0
		));
	}

	/**
	 * 功能：接收用户消息插入数据表me_wx_communication 与me_wx_communication_data
	 * @param msg_info array 原始消息信息
	 * @param account array 接受消息的账号信息
	 * 
	 * @return boolean
	 * 不完善：wx_aid company_id kwyword_id
	 */
	public function insert ($msg_info='', $account){
		if ( ! is_array($msg_info) || ! $msg_info) return FALSE;

		$communication = array ();
		$communication_data = array ();

		switch($msg_info['msgtype']){
			case 'text':
				$communication_data['content'] = $msg_info['content'];
				/* 自动关键词设置<自动置顶|自动忽略> */
				$this->load->model('meo/keyword_model', 'keyword');
				$auto_keywords = $this->keyword->get_auto_keywords($account['company_id']);
				$auto_keywords = $auto_keywords['wexinmsg'];

				/* 根据关键词自动处理忽略和置顶 */
				/* 暂用字符串数组循环 */
				/* TODO:优化为字符串索引树 */
				$communication['is_top'] = 0;
				foreach ($auto_keywords['pintop'] as $key) 
					if (strpos($msg_info['content'], $key) !== FALSE) 
						$communication['is_top'] = 1;

				/* 在没有命中置顶的情况下，检测是否有命中忽略 */
				if ($communication['is_top'] != 1) 
					foreach ($auto_keywords['ignore'] as $key) 
						if (strpos($msg_info['content'], $key) !== FALSE) 
							$communication['operation_status'] = 4;

				break;
			case 'image':
				$communication_data['picurl'] = $msg_info['picurl'];
				$communication_data['media_id'] = $msg_info['mediaid'];
				break;
			case 'voice':
				$communication_data['media_id'] = $msg_info['mediaid'];
				$communication_data['format'] = $msg_info['format'];
				$communication_data['picurl'] = $msg_info['picurl'];
				break;
			case 'video':
				$communication_data['media_id'] = $msg_info['mediaid'];
				$communication_data['thumbmediaid'] = $msg_info['thumbmediaid'];
				$communication_data['picurl'] = $msg_info['picurl'];
				break;
			case 'location':
				$communication_data['location_x'] = $msg_info['location_x'];
				$communication_data['location_y'] = $msg_info['location_y'];
				$communication_data['scale'] = $msg_info['scale'];
				$communication_data['label'] = $msg_info['label'];
				break;
			case 'link':
				$communication_data['title'] = $msg_info['title'];
				$communication_data['description'] = $msg_info['description'];
				$communication_data['url'] = $msg_info['url'];
				break;
			default:
                return false;
				break;
		}
		$communication['company_id'] = $account['company_id'];
		$communication['wx_aid'] = $account['id'];
		$communication['openid'] = $msg_info['fromusername'];
		$communication['type'] = $msg_info['msgtype'];
		$communication['msgid'] =$msg_info['msgid'];
		$communication['created_at'] = date("Y-m-d H:i:s",$msg_info['createtime']);

		// 添加数据到wx_communication表中
		$this->db->insert('wx_communication',$communication);
		$insertid = $this->db->insert_id();

		//进行自动分配
		if($insertid != ''){
			$this->auto_allot($insertid);
		}
		
		if ($insertid > 0) // 添加数据到wx_communication_data表中
			$this ->db ->insert('wx_communication_data', array_merge($communication_data, array('communication_id'=>$insertid)));

		return $insertid > 0 ? array ('id'=>$insertid) : array ();
	}


	public function get_cmn_history($limit_param)
	{
		extract($limit_param);
		$start = intval($start) > 0 ? intval($start) : 0;
		$limit = (intval($limit) > 0 && intval($limit) < 80) ? intval($limit) : 0;

		$wx_user_id = $this->input->get('wx_user_id');
		$where = array(
			'wc.wx_aid' => $this->session->userdata('wx_aid'),
			'wu.id' => $wx_user_id,
			'wc.is_deleted' => 0
		);
		// 获取总数
		$total = $this->db->from('wx_communication wc')
			->join('wx_user wu', 'wc.openid = wu.openid', 'left')
			->where($where)->get()->num_rows();

		if ( ! $total > 0) // 总数为 0
			return array();

		// 获取data
		$feeds = $this->db->select('wc.*, wu.nickname, wu.headimgurl, wu.id wx_user_id, GROUP_CONCAT(DISTINCT `c`.`cat_name`) AS cate_names', FALSE)
			->select('wcd.content, wcd.picurl, wcd.media_id, wcd.format, wcd.thumbmediaId, wcd.location_x, wcd.location_y, wcd.scale, wcd.label, wcd.title, wcd.description, wcd.url')
			->from('wx_communication wc')
			->join('wx_user wu', 'wc.openid = wu.openid', 'left')
			->join('wx_communication_data wcd', 'wc.id = wcd.communication_id', 'left')
			->join('rl_wx_communication_category rwcc', 'wc.id = rwcc.cmn_id', 'left')
			->join('category c', 'c.id = rwcc.cat_id', 'left')
			->where($where)
			->group_by('wc.id')
			->limit($limit, $start)
			->order_by('created_at', 'desc')
			->get()->result_array();

        foreach($feeds as &$feed){
            $feed['content'] = $this->face_analysis($feed['content']);
            // 获取回复内容
        	$feed['replies'] = $this->db->select('content, media_id, type, staff_id, staff_name, created_at')
				->where('cmn_id', $feed['id'])
				->get('wx_communication_reply')->result_array();
        }
        $data['feeds'] = $feeds;
		$data['total_number'] = $total;
		$data['items_per_page'] = $limit;
		$data['current_page'] = $current_page;

		return $data;
	}



    // 根据用户id获取用户标签与组信息
    public function get_user_tag_group(){
        $id = 35;
        $sql = 'SELECT * FROM '.$this->db->dbprefix('').'
                ';
    }

    //qq表情解析
    public function face_analysis($content=''){
        $this->config->load('mex/emoji');
        if(strpos($content,'/:') !== false){
            $content = str_replace($this->config->item('emoji_code'),$this->config->item('emoji_image'),$content);
        }
        return $content;
    }
    /**
	** 自动分配
	** @param $communication_id 插入的信息id
	**/
		public function auto_allot($communication_id){
			//判断是否有相应权限的csr在线
			$this->load->database();
			$where = array(
					'state'=>1,
					'do_message'=>1,
					'is_deleted'=>0
				);
			$state_on = $this->db->select('id')
						->from('staff')
						->where($where)
						->get()->result_array();
			// var_dump($state_on);
			if(!$state_on){
				//如果没有相应权限的csr在线，把信息放入待分类中
				$this->db->set('operation_status',0);
				$this->db->where('id',$communication_id);
				$this->db->update('wx_communication');
				if($this->db->affected_rows()){}
					// echo $this->db->last_query();
			}else{
				//如果有相应权限的csr在线的话，判断这个信息八小时之前有没有处理信息
				$openid = $this->db->select('openid,created_at')
					->from('wx_communication')
					->where('id',$communication_id)
					->get()->result_array();
				//获取当前信息的时间戳
				// var_dump($openid[0]['created_at']);
				$created_at = strtotime($openid[0]['created_at']);
				// var_dump($created_at);
				//8小时前时间戳
				$eight = 60*60*8;

				$created_at = $created_at - $eight;
				$created_at = date('Y-m-d H:i:s',$created_at);

				// var_dump($created_at);

				//查询在8小时之内有没有人处理过openid为$openid['openid']的信息 
				$sql = "select staff_id from me_wx_communication where openid = '{$openid[0]['openid']}' and created_at > '{$created_at}' and created_at <'{$openid[0]['created_at']}' and operation_status > 0 order by created_at desc";
				// echo $sql;
				$query = $this->db->query($sql);
				if($query->num_rows()>0){
					$once = $query->result_array();
				}else{
					$once = '';
				}
				// var_dump($once);exit;
				//如果8小时之前有处理的信息查询处理这条信息的人
				if($once && $once[0]['staff_id']>0){
					$sql = "select id from me_staff where id = {$once[0]['staff_id']} and state = 1 and do_message = 1 and is_deleted = 0";
					$query = $this->db->query($sql);
					if($query->num_rows()>0){
						$state = $query->result_array();
					}else{
						$state = '';
					}
					// var_dump($state);exit;
					//判断之前处理这条信息的人是否在线
					if($state && $state[0]['id']>0){
						//把这条信息分配给之前处理这条信息的人
						$sql = "update me_wx_communication set staff_id = {$state[0]['id']},operation_status = 1 where id = {$communication_id} ";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
					}else{
						//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						// var_dump($state_on);
                        foreach($state_on as $val){
                            $sql = "select count(c.id) from me_wx_communication as c where c.staff_id = {$val['id']} and c.operation_status = 1";
                            // echo $sql;
                            $query = $this->db->query($sql)->row_array();
                            if($query){
                                $communication_num[] = array('count(c.id)'=>$query['count(c.id)'],'id'=>$val['id']);
                            }else{
                                $communication_num[] = array('count(c.id)'=>0,'id'=>$val['id']);
                            }
                        }
						// var_dump($communication_num);
						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						$sql = "update me_wx_communication set staff_id = {$min_num_person},operation_status = 1 where id = {$communication_id} ";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
					}
				}else{
					//把这条信息分配给待处理量小的人
						// var_dump($state_on);
						// var_dump($state_on);
                        foreach($state_on as $val){
                            $sql = "select count(c.id) from me_wx_communication as c where c.staff_id = {$val['id']} and c.operation_status = 1";
                            // echo $sql;
                            $query = $this->db->query($sql)->row_array();
                            if($query){
                                $communication_num[] = array('count(c.id)'=>$query['count(c.id)'],'id'=>$val['id']);
                            }else{
                                $communication_num[] = array('count(c.id)'=>0,'id'=>$val['id']);
                            }
                        }
						// var_dump($communication_num);
						$min_num = array('count(c.id)'=>99999);//处理量最小的人的id
						foreach($communication_num as $val){
							$val['count(c.id)'] < $min_num['count(c.id)']?$min_num = $val:$min_num;
						}
						$min_num_person = $min_num['id'];//待处理量最好的人的id
						//分配信息
						$sql = "update me_wx_communication set staff_id = {$min_num_person},operation_status = 1 where id = {$communication_id} ";
						// $this->db->set('staff_id',$min_num_person);
						// $this->db->where('id',$communication_id);
						// $this->db->update('wx_communication');
						$query = $this->db->query($sql);
						if($this->db->affected_rows()>0){}else{
							return '';exit;
						}
				}
			}
		}
} 
