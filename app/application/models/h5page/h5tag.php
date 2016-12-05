<?php
/**
 * H5  特征标签 作为mex的组件整合标签体系
 *
 * PHP version 5
 *
 * @category  Mex
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @link      http://www.masengine.com
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * H5  特征标签
 *
 * @category  Mex
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class H5tag extends CI_Model
{

    /**
     * 初始化
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->g    = is_array($this->input->get()) ? $this->input->get() : array();
        $this->p    = is_array($this->input->post()) ? $this->input->post() : array();
        $this->s    = $this->session->all_userdata();
        $this->b_id =  isset($this->s['company_id']) ? 
           $this->s['company_id'] : 0 ;
        $this->c_id = isset($this->s['wx_id']) ?
            $this->s['wx_id'] : 1;
        //表名称转换
        $this->tb = $this->config->item('tb');

    }

    /**
     * 获取tag列表
     * 
     * @return array tag列表
     */
    public function getTagList()
    {
        $where = array(
                 // $this->tb['c_id'] => $this->c_id,
                $this->tb['b_id'] => $this->b_id,
                 $this->tb['category'] => $this->input->post('filter')
            );
        $this->db->where($where);
        //$this->db->group_by('pid', 'desc');
        $adList = $this->db->get($this->tb['cate'])->result_array();
        //var_dump($adList);
        $tagList = array();
        if ($adList) {
            foreach ($adList as $k => $v) {
                $tagList[$v['pid']][] = $v;
            }
        }
        return $tagList;
    }
}
