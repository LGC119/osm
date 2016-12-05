'use strict';

define(['me'], function (me) {
	me.controller('GroupController', ['$scope', '$sce', '$http' , 'WeixinUserGroup' , function ($scope, $sce, $http, WeixinUserGroup) {
		$scope.groupData = {};
		$scope.statisticsData = {};
		$scope.status = 0;
		$scope.arrange = 1;
		var dt = new Date(format_date(new Date()) + ' 00:00:00');
		$scope.dateTime = dt.getTime() - 1;


		/* 格式化日期 */
		function format_date (o)
		{
			if (typeof o != 'object' || o == null) return '';

			var y = o.getFullYear();
			var m = (o.getMonth() + 1) < 10 ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
			var d = o.getDate() < 10 ? '0' + o.getDate() : o.getDate();

			return y + '-' + m + '-' + d;
		}

		$scope.getGroups = function () {
			var params = {
				current_page: $scope.groupData.current_page || 1,
				items_per_page: $scope.groupData.items_per_page || 12,
				status: $scope.status || 0,
				arrange: $scope.arrange 
			};
			if ($scope.keyword) {
				params.keyword = $scope.keyword;
			}

			WeixinUserGroup.getList(params, function (res) {
				if (res.code == 200) {
					$scope.groupData = res.data;
				}
			});
		}
		$scope.getGroups();


		/* 组过期时间提示 */
		(function(){
			$('[data-rel=tooltip]').live('hover', function(){
				$(this).tooltip({html:true});
			});
			// $('[data-rel=popover]').live('click', function(){
			//  $(this).popover({html:true})
			// });
		})();


		$scope.searchGroups = function () {
			$scope.getGroups();
		}

		// 弹出框框
		$scope.alertBox = function(title,content,fun,groupName,groupId){

			$scope.title = title;
			$scope.fun = fun;
			$scope.content = $sce.trustAsHtml(
				$(content).html()
			);
			// 修改使用
			if(groupName){
				$("#editGroupInput").val(groupName);
			}
			if(groupId){
				$("#editGroupHide").val(groupId);
			}

			$("#alertBox").modal('show');
		}

		// 点击确认
		$scope.alertConfirm = function(){
			eval('$scope.' + $scope.fun + '()');
		}

		// 添加分组信息
		$scope.create_group = function(){
			var name = $("input[name='group_name']").val();
			var desc = $("input[name='group_desc']").val();
			var feature = $("input[name='group_feature']").val();
			var valid = $("input[name='group_valid']").val();
			$http.post(
				_c.appPath+'mex/group/insert_group',
				{
					name     : name,
					desc     : desc,
					feature  : feature,
					valid    : valid
				}
			).success(function(data){
//                    console.log(data);
//                    return;
					if(data == 'success'){
						$.gritter.add({
							title: '添加成功!',
//                    text: '请上传图片',
							time:'500',
							class_name:'gritter-success gritter-center'
						});
						$("#alertBox").modal('hide');
					}else{
						$.gritter.add({
							title: '添加失败!',
//                    text: '请上传图片',
							time:'1000',
							class_name:'gritter-error gritter-center'
						});
						$("#alertBox").modal('hide');
					}
					$("#alertBox").modal('hide');
				}).error(function(){

				});

		}

		// 修改分组
		$scope.edit_group = function(){
			var name = $("input[name='name']").val();
			var id = $("input[name='id']").val();
			$http.post(
				_c.appPath+'mex/group/edit_group',
				{
					name     : name,
					id    : id
				}
			).success(function(data){
				if(data == 'success'){
					$.gritter.add({
						title: '修改成功!',
						time:'500',
						class_name:'gritter-success gritter-center'
					});
					$("#alertBox").modal('hide');
				}else{
					$.gritter.add({
						title: '修改失败!',
						time:'1000',
						class_name:'gritter-error gritter-center'
					});
					$("#alertBox").modal('hide');
				}
				$("#alertBox").modal('hide');
			}).error(function(){

			});
		}

		//组特征展示
		$scope.show_filter_param = function(group_id){
			$('#filter_param').modal('show');
			$scope.get_group_by_id(group_id);
		}
		//根据id获取用户组信息
		$scope.get_group_by_id = function(group_id){
			WeixinUserGroup.getFilterGroup({'id':group_id},function(data){
				$scope.filter_param = data;
			});
		}

		//组统计展示
		$scope.show_statistics = function(group_id){
			$('#show_statistics').modal('show');
			$scope.get_group_by_id(group_id);
			var statistics_sex = [];
			var statistics_province = [];
			var statistics_pay = [];
			var statistics_sex_data = [];
			var statistics_province_data = [];
			var statistics_pay_data = [];
			WeixinUserGroup.get_group_statistics({'group_id':group_id}, function (res) {
				$scope.statisticsData = res;
				//性别highchart
				angular.forEach(res.data.sex, function (v, k) {
					if (k == 1) {
						k = '男';
					};
					if (k == 2) {
						k = '女';
					};
						statistics_sex_data.push([k,v]);
					});
				statistics_sex['title'] = '用户组性别统计';
				statistics_sex['url'] = '#chart_sex';
				statistics_sex['data'] = statistics_sex_data;
				$scope.show_highchart(statistics_sex);

				//地区highchart
				angular.forEach(res.data.province, function (v, k) {
						statistics_province_data.push([k,v]);
					});
				statistics_province['title'] = '用户组地区统计';
				statistics_province['url'] = '#chart_area';
				statistics_province['data'] = statistics_province_data;
				$scope.show_highchart(statistics_province);

				//购买力highchart
				var pay_high = 0;
				var pay_middle = 0;
				var pay_low = 0;
				angular.forEach(res.data.purchasing_power, function (v, k) {
					if (k > 1) {
						pay_high += v;
					}else
					if (k > 0.4) {
						pay_middle += v;
					}else{
						pay_low += v;
					}
				});
				statistics_pay['title'] = '用户组购买力统计';
				statistics_pay['url'] = '#chart_pay';
				statistics_pay['data'] = [['低购买力',pay_low],['中购买力',pay_middle],['高购买力',pay_high]];
				$scope.show_highchart(statistics_pay);
			});
		}
		//highchart展示
		$scope.show_highchart = function(data){
			$(data['url']).highcharts({
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false
				},
				title: {
					text: data['title']
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				credits:false,
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							format: '<b>{point.name}</b>: {point.percentage:.1f} %',
							style: {
								color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
							}
						}
					}
				},
				series: [{
					type: 'pie',
					name: '所占比例',
					data: data['data']
				}]
			});
		}

		$scope.listByRegular = function(){
			$scope.arrange = 0;
			$scope.getGroups();
		}

		$scope.listByGoofy = function(){
			$scope.arrange = 1;
			$scope.getGroups();
		}
	}]);
});

