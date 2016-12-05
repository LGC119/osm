'use strict';

define(['me'], function (me) {
	me.controller('AdvEventListController', ['$scope', '$sce', '$http', 'Event', function ($scope, $sce, $http, Event) {
		// 获取用户组
		$scope.events_list = [];
		$scope.empty = '载入中...';

		$scope.p = {type:-1, industry:-1, start:'', end:'', keyword:'',from:3};

		$scope.types = {
			0 : '默认', 
			1 : '抽奖', 
			2 : '线下', 
			3 : '调查', 
			4 : '会员绑定'
		};

		$scope.industries = {0:'默认', 1:'快消', 2:'汽车', 3:'数码'};

		/* 获取所有活动信息 */
		$scope.get_list = function () 
		{
			var verify = verify_params();
			if (verify !== true) {
				alert(verify);
				return false;
			}

			$scope.p.current_page = $scope.events_list.current_page || 1;
			$scope.p.items_per_page = $scope.events_list.items_per_page || 10;

			$http.post(
				_c.appPath + 'meo/event/get_list',
				$scope.p
			).success(function(res){
				if (res.code == 200) 
					$scope.events_list = res.data;
				else 
					$scope.empty = res.message || '获取活动信息失败！';
			}).error(function(){
				$scope.empty = '获取活动信息失败！';
			})
		}

		function verify_params () 
		{
			if ($scope.p.start != '' && ! /^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.start)) 
				return '您输入的起始日期格式不正确，正确格式[2014-05-01]！';

			if ($scope.p.end != '' && ! /^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.end)) 
				return '您输入的结束日期格式不正确，正确格式[2014-05-01]！';

			if ($scope.p.keyword.length > 40) 
				return '关键字的长度请不要超过40！';

			return true;
		}

		/* 停止活动 */
		$scope.stop = function (event) 
		{
			if (confirm('确定停止活动:' + event.event_title + '?')) {
				$http.get(
					_c.appPath + 'meo/event/stop/' + event.id
				).success(function(res){
					if (res.code == 200) {
						alert('操作成功！');
						$scope.get_list();
					} else {
						alert(res.message || '操作失败！');
					}
				}).error(function(){
					alert('操作失败！');
				});
			}
		}

		/* 删除活动 */
		$scope.delete = function (event) 
		{
			if (confirm('确定删除活动:' + event.event_title + '?')) {
				$http.get(
					_c.appPath + 'meo/event/delete/' + event.id
				).success(function(res){
					if (res.code == 200) {
						alert('删除成功！');
						$scope.get_list();
					} else {
						alert(res.message || '删除失败！');
					}
				}).error(function(){
					alert('删除失败！');
				});
			}
		}

	}]);
});