<?php
/**
 * 微博授权服务前端处理
 *
 * PHP version 5
 *
 * @category  Mef
 * @package   Auth
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @link      http://www.masengine.com
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * 微博授权服务前端处理
 *
 * @category  Mef
 * @package   Auth
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class AuthWeibo extends CI_Controller
{
    /**
     * 预处理
     *
     * @return  void [description]
     */
    public function __construct()
    {
        parent::__construct();
        $this->s = $this->session->all_userdata();
        $this->p = $this->input->post();
        $this->g = $this->input->get();
        $this->load->model('h5page/AuthWeiboService');
        //表名称转换
        $this->tb = $this->config->item('tb');
    }    

    /**
     * 切换授权
     *
     * @return [type] [description]
     */
    public function authSwitch()
    {
        //var_dump($this->g);
        echo 'Loading';
        $this->AuthWeiboService->authSwitch();
        return;
    }    

    /**
     * 授权成功访问这里
     *
     * @return [type] [description]
     */
    public function auth()
    {
        //var_dump($this->g);
        $token = $this->AuthWeiboService->auth($this->g['code']);
        //var_dump($token);
        
        
        return;
    }



}
