'use strict';
define(['me'], function (me) {
	me.controller('HeaderController', ['$scope', '$http', 'Account', 'SuspendingService', '$route', function ($scope, $http, Account, SuspendingService, $route) {
		// $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
		$scope.currentWbAccount = '微博账号';
		$scope.currentWxAccount = '微信账号';
		$scope.wbPlatformIcons = [
			'',
			'assets/img/wb_48x48.png',
			'assets/img/tx-icon-24X24.png'
		];
		
		$scope.currentVar = {
			'weibo': 'currentWb',
			'weixin': 'currentWx'
		}

		$scope.currentWb = {};
		$scope.currentWx = {};

		/* 系统挂起任务 */
		$scope.SS = SuspendingService;

		// 检查是否登录
		(function () {
			var url = _c.appPath + 'gate/has_login';
			$http.get(
				url
			).success(function (data) {
				if (data === '0') {
					window.location.href='index.html';
				} else {
					$scope.staffInfo = data;
				}
			}).error(function () {
				// alert('Network looks bad, Please try again !');
			});
		})();

		// 获取账号列表
		(function () {
			// 获取所有账号
			Account.query({}, function(res){
				if (res.code == 200) 
					$scope.accounts = res.data;
			}, function () {
				$.gritter.add({ 
					title : '错误', 
					text : '获取账号失败', 
					time : 2000, 
					class_name : 'gritter-error gritter-center'
				});
			});
		})();

		$scope.switchAccount = function (type, account) {
			/* 账号延期提醒 */
			if (account.expired) 
				$.gritter.add({ title:'授权过期', text:'授权已过期，请登陆账号延期！', time:2000, class_name:'gritter-error' });
			/* 账号延期提醒END */
			Account.query({
				'action': 'switch_account',
				'type': type,
				'aid': account.id
			}, function (data) {
				if (data) {
					$scope.accounts[$scope.currentVar[type]].screen_name = account.screen_name;
					$scope.accounts[$scope.currentVar[type]].profile_image_url = account.profile_image_url;
					$scope.accounts[$scope.currentVar[type]].platform = account.platform;

					// 重载页面
					$route.reload();
				} else {
					$.gritter.add({ 
						title : '错误', 
						text : '账号切换失败', 
						time : 2000, 
						class_name : 'gritter-error gritter-center'
					});
				}
			}, function () {
				$.gritter.add({ 
					title : '错误', 
					text : '账号切换失败', 
					time : 2000, 
					class_name : 'gritter-error gritter-center'
				});
			});
		}

		$scope.switchWxAccount = function (type, account) {
			/* 账号延期提醒 */
			/* 账号延期提醒END */
			Account.query({
				'action': 'switch_account',
				'type': type,
				'aid': account.id
			}, function (data) {
				if (data) {
					$scope.accounts[$scope.currentVar[type]].nickname = account.nickname;
					$scope.accounts[$scope.currentVar[type]].head_pic = account.head_pic;
					// 重载页面
					$route.reload();
				} else {
					$.gritter.add({
						title : '错误',
						text : '账号切换失败',
						time : 2000,
						class_name : 'gritter-error gritter-center'
					});
				}
			}, function () {
				$.gritter.add({
					title : '错误',
					text : '账号切换失败',
					time : 2000,
					class_name : 'gritter-error gritter-center'
				});
			});
		}


		/* 登出 */
		$scope.logout = function () {
			if (confirm('确定退出？')) {
				var url = _c.appPath + 'gate/logout';
				$http.post(url).
					success(function (data) {
						window.location.href='index.html';
					}).
					error(function () {
						alert('Network looks bad, Please try again !');
					});
			}
		}

		// 微信表情
		var emotionsTpl = $('#emotionsTpl').html();
		$('#emotionsTpl').remove();

		$.fn.extend({

			emotion: function(options) {    // 表情插件
				var defaults = {
					'textarea': 'msg-content'
				};

				var options = $.extend(defaults, options);

				//获取光标位置并插入函数
				function getValue(objid,str) {
					var myField = document.getElementById(""+objid);
					//IE浏览器
					if (document.selection) {
						myField.focus();
						var sel = document.selection.createRange();
						// sel.text = sel.text + str;
						text = sel.text + str;
						sel.select();
						if (options.ngModel) {
							$scope.$apply(function () {
								options.ngModel.reply = text;
							});
						}
					}

					//火狐/网景 浏览器
					else if (myField.selectionStart || myField.selectionStart == '0')
					{
						//得到光标前的位置
						var startPos = myField.selectionStart;
						//得到光标后的位置
						var endPos = myField.selectionEnd;
						// 在加入数据之前获得滚动条的高度
						var restoreTop = myField.scrollTop;
						myField.value = myField.value.substring(0, startPos) + str + myField.value.substring(endPos, myField.value.length);
						//如果滚动条高度大于0
						if (restoreTop > 0) {
							 // 返回
							 myField.scrollTop = restoreTop;
						}
						myField.focus();
						myField.selectionStart = startPos + str.length;
						myField.selectionEnd = startPos + str.length;
						// 与angular同步
						if (options.ngModel) {
							$scope.$apply(function () {
								options.ngModel.reply = myField.value;
							});
						}
					}
					else {
						myField.value += str;
						myField.focus();
						if (options.ngModel) {
							$scope.$apply(function () {
								options.ngModel.reply = myField.value;
							});
						}
					}
					
				}

				return this.each(function() {
					var render = $(this);
					render.click(function() {
						render.parent().after(emotionsTpl);
					});
					$('body').off('mouseleave', '.wx-emotions');
					$('body').off('mouseenter', '.eItem');
					$('body').off('click', '.eItem');
					$('body').on(
						'mouseleave',
						'.wx-emotions',
						function() {
							$('.wx-emotions').fadeOut();
						}
					);

					$('body').on(
						'mouseenter',
						'.eItem',
						function() {
							$('.emotionsGif').html('<img src="'+ $(this).attr('data-gifurl') + '" />');
						}
					);
					$('body').on(
						'click',
						'.eItem',
						function() {
							// var content = $('.editArea textarea').val();
							// $('.editArea textarea').val( content + '/' + $(this).attr('data-title') + ' ' );
							getValue( options.textarea, '/' + $(this).attr('data-title') );
							
							$('.wx-emotions').hide();
						}
					);
				});
			}
		})
		
		$scope.get_permission = function(){
			$http.get(
				_c.appPath + 'common/menu'
				).success(function (res){
					angular.forEach(res.data, function (v, k) {
						if (v['id'] == 2) {
							$scope.wb_permission = 1;
						};
						if (v['id'] == 3) {
							$scope.wx_permission = 1;
						};
					});
				}).error(function(){
					// alert('Network looks bad, Please try again !');
				});
		}
		$scope.get_permission();
	}]);
});