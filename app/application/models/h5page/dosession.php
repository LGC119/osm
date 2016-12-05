<?php
/**
 * Session处理机制
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
 * Session处理机制
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class Dosession extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        //当前所有session信息
        $this->ses = $this->session->all_userdata();

        //当前的登录状态
        $this->s = isset($this->ses['ok']) ? true : false ;
    }


    //返回当前session信息
    public function ses()
    {
        $this->sessiondata = $this->session->all_userdata();
        return $this->sessiondata ;
    }

    //刷新session信息  
    public function refreshSession()
    {
        if (!$this->s) {
            return false;   
        }
        //查表
        $u = $this->db->get_where('user_b', array('id' => $this->ses['userinfo']['id']), 1)->result_array();
        $u = $u[0];
            $user_data = array(
                          'id'      => $u['id'],
                          'company' => $u['user_a_id'],
                          'name'    => $u['name'],
                          'app_key' => $u['app_key'],
                          'app_pay' => $u['app_pay'],
                          'status'  => $u['status'],
                          'template'=> $u['template'],
                         );
        $this->session->set_userdata(array('ok' => 12, 'userinfo' => $user_data));

        return true;
    }

    //获取所有用户字段信息  保密，不传客户端
    public function getUserinfo()
    {
        //if (!$this->s) {
        //    return false;   
        //}
        //查表
        $u = $this->db->get_where('user_b', array('id' => $this->ses['userinfo']['id']), 1)->result_array();
        $u = $u[0];
        return $u;
        
    }

    public function getAppInfo($b)
    {
        $u = $this->db->get_where('user_b', array('id' => $b), 1)->result_array();
        $rst['app_key']    = $u[0]['app_key'];
        $rst['app_pay']    = $u[0]['app_pay'];
        $rst['app_secret'] = $u[0]['app_secret'];
        return $rst;
    }

}
