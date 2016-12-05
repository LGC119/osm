<?php
/**
 * H5page 分类标签管理
 *
 * PHP version 5
 *
 * @category  Mex
 * @package   H5
 * @author    Xujian <jian.xu@masengine.com>
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @link      http://www.masengine.com
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * H5page 分类标签管理
 *
 * @category  Mex
 * @package   H5
 * @author    Xujian <jian.xu@masengine.com>
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class H5cate extends CI_Model
{
    /**
     * 初始化 c_id  wx_id
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->cid   = $this->session->userdata('company_id');
        $this->wx_id = $this->session->userdata('wx_id');

        //表名称转换
        $this->tb = array(
                'prefix'        => $this->db->dbprefix, //数据库表前缀
                'activity'      => 'wx_h5_activity', //活动表
                'participants'  => 'wx_h5_participants',
                'cate'          => 'wx_cate',
                'activity_cate' => 'wx_activity_cate',
                'template'      => 'wx_h5_template',
                'template_type' => 'wx_h5_template_type',
                'ads'           => 'wx_h5_ads',
                'b_id'          =>'company_id', 
                'c_id'          => 'wx_id',
                'category'         => 'category',
            );
    }

    /**
     * 获取分类列表
     * 
     * @return array normal
     */
    public function getcatelist()
    {

        $where = array(
            'company_id' =>$this->cid,
            'wx_id'      =>$this->wx_id,
        );
        // 筛选大类
        $get = $this->input->get(null, true);
        if (isset($get['category'])) {
            $where['category'] = $get['category'];
        }
        $query = $this->db
            ->select("{$this->tb['cate']}.*, count({$this->tb['prefix']}{$this->tb['activity_cate']}.activity) count", false)
            ->from($this->tb['cate'])
            ->join($this->tb['activity_cate'], "{$this->tb['cate']}.id = {$this->tb['activity_cate']}.cate", 'LEFT')
            ->where($where)
            ->group_by($this->tb['cate'] . '.id')
            ->order_by('path', 'asc')
            ->get();
        $rst = $query->result_array();
        if (empty($rst)) {
            return array('code' => 200, 'data' => array(), 'pcates_count' => count(array()));
        }
        $cate = array();
        foreach ($rst as $val) {
            if ($val['pid'] == 0) {
                $cate[ $val['id'] ]              = array();
                $cate[ $val['id'] ]['name']      = $val['name'];
                $cate[ $val['id'] ]['code']      = $val['code'];
                $cate[ $val['id'] ]['is_preset'] = $val['is_preset'];
                $cate[ $val['id'] ]['content']   = array();
            } else {
                $cate[ $val['pid'] ]['content'][ $val['id'] ]['name']      = $val['name'];
                $cate[ $val['pid'] ]['content'][ $val['id'] ]['is_preset'] = $val['is_preset'];
                $cate[ $val['pid'] ]['content'][ $val['id'] ]['count']     = $val['count'];
            }
        }
        return array('code' => 200, 'data' => $cate, 'pcates_count' => count($cate));
    }

    /**
     * 删除分类和标签
     * 
     * @return array normal
     */
    public function del_cate()
    {

        $get = $this->input->get(null, true);
        $this->db->where('id', $get['id']);
        $this->db->or_where('pid', $get['id']);
        $this->db->where($this->tb['b_id'], $this->cid);
        $this->db->delete($this->tb['cate']);
        if ($this->db->affected_rows() > 0) {
            return array(200, '删除成功');
        } else {
            return array(400, '删除失败');
        }
    }

    /**
     * 添加分类
     * 
     * @return array normal
     */
    public function new_cate()
    {
        $post         = $this->input->post(null, true);
        $post['name'] = trim($post['name']);

        if (!$post['name']) {
            return array('code' => 600, 'msg' => '输入为空！');
        }

        $post['path']       = ($post['pid'] == 0) ? '0' : '0-' . $post['pid'];
        $post[$this->tb['b_id']] = $this->cid;
        $post[$this->tb['c_id']]      = $this->wx_id;
        $post[$this->tb['category']]      = 1;

        // 查询同账号下是否有相同的分类名
        $where = array('wx_id' => $post[$this->tb['c_id']], 'name' => $post['name']);
        $rst = $this->db->select('name')
            ->get_where($this->tb['cate'], $where)
            ->result_array();
        if (count($rst)) {
            return array('code' => 600, 'msg' => '标签名重复！');
        }

        $this->db->insert($this->tb['cate'], $post);
        $insert_id = $this->db->insert_id();
        if ( $insert_id > 0 ) {
            return array('code' => 200, 'msg' => '添加成功', 'id' => $insert_id);
        } else {
            return array('code' => 400, 'msg' => '添加失败');
        }
    }
}