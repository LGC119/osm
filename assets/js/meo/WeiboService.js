'use strict';

define(['me'], function (me) {
	// 获取所有账号
	me.factory('Weibo', ['$resource', function ($resource) {

		var url = _c.appPath + 'meo/weibo/:action';
		return $resource(url, {}, {
			get_feeds: {
				method : 'GET',
				params : {
					
				}
			},
			getRepostDataByLink: {
				method: 'GET',
				params: {
					action: 'get_repost',
					weibo_url: ''
				}
			},
			getShortUrl: {
				method: 'GET',
				params: {
					action: 'get_shorturl',
					url: ''
				}
			},
			create: {
				method: 'POST',
				params: {
					action: 'send_status'
				}
			},
			friendsTimeline: {
				method: 'GET',
				params: {
					action: 'get_friends_timeline'
				}
			},
			userTimeline: {
				method: 'GET',
				params: {
					action: 'get_user_timeline'
				}
			},
			filterTimeline: {
				method: 'POST',
				params: {
					action: 'get_timeline_filter'
				}
			}
		});
	}]);

});