'use strict';

define(['me'], function (me) {
	me.controller('WeixinUserController', ['$scope', '$sce', 'WeixinUser', 'User', 'Tag', 'WeixinUserGroup', '$modal', '$http', '$routeParams', function ($scope,  $sce, WeixinUser, User, Tag, WeixinUserGroup, $modal, $http, $routeParams) {

		// 日期插件初始化
		$scope.bootDate = {};
		$scope.bootDate.dt = new Date();
		$scope.minDate = new Date();
		$scope.dateShow = false;
		/* 时间选择控件 */
        $scope.today = function () {
            $scope.dt = new Date();
        };
        $scope.today();

		$scope.clear = function () {
			$scope.dt = null;
		};

		// Disable weekend selection
		$scope.disabled = function (date, mode) {
			return false;
		};

		$scope.toggleMin = function () {
			$scope.minDate = $scope.minDate ? null : new Date();
		};
		$scope.toggleMin();

		$scope.openDatepicker = function ($event, target) {
			$event.preventDefault();
			$event.stopPropagation();

			$scope[target] = true;
		};

		$scope.dateOptions = {
			formatYear: 'yy',
			startingDay: 1
		};
		// return 0000-00-00 格式日期
		function date_format(o) {
			if (typeof o != 'object' || o == null)
				return '';

			var y = o.getFullYear();
			var m = ((o.getMonth() + 1) < 10) ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = (o.getDate() < 10) ? '0' + o.getDate() : o.getDate();
			return y + '-' + m + '-' + d;
		}
		/* 时间选择控件 */

		$scope.searchGroupId = $routeParams.searchGroupId;
		$scope.sendId = $routeParams.sendId;

		$scope.common = {
			groupSend:{}
		};

		/* 搜索参数 */
		$scope.selectedTags = {};
		// 存一些临时变量
		$scope.post = {
			country:''
		};
		$scope.group = {};
		$scope.common.checkedUser = true;

		// 获取用户列表
		$scope.common.usersList = {
			data: {}
		};

		/* 获取微信用户列表 */
		$scope.getUsersList = function () {

			var params = $scope.post || {};
            var sub_start = $scope.subscribe_start;
            var sub_end = $scope.subscribe_end;
            var comm_start = $scope.communication_start;
            var comm_end = $scope.communication_end;
            params.sub_start = date_format(sub_start);
            params.sub_end = date_format(sub_end);
            params.comm_start = date_format(comm_start);
            params.comm_end = date_format(comm_end);

            //params.subscribe_start = date_format(params.subscribe_start);
            //params.subscribe_end = date_format(params.subscribe_end);
            //params.communication_start = date_format(params.communication_start);
            //params.communication_end = date_format(params.communication_end);
         
			params.page = $scope.common.usersList.data.page || 1;
			params.perpage = $scope.common.usersList.data.perpage || 20;

			// 格式化标签为Array;
			params['tags[]'] = new Array();
			for (var i in $scope.selectedTags) 
				params['tags[]'].push(parseInt(i));
			// 用户组来的根据组搜索请求
			if ($scope.searchGroupId != 0) {
				params.group_id = $scope.searchGroupId;
			}
			if($scope.sendId != 0){
				params.sendId = $scope.sendId;
			}

			WeixinUser.select_user(params, function (data) {
				$scope.common.usersList = data;
				if(data.code == 200) {
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
						time : 1000, 
						class_name : 'gritter-warning gritter-center'
					});
				}
			});
		}
		$scope.getUsersList();

		/*筛选动作*/
		$scope.search = function () 
		{
			_formatSelectGroupSend();
			$scope.getUsersList();
		};

		var _formatSelectGroupSend = function () {
			var groupSend = [];
			for (var i in $scope.common.groupSend) {
				if ($scope.common.groupSend[i]) {
					groupSend.push(i);
				}
			}
			$scope.post['group_send[]'] = angular.copy(groupSend);
		}



		/* 基础筛选参数 */
		$scope.filters = {
			'sex':[{key:1, val:'男'}, {key:2, val:'女'}, {key:0, val:'未知'}],
			'relation':[{key:0, val:'无关系'}, {key:1, val:'关注我的'}, {key:2, val:'我关注的'}, {key:3, val:'双向关注'}],
			'doublev':[{key:0, val:'未关联'}, {key:1, val:'已关联'}],
			'province': _c.get_city
		};

		$scope.sexList = {
			'-1' : '全部性别',
			'0' : '未知',
			'1' : '男',
			'2' : '女'
		};

		$scope.filter_labels = { 
			'sex' : '性别',  
			'doublev' : '双微关联', 
			'relation' : '品牌关系', 
			'province' : '地区',
			'city' : '',
			'tags[]' : '标签',
			'group_send[]' : '群发历史'
		};

		// 返回性别的Key值
		$scope.returnSexKey = function(sex){
			for(var i in $scope.sexList){
				if($scope.sexList[i] == sex){
					return i;
				}
			}
		}

		$scope.groupSendList = {
			empty: '载入中...'
		};
		// 群发历史筛选列表获取
		$scope.getGroupSendList = function () {
			
			var params = {
				page: $scope.groupSendList.current_page || 1,
				perpage: $scope.groupSendList.items_per_page || 5
			}

			$http.get(
				_c.appPath+'mex/send/get_send_list?' + $.param(params)
			).success(function (data) {
				if (data) {
					$scope.groupSendList = data.data;
				} else {
					$scope.groupSendList = {
						data: {},
						empty: '暂无群发记录'
					}
				}
			});
		}


		/*获取热门标签*/
		$http.get(
			_c.appPath + 'meo/stats_tag/top_tags'
		).success(function (res) {
			if (res.code == 200) $scope.common.hotTags = res.data;
		});

		// 标签弹框
		$scope.showTagModal = function () {
			var resolve =  {
				tags: function () {
					return $scope.tags;
				},
				selectedTags: function () {
					return $scope.selectedTags
				}
			};
			Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
		}

		// 订阅标签弹框
		$scope.showTagModal_sub = function () {
			var resolve =  {
				tags: function () {
					return $scope.tags_sub;
				},
				selectedTags: function () {
					return $scope.selectedTags
				}
			};
			Tag.showTagModal_sub($scope, tagModalInstanceCtrl, resolve);
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
		// 标签选择弹框控制器
		var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'selectedTags',  function ($scope, $modalInstance, tags, selectedTags) {
			$scope.tags = tags;
			$scope.selectedTags = selectedTags;
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
			$scope.ok = function () {
                $modalInstance.close();
            }
		}];

		// 点击标签的X按钮，删除已选择的标签
		$scope.removeSelectedTag = function (tagid) { delete $scope.selectedTags[tagid]; }

		
		// 清空提交参数
		$scope.clear = function () { 
			$scope.post = {}; 
			// 清除群发选择
			$scope.common.groupSend = {};
			// 清除标签选择
			$scope.common.tempTags = {};
			$scope.common.tagName = [];
			$scope.getUsersList();
		}

		// 弹出用户详情
		$scope.showUserModal = function (wxUserId, $event) {
			// 阻止冒泡
			if ($event.stopPropagation) $event.stopPropagation();
			if ($event.preventDefault) $event.preventDefault();

			var resolve = {
				common: function () {
					return $scope.common;
				},
				wxUserId: function () {
					return wxUserId;
				},
				bootDate:function(){
					return $scope.bootDate;
				}
			};
			var type = 'weixin';
			User.showUserModal($scope, userModalInstanceCtrl, resolve, type, wxUserId);
		}

		var userModalInstanceCtrl = ['$scope', '$modalInstance', 'common', 'type', 'userData', 'wxUserId','bootDate',  function ($scope, $modalInstance, common, type, userData, wxUserId,bootDate) {
			$scope.common = common;
			$scope.userData = userData;
			$scope.bootDate = bootDate;
			$scope.userData.cmnHistory = {};

			$scope.getCmnHistory = function () {
				var params = {
					wx_user_id: wxUserId,
					current_page: $scope.userData.cmnHistory.current_page || 1,
					items_per_page: $scope.userData.cmnHistory.items_per_page || 10
				};
				$http.get(
					_c.appPath + 'mex/communication/cmn_history?' + $.param(params)
				).success(function (res) {
					if (res.code == 200) {
						$scope.userData.cmnHistory = res.data;
                        for(var cmni in $scope.userData.cmnHistory.feeds){
                        	for (var k in $scope.userData.cmnHistory.feeds[cmni].replies) {
                        		var reply = $scope.userData.cmnHistory.feeds[cmni].replies[k];
                        		if (reply.type != 'text')
	                                reply.content = $.parseJSON(reply.content);
                        	}
                        }
					} else if (res.code == 204) {
						$scope.userData.cmnHistory = {
							data: {}
						};
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

			$scope.getCmnHistory();

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
				var exec_time = $scope.userData.birthdate.getFullYear() + '-'
								+ ($scope.userData.birthdate.getMonth() + 1) + '-'
								+ $scope.userData.birthdate.getDate();
				User.resource.edit({
					'type': type,
					'id': wxUserId,
					'data': {
						full_name: $scope.userData.full_name,
						birthday: exec_time,
						gender:$scope.userData.gender,
						blood_type: $scope.userData.blood_type,
						constellation: $scope.userData.constellation,
						address1: $scope.userData.address1,
						tel1: $scope.userData.tel1,
						email1: $scope.userData.email1,
						qq1: $scope.userData.qq1
					}
				},function (data) {
					if (data.code == 200) {
						$scope.userData.birthday = exec_time;
						$.gritter.add({
							title : '成功', 
							text : '修改成功', 
							time : 1000, 
							class_name : 'gritter-success gritter-center'
						});
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

			$scope.showTagModal = function (wx_user_id, tags) {
//                console.log(tags)
				// 设定选中的标签
				var tempTags = {};
				if (tags.length > 0) {
					for (var i in tags) {
						tempTags[tags[i]['id']] = true;
					}
				}
				var resolve =  {
					wx_user_id: function () {
						return wx_user_id
					},
					tempTags: function () {
						return tempTags
					},
                    userData: function(){
                        return $scope.userData;
                    }
				};

				Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
			}

			// 标签选择弹框控制器
			var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'wx_user_id', 'tempTags', 'tags','userData', function ($scope, $modalInstance, wx_user_id, tempTags, tags,userData) {
				$scope.wx_user_id = wx_user_id;
				$scope.tags = tags;
                $scope.userData = userData;
//                console.log($scope.userData)
				$scope.common = {tempTags:tempTags};

				// 将选中的标签添加入待发的标签数组
				$scope.pushTag = function (tagId, tagName) {
					var tagId = parseInt(tagId);
					var tag = { id: tagId, name: tagName }
//                    console.log($scope.userData.tags);
					// 点击时checkbox的选中状态为点击前的状态
					if (!$scope.common.tempTags[tagId]) {
						$scope.common.tempTags[tagId] = true;
                        $scope.userData.tags.push({'id':tagId,'tag_name':tagName});
					} else {
                        $scope.delete_id(tagId);
						delete($scope.common.tempTags[tagId]);
					}
				}

                $scope.delete_id = function(tagId){
                    for(var i in $scope.userData.tags){
                        if(tagId == $scope.userData.tags[i].id){
                            $scope.userData.tags.splice(i,1);
//                            delete $scope.userData.tags[i];
                        }
                    }
                }

				// 确认选择标签，并发布H5页面
				$scope.ok = function () {
					var tags = []; // 获取当前选中的标签
					if (!$.isEmptyObject($scope.common.tempTags)) {
						for (var i in $scope.common.tempTags) 
							if ($scope.common.tempTags[i] == true){
								tags.push(i);
                            }
					}
//                    console.log(tags);return;

					$http.post(
						_c.appPath + 'common/user/edit_user_tag', 
						{
							source   : 'wx',
							user_id  : $scope.wx_user_id,
							tags     : tags
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
		// 选择指定筛选的用户
		$scope.common.checkedUser = function(){
			$scope.common.checkedUser = !$scope.common.checkedUser;
		}



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
				WeixinUser.userIntoGroup({
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
				WeixinUser.select_user({
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
				arrange : 1, 
				current_page : 1, 
				items_per_page : 50, 
				status : 1
			}
			$scope.groupsList = WeixinUserGroup.getList(params);
		}
		$scope.getUserGroups();

		$scope.showGroupCreateModal = function () {
			$scope.group_date = {
				group_date_1:true
			};
			var modalInstance = $modal.open({
				templateUrl: 'groupCreateModal',
				controller: groupCreateModalInstance,
				resolve: {
					group: function () {
						return $scope.group;
					},
					common: function () {
						return $scope.common;
					},
					group_date:function(){
						return $scope.group_date;
					}
				}
			});
		}

		var groupCreateModalInstance = ['$scope', '$modalInstance', 'WeixinUserGroup', 'group', 'common','group_date', function ($scope, $modalInstance, WeixinUserGroup, group, common,group_date) {
			$scope.group_date = group_date;
			$scope.group = group;
			$scope.common = common;
			$scope.group.expires_in = new Date();
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

			$scope.datePe = function(num){
				if(typeof $scope.group.expires_in == 'undefined'){
					$scope.group = {};
				}
				$scope.group.expires_in = '';
				$scope.group_date.group_date_1 = num == 1 ? true : false;
			}
		}];

		// 获取筛选条件
		var getGroupFeature = function () {
			$scope.group.filter_param = '';
			$scope.group.feature = '';
			$scope.group.tag_name = '';
			$scope.group.send_introduce = '';

			/* 获取当前设定的条件 [用户特征文字描述] */
			for (var i in $scope.post) {
				switch(i){
					case 'province':
						if ($scope.post[i] < 1) {
							continue;
						};
						$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
						$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.post[i] + ' ';
						break;
					case 'city':
						if ($scope.post[i] < 1) {
							continue;
						};
						$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
						$scope.group.feature += $scope.filter_labels[i] + '' + $scope.post[i] + '; ';
						break;
					case 'sex':
						for (var j in $scope.filters[i]) {
							if ($scope.filters[i][j].key == $scope.post[i]) {
								$scope.group.filter_param += i + '=' + $scope.post[i] + '&';
								$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.filters[i][j].val + '; ';
								break;
							}
						}
						break;

					case 'tags[]':
						if ($.isEmptyObject($scope.selectedTags)) continue;
						for (var k in $scope.selectedTags) 
							$scope.group.tag_name += $scope.selectedTags[k].name + ' ';
						if ($scope.group.tag_name.trim() == '') continue;
						$scope.group.filter_param += i + '=' + $scope.group.tag_name + '&';
						$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.group.tag_name + '; ';
						break;
						
					case 'group_send[]':
						if ($scope.post[i].length < 1) {
							continue;
						};
						for (var k = 0; k < $scope.post['group_send[]'].length; k++) {
							var send_id = $scope.post['group_send[]'][k];
							for (var j = 0; j < $scope.groupSendList.list.length; j++) {
								if ($scope.groupSendList.list[j].id == send_id) {
									$scope.group.send_introduce += '(id:' + send_id + ';发送时间:' + $scope.groupSendList.list[j].exec_time + ')';
								};
							};

						};
						$scope.group.filter_param += i + '=' + $scope.group.send_introduce + '&';
						$scope.group.feature += $scope.filter_labels[i] + ':' + $scope.group.send_introduce + ';';
						break;
				}
			}
			// 移除结尾的&符号
			$scope.group.filter_param = $scope.group.filter_param.replace(/&$/ig, '');      

			/*if ($scope.group.filter_param == '') {
				alert('请设置筛选条件！');
			} else {
				$('#create_group').modal('show');
			}*/
		}

		// 创建组
		var createGroup = function ($modalInstance) {
			$scope.group.members_count = $scope.common.selectedCount;
			if(!$scope.group_date.group_date_1){
				if (typeof $scope.group.expires_in == 'object') {
                    $scope.group.expires_in = $scope.group.expires_in.getFullYear() + '-'
                        + ($scope.group.expires_in.getMonth() + 1) + '-'
                        + $scope.group.expires_in.getDate();
				}
			}else{
				 $scope.group.expires_in = "2037-08-08";
			}

			if ($scope.group.name == undefined || /^[\s]*$/ig.test($scope.group.name)) {
				$.gritter.add({
					title : '提醒', 
					text : '请填写组名称', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			} else if ( ! /^\d{4}-\d{1,2}-\d{1,2}/.test($scope.group.expires_in)) {
				$.gritter.add({
					title : '提醒', 
					text : '请按照格式填写有效期 - [2014-12-12] ！', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			} else if ($scope.group.description == undefined || /^[\s]*$/ig.test($scope.group.description)) {
				$.gritter.add({
					title : '提醒', 
					text : '请填写组描述！', 
					time : 2000, 
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			}

			var _createGroup = function () {
				WeixinUserGroup.create($scope.group, function (data) {
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
				// 全部
				$scope.group.ids = [];
				$scope.group.is_locked = 0;
				var params = {};
				if ($scope.post) {
					params = $scope.post;
				}
				params.page = $scope.common.usersList.data.page || 1;
				params.perpage = $scope.group.members_count || 20;

				// 用户组来的根据组搜索请求
				if ($scope.searchGroupId != 0) {
					params.group_id = $scope.searchGroupId;
				}
				WeixinUser.select_user(params, function (data) {
					angular.forEach(data.data.users, function (v, k) {
						$scope.group.ids.push(v.id);
					});
					_createGroup();
				});
			} else if($scope.common.checkedUser){
				// 选中的
				$scope.group.ids = $scope.common.selectedUsers;
				_createGroup();
			} else{
				// 空组
				_createGroup();
			}
		}

	}]);
});

