'use strict';

define(['me'], function (me) {
	me.controller('AdvGroupController', ['$scope', '$sce', '$http' , function ($scope, $sce, $http) {
		$scope.groupData = {};
		$scope.statisticsData = {};
		$scope.status = 0;
		$scope.arrange = 1;
		var dt = new Date(format_date(new Date()) + ' 00:00:00');
		$scope.dateTime = dt.getTime() - 1;
		$scope.groupEmpty = '正在加载用户组信息...';


		/* 格式化日期 */
		function format_date (o)
		{
			if (typeof o != 'object' || o == null) return '';

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			return y + '-' + m + '-' + d;
		}

		$scope.groupList = {};
		$scope.getGroups = function () {
			var params = {
				page: $scope.groupList.page || 1,
				perpage: $scope.groupList.perpage || 12,
				arrange: $scope.arrange 
			};

			if ($scope.keyword) {
				params.keyword = $scope.keyword;
			}

			$http.post(
				_c.appPath + 'mei/group/get_list', 
				params
			).success(function(res){
				if (res.code==200) 
					$scope.groupList = res.data;
				else 
					$scope.groupEmpty = res.message || '没有高级用户组信息';
			}).error(function(){
				$scope.groupEmpty = '无法获取系统高级用户组信息！';
			})
		}

	}]);
});

