<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define("MERET_OK", 200);			#[200成功返回]
define("MERET_EMPTY", 204);			#[204数据为空]
define("MERET_BADREQUEST", 400);	#[400参数错误]
define("MERET_UNAUTHORIZED", 401);	#[401没有权限]
define("MERET_NOTFOUND", 404);		#[404无效请求]
define("MERET_SVRERROR", 500);		#[500内部错误]
define("MERET_TIMEOUT", 504);		#[504请求超时]
define("MERET_APIERROR", 506);		#[506接口出错]
define("MERET_OTHER", 508);			#[508其他错误]
define("MERET_DBERR", 330601);       #[3304数据库错误]

/*
**
** ME_Controller 及其子类返回码信息
** 请务必使用MERet函数返回controller的执行结果
** @ 如需扩展返回错误信息请在message里面添加
** @ 此处只包含最基本的返回结果及代码
** @ 抄袭HTTP返回码，欢迎提意见
**
*/
$config['return_codes']	= array(
	200 => "OK", 					#[200成功返回]
	204 => "No Content", 			#[204数据为空]
	400 => "Bad Request", 			#[400参数错误]
	401 => "Unauthorized", 			#[401没有权限]
	404 => "Not Found", 			#[404无效请求]
	500 => "Internal Server Error", #[500内部错误]
	504 => "Gateway Timeout", 		#[504请求超时]
	506 => "Interface Error", 		#[506接口出错]
    508 => "Unknown Error"
);

/* End of file controller.php */
/* Location: ./application/config/controller.php */