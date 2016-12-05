'use strict';

/***********************************************************
-|-|-	        _==/          i     i          \==_
-|-|-	      /XX/            |\___/|            \XX\
-|-|-	    /XXXX\            |XXXXX|            /XXXX\
-|-|-	   |XXXXXX\_    ^    _XXXXXXX_    ^    _/XXXXXX|
-|-|-	 XXXXXXXXXXXxxxxxxxXXXXXXXXXXXxxxxxxxXXXXXXXXXXX
-|-|-	 |XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX|
-|-|-	 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
-|-|-	 |XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX|
-|-|-	 XXXXXX/^^^^"\XXXXXXXXXXXXXXXXXXXXX/^^^^^\XXXXXX
-|-|-	   |XXX|       \XXX/^^\XXXXX/^^\XXX/       |XXX|
-|-|-	     \XX\       \X/    \XXX/    \X/       /XX/
-|-|-	        "\       "      \X/      "      /"
***********************************************************/

/* 挂起任务列表，用于注入 */
/* 成员列表：
TaskList : 		挂起任务列表数组
count : 		挂起任务数量, 微博&微信
get_tasks : 	获取挂起任务，数量统计
add_task : 		添加挂起任务 <TODO>
edit_task : 	修改挂起任务 <TODO>
del_task : 		删除挂起任务 <TODO>
set_notify : 	设置提醒方法
clear_notify : 	清空提醒方法
 */
define(['me'], function (me) {
	me.factory('SuspendingService', ['$resource', '$http', function ($resource, $http) {
		var service = {};
		service.TaskList = [];

		service.count = {wb:0, wx:0};		// 微博, 微信挂起数量
		service.delayed = {wb:0, wx:0};		// 微博, 微信超时数量
		service.pintops = {wb:0, wx:0};		// 微博, 微信自动置顶

		service.get_tasks = function(){
			$http.get(
				_c.appPath + 'meo/communication/get_suspending'
			).success(function(res){
				if (res.code == 200) {
					service.count = res.data.count;
					service.delayed = res.data.delayed;
					service.pintops = res.data.pintops;
					service.TaskList = res.data.tasks;
					service.set_notify();							// 设定JS定时提醒
				}
			});
		};

		// 更新系统挂起任务的提醒 [setTimeout最近的1条]
		service.set_notify = function () { 
			if (service.TaskList.length == 0)
				return false;

			this.clear_notify();

			var today = new Date();
			var time = today.getTime();
			var tomorrow = new Date(today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate()).getTime();
			tomorrow += 24 * 3600 * 1000;
			var nearest = tomorrow;

			var timeouts = [];

			for (var item in service.TaskList) { 
				var item_time = new Date(service.TaskList[item].rm_time).getTime();

				// 与当前时间对比，小于当前时间为已延误，大于当前时间并在本天内的设置提醒！
				if (item_time > time && item_time < nearest) {
					timeouts = [item];
					nearest = item_time;
				} else if (item_time == nearest) {
					timeouts.push(item);
				}
			}

			var ids = '';
			if (timeouts.length > 0) {
				for (var i in timeouts) {
					ids += '<strong>' + service.TaskList[timeouts[i]].rm_time + '</strong><br>' + service.TaskList[timeouts[i]].rm_desc + "<br>";
					var icon_type = service.TaskList[timeouts[i]].type;
				}

				/* TODO:提前三分钟发出提醒 */
				this.notifies = setTimeout(function () {
					$.gritter.add({ 
						text : ids, 
						sticky : true, 
						image : 'assets/img/' + icon_type + '_48x48.png', 
						class_name : 'gritter-info' });
					service.set_notify();
				}, nearest - time);
			}
		};

		service.clear_notify = function () {
			if (this.notifies != undefined) 
				clearTimeout(this.notifies);
		}

		service.get_tasks();
		service.set_notify();
		return service;
	}]);
});