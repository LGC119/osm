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
class Wxh5_Ext extends CI_Controller
{
    /**
     * 预处理
     *
     * @return  void [description]
     */
    public function __construct()
    {
        parent::__construct();
        $this->ses = $this->session->all_userdata();
        $this->s = isset($this->ses['ok']) ? true : false ;
        $this->load->model('h5page/h5page_model');
        $this->load->model('h5page/linkh5_model');
        //表名称转换
        $this->tb = $this->config->item('H5page_table');
    }    


    public function submiter()
    {
        $this->h5page_model->submiter();
        return false;
    }

    // 用户访问页面时，找到该页面信息
    public function go()
    {
        $g = $this->input->get();
        $uid = isset($g['uid']) ? $g['uid'] : null ;
        $openid = isset($g['openid']) ? $g['openid'] : null;

        // 判断是微博/PC访问还是微信访问，定义不同变量
        $identify = 'uid';
        if (empty($uid)) $identify = 'openid';

        // 查找对应页面信息
        $page_info = $this->h5page_model->get_h5page_info($g['id']);
        if (empty($page_info)) exit('暂无相关页面');

        // 查找是否有相应活动与该页面关联，如果活动已经结束，则不显示页面（尚未开发）

        // 如果没有uid传入，且不是信息展示页，则跳转到 授权（信息展示页无需授权）（按理说应该是如果有活动再进行这个判断，因为活动基本都不是信息展示页）
        if ($$identify !== 'noooo' && empty($uid) && empty($openid) && $page_info['template'] != '104info') 
        {
            
            $arr = array('authurl' => base_url() . 'index.php/h5page/wxh5_ext/gotoAuth');
            $this->load->view(H5PAGE_TPL_PATH . 'needAuth.html', $arr); 
            return ;
        }
        
        //识别用户
        $session = array(
                    'info'                => $uid, //旧版本兼容
                    'uid'                 => $uid,
                    'openid'              => $openid,
                    'identify'            => $identify,
                    $this->tb['h5page'] => $g['id'],
                    'user_b_id'           => $page_info['company_id'],
                    );
        $this->session->set_userdata($session);

        //避开编辑状态（暂不理解noooo，似乎是用来测试，或通过没有用户信息的链接访问）
        if ($$identify != 'noooo') {
            //记录用户访问
            //通过uid，openid拉取用户数据
            $user_info = $this->linkh5_model->user_link($identify, $$identify, 'fsd');

            $insert = array(
                'time'         => time(),
                'info'         => 'noooo',
                'event_id'  => $id,
                $identify      => $$identify,
                'sex'          => $user_info[$$identify]['sex'],
                'display_name' => $user_info[$$identify]['display_name'],
                'avartar'      => $user_info[$$identify]['avartar'],
                'province'     => $user_info[$$identify]['province'],
                'city'         => $user_info[$$identify]['city'],
                'b_id'         => $this->session->userdata('user_b_id'),
            );
            $this->db->set($insert);
            $wherePrepare = array(
                    'uid' => $uid ,
                    'openid' => $openid,
                    'event_id' => $id, 
                );
            $where = array();
            foreach ($wherePrepare as $k => $v) {
                if (!empty($v)) {
                    $where[$k] = $v;
                }
            }
            $isuser = $this->db->get_where($this->tb['participants'], $where)->result_array();
            if ( (!$isuser) && $this->db->insert($this->tb['participants'])) {
                $this->session->set_userdata(array('db_log_id' => $this->db->insert_id()));
            } else {
                $this->session->set_userdata(array('db_log_id' => $isuser[0]['id']));
            }
        }

        $this->load->view(H5PAGE_TPL_PATH . 'tpl_' . $page_info['template'] . '.html', array('htmls' => $page_info)); //输出网页头
        $this->load->view(H5PAGE_TPL_PATH . 'tpl_footer.html');
    }

    // 前台AJAX调用该方法，获取页面html代码
    public function gethtml()
    {
        $html = $this->h5page_model->gethtml();
        echo $html;
    }

    public function mobile()
    {
        $tpl  = $this->input->get('tpl');
        $this->h5page_model->mobile($tpl);
        return false;
    }



    public function t2() 
    {
            $rst = $this->db->query("
                        SELECT 
                            us.city c , 
                            COUNT(pa.uid ) cnt
                        FROM meo_h5_participants pa
                        LEFT JOIN meo_wx_users us
                            ON pa.uid = us.uid 
                        GROUP BY c
                        ORDER BY cnt DESC
                        LIMIT 10
                        ")->result_array();
            echo '<pre>';
            var_dump($rst);
    }
    public function t()
    {
        $id  = $this->input->get('id');
        $uid = $this->input->get('uid');
        echo $id,'==',$uid;
        
        $sql     = "delete from meo_h5_participants where activity = 30";
        $result  = $this->db->query($sql);//发送语句，返回结果集/bool
        
        $sql     = "select uid from meo_wx_users ";
        $resulter  = $this->db->query($sql)->result_array();//发送语句，返回结果集/bool
        if (empty($resulter)) {
            die('no uid');
        }
        
        $time =  strtotime(date('Y-m',time()));
        $time =  time() - 30 * 24 * 3600;
        
        foreach ($resulter as $k => $v) {
            $rand   = mt_rand(200,6099);
            $time   = $time + $rand ;
            $info   = rand(2,8) == 6 ? 'noooo' : mt_rand();
            $sql    = "insert into meo_h5_participants values(null,'{$info}', {$time} ,30,'{$v['uid']}',(floor(rand()*10)%2))";
            $result  = $this->db->query($sql);//发送语句，返回结果集/bool
            //echo $rst['uid'];
        
        }
    }


    /**
     * 富文本编辑器里的图片上传
     *
     * @return void 
     */
    public function editorUpload()
    {
        //var_dump($_FILES);
        $rst = $this->h5page_model->uploader('../uploads/h5page/' . $this->ses['userinfo']['id'] . '/h5page/img/', null, null, 'editor_');
        //var_dump($rst);
        /*if($rst['error'] == 0){
            $this->db->set(array(
                            'user_b_id' => $this->ses['userinfo']['id'],
                            'time' => time(),
                            'filename' => $rst['message']['file_name'],
                            ));
            if (!$this->db->insert('picture')){
                die('sql error');
            };
        }*/
        
        header('Content-type: text/html; charset=UTF-8');
        $rst['width'] = '100%';
        $rst['style'] = 'width: 100%; height: auto; border: 0px;';
        echo json_encode($rst);
        exit;
    }

    public function picUpload()
    {
        //var_dump($_FILES);
        $rst = $this->h5page_model->uploader('../uploads/h5page/' . $this->ses['userinfo']['id'] . '/img/', null, null, 'photo_', 'Filedata');
        //var_dump($rst);
        if($rst['error'] == 0){

            //存图片表
            /*$this->db->set(array(
                            'user_b_id' => $this->ses['userinfo']['id'],
                            'time' => time(),
                            'filename' => $rst['message']['file_name'],
                            ));
            if (!$this->db->insert('picture')){
                die('sql error');
            };*/


        }


        echo json_encode($rst);
    }


    /**
     * H5广告 记录访问信息
     * 
     * @return string result
     */
    public function clicklogger() 
    {
        $i = 1;
        $p = $this->input->post('click');
        $title = $this->input->post('title');
        $adid = $this->input->post('adid');  //广告id
        
        //h5 广告统计系统记录信息
        $insert = array(
                'ad' => $adid,
                'time' => time(),
                'participants_id'=> $this->ses['db_log_id'],
                'ip' => $_SERVER['REMOTE_ADDR'],
            );
        $this->db->insert($this->tb['ads_logger'], $insert);
        $ads = $this->db
            ->where(array('id' => $adid))
            ->get($this->tb['ads'])
            ->result_array();
        $this->db
            ->where(array('id' => $adid))
            ->update($this->tb['ads'], array('count'=> $ads[0]['count'] + 1));
            

        //读出点击数据
        $id = $this->session->userdata($this->tb['activity']);
        $this->db->select('clickurl');
        $rst = $this->db
            ->get_where($this->tb['activity'], array('id' => $id))
            ->result_array();
        $rst    = json_decode($rst[0]['clickurl'], true);
        $identify    = $this->ses['identify'];
        $uid = $this->ses[$identify];
        if (empty($uid) and empty($openid)) {
            
            return 8;
        } else {
            $this->db->select('readurl');
            $isuser = $this->db->get_where($this->tb['participants'], array($identify => $uid))->result_array();
            
        }
        //数据加一
        if (isset($rst[$p])) {
            $rst[$p]['counter'] += 1;
        } else {
            $rst[$p]['counter'] = 1;
        }
        if (isset($rst[$p]['title'][$title])) {
            $rst[$p]['title'][$title] += 1 ;
        } else {
            $rst[$p]['title'][$title] = 1 ;
        }
        
        //写入
        $ok = $this->db->update($this->tb['activity'], array('clickurl' => json_encode($rst)), array('id' => $id));
        $ok2 = $this->db->update($this->tb['participants'], array('readurl' => 1), array($identify => $uid));

        echo ($ok && $ok2) == true ? 'ok' : '0';
        return  ($ok && $ok2) == true ? 'ok' : '0';
    }

    /**
     * 转入授权
     *
     * @return [type] [description]
     */
    public function gotoAuth()
    {
        $this->load->model('h5page/Linkh5');
        $this->Linkh5->nonUid();
    }

}
