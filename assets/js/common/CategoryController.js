'use strict';

define(['me'], function (me) {
	me.controller('CategoryController', ['$scope', '$http', '$modal', function($scope, $http, $modal){
		$scope.empty = "载入中...";

		/* 新增二级分类名 */
		$scope.sub_cat_names = {};

		/* 添加分类 */
		$scope.add = function (pid) 
		{
			if (pid == undefined) pid = 0;
			if (pid > 0)
				var name = $scope.sub_cat_names[pid];
			else 
				var name = $scope.top_cat;

			var res = _validateCategoryName(name);
			if (res !== true) {
				$.gritter.add({ title: '无法添加!', text:res, time:'1000', class_name:'gritter-warning gritter-center' });
				return false;
			}

			$http({
				url : _c.appPath + 'common/category/add_category?pid=' + pid + '&name=' + name.trim()
			}).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ title: '添加成功!', time:'800', class_name:'gritter-success gritter-center' });
					if (pid > 0) 
						$scope.sub_cat_names[pid] = '';
					else 
						$scope.top_cat = '';
					$scope.getCategoryList();
				} else {
					$.gritter.add({ title: '失败!', text: res.message || '添加分类失败，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
				}
			}).error(function(){
				$.gritter.add({ title: '失败!', text: '无法添加分类，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
			});
		}

		/* 显示编辑的对话框 */
		$scope.edit = function (item) 
		{
			var modalInstance = $modal.open({
				templateUrl: 'categoryEditModal',
				controller: categoryEditModal,
				size: 'xs',
				resolve: {
					data: function () {
						return {
							item : item, 
							categories : $scope.categories, 
							get_list : $scope.getCategoryList
						};
					}
				}
			});
		}

		/* 删除分类 */
		$scope.delete = function (item) 
		{
			var name = item.cat_name;
			if ($scope.categories.relation[item.id] != undefined) 
				name += ' 及其下级分类';

			if (confirm('确定删除分类：' + name + ' ?')) 
			{
				$http({
					url : _c.appPath + 'common/category/delete/' + item.id
				}).success(function(res){
					if (res.code == 200) 
						$.gritter.add({ title: '成功!', time:'500', class_name:'gritter-success gritter-center' });
					else 
						$.gritter.add({ title: '失败!', text: res.message || '删除分类失败，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
					$scope.getCategoryList();
				}).error(function(){
					$.gritter.add({ title: '失败!', text:'无法删除分类，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
				});
			}
			return ;
		}

		/* 获取分类的数据 */
		/* 原型为数组{'category' : $category, 'relation' : $relation} */
		$scope.getCategoryList = function () {
			$scope.empty = '载入中...';
			$http.get(
				_c.appPath + 'common/category/get_quick_cats'
			).success(function(res){
				if (res.code == 200)
					$scope.categories = res.data;
				else 
					$scope.empty = res.message || '获取分类信息数据失败！';
			}).error(function(){
				$scope.empty = '无法获取分类信息数据！';
			});

			return ;
		}

		/* 分类名编辑的弹框控制器 */
		var categoryEditModal = ['$scope', '$modalInstance', 'data', function ($scope, $modalInstance,data) {

			$scope.category = angular.copy(data.item);
			$scope.category.new_name = data.item.cat_name;
			var categories = data.categories;
			var get_list = data.get_list;

			$scope.ok = function () {
				var name = $scope.category.new_name.trim();
				var res = _validateCategoryName(name);
				if (res !== true) {
					$.gritter.add({ title: '失败!', text: res, time:'1000', class_name:'gritter-warning gritter-center' });
					return false;
				}

				$http.post(
					_c.appPath + 'common/category/edit/' + $scope.category.id, 
					{name:name}
				).success(function (res){
					if (res.code == 200) {
						$.gritter.add({ title: '成功!', time:'500', class_name:'gritter-success gritter-center' });
						$modalInstance.close();
					} else {
						$.gritter.add({ title: '失败!', text: res.message || '修改分类失败，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
					}
					get_list();
				}).error(function (){
					$.gritter.add({ title: '失败!', text: '无法修改分类，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
				});
			}

			$scope.cancel = function () {
				$modalInstance.close();
			}
		}];

		var _validateCategoryName = function (name) 
		{
			if (typeof name !== 'string') 
				return '分类名必须是字符类型！';
			if (name.trim() == '') 
				return '分类名不能为空！';
			if (name.length > 20) 
				return '分类名长度不能超过20个字符！';
			if ( ! /^[a-zA-Z\d\u0391-\uFFE5]+$/.test(name))
				return '分类名可包含中文、英文和数字，请不要包含特殊字符或空格！';

			return true ;
		}

		/* 修改分类的警戒值 */
		$scope.editThreshold = function (item, type) 
		{
			if (type != 'wb' && type != 'wx') return false;

			var key = type + '_threshold';
			var val = parseInt(item[key]);
			if (val < 0) {
				$.gritter.add({ title: '错误!', text: '警戒值请设定为一个非负整数！', time:'1000', class_name:'gritter-warning gritter-center' });
				return false;
			}

			$http.post(
				_c.appPath + 'common/category/edit_threshold', 
				{id:item.id, key:key, val:val}
			).success(function(res){
				if (res.code == 200) 
					item[type + 'ThresEdit'] = false;
				else 
					$.gritter.add({ title: '失败!', text: res.message || '修改警戒值失败，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
				$scope.getCategoryList();
			}).error(function(){
				$.gritter.add({ title: '失败!', text: res.message || '无法修改警戒值，请稍后尝试！', time:'1000', class_name:'gritter-error gritter-center' });
			})
		}


	}]);
});