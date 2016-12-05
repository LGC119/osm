'use strict';

define([], function () {
	return {
		defaultRoutePath: '/dashboard',
		routes: {
			'/dashboard/': {
				templateUrl: 'assets/html/common/dashboard.html',
				dependencies: [
					'common/DashboardController',
					'../lib/highcharts/highcharts',
					'../lib/jquery/jquery.tagcloud'
				]
			},
			'/wb-account/': {
				templateUrl: 'assets/html/system/wb_account.html',
				dependencies: [
					'system/WeiboAccountController'
				]
			},
			'/wx-account/': {
				templateUrl: 'assets/html/system/wx_account.html',
				dependencies: [
					'system/Wx_accountController'
				]
			},
			'/application/': {
				templateUrl: 'assets/html/system/application.html',
				dependencies: [
					'system/ApplicationController'
				]
			},
			'/staff/': {
				templateUrl: 'assets/html/system/staff.html',
				dependencies: [
					'system/StaffController',
					'system/StaffService'
				]
			},
			'/position/': {
				templateUrl: 'assets/html/system/position.html',
				dependencies: [
					'system/PositionController',
				]
			},
			'/setting/': {
				templateUrl: 'assets/html/system/setting.html',
				dependencies: [
                    'system/SettingController',
				]
			},
			'/profile/': {               // 个人信息设置
				templateUrl: 'assets/html/system/profile.html',
				dependencies: [
					'system/ProfileController'
				]
			},
			'/untouched/:type/': {
				templateUrl: 'assets/html/meo/untouched.html',
				dependencies: [
					'meo/WeiboOperationController',
					'meo/CommunicationService',
					'system/StaffService'
				]
			},
			'/categorized/:type/': {    // 待处理微博
				templateUrl: 'assets/html/meo/categorized.html',
				dependencies: [
					'meo/WeiboOperationController',
					'meo/CommunicationService',
					'system/StaffService'
				]
			},
			'/ignored/:type/': {
				templateUrl: 'assets/html/meo/ignored.html',
				dependencies: [
					'meo/WeiboOperationController',
					'meo/CommunicationService',
					'system/StaffService'
				]
			},
			'/replied/:type/': {
				templateUrl: 'assets/html/meo/replied.html',
				dependencies: [
					'meo/WeiboOperationController',
					'meo/CommunicationService',
					'system/StaffService'
				]
			},
			'/suspending/:type/': {     // 挂起中的微博
				templateUrl: 'assets/html/meo/suspending.html',
				dependencies: [
					'meo/WeiboOperationController',
					'meo/CommunicationService',
					'system/StaffService'
					// 'common/SuspendingService'
				]
			},
			'/weibo-user/:searchGroupId/': {                // 微博用户管理
				templateUrl: 'assets/html/meo/user.html',
				dependencies: [
					'meo/WeiboUserController',
					'meo/WeiboUserService',
					'meo/WeiboUserGroupService',
					'common/tag/TagService',
					'common/UserService'
				]
			},
			'/weibo-user-group/:type/': {               // 微博用户组管理
				templateUrl: 'assets/html/meo/user-group.html',
				dependencies: [
					'meo/WeiboUserGroupController',
					'meo/WeiboUserGroupService',
					'../lib/highcharts/highcharts'
				]
			},
			'/category/': {       // 微博用户管理
				templateUrl: 'assets/html/common/category.html',
				dependencies: [
					'common/CategoryController'
				]
			},
			'/msg-rule/': {
				templateUrl: 'assets/html/meo/msg-rule.html',
				dependencies: [
					'meo/MsgRuleController',
					'mex/RuleService',
					'common/MediaService',
					'common/tag/TagService'
				]
			},
			'/msg-other-rule/': {
				templateUrl: 'assets/html/meo/msg-otherrule.html',
				dependencies: [
					'meo/MsgOtherruleController',
					'mex/RuleService',
					'common/MediaService'
				]
			},
			'/tag/': {
				templateUrl: 'assets/html/common/tag/tag.html',
				dependencies: [
					'common/tag/TagController',
					'common/tag/TagService',
					'common/tag/TagDirectives'
				]
			},
			'/h5page-create/:page/': {
				templateUrl: 'assets/html/common/h5page/create.html',
				dependencies: [
					'common/h5page/H5pageController',
					'common/h5page/H5pageService',
					'common/h5page/H5pageDirectives',
					'common/tag/TagService'/*,
					 '../lib/kindeditor/kindeditor-min',
					 '../lib/kindeditor/plugins/code/prettify',
					 '../lib/kindeditor/lang/zh_CN'*/
				]
			},
			'/h5page-list/:page/': {
				templateUrl: 'assets/html/common/h5page/list.html',
				dependencies: [
					'common/h5page/H5pageController',
					'common/h5page/H5pageService',
					'common/h5page/H5pageDirectives',
					'common/tag/TagService'
				]
			},

			// 微博发布部分routes
			'/weibo-send/': {
				templateUrl: 'assets/html/meo/weibo-send.html',
				dependencies: [
					'meo/WeiboSendController',
					'meo/WeiboService',
					'common/tag/TagService'
				]
			},
			'/friends-timeline/': {
				templateUrl: 'assets/html/meo/friends-timeline.html',
				dependencies: [
					'meo/WeiboSendController',
					'meo/WeiboService',
					'common/tag/TagService'
				]
			},
			'/user-timeline/': {
				templateUrl: 'assets/html/meo/user-timeline.html',
				dependencies: [
					'meo/UserTimelineController',
					'meo/CommunicationService',
					'meo/WeiboService',
					'common/tag/TagService'
				]
			},
			'/weibo-crontab/': {
				templateUrl: 'assets/html/meo/weibo-crontab.html',
				dependencies: [
					'meo/WeiboCrontabController',
					'meo/WeiboService',
					'common/tag/TagService'
				]
			},

			// 微博活动创建部分
			'/wb-event-create/:group_id': {
				templateUrl: 'assets/html/meo/event-create.html',
				dependencies: [
					'../lib/fuelux.wizard.min',
					'../lib/jquery/chosen.jquery.min',
					'meo/WbEventCreateController',
					'meo/WeiboEventDirectives',
					'common/h5page/H5pageService',
					'common/EventService',
					'common/tag/TagService',
					'common/MediaService'
				]
			},
			'/wb-event-manage/': {
				templateUrl: 'assets/html/meo/event-list.html',
				dependencies: [
					'meo/WbEventListController',
					'common/EventService',
					'common/tag/TagService'
				]
			},
			'/wb-event-detail/:type/:id/': {
				templateUrl: 'assets/html/meo/event-detail.html',
				dependencies: [
					'meo/WbEventDetailController',
					// 'advanced/EventInnerController',
					'common/EventService',
					'common/tag/TagService',
					'../lib/highcharts/highcharts'
				]
			},

			/* 信息监控 */
			'/wb-keyword': {
				templateUrl: 'assets/html/meo/wb-keyword.html',
				dependencies: [
					'meo/WbKeywordController'
				]
			},
			'/monitor-wb-category': {
				templateUrl: 'assets/html/meo/monitor-wb-category.html',
				dependencies: [
					'meo/MonitorWeiboCategoryController'
				]
			},
			'/monitor-wx-category': {
				templateUrl: 'assets/html/mex/monitor-wx-category.html',
				dependencies: [
					'mex/MonitorWeixinCategoryController'
				]
			},


			/* 微信人工客服 */
			'/wx-untouched/': {
				templateUrl: 'assets/html/mex/wx_untouched.html',
				dependencies: [
					'mex/WeixinOperationController',
					'mex/CommunicationService',
					'system/StaffService',
					'common/MediaService',
					'common/UserService',
					'common/tag/TagService'
				]
			},
			'/wx-categorized/': {    // 待处理微信消息
				templateUrl: 'assets/html/mex/wx_categorized.html',
				dependencies: [
					'mex/WeixinOperationController',
					'common/MediaService',
					'system/StaffService',
					'mex/CommunicationService',
					'common/UserService',
					'common/tag/TagService'
				]
			},
			'/wx-ignored/': {
				templateUrl: 'assets/html/mex/wx_ignored.html',
				dependencies: [
					'mex/WeixinOperationController',
					'mex/CommunicationService',
					'system/StaffService',
					'common/MediaService',
					'common/UserService',
					'common/tag/TagService'
				]
			},
			'/wx-replied/': {
				templateUrl: 'assets/html/mex/wx_replied.html',
				dependencies: [
					'mex/WeixinOperationController',
					'mex/CommunicationService',
					'system/StaffService',
					'common/MediaService',
					'common/UserService',
					'common/tag/TagService'
				]
			},
			'/wx-suspending/': {     // 挂起中的微信消息
				templateUrl: 'assets/html/mex/wx_suspending.html',
				dependencies: [
					'mex/WeixinOperationController',
					'mex/CommunicationService',
					'common/MediaService',
					'system/StaffService',
					'common/UserService',
					'common/tag/TagService'
				]
			},
            '/company/':{ //公司设置信息
                templateUrl:'assets/html/system/company.html',
                dependencies:[
                    'system/CompanyController'
                    ]
            },

			/**
			 * ============================
			 * 模块: 自动回复规则
			 * author: liurq
			 * date  : 2014-06-12
			 * ============================
			 */
			//            自动回复规则功能
			'/wx-rule/': {
				templateUrl: 'assets/html/mex/rule.html',
				dependencies: [
					'mex/RuleController',
					'mex/RuleService',
					'common/MediaService',
					'common/tag/TagService'
				]
			},
			//            自定义菜单
			'/wx-custom-menu/': {
				templateUrl: 'assets/html/mex/umenu.html',
				dependencies: [
					'mex/UmenuController',
					'mex/UmenuService',
					'common/MediaService',
					'common/tag/TagService'
				]
			},
			//            其他规则
			'/wx-other-rule/': {
				templateUrl: 'assets/html/mex/otherrule.html',
				dependencies: [
					'mex/OtherruleController',
					'mex/RuleService',
					'common/MediaService'
				]
			},

			/**
			 * ============================
			 * 模块: 粉丝管理
			 * author: liurq
			 * date  : 2014-06-12
			 * ============================
			 */
			//            微信用户管理功能
			'/wx-user/:searchGroupId/:sendId/': {
				templateUrl: 'assets/html/mex/user.html',
				dependencies: [
					'mex/UserController',
					'mex/UserService',
					'mex/GroupService',
					'common/tag/TagService',
					'common/UserService'
				]
			},
			//            微信用户组管理功能
			'/wx-group/': {
				templateUrl: 'assets/html/mex/group.html',
				dependencies: [
					'mex/GroupController',
					'mex/GroupService',
					'../lib/highcharts/highcharts'
				]
			},

			// 微信活动相关
			'/wx-event-create/': {
				templateUrl: 'assets/html/mex/event-create.html',
				dependencies: [
					'mex/WeixinEventController',
					'common/EventService',
					'common/tag/TagService'
				]
			},
			'/wx-event-manage/': {
				templateUrl: 'assets/html/mex/event-list.html',
				dependencies: [
					'mex/WeixinEventController',
					'common/EventService',
					'common/tag/TagService'
				]
			},


			/**
			 * ========================
			 * 模块: 信息推送
			 * author: liurq
			 * date  : 2014-06-12
			 * ========================
			 */
			//            微信群发功能
			'/wx-sendAll/:group_id/': {
				templateUrl: 'assets/html/mex/sendall.html',
				dependencies: [
					'mex/SendallController',
					'mex/GroupService',
					'common/MediaService',
					'mex/UserService',
					'common/tag/TagService'
				]
			},
			//            微信群发列表
			'/wx-list-sendall': {
				templateUrl: 'assets/html/mex/sendall-list.html',
				dependencies: [
					'mex/SendallListController',
					'mex/SendallListService',
					'../lib/highcharts/highcharts'
					//                    'mex/GroupService',
					//                    'common/MediaService',
					//                    'mex/UserService'
				]
			},

			//            素材库管理功能
			'/media/:type/': {
				templateUrl: 'assets/html/common/media.html',
				dependencies: [
					//                    '../lib/masonry/masonry.min',
					'common/MediaController',
					// '../lib/uploadify/jquery.uploadify.min',
					'common/MediaService',
					//                    '../lib/masonry/masonry.min',
					//                    '../lib/masonry/angular-masonry',
					//                    '../lib/soundmanager2/soundmanager2',
					//                    '../lib/soundmanager2/inlineplayer',
					//                    '../lib/ueditor/ueditor.all.min',
					//                    '../lib/ueditor/ueditor.config',
					'common/tag/TagService'
				]
			},
			// 智库
			'/quick-reply/': {
				templateUrl: 'assets/html/common/quick-reply.html',
				dependencies: [
					'common/QuickReplyController'
				]
			},

			//            二维码管理功能
			'/twodcode/': {
				templateUrl: 'assets/html/common/twodcode.html',
				dependencies: [
					'common/TwodcodeController',
					'common/TwodcodeService'
				]
			},

			//            二维码详情
			'/twodcode_detail/:code_id/': {
				templateUrl: 'assets/html/common/twodcode_detail.html',
				dependencies: [
					'common/TwodcodedetailController',
					'common/TwodcodedetailService',
					'../lib/highcharts/highcharts'
				]

			},

			//            门店地址
			'/shop_place/': {
				templateUrl: 'assets/html/common/shop_place.html',
				dependencies: [
					'common/ShopplaceController',
					'common/ShopplaceService'
				]

			},

			// 高级功能
			'/adv-dashboard/': {
				templateUrl: 'assets/html/advanced/dashboard.html',
				dependencies: [
					'advanced/DashboardController',
					'../lib/highcharts/highcharts'
				]
			},
			// 高级功能
			'/adv-user/:group_id': {
				templateUrl: 'assets/html/advanced/user.html',
				dependencies: [
					'advanced/UserController',
					'common/tag/TagService',
					'../lib/jquery/jquery-ui.custom.min'
				]
			},
			// 高级功能
			'/adv-group/': {
				templateUrl: 'assets/html/advanced/group.html',
				dependencies: [
					'advanced/AdvGroupController'
				]
			},
			// 高级活动创建部分
			'/adv-event-create/:group_id': {
				templateUrl: 'assets/html/advanced/event-create.html',
				dependencies: [
					'../lib/fuelux.wizard.min',
					'advanced/EventCreateController',
					'common/EventService',
					'common/h5page/H5pageService',
					'common/tag/TagService',
					'common/MediaService'
				]
			},
			'/adv-event-manage/': {
				templateUrl: 'assets/html/advanced/event-list.html',
				dependencies: [
					'advanced/EventListController',
					'common/EventService',
					'common/tag/TagService'
				]
			},
			'/adv-event-detail/:type/:id/': {
				templateUrl: 'assets/html/advanced/event-detail.html',
				dependencies: [
					'advanced/EventDetailController',
					'common/EventService',
					'common/tag/TagService',
					'../lib/highcharts/highcharts'
				]
			},

			// 统计部分
			/*微博|微信粉丝信息统计*/
			'/user-stats/:type': {
				templateUrl: 'assets/html/common/stats/user-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*微博|微信舆情分类统计*/
			'/interact-stats/:type': {
				templateUrl: 'assets/html/common/stats/interact-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*微博舆情关键词统计*/
			'/keyword-stats': {
				templateUrl: 'assets/html/common/stats/keyword-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*私信|微信命中关键词及规则统计*/
			'/rule-stats/:type': {
				templateUrl: 'assets/html/common/stats/rule-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*微博|微信CSR工作量统计*/
			'/workload-stats/:type': {
				templateUrl: 'assets/html/common/stats/workload-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*微博|微信舆情处理量分析*/
			'/tag-stats/:type': {
				templateUrl: 'assets/html/common/stats/tag-stats.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			/*微信自定义菜单点击量统计*/
			'/click_menu/:type': {
				templateUrl: 'assets/html/common/stats/click_menu.html',
				dependencies: [
					'common/StatsController',
					'common/HighChartService',
					'../lib/highcharts/highcharts'
				]
			},
			//统计部分END
			'/help/:rand': {
				templateUrl: 'assets/html/common/help.html',
				dependencies: [
					'common/HelpController'
				]
			},
			'/auto-top': {
				templateUrl: 'assets/html/common/auto-top.html',
				dependencies: [
					'common/AutoTopController'
				]
			},

			/* 微信待处理，切换到对话模式 */
			'/wx-categorized-talk/': {
				templateUrl: 'assets/html/mex/wx_categorized_talk.html',
				dependencies: [
					'mex/WeixinOperationController',
					'system/StaffService',
					'common/MediaService',
					'mex/CommunicationService',
					'common/UserService',
					'common/tag/TagService'
				]
			}

		}
	};
});
