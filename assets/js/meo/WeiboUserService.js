'use strict';

define(['me'], function (me) {
	// 获取所有账号
	me.factory('WeiboUser', ['$resource', function ($resource) {

		var url = _c.appPath + 'meo/wb_user/:action';
		return $resource(url, {}, {
			usersList: {
				method: 'GET',
				params: {
					action: 'get_list'
				}
			}
		});

		
	}]);

});