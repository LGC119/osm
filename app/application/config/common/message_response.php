<?php 

/**
+++ 自动回复的一些设置 +++
**/
$config['message_response_link'] = array (
	'type' 		=> 'text',
	'content' 	=> '您发送的链接已送达，我们会尽快回复！'
);

$config['message_response_image'] = array (
	'type' 		=> 'text',
	'content' 	=> '您发送的图片已送达，我们会尽快回复！'
);

$config['message_response_voice'] = array (
	'type' 		=> 'text',
	'content' 	=> '您发送的语音已送达，我们会尽快回复！'
);

$config['message_response_video'] = array (
	'type' 		=> 'text',
	'content' 	=> '您发送的视频已送达，我们会尽快回复！'
);

$config['message_response_location'] = array (
	'type' 		=> 'text',
	'content' 	=> '您发送的位置信息不准确，请重新定位后再发送！'
);

$config['reply_templates'] = array (
	// 文字回复默认格式
	'text' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
		</xml>',
	//地理位置回复格式
	'location' =>'<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>1</ArticleCount>
			<Articles>
			<item>
			<Title><![CDATA[%s]]></Title> 
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[]]></Url>
			</item>
			</Articles>
		</xml>',
	// 图片回复默认格式
	'image' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[image]]></MsgType>
			<Image><MediaId><![CDATA[%s]]></MediaId></Image>
		</xml>',
	// 语音回复默认格式
	'voice' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[voice]]></MsgType>
			<Voice><MediaId><![CDATA[%s]]></MediaId></Voice>
		</xml>',
	// 视频回复默认格式
	'video' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[video]]></MsgType>
			<Video>
				<MediaId><![CDATA[%s]]></MediaId>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
			</Video>
		</xml>',
	// 音乐回复默认格式
	'music' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[music]]></MsgType>
			<Music>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				<MusicUrl><![CDATA[%s]]></MusicUrl>
				<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
				<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
			</Music>
		</xml>',
	// 图文回复默认格式
	'news' => '<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>%s</ArticleCount>
			<Articles>%s</Articles>
		</xml>',
	// 图文回复单条格式
	'news_item' => '<item>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[%s]]></Url>
		</item>'
);