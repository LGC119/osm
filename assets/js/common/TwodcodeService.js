'use strict';

define(['me'], function (me) {
	me.factory('TwodcodeService', ['$resource', '$modal', function ($resource, $modal){
		var url = _c.appPath + 'common/twodcode/:action';
        return $resource(url, {}, {//ajax请求后台接口
            get_list: {
                method: 'GET',
                params: {
                    action: 'get_twodcode_data'
                }
            }

        });	}]);

});