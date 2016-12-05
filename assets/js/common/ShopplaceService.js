'use strict';

define(['me'], function (me) {
	me.factory('ShopplaceService', ['$resource', '$modal', function ($resource, $modal){
		var url = _c.appPath + 'common/shopplace/:action';
        return $resource(url, {}, {//ajax请求后台接口
            get_list: {
                method: 'GET',
                params: {
                    action: 'get_shopplace_data'
                }
            }

        });	}]);

});