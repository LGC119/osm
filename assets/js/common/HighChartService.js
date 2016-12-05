'use strict';

define(['me'], function (me) {
	me.factory('HighChartService', ['$resource', function ($resource){

		/* highChart对象，具有绘制数据图方法 */
		var highChart = {};

		/**
		 * HighChart 绘制多数据源图表 柱状图 | 曲线图
		 *
		 * @param target Dom ID
		 * @param data 数据原型
		 * @param config 具体配置
		 */
		highChart.drawMultipleChart = function (target, data, config)
		{
			// 判断X轴是否时间维度
			var xAxis = {};
			if (config.xType == 'datetime') {
				xAxis = {type: config.xType};
			} else {
				xAxis = {categories: data.category};
			}

			$('#'+target).highcharts({
				chart: {
					type : config.chartType,
					height: 300
				},
				title: {
					text: null
				},
				xAxis: xAxis,
				yAxis: {
					title: config.yTitle,
					plotLines: [{
						value: 0,
						width: 1,
						color: '#808080'
					}]
				},
				tooltip: {
					valueSuffix: config.unit
				},
				legend: {
					layout: 'horizontal',
					align: 'center',
					verticalAlign: 'bottom',
					borderWidth: 0
				},
				series: data.series,
				credits: {
					enabled: false
				}
			});
		};

		/**
		 * HighChart 绘制单数据源图表 饼状图 | 柱状图 | 曲线图
		 *
		 * @param target Dom Id
		 * @param data 数据原型
		 * @param config 具体配置
		 */
		highChart.drawSingleChart = function (target, data, config)
		{
			$('#'+target).highcharts({
				chart: {
					type : config.chartType,
					plotBackgroundColor: null,
					plotShadow: false,
					height: 240
				},
				title: {
					text: null
				},
				xAxis: {
					categories: data.category,
					labels: {
						rotation: config.xRotation
					}
				},
				yAxis: {
					min: 0,
					title: {
						text: config.dataName
					},
					allowDecimals: false
				},
				tooltip: {
					pointFormat: config.pointFormat
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
							enabled: false
						},
						showInLegend: true,
						colors: config.colors
					}
				},
				series: [{
					name: config.seriesName,
					data: data.data
				}],
				credits: {
					enabled: false
				}
			});

		};

		/**
		 * 过滤直接获取的原始数据, 转换成柱状图或饼状图
		 * @param data
		 * @param mapping
		 * @returns {{data: Array}}
		 */
		highChart.translateSingleData = function (data, mapping)
		{
			var chartData = {data:[], category:[]}; // 最终chart数据
			var tmpObject = {};
			for (var i in data)
			{
				var item = data[i];
				if (typeof item[mapping.key] != 'undefined' && typeof item[mapping.val] != 'undefined')
				{
					var key = item[mapping.key];
					var val = item[mapping.val];

					//if ( !$.isEmptyObject(mapping.key_map) && typeof mapping.key_map[key] == 'undefined') continue; // 未知数据类型

					var chartKey = mapping.key_map[key] || key;
					var chartVal = parseFloat(val);

					if (typeof tmpObject[chartKey] == 'undefined') {
						tmpObject[chartKey] = chartVal;
						chartData.category.push(chartKey);
					} else {
						tmpObject[chartKey] += chartVal;
					}
				}
			}

			for (var i in tmpObject)
				chartData.data.push([i, tmpObject[i]]);

			return chartData;
		};

		/**
		 * 过滤直接获取的原始数据, 转换成柱状图或曲线图
		 * @param data
		 * @param mapping
		 * @returns {{series: Array, category: Array}}
		 */
		//highChart.translateLineData = function (data)
		//{
		//	var chartData = {series:[], category:[]}; // 最终chart数据
		//	var series = {name:data.name, data:[]};
		//	for (var i in data)
		//	{
		//		var item = data[i];
		//		var chartKey = mapping.key_map[key];
		//		var chartVal = parseFloat(val);
		//
		//		chartData.category.push(chartKey);
		//		series.data.push(chartVal);
		//	}
		//
		//	chartData.series.push(series);
		//	console.log(chartData);
		//
		//	return chartData;
		//}

		return highChart;

	}]);

});