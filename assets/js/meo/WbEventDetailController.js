'use strict';

define(['me'], function (me) {
	me.controller('WbEventDetailController', ['$scope', '$sce', '$http', 'Event', '$routeParams', function ($scope, $sce, $http, Event, $routeParams) {
		// 获取url参数
		$scope.id = $routeParams.id;
		$scope.type = $routeParams.type;
		$scope.url = "assets/html/meo/" + $scope.type + ".html";
		$scope.empty = 'loading...';

		$scope.initPage = function () {
			switch ($scope.type) 
			{
				case 'event-info' :
					$scope.get_info();
					break;
				case 'event-stats' :
					$scope.get_stats();
					break;
				case 'event-parti-list' :
					$scope.get_participants(0);
					break;
				case 'event-winner-list' :
					$scope.get_participants(1);
					break;
				case 'event-wave-stats' :
					$scope.get_info();
					break;
				default :
					$scope.get_info();
					break;
			}
			return ;
		}

		/* 活动信息标签页 */
		$scope.get_info = function () {
			$http.get(
				_c.appPath + 'meo/event/info/' + $scope.id
			).success(function(res){
				if (res.code == 200) {
					$scope.info = res.data;
				} else {
					$scope.empty = res.message || '无法获取活动信息！';
				}
			}).error(function(){
				$scope.empty = '无法获取活动信息！';
			});
		}
		/* 活动信息标签页END */

		/* 参与者/中奖者列表页面 */
		$scope.verifiedIcons = {
			'approve_co': 'assets/img/approve_co.png',
			'approve': 'assets/img/approve.png',
			'daren': 'assets/img/daren.png'
		}
		$scope.verifiedType = {
			'220': $scope.verifiedIcons.daren,
			'0': $scope.verifiedIcons.approve,
			'2': $scope.verifiedIcons.approve_co,
			'3': $scope.verifiedIcons.approve_co,
			'4': $scope.verifiedIcons.approve_co,
			'5': $scope.verifiedIcons.approve_co,
			'6': $scope.verifiedIcons.approve_co,
			'7': $scope.verifiedIcons.approve_co,
		}
		$scope.participants = {};
		$scope.parti_empty = '正在载入活动参与者信息...';
		$scope.parti_filter = {};
		$scope.get_participants = function (is_winner) {
			$scope.selectedIds = {};
			$http.post(
				_c.appPath + 'meo/event/get_participants/' + $scope.id, 
				{
					'current_page' : $scope.participants.current_page || 1,
					'items_per_page' : $scope.participants.items_per_page || 15, 
					'is_winner' : is_winner
				}
			).success(function(res){
				if (res.code == 200) {
					$scope.participants = res.data;
				} else {
					$scope.parti_empty = res.message || '没有获取到活动参与者！';
				}
			}).error(function(){
				$scope.parti_empty = '获取活动参与者失败！';
			});
		}

		$scope.selectedIds = {};
		$scope.pushWinner = function (id) 
		{
			if ($scope.selectedIds[id] == undefined) 
				$scope.selectedIds[id] = true;
			else 
				delete($scope.selectedIds[id]);
		}

		/* 全选操作 */
		$scope.selectAll = function () 
		{
			if ($.isEmptyObject($scope.selectedIds)) {
				for (var i in $scope.participants.participants) 
					$scope.selectedIds[$scope.participants.participants[i].id] = true;
			} else {
				$scope.selectedIds = {};
			}
			$scope.checkall = ( ! $.isEmptyObject($scope.selectedIds));
		}

		/* 设定中奖人 */
		$scope.setWinner = function () 
		{
			var ids = [];
			if ( ! $.isEmptyObject($scope.selectedIds)) 
			{
				for ( var i in $scope.selectedIds) 
					ids.push(i);
				$http.post(
					_c.appPath + 'meo/event/set_winner/' + $scope.id, 
					{ids:ids}
				).success(function(res){
					if (res.code == 200) {
						alert('中奖设定成功！');
						$scope.get_participants(0);
					} else {
						alert(res.message);
					}
				}).error(function(){
					alert('无法设置中奖者，请检查网络！');
				})
			}
		}

		/* 取消中奖人 */
		$scope.unsetWinner = function () 
		{
			var ids = [];
			if ( ! $.isEmptyObject($scope.selectedIds)) 
			{
				for ( var i in $scope.selectedIds) 
					ids.push(i);
				$http.post(
					_c.appPath + 'meo/event/unset_winner/' + $scope.id, 
					{ids:ids}
				).success(function(res){
					if (res.code == 200) {
						alert('移出操作成功！');
						$scope.get_participants(1);
					} else {
						alert(res.message);
					}
				}).error(function(){
					alert('无法执行移出操作，请检查网络！');
				})
			}
		}
		/* 参与者/中奖者列表页面END */

		/* 活动统计页面 */
		/**
		 * ==统计数据结果==
		 * timeline							// 活动参与时间线，以日为单位
		 * pushed							// 推送状态，(0, 1, 2)
		 * p_region, p_gender, p_vt			// 参与者地区，性别，身份统计
		 * unp_region, unp_gender, unp_vt	// 未参与者地区，性别，身份统计
		**/
		$scope.get_stats = function () 
		{
			$http.get(
				_c.appPath + 'meo/event/stats/' + $scope.id
			).success(function(res){
				if (res.code == 200) 
					$scope.darwCharts(res.data);
				else 
					$scope.stats_empty = res.message || 'Load Event Statistics Error !';
			}).error(function(){
				$scope.stats_empty = 'Can\'t Load Event Statistics !';
			});
		}
		/* 活动统计页面END */

		$scope.darwCharts = function (data) 
		{
			if ( ! typeof data == 'object')
				return false;

			/* 活动参与走势(以天为单位) */
			if (typeof data.timeline == 'object') {
				var chart_data = {
					title: '活动参与走势', 
					xAxis: {
						type: 'datetime',
						dateTimeLabelFormats: {month: '%e. %b',year: '%b'}
					},
					y_title: '人数',
					valueSuffix: '人',
					series: []
				}
				var participates = [];
				var reposts = [];
				var comments = [];
				if (data.timeline.participates.length > 0) {
					for (var i in data.timeline.participates) {
						var date = data.timeline.participates[i].date.split('-');
						participates.push([Date.UTC(date[0], parseInt(date[1])-1, date[2]), parseInt(data.timeline.participates[i].sum)]);
					}
				}
				if (data.timeline.reposts.length > 0) {
					for (var i in data.timeline.reposts) {
						var date = data.timeline.reposts[i].date.split('-');
						reposts.push([Date.UTC(date[0], parseInt(date[1])-1, date[2]), parseInt(data.timeline.reposts[i].sum)]);
					}
				}
				if (data.timeline.comments.length > 0) {
					for (var i in data.timeline.comments) {
						var date = data.timeline.comments[i].date.split('-');
						comments.push([Date.UTC(date[0], parseInt(date[1])-1, date[2]), parseInt(data.timeline.comments[i].sum)]);
					}
				}
				chart_data.series = [
					{name:'参与人数', data:participates}, 
					{name:'转发量', data:reposts}, 
					{name:'评论量', data:comments}
				];
				drawLineChart('#timelineChart', chart_data);
			}

			/* 推送状态比例饼图 */
			if (typeof data.pushed == 'object') {
				// 
			}

			/* 饼图[性别，身份] */
			var pie_chart_set = {
				p_gender: {
					target : '#gender_stats_p',
					key : 'gender',
					keys : {0:'未知', 1:'男', 2:'女'},
					title : '性别比例'
				}, 
				unp_gender: {
					target : '#gender_stats_unp',
					key : 'gender',
					keys : {0:'未知', 1:'男', 2:'女'},
					title: '性别比例'
				},
				p_vt: {
					target : '#vt_stats_p',
					key : 'vt',
					keys : {'-1':'普通人',0:'名人',1:'政府',2:'企业',3:'媒体',4:'校园',5:'网站',6:'应用',7:'机构/团体',8:'待审企业',10:'微博女郎',200:'初级',220:'中/高级达人',400:'已故用户'},
					title : '身份比例'
				}, 
				unp_vt: {
					target : '#vt_stats_unp',
					key : 'vt',
					keys : {'-1':'普通人',0:'名人',1:'政府',2:'企业',3:'媒体',4:'校园',5:'网站',6:'应用',7:'机构/团体',8:'待审企业',10:'微博女郎',200:'初级',220:'中/高级达人',400:'已故用户'},
					title: '身份比例'
				}
			};
			for (var i in pie_chart_set) {
				if (typeof data[i] == 'object') {
					var pieChartData = {
						title: pie_chart_set[i].title, 
						series : []
					};
					var series_data = [];
					for (var j in data[i]) 
						series_data.push([pie_chart_set[i].keys[data[i][j][pie_chart_set[i].key]], parseInt(data[i][j].num)]);
					pieChartData.series.push({ type: 'pie', name: '比例', data: series_data });
					drawPieChart(pie_chart_set[i].target, pieChartData);
				} else {
					$(pie_chart_set[i].target).html('没有统计数据！');
				}
			}

			/* 主要地区柱状图 */
			var column_chart_set = {
				p_region: {
					target : '#location_stats_p',
					key : 'province_code',
					keys : {0:'未知', 1:'男', 2:'女'},
					title : '地区分布'
				}, 
				unp_region: {
					target : '#location_stats_unp',
					key : 'province_code',
					keys : {0:'未知', 1:'男', 2:'女'},
					title: '地区分布'
				}
			};
			for (var i in column_chart_set) {
				if (typeof data[i] == 'object') {
					var columnChartData = {
						title: column_chart_set[i].title, 
						yAxis: { min: 0, title: { text: '用户量' }, allowDecimals: false }, 
						categories: [], 
						series : [{name:'人数', data:[]}]
					};
					var series_data = [];
					for (var j in data[i]) {
						columnChartData.categories.push($scope.region_names[data[i][j][column_chart_set[i].key]]);
						columnChartData.series[0].data.push(parseInt(data[i][j].num));
					}
					drawColumnChart(column_chart_set[i].target, columnChartData);
				} else {
					$(column_chart_set[i].target).html('没有统计数据！');
				}
			}
		}

		$scope.region_names = {34:'安徽',11:'北京',50:'重庆',35:'福建',62:'甘肃',44:'广东',45:'广西',52:'贵州',46:'海南',13:'河北',23:'黑龙江',41:'河南',42:'湖北',43:'湖南',15:'内蒙古',32:'江苏',36:'江西',22:'吉林',21:'辽宁',64:'宁夏',63:'青海',14:'山西',37:'山东',31:'上海',51:'四川',12:'天津',54:'西藏',65:'新疆',53:'云南',33:'浙江',61:'陕西',71:'台湾',81:'香港',82:'澳门',400:'海外',100:'其他'};

		/* 绘制曲线图 */
		var drawLineChart = function (target, data) 
		{
			$(target).highcharts({
				title: { text: data.title, x: -20 /*center*/},
				xAxis: data.xAxis,
				yAxis: { title: { text: data.y_title }, plotLines: [{ value: 0, width: 1, color: '#808080'}] },
				tooltip: { valueSuffix: data.valueSuffix },
				legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0},
				series: data.series,
				credits: { enabled: false }
			});
		}

		/* 绘制饼状图 */
		var drawPieChart = function (target, data) 
		{
			$(target).highcharts({
				chart: { plotBackgroundColor: null, plotShadow: false },
				title: { text: data.title },
				tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>' },
				plotOptions: { 
					pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: false }, showInLegend: true } 
				},
				series: data.series,
				credits: { enabled: false }
			});
		}

		/* 绘制柱状图 */
		var drawColumnChart = function (target, data) 
		{
			$(target).highcharts({
				chart: { type: 'column' },
				title: { text: data.title },
				xAxis: { categories: data.categories, labels: { rotation: 0 } },
				yAxis: { min: 0, title: { text: data.yaxis }, allowDecimals: false },
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y} 人</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: { column: { pointPadding: 0.2, borderWidth: 0 } },
				series: data.series,
				credits: { enabled: false }
			});
		}

		$scope.showColumnChart = function () {
			$('.location-column').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '地区分布'
				},
				xAxis: {
					categories: [
						'北京',
						'上海',
						'广州',
						'江苏',
						'河南'
					],
					labels: {
						rotation: 45
					}
				},
				yAxis: {
					min: 0,
					title: {
						text: '用户量'
					},
					allowDecimals: false
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y} 人</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '用户量',
					data: [10543, 9433, 6931, 3310, 1010]
				}],
				credits: {
					enabled: false
				}
			});
		}


	}]);
});