'use strict';

define(['me'], function (me) {
    

    me.controller('TagController', ['$scope', 'Tag', '$modal', '$http', function ($scope, Tag, $modal, $http) {
        // 新增子标签的输入框控制数组
        $scope.newTagInputShown = [];
        // 新增订阅子标签的输入框控制数组
        $scope.newTagInputShown_sub = [];

        // 获取公司下所有标签列表
        
        function getAllTags () {
            Tag.resource.query(function (data) {
                var data = data.data;
                var tags = [];
                for (var i = 0; i < data.length; ++i) {
                    if (i % 2 === 0) {
                        var row = [];
                        var n = tags.push(row) - 1;
                        tags[n].push(data[i]);
                        if (typeof(data[i + 1]) != 'undefined') {
                            tags[n].push(data[i + 1]);
                        }
                    }
                }
                $scope.tags = tags;

            });
        };
        getAllTags();

        // 获取公司下所有订阅标签列表
        
        function getAllTags_sub () {
            Tag.resource.query_sub(function (data) {
                var data = data.data;
                var tags_sub = [];
                for (var i = 0; i < data.length; ++i) {
                    if (i % 2 === 0) {
                        var row = [];
                        var n = tags_sub.push(row) - 1;
                        tags_sub[n].push(data[i]);
                        if (typeof(data[i + 1]) != 'undefined') {
                            tags_sub[n].push(data[i + 1]);
                        }
                    }
                }
                $scope.tags_sub = tags_sub;

            });
        };
        getAllTags_sub();

        // 删除子标签弹出框（参考原来代码）
        $scope.showDelConfirm = function (tagId, tagName) {
            $scope.tagName = tagName;
            var modalInstance = $modal.open({
                templateUrl: 'delConfirmModal.html',
                controller: delModalInstance,
                size: 'sm',
                resolve: {
                    tagName: function () {
                        return tagName;
                    },
                    tagId: function () {
                        return tagId;
                    }
                }
            });
        }

        // 删除确认弹出框控制器
        var delModalInstance = ['$scope', '$modalInstance', 'Tag', 'tagName', 'tagId', function ($scope, $modalInstance, Tag, tagName, tagId) {
            $scope.tagName = tagName;
            $scope.cancel = function () {
                $modalInstance.close();
            }
            // 确认删除
            $scope.ok = function () {
                Tag.resource.delete({
                    'id': tagId
                }, function (data) {
                    if (data.code == 200) {
                        var className = 'gritter-success gritter-center';
                        // 重新载入所有标签
                        getAllTags();
                        getAllTags_sub();
                        $modalInstance.close();
                    } else {
                        var className = 'gritter-warning gritter-center';
                    }
                    $.gritter.add({ 
                        title : data.message, 
                        text : data.message, 
                        time : 2000, 
                        class_name : className 
                    });
                });
            }
        }];

        // 显示增加子标签input框
        $scope.showNewTagInput = function (ptagId) {
            // 当前操作的标签群中的输入框显示
            $scope.newTagInputShown[ptagId] = !$scope.newTagInputShown[ptagId];
            // 其他输入框隐藏
            for (var i in $scope.newTagInputShown) {
                if (i != ptagId) {
                    $scope.newTagInputShown[i] = false;
                }
            }
        }

        // 显示增加子标签input框
        $scope.showNewTagInput_sub = function (ptagId) {
            // 当前操作的标签群中的输入框显示
            $scope.newTagInputShown_sub[ptagId] = !$scope.newTagInputShown_sub[ptagId];
            // 其他输入框隐藏
            for (var i in $scope.newTagInputShown_sub) {
                if (i != ptagId) {
                    $scope.newTagInputShown_sub[i] = false;
                }
            }
        }

        // 添加标签
        $scope.addTag = function (ptagId, newTagName) {
            var newTagName = $.trim(newTagName);
            // 判断标签名是否为空
            if (!newTagName) {
                $.gritter.add({ 
                    title : '注意', 
                    text : '请填写标签名', 
                    time : 2000, 
                    class_name : 'gritter-warning gritter-center'
                });
                return false;
            }
            var newTag = Tag.resource.create({
                'pid': ptagId,
                'name': newTagName
            }, function (data) {
                if (data.code == 200) {
                    var title = '添加标签成功';
                    var className = 'gritter-success gritter-center';
                    // 重新载入所有标签
                    getAllTags();
                    $scope.newPtagName = '';
                    $scope.newTagName = '';
                } else {
                    var title = '添加标签失败';
                    var className = 'gritter-warning gritter-center';
                }
                $.gritter.add({ 
                    title : title, 
                    text : data.message, 
                    time : 2000, 
                    class_name : className 
                });
            });
        }

        // 添加订阅标签
        $scope.addTag_sub = function (ptagId_sub, newTagName_sub) {
            var newTagName_sub = $.trim(newTagName_sub);
            // 判断标签名是否为空
            if (!newTagName_sub) {
                $.gritter.add({ 
                    title : '注意', 
                    text : '请填写标签名', 
                    time : 2000, 
                    class_name : 'gritter-warning gritter-center'
                });
                return false;
            }
            var newTag_sub = Tag.resource.create_sub({
                'pid': ptagId_sub,
                'name': newTagName_sub
            }, function (data) {
                if (data.code == 200) {
                    var title = '添加标签成功';
                    var className = 'gritter-success gritter-center';
                    // 重新载入所有标签
                    getAllTags_sub();
                    $scope.newPtagName_sub = '';
                    $scope.newTagName_sub = '';
                } else {
                    var title = '添加标签失败';
                    var className = 'gritter-warning gritter-center';
                }
                $.gritter.add({ 
                    title : title, 
                    text : data.message, 
                    time : 2000, 
                    class_name : className 
                });
            });
        }
    }]);
});