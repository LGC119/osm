'use strict';

define(['me'], function (me) {
    me.controller('SendallListController', ['$scope','$http','$modal','SendallList', function ($scope,$http,$modal,SendallList) {
        // 初始化
        $scope.params = {};
        $scope.common = {};
        $scope.common.minDate = new Date();
        // 设置时间调整步进
        $scope.common.hstep = 1;
        $scope.common.mstep = 5;

        $scope.event = {};
        $scope.event.type ={
            0:'默认',
            1:'抽奖',
            2:'线下',
            3:'调查',
            4:'会员绑定'
        };
        $scope.event.industry ={
            0:'默认',
            1:'快消',
            2:'汽车',
            3:'数码'
        };
        $scope.sendType = {
            'image':'图片',
            'news' :'图文',
            'articles':'多图文',
            'text' :'文本',
            'voice':'语音'
        }


        // 读取
        $scope.get_list = function(){
            $scope.params.page = $scope.params.page || 1;
            $scope.params.perpage = $scope.params.perpage || 10;
            SendallList.get_send_list($scope.params,function(res){
                if(res.code == 200){
                    if(typeof res.data.list != 'undefined'){
                        for(var i=0;i<res.data.list.length;i++){
//                            console.log(res.data.list[i])
                               if(res.data.list[i]['msgtype'] == 'news'){
                                   for(var j=0;j<res.data.list[i]['data'].length;j++){
                                       res.data.list[i]['data'][j]['url'] = res.data.list[i]['data'][j]['content_source_url'].split("id=")[1];
                                   }
                               }
                               if(res.data.list[i]['msgtype'] == 'articles'){
//                                   console.log(res.data.list[i])
                                   for(var j=0;j<res.data.list[i]['data'].length;j++){
                                       res.data.list[i]['data'][j]['url'] = res.data.list[i]['data'][j]['content_source_url'].split("id=")[1];
                                   }
                               }
                        }
                    }
                    $scope.sendList = res.data.list;
//                    console.log($scope.sendList)
                    $scope.params.page = res.data.current_page;
                    $scope.params.items_per_page = res.data.items_per_page;
                    $scope.params.sum = res.data.total_number;
                }
            })
        }
        $scope.get_list();

        // 删除
        $scope.delete = function(send_id){
            var modalInstance = $modal.open({
                templateUrl: 'delete-modal',
                controller: delModalInstanceCtrl,
                size: 'sm',
                resolve: {
                    send_id: function () {
                        return send_id;
                    },
                    get_list: function(){
                        return $scope.get_list;
                    }
                }
            });
        }
        var delModalInstanceCtrl = ['$scope', '$modalInstance', 'send_id','get_list', function ($scope, $modalInstance,send_id,get_list) {
            $scope.delete_ok = function () {
                $modalInstance.close();
                $http.post(
                    _c.appPath+'mex/send/delete_send',
                    {
                        send_id:send_id
                    }
                ).success(function(data){
                        if(data.code == 200){
                            $.gritter.add({
                                title: '删除成功!',
                                time:'500',
                                class_name:'gritter-success'
                            });
                        }else{
                            $.gritter.add({
                                title: '删除失败!',
                                time:'1000',
                                class_name:'gritter-error'
                            });
                        }
                        get_list();

                    }).error(function(){

                    });
            };
            $scope.cancel = function () {
                $modalInstance.close();
            };
        }];

        // 修改
        $scope.edit = function(send_id,exec_time){
            var modalInstance = $modal.open({
                templateUrl: 'edit-modal',
                controller: editModalInstanceCtrl,
                size: 'md',
                resolve: {
                    send_id: function () {
                        return send_id;
                    },
                    get_list: function(){
                        return $scope.get_list;
                    },
                    dt:function(){
                        return exec_time;
                    },
                    common:function(){
                        return $scope.common;
                    }
                }
            });
        }
        var editModalInstanceCtrl = ['$scope', '$modalInstance', 'send_id','get_list','dt','common', function ($scope, $modalInstance,send_id,get_list,dt,common) {
            $scope.common = common;
            $scope.common.dt = dt;
            $scope.edit_ok = function () {
                $modalInstance.close();
                var exec_time = $scope.common.dt.getFullYear() + '-'
                    + ($scope.common.dt.getMonth() + 1) + '-'
                    + $scope.common.dt.getDate() + ' '
                    + $scope.common.dt.getHours() + ':'
                    + $scope.common.dt.getMinutes() + ':'
                    + $scope.common.dt.getSeconds();
                $http.post(
                    _c.appPath+'mex/send/update_send',
                    {
                        send_id:send_id,
                        exec_time:exec_time
                    }
                ).success(function(data){
                        if(data.code == 200){
                            $.gritter.add({
                                title: '修改成功!',
                                time:'500',
                                class_name:'gritter-success'
                            });
                        }else{
                            $.gritter.add({
                                title: '修改失败!',
                                time:'1000',
                                class_name:'gritter-error'
                            });
                        }
                        get_list();

                    }).error(function(){

                    });
            };
            $scope.cancel = function () {
                $modalInstance.close();
            };
        }];

        // 统计
        $scope.statistics = function(send_id){
            var modalInstance = $modal.open({
                templateUrl: 'info-modal',
                controller: infoModalInstanceCtrl,
                size: 'lg',
                resolve: {
                    send_id: function () {
                        return send_id;
                    }
                }
            });
        }
        var infoModalInstanceCtrl = ['$scope', '$modalInstance','send_id', function ($scope, $modalInstance,send_id) {
            $scope.send_id = send_id;
            // 地区访问量
            $http.post(
                _c.appPath+'mex/send_stat/get_area',
                {
                    send_id:$scope.send_id
                }
            ).success(function(data){
                var valueData = [];
                for(var i=0;i<data.data.length;i++){
                    if(typeof valueData[i] == 'undefined'){
                        valueData[i] = [];
                    }
                    valueData[i].push(data.data[i].area);
                    valueData[i].push(parseInt(data.data[i].num));
                }
                // 地区访问量
                $('#container1').highcharts({
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: '地区访问量统计'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.y}</b>'
                    },
                    credits:false,
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                color: '#000000',
                                connectorColor: '#000000',
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    series: [{
                        type: 'pie',
                        name: '访问量',
                        data:valueData
                    }]
                });
            }).error(function(){

            });
            // 性别访问量
            $http.post(
                _c.appPath+'mex/send_stat/get_sex',
                {
                    send_id:$scope.send_id
                }
            ).success(function(data){
                var valueData = [];
                for(var i=0;i<data.data.length;i++){
                    if(typeof valueData[i] == 'undefined'){
                        valueData[i] = [];
                    }
                    var sex = data.data[i].sex == '1' ? '男' : (data.data[i]=='2' ? '女' : '未知');
                    valueData[i].push(sex);
                    valueData[i].push(parseInt(data.data[i].num));
                }
                // 地区访问量
                $('#container2').highcharts({
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: '性别访问量统计'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.y}</b>'
                    },
                    credits:false,
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                color: '#000000',
                                connectorColor: '#000000',
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    series: [{
                        type: 'pie',
                        name: '访问量',
                        data:valueData
                    }]
                });
            }).error(function(){

            });
            // 时间访问量
            $http.post(
                _c.appPath+'mex/send_stat/get_time',
                {
                    send_id:$scope.send_id
                }
            ).success(function(data){
                var valueData = [];
                for(var i=0;i<data.data.length;i++){
                    if(typeof valueData['created_at'] == 'undefined'){
                        valueData['created_at'] = [];
                        valueData['num'] = [];
                    }
                    valueData['created_at'].push(data.data[i].created_at);
                    valueData['num'].push(parseInt(data.data[i].num));
                }
                // 时间访问量
                    $('#container3').highcharts({
                        title: {
                            text: '不同时间段的访问量',
                            x: -20 //center
                        },
                        subtitle: {
                            text: '',
                            x: -20
                        },
                        xAxis: {
                            categories: valueData['created_at']
                        },
                        credits:false,
                        yAxis: {
                            title: {
                                text: '访问量'
                            },
                            plotLines: [{
                                value: 0,
                                width: 1,
                                color: '#808080'
                            }]
                        },
                        tooltip: {
                            valueSuffix: ''
                        },
                        legend: {
                            layout: 'vertical',
                            align: 'right',
                            verticalAlign: 'middle',
                            borderWidth: 0
                        },
                        series: [{
                            name: '访问量',
                            data: valueData['num']
                        }]
                    });
            }).error(function(){

            });

            $scope.info_ok = function(){
//                console.log(1);
                $modalInstance.close();
            };
            $scope.info_cancel = function(){
//                console.log(2);
//                $modalInstance.close();
            }

        }];

    }]);
});

