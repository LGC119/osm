'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('Umenu', ['$resource', function ($resource){
		var url = _c.appPath + 'mex/umenu/:action';
		return $resource(url, {}, {
            // 获取所有规则信息
            select_umenu : {
                method : 'GET',
                params : {
                    'action' : 'select_umenu'
                }
            },
            select_umenu_data : {
                method : 'GET',
                params : {
                    'action' : 'get_key_content'
                }
            }
		});
	}]);

});