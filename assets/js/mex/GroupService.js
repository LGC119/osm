'use strict';

define(['me'], function (me) {
	// 获取分组信息
	me.factory('WeixinUserGroup', ['$resource', function ($resource){
		var url = _c.appPath + 'mex/group/:action';
		return $resource(url, {}, {
            // 获取所有分组信息
            getList : {
                method : 'GET',
                params : {
                    'action' : 'select_groups'
                }
            }, 
            // 获取指定分组信息
            getFilterGroup : {
                method : 'GET',
                params : {
                    'action' : 'select_group_by_id'
                }
            },
            getAll: {
                method: 'GET',
                params: {
                    'action': 'get_all_groups'
                }
            },
            get_group_statistics: {
                method: 'GET',
                params: {
                    'action': 'get_group_statistics'
                }
            },
            create: {
                method: 'POST',
                params: {
                    action: 'insert_group'
                }
            }
		});
	}]);
});