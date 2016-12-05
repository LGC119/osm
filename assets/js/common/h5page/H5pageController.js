'use strict';
define(['me'], function (me) {
	me.controller('H5pageController', ['$scope', '$modal', 'H5page', 'Tag', '$sce','$routeParams', function ($scope, $modal, H5page, Tag, $sce,$routeParams) {

		// H5页面预览iframe相关变量
		$scope.iframe = {};
		$scope.iframe.baseScr = 'app/index.php/h5page-mobile/wxh5_ext/mobile?tpl=';
		$scope.iframe.tpl = '104info';
		$scope.pages = {};

		// H5页面模板选择切换相关变量
		$scope.tpls = [{
			'title': '信息模板',
			'tplId': '104info',
			'desc': '最简单的图文形式快速发布相关信息，在底部配有广告链接编辑。'
		}, {
			'title': '调研模板',
			'tplId': '103survey',
			'desc': '了解用户想法最直接有效的方法，几个选择就可以轻松的实现与客户的沟通，当然最好能留些小奖励。'
		}, {
			'title': '会员申请模板',
			'tplId': '105info',
			'desc': '绑定用户姓名、手机和微信号，建立真实用户信息库。'
		}, {
			'title': '自定义模板',
			'tplId': 'custom',
			'desc': '使用外部链接，可获取页面点击量，和页面的授权浏览记录！'
		}];

		// H5页面保存相关信息变量
		$scope.h5page = {
			tags: {}
		};

		$scope.common = {
			tempTags: {}
		};

		// $scope.custom_url = '';
		// 发布页面弹框，选择标签并发布页面
		$scope.showTagModal = function () {
			var resolve =  {
				h5page: function () {
					return $scope.h5page;
				},
				tpl: function () {
					return $scope.iframe.tpl;
				},
				common: function () {
					return $scope.common;
				}
			}

			Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
		}

		// 标签选择弹框控制器
		var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'h5page', 'tpl', 'H5page', 'common', function ($scope, $modalInstance, tags, h5page, tpl, H5page, common) {
			$scope.tags = tags;
			$scope.h5page = h5page;
			$scope.common = common;
			var custom_url = $('#custom_url').val();
			
			// 将选中的标签添加入h5page的标签数组
			$scope.pushTag = function (tagId) {
				var tagId = parseInt(tagId);
				// 点击时checkbox的选中状态为点击前的状态
				if (!$scope.common.tempTags[tagId]) {
					$scope.h5page.tags[tagId] = tagId;
				} else {
					delete $scope.h5page.tags[tagId];
				}
			}

			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 确认选择标签，并发布H5页面
			$scope.ok = function () {
				// 获取H5页面配置内容
				var htmlss = new Array();
				$('#mobile-frame').contents().find('.changex').each(function (k, v) {
					var idx = $(this).attr('idx');
					var typex = $(this).attr('typex');
					if (typeof(typex) == 'string') {
						switch (typex) {
							case "text":
								htmlss[idx] = $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').text();
								break;
							case "img":
								htmlss[idx] = $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').attr('src');
								break;
							case "rich":
								htmlss[idx] =  $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').html();
								if (htmlss[idx] == '<b style="color:red;">广告位:如不需要，请清空内容,<br/>可追踪广告点击</b>'){
									htmlss[idx] = '';
								}
								break;
							case "value":
								htmlss[idx] = $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').val();
								break;
							case "tip":
								htmlss[idx] = $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').attr('placeholder');
								break;
							case "radio":
								break;
							case "select":
							   var tmp = new Array();
								$('#mobile-frame').contents().find('.changex[idx="'+idx+'"] .optionx').each(function(k,v){
								   tmp[k] = $(this).val();
								});
								htmlss[idx] = tmp;
								break;
							case "option":
								htmlss[idx] = 'option:none';
								break;
							case "check":
							   var tmp = new Array();
								$('#mobile-frame').contents().find('.changex[idx="'+idx+'"] input').each(function(k,v){
								   tmp[k] = $(this).attr('checkname');
								});
								htmlss[idx] = tmp;
								break;
							case "ads":
								htmlss[idx] =  $('#mobile-frame').contents().find('.changex[idx="'+idx+'"]').html();
								if (htmlss[idx] == '<center><b style="color:red;">点击以添加广告位</b></center>'){
									htmlss[idx] = '';
								}
								break;
						};  //End Switch
					};
				});
				$scope.h5page.html = htmlss;
				$scope.h5page.title = $('#mobile-frame').contents().find('#htmltitle').text();
				$scope.h5page.template = tpl;

				if (tpl == 'custom') {
					/* 判断URL是否合法 */
					if (typeof custom_url == 'undefined' || custom_url.trim() == '') {
						$.gritter.add({ title : '错误！', text : '请填写URL地址！', time : 1000, class_name : 'gritter-warning gritter-center' });
						return false;
					}
					$scope.h5page.custom_url = custom_url.trim();
					if ( ! /^(http|https):\/\//i.test(custom_url)) {
						$.gritter.add({ title : '错误！', text : '请输入正确完整的URL地址！', time : 1000, class_name : 'gritter-warning gritter-center' });
						return false;
					}
				}

				H5page.create($scope.h5page, function (data) {
					if (data.code == 200) {
						var className = 'gritter-success gritter-center';
						$modalInstance.close();
					} else {
						var className = 'gritter-warning gritter-center';
					}

					$.gritter.add({ 
						title : data.message, 
						time : 2000, 
						class_name : className 
					});
				});
			}


		}];


		// 获取h5页面列表
		$scope.pagesList = function (page_id) {
			if(page_id){
				$scope.is_view = true;
				$scope.pages.items_per_page = 1000;
			}
			H5page.pagesList({
				current_page: $scope.pages.current_page || 1,
				items_per_page: $scope.pages.items_per_page || 12
			}, function (res) {
				var data = res.data;
				for (var i in data.data) {
					if (data.data[i].template != 'custom') {
						data.data[i].html_code[2] = $sce.trustAsHtml(data.data[i].html_code[2]);
					}
				}
				$scope.pages = data;
				if(page_id){
					for (var i in $scope.pages.data) {
						if ($scope.pages.data[i].id == page_id) {
							$scope.view_page = $scope.pages.data[i];
							break;
						}
					};
				}
			});
		}

		// 显示页面
		$scope.page_view = function (page_id)
		{
			$scope.is_view = true;
			for (var i in $scope.pages.data) {
				if ($scope.pages.data[i].id == page_id) {
					$scope.view_page = $scope.pages.data[i];
					break;
				}
			};
		}

		if($routeParams.page != 0){
			$scope.pagesList($routeParams.page);
		}

		// 显示列表
		$scope.list_view = function () 
		{
			$scope.is_view = false;
			$scope.view_page = {};
		}

		// 删除页面
		$scope.delete = function (id, title) 
		{
			if (confirm('确定删除页面: ' + $.trim(title) + '?')) {
				H5page.delete({id:id}, function (res) {
					if (res.code == 200) {
						alert('删除成功！');
						// 在页面上删除对应框
						for (var i in $scope.pages.data) 
							if ($scope.pages.data[i]['id'] == id) 
								$scope.pages.data.splice(i, 1);
					} else {
						alert(res.message);
					}
				}, function () {
					alert('删除失败，请稍后尝试！');
				})
			}
		}

		$(function(){
			$('#modal-container-window').on('click', '#addoption', function(){
				$('#optionlist').append('<span class="oneselection"><input class="optiontxt" value="选择项" type="text" /> &nbsp; <span class="label label-important removeme">&nbsp;-&nbsp;</span><br/></span>');
			});
			$('#modal-container-window').on('click', '.removeme', function() {
				$(this).parent('.oneselection').remove();
			});
		})

	}]);
});
