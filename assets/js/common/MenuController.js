'use strict';
define(['me'], function (me) {
	me.controller('MenuController', ['$scope', '$http', '$location', function ($scope, $http, $location) {
		$http({
			method: 'GET',
			url: _c.appPath + 'common/menu'
		}).success(function (data) {
			if (data.code == 200) {
				$scope.menus = data.data;
				// 直接运行函数不行，获取不到dom，必须加timeout，不知为何
				setTimeout(function () {
					$scope.showCurrentMenu();
				}, 500);
			} else {
				$.gritter.add({
					title : '错误', 
					text : '暂无菜单', 
					time : 1000, 
					class_name : 'gritter-danger gritter-center'
				});
			}
		}).error(function () {
			$.gritter.add({
				title : '错误', 
				text : '网络错误', 
				time : 1000, 
				class_name : 'gritter-warning gritter-center'
			});
		});

		// 获取当前刷新页面的地址，展开相应菜单
		$scope.showCurrentMenu = function (path) {
			// 将其他菜单隐藏
			$('#sidebar li').removeClass('active')
			$('#sidebar li').removeClass('active').removeClass('open');
			$('#sidebar ul.submenu').removeClass('nav-show').addClass('nav-hide').hide();
			// 获取当前url路径
			if (!path) {
				var path = $location.path();
				// 有可能有带参数的链接，所以只取链接的第一部分
				path = path.split('/');
				path = '/' + path[1];
			} else {
				path = '/' + path;
			}

			// 获取相应菜单对象
			var currentMenu = $('#sidebar ul.nav-list a[href^="#' + path + '"]');
			// 有可能会有path-xxx的形式href，判断并排除
			if (currentMenu.length > 1) {
				currentMenu = $('#sidebar ul.nav-list a[href^="#' + path + '/"]');
				if (currentMenu.length == 0) {  // 有可能把正确菜单href排除掉，再次挽回
					currentMenu = $('#sidebar ul.nav-list a[href="#' + path + '"]');
				}
			}
			
			currentMenu.parents('li').addClass('active');

			// 获取相应菜单的父对象
			var parentMenu = currentMenu.parents('ul.submenu');
			// alert(parentMenu.length);
			if (parentMenu.length) {
				parentMenu.removeClass('nav-hide').addClass('nav-show').show();
				parentMenu.parent('li').addClass('open');
			}
		}
	}]);
});