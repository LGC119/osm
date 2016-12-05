'use strict';

/* TODO: 处理过后的微博, 在页面上隐藏掉 */
define(['me'], function (me) {
	me.controller('WeiboOperationController', ['$scope', '$http', '$sce','$modal', '$routeParams', 'WeiboCommunication', 'SuspendingService', 'Staff', function($scope, $http, $sce,$modal, $routeParams, WCS, SS, Staff){
		$scope.empty = '载入中...';
		$scope.pcat = [];		// 顶级分类
		$scope.scat = {};		// 二级分类
		$scope.type = $routeParams.type;		// 类型{mentions, comments, keywords, messages}
		$scope.status_names = ['待分类', '待处理', '已忽略', '已处理'];
		$scope.common = {};
		$scope.common.verifiedType = WCS.verifiedType;

        Staff.onlineGetStaffList({},
                function(data){
                    if (data.code == 200) {
                        $scope.staffs = data.data;
                    } else {
                        $scope.empty = data.message;
                    }
                },
                function(){
                    $scope.empty = "网络不通，请稍后尝试！";
                });

		// 时间设置
		$scope.minDate = new Date();

		/* 时间选择控件 */
		$scope.today = function() {
			$scope.dt = new Date();
		};
		$scope.today();

		$scope.clear = function () {
			$scope.dt = null;
		};

		// Disable weekend selection
		$scope.disabled = function(date, mode) {
			return false;
		};

		$scope.toggleMin = function() {
			$scope.minDate = $scope.minDate ? null : new Date();
		};
		$scope.toggleMin();

		$scope.openDatepicker = function($event, target) {
			$event.preventDefault();
			$event.stopPropagation();

			$scope[target] = true;
		};

		$scope.dateOptions = {
			formatYear: 'yy',
			startingDay: 1
		};

		$scope.format = 'yyyy-MM-dd';
		/* 时间选择控件 */

		// 设置时间调整步进
		$scope.hstep = 1;
		$scope.mstep = 5;
		// 是否为12小时制
		$scope.ismeridian = false;
		// 分页信息
		$scope.pages = {};

		// 最大回复信息长度
		$scope.maxRepLen = $scope.type == 'messages' ? 300 : 140;

		// 获取feeds
		$scope.get_timeline = function (status) {
			var date_start = (typeof $scope.start_date == 'object') ? format_date($scope.start_date) : '';
			var date_end = (typeof $scope.end_date == 'object') ? format_date($scope.end_date) : '';
			if (typeof $scope.status == 'undefined')
				$scope.status = status || 0;	// 状态{untouched:0, categorized:1, ignored:4, replied:3, suspending:5}
			$scope.searchPending = true;
			$scope.empty = '载入中...';
			WCS.query.get_feeds({
				type: $scope.type,
				status: $scope.status,
				end: date_end || '',
				start: date_start || '',
				keyword: $scope.keyword || '',
				fkeyword: $scope.fkeyword || '',
				current_page: $scope.pages.current_page || 1,
				items_per_page: $scope.pages.items_per_page || 10
			}, function(res) {
				if (res.code == 200) {
					$scope.timeline = res.data.feeds;
					$scope.pages.current_page = res.data.page;
					$scope.pages.items_per_page = res.data.perpage;
					$scope.pages.total_number = res.data.total_number;
					if ($scope.timeline.length > 0) {
						for (var i in $scope.timeline) { // 对内容和来源链接转义
							var wb_info = $scope.timeline[i].wb_info;
							var keyword = $scope.timeline[i].keyword || '';
							wb_info.text = $sce.trustAsHtml(WCS.build_feeds(wb_info.text, keyword));
							wb_info.source = $sce.trustAsHtml(wb_info.source);
							if (wb_info.retweeted_status != undefined) { // 转发转义
								wb_info.retweeted_status.text = $sce.trustAsHtml(WCS.build_feeds(wb_info.retweeted_status.text));
								wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
							}
							if (wb_info.status != undefined) { // 评论的原微博转义
								wb_info.status.text = $sce.trustAsHtml(WCS.build_feeds(wb_info.status.text));
								wb_info.status.source = $sce.trustAsHtml(wb_info.status.source);
							}
							// 初始化每条信息的全局变量
							$scope[$scope.timeline[i].id] = { cats:[], reply:'', replyType:'c', wordsRemain:$scope.maxRepLen };
							if ($scope.status == 5) {
								$scope[$scope.timeline[i].id].rm_time = $scope.timeline[i]['rm_time'],
								$scope[$scope.timeline[i].id].rm_desc = $scope.timeline[i]['rm_desc']
							}
						}
					}
					$scope.searchPending = false;
				} else {
					$scope.empty = res.message || '获取交流信息失败！';
					$scope.timeline = {};
					$scope.searchPending = false;
				}
			}, function() {
				$scope.empty = '无法获取交流信息数据！';
				$scope.searchPending = false;
			});
		}

		/* 获取分类信息, 过滤不可用分类 <没有子分类的> */
		$http({
			url : _c.appPath + 'common/category'
		}).success(function(res){
			if (res.code == 200) {
				if (res.data.length > 0) {
					var topcat = {};
					var subcat = {};

					for (var i in res.data) {
						var cat = res.data[i];

						if (cat.parent_id == 0) {	// 顶级分类
							// cat.cat_name = cat.cat_name;
							topcat[cat.id] = cat;
							if (subcat[cat.id] == undefined) {
								subcat[cat.id] = [];	// 子分类数组是否存在，不存在创建
								subcat[cat.id].push({
									cat_name: cat.cat_name + '类' // 把顶级标签名放在数组的第一位，页面载入完成后默认selected
								});
							}
						} else {					// 二级分类
							subcat[cat.parent_id].push(cat);
						}
					}
					/* 过滤子分类为空的数据 */
					for (var i in subcat) {
						var scat = subcat[i];
						if (scat.length > 1) {	// 插入了一个顶级标签的对象，所以要大于1
							$scope.pcat.push(topcat[i]);
							for (var j in scat)
								scat[j]['pname'] = topcat[i]['cat_name'];

							$scope.scat[i] = scat;
						}
					}
				}
			} else {
				$.gritter.add({
					title : '获取分类信息出错！',
					text : res.message,
					time : 2000,
					class_name : 'gritter-warning gritter-center'
				});
			}
		}).error(function(){
			$.gritter.add({
				title : '获取分类信息失败！',
				text : '无法获取分类信息，将导致无法进行分类操作，请检查网络！',
				time : 2000,
				class_name : 'gritter-warning gritter-center'
			});
		});
		/* 获取分类信息end */

		// 判断回复长度（需要修改）
		var getLength = (function() {
			var byteLength = function(b) {
				if (typeof b == "undefined")
					return 0
				var a = b.match(/[^\x00-\x80]/g);
				return (b.length + (!a ? 0 : a.length))
			};

			return function(q, g) {
				g = g || {};
				g.max = g.max || 140;
				g.min = g.min || 41;
				g.surl = g.surl || 20;
				var p = $.trim(q).length;
				if (p > 0) {
					var j = g.min,
					s = g.max,
					b = g.surl,
					n = q;
					var r = q.match(/(http|https):\/\/[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([-A-Z0-9a-z\$\.\+\!\_\*\(\)\/\,\:;@&=\?~#%]*)*/gi) || [];
					var h = 0;
					for (var m = 0, p = r.length; m < p; m++) {
						var o = byteLength(r[m]);
						if (/^(http:\/\/t.cn)/.test(r[m])) {
							continue ;
						} else {
							if (/^(http:\/\/)+(weibo.com|weibo.cn)/.test(r[m])) {
								h += o <= j ? o: (o <= s ? b: (o - s + b))
							} else {
								h += o <= s ? b: (o - s + b)
							}
						}
						n = n.replace(r[m], "")
					}
					return Math.ceil((h + byteLength(n)) / 2)
				}
				else
					return 0
			}
		})();

		/* 回复表情 */ // 暂时回复不了
		// $('.elms .emotions').sinaEmotion({ // 表情按钮
		// 	target: $(this).parents('.reply').find('textarea'), // 目标文本框，可以是input或者是textarea
		// 	ngModel: $scope
		// });

		/* 更新输入字数统计 */
		$scope.updateWords = function (cmnId)
		{
			var length = getLength($scope[cmnId].reply);
			$scope[cmnId].wordsRemain = $scope.maxRepLen - length;
		}




		$scope.show_reply = function (target) {
			$(target).parent('.btns').siblings('.reply').stop(true, true).slideToggle('fast');
		};


		/* 分类一条 */
		$scope.categorize = function(cmnId) {
			var cmnId = parseInt(cmnId);

			/* 获取选中的分类 */
			var cats = $scope[cmnId]['cats'];
			if (cats.length < $scope.pcat.length) {
				$.gritter.add({
					title : '提示',
					text : '请选择完整分类信息！',
					time : 2000,
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			} else {
				for (var i in cats)
					if (cats[i] < 1) {
						$.gritter.add({
							title : '提示',
							text : '请正确选择分类信息！',
							time : 2000,
							class_name : 'gritter-warning gritter-center'
						});
						return false;
					}
			}

			$http({
				url : _c.appPath + 'meo/operation/categorize/' + cmnId + '/' + cats.join('_')
			}).success(function(res){
				if (res.code == 200) {
					$scope['showOperationBtn' + cmnId] = true;
					$.gritter.add({
						title : '成功',
						text : '分类成功！！',
						time : 2000,
						class_name : 'gritter-success gritter-center'
					});
					if ($scope.status == 5) SS.get_tasks();	// 在挂起页面操作，即时刷新
				} else {
					$.gritter.add({
						title : '失败',
						text : res.message,
						time : 2000,
						class_name : 'gritter-error gritter-center'
					});
				}
			}).error(function(){
				alert('操作失败，请稍后尝试！');
			});
		};

		/*回复*/
		$scope.reply = function (cmnId) {
			if (!$scope[cmnId].reply) {
				$.gritter.add({
					title : '提示',
					text : '请填写回复内容',
					time : 2000,
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			}

			if (!$scope[cmnId].replyType) {
				$.gritter.add({
					title : '提示',
					text : '请选择一种回复方式',
					time : 2000,
					class_name : 'gritter-warning gritter-center'
				});
				return false;
			}

			if ($scope[cmnId].wordsRemain < 0) {
				$.gritter.add({
					title : '提示',
					text : '回复内容超过了限制字数',
					time : 2000,
					class_name : 'gritter-warning gritter-center'
				});
				// alert('废话少点可以么！');
				return false;
			}

			/* TODO: 回复定时 */
			$http.post(
				_c.appPath + 'meo/operation/reply',
				{
					cmnId: cmnId,
					content: $scope[cmnId].reply,
					reply_type: $scope[cmnId].replyType,
					settime:''
				}
			).success(function(res) {
				if (res.code == 200) {
					$.gritter.add({
						title : '成功',
						text : '回复成功',
						time : 1000,
						class_name : 'gritter-success gritter-center'
					});
					$scope[cmnId].isCollapsed = true; // 隐藏回复成功的微博
					if ($scope.status == 5) SS.get_tasks();	// 在挂起页面操作，即时刷新
				} else {
					$.gritter.add({
						title : '错误',
						text : res.message || '回复失败',
						time : 1000,
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function(){
				$.gritter.add({
					title : '错误',
					text : '无法回复，请稍后尝试！',
					time : 1000,
					class_name : 'gritter-danger gritter-center'
				});
			});
		}

		/* 忽略一条 */
		$scope.ignore = function(cmnId) {
			var cmnId = parseInt(cmnId);

			$http({
				url : _c.appPath + 'meo/operation/ignore/' + cmnId
			}).success(function(res){
				if (res.code == 200) {
					$.gritter.add({
						title : '成功',
						text : '操作成功！',
						time : 2000,
						class_name : 'gritter-success gritter-center'
					});
					// 隐藏操作成功的微博
					$scope[cmnId].isCollapsed = true;
				} else {
					$.gritter.add({
						title : '错误',
						text : res.message,
						time : 2000,
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function(){
				$.gritter.add({
					title : '错误',
					text : '操作失败，请稍后尝试！',
					time : 2000,
					class_name : 'gritter-danger gritter-center'
				});
			});
		};

		/* 取消忽略一条 */
		$scope.unignore = function(e) {
			var cmnId = $(e).parents('.actions').attr('cmn-id');

			$http({
				url : _c.appPath + 'meo/operation/unignore/' + cmnId
			}).success(function(res) {
				if (res.code == 200) {
					$.gritter.add({
						title : '成功',
						text : '操作成功！',
						time : 2000,
						class_name : 'gritter-success gritter-center'
					});
					// 隐藏操作成功的微博
					$scope[cmnId].isCollapsed = true;
				} else {
					$.gritter.add({
						title : '错误',
						text : res.message,
						time : 2000,
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function() {
				$.gritter.add({
					title : '错误',
					text : '操作失败，请稍后尝试！',
					time : 2000,
					class_name : 'gritter-danger gritter-center'
				});
			});
		};

		/* @func show_suspend [显示挂起对话框] */
		$scope.show_suspend = function(item) {
			if (typeof item == 'object') { // 修改挂起记录
				$scope.set_time = item.rm_time;
				$scope.set_desc = item.rm_desc;
				$scope.suspending_item = item.sid;
				$('#suspendbox').modal("show");
			} else { // 创建一条挂起记录
				$scope.set_time = new Date();
				$scope.set_desc = '';
				$scope.suspending_item = item;
				$('#suspendbox').modal("show");
			}
		};

		var format_date = function (o, time)
		{
			if (typeof o != 'object' || o == null)
				return false;

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			if (time != undefined && time == true) { // 返回带小时分钟的时间字串
				var h = o.getHours() < 10 ? '0' + o.getHours() : o.getHours();
				var i = o.getMinutes() < 10 ? '0' + o.getMinutes() : o.getMinutes();
				return y + '-' + m + '-' + d + ' ' + h + ':' + i + ':00';
			}

			return y + '-' + m + '-' + d;
		}

		/* @func suspend [挂起] */
		$scope.suspend = function() {
			var id = $scope.suspending_item;
			var dt = $scope.set_time;
			var set_time = format_date(dt, true);
			$http.post(
				_c.appPath + 'meo/operation/suspend/' + id,
				{
					set_time : set_time,
					desc : $scope.set_desc || ''
				}
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ text : '挂起成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					$scope[id].isCollapsed = true;
					/* 在挂起任务中添加一条记录 */
					SS.get_tasks();
					return true;
				} else {
					$.gritter.add({ text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
					return false;
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			});
		};

		/* @func edit_suspend [挂起] */
		$scope.edit_suspend = function() {
			var sid = $scope.suspending_item;
			var dt = $scope.set_time;
			// console.log(dt);
			if (typeof dt == 'object') {
	            var set_time = format_date(dt, true);
			} else if (/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}$/.test(dt)) {
				var set_time = dt;
			} else {
				$.gritter.add({ text : '请设定时间！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			}
			$http.post(
				_c.appPath + 'meo/operation/change_suspend/',
				{
					id: sid,
					set_time : set_time,
					desc : $scope.set_desc || ''
				}
			).success(function (res) {
				if (res.code == 200) {
					$.gritter.add({ text : '修改成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					$scope.get_timeline();
					SS.get_tasks();
					return true;
				} else {
					$.gritter.add({ text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
					return false;
				}
			}).error(function () {
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			});
		};

		/* @func unsuspend [挂起] */
		$scope.unsuspend = function(id) {
			$http.post(
				_c.appPath + 'meo/operation/unsuspend/' + id
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ text : '取消挂起成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					SS.get_tasks();
					return true;
				} else {
					$.gritter.add({ text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
					return false;
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			});
		};

		/* 置顶一条 */
		$scope.pintotop = function(item) {
			var cmnId = item.id;
			$http({
				url : _c.appPath + 'meo/operation/pin/' + cmnId
			}).success(function(res){
				if (res.code==200) {
					$.gritter.add({ text : '置顶成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					item.is_top = 1;
				} else {
					$.gritter.add({ title : '置顶失败！', text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
			});
		};

		/* @func unpin [取消置顶] */
		$scope.unpin = function(item) {
			var cmnId = item.id
			$http({
				url : _c.appPath + 'meo/operation/unpin/' + cmnId
			}).success(function(res){
				if (res.code==200) {
					$.gritter.add({ text : '取消置顶成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					item.is_top = 0;
				} else {
					$.gritter.add({ title : '取消置顶失败！', text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
			});
		};

		/* 分配 */
        /* 随机分配 */
		$scope.assignRandom = function(item_id, staffs) {
            //随机取一个员工
            var staff = staffs[Math.floor(Math.random()*staffs.length)];
			$http({
				url : _c.appPath + 'meo/operation/assign/' + item_id + '/' + staff.id
			}).success(function(res){
				if (res.code==200) {
					$.gritter.add({ text : '分配给员工 ' + res.data + ' 成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					$scope[item_id].isCollapsed = true; // 隐藏随机分配的信息
				} else {
					$.gritter.add({ title : '分配失败！', text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
			});
		};

        /* 向某个CSR分配 */
		$scope.assign = function(item_id, staff_id, staff_name) {
			$http({
				url : _c.appPath + 'meo/operation/assign/' + item_id + '/' + staff_id
			}).success(function(res){
				if (res.code==200) {
					$.gritter.add({ text : '分配给员工 ' + staff_name + ' 成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					$scope[item_id].isCollapsed = true; // 隐藏指定的员工分配的信息
				} else {
					$.gritter.add({ title : '分配失败！', text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
			});
		};

		$scope.showUserInfoPop = function (target, item) {
			// 调整弹出窗位置
			item.popStyle = {
				left: target.naturalWidth / 2 - 10,
				top: target.naturalHeight - 10
			};
		};

		/* showCmnHistory 获取交流记录 */
		// $scope.cmnHistory = {};
		// $scope.showCmnHistory = function (user)
		// {
		// 	if (typeof user_weibo_id == 'undefined') return false;

		// 	$http(
		// 		_c.appPath + 'meo/wb_user/communications',
		// 		{
		// 			user_weibo_id: user_weibo_id,
		// 			current_page: $scope.cmnHistory.current_page || 1,
		// 			items_per_page: $scope.cmnHistory.items_per_page || 10
		// 		}
		// 	).success(function(res){
		// 		//
		// 	}).error(function(){
		// 		$scope.cmnHistoryEmpty = '获取交流记录失败！';
		// 	});
		// }








		// 智库弹窗
		$scope.showQuickReply = function (item) {
			$scope.common.cmnReply = $scope[item.id];
			var modalInstance = $modal.open({
				templateUrl: 'assets/html/common/quick-reply-modal.html',
				controller: quickReplyModalInstance,
				size: 'lg',
				resolve: {
					common: function () {
						return $scope.common;
					}
				}
			});
		}

		var quickReplyModalInstance = ['$scope', '$modalInstance', 'common', function ($scope, $modalInstance, common) {
			$scope.common = common;
			$scope.quickReplies = {};
			$scope.getQuickReplies = function () {
				var params = {
					keyword: $scope.common.qrKeyword,
					current_page: $scope.quickReplies.current_page || 1,
					items_per_page: $scope.quickReplies.items_per_page || 10
				};
				$http.get(
						_c.appPath + 'common/quick_reply/get_qrs?' + $.param(params)
					).success(function (res) {
						if (res.code == 200) {
							$scope.quickReplies = res.data;
						} else if (res.code == 204) {
							$scope.qrs_empty = '暂无记录';
							$scope.quickReplies = {
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
							text : '无法获取智库',
							time : 2000,
							class_name : 'gritter-warning gritter-center'
						});
					});
			}
			$scope.getQuickReplies();

			$scope.quote = function (answer) {
				$scope.common.cmnReply.reply = answer;
				$modalInstance.close();
			}

			$scope.cancel = function () {
				$modalInstance.close();
			};
		}];


	}]);
});
