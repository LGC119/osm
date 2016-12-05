'use strict';

define(['me'], function (me) {
    me.factory('Tag', ['$resource', '$modal', function ($resource, $modal) {
        // 请求资源Resource
        var url = _c.appPath + 'common/tag/:action/';
        var resource = $resource(url, {}, {
            query: {
                method: 'GET',
                params: {
                    'action': 'index'
                }
            }, 
            query_sub: {
                method: 'GET',
                params: {
                    'action': 'index_sub'
                }
            },
            create: {
                method: 'POST',
                params: {
                    'action': 'create'
                }
            },
            create_sub: {
                method: 'POST',
                params: {
                    'action': 'create_sub'
                }
            },
            delete: {
                method: 'POST',
                params: {
                    'action': 'delete'
                }
            }
        });

        // 呈现标签选择模态框方法
        var showTagModal = function ($scope, tagModalInstanceCtrl, resolve) {  
            // 获取标签信息
            if (!$scope.tags || $scope.tags == undefined) { $scope.tags = resource.query(); } 

            // 处理resolve
            var defaultResolve = {
                tags: function () {
                    return $scope.tags;
                }
            }
            var resolve = angular.extend(defaultResolve, resolve);
            var modalOpt = {
                templateUrl: 'assets/html/common/tag/tag-choose-tpl.html',
                controller: tagModalInstanceCtrl,
                size: 'lg',
                resolve: resolve
            };
            var modalInstance = $modal.open(modalOpt);
        }

        // 呈现订阅标签选择模态框方法
        var showTagModal_sub = function ($scope, tagModalInstanceCtrl, resolve) {  
            console.log('test');
            // 获取标签信息
            if (!$scope.tags_sub || $scope.tags_sub == undefined) { $scope.tags_sub = resource.query_sub(); } 

            // 处理resolve
            var defaultResolve = {
                tags: function () {
                    return $scope.tags_sub;
                }
            }
            var resolve = angular.extend(defaultResolve, resolve);
            var modalOpt = {
                templateUrl: 'assets/html/common/tag/tag-choose-tpl.html',
                controller: tagModalInstanceCtrl,
                size: 'lg',
                resolve: resolve
            };
            var modalInstance = $modal.open(modalOpt);
        }


        return {
            resource: resource,
            showTagModal: showTagModal,
            showTagModal_sub: showTagModal_sub
        }     
    }]);
});