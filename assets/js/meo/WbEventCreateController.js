'use strict';

define(['me'], function (me) {
	me.controller('WbEventCreateController', ['$scope', '$sce', '$http', '$routeParams', 'H5page','Media', 'Event', 'Tag', '$modal', function ($scope, $sce, $http, $routeParams, H5page, Media, Event, Tag, $modal) {
		$scope.sendStatus = false;
		$scope.tmodel = '';
		$scope.con = {};
		$scope.con.type = 'text';
		$scope.con.is_text = true;
		// 获取用户组
		$scope.tags = {};
		$scope.target_group_id = parseInt($routeParams.group_id);
		// $scope.groups = {};
		$scope._errors = [];
		$scope.group_empty = '正在加载用户组信息...';
		$scope.tag_empty = '正在加载标签信息...';
		$scope.account_empty = '正在加载微博账号信息...';
		$scope.p = $scope.p || {info: {}, set: {}, tags: {}, groups: {}};
		$scope.p.info.type = 0;
		$scope.p.info.industry = 0;
		/* 初始化选定组信息 */
		if ($scope.target_group_id > 0) $scope.p.groups[$scope.target_group_id] = $scope.target_group_id;
		$scope.wbPrepare = {};

		$scope.selectedTags = {};
		$scope.pushTag = function (tag) {
			if ($scope.selectedTags[tag.id] == undefined)
				$scope.selectedTags[tag.id] = tag;
			else if (keyExsit(tag_id, $scope.p.tags))
				delete $scope.selectedTags[tag.id];
		}
		$scope.popTag = function (id) {
			delete $scope.selectedTags[id];
		}
		$scope.pushGroup = function (group_id, group_name) {
			if (keyExsit(group_id, $scope.p.groups))
				delete $scope.p.groups[group_id];
			else
				$scope.p.groups[group_id] = group_name;
		}

		var keyExsit = function (key, Obj) {
			if (typeof Obj != 'object')
				return false;

			return (typeof Obj[key] == 'undefined') ? false : true;
		}

		/* 获取系统标签信息 */
		$http.get(
				_c.appPath + 'common/tag'
			).success(function (res) {
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
			}).error(function (res) {
				$scope.tag_empty = '无法获取标签信息！';
			});

		$('.chosen-select').chosen();

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
		/* 时间选择控件 */

		$('#event-wizard').ace_wizard({
			// step : 3 // optional argument. wizard will jump to step "2" at first
		}).on('change',function (e, info) {
			if (info.direction == 'next') 	// 再点击下一步的时候
			{
				// $scope._errors = [];
				switch (info.step) {
					case 1 : // 校验 第一步：活动信息 是否填写完整，获取用户分组信息
						if (verify_basic()) {
							if ($scope.userGroups.groups == undefined)
								$scope.getGroups();
						} else {
							$.gritter.add({
								title: '错误',
								text: $scope._errors.join("<br>"),
								time: 2000,
								class_name: 'gritter-warning gritter-center'
							});
							e.preventDefault();
						}
						break;

					case 2: // 校验 第二步：目标用户组 是否填写完整，获取系统绑定的微博账号信息
						if (verify_group()) {
							if ($scope.accounts == undefined)
								get_accounts();
						} else {
							$.gritter.add({
								title: '错误',
								text: $scope._errors.join("<br>"),
								time: 2000,
								class_name: 'gritter-warning gritter-center'
							});
							e.preventDefault();
						}
						break;

					default :
						break;
				}
			}
		}).on('finished',function (e) {
			// do something when finish button is clicked
			// 校验 第三步：推送策略 是否填写完整
			if (verify_setting()) {
				/* 禁用按钮，提交表单，页面显示活动创建中！ */
				$scope.p.con = $scope.con;
                //console.log($scope.p);
				$http.post(
						_c.appPath + 'meo/event/create',
						$scope.p
					).success(function (res) {
						if (res.code == 200) {
							$.gritter.add({
								title: '创建成功！',
								text: '活动创建成功，正在跳转...',
								time: 2000,
								class_name: 'gritter-success gritter-center'
							});
                            setTimeout("window.location.href='main.html#/wb-event-manage'",2000);
							return true;
						} else {
							alert(res.message);
						}
					}).error(function () {
						alert('创建活动失败，请稍后尝试！');
						return false;
					})
			} else {
				return false;
			}
		}).on('stepclick', function (e) {
			// e.preventDefault(); // this will prevent clicking and selecting steps
		});

		// return 0000-00-00 格式日期
		function date_format(o) {
			if (typeof o != 'object' || o == null)
				return false;

			var y = o.getFullYear();
			var m = ((o.getMonth() + 1) < 10) ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = (o.getDate() < 10) ? '0' + o.getDate() : o.getDate();
			return y + '-' + m + '-' + d;
		}

		/* 校验活动信息是否填写正确 */
		function verify_basic() {
			$scope._errors = [];
			if (typeof $scope.p.info.name == 'undefined' || $.trim($scope.p.info.name) == '') {
				$scope._errors.push('请填入活动名称, 2~40个字符！');
			}

			if (typeof $scope.p_info_start == 'object')
				$scope.p.info.start = date_format($scope.p_info_start);
			if (typeof $scope.p_info_end == 'object')
				$scope.p.info.end = date_format($scope.p_info_end);
			if (typeof $scope.p_set_push_start == 'object')
				$scope.p.set.push_start = date_format($scope.p_set_push_start);

			if (!/^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.info.start))
				$scope._errors.push('请选择活动开始时间！');
			if (!/^[\d]{4}-[\d]{2}-[\d]{2}$/.test($scope.p.info.end))
				$scope._errors.push('请选择活动结束时间！');
//			if ($scope.p.set.content == undefined || $.trim($scope.p.set.content) == '')
//				$scope._errors.push('请填入活动微博内容！');

			/* 标签选择 */
			$scope.p.tags = {};
			for (var i in $scope.selectedTags)
				$scope.p.tags[parseInt(i)] = parseInt(i);

			if ($scope._errors.length > 0)
				return false;
			else
				return true;
		}

		/* 校验目标用户组是否选择正确 */
		function verify_group() {

			if ($scope.p.groups.length == 0)
				if (!confirm('您没有选择任何组，将不会对任何用户定向推送，确定么？')) {
					e.preventDefault();
					return false;
				}
			// else 
			// 	for (var i in $scope.p.groups) {
			// 		// if ( ! keyExsit(0))
			// 	};

			return $scope._errors.length > 0 ? false : true;
		}

		/* 发布微博部分 */
		/* 获取新浪微博表情 */
		$('.elms .emotions').sinaEmotion({ target: $('.weibo-text') });
		/* 上传图片 */
		$scope.imgModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'imgModal.html',
				controller: imgModalInstance,
				// size: 'sm',
				resolve: {
					wbPrepare: function () {
						return $scope.wbPrepare;
					},
					p: function () {
						return $scope.p;
					}
				}
			});
		}
		// 图片添加弹出框控制器
		var imgModalInstance = ['$scope', '$modalInstance', 'wbPrepare', 'p', function ($scope, $modalInstance, wbPrepare, p) {
			$scope.wbPrepare = wbPrepare;
			$scope.p = p;

			// 初始方式为图片上传
			$scope.wbPrepare.addType = 'uploadShow';
			$scope.imgUp = {
				uploadShow: true,
				urlShow: false
			};

			$scope.toggleType = function () {
				// 隐藏所有添加图片的方式
				for (var i in $scope.imgUp) {
					$scope.imgUp[i] = false;
				}
				$scope.imgUp[$scope.wbPrepare.addType] = true;
			}

			$scope.cancel = function () {
				$modalInstance.close();
			}

			// 确认
			$scope.ok = function () {
				if (!$scope.p.set.imgurl) {
					$.gritter.add({
						title: '错误',
						text: '没有添加图片',
						time: 2000,
						class_name: 'gritter-warning gritter-center'
					});
					return false;
				}

				$scope.wbPrepare.imageHtml = '<img src="' + $scope.p.set.imgurl + '">';
				$modalInstance.close();
			}
		}];
		/* 短链接 */
		$scope.surlModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'surlModal.html',
				controller: surlModalInstance,
				resolve: {
					wbPrepare: function () {
						return $scope.wbPrepare;
					},
					p: function () {
						return $scope.p;
					}
				}
			});
		}
		/* 短链接控制器 */
		// 短链接弹出框控制器
		var surlModalInstance = ['$scope', '$http', '$modalInstance', 'wbPrepare', 'p', function ($scope, $http, $modalInstance, wbPrepare, p) {
			$scope.wbPrepare = wbPrepare;
			$scope.p = p;
			$scope.p.set.content = $scope.p.set.content || '';
			$scope.cancel = function () {
				$modalInstance.close();
			}
			// 确认
			$scope.ok = function () {
				$scope.pending = true;
				$scope.wbPrepare.surlData = $http.post(
					_c.appPath + 'meo/weibo/get_shorturl',
					{'url': $scope.wbPrepare.originalUrl}
				).success(function (res) {
						if (res.code == 200 && res.data != null && typeof(res.data.me_err_code) == 'undefined') {
							// 给微博内容加上短链
							$scope.wbPrepare.surl = res.data.urls[0].url_short;
							$scope.p.set.content += $scope.wbPrepare.surl;
							$scope.wbPrepare.originalUrl = '';
							$modalInstance.close();
						} else {
							$.gritter.add({
								title: '错误',
								text: res.message || '出现了一个未知错误，请稍后再试',
								time: 2000,
								class_name: 'gritter-warning gritter-center'
							});
						}
						$scope.pending = false;
					}).error(function () {
						$.gritter.add({
							title: '错误',
							text: '无法转换地址，请稍后尝试！',
							time: 2000,
							class_name: 'gritter-warning gritter-center'
						});
					});
			}
		}];
		/* 点击显示H5活动页面 */
		$scope.h5pageModal = function () {
            $scope.h5show = !$scope.h5show;
		}
		/* 发布微博部分 */

		/* 校验推送策略是否正确填写 */
		function verify_setting() {
			return true;
		}

		/* 获取微博可用用户组信息 */
		$scope.userGroups = {current_page: 1, items_per_page: 12};
		$scope.getGroups = function () {
			var page = $scope.userGroups.current_page || 1;
			var perpage = $scope.userGroups.items_per_page || 12;
			$http.get(
					_c.appPath + 'meo/wb_group/select_groups?arrange=1&current_page=' + page + '&items_per_page=' + perpage + '&status=1'
				).success(function (res) {
					if (res.code == 200)
						$scope.userGroups = res.data;
					else
						$scope.group_empty = res.message;
				}).error(function (res) {
					$scope.group_empty = '无法获取用户组信息！';
				});
		}

		function get_accounts() {
			$http.get(
					_c.appPath + 'system/account/get_wb_accounts'
				).success(function (res) {
					if (res.code == 200) {
						if (res.data.length > 0) {
							$scope.accounts = {};
							for (var i in res.data)
								$scope.accounts[res.data[i]['id']] = res.data[i];
						} else {
							$scope.account_empty = '没有设定任何用户组！';
						}
					} else {
						$scope.account_empty = res.message;
					}
				}).error(function (res) {
					$scope.account_empty = '无法获取用户组信息！';
				});

		}

		// chosen插件在初始化时如果select为隐藏，则width会变成0，通过以下方式强行设置width
		// $('.chosen-select').chosen({allow_single_deselect:true});
		$('.chosen-container').each(function () {
			$(this).css('width', '210px');
			$(this).find('.chosen-choices').css({
				'width': '210px',
				'padding': '6px 4px'
			});
			$(this).find('a:first-child').css('width', '210px');
			$(this).find('.chosen-drop').css('width', '210px');
			$(this).find('.chosen-search input').css('width', '200px');
		});


		$scope.fiSelect = function () {
//			console.log($scope.p.set.push_mode)
			if ($scope.p.set.push_mode == 3) {
				$scope.sendStatus = true;
			} else {
				$scope.sendStatus = false;
			}
		}

		$scope.textBox = function () {
			$scope.con.tModel = '';
			$scope.con.type = 'text';
			$scope.con.is_text = true;

		}
		$scope.common = {}

		$scope.showBox = function () {
			var resolve = {
				common: function () {
					return $scope.common;
				},
				sendValue: function () {
//					return $scope.sendValue;
				},
				search: function () {
					return $scope.search;
				},
				con: function () {
					return $scope.con;
				}
			}
//			$scope.is_text = false;
			Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve,'articles',false);

		};

		var mediaModalInstanceCtrl = ['$scope', '$modalInstance', 'get_list', 'common', 'sendValue', 'mediaData', 'type', 'itemSelect', 'search','con', function ($scope, $modalInstance, get_list, common, sendValue, mediaData, type, itemSelect, search,con) {
			$scope.common = common;
			$scope.search = search;
			$scope.con = con;
			// 搜索
			$scope.media_search = function () {
				$scope.params = {};
				$scope.params.type = type;
//                $scope.params.page = pageNum;
				if (type == 'news') {
					$scope.params.tag = $scope.search.status;
				}
				$scope.params.title = $scope.search.title;
				$scope.mediaData = $scope.get_list($scope.params)
			}

			$scope.mediaData = mediaData;
			$scope.get_list = get_list;
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
			$scope.sendValue = sendValue;
			// 点击选择素材的方法
			$scope.itemSelect = itemSelect;
			// 把通用对象中的已选择素材存入规则对象中
			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 确认选择媒体
			$scope.ok = function () {
//				console.log($scope.common);
				if('undefined' == typeof $scope.common.selectedMediaId['articles']){
					$.gritter.add({
						text: '请选择一个图文',
						time: 2000,
						class_name: 'gritter-warning gritter-center'
					});
					return;
				}
				$scope.con.tModel = $scope.common.selectedMediaId['articles'][0];
				$scope.con.type = 'articles';
				$scope.con.is_text = false;
				$modalInstance.close();
			}
		}];

		/* 选择活动的H5页面 */
		$scope.selectPage = function (item) 
		{
			$scope.selectedPage = item;
            $scope.p.info.h5_id = $scope.selectedPage.id;
            //console.log($scope.p);
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

	}]);
});
