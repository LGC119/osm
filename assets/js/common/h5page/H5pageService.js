'use strict';

define(['me'], function (me) {
    me.factory('H5page', ['$resource', function ($resource) {
        var url = _c.appPath + 'h5page/h5page/:action';
        return $resource(url, {}, {
            pagesList: {
                method: 'GET',
                params: {
                    'action': 'pages_list'
                }
            },
            create: {
                method: 'POST',
                params: {
                    'action': 'create'
                }
            },
            delete: {
                method: 'GET',
                params: {
                    'action': 'delete'
                }
            }
        });
    }]);
});