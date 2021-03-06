'use strict';

define(['me'], function (me) {
	me.controller('StatsController', ['$scope', '$sce', '$http', '$routeParams', 'Account', 'HighChartService', function ($scope, $sce, $http, $routeParams, Account, HCS) {

		$scope.dataSource = $routeParams.type == 'meo' ? 'wb' : 'wx';
		$scope.statsEmpty = '统计数据载入中...';

		/**
		 * 获取统计源数据
		 */
		$scope.getStatsData = function (url, callback)
		{
			var aid = $scope.selectedAccount;
			var start = formatDate($scope.startDt);
			var end = formatDate($scope.endDt);

			$http.get(
				_c.appPath + url + '?' + $scope.dataSource + '_aid=' + aid + '&start=' + start + '&end=' + end
			).success(callback).error(function(){
				$scope.statsEmpty = '无法获取统计数据！';
			});
		}

		/**
		 * 获取系统账号
		 */
		$scope.getAccounts = function (callback)
		{
			if (typeof $scope.accounts != 'undefined' || ! $.isEmptyObject($scope.accounts)) return ;
			Account.query(function(res){
				if (res.code == 200) {
					$scope.accounts = res.data[$scope.dataSource + '_accounts'];
					$scope.selectedAccount = res.data['current' + $scope.dataSource[0].toUpperCase() + $scope.dataSource[1]].id;
					if (typeof callback == 'function') callback();
				} else {
					$scope.accountsEmpty = '没有绑定的账号！';
				};
			},function(){
				$scope.accountsEmpty = '无法获取绑定的账号！';
			});
		}

		/**
		 * 微博|微信 粉丝信息数据视图
		 */
		$scope.getUserStats = function ()
		{
			var draw = function () {
				$scope.getStatsData($routeParams.type + '/stats_user', function(res){
					if (res.code != 200)
					{
						$scope.statsEmpty = res.message || '获取统计数据失败！';
						return false;
					}

					/*绘制统计数据图*/
					HCS.drawSingleChart('user_gender_stats', HCS.translateSingleData(res.data.gender, originalDataMap.gender), chartConfig.gender);
					HCS.drawSingleChart('user_vt_stats', HCS.translateSingleData(res.data.fans_type, originalDataMap.fans_type), chartConfig.fans_type);
					HCS.drawSingleChart('user_region_stats', HCS.translateSingleData(res.data.location_distribution, originalDataMap.region), chartConfig.region);
					HCS.drawMultipleChart('user_increcement_stats', translateIncreaseData(res.data.fans_increasement), chartConfig.increase);
				});
			};

			if (typeof $scope.selectedAccount != 'undefined' && $scope.selectedAccount > 0)
				draw();
			else
				$scope.getAccounts(draw); /* 先获取账号信息 */
		};

		$scope.getCategoryStats = function ()
		{
			/* 分类信息图表 */
			var draw = function ()
			{
				$scope.getStatsData($routeParams.type + '/stats_communication', function(res){
					if (res.code != 200)
					{
						$scope.statsEmpty = res.message || '获取统计数据失败！';
						return false;
					}

					/* @, 评论, 私信 交互量 */
					HCS.drawSingleChart('interacts_stats',
						translateInteractData(res.data.interact_number.type_interact_number),
						chartConfig.interacts);

					/* 24小时时间段统计 */
					HCS.drawMultipleChart('hourly_interact',
						translateHourInteract(res.data.interact_number.hour_type_interact_number),
						chartConfig.hourly_interact);

					/* 舆情分类 */
					$scope.topCategories = [];
					$scope.categoryStats = res.data.category_info_number;
					if ($.isEmptyObject($scope.categoryStats) || typeof $scope.categoryStats.relation == 'undefined') {
						$scope.statsEmpty = '分类统计数据为空！';
						return ;
					}
					for (var i in $scope.categoryStats.relation[0])
					{
						var subId = $scope.categoryStats.relation[0];
						$scope.topCategories.push($scope.categoryStats.category[subId[i]]);
					}
					$scope.drawCategoryChart();
				})
			}

			if (typeof $scope.selectedAccount != 'undefined' && $scope.selectedAccount > 0)
				draw();
			else
				$scope.getAccounts(draw); /* 先获取账号信息 */
		};

		/**
		 * 微博私信|微信 规则命中率 统计
		 */
		$scope.getRuleStats = function ()
		{
			var draw = function ()
			{
				if ('mex' == $routeParams.type)
				{
					$scope.wx_rule = '';
					$scope.wx_index = 0;
				}

				$scope.getStatsData($routeParams.type + '/stats_rule', function(res){
					if (res.code != 200)
					{
						$scope.statsEmpty = res.message || '获取统计数据失败！';
						$scope.wx_rule = '';
						return false;
					}

					if ('meo' == $routeParams.type)
					{
						HCS.drawSingleChart('rule_stats', HCS.translateSingleData(res.data.rule_number, originalDataMap.rule), chartConfig.rule);
					}
					else
					{
						$scope.wx_rule = res.data.rule_number;
					}
					HCS.drawSingleChart('keyword_stats', HCS.translateSingleData(res.data.pm_keyword_number, originalDataMap.keyword), chartConfig.keyword);
				});
			}

			if (typeof $scope.selectedAccount != 'undefined' && $scope.selectedAccount > 0)
				draw();
			else
				$scope.getAccounts(draw); /* 先获取账号信息 */
		};

		/**
		 * CSR 处理量统计
		 */
		$scope.getStaffStats = function ()
		{
			$scope.getStatsData($routeParams.type + '/stats_staff', function(res){
				if (res.code != 200)
				{
					$scope.statsEmpty = res.message || '获取统计数据失败！';
					return false;
				}

				/*绘制统计数据图*/
				HCS.drawMultipleChart('staff_stats', translateStaffData(res.data.staff_info, originalDataMap.staff), chartConfig.staff);
			});
		};

		/**
		 * 微博舆情关键词数量统计
		 */
		$scope.getKeywordStats = function ()
		{
			$scope.getStatsData('meo/stats_keyword', function(res){
				if (res.code != 200)
				{
					$scope.statsEmpty = res.message || '获取统计数据失败！';
					return false;
				}

				/*绘制统计数据图*/
				HCS.drawSingleChart('wb_keyword_stats', HCS.translateSingleData(res.data, originalDataMap.wb_keyword), chartConfig.wb_keyword);
			});
		};

		/**
		 * 绘制微博舆情分类数据图
		 */
		$scope.drawCategoryChart = function ()
		{
			var selectedCategories = getSelectedCategories();
			HCS.drawSingleChart('category_stats', selectedCategories, chartConfig.category);
		};

		/**
		 * 微信自定义菜单点击量统计
		 */
		$scope.getMenuStats = function ()
		{
			$scope.getStatsData($routeParams.type + '/stats_menu', function(res){
				if (res.code != 200)
				{
					$scope.statsEmpty = res.message || '获取统计数据失败！';
					return false;
				}

				/*绘制统计数据图*/
				HCS.drawSingleChart('stats_menu', HCS.translateSingleData(res.data.detail, originalDataMap.menu), chartConfig.menu);
			});
		};

		$scope.getTagStats = function ()
		{
			$scope.getStatsData($routeParams.type + '/stats_tag', function(res){
				if (res.code != 200)
				{
					$scope.statsEmpty = res.message || '获取统计数据失败！';
					return false;
				}

				HCS.drawSingleChart('tag_stats', HCS.translateSingleData(res.data, originalDataMap.tag), chartConfig.tag);
			})
		};

		/**
		 * 数据源字段解析表
		 */
		var originalDataMap = {
			gender : {
				key : 'gender',
				val : 'gender_number',
				key_map : {
					null : '未知',
					0 : '未知',
					1 : '男',
					2 : '女'
				}
			},
			fans_type : {
				key : 'verified_type',
				val : 'fans_type_number',
				key_map : {
					'-1' : '普通',
					'0' : '名人',
					'1' : '企业',
					'2' : '企业',
					'3' : '企业',
					'4' : '企业',
					'5' : '企业',
					'6' : '企业',
					'7' : '企业',
					'8' : '企业',
					'200' : '达人',
					'220' : '达人',
					'400' : '达人'
				}
			},
			region : {
				key : 'location',
				val : 'location_number',
				key_map : {
					11 : '北京',
					12 : '天津',
					13 : '河北',
					14 : '山西',
					15 : '内蒙古',
					21 : '辽宁',
					22 : '吉林',
					23 : '黑龙江',
					31 : '上海',
					32 : '江苏',
					33 : '浙江',
					34 : '安徽',
					35 : '福建',
					36 : '江西',
					37 : '山东',
					41 : '河南',
					42 : '湖北',
					43 : '湖南',
					44 : '广东',
					45 : '广西',
					46 : '海南',
					50 : '重庆',
					51 : '四川',
					52 : '贵州',
					53 : '云南',
					54 : '西藏',
					61 : '陕西',
					62 : '甘肃',
					63 : '青海',
					64 : '宁夏',
					65 : '新疆',
					71 : '台湾',
					81 : '香港',
					82 : '澳门',
					100 : '其他',
					400 : '海外',
					'' : '未知'
				}
			},
			wb_keyword : {
				key : 'keyword_text',
				val : 'keyword_number',
				key_map : {}
			},
			rule : {
				key : 'rule_name',
				val : 'rule_number',
				key_map : {}
			},
			keyword : {
				key : 'keyword_name',
				val : 'keyword_number',
				key_map : {}
			},
			tag : {
				key : 'tag_name',
				val : 'tag_number',
				key_map : {}
			},
			staff : {
            },
			menu : {
                key : 'menu_name',
                val : 'num',
				key_map : {}
            }
		};

		/**
		 * 数据源图表设定信息表
		 */
		var chartConfig = {
			gender : {
				chartType : 'pie',
				pointFormat : '<b>{point.y} 人</b> {point.percentage:.2f}%',
				colors : ['#7CB5EC', '#ED6A98', '#434348']
			},
			fans_type : {
				chartType : 'pie',
				pointFormat : '<b>{point.y} 人</b> {point.percentage:.2f}%',
				colors : ['#434348', '#D15B47', '#FFB752', '#7CB5EC']
			},
			region : {
				chartType : 'column',
				pointFormat : '共: <b>{point.y} 人</b>',
				seriesName : '所在地区',
				xRotation : 0,
				dataName : '用户量'
			},
			wb_keyword : {
				chartType : 'column',
				pointFormat : '<b>{point.y} 条</b>',
				seriesName : '关键词',
				dataName : '微博量'
			},
			rule : {
				chartType : 'column',
				pointFormat : '<b>{point.y} 条</b>',
				seriesName : '规则',
				dataName : '信息量'
			},
			keyword : {
				chartType : 'column',
				pointFormat : '<b>{point.y} 条</b>',
				seriesName : '关键词',
				dataName : '信息量'
			},
			tag : {
				chartType : 'column',
				pointFormat : '<b>{point.y}</b>',
				seriesName : '标签',
				dataName : '触发量'
			},
			interacts : {
				chartType : 'column',
				pointFormat : '<b>{point.y}</b>',
				seriesName : '总交互',
				dataName : '交互量'
			},
			category : {
				chartType : 'column',
				pointFormat : '<b>{point.y}</b>',
				seriesName : '总交互',
				dataName : '信息量'
			},
			increase : {
				chartType : 'line',
				yTitle : {text:'人数'},
				unit : '人',
				xType: 'datetime'
			},
			hourly_interact : {
				chartType : 'line',
				yTitle : {text:'交互量'},
				unit : '条'
			},
			staff : {
				chartType : 'column',
				unit : '条',
				xAxis : {},
				yTitle : {text:'处理量'}
			},
			menu : {
				chartType : 'column',
				pointFormat : '<b>点击量:{point.y}</b>',
				unit : '次',
                seriesName : '菜单名称',
				dataName : '点击量',
				xAxis : {},
				yTitle : {text:'点击量'}
			}
		};

		/**
		 * 转换用户粉丝增量的数据
		 * @param data
		 * @returns {{}}
		 */
		var translateIncreaseData = function (data)
		{
			var chartData = {};
			chartData.category = [];
			var series = {name:'粉丝增量', data:[]};
			for (var i in data)
			{
				var item = data[i];
				var date = item.day.split('-');
				series.data.push([Date.UTC(parseInt(date[0]), parseInt(date[1])-1, parseInt(date[2])), parseFloat(data[i].day_total)]);
			}
			chartData.series = [series];
			return chartData;
		};

		/**
		 * 交互量数据转换
		 * @param data
		 */
		var translateInteractData = function (data)
		{
			var chartData = {data:[], category:[]};

			for (var i in data)
			{
				if (i == 0) { chartData.category.push('@我的'); chartData.data.push(['@我的', parseInt(data[i])]); }
				if (i == 1) { chartData.category.push('评论'); chartData.data.push(['评论', parseInt(data[i])]); }
				if (i == 3) { chartData.category.push('私信'); chartData.data.push(['私信', parseInt(data[i])]); }
				if (i == 'image') { chartData.category.push('图片'); chartData.data.push(['图片', parseInt(data[i])]); }
				if (i == 'location') { chartData.category.push('位置'); chartData.data.push(['位置', parseInt(data[i])]); }
				if (i == 'text') { chartData.category.push('文字'); chartData.data.push(['文字', parseInt(data[i])]); }
				if (i == 'video') { chartData.category.push('视频'); chartData.data.push(['视频', parseInt(data[i])]); }
				if (i == 'voice') { chartData.category.push('语音'); chartData.data.push(['语音', parseInt(data[i])]); }
			}

			return chartData;
		};

		/**
		 * 获取当前选中分类的所有子分类，并转化成HighChart数据
		 */
		var getSelectedCategories = function ()
		{
			if (typeof $scope.selectedCategory == 'undefined' || typeof $scope.selectedCategory == null)
			{
				for (var i in $scope.topCategories)
				{
					$scope.selectedCategory = $scope.topCategories[i]['id'];
					break;
				}
			}

			/* 获取下级分类 */
			if (typeof $scope.categoryStats.relation[$scope.selectedCategory] != 'object')
			{
				$scope.statsEmpty = '统计数据为空！';
				return false;
			}

			var chartData = {data:[], category:[]};
			for (var i in $scope.categoryStats.relation[$scope.selectedCategory])
			{
				var id = $scope.categoryStats.relation[$scope.selectedCategory][i];
				if (typeof $scope.categoryStats.category[id] != 'object') continue;

				var item = $scope.categoryStats.category[id];
				chartData.category.push(item.cat_name);
				chartData.data.push([item.cat_name, parseInt(item.category_number)]);
			}

			return chartData;
		};

		/**
		 * 转换按小时统计的交互数据
		 * @param data
		 */
		var translateHourInteract = function (data)
		{
			var chartData = {category:[], series : []};

			chartData.category = ['0h', '1h', '2h', '3h', '4h', '5h', '6h', '7h', '8h', '9h', '10h', '11h', '12h',
				'13h', '14h', '15h', '16h', '17h', '18h', '19h', '20h', '21h', '22h', '23h'];

			for (var i in data)
			{
				if (i == 0) { chartData.series.push({name:'@我的', data:data[i]}); }
				if (i == 1) { chartData.series.push({name:'评论', data:data[i]}); }
				if (i == 3) { chartData.series.push({name:'私信', data:data[i]}); }
				if (i == 'image') { chartData.series.push({name:'图片', data:data[i]}); }
				if (i == 'location') { chartData.series.push({name:'位置', data:data[i]}); }
				if (i == 'text') { chartData.series.push({name:'文字', data:data[i]}); }
				if (i == 'video') { chartData.series.push({name:'视频', data:data[i]}); }
				if (i == 'voice') { chartData.series.push({name:'语音', data:data[i]}); }
			}

			return chartData;
		};

		/**
		 * 转换统计工作量的数据
		 * @param data
		 * @returns {{}}
		 */
		var translateStaffData = function (data)
		{
			var chartData = {category:[], series : []};
			var categorize = {name:'分类量', data:[]};
			var ignore = {name:'忽略量', data:[]};
			var reply = {name:'回复量', data:[]};

			for (var i in data)
			{
				var item = data[i];
				var ca = 0; // 分类数量
				var ig = 0; // 忽略数量
				var re = 0; // 回复数量
				for (var o in item)
				{
					if (o == 'staff_name') continue;
					if (o == 0 || o == 7) ca += parseInt(item[o]);
					if (o == 3) re += parseInt(item[o]);
					if (o == 9) ig += parseInt(item[o]);
				}
				categorize.data.push(ca);
				ignore.data.push(ig);
				reply.data.push(re);
				chartData.category.push(item.staff_name);
			}
			chartData.series = [categorize, ignore, reply];

			return chartData;
		};

		/**
		 * 格式化时间对象为 xxxx-xx-xx 的字符串
		 * @param o
		 * @returns {string}
		 */
		var formatDate = function (o)
		{
			if (typeof o != 'object' || o == null) return '';

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			return y + '-' + m + '-' + d;
		};

		// 时间范围选择设置 时间区间设置为最近30天
		$scope.endDt = $scope.maxDate = new Date();
		var time = $scope.endDt.getTime();
		$scope.startDt =new Date(time - 30 * 24 * 3600 * 1000);

		$scope.dateOptions = {
			formatYear: 'yy',
			startingDay: 1
		};
		$scope.open = function ($event, open, close) {
			$event.preventDefault();
			$event.stopPropagation();
			$scope[open] = true;
			$scope[close] = false;
		};

	}]);
});
