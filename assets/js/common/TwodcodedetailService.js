'use strict';

define(['me'], function (me) {
	me.factory('TwodcodedetailService', ['$resource', '$modal', function ($resource, $modal){
		var url = _c.appPath + 'common/twodcode/:action';
        return $resource(url, {}, {//ajax请求后台接口
            get_code_list: {
                method: 'GET',
                params: {
                    action: 'get_code_by_id'
                }
            },
            get_user_list: {
                method: 'GET',
                params: {
                    action: 'get_user_list'
                }
            }

        });	
    }]);

});