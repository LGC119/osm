<?php 
/**
 * H5page 广告统计分析系统
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
 * H5page 广告统计分析系统
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.5
 * @link      http://www.masengine.com
 */
class H5ads extends CI_Controller
{
    /**
     * 初始化
     *
     * @return void
     */
    public function __construct() 
    {
        parent::__construct();
        $this->load->model('h5page/H5public');
        $this->s = $this->session->all_userdata();
        $this->b_id = $this->s['userinfo']['id'];
        $this->c_id = isset($this->s['user_c_id']) ? $this->s['user_c_id'] : 0 ;
        
        //表名称转换
        $this->tb = $this->config->item('tb');

    }
    
    /**
     * 上传图片
     * 
     * @return void
     */
    public function picUpload()
    {
        if (empty($_FILES)) die('no pic');
        $rst = $this->H5public->uploader(
            '../public/uploads/h5page/' . $this->b_id. '/img/',
            null, null, 
            'ads_', 'uppic'
        );
        //var_dump($rst);
        /*if ($rst['error'] == 0) {
            //存图片表
            $set = array(
                    'user_b_id' => $this->b_id,
                    'time'      => time(),
                    'filename'  => $rst['message']['file_name'],
                );
            $this->db->set($set);
            if (!$this->db->insert('picture')) {
                die('sql error');
            };
        }*/
        $path = $rst['url'];
        echo <<<pg_p
        <script type="text/javascript">
       parent.document.getElementById('picpath').value="{$path}";
       parent.document.getElementById('imgpath').innerHTML = '<center><img id="upimage"  src="{$path}" /></center>';
        </script>
pg_p;
    }
}