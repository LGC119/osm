'use strict';

define(['me'], function (me) {
	me.controller('DashboardController', ['$scope', 'Account', '$http', function ($scope, Account, $http) {
		$scope.wb_empty = $scope.wx_empty = '载入中...';

		Account.query(function (res) {
			if (res.code == 200) {
				if (res.data.wb_accounts.length > 0) 
						$scope.wb_accounts = res.data.wb_accounts;
					else 
						$scope.wb_empty = '没有绑定微博账号';

				if (res.data.wx_accounts.length > 0)
						$scope.wx_accounts = res.data.wx_accounts;
					else
						$scope.wx_empty = '没有绑定微信账号';

			} else {
				$scope.wb_empty = res.message;
				$scope.wx_empty = res.message;
			}
		});

		// 顶部统计点击切换方法
		$scope.switchChart = function (statsUrl, fn) {
			$scope.chartEmpty = '正在载入数据...';
			$scope.statsUrl = statsUrl;
			$scope[fn]();
		}

		// 图表统计部分
		$scope.getColumnChart = function () {
			$http({
				method: 'GET',
				url: _c.appPath + $scope.statsUrl
			}).success(function (data) {
				if (data.code == 200) {
					$scope.statsData = data.data;
					$scope.showEventsTable = false;
					$scope.showColumn = true;
					$scope.showColumnChart();
					$scope.chartEmpty = '';
				} else {
					$scope.showColumn = false;
					$scope.chartEmpty = '暂无相关统计信息';
				}
			});
		}
		$scope.showColumnChart = function (chart_type) {
			var chart_type = chart_type || 'column';
			$('.charts').highcharts({
				chart: {
					type: chart_type
				},
				title: {
					text: $scope.statsData.xtitle
				},
				xAxis: {
					categories: $scope.statsData.x,
					labels: {
						rotation: chart_type == 'column' ? -45 : 0
					}
				},
				yAxis: {
					min: 0,
					title: {
						text: $scope.statsData.ytitle
					},
					labels : {
						enabled : chart_type == 'column' ? true : false
					},
					allowDecimals: false
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0"></td>' +
						'<td style="padding:0"><b>{point.y} 次</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					enabled: chart_type == 'column' ? true : false,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: $scope.statsData.data_title,
					data: $scope.statsData.y
				}],
				credits: {
					enabled: false
				}
			});
		}

		// 活动列表
		$scope.getEvents = function () {
			$http({
				method: 'POST',
				url: _c.appPath + $scope.statsUrl,
				data: {
					current_page: 1,
					items_per_page: 5
				}
			}).success(function (data) {
				if (data.code == 200) {
					$scope.events = data.data.events;
					$scope.eventsEmpty = '';
				} else {
					$scope.eventsEmpty = '暂无活动记录';
				}
				$scope.chartEmpty = '';
				$scope.showEventsTable = true;
				$scope.showColumn = false;
			});
		}

		// 标签列表
		$scope.getHotTags = function () {
			$scope.hotTags = {};
			$http({
				method: 'GET',
				url: _c.appPath + $scope.statsUrl
			}).success(function (res) {
				/* 获取热门标签排序柱状图 */
				if (res.code == 200) 
				{
					/* 获取格式化数据 */
					// $scope.hotTags = 
					var tags = res.data.trigger;
					$scope.statsData.data_title = '热门标签排行';
					$scope.statsData.xtitle = '标签触发量';
					$scope.statsData.ytitle = '';
					$scope.statsData.x = [];
					$scope.statsData.y = [];
					for (var i in tags) 
					{
						$scope.statsData.x.push(tags[i].tag_name);
						$scope.statsData.y.push(parseFloat(tags[i].num));
					}
					// console.log($scope.statsData);
					$scope.chartEmpty = '';
					$scope.showEventsTable = false;
					$scope.showColumn = true;
					$scope.showColumnChart('bar');
				}
			}).error(function(){
				$scope.chartEmpty = '无法获取热门标签统计数据！';
			});
		}
		
	}]);
});