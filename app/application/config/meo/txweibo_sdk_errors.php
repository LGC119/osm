<?php 

$config['txweibo_sdk_errors'] = array(
	/* 获取微博报错 */
	'20' => 'error id param - 微博id值超出限制或为0，请检查输入参数是否正确', 
	'30' => 'error id param - 微博id错误，请认真检查请求参数是否正确', 
	'32' => 'forbidden access - 禁止访问，如城市，uin黑名单限制等', 
	'33' => 'node not exist - 微博记录不存在，请检查请求参数是否正确', 
	'39' => 'content is verifying - 源消息审核中', 
	'41' => 'not verify real name - 未实名认证，用户未进行实名认证，请引导用户进行实名认证', 
	'43' => 'tweet has been deleted - 微博已经被删除，请检查微博id参数是否正确', 
	'44' => 'error ids param - 微博id错误，请认真检查微博id是否正确', 
	'45' => 'error id param - 微博id错误，请检查输入参数是否正确', 
	'1001' => 'common uin blacklist limit - 公共uin黑名单限制', 
	'1002' => 'common ip blacklist limit - 公共IP黑名单限制', 
	'1003' => 'weibo blacklist limit - 微博黑名单限制', 
	'1004' => 'access too fast - 单UIN访问微博过快', 
	'1480' => 'get tweet fail - 服务器内部错误导致拉取微博信息失败，请联系我们反馈问题', 
);