<?php 

/**
+++ 系统关键词设置参数 +++
**/

/* 关键词类型 */
$config['keyword_types'] = array (
	'monitor' => 0, // 微博监控关键词
	'ignore' => 1, // 自动忽略
	'pintop' => 2  // 自动置顶
);

$config['cmn_types'] = array (
	'keywords', 
	'comments', 
	'mentions', 
	'privmsgs', 
	'wexinmsg'
);

/* 关键词使用范围类型 */
$config['cmn_types_info'] = array(
	'keywords' => array (
		'key' => 'keywords', 
		'text' => '关键词', 
		'val' => 1
	), 
	'comments' => array (
		'key' => 'comments', 
		'text' => '评论', 
		'val' => 2
	), 
	'mentions' => array (
		'key' => 'mentions', 
		'text' => '@我的', 
		'val' => 4
	), 
	'privmsgs' => array (
		'key' => 'privmsgs', 
		'text' => '微博私信', 
		'val' => 8
	), 
	'wexinmsg' => array (
		'key' => 'wexinmsg', 
		'text' => '微信消息', 
		'val' => 16
	)
);

/* 关键词适用范围类型数<需设定二进制位数> */
$config['cmn_types_count'] = count($config['cmn_types']);