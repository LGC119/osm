'use strict';

define(['me'], function (me) {
    me.controller('TwodcodeController', ['$scope', '$http', '$sce', '$location', 'TwodcodeService',function($scope, $http, $sce, $location, TwodcodeService) {
        $scope.twodcodeData = {};
        $scope.twodcodeData.data = {};
        $scope.code = {
        };
        $scope.filterData = {
        };
        //获取二维码列表
        $scope.get_twodcode_data = function () {
            $scope.twodcodeData = TwodcodeService.get_list({
                current_page: $scope.twodcodeData.data.current_page || 1,
                items_per_page: 12,
                title: $scope.filterData.title || '',
                category: $scope.filterData.category || ''
            });
        }
        $scope.get_twodcode_data();
        //创建二维码弹窗
        $scope.createBox = function(){
            $scope.code = {};
            $("#createBox").modal('show');
        }

        //创建下拉框类型选择
        $scope.category_data = [{
            id:1,
            categoryName:'门店'
        },{
            id:2,
            categoryName:'活动'
        }];

        //过滤下拉框类型选择
        $scope.filterData.category_filter = [{
            id:1,
            categoryName:'门店'
        },{
            id:2,
            categoryName:'活动'
        }];

        //删除二维码弹窗
        $scope.deletecodeBox = function(id){
            $("#deletecodeBox").modal('show');
            $scope.code_id = id;
        }

        // 确定创建二维码
        $scope.createCfm = function(){
            // console.log($scope.code);
            if (!$scope.code.category) {
                $.gritter.add({
                    title: '请选择二维码类型!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            };
            if (!$scope.code.title) {
                $.gritter.add({
                    title: '请填写名称!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            };
            $http.post(
                _c.appPath+'common/twodcode/create',
                $scope.code,
                {
                    headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest : function (data) {return $.param(data);}
                }
            ).success(function(res){
                   // console.log(res);
                    if(res.code == '200'){
                        $.gritter.add({
                            title: '创建成功!',
                            time:'1000',
                            class_name:'gritter-success gritter-center'
                        });
                        $("#createBox").modal('hide');
                        $scope.get_twodcode_data();
                    }else{
                        $.gritter.add({
                            title: '创建失败!',
                            time:'2000',
                            class_name:'gritter-error gritter-center'
                        });
                        $("#createBox").modal('hide');
                    }
                })
        }

        //删除确认二维码
        $scope.deleteCfm = function () {  
            if ($scope.code_id) {
                $http.post(
                    _c.appPath+'common/twodcode/delete',{
                        id:$scope.code_id
                    },
                    {
                        headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                        transformRequest : function (data) {return $.param(data);}
                    }
                ).success(function(res){
                    if(res.code == '200'){
                            $.gritter.add({
                                title: '删除成功!',
                                time:'1000',
                                class_name:'gritter-success gritter-center'
                            });
                            $("#deletecodeBox").modal('hide');
                            $scope.get_twodcode_data();
                        }else{
                            $.gritter.add({
                                title: '删除失败!',
                                time:'2000',
                                class_name:'gritter-error gritter-center'
                            });
                            $("#deletecodeBox").modal('hide');
                        }
                })
            }else{
                $.gritter.add({
                    title: '数据不存在!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            }
        }

    }]);
});

