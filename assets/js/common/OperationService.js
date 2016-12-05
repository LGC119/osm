'use strict';

define(['me'], function (me) {
	// 获取所有账号
	me.factory('Operation', ['$resource', function ($resource){

		var url = _c.appPath + 'meo/communication/get/:type/:status/:keyword';
		return $resource(url, {}, {
			get_feeds : {
				method : 'GET',
				params : {
					'type' : 'mentions',
					'status' : 0
				}
			}
		});
	}]);

});