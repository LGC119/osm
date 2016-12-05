<?php
/**
 * 项目和 H5模块协调通道
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

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * 项目和 H5模块协调通道
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class Linkh5_Model extends CI_Model
{
    /**
     * 初始化
     *
     * @return  void [description]
     */
    public function __construct()
    {
        parent::__construct();
        $this->g = is_array($this->input->get()) ? $this->input->get() : array();
        $this->p = is_array($this->input->post()) ? $this->input->post() : array();
        $this->s = $this->session->all_userdata();
        //表名称转换
        $this->tb = $this->config->item('tb');
        $this->load->model('h5page/AuthWeiboService');


    }

    /**
     * 参加用户信息调取
     * 
     * @param string $field  [description]
     * @param string $uid    [description]
     * @param string $format [description]
     * 
     * @return array         [description]
     */
    public function user_link($field, $uid, $format = '')
    {

        if (is_array($uid)) {
            //多数据查询
            $this->db->where_in($field, $uid);
        } else {
            //单数据查询
            $this->db->where($field, $uid);
        }

        $rst = $this->db->get($this->tb['user_' . $field])->result_array();
        //var_dump($rst);
        //configthis//
        $conv_uid = array(
                'display_name' => 'screen_name',
                'province'     => 'province',
                'city'         => 'city',
                'avartar'      => 'profile_image_url',
                'uid'          => 'uid',
                'sex'          => 'gender'
            );
        $conv_openid = array(
                'display_name' => 'nickname',
                'province'     => 'province',
                'city'         => 'city',
                'avartar'      => 'imgurl',
                'uid'          => 'openid',
                'sex'          => 'sex',
            );
        $conv = 'conv_' . $field;
        $conv = $$conv;
        $return = array();
        if (!empty($format) ) {
            foreach ($rst as $k => $v) {
                foreach ($conv as $key => $value) {
                    $return[ $v[$conv['uid']] ][$key] = $v[$value];
                }
            }
        }
        
        //针对不同的表来源进行处理
        switch ($field) {
        case 'uid':

            break;
        case 'openid':
            // 由 性别：1男，2女转换为 ：m男,f女,0未知 
            $sex = array('1' => 'm', '2' => 'f' , '0' => '0' );
            /*foreach ($rst as $k => $v) {
                $return[$k][$v['sex']] = $sex[$v['sex']];
                //$return[$k][$v['avartar']] = BASEPATH . $v['sex'];
            }*/
            foreach ($return as $uid => $item) {
                $return[$uid]['avartar'] = base_url() . '/resource/' . $item['avartar'];
            }
            break;
        }
        return $return;
    }

    /**
     * 不匹配uid时的动作
     * 
     * @return void [description]
     */
    public function nonUid()
    {
        $this->session->set_userdata('nonUid', 1);
        //$u = $this->db->get_where($this->tb['user_b'], array('id' => $this->s['user_b_id']))->result_array();
        $session = array(
                 //  'sub_appkey'   => $u[0]['app_key'],
                   'app_secret'   => WB_SKEY,
                   'appkey'       => WB_KEY,
                  );
        $this->session->set_userdata('weibo', $session);
        redirect($this->AuthWeiboService->authurl(base_url() . 'index.php/htmlpage/h5auth'));
    }

    /**
     * 授权跳转到H5页面
     * 
     * @return void
     */
    public function auth()
    {
        $this->session->set_userdata('nonUid', 0);
        $s = $this->session->userdata('token');
        $input = array(
            'id' => $this->session->userdata($this->tb['activity']),
            'uid' => $s['uid'],
            );
        $query = http_build_query($input);
        redirect(base_url() . 'index.php/wxh5_ext/go?' . $query);
    }
}
