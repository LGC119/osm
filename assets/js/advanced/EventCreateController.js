'use strict';

define(['me'], function (me) {
	me.controller('AdvEventCreateController', ['$scope', '$sce', '$http', 'Event', 'Tag', 'H5page', 'Media', '$modal', function ($scope, $sce, $http, Event, Tag, H5page, Media, $modal) {
		// 获取用户组
		$scope.tags = {};
		$scope._errors = [];
		$scope.groupEmpty = '正在加载用户组信息...';
		$scope.groupsList = {};
		$scope.selectedGroupId = 0;
		$scope.tag_empty = '正在加载标签信息...';
		// $scope.account_empty = '正在加载微博账号信息...';
		$scope.groupUserInfoEmpty = '请点击左侧链接查看组成员信息详情！';
		$scope.pagesEmpty = '请点击搜索查看H5页面列表！';
		$scope.pageTitle = '页面预览';
		$scope.p = {info:{},set:{},tags:{}};

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

		$scope.pushTag = function (target, tag_id, tag_name) {
			if ($(target).attr("checked") && ! keyExsit(tag_id, $scope.p.tags)) 
				$scope.p.tags[tag_id] = tag_name;
			else if (keyExsit(tag_id, $scope.p.tags)) 
				delete $scope.p.tags[tag_id];
		}
		$scope.popTag = function (id) { delete $scope.p.tags[id]; }

		$scope.selectGroup = function (group) 
		{
			if ($scope.selectedGroupId == group.id)
				$scope.selectedGroupId = 0;
			else 
				$scope.selectedGroupId = group.id;
		}

		/* 选择活动的H5页面 */
		$scope.selectPage = function (item) 
		{
			$scope.selectedPage = item;
			if (item.template != 'custom')
				$scope.selectedPage.content = $sce.trustAsHtml(item.html_code[2]);
			/* 载入页面预览 */
			$scope.pageTitle = item.title.trim();
		}

		/* 获取系统的H5页面 */
		$scope.pagesList = {};
		$scope.getH5Pages = function () 
		{
			var keyword = $scope.h5_title;
			$scope.pagesEmpty = '正在载入...';
			H5page.pagesList({
				keyword: keyword,
				current_page: $scope.pagesList.current_page || 1,
				items_per_page: $scope.pagesList.items_per_page || 6
			}, function (res) {
				$scope.pagesList = {};
				if (res.code == 200)
					$scope.pagesList = res.data;
				else 
					$scope.pagesEmpty = res.message || '没有获取到H5页面列表！';
			});
		}

		var keyExsit = function (key, Obj) 
		{
			if (typeof Obj != 'object') 
				return false;

			for (var i in Obj)
				if (i == key)
					return true;

			return false;
		}

		/* 获取系统标签信息 */
		$http.get(
			_c.appPath + 'common/tag'
		).success(function(res){
			if (res.code == 200) {
				if (res.data.length > 0) {
					for (var i in res.data) {
						$scope.tags[res.data[i]['id']] = res.data[i];
					}
				} else {
					$scope.tag_empty = '没有设定系统标签！';
				}
			} else {
				$scope.tag_empty = res.message;
			}
		}).error(function(res){
			$scope.tag_empty = '无法获取标签信息！';
		});

		/** 
		 * 创建活动的
		 **/

		$('#event-wizard').ace_wizard({
			// step : 3 // optional argument. wizard will jump to step "2" at first
		}).on('change' , function(e, info) {
			if (info.direction == 'next') 	// 再点击下一步的时候
			{
				switch (info.step) {
					case 1:
						// 校验 第一步：目标用户组 是否填写完整，获取系统绑定的微博账号信息
						if (verify_group()) {
							if (typeof $scope.groupsList == 'undefined')
								$scope.getGroups();
						} else {
							e.preventDefault();
						}
						break;

					default :
						break;
				}
			}
		}).on('finished', function(e) {
			// 校验 第三步：推送策略 是否填写完整
			if ( ! verify_setting()) 
			{
				$.gritter.add({
					title: '错误',
					text: $scope._errors.join("<br>"),
					time: 2000,
					class_name: 'gritter-warning gritter-center'
				});
				return false;
			}
			/* 禁用按钮，提交表单，页面显示活动创建中！ */
			$scope.createPending = true;
			$http.post(
				_c.appPath + 'mei/event/create',
				$scope.p
			).success(function (res) {
				if (res.code == 200) {
					$.gritter.add({
						title: '创建成功！',
						text: '活动创建成功，即将跳转到活动管理页面...',
						time: 2000,
						class_name: 'gritter-success gritter-center'
					});
					setTimeout("window.location.href='main.html#/adv-event-manage'",2000);
				} else {
					$.gritter.add({
						title: '失败',
						text: res.message || '活动创建失败，请稍后尝试！',
						time: 2000,
						class_name: 'gritter-warning gritter-center'
					});
					$scope.createPending = false;
				}
			}).error(function(){
				$.gritter.add({
					title: '失败',
					text: '无法创建活动，请稍后尝试！',
					time: 2000,
					class_name: 'gritter-warning gritter-center'
				});
				$scope.createPending = false;
			});
		}).on('stepclick', function(e) {
			// e.preventDefault(); // this will prevent clicking and selecting steps
		});

		/* 校验活动信息是否填写正确 */
		var verify_setting = function () 
		{
			$scope._errors = [];
			if ($scope.p.info.name == undefined || /^[\s]*$/.test($scope.p.info.name)) 
				$scope._errors.push('请填入活动名称！');

			$scope.p.info.start = date_format($scope.p_info_start);
			$scope.p.info.end = date_format($scope.p_info_end);
			$scope.p.set.push_start = date_format($scope.p_set_push_start);

			if (!/^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.info.start)) 
				$scope._errors.push('请填入活动开始时间，格式[2014-05-01]！');

			if (!/^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.info.end)) 
				$scope._errors.push('请填入活动结束时间，格式[2014-05-01]！');

			if (!/^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.set.push_start)) 
				$scope._errors.push('请填入活动推送时间，格式[2014-05-01]！');

			/* 微博推送内容的获取 */
			if ($scope.common.wbType != 'text' && 
				$scope.common.wbType != 'news' && 
				$scope.common.wbType != 'articles')
				$scope.common.wbType = 'text';
			/* 微信推送内容的获取 */
			if ($scope.common.wxType != 'text' && 
				$scope.common.wxType != 'news' && 
				$scope.common.wxType != 'articles')
				$scope.common.wxType = 'text';
			$scope.p.set.wbContentType = $scope.common.wbType;
			$scope.p.set.wxContentType = $scope.common.wxType;

			if ( ! $scope.selectedGroupId > 0)
				$scope._errors.push('请选择一个有效的用户组！');
			else 
				$scope.p.group_id = $scope.selectedGroupId;

			if ('undefined' == typeof $scope.selectedPage || $scope.selectedPage == null)
				$scope._errors.push('请选择一个有效的H5页面！');
			else 
				$scope.p.info.page_id = $scope.selectedPage.id;

			if ($scope._errors.length > 0)
				return false;
			else 
				return true;
		}

		/* 校验目标用户组是否选择正确 */
		function verify_group () 
		{
			if (parseInt($scope.selectedGroupId) > 0) return true;

			$.gritter.add({ 
				title : '错误', 
				text : '您没有选择任何组，不能创建活动！', 
				time : 1000, 
				class_name : 'gritter-warning gritter-center' 
			});

			return false;
		}

		/* 校验推送策略是否正确填写 */
		function verify_setting () 
		{
			return true;
		}

		/* 获取系统设置的用户组 */
		$scope.getGroups = function () 
		{
			var params = {
				page : $scope.groupsList.page || 1, 
				perpage : $scope.groupsList.perpage || 12
			};
			/* 获取组列表 */
			$http.post(
				_c.appPath + 'mei/group/get_list', 
				params
			).success(function(res){
				if (res.code == 200) 
					$scope.groupsList = res.data;
				else 
					$scope.groupEmpty = res.message || '获取用户组信息失败！';
			}).error(function(res){
				$scope.groupEmpty = '无法获取用户组信息！';
			});
		}

		/* 获取组成员信息 */
		$scope.getGroupUserInfo = function () 
		{
			if ($scope.selectedGroupId < 1) 
			{
				$scope.groupUserInfoEmpty = '请先选定用户组！';
				return false;
			}

			/* 获取组用户关联的账号信息 */
			$scope.groupUserInfoPending = true;
			$http.post(
				_c.appPath + 'mei/group/get_group_user_info', 
				{id:$scope.selectedGroupId}
			).success(function(res){
				if (res.code == 200) 
					$scope.groupUserInfo = res.data;
				else 
					$scope.groupUserInfoEmpty = res.message || '没有获取到组用户信息！';
				$scope.groupUserInfoPending = false;
			}).error(function(){
				$scope.groupUserInfoEmpty = '无法获取到组用户信息！';
				$scope.groupUserInfoPending = false;
			});
		}

		/* 打开图文筛选面板 */
		$scope.common = {wbType:'text', wxType:'text'};
		$scope.showMediaBox = function (type, source) {
			if (type != 'news' && type != 'articles') type = 'articles';
			if (source != 'wb' && source != 'wx') source = 'wb';
			var resolve = {
				common: function () {
					return $scope.common;
				}, 
				source: function () {
					return source;
				}
			}
			Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve, type, false);
		};

		var mediaModalInstanceCtrl = ['$scope', '$modalInstance', 'common', 'mediaData', 'itemSelect', 'get_list', 'type', 'source', function ($scope, $modalInstance, common, mediaData, itemSelect, get_list, type, source) {

			$scope.common = common;
			$scope.mediaData = mediaData;
			$scope.itemSelect = itemSelect;
			$scope.get_list = get_list;
			$scope.type = type;
			// 搜索
			$scope.media_search = function () {
				$scope.params = {};
				$scope.params.type = type;
				if (type == 'news') {
					$scope.params.tag = $scope.search.status;
				}
				$scope.params.title = $scope.search.title;
				$scope.mediaData = $scope.get_list($scope.params)
			}

			$scope.get_media_list = function (pageNum) {
				$scope.params = {};
				$scope.params.type = type;
				$scope.params.page = pageNum;
				if (type == 'news') {
					$scope.params.tag = $scope.search.status;
				}
				$scope.params.title = $scope.search.title;
				$scope.mediaData = $scope.get_list($scope.params)
			}
			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 确认选择媒体
			$scope.ok = function () {
				if('undefined' == typeof $scope.common.selectedMediaId['articles'] && 'undefined' == typeof $scope.common.selectedMediaId['news']){
					$.gritter.add({
						text: '请选择一个图文',
						time: 2000,
						class_name: 'gritter-warning gritter-center'
					});
					return;
				}
				/* 获取多图文或单图文的ID和标题 */
				$scope.common[source + 'Type'] = type;
				$scope.common.Type = $scope.type;
				$modalInstance.close();
			}
		}];

	}]);
});
