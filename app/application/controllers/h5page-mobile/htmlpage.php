<?php 
/**
 * H5page 前端处理
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
 * H5page 前端处理
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class Htmlpage extends CI_Controller
{
    /**
     * 预处理
     * 
     * @return void
     */
    public function __construct() 
    {
        parent::__construct();
        $this->load->model('h5page/Dosession');
        $this->load->model('h5page/AuthWeiboService');
        $this->g = $this->input->get();
        $this->p = $this->input->post();
        $this->s = $this->session->all_userdata();
        $s = $this->session->all_userdata();
        //表名称转换
        $this->tb = $this->config->item('tb');

    }

    /**
     * H5页面相应授权
     *
     * @return [type] [description]
     */
    public function h5auth()
    {
        $g = $this->input->get();
        $p = $this->input->post();
        //拿到code换access_token
        $token = $this->AuthWeiboService->auth($g['code']);
        if (!$token) {
            die('no token found');
        }
        $session = array('token' => $token);
        $this->session->set_userdata($session);
        $user = $this->db->get_where(
            $this->tb['user_w'], array('uid' => $token['uid']), 1
        )->result_array();

        if (empty($user)) {
            $user = $this->AuthWeiboService->getWeiboUserInfo($token['uid']);
            if ($user['result'] && $user['insert']) {
                $user_w_id = $user['insert'];
            } else {
                die('getWeiboUserInfo Failed');
            }
        } else {
            $user_w_id = $user[0]['id'];
        }
        $this->session->set_userdata(array('user_w_id' => $user_w_id));
        //进入H5活动页面
        if (isset($this->s['nonUid']) && $this->s['nonUid'] == 1) {
            $this->load->model('h5page/Linkh5');
            $this->Linkh5->auth();
        }
    }


}