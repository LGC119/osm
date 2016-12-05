<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Group_model extends ME_Model{


    public function __construct(){
        parent::__construct();
        $this ->load ->model('mex/media_model','media');
        $this ->load ->library('Wxapi');

    }

    // 组信息插入数据库
    public function insert_group($data){
        $this ->db ->insert('wx_group',$data);
        return $this->db->insert_id();
    }

    // 组名称的修改
    public function edit_group($wx_group_id,$name,$wx_aid){
        $sql = "UPDATE ".$this ->db ->dbprefix('wx_group')." SET name='$name'
                WHERE id='$wx_group_id'
                AND wx_aid='$wx_aid'";
        $status = $this ->db ->query($sql);
        return $status;
    }

    // 从数据库获取组信息
    public function select_groups($wx_aid,$search)
    {
        $data = array();
        $where = "WHERE wx_aid = {$wx_aid}";
        if($search['keyword'])
        {
            $keyword = $search['keyword'];
            $where .= " AND LOCATE('$keyword',name)";
        }

        $now = date('Y-m-d');
        if ($search['status'] == 1) // 可用组（为过期的和永久组）
        {
            $where .= " AND (expires_in > '{$now}' OR expires_in = '0000-00-00')";
        }
        else if ($search['status'] == 2)
        {
            $where .= " AND expires_in <= '{$now}'";
        }
        $arrange = $search['arrange'];
        if(!$arrange){
            $arrange = 'created_at DESC';
        }

        $data['total_number'] = $this->_all_count($where);
        $limit = $this->_set_limit($data['total_number']);

        $sql = "SELECT g.id,name,feature,description, count(gu.wx_user_id) members_count,expires_in AS expires_date, UNIX_TIMESTAMP(expires_in) * 1000 expires_in
                FROM {$this ->db ->dbprefix('wx_group')} g
                LEFT JOIN {$this ->db ->dbprefix('rl_wx_group_user')} gu 
                ON gu.wx_group_id = g.id 
                {$where} 
                GROUP BY g.id 
                ORDER BY {$arrange} 
                {$limit}";
        $data['groups'] = $this ->db ->query($sql)->result_array();
        $this->current_page = $this->input->get('current_page');
        $data['current_page'] = $this->current_page ? intval($this->current_page) : 1;
        $this->items_per_page = $this->input->get('items_per_page');
        $data['items_per_page'] = $this->items_per_page ? intval($this->items_per_page) : 12;
        return $data;
    }

    //按id读取group
    public function select_group_by_id($id){
        $data = $this->db->get_where($this ->db ->dbprefix('wx_group'),array('id'=>$id))->result_array();
        return $data;
    }
    // 获取总数
    protected function _all_count($where) 
    {
        $sql = "SELECT count(id) total
                FROM {$this ->db ->dbprefix('wx_group')} 
                {$where}";
        $rst = $this->db->query($sql)->row_array();
        return intval($rst['total']);
    }

    /* 分页设定 [默认每页显示20条] */
    private function _set_limit($sum) 
    {
        $page = $this->input->get('current_page');
        $perpage = $this->input->get('items_per_page');

        $page = intval($page) > 0 ? intval($page) : 1;
        $perpage = intval($perpage) > 0 ? intval($perpage) : 12;
        if ($page <= ceil($sum / $perpage))
        {
            $offset = ($page - 1) * $perpage;
            $limit = "LIMIT {$offset}, {$perpage}";
        }
        else 
        {
            $limit = "LIMIT {$perpage}";
        }
        return $limit;
    }

    // 返回粉丝组ID对应名称
    public function groupid_to_name(){
        $sql = 'SELECT id,name FROM '.$this ->db ->dbprefix('wx_group');
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        foreach($data as $v){
            $newData[$v['id']] = $v['name'];
        }
        return $newData;
    }

    // 根据组状态获取不含分页的组
    // WARNING---这个函数会选出所有 账号 创建的组！！！
    // public function get_all_groups($status)
    // {
    //     $now = date('Y-m-d');
    //     if ($status == 1)
    //     {
    //         // 只查可用组
    //         $this->db->where('expires_in >', $now);
    //     }
    //     $rst = $this->db->get('wx_group')->result_array();
    //     return $rst;
    // }
}