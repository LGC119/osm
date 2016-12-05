'use strict';

/* 微博舆情关键词设置控制器 */
define(['me'], function (me) {
	me.controller('WbKeywordController', ['$scope', '$http', function ($scope, $http) {

		$scope.type = 0;
		$scope.cmn_type = {};
		$scope.keywords = {};

		// 获取关键词列表
		$scope.get_keywords = function ()
		{
			$scope.empty = 'loading...';
			$http.post(
				_c.appPath + 'meo/keyword/get_list/' + $scope.type, 
				{
					current_page : $scope.keywords.current_page || 1, 
					items_per_page : $scope.keywords.items_per_page || 10
				}
			).success(function(res){
				if (res.code == 200) {
					$scope.keywords = res.data;
				} else {
					$scope.empty = res.message || '获取关键词出错！';
					$scope.keywords = {list:[]};
				}
				/* 清除选中的关键词 */
				$scope.selectedKeywords = {};
			}).error(function(){
				$scope.empty = '无法获取设置的关键词！';
			});

			return ;
		}

		// 设置新的关键词
		$scope.add_keyword = function ()
		{

			if (typeof $scope.keyword == 'undefined' || $scope.keyword == '') {
				alert('请输入要添加的关键词！');
				return false;
			}

			if ($scope.keyword.length > 20) {
				alert('关键词请不要超过20个字符！');
				return ;
			}

			var cmn_type = 0;
			for (var i in $scope.cmn_type) 
				if ($scope.cmn_type[i] == true)
					cmn_type += parseInt(i);
			$http.post(
				_c.appPath + 'meo/keyword/add',
				{
					keyword: $scope.keyword, 
					type: $scope.type, 
					cmn_type : cmn_type
				}
			).success(function(res){
				if (res.code == 200) 
					$scope.get_keywords();
				else 
					alert(res.message || '添加关键词失败！');
			}).error(function(){
				alert('无法添加关键词，请稍后尝试！');
			});
		}

		// 删除关键词
		$scope.delete_keyword = function (ids)
		{
			if ( ! confirm('确定删除该关键词？')) return false;

			$http.post(
				_c.appPath + 'meo/keyword/delete/' + ids 
			).success(function(res){
				if (res.code == 200)
					$scope.get_keywords();
				else 
					alert(res.message || '删除排除关键词失败！');
			}).error(function(){
				alert('无法删除排除关键词！');
			});
		}

		/* 批量删除关键词 */
		$scope.selectedKeywords = {};
		$scope.delete_batch = function () 
		{
			if ($scope.type == 0) return false; // 舆情监控关键词不能批量删除
			// 获取选中的关键词
			var selectedKeywords = [];
			for (var i in $scope.selectedKeywords) 
				if ($scope.selectedKeywords[i] == true)
					selectedKeywords.push(parseInt(i));

			if (selectedKeywords.length == 0) return false;
			// 请求批量删除
			$http.post(
				_c.appPath + 'meo/keyword/delete_batch', 
				{ids:selectedKeywords}
			).success(function(res){
				if (res.code == 200)
					$scope.get_keywords();
				else 
					alert(res.message || '删除排除关键词失败！');
			}).error(function(){
				alert('无法删除排除关键词！');
			});
		}

		// 修改关键词状态
		$scope.change_status = function (id)
		{
			id = parseInt(id);

			if (id < 1) return false;

			$http.get(
				_c.appPath + 'meo/keyword/change_status/' + id
			).success(function(res){
				if (res.code == 200) 
					$scope.get_keywords();
				else 
					alert(res.message || '修改关键词状态失败！');
			}).error(function(){
				alert('无法修改该关键词的状态！');
			});
		}

		/* 修改关键词阈值 */
		$scope.editThreshold = function (item, showEdit) 
		{
			if (typeof item != 'object') return false;

			var threshold = parseInt(item.total_threshold);
			if (threshold < 0) 
			{
				alert('请填写一个大于零的数字！');
				return ;
			}

			$http.post(
				_c.appPath + 'meo/keyword/edit_threshold', 
				{id:item.id, threshold:threshold}
			).success(function(res){
				if (res.code==200) 
					showEdit = false;
				else 
					alert(res.message || '修改失败，请稍后尝试！');
				$scope.get_keywords();
			}).error(function(){
				alert('无法修改阈值，请稍后尝试！');
			})
		}

	}]);
});
