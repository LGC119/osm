<?php
/**
 * H5page 广告统计分析系统
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
 * H5page 广告统计分析系统
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class H5ads extends CI_Model
{

    /**
     * 初始化
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->g    = is_array($this->input->get()) ? 
                        $this->input->get() : array();
        $this->p    = is_array($this->input->post()) ? 
                        $this->input->post() : array();
        $this->s    = $this->session->all_userdata();
        $this->b_id =  isset($this->s['company_id']) ? 
           $this->s['company_id'] : 0 ;
        $this->c_id = isset($this->s['wx_id']) ?
            $this->s['wx_id'] : 1;
        //表名称转换
        $this->tb = $this->config->item('tb');
    }

    /**
     * 获取广告列表
     * 
     * @return array 广告列表
     */
    public function getAdList()
    {
        $where = array(
                    'b_id' => $this->b_id,
                    'c_id' => $this->c_id,
            );
        $this->db->where($where);
        $this->db->where($this->sqlFilter());
        //$this->db->order_by('time', 'desc');
        $adList = $this->db->order_by('id', 'desc')->get($this->tb['ads'])->result_array();
        return $adList;
    }

 

    /**
     * 发布广告
     * 
     * @return string 返回值
     */
    public function publish()
    {
        $tag = isset($this->p['tag']) ? $this->p['tag'] : null;
        $ad = array();
        parse_str($this->p['ad'], $ad);
        $this->p = $ad;

        if (!isset($this->p['name'])) die('no data');
        $type = $this->p['type'] == 'new' ?  $this->p['newtype'] :  $this->p['type'];
        $url = preg_match('#^http://.*?#', $this->p['url']) ? 
            $this->p['url'] : 'http://' . $this->p['url'];
        $insert = array(
                'type' => $type,
                'time' => time(),
                'name' => $this->p['name'],
                'c_id' => $this->c_id,
                'b_id' => $this->b_id,
                'url'  => $url,
                'img'  => $this->p['img'],
                'desc' => $this->p['desc'],
            );
        $this->db->set($insert);
        if ($this->db->insert($this->tb['ads'])) {
            // For 特征标签
            // 特征标签入库
            $activity_id = $this->db->insert_id();
            if (isset($tag) && !empty($tag)) {
                $tags = array();
                parse_str($tag, $tags);
                $insert_batch = array();
                foreach ($tags as $cate => $v) {
                    $insert_batch[] = array('ad' => $activity_id, 'cate' => $cate);
                }
                $this->db->insert_batch($this->tb['ads_cate'], $insert_batch);
            }
            return 'ok';
        } else {
            return 'error';
        };

    }

    /**
     * 广告类型列表
     * 
     * @return array 广告类型
     */
    public function getAdType()
    {   
        $this->db->select('type');
        $where = array(
                'b_id' => $this->b_id,
                'c_id' => $this->c_id,
                );
        $this->db->where($where);
        $this->db->order_by('time', 'desc');
        $this->db->group_by('type');
        $adType = $this->db->get($this->tb['ads'])->result_array();
        return $adType;
    }

    /**
     * 条件筛选查询
     * 
     * @param array $filter 过滤条件
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
                    if (is_array($v)) {
                        if (empty($v['val'])) continue;
                        $this->db->like($v['key'], $v['val']);
                    }
                    break;
                case 'or_like':
                    if (is_array($v)) {
                        if (empty($v['val'])) continue;
                        $this->db->or_like($v['key'], $v['val']);
                    }
                    break;
                case 'mass':
                    if (is_array($v)) {
                        $where[$v['key'] . ' ' . $v['cal']] = $v['val'];
                    }
                    break;
                case 'order':
                    if (is_array($v)) {
                        if (empty($v['order'])) continue;
                        $this->db->order_by($v['key'], $v['order']);
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
     * 广告统计图表
     * 
     * @return array  统计结果
     */
    public function getAdAnalysis()
    {
        $p  = $this->input->post('filter');
        //var_dump($p); die();
        //设定时间格式
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

        //没设定日期，默认显示前30天的
        if (!isset($p['time']) or empty($p['time']) ) {
            $p['time']['lt'] = date('Y-m-d', time() + 2*24 * 3600);
            $p['time']['gt'] = date('Y-m', time() - 30 * 24 * 3600);
        }

        //时间格式转换 和默认值设定
        if (isset( $p['time']['lt']) ) {
            $p['time']['lt'] = strtotime($p['time']['lt']);
        } else {
            $p['time']['lt'] = strtotime(date('Y-m-d', time()));
        }
        $p['time']['lt'] += 24 * 3600 + 1 ;
        if (isset( $p['time']['gt']) ) {
            $p['time']['gt'] = strtotime($p['time']['gt']);
        } else {
            $p['time']['gt'] = 0 ;
        }

        //判定按天为单位还是小时为单位
        $fmter = $p['time']['lt'] - $p['time']['gt'];
        if ($fmter >= 10 * 24 * 3600) {
            $formater = 'day';
        } else {
            $formater = 'hour';
        }
        $in = is_array($p['id']) ? implode(', ', $p['id']) : $p['id'];
        $sql = "SELECT `time` t , COUNT(*) cnt, ad,
                DATE_FORMAT(FROM_UNIXTIME(`time`),'{$format[$formater]['sql']}') tf
                FROM {$this->tb['prefix']}{$this->tb['ads_logger']} 
                WHERE ad IN ( {$in} ) AND time > ? AND time < ?
                GROUP BY ad, tf ORDER BY t ";
        $conditions = array(
                
                $p['time']['gt'],
                //$p['time']['lt'] + 24 * 3600 + 1,
                $p['time']['lt'],
            );
        $rst = $this->db->query($sql, $conditions)->result_array(); 
        
        $echo = $this->db->last_query();

        //var_dump($echo); die();
        
        //var_dump($rst); die();
        
        foreach ($rst as $ad) {
            $adId[] = $ad['ad'];
            //按照广告id索引的数组
            $adArray[$ad['ad']][] = $ad;
        }

        //var_dump($adId); die();
        
        //查出来广告的名称
        $adList = $this->db
            ->select('id, name')
            ->where_in('id', $adId)
            ->get($this->tb['ads'])
            ->result_array();

        foreach ($adList as $val ) {
            $adNameList[$val['id']] = $val['name'];
        }
        //var_dump($adNameList);die();
        //echo $this->db->last_query();die();
        
        //整理出时间区间的空数组
        $time2   = $p['time']['lt'];
        $time1   = $p['time']['gt'];
        $timeRst = array();
        $timeFmt = array();
        for ( $time1 ;$time1 <$time2; $time1 += $format[$formater]['sec']) {
            $timeRst[]       = array($time1, 0);
            $timeFmt[$time1] = date($format[$formater]['php'], $time1);
        }
        
        //var_dump($adArray); die();
        $return = array();
        foreach ($adArray as $adid => $ad) {
            $t1 = array();
            foreach ($ad as $k => $v) {
                $t1[$v['tf']] =  (int)$v['cnt'];
            }
            $tt1 = array();
            $data = array();
            foreach ($timeFmt as $fmtK => $fmtV) {
                $tt1    = isset( $t1[$fmtV] ) ? $t1[$fmtV] : 0 ;
                $data[] = array( 'x'=> $fmtK * 1000, 'y' => $tt1, 'id' => $adid);
            } 
            
            $return[] = array('name' => $adNameList[$adid], 'data' => $data);
        }

        //var_dump($return); die();
        
        return $return;
    }

    /**
     * 拼装SQL 语句
     * 
     * @param array $where sql条件
     * 
     * @return string        sql语句
     */
    private function _sqlWhere($where)
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
                        $y = preg_match('/time/', $k) ? 
                            (string)(strtotime(addslashes((string) $y))  + 24 * 3600) :(addslashes( (string) $y));
                        
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
                } // End if
            } // End foreach
            $conditionS = empty($condition) ? ' ' : ' WHERE ' . implode(' AND ', $condition);
        } // End if

        return $conditionS;
    }

    /**
     * 当前点击广告的用户信息
     * 
     * @return void
     */
    public function listAdUser()
    {
        $filter = $this->p['filter'];

        //拼装where条件语句
        $where = array(
                'ad' => $filter['id'],
                'time >' => $filter['time'],
                'time <' => $filter['time'] + 24 * 3600,
            );

        //过滤条件
        $this->db->where($where);
        
        $ads_logger = $this->db->get($this->tb['ads_logger'])->result_array();
        if (empty($ads_logger)) {
            return array();
        }
        foreach ($ads_logger as $v) {
            $uidArray[] = $v['participants_id'];
        }


        $user = $this->db->where_in('id', $uidArray)->get($this->tb['participants'])->result_array();
        //载入省市代码配置文件
        $province = $this->config->item('province');
        $city = $this->config->item('city');
        foreach ($user as $k => $v) {
            //省市代码转换为文字
            $user[$k]['city']     = $city[ $user[$k]['province'] ][ $user[$k]['city'] ];
            $user[$k]['province'] = $province[  $user[$k]['province']  ];
        }
        return $user;
    }





}


