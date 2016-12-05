'use strict';

define(['me'], function (me) {
	me.controller('StaffController', ['$scope', '$http', function ($scope, $http) {
		$scope.company_empty = '载入中...';
		$scope.staff_empty = '请选择公司查看员工！';

		/* 获取品牌客户，公司 */
		$scope.companies = {};
		$scope.getCompanies = function () 
		{
			$http.post(
				_c.appPath + 'company/company/get_list', 
				{
					items_per_page : $scope.companies.items_per_page || 10, 
					current_page : $scope.companies.current_page || 1
				}
			).success(function(res){
				if (res.code == 200) 
					$scope.companies = res.data;
				else 
					$scope.company_empty = res.message || '获取品牌客户失败！';
			}).error(function(){
				$scope.company_empty = '无法获取品牌客户！';
			});
		}

		/* 获取品牌客户，公司 */
		$scope.getStaffs = function () 
		{
			if (typeof $scope.selectedCompany == 'undefined' || $scope.selectedCompany == null) 
				return false;

			var id = $scope.selectedCompany.id || 0;
			if (id < 1) return false;

			$scope.staffs = {};
			$scope.staff_empty = '载入中...';
			$http.post(
				_c.appPath + 'company/staff/get_list', 
				{
					company_id : id, 
					items_per_page : $scope.staffs.items_per_page || 10, 
					current_page : $scope.staffs.current_page || 1
				}
			).success(function(res){
				if (res.code == 200) 
					$scope.staffs = res.data;
				else 
					$scope.staff_empty = res.message || '获取品牌客户失败！';
			}).error(function(){
				$scope.staff_empty = '无法获取品牌客户！';
			});
		}

		/* 添加 品牌客户 */
		$scope.create_staff = function () 
		{
			var name = $scope.name.trim();
			if (name === '') return false;

			$http.post(
				_c.appPath + 'company/staff/create', 
				{name:name}
			).success(function(res){
				if (res.code == 200) {
					$scope.addnew = false;
					$.gritter.add({
						title : '成功', 
						text : '添加公司成功！', 
						time : 500, 
						class_name : 'gritter-success gritter-center'
					});
				} else {
					$.gritter.add({
						title : '失败', 
						text : res.message || '添加公司失败！', 
						time : 1200, 
						class_name : 'gritter-danger gritter-center'
					});
				}

				$scope.get_companies();
			}).error(function(){
				$.gritter.add({
					title : '失败', 
					text : '无法添加公司！', 
					time : 1000, 
					class_name : 'gritter-warning gritter-center'
				});
			})
		}

		/* 删除 品牌客户 */
		// $scope.delete_staff = function (item) 
		// {
		// 	/* 确认删除 */
		// 	if (confirm('确认删除“'+item.name+'”?')) 
		// 	{
		// 		$http.get(
		// 			_c.appPath + 'company/staff/delete/' + parseInt(item.id)
		// 		).success(function(res){
		// 			if (res.code == 200) 
		// 				$.gritter.add({
		// 					title : '成功', 
		// 					text : '删除成功！', 
		// 					time : 500, 
		// 					class_name : 'gritter-success gritter-center'
		// 				});
		// 			else 
		// 				$.gritter.add({
		// 					title : '失败', 
		// 					text : res.message || '删除操作失败！', 
		// 					time : 1200, 
		// 					class_name : 'gritter-danger gritter-center'
		// 				});

		// 			$scope.get_companies();
		// 		}).error(function(){
		// 			$.gritter.add({
		// 				title : '失败', 
		// 				text : '无法执行删除操作！', 
		// 				time : 1200, 
		// 				class_name : 'gritter-warning gritter-center'
		// 			});
		// 		})
		// 	}
		// }

	}]);
});