'use strict';

define(['me'], function (me) {
	me.controller('AuthrizationController', ['$scope', '$http', function ($scope, $http) {
		$scope.companyEmpty = '载入中...';
		$scope.currentCompany = {};
		$scope.menu = {};
		$scope.expand = {}; // 菜单折叠开启状态

		/* 获取品牌客户的菜单项和权限项列表 */
		$scope.companies = {};
		$scope.getCompanyAutherizations = function () 
		{
			$http.post( 
				_c.appPath + 'company/autherization/get_company_autherizations', 
				{
					items_per_page : $scope.companies.items_per_page || 10, 
					current_page : $scope.companies.current_page || 1
				}
			).success(function(res){
				if (res.code == 200) 
					$scope.companies = res.data;
				else 
					$scope.companyEmpty = res.message || '获取品牌客户失败！';

				// console.log($scope.companies);
			}).error(function(){
				$scope.companyEmpty = '无法获取品牌客户！';
			});
		}

		/* 获取菜单权限树 */
		$scope.getMenuAutherizations = function () 
		{
			if ($scope.menu != null && ! $.isEmptyObject($scope.menu)) return true;
			$http.get(
				_c.appPath + 'company/autherization/get_menu_autherizations'
			).success(function(res){
				if (res.code == 200) 
					$scope.menu = res.data;
				else 
					$scope.menusEmpty = '获取菜单项信息失败！';
			}).error(function(){
				$scope.menusEmpty = '无法获取菜单项信息！';
			});
		}

		/* 编辑左侧菜单点击事件 */
		$scope.menuClick = function (sid) 
		{
			/* 变更折叠状态 */
			$scope.expand[sid] = !$scope.expand[sid];
			/* 设定当前操作菜单为所点击菜单 */
			if (typeof $scope.menu.list[sid] == 'object') 
				$scope.currentMenu = angular.copy($scope.menu.list[sid]);
		}

		/* 打开编辑权限的窗口 */
		$scope.editAutherization = function (company) 
		{
			if (typeof company == 'undefined') return false;
			$scope.currentCompany = angular.copy(company);
			$('#editAutherizationModal').modal('show');

			/* 获取菜单和权限树 */
			$scope.getMenuAutherizations();
		}

		/* 修改当前公司菜单和权限项 */
		$scope.submitAutherization = function () 
		{
			$scope.submitPending = true;	// 提交菜单等待状态！
			// 提交当前公司的ID，menuIDs，authIDs
			var id = $scope.currentCompany.id;
			var authids = getValidIds($scope.currentCompany.authids);
			var menuids = getValidIds($scope.currentCompany.menuids);

			$http.post(
				_c.appPath + 'company/autherization/set_autherization', 
				{
					company_id : id, 
					authids : authids, 
					menuids : menuids
				}
			).success(function(res){
				if (res.code == 200) {
					alert('修改成功！');
					$('#editAutherizationModal').modal('hide');
				} else {
					alert('修改失败！');
				}

				$scope.getCompanyAutherizations();
				$scope.submitPending = false;
			}).error(function(){
				$scope.submitPending = false;
			});
		}

		var getValidIds = function (arr) 
		{
			var ids = [];
			for (var i in arr) 
			{
				if (parseInt(i) > 0 && arr[i] === true) 
					ids.push(parseInt(i));
			}
			return ids;
		}

	}]);
});