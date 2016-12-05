<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* 
*/
class Osmapi extends CI_Controller
{

    //领取礼物成功接口
    public function exchange_success()
    {
        if (isset($_POST['order_id'])) {
            $orderId = $_POST['order_id'];
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '请传入交易ID', 'status' => 'failed')));
        }
        if (isset($_POST['open_id'])) {
            $openId = $_POST['open_id'];
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '请传入openid', 'status' => 'failed')));
        }

        $this->load->library('Osm_api');
        $this->load->model('osm_model', 'model');
        $openid_arr = array('openId'=>$openId);
        $rst = $this->osm_api->get_member_info_by_openid($openid_arr);
        if (isset($rst['error'])) {
            switch ($rst['error']) {
                case 1:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '用户名或密码不对。', 'status' => 'failed')));
                    break;
                
                case 2:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '请传入openid', 'status' => 'failed')));
                    break;
                
                case 3:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '没有找到会员信息，请致电400', 'status' => 'failed')));
                    break;
                
                default:
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array('msg' => '系统错误，请重新查询。', 'status' => 'failed')));
                    break;
                    break;
            }
        }
        $order = $this->model->get_order($openId, $orderId);
        if (!$order) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '订单不存在', 'status' => 'failed')));
        }
        $update_rst = $this->model->exchange_success($order['id']);
        if (!$update_rst) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('msg' => '更新订单信息失败，请联系客服', 'status' => 'failed')));
        }

        $this->load->library('Wxapi');
        $json = array();
        $json["touser"] = $openId;
        $json["template_id"] = "kr2WItccHQ040K5FCB2x5QC8eIqhaY2nNnxOqdB9bhs";
        $json["topcolor"] = "#FF0000";
        $json["data"] = array(
            "first"=>array(
                "value" => "您好，您已成功领取礼物！",
                "color" => "#173177"
            ),
            "keyword1" => array(
                "value" => $order['gift_name'],
                "color" => "#173177"
            ),
            "keyword2" => array(
                "value" => $order['count'],
                "color" => "#173177"
            ),
            "keyword3" => array(
                "value" => date('Y-m-d H:i:s', time()),
                "color" => "#173177"
            ),
            "remark" => array(
                "value" =>"感谢您的使用，请注意查收礼品。",
                "color" => "#173177"
            )
        );
        $json = json_encode($json);
        $rst = $this->wxapi->send_template(4, $json);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('msg' => '兑换成功', 'status' => 'success')));
    }

}
