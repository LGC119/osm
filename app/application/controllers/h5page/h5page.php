<?php
/**
 * H5后端服务支撑系统
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
 * H5后端服务支撑系统
 *
 * @category  H5page
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class H5page extends ME_Controller
{
	/**
	 * 初始化
	 *
	 * @return void [description]
	 */
	public function __construct()
	{
		parent::__construct();
		$this->ses = $this->session->all_userdata();
		$this->s = isset($this->ses['company_id']) ? true : false ;

		// 上传文件路径
		$this->load->model('h5page/linkh5_model');
		$this->load->model('h5page/h5page_model');
		$this->cid =  isset($this->ses['company_id']) ? 
		   $this->ses['company_id'] : 0 ;
		
		// 表名称转换
		$this->tb = $this->config->item('H5page_table');
		
	}

	//终端页面样例 解析当前用户配置
	public function mobile($tpl) 
	{ 
		$mef_item = array();
		$mef_item['url'] = base_url();
		$this->load->view(H5PAGE_TPL_PATH . 'tpl_' . $tpl . '.html',$mef_item); //输出网页头
		$this->load->view(H5PAGE_TPL_PATH . 'tpl_footer.html'); 
	}

	/**
	 * 生成H5页面
	 * 
	 * @param  array $p post
	 * 
	 * @return string Result    
	 */
	public function create()
	{
		$p = $this->input->post();
		// 将特殊符号换了
		if(isset($p['html'])){
			foreach($p['html'] as &$pV){
				if(gettype($pV) == 'string'){
					$pV = ltrim($pV,'./');
					$pV = str_replace('style=";','style="',$pV);
					$pV = str_replace('&#39;',"'",$pV);
				}
			}
		}
		$page_id = $this->h5page_model->insert_h5page($p);

		if ($page_id)
		{
			// 标签关联入库
			$rst = $this->h5page_model->rl_h5page_tag($page_id, $p['tags']);
			if ($rst) {
				$this->meret(array('id'=>$page_id), MERET_OK, 'H5页面创建成功');
			}
			else
			{
				$this->meret(array(), MERET_SVRERROR, 'H5页面标签关联失败');
			}
		} 
		else 
		{
			$this->meret(array(), MERET_SVRERROR, 'H5页面创建失败');
		}; 
	}

	//上传头图
	public function uploadfiles()
	{
		$this->load->helper('url');
		$config['upload_path']   = H5PAGE_UPLOAD_PATH . $this->cid . '/img/';
		$config['allowed_types'] = 'gif|jpg|png|bmp';
		$time                    = date('Ymd_His', time());
		$config['file_name']     = 'activity_' . $time . '_' . md5($time . rand(0,9999));

		if (!is_dir($config['upload_path'])) {
			mkdir($config['upload_path'],0777,true);
		}

		$this->load->library('upload', $config);
		if ( !$this->upload->do_upload('uppic')) {
			$error          = array('error' => $this->upload->display_errors());
			echo "<script type='text/javascript' charset='utf-8'>alert('上传失败了: {$error["error"]}');</script>";
		} else {
			$data = array('upload_data' => $this->upload->data());
			
			//绝对地址 网页根
			$filepath =$config['upload_path'] . $data['upload_data']['file_name'];
			
			//相对地址
			$filepath = ltrim($filepath,'./');
			$info = '<script type="text/javascript" charset="utf-8">parent.document.getElementById("picpath").innerHTML = "';
			$info .= "<center><img style='max-width:500px;max-height:350px;' id='newimg' src='{$filepath}' /></center>";
			$info .= '"</script>';

			echo $info;
		}
	} 
	

	/**
	 * 文件上传
	 * 
	 * @param string $upload_path   上传文件路径
	 * @param string $allowed_types 上传文件类型， gif|jpg|png|bmp
	 * @param string $file_name     给定文件名字  默认日期随机MD5
	 * @param string $name_prefix   文件名前缀
	 * @param string $uploadForm    上传字段名
	 * 
	 * @return array [result] 0失败,1成功
	 *               [msg]    文件信息
	 *               [path]   成功后的文件路径
	 **/
	public function uploader($upload_path = '../public/uploads/h5page/', $allowed_types = 'gif|jpg|png|bmp', $file_name = null, $name_prefix = 'activity_', $uploadForm = 'imgFile') 
	{
		$config['upload_path']   = $upload_path ===null ? '../public/uploads/h5page/' . $this->wx_id . '/h5page/img/' : $upload_path;
		$config['allowed_types'] = $allowed_types === null ? 'gif|jpg|png|bmp' : $allowed_types;
		
		$time                    = date('Ymd_His', time());
		$config['file_name']     = $file_name === null ? $name_prefix . $time . '_' . md5($time . rand(0,9999)) : $name_prefix . $file_name;
		
		//目录不存在就创建
		if (!is_dir($config['upload_path'])){
			mkdir($config['upload_path'],0777,true);
		}

		$this->load->library('upload', $config);

		//判断是否成功
		if ( !$this->upload->do_upload($uploadForm)) {
			$info['error']   = 1;
			$info['message'] = $this->upload->display_errors();
			//$info['url']   = '';
		} else {
			$path = explode('/', $_SERVER['SCRIPT_NAME']);
			array_pop($path);
			$path = implode('/', $path);
			$data = $this->upload->data();
			//绝对地址 网页根
			$filepath =$path . '/' . $config['upload_path'] . $data['file_name'];
			//相对地址
			//$filepath = $config['upload_path'] . $data['upload_data']['file_name'];
			$info['error']   = 0;
			$info['url']     = $filepath;
			$info['message'] = $this->upload->data();
		}

		return $info;
	}

	// H5页面列表
	public function pages_list()
	{
		//查询条件筛选
		$filter = $this->input->post('filter');
		$p = $this->input->post();

		if (isset($p['detail'])) 
		{
			$filter = array('id' => $this->session->userdata('ing_activity_id'));
		}

		$current_page = $this->input->get_post('current_page');
		$items_per_page = $this->input->get_post('items_per_page');

		$current_page = intval($current_page) > 0 ? intval($current_page) : 1;
		$items_per_page = intval($items_per_page) > 0 ? intval($items_per_page) : 12;

		$page_sets = array (
			'current_page' => $current_page, 
			'items_per_page' => $items_per_page
		);
		$pages = $this->h5page_model->get_all_h5pages($filter, $page_sets);

		//整理数据
		// $tmp = array();
		if ($pages['count'] > 0) {
			$this->load->model('h5page/surl');
			foreach($pages['data'] as $k => &$v) 
			{
				//添加个授权链接
				// $url = base_url().'index.php/h5page/wxh5_ext/go?id='.$v['id'].'-'.$this->ses['wx_aid'].'-0';
				// $this->load->library('Wxapi');
				// $v['codeLink'] = $this->wxapi->return_url($this->ses['wx_aid'],$url);
				// $v['weiboAuthLink'] = $this->_get_weibo_auth_url($url);

				//格式化创建时间
				if (isset($v['createtime'])) 
				{
					$v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
				}
				
				//解码json
				if (isset($v['html_code'])) 
				{
					$v['html_code'] = json_decode($v['html_code'],true);
				}

				//生成活动链接地址
				$v['url'] = base_url() . 'index.php/h5page/wxh5_ext/go?id=' . $v['id'] . '&preview=true';
				$surl = $this->surl->create_url($v['url']);
				$v['surl'] = base_url() . 'index.php/s?i=' . $surl['id'];
			}
		}

		$data = array (
			'data' => $pages['data'],
			'current_page' => $current_page,
			'items_per_page' => $items_per_page,
			'total_number' => $pages['count']
		);

		if ($pages['count'] > 0) 
			$this->meret($data);
		else 
			$this->meret(NULL, MERET_EMPTY, '没有创建H5页面！');

		return;
	}

	// 删除页面，把is_delete置为1 
	public function delete ()
	{
		$id = intval($_GET['id']);
		
		$this->db->where(array('id'=>$id, 'company_id'=>$this->cid))
			->delete('h5_page');

		// 删除关联标签
		if ($this->db->affected_rows()) {
			$this->db->where(array('h5_page_id'=>$id))
				->delete('rl_h5_page_tag');
			$this->meret(TRUE);
		} else {
			$this->meret(NULL, MERET_SVRERROR, '没有找到该记录，请刷新重试！');
		}
	}

	

	//营销管理 列出用户/访问跟踪
	public function listUser()
	{
		//$activity = $this->input->post('activity');
		$p = $this->db->escape($this->input->post());
		$aid = $this->session->userdata('ing_activity_id');
		$this->db->select('info')->from($this->tb['participants'])->where(array('uid'=>$p['uid'],'activity_id' => $aid))->limit(1);
		$query = $this->db->get()->result_array();
		//判断是否有数据
		if (!empty($query)) {

			//整理json数据
			foreach($query as $k => $v) {
				
				//匹配手机号
				if (!empty($query[$k]['info']) && preg_match('/^{/', $query[$k]['info'])) {
				$query[$k]['info'] = json_decode($v['info'], true);
					foreach ($query[$k]['info'] as $x => $y) {
						if (preg_match('#^[0]?1[\w]{10}$#', $y)) {
							$query[$k]['info'][$x] = '1**-***-*****';
						}
					}
				}
			}
			$this->db->select('*')->from($this->tb['activity']);
			$this->db->join($this->tb['template'], $this->tb['template'] . '.tplname=' . $this->tb['activity'] . '.template');
			$this->db->where(array($this->tb['activity'] . '.id' =>$aid ));
			$info = $this->db->get()->result_array();
			$html = array('userinfo' =>$query[0], 'html' => json_decode($info[0]['htmlss'], true), 'tpl' => $info[0]['template'], 'array'=>$info[0]['array']);
			return $html;
		} else {
			return array('error' => 1);
		}
	}

	public function getDetails() 
	{
		$p = $this->input->post('activity');
		$query = $this->db->query("SELECT * 
									FROM {$this->tb['prefix']}{$this->tb['activity']} ac
									WHERE ac.id={$p}
									AND ac.c_id='{$this->c_id}'
									AND ac.cid='{$this->cid}'
									")->result_array();
		return $query[0];
	}

	private function sqlWhere($where) 
	{
		//查询条件筛选
		//$filter = $this->input->post('filter');
		$sql = array(
					'lt'=>'<',
					'gt'=>'>',
					'eq'=>'=',
					'not'=>'!=',
				);
		$condition =  array();
		if (!empty($where) && is_array($where)) {
			foreach ($where as $k => $v) {
				if ($v == false) {
					continue;
				}
				$k = addslashes($k);
				if (is_array($v)) {
					foreach ($v as $x => $y) {
						$y = preg_match('/time/', $k) ? (string)(strtotime(addslashes( (string) $y))  + 24 * 3600 ) :( addslashes( (string) $y));
						
						if ($k == 'time' && $y == false) {
							continue;
						}
						
						//数字识别
						$yy = (string)(int) $y;
						if (!($y === $yy)) {
							$y = "'" . $y . "'";
						}

						if (!empty($y)) {
							$condition[] = $y === '' ? ' ' : "  {$k}{$sql[$x]}{$y} ";
						}
					}
				} else {
					$v = addslashes((string) $v);
						$vv = (string)(int) $v;
						if (!($v === $vv)) {
							$v = "'" . $v . "'";
						}
					$condition[] = $v === '' ? ' ' : "  {$k}={$v} ";
				}
			} // End foreach
			$conditionS = empty($condition) ? ' ' : ' WHERE ' . implode(' AND ', $condition);
		} // End if

		return $conditionS;
	}

	private function getNumDistrict()
	{
		$p  = $this->input->post('filter');
		if (!isset($p['time']) or ($p['time']['lt'] == '结束日期' and $p['time']['gt'] == '开始日期')) {
			$p['time']['lt'] = date('Y-m-d', time() + 24 * 3600);
			$p['time']['gt'] = date('Y-m',time() - 30 * 24 * 3600);
		} 
		$id = $this->session->userdata('ing_activity_id');
		if(empty($id)) {
			die('no id');
		}
		//$p['pa.activity']['eq'] = addslashes($id);
		
		$extra = $this->input->post('extra')  ;
		if ($extra == 'input') {
			$p['pa.info']['not'] = 'noooo';
		} elseif ($extra =='ad' ) {
			$p['pa.readurl']['eq'] = 1;
		}

		
		$where = $this->sqlWhere($p);
		$rst = $this->db->query("
							SELECT 
								pa.city c , 
								pa.province p , 
								COUNT(pa.uid ) cnt, 
								COUNT( if( pa.sex='m' and pa.readurl=1 , pa.sex , null)) readurl_male ,
								COUNT( if( pa.sex='f' and pa.readurl=1 , pa.sex , null)) readurl_female,
								COUNT( if( pa.sex='m' and pa.info!='noooo' , pa.sex , null)) input_male ,
								COUNT( if( pa.sex='f' and pa.info!='noooo' , pa.sex , null)) input_female,
								COUNT( if( pa.sex='m' , pa.sex , null)) all_male ,
								COUNT( if( pa.sex='f' , pa.sex , null)) all_female
							FROM {$this->tb['prefix']}{$this->tb['participants']} pa
							{$where}
							AND pa.activity_id={$this->session->userdata('ing_activity_id')}
							AND pa.c_id = {$this->c_id}
							AND pa.cid = {$this->cid}
							GROUP BY c
							ORDER BY cnt DESC
							LIMIT 10
							")->result_array();
		//echo $this->db->last_query();
		if (empty($rst)) {
			die('nodata');
		}
		$return   = array();
		$district = array();
		foreach ($rst as $k => $v) {
			$return[0][] = $this->city[$v['p']][$v['c']];
			//$return[0][] = $v['p'];
			$return[1][] = (int)$v['all_male'];
			$return[2][] = (int)$v['all_female'];
			//$return[3][] = (int)$v['all_nomale'];
			$return[4][] = (int)$v['input_male'];
			$return[5][] = (int)$v['input_female'];
			//$return[6][] = (int)$v['input_nomale'];
			$return[7][] = (int)$v['readurl_male'];
			$return[8][] = (int)$v['readurl_female'];
			//$return[9][] = (int)$v['readurl_nomale'];
		}
	   // var_dump($rst);
		return $return;
		//return $this->db->last_query();
	}
	
	

	// 获取数据统计（访问量，参与量等）
	private function getNumAccess()
	{

		$p  = $this->input->post('filter');
		$format = array(
						'hour' => array(
									'sql' =>'%y-%m-%d %H:00:00', 
									'php' => 'y-m-d H:00:00',
									'sec' => 3600 ,
								),
						'day'  => array(
									'sql' =>'%y-%m-%d', 
									'php' => 'y-m-d',
									'sec' => 3600 * 24 ,
								),
		);
		if (!isset($p['time']) or ($p['time']['lt'] == '结束日期' and $p['time']['gt'] == '开始日期')) {
			$p['time']['lt'] = date('Y-m-d', time() + 24 * 3600);
			$p['time']['gt'] = date('Y-m',time() - 30 * 24 * 3600);
		} 
		$fmter = strtotime($p['time']['lt']) - strtotime($p['time']['gt']);
		if ($fmter >= 3 * 24 * 3600) {
			$formater = 'day';
		} else {
			$formater = 'hour';
		}
		$id = $this->session->userdata('ing_activity_id');
		if(empty($id)) {
			die('no id');
		}
		$p['pa.activity_id']['eq'] = $id;
		$where = $this->sqlWhere($p);
		$rst = $this->db->query("
							SELECT 
								info , 
								`time` t , 
								COUNT(*) cnt,
								count(if(pa.info!='noooo',pa.info,null)) input ,
								count(if(pa.readurl=1,pa.readurl,null)) readurl,
								DATE_FORMAT(FROM_UNIXTIME(`time`),'{$format[$formater]['sql']}') tf
							FROM {$this->tb['prefix']}{$this->tb['participants']} pa
							{$where}
							GROUP BY tf
							ORDER BY t
							")->result_array();
	   // echo $this->db->last_query();
	   // die();
		$time2   = isset($p['time']['lt']) ? strtotime($p['time']['lt']) : time();
		$time1   = isset($p['time']['gt']) ? strtotime($p['time']['gt']) : strtotime(date('Y-m',time()));
		
		$time1   = $time1 == false ? strtotime(date('Y-m',time())) : $time1;
		$time2   = $time2 == false ? time() : $time2;
		
		$timeRst = array();
		$timeFmt = array();
		for ( $time1 ;$time1 <$time2; $time1 += $format[$formater]['sec']) {
			$timeRst[]       = array($time1, 0);
			$timeFmt[$time1] = date($format[$formater]['php'], $time1);
		}

		$t1 = array();
		$t2 = array();
		$t3 = array();
		foreach ($rst as $k => $v) {
			$t1[$v['tf']] =  (int)$v['cnt'];
			$t2[$v['tf']] =  (int)$v['input'];
			$t3[$v['tf']] =  (int)$v['readurl'];
		}
		$return = array();
		foreach ($timeFmt as $fmtK => $fmtV) {
			$fmtK1k = $fmtK * 1000;
			$tt1 = isset( $t1[$fmtV] ) ? $t1[$fmtV] : 0 ;
			$tt2 = isset( $t2[$fmtV] ) ? $t2[$fmtV] : 0 ;
			$tt3 = isset( $t3[$fmtV] ) ? $t3[$fmtV] : 0 ;
			$return[0][] = array($fmtK1k, $tt1);
			$return[1][] = array($fmtK1k, $tt2);
			$return[2][] = array($fmtK1k, $tt3);
		}
		return $return;
	}

	public function getAnalysis() 
	{
		$type = $this->input->post('type');
		if (empty($type)) {
			die('notype');
		}
		return $this->$type();
	}
	public function loadActivity()
	{
		$idx = $this->input->post('idx');
		$session = array('ing_activity_id' => $idx);
		$this->session->set_userdata($session);
		return 2;
	}  

	/**
	 * 条件筛选查询
	 * 
	 * @param  array  $filter 过滤条件
	 * 
	 * @return array         语句数组
	 */
	public function sqlFilter($filter = array())
	{
		//条件筛选
		$where = array();
		$filter = empty($filter) ? $this->input->post('filter') : $filter;
		if (!empty($filter) && is_array($filter)) {

			foreach ($filter as $k => $v) {
				if (empty($v)) continue;
				switch ($k) {
					case 'like':
						$this->db->like('display_name',$v);
						break;
					case 'mass':
						if (is_array($v)) {
							$where[$v['key'] . ' ' . $v['cal']] = $v['val'];
						}
						break;

					default:
						$where[$k] = $v;
						break;
				} // End switch
			} // End foreach
		} // End if
		return $where;
	}
	/**
	 * 当前活动的参与用户信息
	 * 
	 * @return void
	 */
	public function listActivityUser()
	{
		//拼装where条件语句


		//过滤条件
		$this->db->where($this->sqlFilter());
	   
		$user = $this->db->get($this->tb['participants'])->result_array();

		//载入省市代码配置文件
		$province = $this->config->item('province');
		$city = $this->config->item('city');
		foreach ($user as $k => $v) {
			//若是openid的,则不用转换
			if (!empty($v['openid']))  continue ;
			//省市代码转换为文字
			$user[$k]['city']     = $city[ $user[$k]['province'] ][ $user[$k]['city'] ];
			$user[$k]['province'] = $province[  $user[$k]['province']  ];
		}
		return $user;
	}

  
	/**
	 * 取得模板列表
	 * @param int $type 模板类型值 可选，优先读取post[type]
	 * @return array
	 */
	public function getTemplateList($type = false) 
	{
		$p = $this->input->post();
		if (isset($p['type'])) {
			$type = $p['type'];
		} 
		if ($type == 'all') {
			return false;
		}
		$where = array('type' => $type);

		//查询全部模板
		if ($type = 'all') {
			$where = array();
		}
		$list = $this->db->get_where($this->tb['template'], $where)->result_array();
		return $list;
	}

	/* 获取微信授权链接 */
	private function _get_weixin_auth_url ($url) 
	{
		// 
	}

	/* 生成微博OAuth2.0授权链接 */
	private function _get_weibo_auth_url ($url) 
	{
		$this->load->helper('api');
		$this->load->model('system/account_model', 'account');
		$_oa_info = $this->account->get_oa_info($this->session->userdata('wb_aid'));
		$wbApiObj = get_wb_api($_oa_info);

		$url = 'http://mei.masengine.com/redirect.php?redirect=' . urlencode($url);
		$url = $wbApiObj->get_oauth_url($url);
		return isset($url['me_err_msg']) ? $url['me_err_msg'] : $url;
	}
}
