<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 作者:贾晋嵩
// 用途：调用微博通接口

class CI_Wbapi {
	public $appid; //微博类别 1 新浪 2 腾讯
	public $sina_wb_akey = ''; //新浪app key
	public $sina_wb_skey = ''; // 新浪app secret
	public $sina_wb_callback_url = ''; //新浪回调地址
	
	public $tencent_wb_akey = ''; //腾讯app key
	public $tencent_wb_skey = ''; // 腾讯app secret
	public $tencent_wb_callback_url = ''; //腾讯回调地址
	
	public $authtable='meo_wbto_weibo';
	public $since_table = 'meo_since_id';
	public $yundian_sina_wb_key ='';// cim计算用
	/**
	 * 微博api构造方法
	 * @param string $appinfo 应用的id app_id和是否是绑定操作
	 * @return void
	 */
	public function __construct ($appinfo=NULL)
	{
		$this->ci = &get_instance();
		$this->ci->load->helper('url');
		//载入新浪接口
		require dirname(__FILE__) . '/sdk/sinasdk.php';
		// 载入腾讯接口
		require dirname(__FILE__) . '/sdk/Tencent.php';
		$this->appid = $appinfo['app_id'];
		if ( !empty($appinfo) ) {
			$where = array(
					'id' => $appinfo['app_id']
			);
			$action = $appinfo['action'];
			
			//如果是绑定绑定
			if ( $action == 'bind' ) {
				$app = $this->ci->db->where($where)->get('application')->row_array();
				
				if (!empty($app))
				{
					//新浪微博
					if ($app['platform'] == '1')
					{
						$this->sina_wb_akey = $app['appkey'];
						$this->sina_wb_skey = $app['appskey'];
						$this->sina_wb_callback_url = $app['callbackurl'];
					}
					elseif ($app['platform'] == '2')
					{ //腾讯微博
						$this->tencent_wb_akey = $app['appkey'];
						$this->tencent_wb_skey = $app['appskey'];
						$this->tencent_wb_callback_url = $app['callbackurl'];
						OAuth::init($this->tencent_wb_akey, $this->tencent_wb_skey);
					}
				}
				else
				{
					return array(
							'code' => 400,
							'msg' => '没有找到应用信息请添加！'
					);
				}
			}
		}

		// cim计算用 --- begin
		$rlt = $this->ci->db->query("SELECT appkey,appskey FROM {$this->ci->db->dbprefix('application')} WHERE name='易活动' LIMIT 1")->result_array();
		$this->yundian_sina_wb_key=$rlt[0];
        // --------- end
	}
	
	/**
	 * 获取授权 
	 * @param string $pid 微博类别 1新浪 2 腾讯
	 */
	public function addwb($pid)
	{
		$s = base_url();
		$state = $s . ',' . $this->appid; 
		if ($pid == 1)
		{
			$oauth = new SaeTOAuthV2($this->sina_wb_akey, $this->sina_wb_skey);
			return $oauth->getAuthorizeURL($this->sina_wb_callback_url, 'code', $state);
		}
		elseif ($pid == 2)
		{
			return OAuth::getAuthorizeURL($this->tencent_wb_callback_url, 'code', FALSE, $state);
		}
	}

	/**
	 * 更新授权,使用add_wb_OAuth即可
	 * @param int $pid 微博类别 1 新浪 2 腾讯
	 */
	public function renew($pid)
	{
		return $this->addwb($pid);
	}

	/**
	 * 取消授权,只是删除绑定记录(因为可能该账号也绑在其他公司上)
	 * @param string $uid 解绑的微博uid
	 */
	public function revoke($uid)
	{
		$cid = $this->ci->session->userdata('company_id');
		if ($this->ci->db->query("delete from {$this->authtable} where company_id={$cid} and weibo_id='{$uid}'"))
		{
			//删除since_id记录
			$rst = $this->ci->db->query("delete from {$this->since_table} where weibo_id='{$uid}'");
			if ($rst)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * 已绑定的账号信息
	 * @param string $company_id
	 */
	public function path($company_id = '')
	{
		if ($company_id)
		{
			$rst = $this->ci->db->query("select pid,weibo_id,access_token, nickname, icon from {$this->authtable} where company_id=$company_id")->result_array();
			// echo $this->ci->db->last_query();
		}
		else
		{
			$rst = $this->ci->db->query("select pid,weibo_id,access_token, nickname, icon from {$this->authtable} where company_id={$this->ci->session->userdata('company_id')}")->result_array();
		}
		$auth = array( 
			1 => array(), 
			2 => array() 
		);
		foreach ($rst as $val)
		{
			$auth[$val['pid']][] = array( 
				'uid' => $val['weibo_id'], 
				'access_token' => $val['access_token'], 
				'nickname' => $val['nickname'], 
				'icon' => $val['icon'] 
			);
		}
		return $auth;
	}

	/**
	 * 根据uid获取access_token和pid的小方法
	 * @param string $uid 绑定微博账号的uid
	 */
	private function getATP($uid)
	{
		$auth = $this->ci->session->userdata('auth');
		
		$at = "";
		if (strlen($uid) == 32) //腾讯
		{
			$pid = 2;
			$app_id = $this->ci->session->userdata('app_id');
			$appinfo = $this->ci->db->where(array( 
				'id' => $app_id 
			))->get('meo_applications')->row_array();
			$this->tencent_wb_akey = $appinfo['appkey'];
			$this->tencent_wb_skey = $appinfo['appskey'];
			OAuth::init($this->tencent_wb_akey, $this->tencent_wb_skey);
			foreach ($auth[2] as $val)
			{
				if ($val['uid'] == $uid)
					$at = $val['access_token'];
			}
			
		}
		else //新浪
		{
			$pid = 1;
			foreach ($auth[1] as $val)
			{
				if ($val['uid'] == $uid)
				{
					$at = $val['access_token'];
				}
			}
		}
		session_write_close();
		return array( $pid, $at );
	}

	/**
	 * 腾讯时间线转新浪形式
	 * @param array $rdata
	 * @param boolean $st
	 */
	private function txtosina($rdata, $st = true)
	{
		//多条记录时
		if (isset($rdata['info']))
		{
			$info = $rdata['info'];
			$stafter = array();
			foreach ($info as $val)
			{
				$stafter[] = $this->statustosina($val);
			}
			if ($st)
			{
				$rt = array( 
					'statuses' => $stafter 
				);
				if (isset($rdata['totalnum']))
					$rt['total_number'] = $rdata['totalnum'];
				return $rt;
			}
			elseif ($st === false)
			{
				$rt = array( 
					'comments' => $stafter 
				);
				if (isset($rdata['totalnum']))
					$rt['total_number'] = $rdata['totalnum'];
				return $rt;
			}
			elseif ($st === 0)
			{
				$rt = array( 
					'reposts' => $stafter 
				);
				if (isset($rdata['totalnum']))
					$rt['total_number'] = $rdata['totalnum'];
				return $rt;
			}
			//单条记录
		}
		elseif (isset($rdata['id']))
		{
			return $this->statustosina($rdata);
		}
		else
			return false;
	}

	/**
	 * 单条微博转为新浪样式
	 * @param array $val
	 */
	public function statustosina($val)
	{
		$uid = $this->ci->session->userdata('uid');
		$tmp = array();
		$tmp['created_at'] = $val['timestamp'];
		$tmp['id'] = $val['id'];
		$tmp['mid'] = $val['id'];
		$tmp['idstr'] = $val['id'];
		$tmp['text'] = $val['text'];
		$tmp['source'] = '<a href="' . $val['fromurl'] . '" target="_blank">' . $val['from'] . '</a>';
		$tmp['pid'] = (strlen($uid) == 32 ? 2 : 1);
		$tmp['user'] = array( 
			'id' => $val['name'], 
			'idstr' => $val['name'], 
			'screen_name' => $val['nick'], 
			'name' => $val['name'], 
			'location' => $val['location'], 
			'profile_image_url' => $val['head'] ? $val['head'] . '/50' : '', 
			'verified' => $val['isvip'] 
		);
		if (is_array($val['source']))
		{
			$source = $val['source'];
			$tmp['retweeted_status'] = array( 
				'created_at' => $source['timestamp'], 
				'id' => $source['id'], 
				'mid' => $source['id'], 
				'idstr' => $source['id'], 
				'text' => $source['text'], 
				'source' => '<a href="' . $source['fromurl'] . '">' . $source['from'] . '</a>', 
				'user' => array( 
					'id' => $source['name'], 
					'idstr' => $source['name'], 
					'screen_name' => $source['nick'], 
					'name' => $source['name'], 
					'location' => $source['location'], 
					'profile_image_url' => $source['head'] ? $source['head'] . '/50' : '', 
					'verified' => $source['isvip'] 
				), 
				'reposts_count' => $source['count'], 
				'comments_count' => $source['mcount'] 
			);
		}
		$tmp['reposts_count'] = $val['count'];
		$tmp['comments_count'] = $val['mcount'];
		return $tmp;
	}

	/**
	 * 新浪时间转时间戳
	 * @param array $rst
	 */
	private function totimestamp(&$rst)
	{
		//如果是单条微博
		if (isset($rst['created_at']))
		{
			$rst['created_at'] = strtotime($rst['created_at']);
			$rst['user']['created_at'] = strtotime($rst['user']['created_at']);
			if (isset($rst['retweeted_status']))
			{
				$rst['retweeted_status']['created_at'] = strtotime($rst['retweeted_status']['created_at']);
				$rst['retweeted_status']['user']['created_at'] = strtotime($rst['retweeted_status']['user']['created_at']);
			}
			elseif (isset($rst['status']))
			{
				$rst['status']['created_at'] = strtotime($rst['status']['created_at']);
				$rst['status']['user']['created_at'] = strtotime($rst['status']['user']['created_at']);
			}
			
		}
		elseif (isset($rst['statuses'])) //多条,@我的
		{
			foreach ($rst['statuses'] as &$val)
			{
				$val['created_at'] = strtotime($val['created_at']);
				$val['user']['created_at'] = strtotime($val['user']['created_at']);
				unset($val['user']['remark']);
				if (isset($val['retweeted_status']))
				{
					$val['retweeted_status']['created_at'] = strtotime($val['retweeted_status']['created_at']);
					if (isset($val['retweeted_status']['user']))
					{
						$val['retweeted_status']['user']['created_at'] = strtotime($val['retweeted_status']['user']['created_at']);
					}
				}
			}
			//多条,评论我的
		}
		elseif (isset($rst['comments']))
		{
			foreach ($rst['comments'] as &$val)
			{
				$val['created_at'] = strtotime($val['created_at']);
				$val['user']['created_at'] = strtotime($val['user']['created_at']);
				unset($val['user']['remark']);
				$val['status']['created_at'] = strtotime($val['status']['created_at']);
				$val['status']['user']['created_at'] = strtotime($val['status']['user']['created_at']);
			}
		}
		elseif (isset($rst['reposts']))
		{
			foreach ($rst['reposts'] as &$val)
			{
				$val['created_at'] = strtotime($val['created_at']);
				$val['user']['created_at'] = strtotime($val['user']['created_at']);
				unset($val['user']['remark']);
				$val['retweeted_status']['created_at'] = strtotime($val['retweeted_status']['created_at']);
				if (isset($val['retweeted_status']['user']))
				{
					$val['retweeted_status']['user']['created_at'] = strtotime($val['retweeted_status']['user']['created_at']);
				}
			}
		}
	
	}
	
	/**
	 * 获取得到我的微博
	 * @param string 	$uid
	 * @param int 		$count 新浪 单页返回的记录条数，最大不超过200，默认为20。腾讯 1-70条
	 * @param int 		$page 起始页
	 * @param string 	$since_id 若指定此参数，则只返回ID比since_id大的微博消息（即比since_id发表时间晚的微博消息）
	 * @param string 	$max_id
	 * @param int 		$pageflag
	 * @param int 		$pagetime
	 * @param string 	$atoken
	 */
	public function mentions($uid, $count = 10, $page = 1, $since_id = null, $max_id = null, $pageflag = 0, $pagetime = 0, $atoken = NULL)
	{
		/*if ($atoken)
		{
			$pid = 1;
		}
		else
		{
			list($pid, $atoken) = $this->getATP($uid);
		}*/
		list($pid,$atoken)=$this->getATP($uid);
		echo 'pid:' .$pid.'<br/>';
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$rst = $sina->mentions($page, $count, $since_id, $max_id);
			$this->totimestamp($rst);
			return $rst;
		}
		elseif ($pid == 2)
		{
			Tencent::init($atoken, $uid);
			$params = array( 
				'pageflag' => $pageflag, 
				'pagetime' => $pagetime, 
				'reqnum' => $count, 
				'lastid' => 0, 
				'type' => 3, 
				'contenttype' => 0 
			);
			$r = Tencent::api('statuses/mentions_timeline', $params, 'GET');
			return $this->txtosina($r['data']);
		}
	}

	//获取回复我的微博
	//$count 新浪 单页返回的记录条数，最大不超过200，默认为20。腾讯 1-70条
	public function comments_to_me($uid,$count=50,$page=1,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0, $atoken = NULL){
		/*if($atoken)
		{
			$pid = 1;
		} 
		else 
		{
			list($pid,$atoken)=$this->getATP($uid);
		}*/
		list($pid,$atoken)=$this->getATP($uid);
		echo 'pid:' .$pid.'<br/>';

		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->comments_to_me($page,$count,$since_id,$max_id);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'pageflag'=>$pageflag,
				'pagetime'=>$pagetime,
				'reqnum'=>$count,
				'lastid'=>0,
				'type'=>72,
				'contenttype'=>0
			);
			$r = Tencent::api('statuses/mentions_timeline', $params, 'GET');
			// return $r;
			return $this->txtosina($r['data'],false);
		}
	}

	//获取单条微博
	//参数：uid,微博状态id
	public function show($uid,$statusid){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->show_status($statusid);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'id'=>$statusid
			);
			$r = Tencent::api('t/show', $params, 'GET');
			return $this->txtosina($r['data'],false);
		}
	}
	
	// 评论并转发单条微博
	// 参数：uid，评论的微博id，内容，是否回复同时转发，$is_comment为转发的同时发表评论【0不评论；1评论给当前微博；2给原微博；3评论给所有微博（原微博和当前）】
	public function is_repost($uid,$statusid,$comment,$comment_ori=false,$is_comment=2){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->repost($statusid,$comment,$is_comment);
			// return $rst;
			if(isset($rst['error'])){
				if($rst['error_code'] == 20101){
	        		return array('code'=>700,'messages'=>'该微博已经被删除');
	        	}
	            return array('code'=>400, 'messages'=>'false');
			}
			return array('code'=>200, 'messages'=>'success','rep_id'=>$rst['id'],'data'=>$rst);
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'reid'=>$statusid,
				'content'=>$comment
			);
			$r = Tencent::api('t/repost', $params, 'POST');
			if($r['errcode']==0)
				return true;
			return false;
		}
	}

	//删除单条微博
	//参数：uid,微博状态id
	public function destroy($uid,$statusid){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->destroy($statusid);
			if(isset($rst['error']))
				return false;
			return true;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'id'=>$statusid
			);
			$r = Tencent::api('t/del', $params, 'POST');
			if($r['errcode']==0)
				return true;
			return false;
		}
	}

	//评论单条微博 
	//参数：uid,评论的微博id，内容，是否转发
	public function comment($uid,$statusid,$comment,$comment_ori=false){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->send_comment($statusid,$comment,$comment_ori);
			// return $rst;
			if(isset($rst['error'])){
				if($rst['error_code'] == 20101){
	        		return array('code'=>700,'messages'=>'该微博已经被删除');
	        	}else if($rst['error_code'] == 20019){
	        		return array('code'=>900,'messages'=>'不要太贪心，发一次就够喽');
	        	}
	            return array('code'=>400, 'messages'=>'评论失败');
			}
			return array('code'=>200, 'messages'=>'success','com_id'=>$rst['id']);
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'reid'=>$statusid,
				'content'=>$comment
			);
			$r = Tencent::api('t/comment', $params, 'POST');
			if($r['errcode']==0)
				return true;
			return false;
		}
	}

	//删除单条评论
	//参数：uid,评论的微博id
	public function comment_destroy($uid,$statusid){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->comment_destroy($statusid);
			if(isset($rst['error']))
				return false;
			return true;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'id'=>$statusid
			);
			$r = Tencent::api('t/del', $params, 'POST');
			if($r['errcode']==0)
				return true;
			return false;
		}
	}

	//对一条评论进行回复  
	//参数：uid,评论id,内容，微博id(新浪用)
	public function reply($uid,$commentid,$text,$statusid=null){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->reply($statusid,$text,$commentid);
			if(isset($rst['error']))
				return false;
			return $rst['id'];
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'reid'=>$commentid,
				'content'=>$text
			);
			$r = Tencent::api('t/reply', $params, 'POST');
			if($r['errcode']==0)
				return true;
			return false;
		}
	}

	// 已发布微博
	// 参数：uid,数量，页码，最小id，最大id，腾讯用
	public function status($uid,$count=50,$page=1,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->user_timeline_by_id($uid,$page,$count,$since_id,$max_id);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'pageflag'=>$pageflag,
				'pagetime'=>$pagetime,
				'reqnum'=>$count,
				'lastid'=>0,
				'type'=>3,
				'contenttype'=>0
			);
			$r = Tencent::api('statuses/broadcast_timeline', $params, 'GET');
			return $this->txtosina($r['data']);
		}
	}

	//获取用户发送的微博
	function user_timeline_by_id($uid,$screen_name,$feature =0,$count=50,$page=1,$trim_user = 0,$st=0,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0){
	        //$sina=new SaeTClientV2('','',$this->yundian_sina_wb_access_token);
	        $sina=new SaeTClientV2($this->yundian_sina_wb_key['appkey'],$this->yundian_sina_wb_key['appskey'],'');
			$rst=$sina->user_timeline_by_id($uid,$page,$count,$since_id,$max_id ,$feature,$trim_user);
			if($st<1){
				$this->totimestamp($rst);
			}
			return $rst;
	}

	//发表微博
	//参数：uid,内容
	public function update($uid,$status){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->update($status);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'content'=>$status
			);
			$r = Tencent::api('t/add', $params, 'POST');
			if($r['errcode']==0)
				return array(
						'id'=>$r['data']['id'],
						'created_at'=>$r['data']['time']
						);
			return false;
		}
	}

	// 发布图片微博 
	// 参数：aid，是否包含new_status字段，开始的微博id
	public function upload($uid,$status,$pic_path){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->upload($status,$pic_path);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'content'=>$status
			);
			$multi = array('pic' => $pic_path);
			$r = Tencent::api('t/add_pic', $params, 'POST',$multi);
			if($r['errcode']==0){
					return $r['data']['id'];
				}
			return false;
		}
	}

	// 获取两个用户关系 
	// 参数：uid,sourceid,targetid
	public function friendships_show($uid,$target_id){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->is_followed_by_id($target_id,$uid);
			if(isset($rst['error']))
				return false;
			$followed_by=$rst['source']['followed_by'];
			$following=$rst['source']['following'];
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'names'=>$target_id,
				'flag'=>2
			);
			$r = Tencent::api('friends/check', $params, 'GET');
			// return $r;
		}
		if($r['errcode']!=0)
			return false;
		foreach($r['data'] as $val){
			$followed_by=$val['isfans'];
			$following=$val['isidol'];
		}
		return $followed_by==$following ? ($followed_by ? 4:1) : ($followed_by ? 3:2);
	}

	// 搜索微博  
	// 参数：关键字，aid,数量，页码
	public function search($uid,$keyword,$count=50,$page=1){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			// q string 搜索的关键字，必须进行URLencode。
			// filter_ori int 过滤器，是否为原创，0：全部、1：原创、2：转发，默认为0。
			// filter_pic int 过滤器。是否包含图片，0：全部、1：包含、2：不包含，默认为0。
			// fuid int 搜索的微博作者的用户UID。
			// province int 搜索的省份范围，省份ID。
			// city int 搜索的城市范围，城市ID。
			// starttime int 开始时间，Unix时间戳。
			// endtime int 结束时间，Unix时间戳。
			// count int 单页返回的记录条数，默认为10。
			// page int 返回结果的页码，默认为1。
			// needcount boolean 返回结果中是否包含返回记录数，true：返回、false：不返回，默认为false。
			// base_app int 是否只获取当前应用的数据。0为否（所有数据），1为是（仅当前应用），默认为0。
			// needcount参数不同，会导致相应的返回值结构不同 以上参数全部选填
			$params=array(
				'q'=>urlencode($keyword),
				'count'=>$count,
				'page'=>$page,
				'needcount'=>false
			);
			$rst=$sina->search_statuses_high($params);
			// $this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			// Tencent::init($atoken,$uid);
			// $params = array(
			// 	'pageflag'=>$pageflag,
			// 	'pagetime'=>$pagetime,
			// 	'reqnum'=>$count,
			// 	'lastid'=>0,
			// 	'type'=>3,
			// 	'contenttype'=>0
			// );
			// $r = Tencent::api('statuses/broadcast_timeline', $params, 'GET');
			// return $this->txtosina($r['data']);
		}
	}

	// 指定的单条微博的评论
	// 参数：uid,微博状态id，数量，页码，最小，最大，腾讯，腾讯
	public function comments($uid,$statusid,$count=50,$page=1,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->get_comments_by_sid($statusid,$page,$count,$since_id,$max_id);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'flag'=>1,
				'rootid'=>$statusid,
				'pageflag'=>$pageflag,
				'pagetime'=>$pagetime,
				'reqnum'=>$count,
				'twitterid'=>0
			);
			$r = Tencent::api('t/re_list', $params, 'GET');
			return $this->txtosina($r['data'],false);
		}
	}

	//单条微博的转发列表
	public function repost_timeline($uid,$statusid,$count=50,$page=1,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->repost_timeline($statusid,$page,$count,$since_id,$max_id);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'flag'=>0,
				'rootid'=>$statusid,
				'pageflag'=>$pageflag,
				'pagetime'=>$pagetime,
				'reqnum'=>$count,
				'twitterid'=>0
			);
			$r = Tencent::api('t/re_list', $params, 'GET');
			return $this->txtosina($r['data'],0);
		}
	}

	/**
	 * 获取关注人发的微博列表
	 * @param string $uid		微博uid
	 * @param int $count		每页数量
	 * @param int $page			当前页
	 * @param string $since_id	since_id
	 * @param string $max_id	
	 * @param int $pageflag		
	 * @param int $pagetime		
	 * @return array			
	 */
	public function friends_timeline($uid,$count=50,$page=1,$since_id=null,$max_id=null,$pageflag=0,$pagetime=0){
		list($pid,$atoken)=$this->getATP($uid);
		if($pid==1){
			$sina=new SaeTClientV2($this->sina_wb_akey,$this->sina_wb_skey,$atoken);
			$rst=$sina->home_timeline($page,$count,$since_id,$max_id);
			$this->totimestamp($rst);
			return $rst;
		}elseif($pid==2){
			Tencent::init($atoken,$uid);
			$params = array(
				'pageflag'=>$pageflag,
				'pagetime'=>$pagetime,
				'reqnum'=>$count,
				'type'=>3,
				'contenttype'=>0
			);
			$r = Tencent::api('statuses/home_timeline', $params, 'GET');
			return $this->txtosina($r['data']);
		}
	}

	/**
	 * 单个用户信息转为新浪格式
	 * @param array $r
	 */
	public function usertosina($r){
		$tmp=array(
			'id'=>$r['name'],
			'idstr'=>$r['name'],
			'screen_name'=>$r['nick'],
			'name'=>$r['name'],
			'location'=>$r['location'],
			'description'=>$r['introduction'],
			'url'=>$r['homepage'],
			'profile_image_url'=>$r['head'] ? $r['head'].'/50' : '',
			'profile_url'=>$r['name'],
			'gender'=>$r['sex']==1 ? 'm' : ($r['sex']==2 ? 'f' : 'n'),
			'followers_count'=>$r['fansnum'],
			'friends_count'=>$r['idolnum'],
			'statuses_count'=>$r['tweetnum'],
			'created_at'=>$r['regtime'],
			'following'=>$r['ismyidol'],
			'verified'=>$r['isvip'],
			'status'=>array(
				'created_at'=>$r['tweetinfo'][0]['timestamp'],
				'id'=>$r['tweetinfo'][0]['id'],
				'mid'=>$r['tweetinfo'][0]['id'],
				'idstr'=>$r['tweetinfo'][0]['id'],
				'text'=>$r['tweetinfo'][0]['text'],
				'source'=>'<a href="'.$r['tweetinfo'][0]['fromurl'].'">'.$r['tweetinfo'][0]['from'].'</a>'
			),
			'follow_me'=>$r['ismyfans'],
			'bi_followers_count'=>$r['mutual_fans_num']
		);
		return $tmp;
	}
	
	/**
	 * 查看单个用户资料   
	 * @param string $uid
	 * @param string $userid
	 */
	public function user_show($uid, $userid)
	{
		list($pid, $atoken) = $this->getATP($uid);
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$rst = $sina->show_user_by_id($userid);
			return $rst;
		}
		elseif ($pid == 2)
		{
			Tencent::init($atoken, $uid);
			$params = array( 
				'name' => $userid 
				// 'fopenid'=>$userid
				);
			$r = Tencent::api('user/other_info', $params, 'GET');
			if ($r['errcode'] == 0)
			{
				$r = $r['data'];
				$tmp = $this->usertosina($r);
			}
			else
				$tmp = false;
			return $tmp;
		}
	}
	
	/**
	 * 批量获取用户资料
	 * @param string $uid 用户id
	 * @param string $names 批量用户名（用英文','分割）
	 */
	public function userinfo($uid, $names)
	{
		list($pid, $atoken) = $this->getATP($uid);
		Tencent::init($atoken, $uid);
		$params = array( 
			'names' => $names 
		);
		$r = Tencent::api('user/infos', $params, 'GET');
		return $r;
		// if($r['errcode']==0){
		// 	$r=$r['data'];
		// 	$tmp=$this->usertosina($r);
		// }else
		// 	$tmp=false;
		// return $tmp;
	}

	/**
	 * 查看粉丝列表
	 * @param string $uid 微博uid
	 * @param int $count 每页总数
	 * @param int $cursor 
	 */
	public function followers($uid, $count = 50, $cursor = 0)
	{
		list($pid, $atoken) = $this->getATP($uid);
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$rst = $sina->followers_by_id($uid, $cursor, $count);
			return $rst;
		}
		elseif ($pid == 2)
		{
			Tencent::init($atoken, $uid);
			$params = array( 
				'reqnum' => $count, 
				'startindex' => $cursor, 
				'mode' => 1 
			);
			$r = Tencent::api('friends/fanslist', $params, 'GET');
			if ($r['errcode'] == 0)
			{
				$r = $r['data'];
				$users = array();
				foreach ($r['info'] as $val)
				{
					$tmp = array( 
						'id' => $val['name'], 
						'idstr' => $val['name'], 
						'screen_name' => $val['nick'], 
						'name' => $val['name'], 
						'location' => $val['location'], 
						'profile_image_url' => $val['head'] ? $val['head'] . '/50' : '', 
						'profile_url' => $val['name'], 
						'gender' => $val['sex'] == 1 ? 'm' : ($val['sex'] == 2 ? 'f' : 'n'), 
						'followers_count' => $val['fansnum'], 
						'friends_count' => $val['idolnum'], 
						'following' => $val['isidol'], 
						'verified' => $val['isvip'], 
						'status' => array( 
							'created_at' => $val['tweet'][0]['timestamp'], 
							'id' => $val['tweet'][0]['id'], 
							'mid' => $val['tweet'][0]['id'], 
							'idstr' => $val['tweet'][0]['id'], 
							'text' => $val['tweet'][0]['text'] 
						), 
						'follow_me' => 1 
					);
					$users[] = $tmp;
				}
				$tmp = array( 
					'users' => $users, 
					'next_cursor' => $r['nextstartpos'] 
				);
			}
			else
				$tmp = false;
			return $tmp;
		}
	}

	//微博通部分开始
	/**
	 * 用于post方式接口
	 * @param unknown_type $url
	 * @param unknown_type $postarr
	 */
	public function wbto_post($url,$postarr){
		$ch=curl_init();
		$optarr=array(
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,	//文本流形式返回
			CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,	//指定验证方式
			CURLOPT_USERPWD=>"mastest:123",
			CURLOPT_POST=>true,
			CURLOPT_POSTFIELDS=>$postarr
		);
		curl_setopt_array($ch,$optarr);
		$output=curl_exec($ch);
		curl_close($ch);
		return json_decode($output,true);
	}
	
	/**
	 * 内容分类  
	 * @return mixed
	 */
	public function category(){
		$url='http://wbto.cn/api/content/category.json';
		$data=array('source'=>'31a4eb9ffdba03a2f689e40c0c9d44f6');
		return $this->wbto_post($url,$data);
	}

	/**
	 * 内容列表 参数：类别id，数量，页码，关键字
	 * @param int $cid 公司id
	 * @param int $count 每页总数
	 * @param int $page 当前页
	 * @param string $q 
	 * @param int $rand
	 * @return array
	 */
	public function contentlist($cid = 0, $count = 20, $page = 1, $q = '', $rand = 0)
	{
		$url = 'http://wbto.cn/api/content/index.json';
		$data = array( 
			'source' => '31a4eb9ffdba03a2f689e40c0c9d44f6', 
			'cid' => $cid, 
			'count' => $count, 
			'page' => $page, 
			'q' => $q, 
			'rand' => $rand 
		);
		return $this->wbto_post($url, $data);
	}
	//微博通结束

	/**
	 * 批量获取新浪微博用户的标签
	 * @param array $uids  长度为20的uid数组
	 * @param string $uid  绑定用户uid，获取accesstoken
	 * @return array 返回新浪返回的标签数组
	 */
	public function get_users_tags($uids, $uid)
	{
		$tags_list = array();
		list($pid, $atoken) = $this->getATP($uid);
		
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$tags_list = $sina->get_tags_batch($uids);
		}
		return $tags_list;
	} 
	
	/**
	 * 单个获取新浪微博用户的标签
	 * @param string $touid 需要获取的用户uid
	 * @param string $uid 操作的uid
	 */
	public function get_single_tags($touid, $uid)
	{
		list($pid, $atoken) = $this->getATP($uid);
		
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$tags_list = $sina->get_tags($touid, 1, 200); //每用户最大取到200个标签
			
		}
		return $tags_list;
	}
	
	/**
	 * 批量获取用户的粉丝数、关注数、微博数
	 * @param string $uids  最长100个以逗号","连接
	 * @param string $uids  通过该uid抓取
	 * @param string $atoken
	 * @param int $pid  微博类型 1新浪 2 腾讯
	 * @return Ambigous <multitype:, mixed, string>
	 */
	public function get_followers_attentions_batch($uid, $uids, $atoken = '', $pid = 1)
	{
		if (empty($atoken))
		{
			list($pid, $atoken) = $this->getATP($uid);
		}
		if ($pid == 1)
		{
			$sina = new SaeTClientV2(WB_AKEY, WB_SKEY, $atoken);
			$followers_attentions_list = $sina->user_counts($uids); //每次最大20个标签
		}
		if ($pid == 2)
		{
			$followers_attentions_list = array();
		}
		
		return $followers_attentions_list;
	}
	
	/**
     * 微博私信回复
     * @param  mixed   $reply     回复内容，如果是文字回复则是string，其他为array
     * @param  string  $rtype     回复类型
     * @param  boolean $deal_weibo_id 是否是人工回复
     * @return array              回复是否成功的数组
     */
    public function send_reply_msg($reply, $rtype, $deal_weibo_id)
	{
		// 判断回复类型，拼装post
		switch ($rtype)
		{
			case 'text' :
				$data['text'] = $reply;
				$data = urlencode(json_encode($data));
				$post_data = "source=2949572520&id=" . $deal_weibo_id . "&type=text&data=" . $data;
				break;
			
			case 'pic' :
				$data['articles'] = array();
				foreach ($reply as $v)
				{
					$article = array();
					$article['display_name'] = $v['title'];
					$article['summary'] = $v['desc'] ? $v['desc'] : $v['title'];
					$article['image'] = $v['pic'];
					$article['url'] = $v['url'];
					$data['articles'][] = $article;
				}
				$d = json_encode($data);
				$data = urlencode(json_encode($data));
				$post_data = "source=2949572520&id=" . $deal_weibo_id . "&type=articles&data=" . $data;
				$reply = serialize($reply);
				break;
		}
		// curl调用接口发送私信回复
		$url = "https://m.api.weibo.com/2/messages/reply.json";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_USERPWD, 'event@masengine.com:mas123');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$output = curl_exec($ch);
		curl_close($ch);
		
		// 获取私信回复发送的结果
		$rst = json_decode($output, TRUE);
		// var_dump($rst);
		// exit;
		if (isset($rst['error']))
		{
			if ($rst['error_code'] == 26403)
			{
				return array( 
					'code' => 700, 
					'messages' => '信息已经被回复或超时' 
				);
			}
			return array( 
				'code' => 400, 
				'messages' => '回复失败' 
			);
		}
		
		// 如果回复成功，记录私信回复id
		// $reply_msg_id = $rst['id'];
		// 更新staff_deal
		// $this->update_staff_deal($reply, $reply_msg_id);
		
		return array( 
			'code' => 200, 
			'messages' => '私信回复成功' 
		);
	}

    /**
     * 查询接口频次状况
     * @param string $uid 微博uid
     */
    public function rate_limit_status($uid)
	{
		list($pid, $atoken) = $this->getATP($uid);
		if ($pid == 1)
		{
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$rst = $sina->rate_limit_status();
			return $rst;
		}
	}

    /**
     * 获取关键词抓取的微博 腾讯
     * @param string $uid 腾讯微博uid
     * @param string $keyword 关键字
     * @param int $pagesize
     * @param int $TXpage
     * @param datetime $txtime 时间
     * @param string $atoken  应用授权
     * @return array 成功信息
     */
	public function kw($uid, $keyword = NULL, $pagesize = 0, $TXpage = 0, $txtime, $atoken = NULL)
	{
		if ($atoken)
		{
			$pid = 1;
		}
		else
		{
			list($pid, $atoken) = $this->getATP($uid);
		}
		
		if ($pid == 2)
		{
			Tencent::init($atoken, $uid);
			$params = array( 
				'format' => 'json', 
				'keyword' => $keyword, 
				'pagesize' => $pagesize, 
				'page' => $TXpage, 
				'contenttype' => 0, 
				'sorttype' => 1, 
				'msgtype' => 0, 
				'searchtype' => 0, 
				'starttime' => $txtime, 
				'endtime' => time(), 
				'longitue' => null, 
				'latitude' => null, 
				'radius' => 20000, 
				'province' => null, 
				'city' => null, 
				'needdup' => 1 
			);
			$r = Tencent::api('search/t', $params, 'GET');
			return $this->txtosina($r['data']);
		}
	}
    
    /**
     + function querymid
     + 根据微博ID获取微博mid
     + @param int|string $uid 获取ATP的uid
     + @param int|string $weiboid 微博的ID
     + @param 1|0 是否批量获取
     + @return array $mid
    */
     function querymid($uid, $weiboid, $is_batch = 0)
     {
    	list($pid, $atoken) = $this->getATP($uid);
    	// 新浪接口
    	if ($pid == 1) {
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$mid = $sina->querymid($weiboid, 1, $is_batch);
			return $mid;
		}
		// 非新浪接口
		return array('error'=>'not found');
     }

	/**
	 + function shorten_url
	 + 长链转短链
	 + @param $url_long
	 + @return result
	**/
	function shorten_url($uid, $url_long)
	{
		list($pid, $atoken) = $this->getATP($uid);
		// 新浪接口
		if ($pid == 1) {
			$sina = new SaeTClientV2($this->sina_wb_akey, $this->sina_wb_skey, $atoken);
			$res = $sina->shorten($url_long, $atoken);
			return $res;
		} else {
		// 腾讯接口，暂时为空
			return ;
		}
	}

}