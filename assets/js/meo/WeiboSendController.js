'use strict';

define(['me'], function (me) {
	me.controller('WeiboSendController', ['$scope', '$http', '$sce', 'Weibo', 'Tag', '$modal', function ($scope, $http, $sce, Weibo, Tag, $modal) {
		/*
		 * 发微博页面相关
		 */

		$scope.wordsRemain = 140;

		// 要发送的微博对象
		$scope.wbPrepare = {
			tags: []
		};
		$scope.weibo = {
			sid:'',
			text: '',
			tags: {}
		};
		$scope.common = {
			tempTags: {}
		};
		$scope.filter = {tags: []};
		$scope.common.verifiedIcons = {
			'approve_co': 'assets/img/approve_co.png',
			'approve': 'assets/img/approve.png',
			'daren': 'assets/img/daren.png'
		}
		$scope.common.verifiedType = {
			'220': $scope.common.verifiedIcons.daren,
			'0': $scope.common.verifiedIcons.approve,
			'2': $scope.common.verifiedIcons.approve_co,
			'3': $scope.common.verifiedIcons.approve_co,
			'4': $scope.common.verifiedIcons.approve_co,
			'5': $scope.common.verifiedIcons.approve_co,
			'6': $scope.common.verifiedIcons.approve_co,
			'7': $scope.common.verifiedIcons.approve_co,
		}

		/*
		 * 定时时间相关
		 */
		// 将时间置为当前时间
		$scope.today = function() {
			$scope.wbPrepare.dt = new Date();
		};
		$scope.today();
		$scope.getTenMinutes = function () 
		{
			$scope.today();
			$scope.wbPrepare.dt = new Date($scope.wbPrepare.dt.getTime() + 600 * 1000);
		}

		$scope.minDate = new Date();

		/* 时间选择控件 */
		$scope.clear = function () {
			$scope.dt = null;
		};

		// Disable weekend selection
		$scope.disabled = function(date, mode) {
			return false;
		};

		$scope.openDatepicker = function($event, target) {
			$event.preventDefault();
			$event.stopPropagation();

			$scope[target] = true;
		};

		$scope.dateOptions = {
			formatYear: 'yy',
			startingDay: 1
		};
		/* 时间选择控件 */

		// 设置时间调整步进
		$scope.hstep = 1;
		$scope.mstep = 5;
		// 是否为12小时制
		$scope.ismeridian = false;

		// 清除当前设置的时间
		$scope.clear = function () {
			$scope.wbPrepare.dt = null;
		};

		/*
		 * 图片
		 */
		$scope.imgModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'imgModal.html',
				controller: imgModalInstance,
				// size: 'sm',
				resolve: {
					wbPrepare: function () {
						return $scope.wbPrepare;
					},
					weibo: function () {
						return $scope.weibo;
					}
				}
			});
		}

		// 图片添加弹出框控制器
		var imgModalInstance = ['$scope', '$modalInstance', 'Weibo', 'wbPrepare', 'weibo', function ($scope, $modalInstance, Weibo, wbPrepare, weibo) {
			$scope.wbPrepare = wbPrepare;
			$scope.weibo = weibo;

			// 初始方式为图片上传
			$scope.wbPrepare.addType = 'uploadShow';
			$scope.imgUp = {
				uploadShow: true,
				urlShow: false
			};
			
			$scope.toggleType = function () { // 隐藏所有添加图片的方式
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
				if (!$scope.weibo.image) {
					$.gritter.add({ 
						title : '错误', 
						text : '没有添加图片', 
						time : 2000, 
						class_name : 'gritter-warning gritter-center' 
					});
					return false;
				}

				$scope.wbPrepare.imageHtml = '<img src="' + $scope.weibo.image + '">';
				$modalInstance.close();
				if ($scope.weibo.text == '') {
					$scope.weibo.text = '分享图片';
				};
			}
		}];

		/*
		 * 短链接
		 */
		$scope.surlModal = function () {
			var modalInstance = $modal.open({
				templateUrl: 'surlModal.html',
				controller: surlModalInstance,
				resolve: {
					wbPrepare: function () {
						return $scope.wbPrepare;
					},
					weibo: function () {
						return $scope.weibo;
					}
				}
			});
		}

		// 短链接弹出框控制器
		var surlModalInstance = ['$scope', '$modalInstance', 'Weibo', 'wbPrepare', 'weibo', function ($scope, $modalInstance, Weibo, wbPrepare, weibo) {
			$scope.wbPrepare = wbPrepare;
			$scope.weibo = weibo;
			// console.log(wbPrepare);
			$scope.cancel = function () {
				$modalInstance.close();
			}
			// 确认
			$scope.ok = function () {
				$scope.pending = true;
				// console.log($scope.wbPrepare.originalUrl);
				$scope.wbPrepare.surlData = Weibo.getShortUrl({
					'url': $scope.wbPrepare.originalUrl
				}, function (data) {
					if (data.code == 200 && data.data != null && typeof(data.data.me_err_code) == 'undefined') {
						// 给微博内容加上短链
						$scope.wbPrepare.surl = data.data.urls[0].url_short;
						$scope.weibo.text += $scope.wbPrepare.surl;
						$scope.wbPrepare.originalUrl = '';
						
						$modalInstance.close();
					} else {
						$.gritter.add({ 
							title : '错误', 
							text : data.message || '出现了一个未知错误，请稍后再试', 
							time : 2000, 
							class_name : 'gritter-warning gritter-center' 
						});
					}
					$scope.pending = false;
				});
			}
		}];

		/*
		 * 转发指定微博相关
		 */
		$scope.getRepostData = function () {
			$scope.showSpinner = true;
			$scope.pending = true;
			$scope.repostData = Weibo.getRepostDataByLink({
				'weibo_url': $scope.wbPrepare.repostLink
			}, function (data) {
				$scope.showSpinner = false;
				$scope.pending = false;
				if (data.code == 200) {
					$scope.showRepostData = true;
					$scope.weibo.sid = data.data.idstr;
					data.data.source = $sce.trustAsHtml('来自 ' + data.data.source);
					if (data.data.retweeted_status) {
						$scope.weibo.text += '//@' + data.data.user.screen_name + ': ' + data.data.text;
					};
					if ($scope.weibo.text == '') {
						$scope.weibo.text = '转发微博';
					};
				} else {
					$.gritter.add({ 
						title : '获取微博信息失败！', 
						text : data.message || '出现了一个未知错误，请稍后再试', 
						time : 2000, 
						class_name : 'gritter-warning gritter-center' 
					});
				}
			});
		}

		$scope.cancelRepost = function () {
			$scope.wbPrepare.repostLink = '';
			$scope.weibo.sid = '';
			$scope.showRepostData = false;
		}

		/*
		 * 表情
		 */
		//给表情加事件，暂时用jquery选择
		$('.elms .emotions').sinaEmotion({    //表情按钮
			target: $('.weibo-text'),  //目标文本框，可以是input或者是textarea
			ngModel: $scope
		});

		/*
		 * 发微博打标签相关
		 */
		$scope.showTagModal = function () {
			var resolve =  {
				weibo: function () {
					return $scope.weibo;
				},
				wbPrepare: function () {
					return $scope.wbPrepare; 
				},
				common: function () {
					return $scope.common
				}
			};
			Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
		}

		//已发布微博添加标签
		$scope.add_tags = function(wb_id,weibo_id){
			$scope.showTagModal();
			$scope.weibo.tags_wb_id = wb_id;
			$scope.weibo.tags_weibo_id = weibo_id;
			$scope.weibo.getUserTimeline = $scope.getUserTimeline;
		}

		// 标签选择弹框控制器
		var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'weibo', 'wbPrepare', 'tags', 'common',  function ($scope, $modalInstance, weibo, wbPrepare, tags, common) {
			$scope.weibo = weibo;
			$scope.wbPrepare = wbPrepare;
			$scope.common = common
			$scope.tags = tags;

			// 将选中的标签添加入待发的标签数组
			$scope.pushTag = function (tagId, tagName) {
				var tag = {
					id: tagId,
					name: tagName
				}
				var tagId = parseInt(tagId);
				// 点击时checkbox的选中状态为点击前的状态
				if (!$scope.common.tempTags[tagId]) {
					$scope.weibo.tags[tagId] = tagId;
					_c.arrayAddItem($scope.wbPrepare.tags, tag);
				} else {
					delete $scope.weibo.tags[tagId];
					_c.arrayRemoveItem($scope.wbPrepare.tags, tag);
				}
			}

			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 确认选择标签，并发布H5页面
			$scope.ok = function () {
				if ($scope.weibo.tags_weibo_id) {
					$http.post(
						_c.appPath+'meo/weibo/bind_sent_tags',
						{
							wb_id     : $scope.weibo.tags_wb_id,
							weibo_id     : $scope.weibo.tags_weibo_id,
							tags     : $scope.weibo.tags
						}
					).success(function(data){
							if(data.code == 200){
								$.gritter.add({
									title: '添加标签成功!',
									time:'500',
									class_name:'gritter-success gritter-center'
								});
								$scope.weibo.getUserTimeline();
							}else{
								if (data.code == 400) {
									$.gritter.add({
										title: '标签已存在，请重新添加!',
										time:'1000',
										class_name:'gritter-error gritter-center'
									});
								}else{
									$.gritter.add({
										title: '添加失败!',
										time:'1000',
										class_name:'gritter-error gritter-center'
									});
								}
							}
						}).error(function(){

						});
				};
				$modalInstance.close();
			}
		}];

		// 点击标签的X按钮，删除已选择的标签
		$scope.removeSelectedTag = function (tag) {
			_c.arrayRemoveItem($scope.wbPrepare.tags, tag);
			delete($scope.common.tempTags[tag.id]);
			delete($scope.weibo.tags[tag.id]);
		}

		/*
		 * 微博发布
		 */
		$scope.send = function () {
			if ($scope.weibo.text == '') {
				$.gritter.add({
					title: '请填写文字描述!',
					time:'500',
					class_name:'gritter-warning gritter-center'
				});
				return;
			};
			if ($scope.wbPrepare.showDtPiker) {
				var dt = $scope.wbPrepare.dt;
				$scope.weibo.send_at = format_date(dt, true); 
			}

			$scope.pending = true;
			if ($scope.weibo.image && ! $scope.showRepostData) {
				$scope.weibo.pic_path = $scope.weibo.image;
				delete($scope.weibo.image);
			}

			Weibo.create($scope.weibo, function (data) {

				if (data.code == 200) {
					$scope.weibo.text = '';
					$scope.weibo.sid = '';
					$scope.showRepostData = false;
					$scope.wbPrepare.repostLink = '';
					$scope.wbPrepare.tags = [];
					$scope.common.tempTags = {};
					$scope.weibo.tags = {};
					var title = '成功';
					var msg = '发送成功';
					var className = 'gritter-success gritter-center';
				} else {
					var title = '失败';
					var msg = '发送失败';
					var className = 'gritter-warning gritter-center';
				}

				$.gritter.add({ 
					title : title, 
					text : data.message || msg, 
					time : 2000, 
					class_name : className 
				});
				$scope.pending = false;
			});
		}

		/* 获取长度 */
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

		/* 更新输入字数统计 */
		$scope.updateWords = function (cmnId) 
		{
			var length = getLength($scope.weibo.text);
			$scope.wordsRemain = 140 - length;
			$scope.pending = ($scope.wordsRemain < 0);
		}

		/*
		 * 微博首页相关
		 */
		$scope.timeline = {};
		// 分页相关参数
		$scope.timeline.page = {
			maxSize: 5,
			itemsPerPage: 20,
			totalCount: 20
		}
		// 获取friendsTimeline
		$scope.friendsTimeline = function () {
			Weibo.friendsTimeline({
				current_page: $scope.timeline.page.currentPage || 1,
				items_per_page: $scope.timeline.page.itemsPerPage
			}, function (data) {
				if (data.code == 200) {
					for (var i in data.data.statuses) {
						var wb_info = data.data.statuses[i];
						wb_info.source = $sce.trustAsHtml(wb_info.source);
						wb_info.text = $sce.trustAsHtml(wb_info.text);
						if (wb_info.retweeted_status != undefined) {
							wb_info.retweeted_status.text = $sce.trustAsHtml(wb_info.retweeted_status.text);
							wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
						}
						if (wb_info.status != undefined) {
							wb_info.status.text = $sce.trustAsHtml(wb_info.status.text);
							wb_info.status.source = $sce.trustAsHtml(wb_info.status.source);
						}
						data.data.statuses[i] = {wb_info:wb_info};
					}
					$scope.timeline.list = data;
					$scope.timeline.page = {
						currentPage : parseInt(data.current_page),
						totalCount : parseInt(data.total_number),
						itemsPerPage : parseInt(data.items_per_page)
					};
				} else {
					$.gritter.add({ 
						title : '错误', 
						text : data.message || '获取数据失败', 
						time : 2000, 
						class_name : 'gritter-warning gritter-center'
					});
				}
			});
		}

		/*
		 * 已发布微博相关
		 */
		$scope.userTimeline = {
			data: {
				current_page: 1,
				items_per_page: 20
			}
		};
		// 获取已发布微博
		$scope.userTimelineEmpty = '载入中...';
		$scope.getUserTimeline = function () {
			Weibo.userTimeline({
				current_page: $scope.userTimeline.data.current_page || 1,
				items_per_page: 20
			}, function (data) {
				if (data.code == 200) {
					for (var i in data.data.statuses) {
						var wb_info = data.data.statuses[i];
						var cate_names = '';
						wb_info.source = $sce.trustAsHtml(wb_info.source);
						wb_info.text = $sce.trustAsHtml(wb_info.text);
						if (wb_info.retweeted_status != undefined) {
							wb_info.retweeted_status.text = $sce.trustAsHtml(wb_info.retweeted_status.text);
							wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
						}
						if (wb_info.status != undefined) {
							wb_info.status.text = $sce.trustAsHtml(wb_info.status.text);
							wb_info.status.source = $sce.trustAsHtml(wb_info.status.source);
						}
						if (wb_info.tags != undefined) 
							cate_names = wb_info.tags;
						data.data.statuses[i] = {wb_info:wb_info, cate_names:cate_names};
					}
					$scope.userTimeline = data;
				} else {
					$scope.userTimelineEmpty = data.message || '获取数据失败';
				}
			}, function () {
				$scope.userTimelineEmpty = '无法获取数据，请检查网络，稍后尝试！';
			});
		}

		//删除弹窗
		$scope.deleteTimeline_wb_id = '';
		$scope.deleteTimeline_idstr = '';
		$scope.deleteTimeline = function(wb_id,idstr){
			$('#deleteTimeline').modal('show');	
			if (wb_id) {
				$scope.deleteTimeline_wb_id = wb_id;
			};
			if (idstr) {
				$scope.deleteTimeline_idstr = idstr;
			};
		}
		//删除确认
		$scope.deleteCfm = function(){
			if ($scope.deleteTimeline_wb_id || $scope.deleteTimeline_idstr) {
				$http.post(
					_c.appPath+'meo/weibo/delete_user_timeline', 
					{
						wb_id : $scope.deleteTimeline_wb_id || '',
						idstr : $scope.deleteTimeline_idstr || ''
					}
				).success(function(res){
					if(res.code == '200'){
							$.gritter.add({
								title: '删除成功!',
								time:'1000',
								class_name:'gritter-success gritter-center'
							});
							$("#deleteTimeline").modal('hide');
							$scope.getUserTimeline();
						}else{
							$.gritter.add({
								title: '删除失败!',
								time:'2000',
								class_name:'gritter-error gritter-center'
							});
							$("#deleteTimeline").modal('hide');
						}
				})
			}else{
				$.gritter.add({
					title: '数据不存在!',
					time:'500',
					class_name:'gritter-warning gritter-center'
				});
				return;
			}
		}
		//判断是否为空object
		$scope.isEmpty = function(obj){
			for(var n in obj){return false} 
			return true;
		}

		//筛选
		$scope.get_timeline_filter = function() {

			var post = {
				start : format_date($scope.start_date),
				end : format_date($scope.end_date),
				keyword : $scope.keyword || ''
			}
			// console.log(post, $scope.wbPrepare.tags);

			return ;
			Weibo.filterTimeline({
				current_page: $scope.userTimeline.data.current_page || 1,
				items_per_page: 20,
				tags: $scope.weibo.tags
			}, function (data) {
				if (data.code == 200) {
					for (var i in data.data.statuses) {
						var wb_info = data.data.statuses[i];
						var cate_names = '';
						wb_info.source = $sce.trustAsHtml(wb_info.source);
						wb_info.text = $sce.trustAsHtml(wb_info.text);
						if (wb_info.retweeted_status != undefined) {
							wb_info.retweeted_status.text = $sce.trustAsHtml(wb_info.retweeted_status.text);
							wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
						}
						if (wb_info.status != undefined) {
							wb_info.status.text = $sce.trustAsHtml(wb_info.status.text);
							wb_info.status.source = $sce.trustAsHtml(wb_info.status.source);
						}
						if (wb_info.tags != undefined) 
							cate_names = wb_info.tags;
						data.data.statuses[i] = {wb_info:wb_info, cate_names:cate_names};
					}
					$scope.userTimeline = data;
				} else {
					$scope.userTimeline.code = 204;
				}
			});
		}

		/* 格式化时间字符串 */
		var format_date = function (o, time) 
		{
			if (typeof o != 'object' || o == null)
				return false;
			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			if (time != undefined) {
				var h = o.getHours() < 10 ? '0' + o.getHours() : o.getHours();
				var i = o.getMinutes() < 10 ? '0' + o.getMinutes() : o.getMinutes();
				var s = o.getSeconds() < 10 ? '0' + o.getSeconds() : o.getSeconds(); 
				return y + '-' + m + '-' + d + ' ' + h + ':' + i + ':' + s;
			}

			return y + '-' + m + '-' + d;
		}

	}]);
});