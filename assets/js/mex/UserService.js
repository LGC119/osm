'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('WeixinUser', ['$resource', function ($resource){
		var url = _c.appPath + 'mex/user/:action/';
		return $resource(url, {}, {
            // 获取所有规则信息
            select_user : {
                method : 'GET',
                params : {
                    'action' : 'select_user'
                }
            },
            userIntoGroup: {
                method: 'POST',
                params: {
                    action: 'user_in_group'
                }
            }
		});
	}]);

});