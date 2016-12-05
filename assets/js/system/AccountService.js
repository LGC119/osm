'use strict';

define(['me'], function (me) {
	// 获取所有账号
	me.factory('Account', ['$resource', function ($resource) {

		var url = _c.appPath + 'system/account/:action/:type/:aid/:app_id';
		return $resource(url, {}, {
			query: {
				method: 'GET',
				params: {
					'action': 'get_all_accounts'
				} 
			}
		});
	}]);

});