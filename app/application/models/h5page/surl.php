<?php
/**
 * 短网址处理
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

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * 短网址处理
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.6
 * @link      http://www.masengine.com
 */
class Surl extends CI_Model
{
    /**
     * 初始化
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->g = is_array($this->input->get()) ? $this->input->get() : array();
        $this->p = is_array($this->input->post()) ? $this->input->post() : array();
        
        //表名称转换
        $this->tb = $this->config->item('H5page_table');

    }

    /**
     * 短链访问动作
     * 
     * @return void [description]
     */
    public function access() 
    {
        $input = array_merge($this->g, $this->p); 
        
        if (isset( $input['i']) ) {
            $where['id'] = $input['i'];
            unset($input['i']);
        }
        if (isset( $input['s']) ) {
            $where['surl'] = $input['s'];
            unset($input['s']);
        }
        if (isset( $input['h']) ) {
            $where['hashurl'] = $input['h'];
            unset($input['h']);
        } 

        $query = http_build_query($input);

        $url = $this->db->get_where($this->tb['surl'], $where)->row_array();
        return array('url'=>$url, 'query'=>$query);
        
    }

    /**
     * 创建一条短连接
     * 
     * @param string $url 网址
     * 
     * @return array   生成的短链数组
     */
    public function create_url($url)
    {
        $s = $this->_url_short($url);
        $h = md5($url);
        $insert = array(
                    'surl'    => $s,
                    'hashurl' => $h,
                    'original_url' => $url,
                    );

        //若已存在，则查出来
        $rst = $this->db
            ->get_where($this->tb['surl'], array('surl' => $s))
            ->result_array();

        if ($rst) {
            $return =  $rst[0];
            $return['new'] = false;
        } else {
            //插入数据库
            $rst = $this->db->insert($this->tb['surl'], $insert);
            $return = array(
                        'id' => $this->db->insert_id(),
                        'surl' => $s,
                        'hashurl' => $h,
                        'original_url' => $url,
                ); 
            $return['new'] = true;
        }

        return $return;
    }
    
    /**
     * 短网址字符生成器
     * 
     * @param string $url 网址
     * 
     * @return string 缩略字符
     */
    private function _url_short($url)
    {
        $url= crc32($url);
        $result= sprintf("%u", $url);
        $sUrl= '';
        while ($result>0) {
            $s= $result%62;
            if ($s>35) {
                $s= chr($s+61);
            } elseif ($s>9 && $s<=35) {
                $s= chr($s+ 55);
            }
            $sUrl.= $s;
            $result= floor($result/62);
        }
        return $sUrl;
    }

}
