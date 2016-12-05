'use strict';

/* 管理后台路由设置 */
define([], function () {
	return {
		defaultRoutePath: '/dashboard',
		routes: {
			'/dashboard': {
				templateUrl: 'assets/html/common/dashboard.html', 
				dependencies: [
					'common/DashboardController',
				]
			},

			/* 功能模块设定 */
			'/menu-setting': {
				templateUrl: 'assets/html/module/menu-setting.html', 
				dependencies: [
					'module/MenuSettingController',
				]
			},
			'/auth-setting': {
				templateUrl: 'assets/html/module/auth-setting.html', 
				dependencies: [
					'module/AuthSettingController',
				]
			},

			/* 品牌用户管理 */
			'/company': {
				templateUrl: 'assets/html/company/company.html', 
				dependencies: [
					'company/CompanyController',
				]
			},
			'/staff': {
				templateUrl: 'assets/html/company/staff.html', 
				dependencies: [
					'company/StaffController',
				]
			},
			'/authrization': {
				templateUrl: 'assets/html/company/autherization.html', 
				dependencies: [
					'company/AuthrizationController',
				]
			},

			/* 系统日志查询 */
			'/test' : {
				templateUrl:'assets/html/mex/test.html', 
				dependencies:[
				]
			}
		}
	};
});