'use strict';

/* 微博舆情关键词设置控制器 */
define(['me'], function (me) {
	me.controller('MonitorWeiboCategoryController', ['$scope', '$http', function ($scope, $http) {

		$scope.empty = '载入中...';

		/* 获取监控分类的数据 */
		$scope.getCategories = function () 
		{
			/* 获取所有的需要监控的分类的数据 */
			$http.get(
				_c.appPath + 'common/monitor_category/get_wb_monitored/0'
			).success(function(res){
				if (res.code == 200)
					$scope.categories = res.data;
				else 
					$scope.empty = res.message || '获取监控分类数据失败！';
			}).error(function(){
				$scope.empty = '无法获取监控分类数据！';
			});
		}

	}]);
});
