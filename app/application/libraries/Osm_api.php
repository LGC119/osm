<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Osm_api {
    public function __construct() {
        $this->location = 'http://www.osmcrm.com:8030/MemberService.asmx?wsdl';
        $this->uri = 'http://tempuri.org';
        $this->params = array('user' => 'admin',
                              'pwd'  => 'Lkjhg0987');
        try {
            // $this->client = new SOAPClient(null, array('location' => $this->location,
            //                                             'uri'     => $this->uri));
            $this->client = new SOAPClient($this->location);
        } catch (SOAPFault $e) {
            echo $e->getMessage();
        }
    }

    // 某些接口只能通过 http 方法调用
    private function _get($method, $params) {
        $this->url = 'http://www.osmcrm.com:8030/MemberService.asmx';
        $query = http_build_query($params);
        $this->url .= '/' . $method . '?' . $query;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $rst = curl_exec($ch);
        // 处理 xml
        $rst = $this->_xml_string_to_array($rst);
        return $rst;
    }

    // 可能需要增加 post 方法

    // xml 字符串中需要的信息转为数组
    private function _xml_string_to_array($xml_string) {
        $info_arr = array();
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->loadXML($xml_string);
        $all_info = $this->dom->getElementsByTagName('Table');  // 需要的信息在 Table 节点中
        $all_info = $all_info->length ? $all_info : $this->dom->getElementsByTagName('Result'); // 也有可能在 Result 中...
        // 遍历子节点
        if ($all_info->length == 0) {
            $all_info = $this->dom->getElementsByTagName('Error');
            $info = $all_info->item(0);
            for($j = 0; $j < $info->childNodes->length; $j++) {
                $info_arr['error'] = $info->childNodes->item($j)->nodeValue;
            }
        }else{
            for ($i = 0; $i < $all_info->length; $i++) {
                // 取出唯一一个 info 节点
                $info = $all_info->item($i);
                for($j = 0; $j < $info->childNodes->length; $j++) {
                    $info_arr[$i][$info->childNodes->item($j)->nodeName] = $info->childNodes->item($j)->nodeValue;
                }
            }
        }
        if (isset($info_arr['#text'])) unset($info_arr['#text']);
        return $info_arr;
    }

    // 获取可用的方法
    public function get_functions() {
        $functions = $this->client->__getFucntions();
        return $functions;
    }

    // 注册用户
    public function register($params) {
        $this->params = array_merge($this->params, $params);
        // var_dump($this->params);
        $rst = $this->client->CreateMember($this->params);
        return $rst->CreateMemberResult;
    }

    // 发送手机验证码
    public function send_check_code($params) {
        $this->params = array_merge($this->params, $params);
        $rst = $this->client->CreateCheckCode($this->params);
        return $rst;
    }

    // 获取手机验证码
    public function get_check_code($params) {
        $this->params = array_merge($this->params, $params);
        // var_dump($this->params);
        $rst = $this->client->GetCheck($this->params);
        return $rst;
    }

    // 会员绑定微信
    public function bind_wx($params) {
        $this->params = array_merge($this->params, $params);
        $rst = $this->client->BindMember($this->params);
        return $rst;
    }

    // 通过 openId 查询会员信息
    public function get_member_info_by_openid($params) {
        $this->params = array_merge($this->params, $params);
        // $rst = $this->_get('GetBindingMemberInfoByOpenId',$this->params);
        $rst = $this->client->GetBindingMemberInfoByOpenId($this->params);
        $rst = $this->_xml_string_to_array($rst->GetBindingMemberInfoByOpenIdResult->any);
        return $rst;
    }

    // 通过 openId 查询会员积分
    public function get_member_points_by_openid($params) {
        $this->params = array_merge($this->params, $params);
        // $rst = $this->_get('GetMemberPoints',$this->params);
        $rst = $this->client->GetMemberPoints($this->params);
        return $rst;
    }

    // 获取积分礼品兑换列表
    public function get_points_gift() {
        // var_dump($this->params);
        $rst = $this->client->GetPointsGift($this->params);
        $rst = $this->_xml_string_to_array($rst->GetPointsGiftResult->any);
        // $rst['user'] = $this->params;
        return $rst;
    }

    // 积分兑换礼品
    // 兑换礼品
    public function points_exchange($params) {
        $this->params = array_merge($this->params, $params);
        $rst = $this->client->AddPointExchange($this->params);
        return $rst;
    }

    // 预约时间查询
    public function get_exchange_date($params = array()) {
        $this->params = array_merge($this->params, $params);
        // var_dump($this->params);
        $rst = $this->_get('GetExchangeDate', $this->params);
        return $rst;
    }

    // 兑换明细查询
    public function get_exchange_detail($params) {
        $this->params = array_merge($this->params, $params);
        // var_dump($this->params);
        $rst = $this->client->GetExchangeDetail($this->params);
        $rst = $this->_xml_string_to_array($rst->GetExchangeDetailResult->any);
        return $rst;
    }

    // 会员订阅
    public function MemberSubscript($params) {
        $this->params = array_merge($this->params, $params);
        // var_dump($this->params);
        $rst = $this->client->MemberSubscript($this->params);
        return $rst;
    }

}