<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class User_model extends ME_Model
{

    public function __construct(){
        parent::__construct();
        $this ->load ->library('Wxapi');
        $this ->load ->model('mex/media_model','media');
    }

    // 用户列表展示
    public function select_user($search) 
    {
		$page = $this->input->get_post('page');
		$perpage = $this->input->get_post('perpage');

        $sub_start = $search['subscribe_start'];
        $sub_end = $search['subscribe_end'];
        $com_start = $search['communication_start'];
        $com_end = $search['communication_end'];
        //$no_comm = $search['no_communication'];
        unset($search['subscribe_start']);
        unset($search['subscribe_end']);
        unset($search['communication_start']);
        unset($search['communication_end']);
        //unset($search['no_communication']);

		$sum = $this->user_count($search, $sub_start, $sub_end, $com_start, $com_end);

        if($com_start || $com_end){
            $this->db->select("wu.openid")
                        ->from('wx_user wu')
                        ->join('wx_account wa','wa.id=wu.wx_aid')
                        ->where(array('wu.wx_aid' => $search['wx_aid']));
            
            $this->db->join('wx_communication wc', 'wu.openid = wc.openid', 'left')
                ->group_by('wu.id');
            if($com_start){
                $this->db->where(array('wc.created_at >=' => $com_start));
            }
            if($com_end){
                $this->db->where(array('wc.created_at <=' => $com_end));
            }

            $user_openid = $this->db->get()->result_array();
            //echo '<pre>';
            //print_r($userData);
            foreach($user_openid as $val){
                $in_openid[] = $val['openid']; 
            }
            //print_r($in_openid);
            //exit;
        }

		$this->user_limit($page,$perpage,$sum);

		// 有表以外的搜索条件存在
		if($search['group_id'] || $search['tags'] || $search['group_send'] || $search['send_id']){
			$this->db->select("wu.id,wu.nickname,wu.country,wu.province,wu.city,wu.sex,wu.headimgurl,wu.localimgurl,wu.openid");
			// 用户组
			if($search['group_id']){
				$this->db->from('rl_wx_group_user gu')
							->where('gu.wx_group_id',$search['group_id']);
				$wx_user_id = 'wu.id=gu.wx_user_id';
			}
			if($search['tags']){
				if($search['group_id']){
					$this->db->join('rl_wx_user_tag ut','ut.wx_user_id=gu.wx_user_id','left')
								->where_in('ut.tag_id',$search['tags']);
				}else{
					$this->db->from('rl_wx_user_tag ut')
								->where_in('ut.tag_id',$search['tags']);
					$wx_user_id = 'wu.id=ut.wx_user_id';
				}
			}
			if($search['send_id']){
				if($search['group_id'] || $search['tags']){
					if($search['tags']){
						$this->db->join('event_participant ep','ep.wx_user_id=ut.wx_user_id');
					}else{
						$this->db->join('event_participant ep','ep.wx_user_id=gu.wx_user_id');
					}
					$this->db->join('event_wx_info ei','ep.event_id=ei.event_id')
								->where('ei.send_id',$search['send_id']);
				}else{
					$this->db->from('event_participant ep')
						->join('event_wx_info ei','ep.event_id=ei.event_id')
						->where('ei.send_id',$search['send_id']);
					$wx_user_id = 'wu.id=ep.wx_user_id';
				}
			}
			if($search['group_send']){
				if($search['group_id'] || $search['tags'] || $search['send_id']){
					if($search['group_id']){
						$this->db->join('event_participant ep','ep.wx_user_id=gu.wx_user_id');
					}else if($search['tags']){
						$this->db->join('event_participant ep','ep.wx_user_id=ut.wx_user_id');
					}
					$this->db->join('event_wx_info ei','ep.event_id=ei.event_id')
								->where_in('ei.send_id',$search['group_send']);
				}else{
					$this->db->from('event_participant ep')
								->join('event_wx_info ei','ep.event_id=ei.event_id')
								->where_in('ei.send_id',$search['group_send']);
					$wx_user_id = 'wu.id=ep.wx_user_id';
				}

			}
			$this->db->join('wx_user wu',$wx_user_id,'left');
			$this->db->join('wx_account wa','wa.id=wu.wx_aid');
			if($search['group_id']) unset($search['group_id']);
			if($search['send_id']) unset($search['send_id']);
			if($search['tags']) {
				$search['tags'] = '';
				unset($search['tags']);
			}
			if($search['group_send']) {
				$search['group_send'] = '';
				unset($search['group_send']);
			}
			$search = array_filter($search);
			foreach($search as $seark=>$searv){
				if($seark == 'country' && $searv && $searv != 'false'){
					$search['wu.'.$seark] = '中国';
				}else{
					$search['wu.'.$seark] = $searv;
				}
				unset($search[$seark]);
			}
			$this->db->where($search);
		}else{
			$search = array_filter($search);
			foreach($search as $seark=>$searv){
				if($seark == 'country' && $searv && $searv != 'false'){
					$search['wu.'.$seark] = '中国';
				}else{
					$search['wu.'.$seark] = $searv;
				}
				unset($search[$seark]);
			}
			$this->db->select("wu.id,wu.nickname,wu.country,wu.province,wu.city,wu.sex,wu.headimgurl,wu.localimgurl,wu.openid")
						->from('wx_user wu')
						->join('wx_account wa','wa.id=wu.wx_aid')
						->where($search);
		}

        if($sub_start){
            $this->db->where(array('wu.subscribe_time >=' => $sub_start));
        }
        if($sub_end){
            $this->db->where(array('wu.subscribe_time <=' => $sub_end));
        }
        //if($no_comm == 'true'){
            //$this->db->join('wx_communication wc', 'wu.openid = wc.openid', 'left')
                //->where(array('wc.id' => NULL))
                //->group_by('wu.id');
        //}
        if(isset($in_openid) &&($com_start || $com_end)){
            $this->db->where_not_in('wu.openid',$in_openid);
        }
        // 获取用户信息
        $userData = $this->db->get()->result_array();
        //echo $this->db->last_query();

		// 获取用户ID数组
		$userIds = array();
		foreach($userData as $userv){
			array_push($userIds,$userv['id']);
		}
		// 获取用户标签
		$tagNameData = array();
		if($userIds){
			$this->db->select("ut.wx_user_id,t.tag_name")
						->from('rl_wx_user_tag ut')
						->join('tag t','t.id = ut.tag_id','left')
						->where_in('ut.wx_user_id',$userIds);
			$tagNameData = $this->db->get()->result_array();
		}

		$newTagNameData = array();
		if(count($tagNameData) > 0){
			foreach($tagNameData as $tagv){
				if(!array_key_exists($tagv['wx_user_id'],$newTagNameData)){
					$newTagNameData[$tagv['wx_user_id']] = '';
				}
				$newTagNameData[$tagv['wx_user_id']] .= $tagv['tag_name'].',';
			}
		}
//		// 获取用户所属组
		$groupNameData =  array();
		if($userIds){
			$this->db->select("gu.wx_user_id,g.name")
				->from('rl_wx_group_user gu')
				->join('wx_group g','g.id = gu.wx_group_id','left')
				->where_in('gu.wx_user_id',$userIds);
			$groupNameData = $this->db->get()->result_array();
		}
		$newGroupNameData = array();
		if(count($groupNameData) > 0){
			foreach($groupNameData as $groupv){
				if(!array_key_exists($groupv['wx_user_id'],$newGroupNameData)){
					$newGroupNameData[$groupv['wx_user_id']] = '';
				}
				$newGroupNameData[$groupv['wx_user_id']] .= $groupv['name'].',';
			}
		}
		foreach($userData as $userk=>$userv){
			if(array_key_exists($userv['id'],$newTagNameData)){
				$userData[$userk]['tag_name'] =	rtrim($newTagNameData[$userv['id']],',');
			}else{
				$userData[$userk]['tag_name'] = NULL;
			}
			if(array_key_exists($userv['id'],$newGroupNameData)){
				$userData[$userk]['group_name'] = rtrim($newGroupNameData[$userv['id']],',');
			}else{
				$userData[$userk]['group_name'] = NULL;
			}
		}
		$data['users'] = $userData;
		$data['total_number'] = $sum;
		$data['perpage'] = $perpage;
		$data['page'] = $page;
		return $data;
    }

    /*
    ** 获取筛选参数的用户总量
    ** @param $filter_params (string | array)
    ** return 用户总量
    */
    public function user_count ($search, $sub_start, $sub_end, $com_start, $com_end) {
        if($com_start || $com_end){
            $this->db->select("wu.openid")
                        ->from('wx_user wu')
                        ->join('wx_account wa','wa.id=wu.wx_aid')
                        ->where(array('wu.wx_aid' => $search['wx_aid']));
            
            $this->db->join('wx_communication wc', 'wu.openid = wc.openid', 'left')
                ->group_by('wu.id');
            if($com_start){
                $this->db->where(array('wc.created_at >=' => $com_start));
            }
            if($com_end){
                $this->db->where(array('wc.created_at <=' => $com_end));
            }

            $user_openid = $this->db->get()->result_array();
            foreach($user_openid as $val){
                $in_openid[] = $val['openid']; 
            }
        }
		if($search['group_id'] || $search['tags'] || $search['group_send'] || $search['send_id']){
			$this->db->select("COUNT(1) count");
			// 用户组
			if($search['group_id']){
				$this->db->from('rl_wx_group_user gu')
					->where('gu.wx_group_id',$search['group_id']);
				$wx_user_id = 'wu.id=gu.wx_user_id';
			}
			if($search['tags']){
				if($search['group_id']){
					$this->db->join('rl_wx_user_tag ut','ut.wx_user_id=gu.wx_user_id','left')
						->where_in('ut.tag_id',$search['tags']);
				}else{
					$this->db->from('rl_wx_user_tag ut')
						->where_in('ut.tag_id',$search['tags']);
					$wx_user_id = 'wu.id=ut.wx_user_id';
				}
			}
			if($search['send_id']){
				if($search['group_id'] || $search['tags']){
					if($search['tags']){
						$this->db->join('event_participant ep','ep.wx_user_id=ut.wx_user_id');
					}else{
						$this->db->join('event_participant ep','ep.wx_user_id=gu.wx_user_id');
					}
					$this->db->join('event_wx_info ei','ep.event_id=ei.event_id')
						->where('ei.send_id',$search['send_id']);
				}else{
					$this->db->from('event_participant ep')
						->join('event_wx_info ei','ep.event_id=ei.event_id')
						->where('ei.send_id',$search['send_id']);
					$wx_user_id = 'wu.id=ep.wx_user_id';
				}
			}
			if($search['group_send']){
				if($search['group_id'] || $search['tags'] || $search['send_id']){
					if($search['group_id']){
						$this->db->join('event_participant ep','ep.wx_user_id=gu.wx_user_id');
					}else if($search['tags']){
						$this->db->join('event_participant ep','ep.wx_user_id=ut.wx_user_id');
					}
					$this->db->join('event_wx_info ei','ep.event_id=ei.event_id')
						->where_in('ei.send_id',$search['group_send']);
				}else{
					$this->db->from('event_participant ep')
						->join('event_wx_info ei','ep.event_id=ei.event_id')
						->where_in('ei.send_id',$search['group_send']);
					$wx_user_id = 'wu.id=ep.wx_user_id';
				}

			}
			$this->db->join('wx_user wu',$wx_user_id,'left');
			$this->db->join('wx_account wa','wa.id=wu.wx_aid');
			if($search['group_id']) unset($search['group_id']);
			if($search['send_id']) unset($search['send_id']);
			if($search['tags']) {
				$search['tags'] = '';
				unset($search['tags']);
			}
			if($search['group_send']) {
				$search['group_send'] = '';
				unset($search['group_send']);
			}
			$search = array_filter($search);
//			var_dump($search);
			foreach($search as $seark=>$searv){
				if($seark == 'country' && $searv && $searv != 'false'){
					$search['wu.'.$seark] = '中国';
				}else{
					$search['wu.'.$seark] = $searv;
				}
				unset($search[$seark]);
			}
			$this->db->where($search);
		}else{
			$search = array_filter($search);
			foreach($search as $seark=>$searv){
				if($seark == 'country' && $searv && $searv != 'false'){
					$search['wu.'.$seark] = '中国';
				}else{
					$search['wu.'.$seark] = $searv;
				}
				unset($search[$seark]);
			}
			$this->db->select("COUNT(1) count")
				->from('wx_user wu')
				->join('wx_account wa','wa.id=wu.wx_aid')
				->where($search);
		}

        if($sub_start){
            $this->db->where(array('wu.subscribe_time >=' => $sub_start));
        }
        if($sub_end){
            $this->db->where(array('wu.subscribe_time <=' => $sub_end));
        }
        //if($no_comm == 'true'){
            //$this->db->join('wx_communication wc', 'wu.openid = wc.openid', 'left')
                //->where(array('wc.id' => NULL))
                //->group_by('wu.id');
        //}
        if(isset($in_openid) &&($com_start || $com_end)){
            $this->db->where_not_in('wu.openid',$in_openid);
        }
		$count = $this->db->get()->result_array();
        //echo $this->db->last_query();
        //if($no_comm == 'true'){
            //return count($count);
        //}
		return (int)$count[0]['count'];
    }

	/**
	 * @param
	 * @return string
	 */
	public function user_limit($page,$perpage,$sum){
		$page = intval($page) > 0 ? intval($page) : 1;
		$perpage = intval($perpage) > 0 ? intval($perpage) : 20;
		if ($page > ceil($sum / $perpage)) {
			$this->db->limit($perpage);
		}else{
			$this->db->limit($perpage,($page - 1) * $perpage);
		}
	}

	protected function _get_openids_where($group_send_ids)
    {
        $openids = array();
        $rst = $this->db->select('openids')
                ->from('wx_sendall')
                ->where_in('id', $group_send_ids)
                ->get()->result_array();
        foreach ($rst as $v) 
        {
            $oids_arr = explode(',', $v['openids']);
            foreach ($oids_arr as $openid) 
            {
                $oid = trim($openid, '"');
                $oid = "'{$oid}'";
                array_push($openids, $oid);
            }
        }

        $openids = array_unique($openids);
        $openids = implode(',', $openids);
        $openids_where = " AND user.openid IN ({$openids})";
        return $openids_where;
    }


    // 用户入组
    public function user_in_group($user_ids = NULL,$group_id)
    {
        $values = '';
        if (is_array($user_ids))
        {
            foreach($user_ids as $idV){
                $values .= "('$idV','$group_id'),";
            }
            $values = rtrim($values,',');
        }
        else if (strpos($user_ids,','))
        {
            $sId = explode(',',$user_ids);
            foreach($sId as $idV){
                $values .= "('$idV','$group_id'),";
            }
            $values = rtrim($values,',');
        }
        else
        {
            $values = "($user_ids, $group_id)";
        }
        $sql = 'INSERT IGNORE INTO '.$this ->db ->dbprefix('rl_wx_group_user').'(`wx_user_id`,`wx_group_id`)
                VALUES'.$values;
        // echo $sql;
        return $this ->db ->query($sql);
    }

    // 数据库中根据用户id获取用户详情
    public function select_user_info($user_id){
        $sql = "SELECT id,nickname,country,province,city,sex,signature,localimgurl,headimgurl,communication_time,openid,purchasing_power,brand_interaction
                    FROM ".$this ->db ->dbprefix('wx_user')."
                    WHERE id='$user_id' LIMIT 1";
        $data = $this ->db ->query($sql) ->result_array();
        return $data[0];
    }

    // 根据用户的openid获取用户地区与性别
    public function get_area_sex($openid=''){
        if($openid){
            $sql = "SELECT province,city,sex FROM ".$this->db->dbprefix('wx_user')."
                    WHERE openid='$openid'";
            $data = $this->db->query($sql)->result_array();
            return $data[0];
        }
        return false;
    }

    // 获取验证通过的微信号有几个【获取微信wx_aid】
    public function get_verified(){
        $sql = 'SELECT id,company_id FROM '.$this->db->dbprefix('wx_account').'
                    WHERE verified=1 AND is_delete=0';
        $data = $this->db->query($sql)->result_array();
        return $data;
    }

    // 获取所有用户信息入库
    public function insert_user_all () {
        ini_set('max_execution_time',0);
        $wx_accounts = $this->get_verified();
        if ( ! $wx_accounts) return FALSE;

        foreach($wx_accounts as $wx_account){
            /* 获取粉丝，每次10000条记录 */
            $id_log_file = 'application/logs/wechat_get_followers_last_openid_'.$wx_account['id'].'.log';
            $err_log_file = 'application/logs/wechat_get_followers_err_'.$wx_account['id'].'.log';
            $nextid = file_exists($id_log_file) ? file_get_contents($id_log_file) : '';
	        $openid_list = $this->wxapi->wx_get_user_list($wx_account['id'], $nextid);
	        
	        if ($openid_list && isset($openid_list['errcode'])) {
	        	file_put_contents($err_log_file, json_encode($openid_list));
	        	break ;
	        }

	        /* 循环插入用户 */
	        $openids = $openid_list['data']['openid'];
	        /* 每次200条获取用户信息并入库 */
	        $openid_chunck = array_chunk($openids, 200);
	        unset($openids, $openid_list);
	        foreach ($openid_chunck as $openids) {
	        	$insert_sql = "INSERT INTO {$this->db->dbprefix('wx_user')} (`wx_aid`, `company_id`, `created_at`, `subscribe`, `openid`, `nickname`, `sex`, `language`, `city`, `province`, `country`, `headimgurl`, `subscribe_time`, `remark`) VALUES ";
                foreach ($openids as $openid) {
                	/* 接口获取用户 */
                    $user = $this->wxapi->wx_get_user_info($openid, $wx_account['id']);;
                    if (isset($user['errcode']) OR ! $user['openid']) continue;

                    $sql_created_at 	= date('Y-m-d H:i:s');
                    $sql_subscribe 		= $user['subscribe'] ? 1 : 0;
                    $sql_openid 		= $this->db->escape($user['openid']); 
                    $sql_nickname 		= $this->db->escape($user['nickname']); 
                    $sql_sex 			= $this->db->escape($user['sex']); 
                    $sql_language 		= $this->db->escape($user['language']); 
                    $sql_city 			= $this->db->escape($user['city']); 
                    $sql_province 		= $this->db->escape($user['province']); 
                    $sql_country 		= $this->db->escape($user['country']); 
                    $sql_headimgurl 	= $user['headimgurl'] ? substr($user['headimgurl'],0,-1) . '132' : ''; 
                    $sql_subscribe_time = date('Y-m-d H:i:s', $user['subscribe_time']); 
                    $sql_remark 		= $this->db->escape($user['remark']);

                    $insert_sql .= "({$wx_account['id']}, {$wx_account['company_id']}, '{$sql_created_at}', {$sql_subscribe}, {$sql_openid}, {$sql_nickname}, {$sql_sex}, {$sql_language}, {$sql_city}, {$sql_province}, {$sql_country}, '{$sql_headimgurl}', '{$sql_subscribe_time}', {$sql_remark}),";
                }
				$insert_sql = rtrim($insert_sql, ',') . ' ON DUPLICATE KEY UPDATE subscribe=VALUES(subscribe), openid=VALUES(openid), nickname=VALUES(nickname), sex=VALUES(sex), headimgurl=VALUES(headimgurl), subscribe_time=VALUES(subscribe_time), remark=VALUES(remark)';
                $this->db->query($insert_sql);
                file_put_contents($id_log_file, array_pop($openids));
	        }
        }
    }

    // 根据条件获取要群发的对象的openid
    public function get_user_openid($param=''){
        $wx_aid = isset($param['wx_aid']) ? $param['wx_aid'] : 0;
        $where = " WHERE 1=1 AND wx_aid='$wx_aid'";
        $group = $param['group'];
        $sex = $param['sex'];
        $country = $param['country'];
        $province = $param['province'];
        $city = $param['city'];
        $count = $param['count'];
        $send_num = $param['send_num'];
        // 查看这个公众号的所有粉丝
        $sql = 'SELECT count(1) as allNum FROM '.$this->db->dbprefix('wx_user')." WHERE wx_aid='$wx_aid'";
        $allNum = $this->db->query($sql)->result_array();
        if($sex)
            $where .=" AND user.sex='$sex'";
        if($send_num != 99){
            $num = 4 - $send_num;
            $where .=" AND sn.new_num='$num'";
        }
        if($country && $country!='false')
            $where .=" AND user.country='中国'";
        if($province && $province!='false')
            $where .=" AND user.province='$province'";
        if($city && $city!='false')
            $where .=" AND user.city='$city'";
        if($group){
            $where .=" AND group_user.wx_group_id='$group'";
            if($count){
            $sql = 'SELECT COUNT(1) AS num FROM '.$this ->db ->dbprefix('wx_user').' AS user
                        LEFT JOIN '.$this ->db ->dbprefix('rl_wx_group_user').' AS group_user
                            ON user.id=group_user.wx_user_id
                        LEFT JOIN '.$this->db->dbprefix('wx_send_num').' AS sn
                            ON user.openid=sn.openid'.$where;
            }else{
                $sql = 'SELECT user.openid,user.nickname FROM '.$this ->db ->dbprefix('wx_user').' AS user
                        LEFT JOIN '.$this ->db ->dbprefix('rl_wx_group_user').' AS group_user
                            ON user.id=group_user.wx_user_id
                        LEFT JOIN '.$this->db->dbprefix('wx_send_num').' AS sn
                            ON user.openid=sn.openid'.$where;
            }
        }else{
            if($count){
                $sql = 'SELECT COUNT(1) AS num FROM '.$this ->db ->dbprefix('wx_user').' AS user
                            LEFT JOIN '.$this->db->dbprefix('wx_send_num').' AS sn
                                ON user.openid=sn.openid'.$where;
            }else{
                $sql = 'SELECT user.openid,user.nickname FROM '.$this ->db ->dbprefix('wx_user').' AS user
                            LEFT JOIN '.$this->db->dbprefix('wx_send_num').' AS sn
                                ON user.openid=sn.openid'.$where;
            }
        }
        $data = $this ->db ->query($sql) ->result_array();
        if($count){
            return (int)$data[0]['num'];
        }else{
            $newData['data'] = $data;
            if(isset($allNum[0]['allNum']) && ($allNum[0]['allNum'] == count($data))){
                $newData['status'] ='all';
            }else{
                $newData['status'] ='port';
            }
            return $newData;
        }
    }

    // 微信那获取单个用户信息
    public function get_userinfo($openid,$wx_aid=''){
        $data = $this ->wxapi ->wx_get_user_info($openid,$wx_aid);
        return $data;
    }

    // 添加单个用户的详细信息
    public function insert_userinfo($openid,$wx_aid=''){
        // 用户详情
        $data = $this ->get_userinfo($openid,$wx_aid);
        if(isset($data['remark'])){
            unset($data['remark']);
        }
	foreach ($data as $key => $value) {
		if (!$this->db->field_exists($key, 'me_wx_user')) {
			unset($data[$key]);
		}
	}
//        var_dump($data);
        $data['wx_aid'] = $wx_aid;
        $data['company_id'] = 1;
        $data['subscribe_time'] = date('Y-m-d H:i:s',$data['subscribe_time']);
        $data['created_at'] = date('Y-m-d H:i:s',time());
        // 下载图片
//        $data['localimgurl'] = $this ->get_img($data['headimgurl'],$data['openid'],$wx_aid);
        $sql = "SELECT id FROM ".$this ->db ->dbprefix('wx_user')."
              WHERE openid='$openid'";
        $status = $this ->db ->query($sql) ->num_rows();
        if($status){
          return $this ->db ->update('wx_user',$data,array('openid'=>$openid));
        }else{
          return $this ->db ->insert('wx_user',$data);
        }
//        file_put_contents('/home/test/sub123.txt',$this ->db ->last_query());
    }

    // 用户取消关注
    public function update_subscribe($openid){
        $sql = "UPDATE ".$this ->db ->dbprefix('wx_user')." SET subscribe=0
                WHERE openid='$openid'";
        return $this ->db ->query($sql);
    }

    // 下载图片
    private function get_img($imgurl,$openid='no',$wx_id){
        $imgurl = substr($imgurl,0,-1).'132';
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $imgurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);				// 设置抓取的数据的输出方式 1.文件流 0.直接输出
        //curl_setopt($ch, CURLOPT_POST, 1);						// POST数据
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);		// 把post的变量加上
        //curl_setopt($ch, CURLOPT_TIMEOUT,2);					// outtime
        $output = curl_exec($ch);
        curl_close($ch);
        $dirname='../uploads/mex/userimage/'.$wx_id;
        if(!file_exists($dirname)){
            mkdir($dirname,0777,true);
        }
        $imgurl=$dirname.'/'.$openid.'.gif';
        file_put_contents($imgurl,$output);
        return $openid.'.gif';
    }

    // 返回粉丝ID对应昵称
    public function userid_to_name(){
        $sql = 'SELECT id,nickname FROM '.$this ->db ->dbprefix('wx_user');
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        foreach($data as $v){
            $newData[$v['id']] = $v['nickname'];
        }
        return $newData;
    }

    /**
     * ===============================================
     * 粉丝标签模块
     * 1、获取 2、标记
     * ===============================================
     */
    // 获取粉丝标签
    public function get_user_tag($data){
        $wx_aid = $data['wx_aid'];
        $where =" wx_aid='$wx_aid'";
        if($data['group_id']){
            $groupid = $data['group_id'];
            $where .=" AND group_id='$groupid'";
        }
        if($data['openid']){
            $openid = $data['openid'];
            $where .=" AND openid='$openid'";
        }
        if($data['topNum']){
            $where .=' limit '.$data['topNum'];
        }
        $sql = 'SELECT wx_user_id,openid,tag_id,tagid_to_name,wx_aid,link_tag_hits,rule_tag_hits,manual_tag_hits,event_tag_hits
                FROM '.$this ->db ->dbprefix('rl_wx_user_tag').'
                '.$where;
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }
    // 粉丝标记标签
    public function user_mark($data){
        $data1['wx_aid'] = $data['wx_aid'];
        $data['wx_user_id'] = $data['wx_user_id'] ? $data['wx_user_id'] : 20;
        $data1['wx_user_id'] = $data['wx_user_id'];
        // 根据openid  获取wx_user_id group_id group_name
        $userData = $this ->select_user_info($data['wx_user_id']);
        $data1['openid'] = $userData['openid'];
        $data1['user_name'] = $userData['nickname'];
//        $data1['tag_id'] = $data['tag_id'];
        $tagid_to_name = $this ->tagid_to_name();
        // 查询是否存在，该用户标签信息
        $sql = 'SELECT tag_id FROM '.$this ->db ->dbprefix('rl_wx_user_tag').'
                WHERE wx_user_id="'.$data['wx_user_id'].'"
                AND wx_aid="'.$data['wx_aid'].'"';
        $dbData = $this ->db ->query($sql) ->result_array();
        // 该用户在数据库中已有的标签数组
        $dbNewData = array();
        foreach($dbData as $dbV){
            $dbNewData[] = $dbV['tag_id'];
        }

        $nowData = $data['tag_id'];

        // 第一步操作    没有传过来的，但数据库中有的
        $arr1 = array_diff($dbNewData,$nowData);
        if(count($arr1) > 0){
            foreach($arr1 as $v1){
                $array1['manual_tag_hits'] = 0;
                $where1 = array(
                    'wx_user_id'=>$data['wx_user_id'],
                    'tag_id'=>$v1
                );
                $this ->db ->update('rl_wx_user_tag',$array1,$where1);
            }
        }
        // 第二步操作    传过来的，但数据库中没有的
        $arr2 = array_diff($nowData,$dbNewData);
        if(count($arr2) > 0){
            foreach($arr2 as $v2){
                $data1['manual_tag_hits'] = 1;
                $data1['tag_id'] = $v2;
                $data1['tag_name'] = $tagid_to_name[$v2];
                $this ->db ->insert('rl_wx_user_tag',$data1);
            }
        }
        // 第三步操作    传过来的，数据库中有的
        $arr3 = array_intersect($dbNewData,$nowData);
        if(count($arr3) > 0){
            foreach($arr3 as $v3){
                $array3['manual_tag_hits'] = 1;
                $where3 = array(
                    'wx_user_id'=>$data['wx_user_id'],
                    'tag_id'=>$v3
                );
                $this ->db ->update('rl_wx_user_tag',$array3,$where3);
            }
        }
        return true;
    }


    //获取用户组统计数据
    public function get_user_data_statistics($group_id){
        $this->db->select("u.province,u.sex,u.purchasing_power");
        $this->db->from($this ->db ->dbprefix('wx_user')." AS u");
        $this->db->join($this ->db ->dbprefix('rl_wx_group_user')." AS rl","rl.wx_user_id=u.id");
        $this->db->where("rl.wx_group_id",$group_id);
        $data = $this->db->get()->result_array();
        return $data;
    }


}
