'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('User', ['$resource', '$modal', function ($resource, $modal){

        // 请求资源Resource
		var url = _c.appPath + 'common/user/:action';
        var resource = $resource(url, {}, {
            show: {
                method: 'GET',
                params: {
                    'action': 'show',
                    'type': '',
                    'id': ''
                }
            },
            edit: {
                method: 'POST',
                params: {
                    'action': 'edit'
                }
            },
            // 获取标签对应名称
            getTagIdName:{
                method:'GET',
                params:{
                    'action':'tagid_to_name'
                }
            },
            // 获取用户对应的组
            getGroupIdName:{
                method:'GET',
                params:{
                    'action':'user_to_group'
                }
            }


        });

        // 呈现用户信息模态框方法
        var showUserModal = function ($scope, UserModalInstanceCtrl, resolve, type, snsId) {
            resource.show({
                type: type,
                id: snsId
            }, function (data) {
                $scope.userData = data.data;
                // 处理resolve
                var defaultResolve = {
                    userData: function () {
                        return $scope.userData;
                    },
                    type: function () {
                        return type;
                    },
                    id:function () {
                        return snsId;
                    }
                }
                var r = angular.extend(defaultResolve, resolve);
                var modalOpt = {
                    templateUrl: type == 'weibo' ? 'assets/html/common/weibo-user-tpl.html' : 'assets/html/common/user-info-tpl.html',
                    controller: UserModalInstanceCtrl,
                    size: 'lg',
                    resolve: r
                };
                var modalInstance = $modal.open(modalOpt);


            });


            
        }


		return {
            resource: resource,
            showUserModal: showUserModal
        }
	}]);

});