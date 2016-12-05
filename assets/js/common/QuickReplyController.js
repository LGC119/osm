'use strict';

define(['me'], function (me) {
	me.controller('QuickReplyController', ['$scope', '$http', '$sce', function($scope, $http, $sce) {

		$scope.qrs_empty = '加载中...';
		$scope.quickReplies = {};

		$scope.getQuickReplies = function () {
			var params = {
				current_page: $scope.quickReplies.current_page || 1,
				items_per_page: $scope.quickReplies.items_per_page || 20
			};
			$http.get(
				_c.appPath + 'common/quick_reply/get_qrs?' + $.param(params)
			).success(function (res) {
				if (res.code == 200) {
					$scope.quickReplies = res.data;
				} else if (res.code == 204) {
					$scope.qrs_empty = '暂无记录';
					$scope.quickReplies = {};
				} else {
					$.gritter.add({
						title : '错误', 
						text : res.message || '获取智库信息出错！', 
						time : 2000, 
						class_name : 'gritter-warning gritter-center'
					});
				}
			}).error(function (res) {
				$.gritter.add({
					title : '错误', 
					text : '无法获取智库', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
			});
		}



		$scope.edit = function () 
		{
			var question = $scope.question;
			var answer = $scope.answer;

			if (question.length > 480 || question.length < 1) {
				alert('问题的长度请控制在480个字符以内！');
				return false;
			}
			if (answer.length > 1800 || answer.length < 1) {
				alert('答案的长度请控制在1800个字符以内！');
				return false;
			}

			if ($scope.id > 0) {
				$http.post(
					_c.appPath + 'common/quick_reply/edit/' + $scope.id,
					{q:question, a:answer}
				).success(function(res){
					if (res.code == 200) {
						$.gritter.add({
							title : '成功', 
							text : '修改成功！', 
							time : 800, 
							class_name : 'gritter-success gritter-center'
						});
						$scope.showAdd = false;
					} else {
						$.gritter.add({
							title : '错误', 
							text : res.message || '修改失败，请稍后重试！', 
							time : 1000, 
							class_name : 'gritter-warning gritter-center'
						});
					}
					$scope.getQuickReplies();
				}).error(function(){
					$.gritter.add({
						title : '错误', 
						text : '修改失败，请检查网络并稍后尝试！', 
						time : 1000, 
						class_name : 'gritter-warning gritter-center'
					});
					$scope.getQuickReplies();
				});
			} else {
				// 新增记录
				$http.post(
					_c.appPath + 'common/quick_reply/add',
					{q:question, a:answer}
				).success(function(res){
					if (res.code == 200) {
						$.gritter.add({
							title : '成功', 
							text : '添加成功！', 
							time : 800, 
							class_name : 'gritter-success gritter-center'
						});
						$scope.showAdd = false;
					} else {
						$.gritter.add({
							title : '错误', 
							text : res.message || '添加失败，请检查网络并稍后尝试！', 
							time : 1000, 
							class_name : 'gritter-warning gritter-center'
						});
					}
					$scope.getQuickReplies();
				}).error(function(){
					$.gritter.add({
						title : '错误', 
						text : '添加失败，请检查网络并稍后尝试！', 
						time : 1000, 
						class_name : 'gritter-warning gritter-center'
					});
					$scope.getQuickReplies();
				});
			}

		}

		$scope.set_edit = function (item) {
			$scope.id = item.id;
			$scope.question = item.question;
			$scope.answer = item.answer;
			$scope.showAdd = true;
		}

		$scope.delete = function (id) 
		{
			if (parseInt(id) < 1) return ;

			if (confirm('确定删除这条记录么？')) 
			{
				$http.get(
					_c.appPath + 'common/quick_reply/delete/' + parseInt(id)
				).success(function (res) {
					if (res.code == 200) 
						$.gritter.add({
							title : '成功', 
							text : '删除成功！', 
							time : 800, 
							class_name : 'gritter-success gritter-center'
						});
					else 
						$.gritter.add({
							title : '错误', 
							text : res.message || '删除失败，请稍后重试！', 
							time : 1000, 
							class_name : 'gritter-warning gritter-center'
						});
					$scope.getQuickReplies();
				}).error(function () {
					$.gritter.add({
						title : '错误', 
						text : '删除失败，请稍后重试！', 
						time : 1000, 
						class_name : 'gritter-warning gritter-center'
					});
				})
			}
		}

	}]);
});