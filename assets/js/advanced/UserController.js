'use strict';

define(['me'], function (me) {
	me.controller('AdvUserController', ['$scope', '$sce', '$http', '$modal', 'Tag', '$routeParams', function ($scope, $sce, $http, $modal, Tag, $routeParams) {

		$scope.userEmpty = 'loading...';
		$scope.usersList = {};
		$scope.selectedTags = {};
		$scope.eventsEmpty = '点击“搜索活动”筛选！';
		$scope.selectedEvents = {};

		$scope.post = {gender:-1, blood:-1, constellation:-1, fullname:'', tel:''};
		/* 用户血型 */
		$scope.consts = {
			gender : ['未知', '男', '女'], 
			blood_type : ['未知', 'A型', 'B型', 'AB型', 'O型'], 
			constellation : ['未知', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手', '摩羯', '水瓶', '双鱼']
		};
		/* 用户星座 */

		/* 获取热门标签 */
		$http.get(
			_c.appPath + 'meo/stats_tag/top_tags'
		).success(function (res) {
			if (res.code == 200)
				$scope.hotTags = res.data;
		});

		/* 获取用户列表 */
		$scope.getUserList = function () 
		{
			var params = $scope.getSearchParams ();
			$http.post(
				_c.appPath + 'mei/user/get_list', 
				params
			).success(function(res){
				$scope.usersList = {};
				if (res.code == 200) 
					$scope.usersList = res.data;
				else 
					$scope.userEmpty = res.message || '获取用户列表失败！';
			}).error(function(){
				$scope.userEmpty = '无法获取用户列表！';
			})
		}

		/* 点选用户 */
		$scope.selectedUsers = {};
		$scope.selectUser = function (id) 
		{
			if ($scope.selectedUsers[id] != 'undefined' && $scope.selectedUsers[id] === true) 
				delete($scope.selectedUsers[id]);
			else 
				$scope.selectedUsers[id] = true;
		}

		/* 创建组 */
		$scope.showGroupCreateModal = function () 
		{
			// 
		}

		$scope.showGroupCreateModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'groupCreateModal',
				controller: groupCreateModalInstance,
				resolve: {
					getGroupList : function () {
						return $scope.getGroupList;
					}
				}
			});
		}

		var groupCreateModalInstance = ['$scope', '$modalInstance', 'getGroupList', function ($scope, $modalInstance, getGroupList ) {

			$scope.group = {name:'', desc:''};
			var getGroupList = getGroupList;

			/* 创建组 */
			$scope.ok = function () 
			{
				var name = $scope.group.name;
				var desc = $scope.group.desc;

				if (name.trim()=='') 
				{
					$.gritter.add({ title : '失败', text : '请填写组名称！', time : 1000, class_name : 'gritter-error gritter-center' });
					return false;
				}

				$http.post(
					_c.appPath + 'mei/group/create', 
					{name:name, desc:desc}
				).success(function(res){
					if (res.code == 200) {
						$.gritter.add({ title : '成功', text : '创建分组成功', time : 500, class_name : 'gritter-success gritter-center' });
						getGroupList();
						$modalInstance.close();
					} else {
						$.gritter.add({ title : '失败', text : res.message || '创建用户组失败！', time : 1000, class_name : 'gritter-error gritter-center' });
					}
				}).error(function(){
					$.gritter.add({ title : '失败', text : '无法创建用户组！', time : 1000, class_name : 'gritter-error gritter-center' });
				});
			}

			$scope.cancel = function () { $modalInstance.close(); };
		}];

		/* 获取组列表 */
		$scope.getGroupList = function () 
		{
			$http.get(
				_c.appPath + 'mei/group/get_list'
			).success(function(res){
				if (res.code == 200) 
					$scope.groupList = res.data;
			});
		}

		/* 把选中用户加入到高级组中 */
		$scope.addUserToGroup = function () 
		{
			var selectedUsers = [];
			var selectedGroup = $scope.selectedGroup;

			if ( ! $.isEmptyObject($scope.selectedUsers)) {
				for (var i in $scope.selectedUsers) {
					if ($scope.selectedUsers[i] === true)
						selectedUsers.push(parseInt(i));
				}
			}

			if (selectedUsers.length == 0) {
				$.gritter.add({ title : '提示', text : '请先选中用户！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			}

			if (selectedGroup == null) {
				$.gritter.add({ title : '提示', text : '请先选中组！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			}

			$http.post(
				_c.appPath + 'mei/group/add_user', 
				{
					group_id:selectedGroup, 
					user_ids:selectedUsers
				}
			).success(function(res){
				if (res.code == 200) 
					$.gritter.add({ title : '成功', text : '添加成功！', time : 500, class_name : 'gritter-success gritter-center' });
				else 
					$.gritter.add({ title : '失败', text : res.message || '添加失败！', time : 1000, class_name : 'gritter-error gritter-center' });
			}).error(function(){
				$.gritter.add({ title : '失败', text : '无法添加到组！', time : 1000, class_name : 'gritter-error gritter-center' });
			})
		}

		/* 获取活动列表 */
		$scope.eventsList = {};
		$scope.getEvents = function () 
		{
			var post = {
				type : $scope.eventType,
				status : $scope.eventStatus,
				title : $scope.eventTitle, 
				page : $scope.eventsList.page || 1, 
				perpage : $scope.eventsList.perpage || 5 
			}

			$http.post(
				_c.appPath + 'mei/event/get_list', 
				post
			).success(function(res){
				if (res.code == 200)
					$scope.eventsList = res.data;
				else 
					$scope.eventsEmpty = res.message || '活动列表为空！';
			}).error(function(){
				$scope.eventsEmpty = '获取活动列表失败！';
			});
		}

		/* 获取搜索和分页参数 */
		$scope.getSearchParams = function () 
		{
			var params = {};
			params.current_page = 1;
			params.items_per_page = 20;
			var gender = parseInt($scope.post.gender);
			var blood = parseInt($scope.post.blood);
			var constellation = parseInt($scope.post.constellation);
			var name = $scope.post.fullname.trim();
			var tel = $scope.post.tel.trim();
			if (gender >= 0 && gender <= 2) params.gender = gender;
			if (blood >= 0 && blood <= 4) params.blood = blood;
			if (constellation >= 0 && constellation <= 12) params.constellation = constellation;
			if (name != '') params.name = name;
			if (tel != '') params.tel = tel;
			if ($scope.post.is_crm_user == 1 || $scope.post.is_crm_user == 2) params.is_crm_user = $scope.post.is_crm_user;
			/* 获取筛选标签 */
			if ($scope.post.tagType == 1 || $scope.post.tagType == 2) 
			{
				var tags = [];
				for (var i in $scope.selectedTags) 
					tags.push($scope.selectedTags[i]['id']);
				if (tags.length > 0) {
					params.tags = tags;
					params.tagType = $scope.post.tagType;
				}
			}
			/* 获取筛选活动 */
			if ( ! $.isEmptyObject($scope.selectedEvents)) 
			{
				var events = [];
				for (var i in $scope.selectedEvents) {
					if ($scope.selectedEvents[i] == true) 
						events.push(parseInt(i));
				}
				if (events.length > 0) 
					params.events = events;
			}
			return params;
		}

		/* 选中/取消选中热门标签 */
		$scope.pushTag = function (tagId, tagName) {
			var tag = {
				'name': tagName,
				'id': parseInt(tagId)
			}
			// 点击时checkbox的选中状态为点击前的状态
			if ($scope.selectedTags[tag.id] == undefined) 
				$scope.selectedTags[tag.id] = tag;
			else 
				delete($scope.selectedTags[tag.id]);
		}
		/* 删除标签 */
		$scope.removeSelectedTag = function (tagid) { delete $scope.selectedTags[tagid]; }
		$scope.showTagModal = function () {
			var resolve =  {
				tags: function () {
					return $scope.tags;
				}, 
				selectedTags: function () {
					return $scope.selectedTags;
				} 
			};
			Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
		}
		/* 标签选择弹框控制器 */
		var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'selectedTags', function ($scope, $modalInstance, tags, selectedTags) {
			$scope.selectedTags = selectedTags;
			$scope.tags = tags;
			$scope.common = {tempTags : {}};
			for (var i in $scope.selectedTags) 
				$scope.common.tempTags[i] = true;
			// 将选中的标签添加入待发的标签数组
			$scope.pushTag = function (tagId, tagName) {
				var tag = {
					'name': tagName,
					'id': parseInt(tagId)
				}
				// 点击时checkbox的选中状态为点击前的状态
				if ($scope.selectedTags[tag.id] == undefined) {
					$scope.common.tempTags[tag.id] = true;
					$scope.selectedTags[tag.id] = tag;
				} else {
					delete($scope.selectedTags[tag.id]);
					delete($scope.common.tempTags[tag.id]);
				}
			}
			
			$scope.cancel = function () { $modalInstance.close(); };

			// 确认选择标签
			$scope.ok = function () { $modalInstance.close(); }
		}];
		/* 标签选择弹框控制器 */

		/* 设定日期格式 0000-00-00 */
		var format_date = function (o) 
		{
			if (typeof o != 'object' || o == null) return '';

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			return y + '-' + m + '-' + d;
		}

	}]);
});