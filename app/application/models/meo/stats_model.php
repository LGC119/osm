<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * User: Xujian
 * Date: 14-7-30
 * Time: 10:47
 */

class Stats_model extends ME_Model
{

    public function __construct(){
        parent::__construct();
        $this->cid = $this->session->userdata('company_id');
        $this->wb_aid = $this->session->userdata('wb_aid');
        // $this ->load ->library('Wxapi');
    }

    public function top_interact_users()
    {
        $where = array(
            // 'au.company_id' => $this->cid,
            'au.wb_aid' => $this->wb_aid
        );
        $rst = $this->db->select('u.screen_name, COUNT(c.id) commu_count', FALSE)
            ->from('wb_account_user au')
            ->join('wb_user u', 'au.user_weibo_id = u.user_weibo_id', 'left')
            ->join('wb_communication c', 'u.user_weibo_id = c.user_weibo_id', 'left')
            ->where($where)
            ->group_by('u.id')
            ->order_by('commu_count', 'desc')
            ->limit(10)
            ->get()->result_array();
        return $rst;
    }

    // 需要在wb_account_user添加gender/verified_type/location/city_code几个字段
    public function get_gender_number($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("wu.gender, count(*) as gender_number")
                ->from("wb_account_user wau")
                ->join("wb_user wu", "wau.user_weibo_id = wu.user_weibo_id", 'left')
                ->where("wau.company_id", $company_id)
                ->where("wau.wb_aid", $wb_aid)
                ->where("wau.created_at >=", $start_date)
                ->where("wau.created_at <", $end_date)
                ->group_by("wu.gender");
        $gender_number = $this->db->get()->result_array();

        return $gender_number;
    }

    public function get_fans_verified_type($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("wu.verified_type, count(*) as fans_type_number")
                ->from("wb_account_user wau")
                ->join("wb_user wu", "wau.user_weibo_id = wu.user_weibo_id", 'left')
                ->where("wau.company_id", $company_id)
                ->where("wau.wb_aid", $wb_aid)
                ->where("wau.created_at >=", $start_date)
                ->where("wau.created_at <", $end_date)
                ->group_by("wu.verified_type");
        $verified_type_number = $this->db->get()->result_array();
        return $verified_type_number;
    }

    public function get_location_distribution($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("wu.province_code AS location, count(*) as location_number")
                ->from("wb_account_user wau")
                ->join("wb_user wu", "wau.user_weibo_id = wu.user_weibo_id", 'left')
                ->where("wau.company_id", $company_id)
                ->where("wau.wb_aid", $wb_aid)
                ->where("wau.created_at >=", $start_date)
                ->where("wau.created_at <", $end_date)
                ->group_by("wu.province_code")
                ->order_by("location_number", "DESC")
                ->limit(5);
        $location_number = $this->db->get()->result_array();
        return $location_number;
    }

    function get_fans_increasement($wb_aid, $start_date, $end_date)
    {
        // 当统计周期大于30天时，只取从结束时间往前推30天的数据
        if ((strtotime($end_date) - strtotime($start_date)) > 2592000)
        {
            $start_date = date("Y-m-d", strtotime($end_date) - 2592000);
        }

        $company_id = $this->cid;
        $this->db
                ->select("DATE_FORMAT(`created_at`, '%Y-%m-%d') as day, count(*) as day_total", FALSE)
                ->from("wb_account_user")
                ->where("company_id", $company_id)
                ->where("wb_aid", $wb_aid)
                ->where("created_at >=", $start_date)
                ->where("created_at <", $end_date)
                ->group_by("day");
        $increasement_rs = $this->db->get()->result_array();

        // 如果第一个元素不等于查询开始的时间，说明开始时间的值为0
        // 设置开始的时间为0，便于遍历数组，插入没有的日期
        if (empty($increasement_rs) || $start_date != $increasement_rs[0]['day'])
        {
            array_unshift($increasement_rs, array('day' => $start_date, 'day_total' => 0));
        }

        // 时间查出来可能是不连续的
        // 当某天没有值的情况下，设置那天为0
        foreach ($increasement_rs as $key => $day_data)
        {
            if (0 == $key) continue;
            $last_key = $key - 1;

            $day_stamp = strtotime($day_data['day']);
            $last_stamp = strtotime($increasement_rs[$last_key]['day']);

            if (($diff_seconds = $day_stamp - $last_stamp) > 86400)
            {
                // 去掉开始的那天，以免重复
                $unshfit_day = $day_stamp - $diff_seconds + 86400;
                for ($i = $unshfit_day; $i < $day_stamp; $i += 86400)
                {
                    array_push($increasement_rs, array('day' => date("Y-m-d", $i), 'day_total' => 0));
                }

            }
        }
        usort($increasement_rs, array('Stats_model', 'cmp'));

        return $increasement_rs;
    }

    public function get_category_info_number($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("rwcc.cat_id, count(*) as category_number")
                ->from("wb_communication wc")
                ->join("rl_wb_communication_category rwcc", "wc.id = rwcc.cmn_id")
                ->where("wc.company_id", $company_id)
                ->where("wc.wb_aid", $wb_aid)
                ->where("wc.created_at >=", $start_date)
                ->where("wc.created_at <", $end_date)
                ->group_by("rwcc.cat_id");
        $category_info_number = $this->db->get()->result_array();

	    if (empty($category_info_number)) return $category_info_number;

	    $cats_numbers = array();
	    foreach ($category_info_number as $val)
		    $cats_numbers[$val['cat_id']] = $val['category_number'];

	    /* 获取全部的分类信息 */
	    $this->load->model('common/category_model', 'category');
	    $category_info = $this->category->get_quick_cats($this->cid);

	    foreach ($category_info['category'] as &$category)
	    {
		    if (isset($cats_numbers[$category['id']])) $category['category_number'] = $cats_numbers[$category['id']];
		    else $category['category_number'] = 0;
	    }

        return $category_info;
    }

    public function get_interact_number($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("DATE_FORMAT(`sent_at`, '%H') as hour, type, count(*) as type_number", FALSE)
                ->from("wb_communication")
                ->where("company_id", $company_id)
                ->where("wb_aid", $wb_aid)
                ->where("sent_at >=", $start_date)
                ->where("sent_at <", $end_date)
                ->where("type !=", 2)
                ->group_by("type")
                ->group_by("hour");
        $rs = $this->db->get()->result_array();

        // 数组初始化
        // 避免在遍历的时候判断没值的情况下再初始化
        $type_interact_number = array(0 => 0, 1 => 0, 3 => 0);
        $hour_type_interact_number = array(0 => array(), 1 => array(), 3 => array());

        // $hour_type_interact_number = array();
        foreach ($rs as $hour)
        {

            $type_interact_number[$hour['type']] += $hour['type_number'];

            if (! isset($hour_type_interact_number[$hour['type']][$hour['hour']]))
            {
                $hour_type_interact_number[$hour['type']][$hour['hour']] = 0;
            }
            $hour_type_interact_number[$hour['type']][$hour['hour']] += $hour['type_number'];
        }

        // 遍历每小时交互量，没有那个小时的代表是0，补上
        foreach ($hour_type_interact_number as $type => $data)
        {
            for ($i = 0; $i <= 23; $i++)
            {
                if (10 > $i) $i = 0 . $i;
                if (! isset($data[$i]))
                {
                    $hour_type_interact_number[$type][$i] = 0;
                }
            }
            ksort($hour_type_interact_number[$type]);
            $hour_type_interact_number[$type] = array_values($hour_type_interact_number[$type]);
        }

        $interact_data['type_interact_number'] = $type_interact_number;
        $interact_data['hour_type_interact_number'] = $hour_type_interact_number;
        return $interact_data;
    }

    public function get_keyword_rank($start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("keyword_id, count(*) as keyword_number")
                ->from("wb_communication")
                ->where("type", 2)
                ->where("company_id", $company_id)
                ->where("sent_at >=", $start_date)
                ->where("sent_at <", $end_date)
                ->group_by("keyword_id")
                ->order_by("keyword_number", "DESC")
                ->limit("10");
        $keyword_rank = $this->db->get()->result_array();

	    if ( ! $keyword_rank) return FALSE;

        $keyword_ids = array();
        foreach ($keyword_rank as $keyword_data)
        {
            $keyword_ids[] = $keyword_data['keyword_id'];
        }

        $this->db
                ->select("id, text")
                ->from("wb_keyword")
                ->where("company_id", $company_id)
                ->where_in("id", $keyword_ids);
        $keyword_text_rs = $this->db->get()->result_array();
        $keyword_texts = array();
        foreach ($keyword_text_rs as $keyword_text_rs_data)
        {
            $keyword_texts[$keyword_text_rs_data['id']] = $keyword_text_rs_data['text'];
        }

        foreach ($keyword_rank as $key => $data)
        {
            $keyword_rank[$key]['keyword_text'] = $keyword_texts[$data['keyword_id']];
        }

        return $keyword_rank;
    }

    public function get_rule_number($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("rule_id, count(*) as rule_number")
                ->from("wb_communication")
                ->where("company_id", $company_id)
                ->where("wb_aid", $wb_aid)
                ->where("sent_at >=", $start_date)
                ->where("sent_at <", $end_date)
                ->where("type", 3)
                ->where("rule_id !=", 0)
                ->group_by("rule_id");
        $rule_number = $this->db->get()->result_array();

        if (empty($rule_number)) return $rule_number;

        $rule_ids = array();
        foreach ($rule_number as $rule_data)
        {
            $rule_ids[] = $rule_data['rule_id'];
        }

        $this->db
                ->select("id, name")
                ->from("wb_msg_rule")
                ->where("company_id", $company_id)
                ->where_in("id", $rule_ids);
        $rule_name_rs = $this->db->get()->result_array();
        $rule_names = array();
        foreach ($rule_name_rs as $rule_name_data)
        {
            $rule_names[$rule_name_data['id']] = $rule_name_data['name'];
        }

        foreach ($rule_number as $key => $rule_data)
        {
            if (isset($rule_names[$rule_data['rule_id']]))
            {
                $rule_number[$key]['rule_name'] = $rule_names[$rule_data['rule_id']];
            }
            else
            {
                unset($rule_number[$key]);
            }
        }

        return $rule_number;
    }

    public function get_pm_keyword_number($wb_aid, $start_date, $end_date)
    {
        $company_id = $this->cid;
        $this->db
                ->select("keyword_id, count(*) as keyword_number")
                ->from("wb_communication")
                ->where("company_id", $company_id)
                ->where("wb_aid", $wb_aid)
                ->where("sent_at >=", $start_date)
                ->where("sent_at <", $end_date)
                ->where("type", 3)
                ->where("rule_id !=", 0)
                ->group_by("keyword_id")
                ->order_by("keyword_number", "DESC")
                ->limit(10);
        $pm_keyword_number = $this->db->get()->result_array();

        if (empty($pm_keyword_number)) return $pm_keyword_number;

        $pm_keyword_ids = array();
        foreach ($pm_keyword_number as $key => $pm_data)
        {
            $pm_keyword_ids[] = $pm_data['keyword_id'];
        }

        $this->db
                ->select("id, name")
                ->from("wb_msg_keyword")
                ->where_in("id", $pm_keyword_ids);
        $pm_keyword_name_rs = $this->db->get()->result_array();
        $pm_keyword_names = array();
        foreach ($pm_keyword_name_rs as $pm_keyword_name_data)
        {
            $pm_keyword_names[$pm_keyword_name_data['id']] = $pm_keyword_name_data['name'];
        }

        foreach ($pm_keyword_number as $key => $pm_keyword_data)
        {
            if (isset($pm_keyword_names[$pm_keyword_data['keyword_id']]))
            {
                $pm_keyword_number[$key]['keyword_name'] = $pm_keyword_names[$pm_keyword_data['keyword_id']];
            }
            else
            {
                unset($pm_keyword_number[$key]);
            }
        }

        return $pm_keyword_number;

    }

    public function get_staff_info($start_date, $end_date)
    {
        $company_id = $this->cid;

        $this->db
                ->select("staff_id, operation, count(*) as operation_number")
                ->from("wb_operation_history")
                ->where("company_id", $company_id)
                ->where("created_at >=", $start_date)
                ->where("created_at <", $end_date)
                ->where_in("operation", array(0, 3, 7, 9))
                ->group_by("staff_id")
                ->group_by("operation");
        $staff_rs = $this->db->get()->result_array();

        if (empty($staff_rs)) return $staff_rs;

        $staff_ids = array();
        $staff_info = array();
        foreach ($staff_rs as $staff_data)
        {
            $staff_ids[] = $staff_data['staff_id'];
            $staff_info[$staff_data['staff_id']][$staff_data['operation']] = $staff_data['operation_number'];
        }

        $this->db
                ->select("id, name")
                ->from("staff")
                ->where_in("id", $staff_ids);
        $staff_name_rs = $this->db->get()->result_array();
        $staff_names = array();
        foreach ($staff_name_rs as $staff_name_data)
        {
            $staff_names[$staff_name_data['id']] = $staff_name_data['name'];
        }

        foreach ($staff_info as $key => $staff_data)
        {
            if (isset($staff_names[$key]))
                $staff_info[$key]['staff_name'] = $staff_names[$key];
            else
                unset($staff_info[$key]);
        }

        return $staff_info;
    }

    public function get_tag_info()
    {
        $company_id = $this->cid;
        $this->db
                ->select("tag_id, SUM(link_tag_hits + rule_tag_hits + manual_tag_hits + event_tag_hits + timeline_tag_hits) as tag_number")
                ->from("rl_wb_user_tag")
                ->where("company_id", $company_id)
                ->group_by("tag_id")
                ->order_by("tag_number", "DESC")
                ->limit(15);
        $tag_info = $this->db->get()->result_array();

	    if ( ! $tag_info) return FALSE;

        $tag_ids = array();
        foreach ($tag_info as $tag_info_data)
        {
            $tag_ids[] = $tag_info_data['tag_id'];
        }

        $this->db
                ->select("id, tag_name")
                ->from("tag")
                ->where_in("id", $tag_ids);
        $tag_name_rs = $this->db->get()->result_array();
        $tag_names = array();
        foreach ($tag_name_rs as $tag_name_rs_data)
        {
            $tag_names[$tag_name_rs_data['id']] = $tag_name_rs_data['tag_name'];
        }

        foreach ($tag_info as $key => $tag_info_data)
        {
            if (isset($tag_names[$tag_info_data['tag_id']]))
                $tag_info[$key]['tag_name'] = $tag_names[$tag_info_data['tag_id']];
        }

        return $tag_info;
    }

    // 将数组结果根据日期排序
    public function cmp($a, $b)
    {
        if (strtotime($a['day']) == strtotime($b['day']))
            return 0;

        return (strtotime($a['day']) < strtotime($b['day'])) ? -1 : 1;
    }
}
