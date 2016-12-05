'use strict';

define(['me'], function (me) {
	me.controller('PositionController', ['$scope', '$http', function($scope, $http){
		$scope.empty = '载入中...';
		$scope.dTitle = "添加职位";
		$scope.menuEmpty = '载入菜单中...';
		$scope.authEmpty = '载入权限项...';
		$scope.fold = {}; // 菜单开启状态

		$scope.position = {menus:{}, auths:{}}; // 当前操作的职位
		$scope.menuAuths = []; 					// 选中菜单的权限项
		
		/* 获取职位列表 */
		$scope.getPositionList = function () {
			$http({
				method : 'GET',
				url : _c.appPath + 'system/position'
			}).success(function(data){
				if (data.code == 200) 
					$scope.positions = data.data;
				else 
					$scope.empty = data.message;
			}).error(function(){
				$scope.empty = "网络不通，请稍后尝试！";
			});
		}

		/* 获取菜单树 */
		$scope.getMenuTree = function () 
		{
			/* 已经存在，不再重复获取 */
			if (typeof $scope.menu != 'undefined' && typeof $scope.menu.tree != 'undefined') return true;
			$http.get(
				_c.appPath + 'system/menu/get_menu_tree' 
			).success(function(res){
				if (res.code == 200) 
					$scope.menu = res.data;
				else 
					$scope.menuEmpty = res.message || '获取菜单数据失败！';
			}).error(function(){
				$scope.menuEmpty = '无法获取菜单数据！';
			});
		}

		/* 获取菜单权限列表 */
		$scope.getMenuAuth = function (menuid) 
		{
			$scope.menuAuths = [];
			$scope.authEmpty = '载入中...';
			$scope.menuid = menuid;
			$http.get(
				_c.appPath + 'system/menu/get_menu_auth/' + parseInt(menuid)
			).success(function(res){
				if (res.code == 200) 
					$scope.menuAuths = res.data;
				else 
					$scope.authEmpty = res.message || '获取菜单数据失败！';
			}).error(function(){
				$scope.authEmpty = '无法获取菜单数据！';
			});
		}

		/* 显示下级菜单 */
		$scope.showSubMenu = function (menuid) 
		{
			$scope.menuid = menuid;
			$scope.fold[menuid] = $scope.fold[menuid] ? false : true;
			$scope.menuAuths = [];
			$scope.authEmpty = '目录菜单没有权限项！';
		}

		/* 获取职位的权限和菜单 */
		$scope.getPositionMenuAuth = function (positionid) 
		{
			$http.get(
				_c.appPath + 'system/position/get_menu_auth/' + parseInt(positionid)
			).success(function(res){
				if (res.code != 200) {
					$scope.authEmpty = res.message || '获取菜单数据失败！';
				} else {
					$scope.selectedMenus = res.data.menu; 		// 选中的菜单
					$scope.selectedAuths = res.data.auth; 		// 选中的权限
				}
			}).error(function(){
				alert('无法获取当前职位的菜单及权限信息！');
			});
		}

		/* 新建/修改职位弹框 */
		$scope.initModal = function (position) 
		{
			/* 获取菜单树 */
			$scope.getMenuTree();
			$scope.dTitle = "添加职位";
			$scope.position = {name : '', menuids : {}, authids : {}};
			$scope.action = 'create';

			if (position != undefined) {
				$scope.position = { id : position.id, name : position.name, menuids : position.menuids, authids : position.authids };
				$scope.dTitle = "修改职位信息";
				$scope.action = 'modify';
			}

			$('#createPositionModal').modal('show');
		}

		/* 新建或修改职位提交 */
		$scope.submitPosition = function () 
		{
			if ($scope.position.name.trim() == '') 
			{
				$.gritter.add({ text: '请填写职位名称！', time:1000, class_name:'gritter-error gritter-center' });
				return ;
			}
			if ($.isEmptyObject($scope.position.authids) && $.isEmptyObject($scope.position.menuids)) 
			{
				$.gritter.add({ text: '请选择可用菜单和权限！', time:1000, class_name:'gritter-error gritter-center' });
				return ;
			}

			$scope.editPending = true;
			$http.post(
				_c.appPath + 'system/position/' + $scope.action, 
				$scope.position
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ text: '操作成功！', time:800, class_name:'gritter-success gritter-center' });
					$('#createPositionModal').modal('hide');
				} else {
					$.gritter.add({ text: res.message || '操作失败，请稍后尝试！', time:1000, class_name:'gritter-error gritter-center' });
				}
				$scope.editPending = false;
				$scope.getPositionList();
			}).error(function(){
				$.gritter.add({ text: '操作失败，请稍后尝试！', time:1000, class_name:'gritter-error gritter-center' });
				$scope.editPending = false;
			})
		}

		/* 删除职位提示 */
		$scope.position_id = 0;
		$scope.delete = function (id)
		{
			$("#deletecodeBox").modal('show');
			$scope.position_id = id;
		}

		/* 删除职位 */
		$scope.deleteCfm = function () {  
			if ($scope.position_id > 0) {
				$scope.deletePending = true;
				$http.post(
					_c.appPath+'system/position/delete', 
					{ id : $scope.position_id }
				).success(function(res){
					if(res.code == 200) 
						$.gritter.add({ text: '删除成功!', time:'1000', class_name:'gritter-success gritter-center' });
					else 
						$.gritter.add({ text: res.message || '删除失败!', time:'1000', class_name:'gritter-error gritter-center' });

					$("#deletecodeBox").modal('hide');
					$scope.deletePending = false;
					$scope.getPositionList();
				}).error(function(){
					$.gritter.add({ text: '无法删除!', time:'1000', class_name:'gritter-error gritter-center' });
					$scope.deletePending = false;
				})
			}
		}

	}]);
});