<?php    
/**
 * H5page 前端处理
 *
 * PHP version 5
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @link      http://www.masengine.com
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * H5page 前端处理
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class Wxh5_Ext extends CI_Controller
{
	/**
	 * 预处理
	 *
	 * @return  void [description]
	 */
	public function __construct()
	{
		parent::__construct();
		$this->ses = $this->session->all_userdata();
		$this->s = isset($this->ses['ok']) ? true : false ;
		$this->load->model('h5page/h5page_model');
		$this->load->model('h5page/linkh5_model');
		$this->load->model('mex/user_model','user');
		//表名称转换
		$this->tb = $this->config->item('H5page_table');
	}    


	public function submiter()
	{
		$this->h5page_model->submiter();
		return false;
	}


    /**
     * 用户在H5页面提交信息的时候 用这个接口来接收并存储
     */
    public function handle_submit(){
        $post_info = $this->input->post(null, true); //post过来的表单数据 包括用户的姓名、手机号
        $get_info = $this->input->get(null, true); //get过来的url传参 包括h5_id:h5页面的id event_id:活动id code:微博或微信发来的用户信息code source:提交的来源 wb或wx wx_aid:微信的application id

        // 微信网页不弹窗 授权
        //if(isset($get_info['code']) && isset($get_info['wx_aid'])){
        if(true){ //测试用
           // $this->load->library('Wxapi');
           // $wx_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
           // $accountData = $this->wxapi->get_appid_secret($get_info['wx_aid']);  //默认从session中获取wx_aid 此处是远程提交 无员工登录 没有相应的session
           // $param = array(
           //     'appid'=>$accountData['appid'],
           //     'secret'=>$accountData['secret'],
           //     'code'=>$get_info['code'],
           //     'grant_type'=>'authorization_code'
           // );
           // // 获取 到用户的openid
           // // 本身 h5页面的id  $g['id']
           // $returnData = $this->wxapi->request($wx_url,$param);
           // $returnData = json_decode($returnData,true);
           // $openid = $returnData['openid'];


            $h5_part_user = $this->db->select('mobile')->where(array('mobile'=>$post_info['4']))->get($this->tb['participants'])->row_array();  //从数据库中查找该手机是否提交过

            //如果提交过这个手机号码
            if(!empty($h5_part_user)){
                echo 'resubmit';
                return false;
            }

            //插入的时候先插入到h5_participants表中 后续会有相应的定时任务从ht_participants表中抓取在活动时间段内的用户到event_participants表中
            $insert_h5_part = array( //往h5_participants表中插入的信息
                'time' => time(),  //提交信息的时间
                'event_id' => $post_info['event_id'],  //活动id
                'page_id' => $post_info['h5_id'],  //H5页面id
                //'openid' => $openid, //通过code 获取到的openid
                'name' => $post_info['3'],
                'mobile' => $post_info['4'],
            );

            $this->db->insert($this->tb['participants'], $insert_h5_part);
            $insert_rs = $this->db->insert_id();

            //insert成功 echo 'success' 失败 echo 'no'
            if($insert_rs){
                echo 'success';
                return true;
            }else{
                echo 'no';
                return false;
            }
            //如果没有传相应的code 和 应用的id wx_aid
        }else{
            echo 'failed';
            return false;
        }
    }


	// 用户访问页面时，找到该页面信息
	public function go()
	{
		$g = $this->input->get();
		$uid = isset($g['uid']) ? $g['uid'] : null ;
		$openid = isset($g['openid']) ? $g['openid'] : null;

		if(strpos($g['id'],'-')){
			$idV = explode('-',$g['id']);
			$g['id'] = $idV[0];
			$g['wx_aid'] = $idV[1];
			$g['send_id'] = $idV[2];
		}
		// 判断是微博/PC访问还是微信访问，定义不同变量
		$identify = 'uid';
		if (empty($uid)) $identify = 'openid';

		// 查找对应页面信息
		$page_info = $this->h5page_model->get_h5page_info($g['id']);
		if (empty($page_info)) exit('这个页面已经找不到了，可能是在回收站，也可能静静的躺在服务器磁盘某个分区的磁道里面。又或者，他早已被服务器上新建的信息淹没了。总之，我劝你不要再看下去了。因为人生就是这样，有时候你想起来要看什么东西，但他早已不在了，丢的连个踪影都找不到，好像从来都没有存在过一样！！');

		if ($page_info['template'] == 'custom') 
			header('location:'.$page_info['html_code']);

		$session = array(
			'info'                => $uid, //旧版本兼容
			'uid'                 => $uid,
			'openid'              => $openid,
			'identify'            => $identify,
			$this->tb['h5page'] => $g['id'],
			'user_b_id'           => $page_info['company_id'],
		);
		$this->session->set_userdata($session);

		// 微信网页不弹窗 授权
		if(isset($g['code']) && $g['send_id'] != 0){
			$this->load->library('Wxapi');
			$wx_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
			$wx_aid = $g['wx_aid'];
			$accountData = $this->wxapi->get_appid_secret($wx_aid);
			$param = array(
				'appid'=>$accountData['appid'],
				'secret'=>$accountData['secret'],
				'code'=>$g['code'],
				'grant_type'=>'authorization_code'
			);
			// 获取 到用户的openid
			// 本身 h5页面的id  $g['id']
			$returnData = $this->wxapi->request($wx_url,$param);
			$returnData = json_decode($returnData,true);
			$openid = $returnData['openid'];
			//添加到群发统计表中  me_send_stat
			$sendStat['send_id']=$g['send_id'];
			$sendStat['openid']=$openid;
			// 通过openid获取area与sex
			$userData = $this->user->get_area_sex($openid);
			$sendStat['area']=$userData['province'].$userData['city'];
			$sendStat['sex']=$userData['sex'];
			$sendStat['created_at']=date('Y-m-d',time());
			$this->db->insert('send_stat',$sendStat);


			// 获取到的openid 来完善   rl_wx_user_tag

			// 1 通过h5_page id获取h5页标签
			$sql = 'SELECT tag_id FROM '.$this->db->dbprefix('rl_h5_page_tag').' WHERE h5_page_id='.$g['id'];
			$tagData = $this->db->query($sql)->result_array();
			// 2 通过openid获取用户user_id
			// 将h5标签id 用户id wx_aid openid  若不存在则添加：link_tag_hits为1.00   若存在link_tag_hits +1
			$sql = "SELECT id FROM ".$this->db->dbprefix('wx_user')." WHERE openid='$openid'";
			$userid = $this->db->query($sql)->result_array();
			$userid = $userid[0]['id'];
			$str = '';
			foreach($tagData as $tagV){
				$str .="('$userid','$openid','$tagV[tag_id]','$wx_aid',1),";
			}
			$str = rtrim($str,',');
			$sql = 'INSERT INTO '.$this->db->dbprefix('rl_wx_user_tag').'(`wx_user_id`,`openid`,`tag_id`,`wx_aid`,`link_tag_hits`)
						VALUES'.$str.'
						ON DUPLICATE KEY UPDATE link_tag_hits=link_tag_hits+1';
			$this->db->query($sql);
		}

		$this->load->view(H5PAGE_TPL_PATH . 'tpl_' . $page_info['template'] . '.html', array('htmls' => $page_info)); //输出网页头
		$this->load->view(H5PAGE_TPL_PATH . 'tpl_footer.html');
	}

	/* update_hits 更新H5页面的点击量 */
	public function update_hits ($h5page_id) 
	{
		$is_preview = $this->input->get_post('preview');

		if ( ! $is_preview) return TRUE;
		$this->db->set('hits', 'hits+1', FALSE)->where('id', $h5page_id)->update($this->tb['h5page']);
	}

	/* log_view 统计授权H5页面访问日志 */
	/**
	 * @param $h5page_id H5页面Id
	 * @param $openid 访问用户的微信openid获取user_weibo_id
	 * @param $type 访问类型 wb|wx
	 * 
	 * @return 无返回值
	 **/
	public function view_log ($h5page_id, $openid, $type) 
	{
		// 
	}

	/* H5页面参与活动 */
	public function participate () 
	{
		// 
	}

	// 前台AJAX调用该方法，获取页面html代码
	public function gethtml()
	{
		$html = $this->h5page_model->gethtml();
		echo $html;
	}

	public function mobile()
	{
		$tpl  = $this->input->get('tpl');
		$this->h5page_model->mobile($tpl);
		return false;
	}



	public function t2() 
	{
			$rst = $this->db->query("
						SELECT 
							us.city c , 
							COUNT(pa.uid ) cnt
						FROM meo_h5_participants pa
						LEFT JOIN meo_wx_users us
							ON pa.uid = us.uid 
						GROUP BY c
						ORDER BY cnt DESC
						LIMIT 10
						")->result_array();
			echo '<pre>';
			var_dump($rst);
	}
	public function t()
	{
		$id  = $this->input->get('id');
		$uid = $this->input->get('uid');
		echo $id,'==',$uid;
		
		$sql     = "delete from meo_h5_participants where activity = 30";
		$result  = $this->db->query($sql);//发送语句，返回结果集/bool
		
		$sql     = "select uid from meo_wx_users ";
		$resulter  = $this->db->query($sql)->result_array();//发送语句，返回结果集/bool
		if (empty($resulter)) {
			die('no uid');
		}
		
		$time =  strtotime(date('Y-m',time()));
		$time =  time() - 30 * 24 * 3600;
		
		foreach ($resulter as $k => $v) {
			$rand   = mt_rand(200,6099);
			$time   = $time + $rand ;
			$info   = rand(2,8) == 6 ? 'noooo' : mt_rand();
			$sql    = "insert into meo_h5_participants values(null,'{$info}', {$time} ,30,'{$v['uid']}',(floor(rand()*10)%2))";
			$result  = $this->db->query($sql);//发送语句，返回结果集/bool
			//echo $rst['uid'];
		
		}
	}


	/**
	 * 富文本编辑器里的图片上传
	 *
	 * @return void 
	 */
	public function editorUpload()
	{
		//var_dump($_FILES);
		$rst = $this->h5page_model->uploader('../public/uploads/h5page/' . $this->ses['userinfo']['id'] . '/h5page/img/', null, null, 'editor_');
		//var_dump($rst);
		/*if($rst['error'] == 0){
			$this->db->set(array(
							'user_b_id' => $this->ses['userinfo']['id'],
							'time' => time(),
							'filename' => $rst['message']['file_name'],
							));
			if (!$this->db->insert('picture')){
				die('sql error');
			};
		}*/
		
		header('Content-type: text/html; charset=UTF-8');
		$rst['width'] = '100%';
		$rst['style'] = 'width: 100%; height: auto; border: 0px;';
		echo json_encode($rst);
		exit;
	}

	public function picUpload()
	{
		//var_dump($_FILES);
		$rst = $this->h5page_model->uploader('../public/uploads/h5page/' . $this->ses['userinfo']['id'] . '/img/', null, null, 'photo_', 'Filedata');
		//var_dump($rst);
		if($rst['error'] == 0){

			//存图片表
			/*$this->db->set(array(
							'user_b_id' => $this->ses['userinfo']['id'],
							'time' => time(),
							'filename' => $rst['message']['file_name'],
							));
			if (!$this->db->insert('picture')){
				die('sql error');
			};*/


		}


		echo json_encode($rst);
	}


	/**
	 * H5广告 记录访问信息
	 * 
	 * @return string result
	 */
	public function clicklogger() 
	{
		$i = 1;
		$p = $this->input->post('click');
		$title = $this->input->post('title');
		$adid = $this->input->post('adid');  //广告id
		
		//h5 广告统计系统记录信息
		$insert = array(
				'ad' => $adid,
				'time' => time(),
				'participants_id'=> $this->ses['db_log_id'],
				'ip' => $_SERVER['REMOTE_ADDR'],
			);
		$this->db->insert($this->tb['ads_logger'], $insert);
		$ads = $this->db
			->where(array('id' => $adid))
			->get($this->tb['ads'])
			->result_array();
		$this->db
			->where(array('id' => $adid))
			->update($this->tb['ads'], array('count'=> $ads[0]['count'] + 1));
			

		//读出点击数据
		$id = $this->session->userdata($this->tb['activity']);
		$this->db->select('clickurl');
		$rst = $this->db
			->get_where($this->tb['activity'], array('id' => $id))
			->result_array();
		$rst    = json_decode($rst[0]['clickurl'], true);
		$identify    = $this->ses['identify'];
		$uid = $this->ses[$identify];
		if (empty($uid) and empty($openid)) {
			
			return 8;
		} else {
			$this->db->select('readurl');
			$isuser = $this->db->get_where($this->tb['participants'], array($identify => $uid))->result_array();
			
		}
		//数据加一
		if (isset($rst[$p])) {
			$rst[$p]['counter'] += 1;
		} else {
			$rst[$p]['counter'] = 1;
		}
		if (isset($rst[$p]['title'][$title])) {
			$rst[$p]['title'][$title] += 1 ;
		} else {
			$rst[$p]['title'][$title] = 1 ;
		}
		
		//写入
		$ok = $this->db->update($this->tb['activity'], array('clickurl' => json_encode($rst)), array('id' => $id));
		$ok2 = $this->db->update($this->tb['participants'], array('readurl' => 1), array($identify => $uid));

		echo ($ok && $ok2) == true ? 'ok' : '0';
		return  ($ok && $ok2) == true ? 'ok' : '0';
	}

	/**
	 * 转入授权
	 *
	 * @return [type] [description]
	 */
	public function gotoAuth()
	{
		$this->load->model('h5page/Linkh5');
		$this->Linkh5->nonUid();
	}



}
