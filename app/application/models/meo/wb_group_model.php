<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Wb_group_model extends ME_Model{


    public function __construct(){
        parent::__construct();
        $this ->load ->model('mex/media_model','media');
        $this ->company_id = $this->session->userdata('company_id');

    }

    // 组信息插入数据库
    public function insert_group($data){
        $this ->db ->insert('wb_group',$data);
        return $this->db->insert_id();
    }

    // 组名称的修改
    public function edit_group($wb_group_id,$group_name){
        $sql = "UPDATE ".$this ->db ->dbprefix('wb_group')." SET group_name='$group_name'
                WHERE id='$wb_group_id'";
        $status = $this ->db ->query($sql);
        return $status;
    }

    // 从数据库获取组信息
    public function select_groups($search)
    {
        $data = array();
        $where = "where company_id = '{$this ->company_id}'";
        if($search['keyword'])
        {
            $keyword = $search['keyword'];
            $where .= "AND LOCATE('$keyword',group_name)";
        }

        $now = date('Y-m-d');
        if ($search['status'] == 1)
        {
            $where .= " AND expires_in >= '{$now}'";
        }
        else if ($search['status'] == 2)
        {
            $where .= " AND expires_in < '{$now}'";
        }

        $data['total_number'] = $this->_all_count($where);
        if ($search['all_data'] == 1) {
           $limit ='';
           $where .= " AND expires_in > '{$now}'";
        }else{
            $limit = $this->_set_limit($data['total_number']);
        }
        $arrange = $search['arrange'];

        $sql = "SELECT g.id,group_name,description, count(gu.wb_user_id) members_count,expires_in AS expires_date ,UNIX_TIMESTAMP(expires_in) * 1000 expires_in   
                FROM {$this ->db ->dbprefix('wb_group')} g
                LEFT JOIN {$this ->db ->dbprefix('rl_wb_group_user')} gu 
                ON gu.group_id = g.id 
                {$where} 
                GROUP BY g.id 
                ORDER BY {$arrange}  
                {$limit}";
        // echo $sql;
        $data['groups'] = $this ->db ->query($sql)->result_array();
        $this->current_page = $this->input->get('current_page');
        $data['current_page'] = $this->current_page ? intval($this->current_page) : 1;
        $this->items_per_page = $this->input->get('items_per_page');
        $data['items_per_page'] = $this->items_per_page ? intval($this->items_per_page) : 12;
        return $data;
    }

    //按id读取group
    public function select_group_by_id($id){
        $data = $this->db->get_where($this ->db ->dbprefix('wb_group'),array('id'=>$id))->result_array();
        return $data;
    }
    // 获取总数
    protected function _all_count($where) 
    {
        $sql = "SELECT count(id) total
                FROM {$this ->db ->dbprefix('wb_group')} 
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
        $sql = 'SELECT id,group_name FROM '.$this ->db ->dbprefix('wb_group');
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        foreach($data as $v){
            $newData[$v['id']] = $v['group_name'];
        }
        return $newData;
    }

    /**
     * 获取组用户ID，NAME信息，创建活动时使用
     *
     * @return [
     *  {id:001,name:xxxx}, 
     *  {id:002,name:...},
     *  ...
     * ]
    **/
    public function get_group_user_ids ($group_id) 
    {
        $group_info = $this->db->select('is_locked, filter_param')
            ->from('wb_group')
            ->where(array('id'=>$group_id, 'company_id'=>$this->session->userdata('company_id')))
            ->get()->row_array();

        if ( ! $group_info) 
            return array();

        $user_ids = $this->_get_user_ids_by_group ($group_id);
        if ( ! $user_ids && $group_info['filter_param'] != '') 
        {
            $this->load->model('meo/wb_user_model', 'user_model');
            return $this->user_model->get_user_ids_by_filter($group_info['filter_param']);
        }

        return $user_ids;
    }

    /* 获取锁定组的用户ID，NAME信息 */
    private function _get_user_ids_by_group ($group_id) 
    {
        $user_ids = $this->db->select('rwgu.wb_user_id AS id, wu.screen_name AS name')
            ->from('rl_wb_group_user rwgu')
            ->join('wb_user wu', 'wu.id=rwgu.wb_user_id')
            ->where(array('group_id'=>$group_id))
            ->get()->result_array();

        return $user_ids;
    }

    // 根据组状态获取不含分页的组
    public function get_all_groups($status)
    {
        $now = date('Y-m-d');
        if ($status == 1)
        {
            // 只查可用组
            $this->db->where('expires_in >', $now);
        }
        $rst = $this->db->get('wb_group')->result_array();
        return $rst;
    }
}