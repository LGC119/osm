SET NAMES utf8;
DROP DATABASE IF EXISTS `masengine`;
CREATE DATABASE IF NOT EXISTS `masengine`;
USE `masengine`;


DROP TABLE IF EXISTS `me_admin`;
CREATE TABLE IF NOT EXISTS `me_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '管理员姓名',
  `login_name` varchar(40) NOT NULL DEFAULT '' COMMENT '登录名',
  `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(20) NOT NULL DEFAULT '' COMMENT 'salt值',
  `tel` char(20) NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '账号创建时间',
  `last_login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后登录时间',
  `last_login_ip` varchar(20) NOT NULL DEFAULT '0.0.0.0' COMMENT '最后登录IP',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登陆总次数统计',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理后台的管理员表';


DROP TABLE IF EXISTS `me_application`;
CREATE TABLE IF NOT EXISTS `me_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT '0' COMMENT '公司id',
  `creator` varchar(50) DEFAULT '' COMMENT '应用添加者',
  `appcreator` varchar(50) DEFAULT '' COMMENT '应用创建者  微博昵称',
  `name` varchar(50) DEFAULT '0' COMMENT '应用名称',
  `appkey` varchar(200) DEFAULT '' COMMENT '应用key',
  `appskey` varchar(200) DEFAULT '' COMMENT '应用 secret key',
  `callbackurl` varchar(200) DEFAULT '' COMMENT '应用回调地址',
  `level` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '应用级别  普通授权0 中级授权1 高级授权2 合作授权3 测试授权4',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `platform` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '微博类别 1 新浪 2腾讯',
  `is_delete` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0 是未删除 1是删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='应用表，微博绑定时需要';


DROP TABLE IF EXISTS `me_category`;
CREATE TABLE `me_category` (
  `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '公司id',
  `aid` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '微博或微信的account_id',
  `cat_name` VARCHAR(20) NOT NULL COMMENT '标签名',
  `parent_id` INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类的父id',
  `wb_threshold` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '微博警戒值',
  `wx_threshold` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '微信警戒值',
  `created_at` DATETIME NOT NULL COMMENT '添加时间',
  `add_staff_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '添加员工id',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `tag_name` (`cat_name`)
) COMMENT='舆情信息分类表' COLLATE='utf8_general_ci' ENGINE=MyISAM;


DROP TABLE IF EXISTS `me_company`;
CREATE TABLE IF NOT EXISTS `me_company` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '公司名称',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `secret_token` varchar(50) NOT NULL DEFAULT '' COMMENT 'mei传输token',
  `show_intro` int(11) NOT NULL DEFAULT '1' COMMENT '是否显示首页介绍',
  `is_available` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否可用',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `modal_order` varchar(200) NOT NULL DEFAULT '1,2,3,4,5,6,7' COMMENT '首页modal显示数量 和顺序 控制 7个',
  `allow_wb_account_num` mediumint(11) unsigned NOT NULL DEFAULT '0' COMMENT '可绑定的微博数量',
  `allow_wx_account_num` mediumint(7) unsigned NOT NULL DEFAULT '0' COMMENT '可绑定的微信数量',
  `allow_users_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '可储存的用户数量',
  `allow_wb_keywords_set_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '可设置的关键词数量',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='公司信息';


DROP TABLE IF EXISTS `me_event`;
CREATE TABLE IF NOT EXISTS `me_event` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `aid` varchar(50) NOT NULL DEFAULT '' COMMENT '微博或微信账号ID',
  `event_title` varchar(500) NOT NULL COMMENT '活动名称',
  `detail` text NOT NULL COMMENT '活动详情',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动创建时间',
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  `end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '活动状态：{0:未开始, 1:已发布, 2:终止}',
  `push_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推送状态：{0:未完成, 1:推送完成}',
  `from` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '活动来源：微博0，微信1，双微2',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建员工id',
  `type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '活动类型（需有对照代码列表）',
  `industry` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '活动行业（需有对照代码列表）',
  `h5page_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用的h5页面id',
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '{0:未删除, 1:已删除}',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动信息主表';


DROP TABLE IF EXISTS `me_event_participant`;
CREATE TABLE IF NOT EXISTS `me_event_participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '公司id',
  `event_id` int(11) NOT NULL DEFAULT '0' COMMENT '活动id',
  `participated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '参与时间',
  `wb_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '参与者的wb_user_id（如果参与的是微博，微博账号ID）',
  `wx_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '参与者的wx_user_id（如果参与的是微信，微信账号OpenID）',
  `screen_name` varchar(50) NOT NULL DEFAULT '0' COMMENT '参与者微博或微信名称',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '参与者所在组ID',
  `if_pushed` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否推送 {-1:非推送, 0:未推送, 1:已推送, 2:推送失败:暂无}',
  `result` int(11) NOT NULL DEFAULT '0' COMMENT '中奖信息：默认0未中奖',
  `weibo_id` varchar(50) NOT NULL DEFAULT '' COMMENT '参与者转发微博id',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '所在城市',
  `tel` varchar(50) NOT NULL DEFAULT '0' COMMENT '联系电话',
  `addr` varchar(500) NOT NULL DEFAULT '' COMMENT '详细住址',
  `email` varchar(100) NOT NULL DEFAULT '',
  `is_o2o_member` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否线下会员',
  `customercard` varchar(50) NOT NULL DEFAULT '' COMMENT '线下会员卡号',
  `customerID` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id_wb_user_id_wx_user_id` (`event_id`,`wb_user_id`,`wx_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='参与者信息';


DROP TABLE IF EXISTS `me_event_wb_info`;
CREATE TABLE IF NOT EXISTS `me_event_wb_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `event_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动主id',
  `status_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '活动微博ID',
  `rule` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '活动规则（转发并@多少人）',
  `account_id` varchar(500) NOT NULL DEFAULT '' COMMENT '发出活动微博的微博账号id（推送账号id）',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '活动微博内容',
  `pic_url` varchar(500) NOT NULL DEFAULT '' COMMENT '发微博外链图片地址（如果为上传该项为空）',
  `pic_name` varchar(50) NOT NULL DEFAULT '' COMMENT '发微博上传图片地址（如果为外链该项为空）',
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  `push_mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '推送方式1：评论中@推送  2：直接推送',
  `needed_weibo_counts` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推送完毕需要发送的微博数量',
  `push_interval` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '推送间隔（分钟）',
  `push_each` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '每次推送的人数 (0代表不限制)',
  `push_time_range` varchar(50) NOT NULL DEFAULT '0' COMMENT '推送时段（0代表0点到1点，1代表1点到2点，以此类推，多时间段之间用半角逗号隔开）',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='微博活动信息主表';


DROP TABLE IF EXISTS `me_event_wx_info`;
CREATE TABLE IF NOT EXISTS `me_event_wx_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `event_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动主id',
  `rule_id` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '活动规则',
  `send_id` int(11) NOT NULL DEFAULT '0' COMMENT '群发id',
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '推送时间',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='微信活动信息副表';


DROP TABLE IF EXISTS `me_h5_event`;
CREATE TABLE IF NOT EXISTS `me_h5_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动创建时间',
  `name` char(50) DEFAULT NULL COMMENT '活动名称',
  `status` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动状态，0禁用，1启用，2过期',
  `html_code` text NOT NULL COMMENT '活动网页代码',
  `template_type` varchar(50) DEFAULT NULL COMMENT '模板类型',
  `b_id` int(10) unsigned NOT NULL DEFAULT '0',
  `c_id` int(10) unsigned NOT NULL DEFAULT '0',
  `clickurl` text COMMENT '广告点击次数',
  `search` text COMMENT '用来关键字检索',
  PRIMARY KEY (`id`),
  KEY `template` (`template_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动表';


DROP TABLE IF EXISTS `me_h5_locationdata`;
CREATE TABLE IF NOT EXISTS `me_h5_locationdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_aid` int(11) DEFAULT NULL,
  `province` char(50) NOT NULL COMMENT '省',
  `city` char(50) NOT NULL COMMENT '市',
  `district` char(50) DEFAULT NULL COMMENT '区',
  `street` char(50) DEFAULT NULL COMMENT '街道',
  `street_number` char(50) DEFAULT NULL COMMENT '号',
  `display_name` char(100) DEFAULT NULL COMMENT '显示名称',
  `display_tel` char(50) DEFAULT NULL COMMENT '联系电话',
  `display_address` text COMMENT '地址',
  `display_other` text COMMENT '其他信息',
  `longitude_latitude` varchar(50) DEFAULT NULL COMMENT '坐标位置',
  `display` tinyint(4) DEFAULT '1' COMMENT '是否启用',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='基于微信的门店位置列表';


DROP TABLE IF EXISTS `me_h5_page`;
CREATE TABLE IF NOT EXISTS `me_h5_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(50) DEFAULT '' COMMENT '页面标题',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '页面创建时间',
  `html_code` text NOT NULL COMMENT '页面html内容',
  `template` varchar(20) NOT NULL DEFAULT '' COMMENT '模板类型',
  `ad_click_count` text COMMENT '广告点击次数',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `clickurl` text COMMENT '广告点击次数',
  PRIMARY KEY (`id`),
  KEY `template` (`template`),
  KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='h5页面表';


DROP TABLE IF EXISTS `me_h5_participants`;
CREATE TABLE IF NOT EXISTS `me_h5_participants` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `info` text NOT NULL COMMENT '用户提交的信息',
  `time` int(10) unsigned NOT NULL,
  `page_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'H5页面id',
  `event_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动id',
  `readurl` int(11) DEFAULT '0' COMMENT '是否点击广告',
  `c_id` int(11) DEFAULT '1' COMMENT '参与者id',
  `b_id` int(11) DEFAULT NULL COMMENT 'b用户id',
  `uid` char(50) DEFAULT NULL COMMENT '参与者weibo id',
  `openid` char(50) DEFAULT NULL COMMENT '参与者微信openid',
  `display_name` char(50) DEFAULT NULL COMMENT '显示名称',
  `sex` char(50) DEFAULT '0' COMMENT 'm男,f女,0未知',
  `avartar` varchar(100) DEFAULT '' COMMENT '头像地址',
  `province` char(20) DEFAULT '11' COMMENT '省',
  `city` char(20) DEFAULT '5' COMMENT '市',
  `otherinfo` text COMMENT 'json格式存储',
  PRIMARY KEY (`id`),
  KEY `activity` (`page_id`),
  KEY `openid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='参与者';



DROP TABLE IF EXISTS `me_h5_surl`;
CREATE TABLE IF NOT EXISTS `me_h5_surl` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_url` text COMMENT '原始链接',
  `hashurl` varchar(50) NOT NULL DEFAULT '' COMMENT '哈希码，MD5',
  `surl` varchar(50) NOT NULL DEFAULT '' COMMENT '短网址码',
  PRIMARY KEY (`id`),
  UNIQUE KEY `surl` (`surl`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短网址';



DROP TABLE IF EXISTS `me_h5_template`;
CREATE TABLE IF NOT EXISTS `me_h5_template` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '名称',
  `filename` varchar(50) NOT NULL COMMENT '模板文件名',
  `tplname` varchar(50) NOT NULL COMMENT '模板识别名',
  `path` varchar(50) DEFAULT NULL COMMENT '所在路径',
  `type` int(11) NOT NULL COMMENT '模板类型，关联_h5_template_type',
  `array` text NOT NULL COMMENT '解码数组',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tplname` (`tplname`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模板';



DROP TABLE IF EXISTS `me_h5_template_type`;
CREATE TABLE IF NOT EXISTS `me_h5_template_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` char(50) DEFAULT NULL COMMENT '类型名称',
  `diy` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模板类型表';



DROP TABLE IF EXISTS `me_link`;
CREATE TABLE IF NOT EXISTS `me_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `company_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `aid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '微博，微信的账号ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户的 wb_user_id/wx_user_id',
  `cmn_id` int(11) NOT NULL DEFAULT '0' COMMENT '回复的信息在wb_communication|wx_communication表中的ID [菜单点击事件为0]',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用的图文的ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接URL',
  `short_url` varchar(255) NOT NULL DEFAULT '' COMMENT '短链URL',
  `type` enum('wb','wx') NOT NULL DEFAULT 'wx' COMMENT '用户类型<微博，微信>',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '链接创建时间',
  `hits` mediumint(9) NOT NULL DEFAULT '0' COMMENT '用户的点击量',
  PRIMARY KEY (`id`),
  UNIQUE KEY `aid_user_id_cmn_id_media_id` (`aid`,`user_id`,`cmn_id`,`media_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户对图文链接的点击记录';



DROP TABLE IF EXISTS `me_link_hit_log`;
CREATE TABLE IF NOT EXISTS `me_link_hit_log` (
  `link_id` int(11) NOT NULL COMMENT '链接在link表中的ID',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '链接的点击时间',
  KEY `link_id` (`link_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='链接点击纪录表';



DROP TABLE IF EXISTS `me_log`;
CREATE TABLE IF NOT EXISTS `me_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '员工id',
  `wb_id` varchar(30) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '微博账号的id',
  `wx_id` varchar(30) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '微信账号的id',
  `directory` varchar(200) NOT NULL DEFAULT '',
  `class` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '调用的类',
  `method` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '调用的方法',
  `status` text NOT NULL COMMENT '操作的执行结果 1成功 0失败',
  `ip` varchar(15) NOT NULL DEFAULT '0' COMMENT '操作的ip地址',
  `time` datetime NOT NULL COMMENT '操作时间',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='新日志表，记录系统操作日志';



DROP TABLE IF EXISTS `me_media`;
CREATE TABLE IF NOT EXISTS `me_media` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT '公司id',
  `aid` varchar(20) NOT NULL DEFAULT '' COMMENT '帐号id',
  `staff_id` int(11) NOT NULL COMMENT '员工id',
  `created_at` datetime NOT NULL COMMENT '创建时间（入库时间）',
  `filename` varchar(50) NOT NULL DEFAULT '' COMMENT '素材文件名',
  `wx_media_id` varchar(100) NOT NULL DEFAULT '' COMMENT '素材在微信服务器的media_id',
  `wb_media_id` varchar(100) NOT NULL DEFAULT '' COMMENT '素材在微博服务器上media_id',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `type` varchar(10) NOT NULL DEFAULT '' COMMENT '素材类型',
  `articles` varchar(30) NOT NULL DEFAULT '' COMMENT '多图文的子图文ID，以,号隔开',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='素材表';



DROP TABLE IF EXISTS `me_media_data`;
CREATE TABLE IF NOT EXISTS `me_media_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mid` int(11) DEFAULT NULL COMMENT 'meida表主键id',
  `title` varchar(64) DEFAULT NULL COMMENT '标题',
  `author` varchar(50) DEFAULT NULL COMMENT '作者',
  `description` varchar(120) DEFAULT NULL COMMENT '描述',
  `content` text COMMENT '图文内容/文字回复内容',
  `large_pic` varchar(50) DEFAULT NULL COMMENT '头图（大）文件名',
  `small_pic` varchar(50) DEFAULT NULL COMMENT '头图（小）文件名',
  `thumb_media_id` varchar(100) DEFAULT NULL COMMENT '缩略图',
  `h5_id` int(11) DEFAULT NULL COMMENT '图文h5页面id',
  `content_source_url` varchar(255) DEFAULT NULL COMMENT '原文链接',
  `digest` varchar(255) DEFAULT NULL COMMENT '图文消息的描述',
  `show_cover_pic` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否显示封面，1为显示 0为不显示',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='素材表副表';



DROP TABLE IF EXISTS `me_mei_push_record`;
CREATE TABLE IF NOT EXISTS `me_mei_push_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT '0' COMMENT '公司id',
  `tactics_id` int(11) DEFAULT '0' COMMENT '策略id (暂用)',
  `activity_id` int(11) DEFAULT '0' COMMENT '活动id',
  `user_id` varchar(50) DEFAULT '' COMMENT '微博用户的微博uid',
  `group_id` int(11) DEFAULT '0' COMMENT '用户组id ',
  `status` tinyint(4) DEFAULT '0' COMMENT '是否推送了 0 否1 是',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='推送活动表';



DROP TABLE IF EXISTS `me_mei_tactics`;
CREATE TABLE IF NOT EXISTS `me_mei_tactics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL COMMENT '公司id',
  `policy_name` varchar(30) DEFAULT NULL COMMENT '策略名称',
  `organizer` varchar(32) DEFAULT NULL COMMENT '组织者',
  `policy_description` varchar(100) DEFAULT NULL COMMENT '策略描述',
  `responsible` varchar(32) DEFAULT NULL,
  `create_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `start_time` date DEFAULT '0000-00-00' COMMENT '推送开始时间',
  `end_time` date DEFAULT '0000-00-00' COMMENT '推送结束时间',
  `partner` varchar(50) DEFAULT NULL COMMENT '合作商',
  `target_group` varchar(255) DEFAULT NULL COMMENT '目标群体',
  `push_num` tinyint(4) DEFAULT NULL,
  `push_plan` tinyint(4) DEFAULT NULL,
  `filter_time` char(100) DEFAULT NULL,
  `interval` int(11) DEFAULT NULL,
  `activities` varchar(255) DEFAULT NULL,
  `last_push_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '最后更新时间',
  `push_mode` tinyint(4) DEFAULT NULL COMMENT '推送方式1：评论中@推送  2：直接推送',
  `push_weibo_id` varchar(200) DEFAULT '' COMMENT '推送账号id',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `send_weibo` varchar(200) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='策略表';



DROP TABLE IF EXISTS `me_mei_users_analysis`;
CREATE TABLE IF NOT EXISTS `me_mei_users_analysis` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_weibo_id` varchar(30) NOT NULL DEFAULT '' COMMENT '微博账号',
  `analysis_action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '标记是否需要计算 1 需要处理 0不处理',
  `allot` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '弃用',
  `from_company` varchar(50) DEFAULT NULL COMMENT '所在公司',
  `from_position` varchar(50) DEFAULT NULL COMMENT '职位',
  `from_school` varchar(50) DEFAULT NULL COMMENT '学校',
  `from_client` varchar(50) NOT NULL DEFAULT '' COMMENT '客户端',
  `attentions` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关注数',
  `repaste` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '转帖',
  `original_invitation` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '原创',
  `add_favorites_count` int(11) DEFAULT '0' COMMENT '收藏数变化绝对值',
  `add_attentions` int(11) DEFAULT '0' COMMENT '关注数变化绝对值',
  `attitudes_count` int(11) unsigned DEFAULT NULL COMMENT '被表态数(赞)',
  `status_id` int(11) DEFAULT NULL,
  `be_comments` int(11) unsigned DEFAULT '0' COMMENT '微博被评论数',
  `be_repost` int(11) unsigned DEFAULT NULL COMMENT '微博被转发数',
  `last_update_time` int(10) unsigned DEFAULT NULL COMMENT '最后更新时间',
  `edulevel` char(10) DEFAULT NULL COMMENT '教育水平',
  `comment_transmit` int(11) unsigned DEFAULT NULL COMMENT '转发并说话数',
  `repaste_6` int(11) DEFAULT NULL COMMENT '第二时间点的转发数',
  `original_invitation_6` int(11) DEFAULT NULL COMMENT '第二时间点的原创数',
  `comment_transmit_6` int(11) DEFAULT NULL COMMENT '第二时间点的转发并说话数',
  `favorites_count` int(11) unsigned DEFAULT NULL COMMENT '收藏数',
  `follower_increment` int(11) DEFAULT NULL COMMENT '粉丝数增量',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_weibo_id` (`user_weibo_id`),
  KEY `analysis_action` (`analysis_action`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户分析数据表，计算cim用';



DROP TABLE IF EXISTS `me_menu`;
CREATE TABLE IF NOT EXISTS `me_menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单名',
  `pid` int(10) unsigned DEFAULT '0' COMMENT '父id',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标class',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单url',
  `order` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单顺序',
  `help` varchar(500) DEFAULT '' COMMENT '菜单帮助信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='菜单项';



DROP TABLE IF EXISTS `me_msg_user_info`;
CREATE TABLE IF NOT EXISTS `me_msg_user_info` (
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户user表id',
  `user_weibo_id` varchar(50) NOT NULL,
  `fakeid` varchar(50) NOT NULL DEFAULT '',
  `uid` varchar(50) NOT NULL DEFAULT '',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `cardid` varchar(50) NOT NULL DEFAULT '' COMMENT '会员卡号',
  `relate_code` varchar(50) NOT NULL,
  KEY `uid` (`user_id`),
  KEY `openid` (`user_weibo_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户绑定信息（存了双V关联的对应关系）';



DROP TABLE IF EXISTS `me_operation_category`;
CREATE TABLE IF NOT EXISTS `me_operation_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wb_aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定微博账号ID',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `cat_name` varchar(50) NOT NULL COMMENT '标签名',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类的父id',
  `created_at` datetime DEFAULT NULL COMMENT '添加时间',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加员工id',
  `is_display` tinyint(1) DEFAULT '1' COMMENT '标签是否在分类微博是否显示',
  `is_deleted` tinyint(1) DEFAULT '1' COMMENT '是否被删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='操作分类表';



DROP TABLE IF EXISTS `me_permission`;
CREATE TABLE IF NOT EXISTS `me_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作/功能代码函数名',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '操作/功能名称',
  `module` varchar(20) NOT NULL DEFAULT '' COMMENT '功能模块',
  `controller` varchar(20) NOT NULL DEFAULT '' COMMENT '功能控制器',
  `method` varchar(20) NOT NULL DEFAULT '' COMMENT '功能方法',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父id，如果为0则为菜单（暂定）',
  `level` int(11) NOT NULL DEFAULT '0' COMMENT '表示所属层次，菜单=〉1，模块=〉2，操作=〉3',
  `menu_id` int(10) DEFAULT '0' COMMENT '菜单id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='权限表';



DROP TABLE IF EXISTS `me_position`;
CREATE TABLE IF NOT EXISTS `me_position` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '职位名称',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `created_staff` int(11) NOT NULL DEFAULT '0' COMMENT '创建员工id',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='职位表';



DROP TABLE IF EXISTS `me_quick_reply`;
CREATE TABLE IF NOT EXISTS `me_quick_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `cat_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '主分类id',
  `question` varchar(500) NOT NULL DEFAULT '' COMMENT '智库问题',
  `answer` varchar(2000) NOT NULL DEFAULT '' COMMENT '问题的回答',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='智库(快速回复)';



DROP TABLE IF EXISTS `me_rl_company_menu`;
CREATE TABLE IF NOT EXISTS `me_rl_company_menu` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT '公司id',
  `menu_id` int(10) NOT NULL DEFAULT '0' COMMENT '菜单id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id_menu_id` (`company_id`,`menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='公司-菜单 关联表';



DROP TABLE IF EXISTS `me_rl_company_permission`;
CREATE TABLE IF NOT EXISTS `me_rl_company_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT '0' COMMENT '公司id',
  `permission_id` int(11) DEFAULT '0' COMMENT '权限id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id_permission_id` (`company_id`,`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='公司拥有的权限关联表';



DROP TABLE IF EXISTS `me_rl_event_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_event_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动id',
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签id',
  PRIMARY KEY (`id`),
  KEY `activity` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动 与 标签 关联';



DROP TABLE IF EXISTS `me_rl_event_wb_group`;
CREATE TABLE IF NOT EXISTS `me_rl_event_wb_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微博组id',
  `event_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动主id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动与微博用户组关联表';



DROP TABLE IF EXISTS `me_rl_event_wx_group`;
CREATE TABLE IF NOT EXISTS `me_rl_event_wx_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微信组id',
  `event_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动主id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动与微信用户组关联表';



DROP TABLE IF EXISTS `me_rl_h5_page_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_h5_page_tag` (
  `id` int(11) unsigned DEFAULT '0',
  `h5_page_id` int(11) unsigned DEFAULT '0',
  `tag_id` int(11) unsigned DEFAULT '0',
  KEY `activity` (`h5_page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='活动 与 标签 关联';



DROP TABLE IF EXISTS `me_rl_media_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_media_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(10) NOT NULL DEFAULT '0' COMMENT '回复内容id',
  `tag_id` int(10) NOT NULL DEFAULT '0' COMMENT '标签id',
  PRIMARY KEY (`id`),
  KEY `media_id` (`media_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='回复，标签关联表';



DROP TABLE IF EXISTS `me_rl_position_menu`;
CREATE TABLE IF NOT EXISTS `me_rl_position_menu` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `position_id` int(10) NOT NULL DEFAULT '0' COMMENT '职位id',
  `menu_id` int(10) NOT NULL DEFAULT '0' COMMENT '权限id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `position_id_menu_id` (`position_id`,`menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='职位-菜单 关联表';



DROP TABLE IF EXISTS `me_rl_position_permission`;
CREATE TABLE IF NOT EXISTS `me_rl_position_permission` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `position_id` int(10) NOT NULL DEFAULT '0' COMMENT '职位id',
  `permission_id` int(10) NOT NULL DEFAULT '0' COMMENT '权限id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `position_id_permission_id` (`position_id`,`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='职位-权限 关联表';



DROP TABLE IF EXISTS `me_rl_sina_tag_user`;
CREATE TABLE IF NOT EXISTS `me_rl_sina_tag_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wb_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'wb_users表自增id',
  `tag_id` varchar(50) NOT NULL DEFAULT '' COMMENT '标签id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_weibo_id_tag_id` (`tag_id`,`wb_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='新浪官方用户标签关联表';



DROP TABLE IF EXISTS `me_rl_wb_communication_category`;
CREATE TABLE IF NOT EXISTS `me_rl_wb_communication_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cmn_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微博交流表自增id',
  `cat_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '分类ID',
  PRIMARY KEY (`id`),
  KEY `cat_id` (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博分类关联表';



DROP TABLE IF EXISTS `me_rl_wb_group_user`;
CREATE TABLE IF NOT EXISTS `me_rl_wb_group_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wb_user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_gid` (`wb_user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='组和用户关联表';



DROP TABLE IF EXISTS `me_rl_wb_rule_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_wb_rule_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL DEFAULT '0' COMMENT '规则id',
  `tag_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博自动回复规则标签关联表';



DROP TABLE IF EXISTS `me_rl_wb_user_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_wb_user_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wb_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '粉丝 ID',
  `user_name` varchar(120) DEFAULT '' COMMENT '粉丝名称',
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签id',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wb_aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微博账号id',
  `link_tag_hits` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '链接标签点击计数(H5)',
  `rule_tag_hits` int(11) NOT NULL DEFAULT '0' COMMENT '规则标签命中计数(私信规则)',
  `manual_tag_hits` tinyint(1) NOT NULL DEFAULT '0' COMMENT '手动标签 {没打过:0, 打过:1}',
  `event_tag_hits` int(11) NOT NULL DEFAULT '0' COMMENT '活动标签点击计数(活动)',
  `timeline_tag_hits` int(11) NOT NULL DEFAULT '0' COMMENT '转发或评论带标签微博命中计数',
  `weight` smallint(6) NOT NULL DEFAULT '0' COMMENT '标签权重 [排序显示]',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wb_user_id_tag_id_wb_aid` (`wb_user_id`,`tag_id`,`wb_aid`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博用户 标签关联表';



DROP TABLE IF EXISTS `me_rl_wb_user_timeline_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_wb_user_timeline_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wb_id` int(11) NOT NULL COMMENT 'wb_user_timeline对应id',
  `tag_id` int(11) unsigned NOT NULL COMMENT 'wx_cate对应id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='通过系统发微博与标签关联表';



DROP TABLE IF EXISTS `me_rl_wx_communication_category`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_communication_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cmn_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类标签id',
  `cat_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '分类ID',
  PRIMARY KEY (`id`),
  KEY `cat_id` (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='微信分类关联表';



DROP TABLE IF EXISTS `me_rl_wx_group_user`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_group_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '微信用户ID',
  `wx_group_id` int(11) NOT NULL DEFAULT '0' COMMENT '组ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_gid` (`wx_user_id`,`wx_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信组和用户关联表';



DROP TABLE IF EXISTS `me_rl_wx_media_rule`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_media_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(10) DEFAULT '0' COMMENT '回复内容id',
  `rule_id` int(10) DEFAULT '0' COMMENT '规则id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_id_rule_id` (`media_id`,`rule_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='回复规则关联表';



DROP TABLE IF EXISTS `me_rl_wx_rule_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_rule_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL DEFAULT '0' COMMENT '规则id',
  `tag_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='规则标签关联表';



DROP TABLE IF EXISTS `me_rl_wx_user_2dcode`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_user_2dcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(255) NOT NULL DEFAULT '0' COMMENT '用户openid',
  `wx_2dcode_id` int(11) NOT NULL COMMENT '二维码id',
  `type` tinyint(1) DEFAULT '1' COMMENT '1：未关注  2：已关注',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `wx_user_id` (`openid`),
  KEY `2dcode_id` (`wx_2dcode_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信二维码-用户关联表';



DROP TABLE IF EXISTS `me_rl_wx_user_tag`;
CREATE TABLE IF NOT EXISTS `me_rl_wx_user_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wx_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '粉丝 ID',
  `openid` varchar(32) NOT NULL DEFAULT '0' COMMENT '微信用户openid',
  `tag_id` varchar(160) NOT NULL DEFAULT '0' COMMENT '标签id',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(11) NOT NULL DEFAULT '0' COMMENT '微信账号id',
  `link_tag_hits` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '链接标签点击计数',
  `rule_tag_hits` int(11) NOT NULL DEFAULT '0' COMMENT '规则标签命中计数',
  `manual_tag_hits` tinyint(1) NOT NULL DEFAULT '0' COMMENT '手动标签',
  `event_tag_hits` int(11) NOT NULL DEFAULT '0' COMMENT '活动标签点击计数',
  `weight` smallint(6) NOT NULL DEFAULT '0' COMMENT '标签权重 (通过计算更新)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wx_user_id_tag_id_wx_aid` (`wx_user_id`,`tag_id`,`wx_aid`),
  KEY `openid` (`openid`),
  KEY `cate_id` (`tag_id`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户 标签关联表';



DROP TABLE IF EXISTS `me_send_stat`;
CREATE TABLE IF NOT EXISTS `me_send_stat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `send_id` int(11) NOT NULL DEFAULT '0' COMMENT '群发ID',
  `openid` varchar(50) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `area` varchar(30) NOT NULL DEFAULT '' COMMENT '城市',
  `sex` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` date NOT NULL COMMENT '点击时间',
  `num` int(11) NOT NULL DEFAULT '1' COMMENT '点击次数',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='群发统计';



DROP TABLE IF EXISTS `me_sina_user_tag`;
CREATE TABLE IF NOT EXISTS `me_sina_user_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sina_tag_id` varchar(50) NOT NULL DEFAULT '' COMMENT '新浪微博标签id',
  `sina_tag_name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名',
  `weight` bigint(20) NOT NULL DEFAULT '0' COMMENT '微博标签权重',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_id` (`sina_tag_id`),
  KEY `company_id_tag_name` (`sina_tag_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='新浪官方用户标签表';



DROP TABLE IF EXISTS `me_staff`;
CREATE TABLE IF NOT EXISTS `me_staff` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '员工姓名',
  `login_name` varchar(50) NOT NULL DEFAULT '' COMMENT '登录名',
  `password` varchar(50) NOT NULL DEFAULT '' COMMENT 'SHA-1双层不可逆加密',
  `position_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '职位id',
  `tel` char(15) NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `expired_in` datetime DEFAULT NULL COMMENT '账号有效期（过期时间）',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '账号创建时间',
  `is_available` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否在线',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `last_login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后登录时间',
  `last_login_ip` varchar(20) NOT NULL DEFAULT '0.0.0.0' COMMENT '最后登录IP',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登陆总次数统计',
  `state` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '员工在线或者离线（0为离线，1为在线）',
  `do_message` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否有处理权限(1为有被分配的权限，0，为没有被分配的权限)',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='员工表';



DROP TABLE IF EXISTS `me_staff_reply`;
CREATE TABLE IF NOT EXISTS `me_staff_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cmn_id` int(11) unsigned NOT NULL COMMENT '交流表记录id',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '处理时间',
  `company_id` int(11) unsigned NOT NULL COMMENT '公司id',
  `staff_id` int(11) unsigned NOT NULL COMMENT '处理人id',
  `staff_name` varchar(50) NOT NULL COMMENT '处理人姓名',
  `wb_aid` int(11) NOT NULL COMMENT '使用回复账号id',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '处理的回复内容',
  `user_weibo_id` varchar(100) NOT NULL COMMENT '处理的用户id',
  `reply_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '回复方式 {0:转发，1:评论，2:回复，3:评论并转发，4:私信}',
  `result` tinyint(1) NOT NULL DEFAULT '0' COMMENT '回复结果 {0:未发送，1:已发送，2:发送失败}',
  `weibo_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '发出的微博id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='员工处理表';



DROP TABLE IF EXISTS `me_suspending`;
CREATE TABLE IF NOT EXISTS `me_suspending` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` smallint(6) NOT NULL COMMENT '绑定(微博/微信)账号ID',
  `company_id` smallint(5) unsigned NOT NULL COMMENT '公司id',
  `staff_id` smallint(5) unsigned NOT NULL COMMENT '员工id',
  `staff_name` varchar(50) NOT NULL COMMENT '员工姓名',
  `remind_time` datetime NOT NULL COMMENT '提醒时间',
  `description` varchar(255) DEFAULT NULL COMMENT '提醒描述',
  `cmn_id` int(11) NOT NULL COMMENT '相关微博id',
  `type` enum('wb','wx') NOT NULL DEFAULT 'wb' COMMENT '微博/微信',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '挂起时的状态 0:未操作, 1:已分类, 2:已处理, 3:已发送, 4:已忽略',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博、微信处理挂起（定时提醒）';



DROP TABLE IF EXISTS `me_tag`;
CREATE TABLE IF NOT EXISTS `me_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT '公司id',
  `tag_name` varchar(50) NOT NULL COMMENT '标签名称',
  `pid` int(11) unsigned NOT NULL COMMENT '父级标签id',
  `preset_tag_code` varchar(50) NOT NULL DEFAULT '' COMMENT '预置标签代码',
  `is_preset` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否预置',
  `add_staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加员工id',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否被删除0否1是',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id_tag_name` (`company_id`,`tag_name`),
  KEY `pid` (`pid`),
  KEY `company_id` (`company_id`),
  KEY `code` (`preset_tag_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='标签表';



DROP TABLE IF EXISTS `me_user`;
CREATE TABLE IF NOT EXISTS `me_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL DEFAULT '' COMMENT '名',
  `last_name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓',
  `full_name` varchar(50) NOT NULL DEFAULT '' COMMENT '全名',
  `gender` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '1:男，2:女，0:未知',
  `identity` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号码',
  `birthday` date NOT NULL DEFAULT '0000-00-00' COMMENT '出生日期',
  `blood_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '1->A型，2->B型，3->AB型，4->O型，0->未知',
  `constellation` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '([1~12]->[白羊~双鱼]) 0为未知',
  `collage` varchar(50) NOT NULL DEFAULT '' COMMENT '大学',
  `profession` varchar(50) NOT NULL DEFAULT '' COMMENT '职业',
  `edu_level` varchar(50) NOT NULL DEFAULT '' COMMENT '教育水平',
  `hobby` varchar(200) NOT NULL DEFAULT '' COMMENT '爱好',
  `main_page` varchar(50) DEFAULT '' COMMENT '个人主页',
  `company_id` int(11) DEFAULT '0' COMMENT '用户所属的公司id',
  `in_collage_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `love_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `graduate_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `marry_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `marry_plan_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `grow_up_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `pregnancy_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `children_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `house_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `buycar_stat` tinyint(4) DEFAULT '0' COMMENT '状态信息',
  `tel1` varchar(20) NOT NULL DEFAULT '',
  `tel2` varchar(20) NOT NULL DEFAULT '',
  `tel3` varchar(20) NOT NULL DEFAULT '',
  `email1` varchar(100) NOT NULL DEFAULT '',
  `email2` varchar(100) NOT NULL DEFAULT '',
  `email3` varchar(100) NOT NULL DEFAULT '',
  `qq1` varchar(20) NOT NULL DEFAULT '',
  `qq2` varchar(20) NOT NULL DEFAULT '',
  `qq3` varchar(20) NOT NULL DEFAULT '',
  `address1` varchar(255) NOT NULL DEFAULT '',
  `address2` varchar(255) NOT NULL DEFAULT '',
  `address3` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户主表';


DROP TABLE IF EXISTS `me_user_group`;
CREATE TABLE `me_user_group` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NULL DEFAULT NULL COMMENT '组名称',
  `description` VARCHAR(200) NULL DEFAULT NULL COMMENT '组描述',
  `created_at` DATETIME NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='高级用户组';


DROP TABLE IF EXISTS `me_user_o2o_relation`;
CREATE TABLE IF NOT EXISTS `me_user_o2o_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='O2O关联表';



DROP TABLE IF EXISTS `me_user_sns_relation`;
CREATE TABLE IF NOT EXISTS `me_user_sns_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `wb_user_id` int(11) NOT NULL DEFAULT '0',
  `wx_user_id` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '入库时间',
  `related_code` int(11) NOT NULL,
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='新媒体社交账号关联表';



DROP TABLE IF EXISTS `me_wb_account`;
CREATE TABLE IF NOT EXISTS `me_wb_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `weibo_id` varchar(100) NOT NULL COMMENT '微博id',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `platform` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '平台id   1新浪2腾讯',
  `access_token` varchar(50) NOT NULL COMMENT '应用授权的token',
  `refresh_token` varchar(50) DEFAULT '' COMMENT '腾讯用',
  `screen_name` varchar(100) NOT NULL DEFAULT '' COMMENT '微博昵称',
  `friends_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关注数',
  `followers_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `statuses_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微博数',
  `profile_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '头像地址',
  `expires_in` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '授权剩余时间',
  `token_updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '授权更新时间',
  `verified_type` int(11) NOT NULL DEFAULT '-1' COMMENT '用来判断身份',
  `app_id` int(11) NOT NULL DEFAULT '0' COMMENT '授权应用ID',
  `app_name` varchar(50) NOT NULL DEFAULT '' COMMENT '授权应用名',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '入库（首次授权）时间',
  `registered_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '微博账号注册时间（接口中的created_at字段）',
  `nokeyword_reply` varchar(1000) NOT NULL DEFAULT '' COMMENT '无关键词匹配',
  `subscribed_reply` varchar(1000) NOT NULL COMMENT '关注时发的',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '0正常 1删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博账号表';



DROP TABLE IF EXISTS `me_wb_account_user`;
CREATE TABLE IF NOT EXISTS `me_wb_account_user` (
  `user_weibo_id` varchar(50) NOT NULL COMMENT '微博账号',
  `company_id` int(11) unsigned NOT NULL COMMENT '公司id',
  `wb_aid` varchar(100) NOT NULL COMMENT '绑定微博id',
  `last_cmn_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最近交流时间',
  `relationship` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户与公司微博关系：0无1关注我的2我关注的3互相关注',
  `brand_friendly` decimal(6,5) unsigned NOT NULL DEFAULT '0.40000' COMMENT '品牌友好度 3',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户入库时间',
  UNIQUE KEY `user_weibo_id_wb_aid` (`user_weibo_id`,`wb_aid`),
  KEY `aid` (`wb_aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户账号关联表';



DROP TABLE IF EXISTS `me_wb_communication`;
CREATE TABLE IF NOT EXISTS `me_wb_communication` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wb_aid` int(11) NOT NULL COMMENT '获取微博的微博账号id',
  `type` tinyint(1) unsigned NOT NULL COMMENT '微博类型:0@,1评论，2keyword，3私信',
  `company_id` int(11) unsigned NOT NULL COMMENT '公司id',
  `user_weibo_id` varchar(50) NOT NULL COMMENT '用户微博id',
  `status_id` varchar(50) NOT NULL COMMENT '原始微博id',
  `weibo_id` varchar(50) NOT NULL COMMENT '微博id(腾讯15位，新浪16位)',
  `operation_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未操作, 1:已分类, 2:已处理, 3:已发送, 4:已忽略, 5:挂起中',
  `staff_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `is_top` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '置顶',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '沟通内容',
  `sent_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '微博发表时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '入库时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作更新时间',
  `wb_info` text NOT NULL COMMENT '串行化原始信息[base64_encode(gzcompress(json_encode($data, JSON_UNESCAPED_UNICODE), 9))]',
  `location` varchar(100) NOT NULL DEFAULT '其他' COMMENT '地区',
  `keyword_id` int(11) NOT NULL DEFAULT '0' COMMENT '关键字抓取ID，(私信命中关键词ID)',
  `rule_id` int(10) NOT NULL DEFAULT '0' COMMENT '私信命中规则ID',
  `tags` varchar(200) NOT NULL DEFAULT '' COMMENT '微博标签',
  `source` varchar(200) NOT NULL DEFAULT '' COMMENT '发微博的客户端<a链接DOM>',
  `platform` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源平台1:新浪，2:腾讯',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已被删除(0:否，1:是)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_company_id_weibo_id` (`wb_aid`,`company_id`,`weibo_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博沟通信息表';



DROP TABLE IF EXISTS `me_wb_communication_reply`;
CREATE TABLE IF NOT EXISTS `me_wb_communication_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL DEFAULT '0',
  `cmn_id` int(11) NOT NULL COMMENT '微博交流表ID',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '自动回复的类型',
  `content` text NOT NULL COMMENT '自动回复的内容',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '自动回复时间',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否成功发送 {0:未发送, 1:已发送, 2:发送失败}',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博自动回复表';



DROP TABLE IF EXISTS `me_wb_group`;
CREATE TABLE IF NOT EXISTS `me_wb_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL DEFAULT '' COMMENT '组名称',
  `description` varchar(255) NOT NULL COMMENT '组描述',
  `filter_param` text NOT NULL COMMENT '筛选组设定',
  `feature` text NOT NULL COMMENT '用户筛选条件文字描述',
  `expires_in` date NOT NULL DEFAULT '0000-00-00' COMMENT '到期时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `members_count` int(11) NOT NULL DEFAULT '0' COMMENT '该组用户的总数',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属公司ID',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可以锁定，如果为1 则不进行更新 0保持时效性，按照筛选条件定时更新',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博用户组';



DROP TABLE IF EXISTS `me_wb_keyword`;
CREATE TABLE IF NOT EXISTS `me_wb_keyword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT '0',
  `wb_aid` varchar(100) NOT NULL DEFAULT '1' COMMENT '判断关键词是否是当前用户专属',
  `staff_id` int(11) NOT NULL DEFAULT '0' COMMENT '关键词添加员工ID',
  `staff_name` varchar(20) NOT NULL DEFAULT '0' COMMENT '关键词添加员工姓名',
  `text` varchar(50) NOT NULL DEFAULT '' COMMENT '关键词名称',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '关键词类型{0:微博监控, 1:自动忽略, 2:自动置顶}',
  `cmn_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '关键词适用communication类型',
  `vdong_id` int(11) NOT NULL DEFAULT '0' COMMENT '远程关键字id',
  `sina_starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '搜索起始时间 sina 新浪',
  `tx_stattime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '搜索起始时间 tencent腾讯',
  `monitor_start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '关键字监控开始时间',
  `monitor_end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '关键字监控结束时间',
  `total_threshold` int(11) NOT NULL DEFAULT '0' COMMENT '总量阈值 关键词监控',
  `total_count_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '总量是否超标 关键词监控',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加关键词时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '关键词监控 监控开关 0 关  1 开',
  `interval_threshold` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '如果按周期监控，这里显示周期，单位小时。如果不按周期监控则为空',
  `interval_count_flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '周期量是否超标',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0 否 1 是',
  PRIMARY KEY (`id`),
  KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博抓取关键词表';



DROP TABLE IF EXISTS `me_wb_msg_keyword`;
CREATE TABLE IF NOT EXISTS `me_wb_msg_keyword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `rule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应规则id',
  `name` varchar(50) DEFAULT NULL COMMENT '关键词',
  `aid` int(10) NOT NULL DEFAULT '0' COMMENT '微博账号id',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '命中次数',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博自动回复关键字表';



DROP TABLE IF EXISTS `me_wb_msg_media_rule`;
CREATE TABLE IF NOT EXISTS `me_wb_msg_media_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(10) DEFAULT '0' COMMENT '回复内容id',
  `rule_id` int(10) DEFAULT '0' COMMENT '规则id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_id_rule_id` (`media_id`,`rule_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博回复规则关联表';



DROP TABLE IF EXISTS `me_wb_msg_rule`;
CREATE TABLE IF NOT EXISTS `me_wb_msg_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL COMMENT '公司id',
  `name` varchar(100) DEFAULT NULL COMMENT '规则名称',
  `aid` int(11) NOT NULL DEFAULT '10' COMMENT '微博账号id',
  `starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  `endtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '截止时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '规则修改时间',
  `group_id` varchar(200) DEFAULT NULL COMMENT '触发规则的用户组id,以,分隔',
  `hits` int(11) NOT NULL DEFAULT '0' COMMENT '规则命中量',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博规则表';



DROP TABLE IF EXISTS `me_wb_operation_history`;
CREATE TABLE IF NOT EXISTS `me_wb_operation_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wb_aid` int(11) NOT NULL DEFAULT '0' COMMENT '绑定微博帐号id',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间（操作记录入库时间）',
  `staff_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `staff_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `cmn_id` int(11) NOT NULL DEFAULT '0' COMMENT '交流记录表主键id',
  `assign_status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0:未分配, 1:已分配, 2:重分配',
  `audit_status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0:正在审核, 1:通过回复, 2:驳回回复',
  `operation_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0:未操作, 1:已分类, 2:已处理, 3:已发送, 4:已忽略, 5:挂起中',
  `operation` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0:分类, 1:分配, 2:提交处理, 3:发送, 4:审核通过, 5:驳回, 6:重分配, 7:修改分类, 8:定时发送, 9:忽略, 50:置顶, 51:取消置顶, 90:挂起, 91:取消忽略',
  `reason` text NOT NULL COMMENT '驳回/重分配原因（可能需要增加接口调用返回值）',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='操作流程历史表';



DROP TABLE IF EXISTS `me_wb_send_crontab`;
CREATE TABLE IF NOT EXISTS `me_wb_send_crontab` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT '0' COMMENT '公司id',
  `wb_aid` int(11) DEFAULT '0' COMMENT '微博帐号id',
  `weibo_id` varchar(50) NOT NULL DEFAULT '' COMMENT '处理微博id',
  `user_weibo_id` varchar(50) NOT NULL DEFAULT '' COMMENT '用户微博id',
  `sid` varchar(50) NOT NULL DEFAULT '0' COMMENT '需评论的微博id或者是转发原微博的id',
  `cid` varchar(50) NOT NULL DEFAULT '0' COMMENT '回复评论的id',
  `status_content` varchar(255) DEFAULT '' COMMENT '原微博内容',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '当前回复人的名字',
  `text` varchar(255) DEFAULT '0' COMMENT '发送微博内容',
  `pic_path` varchar(500) DEFAULT '' COMMENT '微博中的图片路径',
  `send_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '定时的时间',
  `set_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '什么时间设定的',
  `is_sent` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否发送：0:未发送，1:发送，2:出现错误延迟发送, 3:异常终止发送',
  `type` tinyint(3) NOT NULL DEFAULT '99' COMMENT '回复微博类型（0只转发，1只评论，2评论并转发，3回复评论，4私信）',
  `staff_id` int(50) NOT NULL DEFAULT '0' COMMENT '处理人id',
  `data` varchar(255) DEFAULT '0' COMMENT 't-tagid1_tagid2',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博定时发送表';



DROP TABLE IF EXISTS `me_wb_stats_follower`;
CREATE TABLE IF NOT EXISTS `me_wb_stats_follower` (
  `wb_aid` int(11) NOT NULL DEFAULT '0',
  `total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '粉丝总量',
  `commu_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '互动粉丝量',
  `gender_stat` text NOT NULL COMMENT '按性别的统计结果(json)',
  `region_stat` text NOT NULL COMMENT '按地区的统计结果(json)',
  `verified_type_stat` text NOT NULL COMMENT '按认证类型的统计结果(json)',
  `sub_followers_stat` text NOT NULL COMMENT '按照粉丝的粉丝量统计(json)',
  `platform` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '来源平台(1:新浪微博，2:腾讯微博)',
  `log_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '统计记录时间',
  `date` date NOT NULL DEFAULT '0000-00-00' COMMENT '记录日期(2014-04-03)',
  PRIMARY KEY (`wb_aid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='粉丝多维度人数按日统计[记录了每日的粉丝总量和互动粉丝量]';



DROP TABLE IF EXISTS `me_wb_user`;
CREATE TABLE IF NOT EXISTS `me_wb_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_weibo_id` varchar(50) NOT NULL DEFAULT '' COMMENT '微博账号',
  `platform` tinyint(4) NOT NULL DEFAULT '1' COMMENT '来源平台 {1:sina, 2:tencent}',
  `idstr` varchar(50) NOT NULL DEFAULT '' COMMENT '微博ID字符串',
  `screen_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '友好显示名称',
  `country_code` varchar(8) NOT NULL DEFAULT '0' COMMENT '国家代码(包含字母)',
  `province_code` varchar(8) NOT NULL DEFAULT '0' COMMENT '省代码 腾讯的包含字母，新浪是数字',
  `city_code` varchar(8) NOT NULL DEFAULT '0' COMMENT '市代码 腾讯的包含字母，新浪是数字',
  `location` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '用户个人描述',
  `url` varchar(50) DEFAULT '' COMMENT '用户博客地址',
  `profile_image_url` varchar(255) DEFAULT '' COMMENT '头像地址',
  `profile_url` varchar(50) DEFAULT '' COMMENT '用户的微博统一URL地址',
  `domain` varchar(50) DEFAULT '' COMMENT '用户个性域名',
  `weihao` varchar(50) DEFAULT '' COMMENT '用户微号',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:男 2:女 0:未知',
  `followers_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `friends_count` int(11) NOT NULL DEFAULT '0' COMMENT '关注数',
  `statuses_count` int(11) NOT NULL DEFAULT '0' COMMENT '微博数',
  `favourites_count` int(11) NOT NULL DEFAULT '0' COMMENT '收藏数',
  `registerd_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '用户注册时间',
  `geo_enabled` tinyint(1) DEFAULT '0' COMMENT '是否允许标识用户的地理位置 0:否 1:是',
  `allow_all_act_msg` tinyint(1) DEFAULT '0' COMMENT '允许所有人给我发私信 0:否 1:是',
  `allow_all_comment` tinyint(1) DEFAULT '0' COMMENT '是否允许所有人对我的微博进行评论 0:否 1:是',
  `verified` tinyint(1) DEFAULT '0' COMMENT '是否是微博认证用户，即加V用户 0:否 1:是',
  `verified_type` smallint(5) DEFAULT '999' COMMENT '发微用户认证身份,-1普通，220,200达人，0个人认证，2企业认证，999为腾讯普通用户，-2为腾讯认证用户',
  `verified_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '认证原因',
  `avatar_large` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像地址（大图），180×180像素',
  `avatar_hd` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像地址（高清），高清头像原图',
  `bi_followers_count` int(11) NOT NULL DEFAULT '0' COMMENT '用户的互粉数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_weibo_id` (`user_weibo_id`),
  KEY `country_code` (`country_code`),
  KEY `province_code` (`province_code`),
  KEY `city_code` (`city_code`),
  KEY `gender` (`gender`),
  KEY `verified_type` (`verified_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博用户表';



DROP TABLE IF EXISTS `me_wb_user_cim_info`;
CREATE TABLE IF NOT EXISTS `me_wb_user_cim_info` (
  `wb_user_id` int(11) DEFAULT NULL COMMENT 'wb_users表id',
  `purchasing` double DEFAULT NULL COMMENT '购买力',
  `liveness` double DEFAULT NULL COMMENT '活跃度',
  `influence` double DEFAULT NULL COMMENT '影响力',
  UNIQUE KEY `wb_user_id` (`wb_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微博用户CIM指数信息表';



DROP TABLE IF EXISTS `me_wb_user_timeline`;
CREATE TABLE IF NOT EXISTS `me_wb_user_timeline` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司ID',
  `wb_aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定微博账号的ID',
  `weibo_id` varchar(32) NOT NULL DEFAULT '' COMMENT '品牌账户发的微博的id',
  `text` varchar(255) DEFAULT '' COMMENT '用户发的微博的内容',
  `is_retweeted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否是转发',
  `created_at` int(11) NOT NULL COMMENT '微博创建时间',
  `me_sent` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否通过本系统发送 1:是 0:否',
  `wb_info` text COMMENT '发送微博后的返回值',
  `is_deleted` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `weibo_id` (`weibo_id`) USING BTREE,
  KEY `company_id_aid` (`company_id`,`wb_aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='存品牌用户自己发的微博';



DROP TABLE IF EXISTS `me_wx_2dcode`;
CREATE TABLE IF NOT EXISTS `me_wx_2dcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_aid` int(11) DEFAULT '1' COMMENT '微信id',
  `company_id` int(11) NOT NULL COMMENT '公司id',
  `title` varchar(50) DEFAULT NULL COMMENT '名称',
  `category` int(11) NOT NULL DEFAULT '0' COMMENT '二维码类型：0门店；1活动',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `pic_url` varchar(500) DEFAULT NULL COMMENT '图片地址',
  `ticket` varchar(200) NOT NULL COMMENT '二维码ticket',
  `content` text COMMENT '二维码内容',
  `scene_id` int(11) DEFAULT '123' COMMENT '创建二维码时的scene_id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信二维码';



DROP TABLE IF EXISTS `me_wx_account`;
CREATE TABLE IF NOT EXISTS `me_wx_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `account_name` varchar(50) DEFAULT '' COMMENT '微信账号名称',
  `nickname` varchar(50) DEFAULT '' COMMENT '微信昵称 自定义的',
  `head_pic` varchar(50) DEFAULT '' COMMENT '微信头像',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '账号添加到系统中的时间',
  `verified` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否验证过  0否   1是',
  `token` varchar(50) NOT NULL COMMENT '公众平台填写的token',
  `subscribed_reply` varchar(1000) NOT NULL DEFAULT '' COMMENT '被关注发送的信息',
  `nokeyword_reply` varchar(1000) NOT NULL DEFAULT '' COMMENT '没有关键词时的回复',
  `access_token` varchar(512) NOT NULL DEFAULT '' COMMENT '公众平台授权token',
  `appid` varchar(50) NOT NULL DEFAULT '' COMMENT '公众平台appid',
  `secret` varchar(50) NOT NULL DEFAULT '' COMMENT '公众平台appsecret',
  `sns_related_reply` text COMMENT 'sns账号关联回复（双微）',
  `o2o_related_reply` text COMMENT 'o2o账号关联回复（线下会员）',
  `type_reply` varchar(20) NOT NULL DEFAULT 'text',
  `type2_reply` varchar(20) NOT NULL DEFAULT 'text',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '0正常  1删除',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `wxaccount` (`account_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信账号表';



DROP TABLE IF EXISTS `me_wx_communication`;
CREATE TABLE IF NOT EXISTS `me_wx_communication` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(11) NOT NULL DEFAULT '0' COMMENT '微信用户Id',
  `openid` varchar(50) DEFAULT NULL COMMENT '用户openid',
  `keyword_id` int(10) unsigned DEFAULT '0' COMMENT '命中的关键词id',
  `rule_id` int(10) unsigned DEFAULT '0' COMMENT '命中规则ID',
  `type` varchar(50) DEFAULT NULL COMMENT '内容类型',
  `msgid` varchar(100) NOT NULL DEFAULT '0' COMMENT '接收消息id',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '入库时间',
  `is_top` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否置顶 0:否, 1:是',
  `operation_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0:未操作, 1:已分类, 2:已处理, 3:已发送, 4:已忽略, 5:挂起中',
  `is_deleted` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否删除 {0:否, 1:是}',
  `staff_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '处理人ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `msgid` (`msgid`),
  KEY `openid` (`openid`),
  KEY `wx_aid` (`wx_aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信交流表';



DROP TABLE IF EXISTS `me_wx_communication_data`;
CREATE TABLE IF NOT EXISTS `me_wx_communication_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `communication_id` int(11) NOT NULL COMMENT 'communication表',
  `content` text NOT NULL COMMENT '文本消息内容',
  `picurl` varchar(255) NOT NULL COMMENT '图片链接 ',
  `media_id` varchar(255) NOT NULL DEFAULT '0' COMMENT '语音消息媒体id',
  `format` varchar(255) NOT NULL COMMENT '语音格式',
  `thumbmediaId` int(11) NOT NULL DEFAULT '0' COMMENT '视频消息缩略图媒体id',
  `location_x` varchar(100) NOT NULL COMMENT '地理位置纬度',
  `location_y` varchar(100) NOT NULL COMMENT '地理位置经度',
  `scale` varchar(20) NOT NULL COMMENT '地图缩放大小',
  `label` varchar(100) NOT NULL COMMENT '地理位置信息',
  `title` varchar(200) NOT NULL COMMENT '消息标题',
  `description` varchar(200) NOT NULL COMMENT '消息描述',
  `url` varchar(255) NOT NULL COMMENT '消息链接 ',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='消息副表';



DROP TABLE IF EXISTS `me_wx_communication_reply`;
CREATE TABLE IF NOT EXISTS `me_wx_communication_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '回复ID',
  `cmn_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微信交流表 id',
  `openid` varchar(50) NOT NULL DEFAULT '' COMMENT '回复用户的OPENID',
  `type` enum('text','image','voice','news','music','video') NOT NULL DEFAULT 'text' COMMENT '回复的类型 {0:文本, 1:图片, 2:语音, 3:视频, 4:音乐, 5:图文}',
  `media_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '回复图文消息ID, 多图文为0',
  `content` text COMMENT '回复的内容',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '回复的时间',
  `staff_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '分配员工ID {ID为0时是自动回复}',
  `staff_name` varchar(50) NOT NULL DEFAULT '' COMMENT '分配员工姓名',
  `is_crontab` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是定时任务,非0为定时任务，值为上次的status +10',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否成功发送 {0:未发送, 1:已发送, 2:发送失败}',
  PRIMARY KEY (`id`),
  KEY `wx_communication_id` (`cmn_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='人工(自动)回复的表';



DROP TABLE IF EXISTS `me_wx_custom_menu_log`;
CREATE TABLE IF NOT EXISTS `me_wx_custom_menu_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微信账号id',
  `staff` varchar(100) DEFAULT NULL COMMENT '员工登陆账户名',
  `method` varchar(50) DEFAULT NULL COMMENT '操作方法',
  `menu` varchar(1000) DEFAULT NULL COMMENT '操作后的菜单',
  `result` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '操作结果1成功0失败',
  `datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='自定义菜单操作日志表';



DROP TABLE IF EXISTS `me_wx_custom_menu_media`;
CREATE TABLE IF NOT EXISTS `me_wx_custom_menu_media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信账号id',
  `key` varchar(50) DEFAULT NULL COMMENT '菜单key值',
  `media_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复表 id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信菜单触发 回复规则';



DROP TABLE IF EXISTS `me_wx_group`;
CREATE TABLE IF NOT EXISTS `me_wx_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_aid` int(10) DEFAULT NULL COMMENT '微信用户id',
  `name` varchar(50) DEFAULT NULL COMMENT '组名称',
  `description` varchar(255) DEFAULT NULL COMMENT '组描述',
  `filter_param` text NOT NULL COMMENT '筛选组设定',
  `feature` text NOT NULL COMMENT '组功能',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `expires_in` date DEFAULT '0000-00-00' COMMENT '到期时间',
  `members_count` int(11) NOT NULL DEFAULT '0' COMMENT '该组用户的总数',
  `company_id` int(11) NOT NULL DEFAULT '0',
  `filter_setting` text COMMENT '锁定组专用，定时调的查询任务',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可以锁定，如果为1 则可以执行组特征的条件筛选 0保持时效性',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信用户组';



DROP TABLE IF EXISTS `me_wx_keyword`;
CREATE TABLE IF NOT EXISTS `me_wx_keyword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `rule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应规则id',
  `name` varchar(50) DEFAULT NULL COMMENT '关键词',
  `aid` int(10) NOT NULL DEFAULT '0' COMMENT '微信账号id',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '命中次数',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信关键字表';



DROP TABLE IF EXISTS `me_wx_operation_history`;
CREATE TABLE IF NOT EXISTS `me_wx_operation_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(11) NOT NULL DEFAULT '0' COMMENT '绑定微博帐号id',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间（操作记录入库时间）',
  `staff_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `staff_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `cmn_id` int(11) NOT NULL DEFAULT '0' COMMENT '交流记录表主键id',
  `assign_status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0:未分配, 1:已分配, 2:重分配',
  `audit_status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0:正在审核, 1:通过回复, 2:驳回回复',
  `operation_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0:未操作, 1:已分类, 2:已处理, 3:已发送, 4:已忽略, 5:挂起中',
  `operation` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0:分类, 1:分配, 2:提交处理, 3:发送, 4:审核通过, 5:驳回, 6:重分配, 7:修改分类, 8:定时发送, 9:忽略, 50:置顶, 51:取消置顶, 90:挂起, 91:取消忽略',
  `reason` text NOT NULL COMMENT '驳回/重分配原因（可能需要增加接口调用返回值）',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信操作流程历史表';



DROP TABLE IF EXISTS `me_wx_rule`;
CREATE TABLE IF NOT EXISTS `me_wx_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL COMMENT '公司id',
  `name` varchar(100) DEFAULT NULL COMMENT '规则名称',
  `aid` int(11) NOT NULL DEFAULT '10' COMMENT '微信账号id',
  `starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  `endtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '截止时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '规则修改时间',
  `group_id` varchar(200) DEFAULT NULL COMMENT '触发规则的用户组id,以,分隔',
  `hits` int(11) NOT NULL DEFAULT '0' COMMENT '规则命中量',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信规则表';



DROP TABLE IF EXISTS `me_wx_sendall`;
CREATE TABLE IF NOT EXISTS `me_wx_sendall` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` varchar(30) NOT NULL DEFAULT '' COMMENT '群发返回的标记Id',
  `media_id` int(11) NOT NULL DEFAULT '0' COMMENT '素材id',
  `content` text NOT NULL COMMENT '发送内容',
  `company_id` int(11) NOT NULL DEFAULT '0',
  `wx_aid` int(11) NOT NULL DEFAULT '0' COMMENT '微信aid',
  `openids` text NOT NULL COMMENT '群发用户openid，以逗号连接',
  `msgtype` varchar(50) NOT NULL DEFAULT '' COMMENT '发送类型',
  `json_data` text NOT NULL COMMENT '发送的JSON字符串',
  `exec_time` datetime NOT NULL COMMENT '定时发送时间',
  `is_send` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0未发送 1已发送',
  `totalcount` int(10) NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `filtercount` int(10) NOT NULL DEFAULT '0' COMMENT '过滤后应发的',
  `sentcount` int(10) NOT NULL DEFAULT '0' COMMENT '发送成功的粉丝数',
  `errorcount` int(10) NOT NULL DEFAULT '0' COMMENT '发送失败的粉丝数',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `actual_send_at` datetime NOT NULL COMMENT '发送成功时间',
  `status` varchar(100) NOT NULL DEFAULT '' COMMENT '群发状态：send success   send fail   err(num)',
  `is_delete` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0：正常  1：删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信定时群发';



DROP TABLE IF EXISTS `me_wx_send_num`;
CREATE TABLE IF NOT EXISTS `me_wx_send_num` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `year` char(6) NOT NULL DEFAULT '' COMMENT '年月',
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '用户openid',
  `num` text NOT NULL COMMENT '预留字段，保存所有月份',
  `new_num` tinyint(2) NOT NULL DEFAULT '0' COMMENT '当月发送次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `me_wx_umenu`;
CREATE TABLE IF NOT EXISTS `me_wx_umenu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wx_aid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信账户id',
  `key` varchar(100) DEFAULT NULL COMMENT 'key',
  `info` text NOT NULL COMMENT '菜单数组串行化',
  `medias` varchar(255) DEFAULT NULL COMMENT '自动回复的media id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信自定义菜单表';



DROP TABLE IF EXISTS `me_wx_user`;
CREATE TABLE IF NOT EXISTS `me_wx_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT '公司id',
  `wx_aid` int(11) NOT NULL DEFAULT '0' COMMENT '所属微信公众账号名',
  `nickname` varchar(50) CHARACTER SET utf8 DEFAULT '微信用户' COMMENT '用户昵称',
  `country` varchar(50) CHARACTER SET utf8 DEFAULT '中国' COMMENT '国家',
  `province` varchar(50) CHARACTER SET utf8 DEFAULT '其他' COMMENT '省份',
  `city` varchar(50) CHARACTER SET utf8 DEFAULT '其他' COMMENT '城市',
  `sex` tinyint(1) DEFAULT '1' COMMENT '性别：1男，2女',
  `remark` varchar(50) NOT NULL DEFAULT '',
  `fakeid` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '0' COMMENT '微信用户真实id',
  `signature` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '签名',
  `headimgurl` varchar(200) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '头像图片链接',
  `localimgurl` varchar(200) NOT NULL DEFAULT '' COMMENT '本地图片名',
  `subscribe` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否关注 1关注 0 未关注',
  `subscribe_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '关注时间',
  `communication_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '与账号的沟通时间',
  `openid` varchar(50) CHARACTER SET utf8 DEFAULT '0' COMMENT '微信用户openid',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '入库时间',
  `purchasing_power` decimal(6,5) unsigned DEFAULT '0.00000' COMMENT '微信的购买力',
  `brand_interaction` decimal(6,5) unsigned DEFAULT '0.40000' COMMENT '微信品牌交互度',
  `language` varchar(20) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid_UNIQUE` (`openid`),
  KEY `wx_id` (`wx_aid`),
  KEY `fakeid` (`fakeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='微信用户表';