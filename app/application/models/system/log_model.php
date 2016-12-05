<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function write_db($table_name, $log_info)
    {
        $fields = $this->db->list_fields($table_name);
        if (!isset($log_info['status']))
        {
            $log_info['status'] = '';
        }
        foreach ($log_info as $k => $v)
        {
            if (! in_array($k, $fields))
                unset($log_info[$k]);
        }
        $this->db->insert($table_name, $log_info);
        return;
    }

    public function write_file($filename, $log_info)
    {
        return;
    }
}