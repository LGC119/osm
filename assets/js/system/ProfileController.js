'use strict';

define(['me'], function (me) {
	me.controller('ProfileController', ['$scope', '$http', function($scope, $http){
		$scope.empty = '加载个人信息...';

		$scope.name = null;
		$scope.email = null;
		$scope.tel = null;

		// 获取个人信息
		$scope.get_profile = function () 
		{
			$http.get(
				_c.appPath + 'system/staff/get_profile'
			).success(function(res){
				if (res.code == 200)
					$scope.user = res.data;
				else 
					$scope.empty = res.message || '获取个人信息失败！';
			}).error(function(){
				$scope.empty = '无法获取个人信息！';
			});
		}

		/* 修改个人信息 */
		$scope.edit_profile = function (k) 
		{
			if (k != 'email' && k != 'tel' && k != 'name') 
				return false;

			if ($scope[k] == null || $scope[k].trim() == '') 
			{
				$.gritter.add({ 
					title : '错误', 
					text : '字段值不能留空！', 
					time : 1000, 
					class_name : 'gritter-error gritter-center'
				});
				return false;
			}

			/* 如果没有改变，返回 */
			if ($scope[k] == $scope.user[k]) {
				$scope[k] = null;
				return true;
			}

			$http.post(
				_c.appPath + 'system/staff/edit_profile', 
				{k:k, v:$scope[k]}
			).success(function(res){
				if (res.code == 200) 
					$scope.get_profile();
				else 
					$.gritter.add({ 
						title : '错误', 
						text : res.message || '修改个人信息失败！', 
						time : 1000, 
						class_name : 'gritter-error gritter-center'
					});
				$scope[k] = null;
			}).error(function(){
				$.gritter.add({ 
					title : '错误', 
					text : '无法修改个人信息，请稍后尝试！', 
					time : 1000, 
					class_name : 'gritter-error gritter-center'
				});
			});
		}

		$scope.reset = function () 
		{
			$scope.oldpass = $scope.newpass = $scope.newpassconfirm = '';
		}

		$scope.edit_password = function () 
		{
			var verify_res = _verify_password();
			if (verify_res !== true) 
			{
				$.gritter.add({ 
					title : '错误', 
					text : verify_res, 
					time : 800, 
					class_name : 'gritter-error gritter-center'
				});
				return false;
			}

			$http.post(
				_c.appPath + 'system/staff/edit_password', 
				{
					oldpass:$scope.oldpass, 
					newpass:$scope.newpass, 
					newpassconfirm:$scope.newpassconfirm
				}
			).success(function(res){
				if (res.code == 200) 
					$.gritter.add({ 
						title : '修改成功', 
						text : '密码修改成功，请退出并重新登录！', 
						time : 800, 
						class_name : 'gritter-success gritter-center'
					});
				else 
					$.gritter.add({ 
						title : '错误', 
						text : res.message || '修改密码失败！', 
						time : 1000, 
						class_name : 'gritter-error gritter-center'
					});
			}).error(function(){
				$.gritter.add({ 
					title : '错误', 
					text : '无法修改密码，请稍后尝试！', 
					time : 1000, 
					class_name : 'gritter-error gritter-center'
				});
			});
		}

		var _verify_password = function () 
		{
			if ($scope.oldpass == undefined || $scope.oldpass.trim() == '') 
				return '请填写原始密码！';
			if ($scope.newpass == undefined || $scope.newpass.trim() == '') 
				return '请填写新密码！';
			if ($scope.newpassconfirm == undefined || $scope.newpassconfirm.trim() == '') 
				return '请确认新密码！';

			if ($scope.newpass.length < 6 || $scope.newpass.length > 40) 
				return '密码长度请保证在 6 ~ 40 个字符之间！';

			if ($scope.newpass != $scope.newpassconfirm) 
				return '两次填入的新密码不一致！';

			return true;
		}

		$scope.edit = function (k) 
		{
			$scope[k] = $scope.user[k];
		}

	}]);
});