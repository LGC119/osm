<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 待分类的舆情
*/
class Twodcode_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->twodcode_table = $this->db->dbprefix('wx_2dcode');
		$this->rl_wx_user_2dcode_table = $this->db->dbprefix('rl_wx_user_2dcode');
	}

	/* 获取现有分类数据 */
	public function get_twodcode_data ($current_page ,$items_per_page,$title,$category) 
	{
		//分页limit
		$limit_begin = ($current_page - 1) * $items_per_page;
		$this->db->order_by('id','desc');
    	if ($title) {
    		$this->db->like('title',$title);
    	}
    	if ($category) {
    		$this->db->where('category',$category);
    	}
		//获取wx_2dcode表中数据
		$rst['data'] = $this->db->get($this->twodcode_table,$items_per_page,$limit_begin)->result_array();
		//统计总条数
		if ($title) {
			if ($category) {
				$count_code = $this->db->like('title',$title)->where('category',$category)->from($this->twodcode_table)->count_all_results();
			}else{
				$count_code = $this->db->like('title',$title)->from($this->twodcode_table)->count_all_results();
			}
		}else{
			if ($category) {
				$count_code = $this->db->where('category',$category)->from($this->twodcode_table)->count_all_results();
			}else{
				$count_code =  $this->db->count_all_results($this->twodcode_table);
			}
		}
		$rst['count_code'] = $count_code;
		$rst['current_page'] = $current_page;
		$rst['items_per_page'] = $items_per_page;
		return $rst;
	}
	//创建二维码
	public function create($data)
	{
		$result = $this->db->insert($this->twodcode_table, $data);
		$id = $this->db->insert_id();
		if ($result > 0)
			return array_merge($data, array('id'=>$id));
		else 
			return '服务器忙，请稍后尝试！';
	}
	//删除二维码
	public function delete($id)
	{
		$this->db->delete($this->twodcode_table, array('id' => $id));
		if ($this->db->affected_rows())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	// 获取access_token
    public function get_token($wx_aid){
        $sql = "SELECT access_token
        		FROM ".$this ->db ->dbprefix('wx_account')."
                WHERE id='$wx_aid'";
        $data = $this ->db ->query($sql)->row_array();
        return $data['access_token'];
    }

    //按code_id获取指定二维码数据
    public function get_code_by_id($code_id){
    	$data = $this->db->get_where($this->twodcode_table,array('id'=>$code_id))->result_array();
    	$data[0]['user_sum'] = $this->get_user_sum($code_id);
    	return $data;
    }
    //二维码数据过滤
    public function get_list_filter($title,$category){
    	$this->db->like('title',$title);
		$this->db->order_by('id','desc');
    	if ($category) {
    		$this->db->where('category',$category);
    	}
    	$rst['data'] = $this->db->get($this->twodcode_table)->result_array();
    	return $rst;
    }
    //按code_id获取微信用户数据
    public function get_user_list($code_id,$years_num){
    	$years_num = intval($years_num);
    	$data = array();
    	if ($code_id) {
	    	$data['data'] = $this->db->query("SELECT rl.type,rl.created_at,user.nickname,user.province,user.city,user.sex,user.headimgurl,user.subscribe_time 
	    					FROM {$this->db->dbprefix('wx_user')} user 
	    					LEFT JOIN {$this->rl_wx_user_2dcode_table} rl 
	    					ON user.openid = rl.openid
	    					WHERE rl.wx_2dcode_id = {$code_id} 
 							ORDER BY user.id DESC")->result_array();
	    	$data['chart_created'] = array();
	    	// $data['chart_created'][0] = array();
	    	for ($i=1; $i < 13; $i++) { 
	    		if ($i < 10) {
	    			$i = '0'.$i;
	    		}
	    		$this_month = $years_num.'-'.$i.'-01 00:00:00';
	    		$next_month = $years_num.'-0'.($i+1).'-01 00:00:00';
	    		if ($i == 12) {
	    			$next_month = ($years_num+1).'-01-01 00:00:00';
	    		}
	    		$sql = "SELECT count(rl.id) 
	    				FROM {$this->rl_wx_user_2dcode_table} rl 
	    				LEFT JOIN  {$this->db->dbprefix('wx_user')} user 
	    				ON user.openid = rl.openid
	    				WHERE rl.created_at between '{$this_month}' AND '{$next_month}' AND rl.wx_2dcode_id = {$code_id}";
	    		$chart_created = $this->db->query($sql)->result_array();
	    		if (isset($chart_created[0]['count(rl.id)'])) {
	    			$data['chart_created'][] = intval($chart_created[0]['count(rl.id)']);
	    		}
	    	}
	    	return $data;
    	}else{
    		return false;
    	}
    }
    //获取user_sum	
    public function get_user_sum($wx_2dcode_id){
    	$this->db->where('wx_2dcode_id',$wx_2dcode_id);
    	$this->db->from($this->rl_wx_user_2dcode_table);
    	$data = $this->db->count_all_results();
    	return $data;
    }

    //多维数组转换为一维数组
    private function arrayChange($arr1){ 
	    static $arr2; 
	    foreach($arr1 as $v){ 
	        if(is_array($v)){ 
	            $this->arrayChange($v); 
	        } 
	        else{ 
	            $arr2[]=$v; 
	        } 
	    } 
	    return $arr2; 
	}


    //二维码事件
    public function event_scan($data='',$wx_aid=''){
        $scene_id = $data['scene_id'];
        $sql = "SELECT id FROM ".$this ->db ->dbprefix('wx_2dcode')."
                    WHERE scene_id='$scene_id'";
//        file_put_contents('/home/test/hh1.txt',$sql);
        $code_id = $this ->db ->query($sql) ->result_array();
        $code_id = isset($code_id[0]['id']) ? $code_id[0]['id'] :0;
        $data['wx_2dcode_id'] = $code_id;
        unset($data['scene_id']);
        $this ->db ->insert('rl_wx_user_2dcode',$data);
//        file_put_contents('/home/test/hh2.txt',$this->db->last_query());
        echo $this ->db ->last_query();
    }


}

/* End of file category.php */
/* Location: ./application/controllers/common/category.php */