<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** get_wb_api		获取用户微博API
*/
if ( ! function_exists('get_wb_api'))
{
	function get_wb_api($oa_info)
	{
		$className = ($oa_info['platform'] == 1) ? 'wbapi_sina' : 'wbapi_tx';
		require_once APPPATH . "libraries/{$className}.php";

		$className = ucfirst($className);
		/* 获取API对象，并用wb_aid初始化API对象 */
		$wbapiObj = new $className($oa_info);
		return $wbapiObj;
	}
}

/*
** get_wx_api		获取用户微信API
*/
if ( ! function_exists('get_wx_api'))
{
	function get_wx_api($weixin_id)
	{
		require_once APPPATH . "libraries/Wxapi.php";
		$wxapiObj = new CI_Wxapi();
		return $wxapiObj;
	}
}

/*
** convert_sina_time 转换新浪时间为 DATETIME 格式
*/
if ( ! function_exists('convert_sina_time'))
{
	function convert_sina_time(&$item, $key)
	{
		if ($key == 'created_at')
			$item = date('Y-m-d H:i:s', strtotime($item));
	}
}

/*
** insert_convert 转换接口数据为SQL数据库写入形式，字符串加引号，布尔转为0,1，Float ID转为数字
*/
if ( ! function_exists('insert_convert'))
{
	function insert_convert(&$item, $key)
	{
		if (is_string($item))
		{
			$item = "'" . str_replace("'", "''", remove_invisible_characters($item)) . "'";
		}
		elseif (is_bool($item))
		{
			$item = ($item === FALSE) ? 0 : 1;
		}
		elseif (is_null($item))
		{
			$item = 'NULL';
		}
		elseif (is_float($item)) 
		{
			$item = number_format($item, 0, '', '');
		}
	}
}

/*
** tencent_convert 转换腾讯微博为新浪微博格式，-_-~
*/
if ( ! function_exists('tencent_convert'))
{
	function tencent_convert($status)
	{
		$converted = array (
		);

		return $converted;
	}
}

/**
 * get_sina_wb_info 获取新浪微博的wb_info // 精简压缩的原始数据文件
 */
if ( ! function_exists('get_sina_wb_info')) 
{
	function get_sina_wb_info ($data) 
	{
		if ( ! is_array($data) OR ! $data) return '';
		
		$wb_info = array (
			'id' => $data['id'],
			'mid' => $data['mid'],
			'text' => $data['text'],
			'source' => $data['source'],
			'created_at' => $data['created_at'],
			'reposts_count' => isset($data['reposts_count']) ? $data['reposts_count'] : 0,
			'comments_count' => isset($data['comments_count']) ? $data['comments_count'] : 0,
			'user' => array (
				'id' 				=> $data['user']['id'],
				'screen_name' 		=> $data['user']['screen_name'],
				'location' 			=> $data['user']['location'],
				'description' 		=> $data['user']['description'],
				'profile_image_url' => $data['user']['profile_image_url'],
				'gender' 			=> $data['user']['gender'],
				'followers_count' 	=> $data['user']['followers_count'],
				'friends_count' 	=> $data['user']['friends_count'],
				'statuses_count' 	=> $data['user']['statuses_count'],
				'verified_type' 	=> $data['user']['verified_type']
			)
		);
		/* 包含图片 */
		if (isset($data['original_pic'])) $wb_info['original_pic'] = $data['original_pic'];
		if (isset($data['pic_urls'])) $wb_info['pic_urls'] = $data['pic_urls'];


		/* 转发微博或评论微博的原微博 */
		$status = isset($data['status']) ? 'status' : isset($data['retweeted_status']) ? 'retweeted_status' : '';
		if ($status) 
		{
			$wb_info[$status] = array (
				'created_at' => $data[$status]['created_at'],
				'id' => $data[$status]['id'],
				'mid' => $data[$status]['mid'],
				'text' => $data[$status]['text'],
				'source' => $data[$status]['source'],
				'reposts_count' => isset($data[$status]['reposts_count']) ? $data[$status]['reposts_count'] : 0,
				'comments_count' => isset($data[$status]['comments_count']) ? $data[$status]['comments_count'] : 0,
				'user' => array (
					'id' => $data[$status]['user']['id'],
					'screen_name' => $data[$status]['user']['screen_name']
				),
			);

			/* 包含图片 */
			if (isset($data[$status]['original_pic'])) $wb_info[$status]['original_pic'] = $data[$status]['original_pic'];
			if (isset($data[$status]['pic_urls'])) $wb_info[$status]['pic_urls'] = $data[$status]['pic_urls'];
		}

		/* 评论微博的回复内容 */
		if (isset($data['reply_comment'])) 
		{
			$wb_info['reply_comment'] = array (
				'id' => $wb_info['reply_comment']['id'],
				'mid' => $wb_info['reply_comment']['mid'],
				'text' => $wb_info['reply_comment']['text'],
				'source' => $wb_info['reply_comment']['source'],
				'created_at' => $wb_info['reply_comment']['created_at'],
				'user' => array (
					'id' => $wb_info['reply_comment']['user']['id'],
					'screen_name' => $wb_info['reply_comment']['user']['screen_name']
				)
			);
		}

		$wb_info_string = base64_encode(gzcompress(json_encode($wb_info, JSON_UNESCAPED_UNICODE), 9));
		return $wb_info_string;
	}
}

/*
** vdong_convert 转换微动数据为新浪微博格式，-_-~
*/
if ( ! function_exists('vdong_convert'))
{
	function vdong_convert($data)
	{
		if (isset($data['rp_source_status']) && $data['rp_source_status']['id'] != 0)
		{
			$retweeted_status = array ( 
				"created_at" => '', 
				"id" => number_format($data['rp_source_status']['id'], 0, '', ''), 
				"text" => $data['rp_source_status']['text'], 
				"mid" => number_format($data['rp_source_status']['id'], 0, '', ''), 
				"user" => array( 
					"id" => $data['rp_source_status']['user']['uid'], 
					"screen_name" => $data['rp_source_status']['user']['screen_name'], 
					"name" => $data['rp_source_status']['user']['screen_name'], 
					"province" => $data['rp_source_status']['user']['province'], 
					"city" => $data['rp_source_status']['user']['city'], 
					"location" => isset($data['rp_source_status']['user']['location']) ? $data['rp_source_status']['user']['location'] : '其他', 
					"description" => $data['rp_source_status']['user']['description'] 
				)
			);
		}

		$data = array (
			"created_at" => $data['cdate'], 
			"id" => number_format($data['id'], 0, '', ''), 
			"text" => $data['text'], 
			"source" => $data['source'], 
			"is_top" => $data['is_top'], 
			"mid" => number_format($data['id'], 0, '', ''), 
			"operation_status" => $data['operation_status'], 
			"user" => array( 
				"id" => $data['user']['uid'], 
				"idstr" => $data['user']['uid'], 
				"mid" => $data['user']['uid'], 
				"screen_name" => $data['user']['screen_name'], 
				"name" => $data['user']['screen_name'], 
				"province" => $data['user']['province'], 
				"city" => $data['user']['city'], 
				"location" => $data['user']['location'], 
				"description" => $data['user']['description'], 
				"profile_image_url" => $data['user']['profile_image_url'], 
				"gender" => $data['user']['gender'], 
				"verified" => $data['user']['is_verify'] ? 1 : 0, 
				"verified_type" => $data['user']['verify_type'] 
			)
		);

		// 微薄图片
		if (isset($data['pic']) && ! empty($data['pic'])) {
			$data['original_pic'] = $data['pic'];
			$data['pic_urls'] = array ('thumbnail_pic'=>$data['pic']);
		}

		isset($retweeted_status) && $data['retweeted_status'] = $retweeted_status;

		return $data;
	}
}

/* End of file api_helper.php */
/* Location: ./application/helpers/api_helper.php */
