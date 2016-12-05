<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Base_Controller extends CI_Controller
{
    static $_log_info = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->library('ME_Log');

    }

    public function __destruct()
    {
        $directory                     = $this->router->fetch_directory() ? $this->router->fetch_directory() : 'controllers';
        $this->_log_info['directory']  = trim($directory, '/');
        $this->_log_info['class']      = $this->router->fetch_class();
        $this->_log_info['method']     = $this->router->fetch_method();
        
        $this->_log_info['ip'] = $this->input->is_cli_request() ? 'localhost' : $_SERVER['REMOTE_ADDR'];
        
        $this->_log_info['company_id'] = $this->session->userdata('company_id') ? $this->session->userdata('company_id') : 0;
        $this->_log_info['staff_id']   = $this->session->userdata('staff_id') ? $this->session->userdata('staff_id') : 0;
        
        $this->_log_info['time']       = date('Y-m-d H:i:s');
        $this->_log_info['wb_id']      = $this->session->userdata('wb_aid') ? $this->session->userdata('wb_aid') : 0;
        $this->_log_info['wx_id']      = $this->session->userdata('wx_aid') ? $this->session->userdata('wx_aid') : 0;

        $this->me_log->write_log($this->_log_info);
    }
}