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
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class H5page_Model extends CI_Model
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

		//上传文件路径
		$this->uploadPath = '../uploads/h5page/';
		$this->load->model('h5page/linkh5_model');
		$this->cid =  isset($this->ses['company_id']) ? 
		   $this->ses['company_id'] : 0 ;
		
		//表名称转换
		$this->tb = $this->config->item('H5page_table');
	}

	//终端页面样例
	public function mobile($tpl) 
	{
		$mef_item = array();
		$mef_item['url'] = base_url();
		$this->load->view(H5PAGE_TPL_PATH . 'tpl_' . $tpl . '.html',$mef_item); //输出网页头
		$this->load->view(H5PAGE_TPL_PATH . 'tpl_footer.html'); 
	}

	// 根据id获取页面信息
	public function get_h5page_info($id)
	{
		$this->db->where('id', $id);
		$page_info = $this->db->get($this->tb['h5page'])->row_array();
		return $page_info;
	}

	/**
	 * 用户访问生成的活动页面时，传输模板内容（user）
	 * 
	 * @return void
	 */
	public function gethtml()
	{
		$id = $this->session->userdata($this->tb['h5page']);
		
		$this->db->where('id', $id);
		$rst = $this->db->get($this->tb['h5page'])->row_array();
		return $rst['html_code'];
	}

	/**
	 * 用户提交信息
	 * 
	 * @return void [description]
	 */
	public function submiter()
	{
		$p      = $this->input->post(null, true);
		$id     = $this->session->userdata($this->tb['activity']);
		$logid  = $this->session->userdata('db_log_id');
		$this->db->where('id', $logid);
		$update = array(
			'time'     => time(),
			'info'     => json_encode($p),
			'activity_id' => $id,
			//'uid'      => $this->session->userdata('info'),
		);
		if ($this->db->update($this->tb['participants'], $update)) {
			echo 'success';
		} else {
			echo 'no';
		}
	}

	/**
	 * H5页面信息入库
	 * 
	 * @param  array $p post
	 * 
	 * @return string Result    
	 */
	public function insert_h5page($p)
	{
		$html = isset($p['html']) ? $p['html'] : '';
		if ($html) $html[1] = preg_replace('#h5_tpl\.png#', 'h5_empty.png', $html[1]);
		$insert = array(
			'created_at' 	=> date('Y-m-d:H-i-s'),
			'title' 		=> trim($p['title']),
			'html_code' 	=> json_encode($html,JSON_UNESCAPED_UNICODE),
			'template' 		=> trim($p['template']),
			'company_id' 	=> $this->cid ,
		);

		if ($p['template'] == 'custom') {
			$insert['html_code'] = $p['custom_url'];
			$insert['title'] = '自定义模版';
		}

		$this->db->insert($this->tb['h5page'], $insert);
		$page_id = $this->db->insert_id();

		return $page_id ? $page_id : FALSE;
	}

	// 插入H5页面与标签关联
	public function rl_h5page_tag ($page_id, $tags = array()) 
	{
		if (!empty($tags)) 
		{
			$insert_batch = array();
			foreach ($tags as $tag_id) {
				$tag_id = (int) $tag_id;
				$tag_id > 0 && $insert_batch[] = array('h5_page_id' => $page_id, 'tag_id' => $tag_id);
			}

			$this->db->insert_batch($this->tb['page_tag'], $insert_batch);

			return $this->db->affected_rows() ? TRUE : FASLE;
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
	 *                [msg]    文件信息
	 *                [path]   成功后的文件路径
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
		} else {
			$path = explode('/', $_SERVER['SCRIPT_NAME']);
			array_pop($path);
			$path = implode('/', $path);
			$data = $this->upload->data();
			//绝对地址 网页根
			$filepath =$path . '/' . $config['upload_path'] . $data['file_name'];
			//相对地址
			$info['error']   = 0;
			$info['url']     = $filepath;
			$info['message'] = $this->upload->data();
		}

		return $info;
	}

	//列出活动
	public function get_all_h5pages($filter, $page_sets = array ())
	{
		// 查询条件定义
		$where = array();

		// 筛选
		if (!empty($filter) && is_array($filter)) 
		{
			foreach ($filter as $k => $v) 
			{
				switch ($k) 
				{
					case 'time':
						$v['lt'] = strtotime(addslashes($v['lt']));
						$condition .= $v['lt'] == false ? '' : " AND ac.createtime<{$v['lt']} ";
						$v['gt'] = strtotime(addslashes($v['gt']));
						$condition .= $v['gt'] == false ? '' : " AND ac.createtime>{$v['gt']} ";
						break;
					case 'search':
						$v = addslashes($v);
						$condition .= $v == false ? '' : " AND ac.search LIKE '%{$v}%' ";
						break;
					case 'template':
						$k = addslashes($k);
						$v = addslashes($v);
						$condition .= $v == false ? '' : " AND ac.{$k}='{$v}' ";
						break;
					case 'status':
						$k = addslashes($k);
						$v = $v == '' ? false : addslashes($v);
						$condition .= $v === false ? '' : " AND ac.{$k}={$v} ";
						break;
					default:
						$k = addslashes($k);
						$v = empty($v) ? false : addslashes($v);
						$condition .= $v === false ? '' : " AND ac.{$k}='{$v}' ";
						break;
				} // End switch
			} // End foreach
		} // End if

		$where['page.company_id'] = $this->cid;
		$where['page.is_deleted'] = 0;

		$total_number = $this->db->select('page.id')
			->from("{$this->tb['h5page']} as page")
			->join("{$this->tb['participants']} as parti", 'page.id=parti.page_id', 'left')
			->join('rl_h5_page_tag ht', 'ht.h5_page_id = page.id', 'left')
			->join('tag t', 'ht.tag_id = t.id', 'left')
			->where($where)
			->group_by('page.id')->get()->num_rows();

		$rst = array ();
		if ($total_number > 0) {
			$this->db->select('page.*, count(parti.id) visitor_num, group_concat(t.tag_name) tags', FALSE)
				->from("{$this->tb['h5page']} as page")
				->join("{$this->tb['participants']} as parti", 'page.id=parti.page_id', 'left')
				->join('rl_h5_page_tag ht', 'ht.h5_page_id = page.id', 'left')
				->join('tag t', 'ht.tag_id = t.id', 'left')
				->where($where)
				->group_by('page.id')
				->order_by('page.id', 'DESC');

			if (isset($page_sets['current_page']) && isset($page_sets['items_per_page'])) {
				$cp = $page_sets['current_page'];
				$ipp = $page_sets['items_per_page'];
				if ($cp > ceil($total_number / $ipp)) {
					$this->db->limit($ipp);
				} else {
					$this->db->limit($ipp, ($cp - 1) * $ipp);
				}
			}
			$rst = $this->db->get()->result_array();
		}

		return array ('data'=>$rst, 'count'=>$total_number);
	}

	//删除发布的活动 
	public function delActivity()
	{
		$id = $this->input->post('delid');
		if ($id) {
			$this->db->where(array(
							   'id' => $id,
							   'cid' => $this->cid ,
							   'c_id' => $this->c_id ,
							 ));
			$this->db->update($this->tb['activity'], array(
				'status' => 9,    //标记status为9 作为删除标志
			));
			$this->logs->log(array(
							   'id' => $id,
							   'cid' => $this->cid ,
							   'c_id' => $this->c_id ,
							 ),'wb_activity_h5_pages_manager','delete',1);
			return array('ok');
		} else {
			echo $id;
		}
	}

	/**
	 * 更改活动状态，1 0
	 *
	 * @return array    ['error'] 0,1
	 *                  ['stat']  更新后的状态
	 **/
	public function switchStatus()
	{
		$idx = $this->input->post('idx');
		$this->db->select('status');
		$rst = $this->db->get_where($this->tb['activity'], array('id' => $idx),1)->result_array();

		$switch = array(0 => 1, 1 => 0);
		$result = $this->db->update($this->tb['activity'],array('status' => $switch[$rst[0]['status']] ), array('id' => $idx));

		if (($result && $rst) == false){
			$this->logs->log(array('status' => $switch[$rst[0]['status']] ),'wb_activity_h5_pages_manager','update',0);
			return array('error' => 1);
			exit();
		}
		$this->logs->log(array('status' => $switch[$rst[0]['status']] ),'wb_activity_h5_pages_manager','update',1);
		return array('error' => 0, 'stat' => $switch[$rst[0]['status']]);
	}

	// 营销管理 列出用户/访问跟踪
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


}