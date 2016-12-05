'use strict';

define(['me'], function (me) {
	me.controller('StaffController', ['$scope','$route', '$http', 'Staff', function($scope,$route, $http, Staff){
		$scope.empty = '载入中...';
		$scope.dTitle = "添加员工";

		$scope.getStaffList = function () 
		{
			Staff.getStaffList({},
			function(data){
				if (data.code == 200) {
					$scope.staffs = data.data;
				} else {
					$scope.empty = data.message;
				}
			},
			function(){
				$scope.empty = "网络不通，请稍后尝试！";
			});
		}

		Staff.getPositionList({},
				function(data){
					if (data.code == 200) {
						$scope.positions = data.data;
					} else {
						$.gritter.add({
							title : '错误', 
							text : '职位信息获取失败', 
							time : 1000, 
							class_name : 'gritter-warning gritter-center'
						});
					}
				},
				function(){
					$.gritter.add({
						title : '错误', 
						text : '网络错误，请稍后再试', 
						time : 1000, 
						class_name : 'gritter-warning gritter-center'
					});
				});


		$scope.initModal = function (staff) 
		{
			if (staff)
			{
				$scope.dTitle = "修改员工信息";
				$scope.isDis = true;
				$scope.staff = staff;	
			}
			else 
			{
				$scope.dTitle = "添加员工";
				$scope.isDis = false;
				$scope.staff = {
					position_id: $scope.positions[0].id 	// 默认选中职位列表中的第一个
				};
				$scope.tip = '';
			}
			$('#add_new').modal('show');
		}

		$scope.submit = function () 
		{
			var url = _c.appPath + 'system/staff';
			url += $scope.staff.id == undefined ? '/add_staff' : '/edit_staff';
			$http.post(
				url,
				$scope.staff 
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({
						title : '成功', 
						text : '操作成功', 
						time : 1000, 
						class_name : 'gritter-success gritter-center'
					});
					$('#add_new').modal('hide');
					$scope.getStaffList();
				} else {
					$.gritter.add({
						title : '错误', 
						text : res.message, 
						time : 1000, 
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function(){
				$.gritter.add({
					title : '错误', 
					text : '网络错误，请稍后再试', 
					time : 1000, 
					class_name : 'gritter-warning gritter-center'
				});
			});
		}

		//删除弹窗
		$scope.staff_id = '';
		$scope.delete = function (id)
		{
			$("#deletecodeBox").modal('show');
			$scope.staff_id = id;
		}

		//删除确认
		$scope.deleteCfm = function () {  
			if ($scope.staff_id) {
				$http.post(
					_c.appPath+'system/staff/del_staff',{
						id:$scope.staff_id
					} 
				).success(function(res){
					if(res.code == '200'){
							$.gritter.add({
								title: '删除成功!',
								time:'1000',
								class_name:'gritter-success gritter-center'
							});
							$("#deletecodeBox").modal('hide');
							$scope.getStaffList();
						}else{
							$.gritter.add({
								title: '删除失败!',
								time:'2000',
								class_name:'gritter-error gritter-center'
							});
							$("#deletecodeBox").modal('hide');
						}
				})
			}else{
				$.gritter.add({
					title: '数据不存在!',
					time:'500',
					class_name:'gritter-warning gritter-center'
				});
				return;
			}
		}

		$scope.do_message = function (id,do_message) {
			$http.post(
					_c.appPath+'system/staff/do_message',{
						id:id,
						do_message:do_message
					} 
				)
			$route.reload();
		}
	}]);
});
