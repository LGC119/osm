'use strict';

define(['me'], function (me) {
	me.controller('CompanyController', ['$scope', '$http', function ($scope, $http) {
		$scope.empty = '载入中...';
		$scope.name = '';

		/* 获取品牌客户，公司 */
		$scope.companies = {};
		$scope.get_companies = function () 
		{
			$http.get(
				_c.appPath + 'company/company/get_list', 
				{
					items_per_page : $scope.companies.items_per_page || 10, 
					current_page : $scope.companies.current_page || 1
				}
			).success(function(res){
				if (res.code == 200) 
					$scope.companies = res.data;
				else 
					$scope.empty = res.message || '获取品牌客户失败！';
			}).error(function(){
				$scope.empty = '无法获取品牌客户！';
			});
		}

		/* 添加 品牌客户 */
		$scope.create_company = function () 
		{
			var name = $scope.name.trim() || '';
			if (name === '') return false;

			$http.post(
				_c.appPath + 'company/company/create', 
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

		/* 修改 品牌客户 */
		$scope.modify_company = function () 
		{
			alert('暂不支持修改操作！');
		}

		/* 删除 品牌客户 */
		$scope.delete_company = function (item) 
		{
			/* 确认删除 */
			if (confirm('确认删除“'+item.name+'”?')) 
			{
				$http.get(
					_c.appPath + 'company/company/delete/' + parseInt(item.id)
				).success(function(res){
					if (res.code == 200) 
						$.gritter.add({
							title : '成功', 
							text : '删除成功！', 
							time : 500, 
							class_name : 'gritter-success gritter-center'
						});
					else 
						$.gritter.add({
							title : '失败', 
							text : res.message || '删除操作失败！', 
							time : 1200, 
							class_name : 'gritter-danger gritter-center'
						});

					$scope.get_companies();
				}).error(function(){
					$.gritter.add({
						title : '失败', 
						text : '无法执行删除操作！', 
						time : 1200, 
						class_name : 'gritter-warning gritter-center'
					});
				})
			}
		}

	}]);
});