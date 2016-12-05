<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MasEngine Base Models
 *
 * 扩展CI基础Models, 添加支持主从数据库，读写分离
 *
 * @package		MasEngine Base
 * @copyright	Copyright (c) 2014 - 2016, MasEngine, Inc.
 * @since		Version 3.0
 */

class ME_Model extends CI_Model
{

	public $_db_master;
	public $_db_slave;

	public function __construct()
	{
		parent::__construct();

		// $this->_db_master = &db('master');
		// $this->_db_slave = &db('slave');

		//$this->db = db('default');
	}

    public function insert($tablename = '', $data = array())
    {
        $return_arr = array();
        if ($this->db->insert($tablename, $data))
        {

            $return_arr['status'] = TRUE;
            if ($this->db->insert_id())
                $return_arr['insert_id'] = $this->db->insert_id();
        }
        else
        {
            $return_arr['status'] = FALSE;
            $return_arr['error'] = 'failed to insert database';
            $return_arr['error_code'] = MERET_SVRERROR;
        }
        return $return_arr;
    }

    public function insert_batch($tablename = '', $data = array())
    {
        if ($this->db->insert_batch($tablename, $data))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    public function get_where($tablename, $where = NULL, $limit = NULL, $offset = NULL)
    {
        $rs = $this->db->get_where($tablename, $where, $limit, $offset)->result_array();
        return $rs;
    }

    public function get_one($tablename, $where = NULL)
    {
        $this->db->select('*');
        $rs = $this->db->get_where($tablename, $where, 1);
        return $rs->row_array();
    }

    public function get_where_in($tablename, $select, $column, $arr)
    {
        $this->db->select($select);
        $this->db->from($tablename);
        $this->db->where_in($column, $arr);
        $rs = $this->db->get()->result_array();
        return $rs;
    }


    public function update($tablename, $where, $update_arr)
    {
        $this->db->where($where);
        return $this->db->update($tablename, $update_arr);
    }

    public function delete($tablename, $where)
    {
        $this->db->where($where);
        return $this->db->delete($tablename);
    }

    // 修改删除标记的删除
    public function update_delete_mark($tablename, $where)
    {
        $update = array(
            'is_deleted'=>1
        );
        $this->db->where($where);
        $rst = $this->db->update($tablename, $update, $where);
        return $rst;
    }

    public function list_fields($tablename)
    {
        return $this->db->list_fields($tablename);
    }

    public function count_all($tablename)
    {
        return $this->db->count_all($tablename);
    }

    public function safe_data($tablename, $data)
    {
        $fields = $this->db->list_fields($tablename);
        foreach ($data as $key => $val)
        {
            if (! in_array($key, $fields))
                unset($data[$key]);
        }
        return $data;
    }
}

/* End of file ME_Model.php */
/* Location: ./application/core/ME_Model.php */