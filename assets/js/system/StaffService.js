'use strict';

define(['me'], function (me) {
    // 获取员工相关信息
    me.factory('Staff', ['$resource', function ($resource) {
        var resource = $resource('', {}, {
            getStaffList : {
                method : 'GET',
                url : _c.appPath + 'system/staff'
            },
            onlineGetStaffList : {
                method : 'GET',
                url : _c.appPath + 'system/staff/onlineGetStaffList'
            },
            getPositionList : {
                method : 'GET',
                url : _c.appPath + 'system/position'
            }
        });

        return resource;
    }]);
});

