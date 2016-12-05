'use strict';

define(['me'], function (me) {
    me.controller('TwodcodedetailController', 
    ['$scope', '$http', '$sce', '$location', '$routeParams','TwodcodedetailService',
    function($scope, $http, $sce, $location, $routeParams, TwodcodedetailService) {
    
        $scope.code_id = $routeParams.code_id;
        $scope.years = [{
            y:2013
        },{
            y:2014
        },{
            y:2015
        },{
            y:2016
        }];
        $scope.years_num = 2014;
        //获取二维码详情
        $scope.get_code_data = function(){
            TwodcodedetailService.get_code_list({
                'code_id':$scope.code_id
            },function (data){
                $scope.twodcodedetailData = data;
            });
        }
        $scope.get_code_data();

        //highchart展示
        $scope.show_highchart = function(data){
            $(data['url']).highcharts({
                title: {
                    text: data['title'],
                    x: -20 //center
                },
                subtitle: {
                    text: '',
                    x: -20
                },
                xAxis: {
                    categories: ['一月', '二月', '三月', '四月', '五月', '六月',
                        '七月', '八月', '九月', '十月', '十一月', '十二月']
                },
                yAxis: {
                    title: {
                        text: '人数 (个)'
                    },
                    plotLines: [{
                        value: 0,
                        width: 1,
                        color: '#808080'
                    }]
                },
                tooltip: {
                    valueSuffix: '个'
                },
                legend: {
                    layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'middle',
                    borderWidth: 0
                },
                series: [{
                    name: '扫描人数',
                    data: data['chart_created']
                }]
            });
        }

        //获取数据
        $scope.chart_data = [];
        $scope.get_user_data = function(){
            TwodcodedetailService.get_user_list({
                'code_id':$scope.code_id,
                'years_num':$scope.years_num
            },function (data){
                $scope.twodcodeuserData = data;
                if (data.code == 200) {
                    $scope.chart_data['chart_created'] = data.data.chart_created;
                    $scope.chart_data['url'] = '#chart_created';
                    $scope.chart_data['title'] = '用户扫描时间统计';
                    $scope.show_highchart($scope.chart_data);
                }
            });
        }
        $scope.get_user_data();

        
    }]);
});

