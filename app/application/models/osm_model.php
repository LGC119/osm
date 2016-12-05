<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* 欧诗漫定制化功能model
*/
class Osm_model extends ME_Model
{
	
	public function __construct()
	{
        $this->load->library('Wxapi');
		parent::__construct();
	}

	//获取所有订阅标签
	public function get_all_tags_sub ($company_id) 
	{
		$rst = $this->db->select('id, tag_name, pid, created_at')
			->from('tag')
			->where('company_id', $company_id)
			->where('is_preset', 1)
			->order_by('id', 'desc')
			->get()->result_array();
		$tags = array();
		foreach ($rst as $k => $tag)
		{
			if ($tag['pid'] == 0)
			{
				$tag['tags'] = array();
				$tags[] = $tag;

				unset($rst[$k]);
			}

		}

		foreach ($tags as $k => $ptag) 
		{
			foreach ($rst as $tag) 
			{
				if ($tag['pid'] == $ptag['id'])
				{
					$tags[$k]['tags'][] = $tag;
				}	
			}
		}
		return $tags;
	}

	//根据openid获取tag_id
	public function get_tags_sub_by_openid ($company_id,$openId){
		$rst = $this->db->select('tag_id')
			->from('rl_wx_user_tag')
			->where('company_id', $company_id)
			->where('openid', $openId)
			->order_by('id', 'desc')
			->get()->result_array();
		return $rst;
	}

	//根据tag_name获取tag_id
	public function get_id_by_tag_name ($tag_name){
		$rst = $this->db->select('id')
			->from('tag')
			->where('tag_name', $tag_name)
			->order_by('id', 'desc')
			->get()->row_array();
		return intval($rst['id']);
	}

	//根据openId获取用户id
	public function get_user_info ($openId){
		$rst = $this->db->select(['id', 'customer_id'])
			->from('wx_user')
			->where('openid', $openId)
			->get()->row_array();
		return $rst;
	}

	//记录订阅
	public function insert_rl_tag_user ($rl){
		$id = $this->db->insert('rl_wx_user_tag', $rl);
		if ($id > 0)
			return true;
		else 
			return false;
	}

	//重新订阅前删除已经订阅过的标签
	public function delete_tags ($openId){
		$rst = $this->db->delete('rl_wx_user_tag',array('openid'=>$openId));
		if ($this->db->affected_rows())
		{
			return true;
		}else{
			return false;
		}
	}

	//根据openid获取cid
	public function get_cid_by_wx_aid ($wx_aid){
		$rst = $this->db->select('company_id')
			->from('wx_account')
			->where('id', $wx_aid)
			->get()->row_array();
		return intval($rst['company_id']);
	}

	//根据openid标记微信用户
	public function update_is_member_by_openid ($openId,$member_id,$from){
		$this->db->where('openid', $openId);
		$this->db->update('wx_user',array('customer_id'=>$member_id,'customer_from'=>$from));
		if ($this->db->affected_rows())
		{
			return true;
		}else{
			return false;
		}
	}

	public function insert_user_gift($gift)
	{
		$id = $this->db->insert('wx_user_gifts', $gift);
		if ($id > 0){
			return true;
		} else {
			return false;
		}
	}

	public function get_user_info_by_customer_id($customerId)
	{
		$rst = $this->db->select(['id', 'openid'])
			->from('wx_user')
			->where('customer_id', $customerId)
			->get()->row_array();
		return $rst;
	}

	public function exchange_success($orderId)
	{
		$this->db->where('id', $orderId);
		$this->db->update('wx_user_gifts',array('status' => 1,'receive_time' => date('Y-m-d H:i:s', time())));
		if ($this->db->affected_rows())
		{
			return true;
		}else{
			return false;
		}
	}

	public function get_order($openId, $orderId)
	{
		$userInfo = $this->db->select(['id', 'customer_id'])
			->from('wx_user')
			->where('openid', $openId)
			->get()->row_array();
		$order = $this->db->select('*')->from('wx_user_gifts')
		->where('user_id', $userInfo['id'])
		->where('order_id', $orderId)
		->where('status', 0)
		->get()->row_array();
		return $order;
	}

	public function get_remind_orders()
	{
		$orders = $this->db
			->query("select * from ".$this->db->dbprefix('wx_user_gifts')." where status=0 and DATE(exchange_end) = DATE(NOW() + INTERVAL 1 WEEK)")
			->result();
		return $orders;
	}

	//根据Id获取用户openid
	public function get_user_info_by_id ($id){
		$rst = $this->db->select(['openid'])
			->from('wx_user')
			->where('id', $id)
			->get()->first_row();
		return $rst;
	}

	public function remind_success($id)
	{
		$this->db->where('id', $id);
		$this->db->update('wx_user_gifts',array('status' => 3));
		if ($this->db->affected_rows())
		{
			return true;
		}else{
			return false;
		}
	}

    public function send_remind_notify()
    {
        $orders = $this->get_remind_orders();
        foreach ($orders as $order) {
            $this->load->library('Wxapi');
            $json = array();
            $json["touser"] = $this->get_user_info_by_id($order->user_id)->openid;
            $json["template_id"] = "itfesQK57kUoj4zAP6akpeTW1OmtKpb54lB1OiJfCQk";
            $json["topcolor"] = "#FF0000";
            $json["data"] = array(
                "first"=>array(
                    "value" => "亲爱的泉粉您好，您参与的小样领取申请活动将于一周内到期，欢迎至柜领取！",
                    "color" => "#173177"
                ),
                "keyword1" => array(
                    "value" => $order->gift_name,
                    // "value" => '测试礼品',
                    "color" => "#173177"
                ),
                "keyword2" => array(
                    "value" => $order->counter,
                    // "value" => 1,
                    "color" => "#173177"
                ),
                "keyword3" => array(
                    "value" => $order->expire_time,
                    "color" => "#173177"
                ),
                "remark" => array(
                    "value" =>"祝您拥有健康美丽的肌肤！",
                    "color" => "#173177"
                )
            );
            $json = json_encode($json);
        	$this->load->library('Wxapi');
            $rst = $this->wxapi->send_template($this->wx_aid,$json);
            if ($rst['errcode'] == 0) {
                $this->remind_success($order->id);
            }
            return;
        }
    }

}


?>