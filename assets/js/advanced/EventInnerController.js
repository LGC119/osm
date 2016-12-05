'use strict';

define(['me'], function (me) {
	me.controller('AdvEventInnerController', ['$scope', '$sce', '$http', 'Event', '$routeParams', function ($scope, $sce, $http, Event, $routeParams) {
		/*// 获取url参数
		$scope.id = $routeParams.id;
		$scope.type = $routeParams.type;
		$scope.url = 'assets/html/advanced/' + $scope.type + '.html'*/

		$scope.showLineChart = function () {
            $('.event-trend').highcharts({
                title: {
                    text: '活动参与走势',
                    x: -20 //center
                },
                xAxis: {
                    categories: ['1', '2', '3', '4', '5',
                        '6', '7', '8', '9', '10', '11', '12', 
                        '13', '14', '15', '16', '17', '18', 
                        '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30']
                },
                yAxis: {
                    title: {
                        text: '人数'
                    },
                    plotLines: [{
                        value: 0,
                        width: 1,
                        color: '#808080'
                    }]
                },
                tooltip: {
                    valueSuffix: '人'
                },
                legend: {
                    layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'middle',
                    borderWidth: 0
                },
                series: [{
                    name: '微博参与量',
                    data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6, 10.2, 10.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5, 0, 0, 0, 0, 0, 0]
                }, {
                    name: '微信参与量',
                    data: [0.2, 0.8, 1.7, 1.3, 2.0, 2.0, 4.8, 6.1, 9.1, 14.1, 16.6, 19.5, 26.5, 23.3, 18.3, 13.9, 9.6, 11.2, 12.8, 15.7, 18.3, 17.0, 22.0, 4.8, 0, 0, 0, 0, 0, 0]
                }, {
                    name: 'APP参与量',
                    data: [0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 19.0, 13.9, 11.0, 17.0, 16.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6, 0, 0, 0, 0, 0, 0]
                }/*, {
                    name: '微信量',
                    data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 16.6, 14.8, 15.7, 17.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 18.6, 21.5, 16.5, 3.3]
                }*/],
                credits: {
                    enabled: false
                }
            });
        }

        /*$scope.showPieChart();
        $scope.showColumnChart();*/
        // $scope.showLineChart();
	}]);
});