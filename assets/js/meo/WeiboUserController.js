'use strict';

define(['me'], function (me) {
	me.controller('WeiboUserController', ['$scope', '$sce', '$http', '$modal', 'WeiboUser', 'WeiboUserGroup', 'Tag', 'User', '$routeParams', function ($scope, $sce, $http,$modal, WeiboUser, WeiboUserGroup, Tag, User, $routeParams) {

		$scope.searchGroupId = $routeParams.searchGroupId;
		/*身份对应图标列表*/
		$scope.common = {};
		$scope.common.verifiedIcons = {
			'approve_co': 'assets/img/approve_co.png',
			'approve': 'assets/img/approve.png',
			'daren': 'assets/img/daren.png'
		}
		$scope.common.verifiedType = {
			'200': $scope.common.verifiedIcons.daren,
			'220': $scope.common.verifiedIcons.daren,
			'0': $scope.common.verifiedIcons.approve,
			'2': $scope.common.verifiedIcons.approve_co,
			'3': $scope.common.verifiedIcons.approve_co,
			'4': $scope.common.verifiedIcons.approve_co,
			'5': $scope.common.verifiedIcons.approve_co,
			'6': $scope.common.verifiedIcons.approve_co,
			'7': $scope.common.verifiedIcons.approve_co
		}

		/* 搜索参数 */
		$scope.selectedTags = {};
		// 存一些临时变量
		$scope.post = { country:'' };
		$scope.group = {};

		/* 基础筛选参数 */
		$scope.filters = {
			'verify_type_sina':[{key:'-1', val:'普通'}, {key:'200', val:'达人'}, {key:'0', val:'个人认证'}, {key:'2', val:'企业认证'}],
			'verify_type_tx':[{key:'999', val:'普通'}, {key:'-2', val:'认证'}],
			'followers_count':[{key:'0-99', val:'0 - 99'}, {key:'100-499', val:'100 - 499'}, {key:'500-999', val:'500 - 999'}, {key:'1000-4999', val:'1000 - 4999'}, {key:'5000-9999', val:'5000 - 9999'}, {key:'10000-49999', val:'10000 - 49999'}, {key:'50000-99999', val:'50000 - 99999'}, {key:'100000-', val:'100000 +'}],
			'statuses_count':[{key:'0-99', val:'0 - 99'}, {key:'100-499', val:'100 - 499'}, {key:'500-999', val:'500 - 999'}, {key:'1000-4999', val:'1000 - 4999'}, {key:'5000-9999', val:'5000 - 9999'}, {key:'10000-', val:'10000 +'}],
			'gender':[{key:1, val:'男'}, {key:2, val:'女'}, {key:0, val:'未知'}],
			'relation':[{key:0, val:'无关系'}, {key:1, val:'关注我的'}, {key:2, val:'我关注的'}, {key:3, val:'双向关注'}],
			'doublev':[{key:0, val:'未关联'}, {key:1, val:'已关联'}],
			'province':_c.get_city,
			'subscribe':[{key:0, val:'未订阅'}, {key:1, val:'已订阅'}]
		};

		$scope.filter_labels = {
			'verify_type_sina' : '微博身份', 
			'verify_type_tx' : '微博身份', 
			'followers_count' : '粉丝数', 
			'statuses_count' : '微博数', 
			'gender' : '性别', 
			'account' : '账号', 
			'doublev' : '双微关联', 
			'relation' : '品牌关系', 
			'province' : '地区', 
			'city' : '',
			'tags[]' : '标签',
			'subscribe' : '订阅'
		};

		/* 获取筛选参数 */
		$http.get(
			_c.appPath + 'meo/wb_user/get_filter_params'
		).success(function(res){
			if (res.code == 200) {
				$scope.filters['account'] = res.data.account;
			} else {
				$.gritter.add({
					title : '提示', 
					text : res.message || '获取筛选参数失败！', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
			}
		}).error(function(res){
			$.gritter.add({
				title : '提示', 
				text : res.message || '无法获取筛选参数！', 
				time : 2000, 
				class_name : 'gritter-warning gritter-center'
			});
		});
		/* 获取筛选参数 END */

		/*获取热门标签*/
		$http.get(
			_c.appPath + 'meo/stats_tag/top_tags'
		).success(function (res) {
			if (res.code == 200) 
				$scope.common.hotTags = res.data;
		});


		/* 活动筛选相关 */
		$scope.events_empty = '请使用关键词筛选活动信息！';
		$scope.events_list = {items_per_page:5, current_page:1};
		$scope.post.events = {};
		$scope.ev_types = { 0 : '默认', 1 : '抽奖', 2 : '线下', 3 : '调查', 4 : '会员绑定' };
		$scope.ev_industries = {0:'默认', 1:'快消', 2:'汽车', 3:'数码'};
		$scope.get_events = function () // 获取活动
		{
			$scope.ev_pending = true;
			$http.post(
				_c.appPath + 'meo/event/get_list',
				{
					// start : format_date($scope.events_list.start), 
					// end : format_date($scope.events_list.end), 
					keyword : $scope.events_list.keyword, 
					current_page : $scope.events_list.current_page || 1, 
					items_per_page : $scope.events_list.items_per_page || 5
				}
			).success(function(res){
				if (res.code == 200) {
					$scope.events_list = res.data;
				} else {
					$scope.events_list = {};
					$scope.events_empty = res.message || '获取活动信息失败！';
				}
				$scope.ev_pending = false;
			}).error(function(){
				$scope.events_empty = '获取活动信息失败！';
				$scope.ev_pending = false;
			});
		}
		$scope.pushEvent = function () // 选中
		{
			for (var i in $scope.events_list.events) 
				if ($scope.post.events[$scope.events_list.events[i].id] != true) {
					$scope.evs_checked = false;
					return ;
				}
			$scope.evs_checked = true;
		}
		$scope.allEvents = function () // 全选
		{
			var evs = $scope.events_list.events;
			if (evs == undefined || evs.length == 0)
				return false;

			for (var e in evs) {
				if ($scope.post.events[evs[e].id] != true) {
					for (var i in evs) 
						$scope.post.events[evs[i].id] = true;
					return true;
				}
				delete($scope.post.events[evs[e].id]);
			}
		}
		/* 活动筛选相关 */

		/* 舆情关键词相关 */
		$scope.keywords_empty = 'loading...！';
		$scope.post.keywords = {};
		$scope.get_keywords = function () // 获取关键词
		{
			if ($scope.keywords != undefined) 
				return false;
			$http.get(
				_c.appPath + 'meo/keyword/get_list/0'
			).success(function(res){
				if (res.code == 200) 
					$scope.keywords = res.data.list;
				else 
					$scope.keywords_empty = res.message || '获取关键词失败！';
			}).error(function(){
				$scope.keywords_empty = '获取关键词失败！';
			});
		}
		$scope.pushKeyword = function (id) 
		{
			if ($scope.post.keywords[id] == true)
				delete($scope.post.keywords[id]);
			else 
				$scope.post.keywords[id] = true;
		}
		/* 舆情关键词相关 */

		/* 沟通记录相关 */
		$scope.interacts_empty = '请输入关键词搜索微博！';
		$scope.interacts_list = {keyword:'', items_per_page:5, current_page:1};
		$scope.post.interacts = {};
		$scope.get_interacts = function () 
		{
			var k = $scope.interacts_list.keyword ? $scope.interacts_list.keyword.trim() : '';
			$scope.ia_pending = true;
			$http.post(
				_c.appPath + 'meo/wb_user/get_timeline', 
				{
					keyword : k, 
					current_page : $scope.interacts_list.current_page || 1, 
					items_per_page : $scope.interacts_list.items_per_page || 5
				}
			).success(function(res){
				if (res.code == 200)
					$scope.interacts_list = res.data;
				else 
					$scope.interacts_empty = res.message || '搜索微博失败！';
				$scope.ia_pending = false;
				$scope.interacts_list.keyword = k;
			}).error(function(){
				$scope.interacts_empty = '无法搜索微博！';
				$scope.ia_pending = false;
				$scope.interacts_list.keyword = k;
			});
		}
		/* 沟通记录相关 */

		/* 设定日期格式 0000-00-00 */
		var format_date = function (o) 
		{
			if (typeof o != 'object' || o == null) return '';

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			return y + '-' + m + '-' + d;
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

		// 点击标签的X按钮，删除已选择的标签
		$scope.removeSelectedTag = function (tagid) { delete $scope.selectedTags[tagid]; }

		/*筛选动作*/
		$scope.search = function () { $scope.getUsersList(); };

		// 清空提交参数
		$scope.clear = function () 
		{ 
			$scope.post = {}; 
			$scope.selectedTags = {}; 
			$scope.getUsersList(); 
		}

		// 获取用户列表
		var getArray = function (o) 
		{
			if (typeof o != 'object')
				return [];

			var arr = [];
			for (var i in o) 
			{
				var v = parseInt(i);
				if (v > 0 && o[i] == true) arr.push(v);
			}
			return arr;
		}

		$scope.common.usersList = { data: {} };
		$scope.getUsersList = function () {
			$scope.post.page = $scope.common.usersList.data.page || 1;
			$scope.post.perpage = $scope.common.usersList.data.perpage || 20;
			// 用户组来的根据组搜索请求
			if ($scope.searchGroupId != 0) 
				$scope.post.group_id = $scope.searchGroupId;

			// 格式化标签为Array;
			$scope.post['tags[]'] = [];
			for (var i in $scope.selectedTags) 
				$scope.post['tags[]'].push(parseInt(i));
			// 格式化活动历史、舆情关键词、沟通记录，为Array
			for (var i in {'events':0, 'keywords':0, 'interacts':0}) 
				if (typeof $scope.post[i] == 'object') 
					$scope.post[i+'[]'] = getArray($scope.post[i]);

			WeiboUser.usersList($scope.post, function (data) {
				$scope.common.usersList = data;
				if (data.code == 200) {
					$scope.common.selectedUsers = [];
					$scope.common.selectedCount = 0;
					$scope.common.isSelectAll = false;
				} else {
					$scope.common.usersList = {
						data: {}
					};
					$.gritter.add({
						title : '提示', 
						text : data.message, 
						time : 2000, 
						class_name : 'gritter-danger gritter-center'
					});
				}
			});
		}
		$scope.getUsersList();

		// 选中/反选用户
		$scope.common.selectedUsers = [];
		$scope.common.selectUser = function (user) {
			user.isSelected = !user.isSelected;
			if (user.isSelected) {
				$scope.common.selectedUsers = _c.arrayAddItem($scope.common.selectedUsers, user.id);
				$scope.common.selectedCount++;
				// 如果选中的人数等于筛选出的所有人，将全选勾选
				if ($scope.common.selectedCount == $scope.common.usersList.data.total_number) $scope.common.isSelectAll = true;
			} else {
				$scope.common.selectedUsers = _c.arrayRemoveItem($scope.common.selectedUsers, user.id);
				$scope.common.selectedCount--;
				// 反选用户时，将全选去除勾选状态
				$scope.common.isSelectAll = false;

			}
		}

		// 选择所有筛选出的用户
		$scope.common.selectedCount = 0;
		$scope.common.selectAll = function () {
			for (var i in $scope.common.usersList.data.users) {
				if (!$scope.common.isSelectAll) {
					$scope.common.usersList.data.users[i].isSelected = true;
					$scope.common.selectedCount = $scope.common.usersList.data.total_number;
				} else {
					$scope.common.usersList.data.users[i].isSelected = false;
					$scope.common.selectedCount = 0;
				}
			}
		}
		
		// 弹出用户详情
		$scope.showUserModal = function (e, id) {
			e.stopPropagation();
			var resolve = {
				common: function () {
					return $scope.common;
				},
				wb_user_id:function(){
					return id;
				}
			};
			var type = 'weibo';
			User.showUserModal($scope, userModalInstanceCtrl, resolve, type,id);
		}

		/* 用户详情弹窗控制器 */
		var userModalInstanceCtrl = ['$scope', '$modalInstance', 'common','userData','type','wb_user_id','id','Tag',  function ($scope, $modalInstance, common,userData, type,wb_user_id,id,Tag) {
			$scope.common = common;
			$scope.userData = userData;
			$scope.userData.sex = $scope.userData.gender;
			$scope.cmnHistory = {};

			$scope.communicationType = ['提到我', '评论我', '关键词', '私信'];

			$scope.getCommunicationHistory = function () {
				if ($scope.cmnHistory.data != undefined) return false;
				$http.post(
					_c.appPath + 'meo/wb_user/communications', 
					{
						wb_user_id: wb_user_id,
						current_page: $scope.cmnHistory.current_page || 1,
						items_per_page: $scope.cmnHistory.items_per_page || 10
					}
				).success(function (res) {
					if (res.code == 200) {
						$scope.cmnHistory = res.data;
					} else if (res.code == 204) {
						$scope.cmnHistory = {feeds:[]};
					} else {
						$.gritter.add({
							title : '错误',
							text : res.message,
							time : 2000,
							class_name : 'gritter-warning gritter-center'
						});
					}
				}).error(function (res) {
					$.gritter.add({
						title : '错误',
						text : '无法获取沟通记录',
						time : 2000,
						class_name : 'gritter-warning gritter-center'
					});
				});
			}

			// $scope.getCmnHistory();

			/* 时间选择控件 */
			$scope.today = function() {
				$scope.dt = new Date();
			};
			$scope.today();

			$scope.clear = function () {
				$scope.dt = null;
			};

			// Disable weekend selection
			$scope.dtdisabled = function(date, mode) {
				return false;
			};

			$scope.toggleMin = function() {
				$scope.minDate = $scope.minDate ? null : new Date();
			};
			$scope.toggleMin();

			$scope.dateOptions = {
				formatYear: 'yy',
				startingDay: 1
			};
			/* 时间选择控件 */

			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 点击编辑
			$scope.edit = function () {
				$scope.showEditInput = true;
				$scope.userData.birthdate = new Date($scope.userData.birthday);
				$scope.oriUserData = angular.copy($scope.userData);
			};

			// 确认编辑
			$scope.confirmEdit = function () {
				/* 修改用户的日期 */
				if (typeof $scope.userData.birthdate == 'object' && $scope.userData.birthdate != null) 
					var birthday = format_date($scope.userData.birthdate);
				else 
					var birthday = $scope.userData.birthday;

				User.resource.edit({
					'type': type,
					'id': wb_user_id,
					'data': {
						gender:$scope.userData.gender,
						full_name: $scope.userData.full_name,
						birthday: birthday,
						blood_type: $scope.userData.blood_type,
						constellation: $scope.userData.constellation,
						address1: $scope.userData.address1,
						tel1: $scope.userData.tel1,
						email1: $scope.userData.email1,
						qq1: $scope.userData.qq1
					}
				},function (data) {
					if (data.code == 200) {
						$.gritter.add({
							title : '成功',
							text : '修改成功',
							time : 1000,
							class_name : 'gritter-success gritter-center'
						});
						$scope.userData.birthday = birthday;
					}
					// $modalInstance.close();
					$scope.showEditInput = false;
				});
			}

			// 取消编辑
			$scope.cancelEdit = function () {
				$scope.userData = $scope.oriUserData;
				$scope.showEditInput = false;
			}

			$scope.showTagModal = function (wb_user_id, tags) {
				// 设定选中的标签
				var tempTags = {};
				if (tags.length > 0) {
					for (var i in tags) {
						tempTags[tags[i]['id']] = true;
					}
				}
				var resolve =  {
					wb_user_id: function () {
						return wb_user_id
					},
					tempTags: function () {
						return tempTags
					}
				};

				Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
			}

			// 标签选择弹框控制器
			var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'wb_user_id', 'tempTags', 'tags', function ($scope, $modalInstance, wb_user_id, tempTags, tags) {
				$scope.wb_user_id = wb_user_id;
				$scope.tags = tags;
				$scope.common = {tempTags:tempTags};

				// 将选中的标签添加入待发的标签数组
				$scope.pushTag = function (tagId, tagName) {
					var tagId = parseInt(tagId);
					var tag = { id: tagId, name: tagName }
					// 点击时checkbox的选中状态为点击前的状态
					if (!$scope.common.tempTags[tagId]) {
						$scope.common.tempTags[tagId] = true;
					} else {
						delete($scope.common.tempTags[tagId]);
					}
				}

				// 确认选择标签，并发布H5页面
				$scope.ok = function () {
					var tags = []; // 获取当前选中的标签
					if (!$.isEmptyObject($scope.common.tempTags)) {
						for (var i in $scope.common.tempTags) 
							if ($scope.common.tempTags[i] == true)
								tags.push(i);
					}

					$http.post(
						_c.appPath + 'common/user/edit_user_tag', 
						{
							user_id  : $scope.wb_user_id,
							tags        : tags
						}
					).success(function(res){
						if(res.code == 200){
							$.gritter.add({
								title: '修改标签成功!',
								time:'500',
								class_name:'gritter-success gritter-center'
							});
						}else{
							$.gritter.add({
								title: res.data,
								time:'1000',
								class_name:'gritter-error gritter-center'
							});
						}
						$modalInstance.close();
					}).error(function(){
						$.gritter.add({
							title: '修改标签失败，请检查网络，稍后尝试！',
							time:'1000',
							class_name:'gritter-error gritter-center'
						});
						$modalInstance.close();
					});
				}

				/* 取消修改 */
				$scope.cancel = function () { $modalInstance.close(); };
			}];
		}];
		/* 用户详情弹窗控制器 */

		// 将选中的用户加入指定组
		$scope.common.intoGroup = function () {
			// 判断是否选组
			if (!$scope.common.selectedGroup) {
				$.gritter.add({
					title : '提示', 
					text : '请选择分组', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			}
			// 判断是否选中人
			if (!$scope.common.selectedUsers.length && !$scope.common.isSelectAll) {
				$.gritter.add({
					title : '提示', 
					text : '请选择用户', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			}

			// 请求添加用户的地址
			var _addUser = function () {
				WeiboUserGroup.addUser({
					group_id: $scope.common.selectedGroup,
					user_ids: $scope.common.selectedUsers
				}, function (data) {
					if (data.code == 200) {
						$.gritter.add({
							title : '成功', 
							text : '添加用户至分组成功', 
							time : 2000, 
							class_name : 'gritter-success gritter-center'
						});
						$scope.getUsersList();
					} else {
						$.gritter.add({
							title : '失败', 
							text : data.message, 
							time : 2000, 
							class_name : 'gritter-danger gritter-center'
						});
					}
				});
			}

			// 选中 全选，则获取所有筛选条件下的人的id
			if ($scope.common.isSelectAll) {
				$scope.common.selectedUsers = [];
				WeiboUser.usersList({
					page: 1,
					perpage: $scope.common.selectedCount
				}, function (data) {
					// $scope.common.usersList = data;
					angular.forEach(data.data.users, function (v, k) {
						$scope.common.selectedUsers.push(v.id);
					});
					// 添加用户
					_addUser();
				});
			} else {
				// 添加用户
				_addUser();
			}
		}
		
		// 获取组列表
		$scope.getUserGroups = function () {
			var params = {
				// all_data: 1,
				arrange : 1, 
				current_page : 1, 
				items_per_page : 50, 
				status : 1
			}
			$scope.groupsList = WeiboUserGroup.getList(params);
		}
		$scope.getUserGroups();

		$scope.showGroupCreateModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'groupCreateModal',
				controller: groupCreateModalInstance,
				resolve: {
					group: function () {
						return $scope.group;
					},
					common: function () {
						return $scope.common;
					}
				}
			});
		}

		var groupCreateModalInstance = ['$scope', '$modalInstance', 'WeiboUserGroup', 'group', 'common', function ($scope, $modalInstance, WeiboUserGroup, group, common) {
			$scope.group = group;
			$scope.common = common;
			$scope.group.expires_date = new Date();
			$scope.common.minDate = new Date();

			$scope.openDatePicker = function($event) {
				$event.preventDefault();
				$event.stopPropagation();
				$scope.common.dtOpened = true;
			};
	   
			getGroupFeature();

			$scope.cancel = function () {
				$modalInstance.close();
			};

			$scope.ok = function () {
				createGroup($modalInstance);
			}
		}];

		// 获取筛选条件
		var getGroupFeature = function () {
			$scope.group.filter_param = '';
			$scope.group.tag_name = '';
			$scope.group.feature = '';

			/* 获取当前设定的条件 [用户特征文字描述] */
			$scope.post['tags[]'] = [];
			for (var i in $scope.post) {
				if (i == 'province') {
					if ($scope.post[i] < 1) {
						continue;
					};
					$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
					$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.post[i] + ' ';
					continue;
				};
				if (i == 'city') {
					if ($scope.post[i] < 1) {
						continue;
					};
					$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
					$scope.group.feature += $scope.filter_labels[i] + '' + $scope.post[i] + '; ';
					continue;
				};
				if (i == 'tags[]'){
					if ($.isEmptyObject($scope.selectedTags)) continue;
					for (var k in $scope.selectedTags) 
						$scope.group.tag_name += $scope.selectedTags[k].name + ' ';
					if ($scope.group.tag_name.trim() == '') continue;
					$scope.group.filter_param += i + '=' + $scope.group.tag_name + '&';
					$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.group.tag_name + '; ';
					continue;
				}
				for (var j in $scope.filters[i]) {
					if ($scope.filters[i][j].key == $scope.post[i]) {
						$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
						$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.filters[i][j].val + '; ';
						break;
					}
				}
			}
			// 移除结尾的&符号
			$scope.group.filter_param = $scope.group.filter_param.replace(/&$/ig, '');
		}

		// 创建组
		var createGroup = function ($modalInstance) {
			$scope.group.members_count = $scope.common.selectedCount;
			$scope.group.expires_in = '';
			if (typeof $scope.group.expires_date == 'object' && $scope.group.expires_date != null)
				$scope.group.expires_in = format_date($scope.group.expires_date);

			if ($scope.group.name == undefined || /^[\s]*$/ig.test($scope.group.name)) {
				alert('请填写组名称！');
				return false;
			} else if ( ! /^\d{4}-\d{1,2}-\d{1,2}/.test($scope.group.expires_in)) {
				alert('请按照格式填写有效期 - [2014-12-12] !');
				return false;
			} else if ($scope.group.desc == undefined || /^[\s]*$/ig.test($scope.group.desc)) {
				alert('请填写组描述！');
				return false;
				$('#create_group').modal('show');
			}

			var _createGroup = function () {
				WeiboUserGroup.create($scope.group, function (data) {
					if (data.code == 200) {
						$.gritter.add({
							title : '成功', 
							text : '创建分组成功', 
							time : 2000, 
							class_name : 'gritter-success gritter-center'
						});
						$scope.getUserGroups();
						$modalInstance.close();
					} else {
						$.gritter.add({
							title : '失败', 
							text : '创建分组失败', 
							time : 2000, 
							class_name : 'gritter-danger gritter-center'
						});
					}
				});
			}

			if ($scope.group.filter_param) {
				$scope.group.has_feature = 1;               
			}
			if ($scope.common.isSelectAll) {
				$scope.group.ids = [];
				$scope.group.is_locked = 0;
				var params = {};
				if ($scope.post) {
					params = $scope.post;
				}
				params.page = $scope.common.usersList.data.page || 1;
				params.perpage = $scope.common.usersList.data.perpage || 20;

				// 用户组来的根据组搜索请求
				if ($scope.searchGroupId != 0) {
					params.group_id = $scope.searchGroupId;
				}
				WeiboUser.usersList(params, function (data) {
					// $scope.common.usersList = data;
					angular.forEach(data.data.users, function (v, k) {
						$scope.group.ids.push(v.id);
					});
					_createGroup();
				});
			} else {
				_createGroup();
			} 
		}
	}]);
});