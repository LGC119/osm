<?php
/**
 * 微博授权服务
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
 * 微博授权服务
 *
 * @category  Mef
 * @package   Auth
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class AuthWeiboService extends CI_Model
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
    }

    /**
     * 调用微博api接口使用这个东西
     * 
     * @param string $fn function name
     * 
     * @return object     返回new的对象
     */
    public function wbApi($fn = 'SaeTClientV2')
    {
        $this->s = $this->session->all_userdata();
        $apipath = './' . APPPATH.'models/h5page/saetv2.ex.class.php';
        include_once $apipath;

        //判决appkey 来源
        if (isset ($this->s['weibo'])) {
            //$akey = $this->s['weibo']['sub_appkey'];
            $akey = $this->s['weibo']['appkey'];
            $skey = $this->s['weibo']['app_secret'];
        } else {
            $akey = WX_WB_AKEY;
            $skey = WX_WB_SKEY;
        }

        if ($fn == 'SaeTClientV2') {
            $token = isset($this->s['weibo']['access_token']) ?
                $this->s['weibo']['access_token'] : $this->s['token']['access_token'];
            $return = new $fn($akey, $skey, $token);
        }
        if ($fn == 'SaeTOAuthV2') {
            $return = new $fn($akey, $skey);
        }
        
        return $return;
    }


    /**
     * 微博授权切换
     *
     * @return void 
     */
    public function authSwitch()
    {
        //用于授权跳转，正式情况下需要禁用这个**
        if (isset($this->g['state']) && isset($this->g['code'])) {
            redirect(
                $this->g['state'] 
                . '?code=' 
                . $this->g['code'] . '&state=' . $this->g['state']
            );
        } else {
            echo 'error authweibo  ';
            var_dump($_POST);
            var_dump($_GET);
        }
        return;
    }

    /**
     * 微博授权成功跳转页面
     * 
     * @return void
     */
    public function auth($code, $callback = WX_WB_CALLBACK_SWITCH)
    {
        $o = $this->wbApi('SaeTOAuthV2');
        //拿code换accessToken
        $keys = array();
        $keys['code'] = $code;
        $keys['redirect_uri'] = $callback ;
        try {
            $token = $o->getAccessToken('code', $keys);
        } catch (OAuthException $e) {
            //授权失败到这里
            echo 'auth error';
            //var_dump($e);
        }

        //授权出错的处理方式
        if (!isset($token) or empty($token)) {
            //$this->getAuth();
            //echo '<a href="' . $this->authurl() . '">授权出错，点此重新授权</a>';
            echo '授权出错';
            var_dump($this->g);
            return false;
        }
        return $token;
    }

    /**
     * 生成授权URL
     *
     * @param string  $state      状态璨璨菽
     * @param string  $callback   回调地址
     * @param string  $display    显示方式,mobile:H5
     * @param boolean $forcelogin 强制重新登录
     *
     * @return string              授权地址
     */
    public function authurl($state = WX_WB_CALLBACK_URL, $callback = WX_WB_CALLBACK_SWITCH, $display = 'mobile', $forcelogin = false)
    {
        $o = $this->wbApi('SaeTOAuthV2');
        $code_url = $o->getAuthorizeURL($callback, 'code', $state, $display, $forcelogin);
        return $code_url;
    }

    /**
     * 获取微博用户基本信息
     *
     * @param string $uid uid
     * @param bool $addNew 是否插入数据库
     *
     * @return array      用户信息数组
     */
    public function getWeiboUserInfo($uid, $addNew = true)
    {
        $wb = $this->wbApi();
        $user = $wb->show_user_by_id($uid);

        //若数据库中没有这个用户,则插入user_w
        $get_where = array('uid' => $uid);
        $findUser = $this->db->get_where($this->tb['user_w'], $get_where)->result_array();
        $insert = false;
        if (empty($findUser) && $addNew) {
            $userInsert = array(
                'uid'                => $user['id'],
                'screen_name'        => $user['screen_name'],
                'name'               => $user['name'],
                'province'           => $user['province'],
                'city'               => $user['city'],
                'location'           => $user['location'],
                'description'        => $user['description'],
                'url'                => $user['url'],
                'profile_image_url'  => $user['profile_image_url'],
                'profile_url'        => $user['profile_url'],
                'domain'             => $user['domain'],
                'weihao'             => $user['weihao'],
                'gender'             => $user['gender'],
                'followers_count'    => $user['followers_count'],
                'friends_count'      => $user['friends_count'],
                'statuses_count'     => $user['statuses_count'],
                'favourites_count'   => $user['favourites_count'],
                'created_at'         => $user['created_at'],
                'allow_all_act_msg'  => $user['allow_all_act_msg'],
                'geo_enabled'        => $user['geo_enabled'],
                'verified'           => $user['verified'],
                'allow_all_comment'  => $user['allow_all_comment'],
                'avatar_large'       => $user['avatar_large'],
                'verified_reason'    => $user['verified_reason'],
                'follow_me'          => $user['follow_me'],
                'bi_followers_count' => $user['bi_followers_count'],
                'lang'               => $user['lang'],
            );
            $this->db->set($userInsert);
            $this->db->insert($this->tb['user_w']);
            $insert = $this->db->insert_id();
        }
        $return = array('data' => $user, 'result' => true, 'insert' => $insert);
        return $return;
    }

    /**
     * 转发微博
     *
     * @param string $sid       原微博id
     * @param string $text      添加的评论信息。可选。
     * @param int    $isComment 转发的同时发表评论，0：否、1：评论给当前微博、2：评论给原微博、3：都评论，默认为0。
     *
     * @return array              [description]
     */
    public function weiboRepost($sid, $text = null, $isComment = 0)
    {
        $wb = $this->wbApi();
        $return = $wb->repost($sid, $text, $isComment);
        return $return;
    }

    /**
     * 发布一条微博信息 
     *
     * @param string $status      [微博信息内容不超过140个汉字]
     * @param float  $picPath     图片路径
     *
     * @return array 
     */
    public function WeiboPublish($status, $picPath = null)
    {
        $wb = $this->wbApi();
        $fn = empty($picPath) ? 'update' : 'upload';
        
        if ($picPath) {
            //定义绝对网址
            $server = 'http://' . $this->input->server('HTTP_HOST');
            $picPath = $server . $picPath;
        }
        
        $return = $wb->$fn($status, $picPath);
        return $return;
    }

    /**
     * 发表图片微博
     *
     * 发表图片微博消息。目前上传图片大小限制为<5M。 
     *  
     * @param string $status 要更新的微博信息。信息内容不超过140个汉字, 为空返回400错误。
     * @param string $pic_path 要发布的图片路径, 支持url。[只支持png/jpg/gif三种格式]
     * 
     * @return array
     */
    public function weiboUpload($status, $pic_path)
    {
        $wb = $this->wbApi();
        $return = $wb->upload($status, $pic_path);
        return $return;
    }

    /**
     * 获取用户发布的微博信息列表
     *
     * 返回用户的发布的最近n条信息
     *
     * @param string $uid   指定用户UID或微博昵称
     * @param int    $page  页码
     * @param int    $count 每次返回的最大记录数，最多200，默认50。
     *
     * @return  array 
     */
    public function weiboNewList($uid, $page = 1 , $count = 20)
    {
        $wb = $this->wbApi();
        $return = $wb->user_timeline_by_id($uid, $page, $count);
        return $return;
    }

}
