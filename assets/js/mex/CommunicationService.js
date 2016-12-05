'use strict';

define(['me'], function (me) {
	// 获取微信FEED
	me.factory('WxCommunication', ['$resource', function ($resource){

		var url = _c.appPath + 'mex/communication/:action/:status/:keyword';
		return $resource(url, {}, {
			get_feeds : {
				method : 'GET',
				params : {
                    'action' : 'get',
					'status' : 0
				}
			},
            get_users: {
				method : 'GET',
				params : {
                    'action' : 'get_user',
					'status' : 0
				}
            } 
		});
	}]);

});
