'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('Rule', ['$resource', function ($resource){
        var wx_url = _c.appPath + 'mex/rule/:action/:type:aid/:app_id';
        var wb_url = _c.appPath + 'meo/rule/:action/:type:aid/:app_id';
        var wx_res = $resource(wx_url, {}, {
            selectRules : {
                method : 'GET',
                params : {
                    'action' : 'select_rule',
                    'metype' : ''
                }
            },
            select_other : {
                method : 'GET',
                params : {
                    'action' : 'select_other',
                    'metypd' : ''
                }
            }
        });

        var wb_res = $resource(wb_url, {}, {
            selectRules : {
                method : 'GET',
                params : {
                    'action' : 'select_rule',
                    'metype' : ''
                }
            },
            select_other : {
                method : 'GET',
                params : {
                    'action' : 'select_other',
                    'metypd' : ''
                }
            }
        });

        return {
            wxres:wx_res,
            wbres:wb_res
        };
	}]);

});
