'use strict';

define(['me'], function (me) {
	me.controller('MenuSettingController', ['$scope', '$http', function ($scope, $http) {
		$scope.empty = '载入中...';
		$scope.fold = {}; // 菜单开启状态
		$scope.currentMenu = {}; // 当前编辑的菜单

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

		/* 获取菜单详情 */
		$scope.getMenuDetail = function (menuid) 
		{
			// 当前菜单项置为点中菜单项
			angular.copy($scope.menu.list[menuid], $scope.currentMenu);
			$scope.fold[menuid] = $scope.fold[menuid] ? false : true;
		}

		/* 编辑菜单 */
		$scope.editMenu = function (field) 
		{
			if ( typeof field != 'string' || (field != 'name' && field != 'icon' && field != 'url') ) return false;

			$http.post(
				_c.appPath + 'module/menu_setting/edit_menu', 
				{field : field, value : $scope.currentMenu[field], id : $scope.currentMenu.id}
			).success(function(res){
				if (res.code == 200) 
					alert('修改成功！');
				else 
					alert(res.message||'修改失败！');
				$scope.getMenuTree();
			}).error(function(){
				alert('无法修改菜单！');
			});
		}

	}]);
});