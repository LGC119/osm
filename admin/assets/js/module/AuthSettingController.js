'use strict';

define(['me'], function (me) {
	me.controller('AuthSettingController', ['$scope', '$http', function ($scope, $http) {
		$scope.empty = '载入中...';
		$scope.auth_empty = '请点击左侧菜单获取权限列表！';
		$scope.post = {};

		/* 获取当前系统设定的菜单 */
		$scope.menu = {};
		$scope.getMenuTree = function () 
		{
			$http.get(
				_c.appPath + 'module/menu_setting/get_menu_tree' 
			).success(function(res){
				if (res.code == 200) 
					$scope.menu = res.data;
				else 
					$scope.empty = res.message || '获取系统菜单失败！';
			}).error(function(){
				$scope.empty = '无法获取系统菜单！';
			});
		}

		/* 获取选中菜单的权限列表 */
		$scope.getMenuAuth = function (menuid) 
		{
			$scope.post.menuid = 0;
			if (typeof $scope.menu.tree[menuid] != 'undefined') return true;

			$scope.post.menuid = menuid;
			$scope.auth_empty = 'loading...';
			$http.get(
				_c.appPath + 'module/auth_setting/get_menu_auth/' + parseInt(menuid)
			).success(function(res){
				$scope.auth_acts = false;
				if (res.code == 200) 
					$scope.auth_acts = res.data;
				else 
					$scope.auth_empty = res.message || '获取当前菜单权限列表失败！';
			}).error(function(){
				$scope.auth_empty = '无法获取当前菜单权限列表！';
			});
		}

		$scope.showAuthActsModal = function () 
		{
			$('#authActsModal').modal('show');
		}

		/* 创建一个权限项 */
		$scope.addAuthAct = function () 
		{
			if ($scope.post.menuid == 0) 
			{
				alert('请先选中一个菜单项！');
				return false;
			}

			var arr = ['title', 'module', 'controller', 'method'];
			for (var i in arr) 
			{
				var v = arr[i];
				if (typeof $scope.post[v] == 'undefined' || $scope.post[v].trim() == '') 
				{
					alert('请填写完整！');
					return ;
				}
				if (v != 'title' && ! /^[a-z0-9_]+$/ig.test($scope.post[v])) 
				{
					alert('填写的正确的参数！');
					return ;
				}
			}

			/* 创建权限项 */
			$http.post(
				_c.appPath + 'module/auth_setting/add_auth', 
				$scope.post
			).success(function(res){
				if (res.code == 200)
					$('#authActsModal').modal('hide');
				else 
					alert(res.message || '添加权限项失败！');
				$scope.getMenuAuth($scope.post.menuid);
			}).error(function(){
				alert('无法添加权限！');
			});
		}

	}]);
});