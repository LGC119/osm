'use strict';

define(['me'], function (me) {
	me.controller('HeaderController', ['$scope', '$http', function ($scope, $http) {


		/* 检测后台账号是否已登录 */
		$scope.chkLogin = function () 
		{
			$http.get(
				_c.appPath + 'gate/chkLogin'
			).success(function(res){
				if (res == 0)
					window.location.href = 'index.html';
				else 
					$scope.adminInfo = res;
				return ;
			});
		}
		$scope.chkLogin();

		/* 登出系统 */
		$scope.logout = function () {
			if (confirm('确定退出？')) {
				var url = _c.appPath + 'gate/logout';

				$http.post(url).success(function (data) {
					window.location.href = 'index.html';
				}).error(function () {
					alert('Network looks bad, Please try again !');
				});
			}
		}

	}]);
});