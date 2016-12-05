'use strict';

define(['me'], function (me) {
	me.controller('UserTimelineController', ['$scope', '$http', '$sce', 'Weibo', 'Tag', 'WeiboCommunication', '$modal', function ($scope, $http, $sce, Weibo, Tag, WCS, $modal) {

		$scope.filter = {tags: []};
		$scope.common = {};
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
			$scope.dt = new Date();
		};
		$scope.today();

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

		// 清除当前设置的时间
		$scope.clear = function () {
			$scope.dt = null;
		};
		/* 时间选择控件 */

		/*
		 * 发微博打标签相关
		 */
		// $scope.tags = {};
		$scope.selectedTags = {};
		$scope.showTagModal = function (weibo_id, tags) {
			var resolve = {
				/* 系统设置的标签项 */
				tags: function () {
					return $scope.tags
				}, 
				/* 筛选条件中选定的标签 (标签筛选时用) */
				selectedTags: function () {
					return weibo_id == undefined ? $scope.selectedTags : true;
				}, 
				/* 要绑定标签的微博ID (绑定标签时用) */
				weibo_id: function () {
					return weibo_id == undefined ? 0 : weibo_id;
				}, 
				/* 微博已绑定的标签数组 (绑定标签时用) */
				bindedTags: function () {
					return weibo_id == undefined ? [] : tags;
				},
				getTimeline: function () {
					return weibo_id == undefined ? false : $scope.getTimeline;
				}
			};
			Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
		}

		// 已发布微博绑定标签
		$scope.bindTags = function(weibo_id, tagids){
			/* 获取已关联的标签 */
			var tags = [];
			if (tagids != undefined && tagids.length > 0) 
				tags = tagids.split(',');
			$scope.showTagModal(weibo_id, tags);
		}

		// 标签选择弹框控制器
		var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'weibo_id', 'selectedTags', 'bindedTags', 'getTimeline',  function ($scope, $modalInstance, tags, weibo_id, selectedTags, bindedTags, getTimeline) {

			var is_filter = weibo_id === 0; // 判定是筛选标签还是绑定标签
			$scope.selectedTags = is_filter ? selectedTags : {};
			$scope.tags = tags;
			$scope.common = {tempTags : {}};

			for (var i in bindedTags) {// 已发布微博，标签绑定
				var tag_id = parseInt(bindedTags[i]);
				$scope.selectedTags[tag_id] = tag_id;
			}

			for (var i in $scope.selectedTags) // 标签筛选，选中标签
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

			// 确认选择标签 【筛选标签和绑定标签两个功能】
			$scope.ok = function () 
			{
				if (is_filter) // 已发布的微博标签筛选
				{
					$modalInstance.close();
					return ;
				}

				// 已发布微博绑定标签
				var post = {weibo_id : weibo_id};
				for (var i in $scope.selectedTags)
					post['tags[' + i + ']'] = i;
				$http.post(
					_c.appPath + 'meo/weibo/bind_sent_tags', 
					post
				).success(function(res){
					if (res.code == 200) {
						$.gritter.add({
							title: '绑定成功!',
							text: '绑定标签标签成功!',
							time:'1000',
							class_name:'gritter-success gritter-center'
						});
						$modalInstance.close();
						getTimeline();
					} else {
						$.gritter.add({
							title: '绑定失败!',
							text: res.message || '绑定标签失败!',
							time:'1000',
							class_name:'gritter-info gritter-center'
						});
					}
				}).error(function(){
					$.gritter.add({
						title: '绑定失败!',
						text: '无法绑定标签，请检查网络，稍后尝试!',
						time:'1000',
						class_name:'gritter-danger gritter-center'
					});
					return false;
				})
			}
		}];

		// 点击标签的X按钮，删除已选择的标签
		$scope.removeSelectedTag = function (tagid) { delete $scope.selectedTags[tagid]; }

		/*
		 * 已发布微博相关
		 */
		$scope.userTimeline = {
			data: {
				current_page: 1,
				items_per_page: 10
			}
		};

		/* 获取方法区分 */
		$scope.getTimeline = function () 
		{
			// 如果有请求参数调用 getFilteredTimeline
			if ((typeof $scope.start_date == 'object' && $scope.start_date != null) 
				|| (typeof $scope.end_date == 'object' && $scope.end_date != null) 
				|| ($scope.keyword != undefined && $scope.keyword.trim() != '') 
				|| ($scope.selectedTags != undefined && ! $scope.isEmpty($scope.selectedTags)) )
				$scope.getFilteredTimeline();
			else // 如没有请求参数调用 getUserTimeline
				$scope.getUserTimeline();
		}
		// 获取已发布微博
		$scope.userTimelineEmpty = '载入中...';
		$scope.getUserTimeline = function () {
			Weibo.userTimeline({
				current_page: $scope.userTimeline.data.current_page || 1,
				items_per_page: $scope.userTimeline.data.items_per_page || 10
			}, function (data) {
				if (data.code == 200) {
					for (var i in data.data.statuses) {
						var wb_info = data.data.statuses[i];
						wb_info.source = $sce.trustAsHtml(wb_info.source);
						wb_info.text = $sce.trustAsHtml(WCS.build_feeds(wb_info.text));
						if (wb_info.retweeted_status != undefined) {
							wb_info.retweeted_status.text = $sce.trustAsHtml(WCS.build_feeds(wb_info.retweeted_status.text));
							wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
						}

						data.data.statuses[i] = {wb_info:wb_info, cate_names:wb_info.tags, tagids:wb_info.tagids};
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

		//筛选已发微博
		$scope.getFilteredTimeline = function() {

			var post = {
				start : format_date($scope.start_date),
				end : format_date($scope.end_date),
				keyword : $scope.keyword || '', 
				items_per_page: $scope.userTimeline.data.items_per_page || 10,
				current_page: $scope.userTimeline.data.current_page || 1
			}

			for (var i in $scope.selectedTags) 
				post['tags[' + i + ']'] = i;

			$scope.searchPending = true;
			Weibo.filterTimeline(
				post, function (data) {
				if (data.code == 200) {
					for (var i in data.data.statuses) {
						var item = data.data.statuses[i];
						var wb_info = item.wb_info;
						wb_info.tags = data.data.statuses[i].tags;
						wb_info.source = $sce.trustAsHtml(wb_info.source);
						wb_info.text = $sce.trustAsHtml(wb_info.text);
						if (wb_info.retweeted_status != undefined) {
							wb_info.retweeted_status.text = $sce.trustAsHtml(wb_info.retweeted_status.text);
							wb_info.retweeted_status.source = $sce.trustAsHtml(wb_info.retweeted_status.source);
						}
						data.data.statuses[i] = {wb_info:wb_info, cate_names:item.tags, tagids:item.tagids};
					}
					$scope.userTimeline = data;
					$scope.searchPending = false;
				} else {
					$scope.userTimeline.code = 204;
					$scope.searchPending = false;
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