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

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 项目和 H5模块协调通道
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class H5public extends CI_Model
{
    /**
     * Initialization
     *
     * @return void [description]
     */
    public function __construct()
    {
        parent::__construct();
/*      
        $this->g = is_array($this->input->get()) ? $this->input->get() : array();
        $this->p = is_array($this->input->post()) ? $this->input->post() : array();*/
    }

    /**
     * 文件上传
     * @param  string     $upload_path     上传文件路径
     * @param  string     $allowed_types   上传文件类型， gif|jpg|png|bmp
     * @param  string     $file_name       给定文件名字  默认日期随机MD5
     * @param  string     $name_prefix     文件名前缀
     * @return array [result] 0失败,1成功
     *                [msg]    文件信息
     *                [path]   成功后的文件路径
     **/
    public function uploader($upload_path = '../public/uploads/h5page/', $allowed_types = 'gif|jpg|png|bmp', $file_name = null, $name_prefix = 'activity_', $uploadForm = 'imgFile') 
    {
        $config['upload_path']   = $upload_path ===null ? '../public/uploads/h5page/' . $this->wx_id . '/h5page/img/' : $upload_path;
        $config['allowed_types'] = $allowed_types === null ? 'gif|jpg|png|bmp' : $allowed_types;
        
        $time                    = date('Ymd_His', time());
        $config['file_name']     = $file_name === null ? $name_prefix . $time . '_' . md5($time . rand(0,9999)) : $name_prefix . $file_name;
        
        //目录不存在就创建
        if(!is_dir($config['upload_path'])){
            mkdir($config['upload_path'],0777,true);
        }

        $this->load->library('upload', $config);

        //判断是否成功
        if ( !$this->upload->do_upload($uploadForm)) {
            $info['error']   = 1;
            $info['message'] = $this->upload->display_errors();
            //$info['url']   = '';
        } else {
            $path = explode('/', $_SERVER['SCRIPT_NAME']);
            array_pop($path);
            $path = implode('/', $path);
            $data = $this->upload->data();
            //绝对地址 网页根
            $filepath =$path . '/' . $config['upload_path'] . $data['file_name'];
            //相对地址
            //$filepath = $config['upload_path'] . $data['upload_data']['file_name'];
            $info['error']   = 0;
            $info['url']     = $filepath;
            $info['message'] = $this->upload->data();
        }

        return $info;
    }
    //条件筛选查询
    public function sqlFilter($filter)
    {
        $where  = array();
        foreach ($fil as $key => $value) {
            if (empty($value) or !is_array($value)) {
                continue;
            } // End if
            foreach ($value as $k => $v) {
                if (empty($v)) continue;
                switch ($k) {
                    case 'like':
                        $return = $return->like('display_name',$v);
                        break;
                    case 'mass':
                        if (is_array($v)) {
                            $where['array'][$v['0'] . ' ' . $v['1']] = $v['2'];
                        }
                        $where['sql'] = " {$v[0]} {$v[1]} {$v[2]}";
                        break;
                    default:
                        $where[$k] = $v;
                        break;
                } // End switch
            } // End foreach
        }
        return $return;
    }

}
