'use strict';

define(['me'], function (me) {
	me.controller('ApplicationController', ['$scope', '$http', function($scope, $http){
		$scope.empty = '载入中...';
		$scope.level = ['普通授权', '中级授权', '高级授权', '合作授权'];
		$scope.dTitle = "添加新的应用";
		$scope.app = {platform:1, level:0};
		$scope.apps = [];

		$scope.is
		
		$http({
			method : 'GET',
			url : _c.appPath + 'system/application/get_all_apps'
		}).success(function(data){
			if (data.code == 200) {
				$scope.apps = data.data;
			} else {
				$scope.empty = data.message;
			}
		}).error(function(){
			$scope.empty = "网络不通，请稍后尝试！";
		});

		$scope.initModal = function (appid) 
		{
			if (appid != undefined && $scope.apps.length > 0)
			{
				for (var i in $scope.apps) 
				{
					if ($scope.apps[i]['id'] == appid) 
					{
						$scope.dTitle = "编辑应用信息";
						var app = $scope.apps[i];
						$scope.app = {
							platform	: app.platform, 
							name		: app.name, 
							appcreator	: app.appcreator, 
							appkey		: app.appkey, 
							appskey		: app.appskey, 
							callbackurl	: app.callbackurl, 
							level		: app.level
						};
						$('#add_new').modal('show');
						break;
					}
				}
			}
			else 
			{
				$scope.dTitle = "添加新的应用";
				$scope.app = {
					platform	: 1, 
					level		: 0
				};
				$('#add_new').modal('show');
			}
		}

		/* 添加微博应用 */
		$scope.add = function () 
		{
			// 
		}

		/* 修改微博应用 */
		$scope.edit = function () 
		{
			// 
		}

		/* 删除微博应用 */
		$scope.delete = function (appid)
		{
			// 
		}

	}]);
});