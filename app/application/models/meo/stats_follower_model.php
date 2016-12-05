<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 微博 Communication 模型 (舆情)
*/
class Stats_follower_model extends CI_model
{
	
	public function __construct()
	{
		parent::__construct();
	}

	/* 获取某时段内粉丝的的统计信息 */
	public function get_follower_stats ($wb_aid, $start, $end)
	{
		$raw_stats = $this->db->select('wb_aid, total, commu_total, gender_stat, region_stat, verified_type_stat, sub_followers_stat, date')
			->from('wb_stats_follower')
			->where("`wb_aid`={$wb_aid} AND `date` BETWEEN '{$start}' AND '{$end}'", NULL, FALSE)
			->get()->result_array();

		$followers_stats = array ();

		if ($raw_stats)  /* 将原始数据库数组转化为按时间轴排序的数组 */
		{
			// 最终统计数据 $followers_stats 三维数组
			// $followers_stats = [
			// 	'gender_stat' = [
			// 		'男'   = [2014-01-01=>27, 2014-01-02=>32, 2014-01-03=>36], 
			// 		'女'   = [2014-01-01=>25, 2014-01-02=>30, 2014-01-03=>32], 
			// 		'未知' = [2014-01-01=>25, 2014-01-02=>30, 2014-01-03=>32]
			// 	],
			// 	'region_stat' = [...]
			// ];
			$stat_types = array('gender_stat', 'region_stat', 'verified_type_stat', 'sub_followers_stat');
			foreach ($raw_stats as $val) {
				foreach ($stat_types as $type) {
					$v_data = json_decode($val[$type], true);
					if ($v_data) {
						foreach ($v_data as $v_key => $v_val) {
							if (isset($followers_stats[$type]) && isset($followers_stats[$type][$v_key]))
								$followers_stats[$type][$v_key][$val['date']] = $v_val;
							else if (isset($followers_stats[$type])) 
								$followers_stats[$type][$v_key] = array($val['date']=>$v_val);
							else 
								$followers_stats[$type] = array($v_key=>array($val['date']=>$v_val));
						}
					}
				}
			}
		}

		return $followers_stats;
	}

	/* 记录当天的粉丝统计信息 */
	public function log_follower_stats () 
	{

		/* 检查当天记录是否存在 */
		$record_exsit = $this->db->from('wb_stats_follower')
			->where('date', date('Y-m-d'))
			->get()->num_rows();
		if ($record_exsit)
			return 'Stats Already Logged !';

		$accounts = array ();
		/* 每个账号的粉丝量 & 交流过的粉丝总量 */
		$followers_count = $this->db->select('count(*) AS sum, wb_aid, last_cmn_time AS has_commu')
			->from('wb_account_user wau')
			->where_in('relationship', array(1, 3))
			->group_by('wb_aid, (has_commu = "0000-00-00 00:00:00")')
			->get()->result_array();

		foreach ($followers_count as $val) {
			if ( ! isset($accounts[$val['wb_aid']])) 
				$accounts[$val['wb_aid']] = array ('total' => 0);

			if ($val['has_commu'] != '0000-00-00 00:00:00') 
				$accounts[$val['wb_aid']]['commu_total'] = $val['sum'];

			$accounts[$val['wb_aid']]['total'] += $val['sum'];
		}

		/* 各维度统计数据 */
		$stats['gender_stat']			= $this->_get_gender_stat();
		$stats['region_stat']			= $this->_get_region_stat();
		$stats['verified_type_stat']	= $this->_get_verified_type_stat();
		$stats['sub_followers_stat']	= $this->_get_sub_followers_stat();

		foreach ($stats as $key => $val) {
			foreach ($val as $k => $v) {
				if (isset($accounts[$k])) 
					$accounts[$k][$key] = $v;
			}
		}

		$insert_arr = array ();
		foreach ($accounts as $key => $val) {
			$insert_arr[] = array(
				'wb_aid' => $key, 
				'total' => $val['total'], 
				'commu_total' => isset($val['commu_total']) ? $val['commu_total'] : 0, 
				'gender_stat' => $val['gender_stat'], 
				'region_stat' => $val['region_stat'], 
				'verified_type_stat' => $val['verified_type_stat'], 
				'sub_followers_stat' => $val['sub_followers_stat'], 
				'date' => date('Y-m-d'), 
				'log_time' => date('Y-m-d H:i:s')
			);
		}

		$this->db->insert_batch('wb_stats_follower', $insert_arr);

		return intval($this->db->affected_rows());
	}

	/* 获取所有账号的粉丝性别信息 */
	private function _get_gender_stat () 
	{
		$stats = $this->db->select('count(*) AS num, gender, wau.wb_aid')
			->from('wb_account_user wau')
			->join('wb_user wu', 'wau.wb_user_id = wu.user_weibo_id', 'left')
			->group_by('wau.wb_aid, gender')
			->where_in('wau.relationship', array(1, 3))
			->get()->result_array();

		$gender_stats = array();
		if (is_array($stats) && $stats) 
		{
			$gender_map = $this->config->item('gender_map');
			foreach ($stats as $val) 
			{
				if ( ! array_key_exists($val['gender'], $gender_map)) 
					$val['gender'] = 0;	// 不明确的性别归为未知

				$key_name = $gender_map[$val['gender']];

				if (isset($gender_stats[$val['wb_aid']])) 
					if (isset($gender_stats[$val['wb_aid']][$key_name]))
						$gender_stats[$val['wb_aid']][$key_name] += $val['num'];		// 可能有重复的，比如 0
					else 
						$gender_stats[$val['wb_aid']][$key_name] = $val['num'];
				else 
					$gender_stats[$val['wb_aid']] = array ($key_name => $val['num']);
			}
		}

		foreach ($gender_stats as &$val) 
			$val = json_encode($val, JSON_UNESCAPED_UNICODE);
		return $gender_stats;
	}

	/* 获取所有账号的粉丝地区信息 */
	private function _get_region_stat () 
	{
		$stats = $this->db->select('count(*) AS num, province_code, wau.wb_aid')
			->from('wb_account_user wau')
			->join('wb_user wu', 'wau.wb_user_id = wu.user_weibo_id', 'left')
			->group_by('wau.wb_aid, province_code')
			->where_in('wau.relationship', array(1, 3))
			->get()->result_array();

		$region_stats = array();
		if (is_array($stats) && $stats) 
		{
			$region_map = $this->config->item('region_map');
			foreach ($stats as $val) 
			{
				if ( ! array_key_exists($val['province_code'], $region_map)) 
					$val['province_code'] = 100;	// 不明确的地区归为其他

				$key_name = $region_map[$val['province_code']];

				if (isset($region_stats[$val['wb_aid']])) 
					if (isset($region_stats[$val['wb_aid']][$key_name]))
						$region_stats[$val['wb_aid']][$key_name] += $val['num'];		// 可能有重复的，比如 0
					else 
						$region_stats[$val['wb_aid']][$key_name] = $val['num'];
				else 
					$region_stats[$val['wb_aid']] = array ($key_name => $val['num']);
			}
		}

		foreach ($region_stats as &$val) 
			$val = json_encode($val, JSON_UNESCAPED_UNICODE);
		return $region_stats;
	}

	/* 获取所有账号的粉丝身份信息 */
	private function _get_verified_type_stat () 
	{
		$stats = $this->db->select('count(*) AS num, verified_type, wau.wb_aid')
			->from('wb_account_user wau')
			->join('wb_user wu', 'wau.wb_user_id = wu.user_weibo_id', 'left')
			->group_by('wau.wb_aid, verified_type')
			->where_in('wau.relationship', array(1, 3))
			->get()->result_array();

		$vt_stats = array();
		if (is_array($stats) && $stats) 
		{
			$vt_map = $this->config->item('vt_map');
			foreach ($stats as $val) 
			{
				if ( ! array_key_exists($val['verified_type'], $vt_map)) 
					$val['verified_type'] = -1;	// 不明确的身份归为普通

				$key_name = $vt_map[$val['verified_type']];

				if (isset($vt_stats[$val['wb_aid']])) 
					if (isset($vt_stats[$val['wb_aid']][$key_name]))
						$vt_stats[$val['wb_aid']][$key_name] += $val['num'];		// 可能有重复的，比如 0
					else 
						$vt_stats[$val['wb_aid']][$key_name] = $val['num'];		// 可能有重复的，比如 0
				else 
					$vt_stats[$val['wb_aid']] = array ($key_name => $val['num']);
			}
		}

		foreach ($vt_stats as &$val) 
			$val = json_encode($val, JSON_UNESCAPED_UNICODE);
		return $vt_stats;
	}

	/* 获取所有账号的粉丝粉丝量信息 */
	private function _get_sub_followers_stat () 
	{
		$sub_followers_range = $this->config->item('sub_followers_range');

		$stats = $this->db->select("ELT(interval(wu.followers_count, 0, 100, 500, 1000, 5000, 10000, 100000), '0', '1', '2', '3', '4', '5', '6') as sf, COUNT(*) AS num, wau.wb_aid", FALSE)
			->from('wb_account_user wau')
			->join('wb_user wu', 'wau.wb_user_id = wu.user_weibo_id', 'left')
			->group_by('wau.wb_aid, sf')
			->where_in('wau.relationship', array(1, 3))
			->get()->result_array();

		$sf_stats = array();
		if (is_array($stats) && $stats) 
		{
			$sf_map = $this->config->item('sf_map');
			foreach ($stats as $val) 
			{
				$key_name = $sf_map[$val['sf']];

				if (isset($sf_stats[$val['wb_aid']])) 
					$sf_stats[$val['wb_aid']][$key_name] = $val['num'];
				else 
					$sf_stats[$val['wb_aid']] = array ($key_name => $val['num']);
			}
		}

		foreach ($sf_stats as &$val) 
			$val = json_encode($val, JSON_UNESCAPED_UNICODE);
		return $sf_stats;
	}

}