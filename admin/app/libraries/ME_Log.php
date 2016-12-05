<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ME_Log {

    var $CI = '';

    var $type = '';

    var $table_name = '';

    public function __construct($params = array('table_name' => 'log', 'type' => 'db'))
    {
        extract($params);

        $this->CI =& get_instance();

        $this->CI->load->model('system/log_model');

        $this->type = (! isset($type) || '' == $type) ? 'db' : $type;

        if ('db' == $type)
        {
            $this->table_name = (! isset($table_name) || '' == $table_name) ? 'log' : $table_name;
        }
        
    }

    public function write_log($log_info = array())
    {
        if ('db' == $this->type)
        {
            $this->_write_db($log_info);
        }
        else
        {
            $this->_write_file($log_info);
        }
    }

    private function _write_db($log_info = array())
    {
        $this->CI->log_model->write_db($this->table_name, $log_info);
    }

    private function _write_file($log_info = array())
    {
        return;
    }

}
