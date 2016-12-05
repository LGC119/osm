<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report_model extends CI_model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_company_ids()
    {
        $rs = $this->db->select('id')->get('company')->result_array();
        $company_ids = array();
        foreach ($rs as $val)
        {
            $company_ids[] = $val['id'];
        }
        return $company_ids;
    }

    // total of weibo & weixin in each hour
    public function get_all_type_hour_communication_data($company_id, $day)
    {

        $this->db
                ->select("DATE_FORMAT(`created_at`, '%Y-%m-%d %H') as hour, type, count(*) as type_total", FALSE)
                ->from("wb_communication")
                ->where("DATE_FORMAT(`created_at`, '%Y-%m-%d') = '{$day}'")
                ->group_by("type")
                ->group_by("hour");
        $rs1 = $this->db->get()->result_array();

        $this->db
                ->select("DATE_FORMAT(`created_at`, '%Y-%m-%d %H') as hour, 'weixin' as type, count(*) as type_total", FALSE)
                ->from("wx_communication")
                ->where("DATE_FORMAT(`created_at`, '%Y-%m-%d') = '{$day}'")
                ->group_by("hour");
        $rs2 = $this->db->get()->result_array();

        $rs = array_merge($rs1, $rs2);
        $type_total = array();
        foreach ($rs as $val)
        {
            $type_total[$val['type']][$val['hour']]['type_total'] = $val['type_total'];
        }
print_r($type_total);exit;
        return $type_total;
    }
}
