<?php 
/**
 * 短网址处理
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
 * 短网址处理
 *
 * @category  Mef
 * @package   H5
 * @author    RenKai <renkai@masengine.com>
 * @copyright 2013 MasEngine
 * @license   http://www.masengine.com Licence
 * @version   Release: 1.1
 * @link      http://www.masengine.com
 */
class S extends CI_Controller {

    /**
     * Index Page for this controller.
     */
    public function __construct() 
    {
        parent::__construct();
        $this->load->helper('url');
        $this->sessiondata = $this->session->all_userdata();
        $this->load->model('h5page/surl');
    }
    
    public function index()
    {
        $rst = $this->surl->access();
        //redirect($url[0]['original_url'] . $query);
        if ($rst['url'])
        {
            //echo $url[0]['original_url'] . $query;
            $pre = '';
            if (!empty($input) ) 
            {
                if ( preg_match('*\?*', $rst['url']['original_url'])) 
                {
                    $pre = '&';
                } 
                else 
                {
                    $pre = '?';
                }
            }
            redirect($rst['url']['original_url'] . $pre . $rst['query']);
        } 
        else 
        {
            echo 'no url';
        }

    }

    public function create()
    {
        $url = $this->input->get('url');
        $return = $this->surl->createUrl($url);
        echo '<pre>';
        var_dump($return);
    }
    public function test()
    {
        $this->load->view('h5page/test.php');
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
