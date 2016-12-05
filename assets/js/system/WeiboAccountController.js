'use strict';

define(['me'], function (me) {
	me.controller('WeiboAccountController', ['$scope', '$http', 'Account', function ($scope, $http, Account){
		$scope.wb_empty = '载入中...';
		$scope.app_empty = '正在加载微博APP信息...';
		$scope.platforms = ['','新浪微博','腾讯微博'];
		$scope.wb_accounts = [];
		$scope.apps = [];
		
		// 获取所有绑定账号
		$scope.get_accounts = function () 
		{
			$scope.accounts = Account.query(function(res){
				if (res.code == 200) {
					if (res.data.wb_accounts.length > 0) 
						$scope.wb_accounts = res.data.wb_accounts;
					else 
						$scope.wb_empty = '没有绑定微博账号';
				} else {
					$scope.wb_empty = '无法获取绑定微博账号！';
				}
			});
		}
	
		// 获取所有应用
		$http({
			method : 'GET',
			url : _c.appPath + 'system/application/get_all_apps'
		}).success(function(res){
			if (res.code == 200) 
				$scope.apps = res.data;
			else 
				$scope.app_empty = res.message;
		}).error(function(){
			$scope.app_empty = "获取应用失败，请稍后尝试！";
		});

		$scope.initModal = function () 
		{
			$('#add_new').modal("show");
		}

		// 获取微博应用授权
		$scope.get_wb_auth = function (appid)
		{
			$http({
				get : 'POST',
				url : _c.appPath + 'system/account/get_bind_url/'+appid
			}).success(function(response){
				if (response.code == 200) 
					window.open(response.data, 'newwindow', 'height=500,width=960,top=0,left=0,toolbar=no,menubar=no,scrollbars=no,resizable=yes,location=no,status=no');
				else 
					alert(response.message);
			}).error(function(){
				alert('无法绑定，请稍后尝试！');
			});
		}

		// 解绑账号
		$scope.delete = function (id, name) 
		{
			var id = parseInt(id);
			if ( ! id > 0) 
				return false;

			if (confirm('确定解绑账号: '+name+'？')) {
				$http.get(
					_c.appPath + 'system/account/wb_unbind/' + id
				).success(function(res){
					if (res.code == 200) {
						alert('解绑成功！');
						/* 删除数据 */
						for (var i in $scope.wb_accounts) {
							if ($scope.wb_accounts[i]['id'] == id)
								$scope.wb_accounts.splice(i, 1);
						}
					} else {
						alert(res.message);
					}
				}).error(function(){
					alert('解绑失败，请稍后尝试！');
				});
			}
		}

	}]);
});

/* 绑定成微博成功返回 */
function success()
{
	alert('绑定成功！');
	/* 刷新绑定数据，刷新页面 */
	window.location.reload();
}

/* 绑定微博失败 */
function error()
{
	alert('绑定失败！');
}