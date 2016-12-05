'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('SendallList', ['$resource', function ($resource){
		var url = _c.appPath + 'mex/send/:action';
		return $resource(url, {}, {
            // 获取所有规则信息
            get_send_list : {
                method : 'GET',
                params : {
                    'action' : 'get_send_list'
                }
            }
		});
	}]);

});