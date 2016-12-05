'use strict';

/* 微博信息流处理控制器 */
define(['me'], function (me) {
	me.controller('WeixinOperationController', ['$scope', '$http', '$sce', '$routeParams', 'WxCommunication', 'SuspendingService', '$modal','Media','User','Tag','Staff', function ($scope, $http, $sce, $routeParams, WxCommunication, SuspendingService, $modal, Media, User, Tag , Staff) {
        // 城市选择器
        $scope.cityData = {};
        $scope.cityData.city = _c.get_city;
        $scope.cityData.country = false;
        $scope.cityData.province = '';
        $scope.cityData.cityV = '';
        $scope.search = {};
        $scope.search.nickname = '';
        $scope.search.user_openid = '';
        $scope.search.content = '';
        $scope.search.sex = '';

        $scope.status_names = ['待分类', '待处理', '已忽略', '已处理'];

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

        // 日期插件初始化
        $scope.bootDate = {};
        $scope.bootDate.dt = null;
//        $scope.minDate = new Date();
        $scope.dateShow = false;

        $scope.bootDate.dt2 = null;
//        $scope.bootDate.minDate2 = new Date();
        $scope.bootDate.dateShow2 = false;

        // 搜索条件
//        $scope.search = {};
        $scope.search.tag = [];
        $scope.search.status = '';
        $scope.search.title = '';

        // 获取标签列表
        Tag.resource.query({},function(data) {
            $scope.search.tags = data;
            if (typeof data != 'object' || data.data == null) return ;
            var obj = {};
            for( var tagI=0;tagI<$scope.search.tags.data.length;tagI++ ){
                var searchid = "1"+$scope.search.tags.data[tagI]['id'];
                var name = $scope.search.tags.data[tagI]['tag_name'];
                obj['id'] = searchid
                obj['name']=name;
                $scope.search.tag.push(obj);
                obj={};
                if($scope.search.tags.data[tagI]['tags'].length > 0){
                    for(var tagsI=0;tagsI<$scope.search.tags.data[tagI]['tags'].length;tagsI++){
                        searchid = $scope.search.tags.data[tagI]['tags'][tagsI]['id'];
                        name = "- - -"+$scope.search.tags.data[tagI]['tags'][tagsI]['tag_name'];
                        obj['id'] = searchid
                        obj['name']=name;
                        $scope.search.tag.push(obj);
                        obj={};
                    }
                }
            }
        });

		$scope.empty = '载入中...';
		$scope.pcat = [];		// 顶级分类
		$scope.scat = {};		// 二级分类
		$scope.status = $routeParams.status;
		$scope.timeline = {};
        $scope.common = {};
        $scope.cmnData = {};
        $scope.qrs_empty = '加载中...';

        /* 挂起任务列表 */
        $scope.SS = SuspendingService;
        // 时间设置
        $scope.minDate = new Date();

        // 设置时间调整步进
        $scope.hstep = 1;
        $scope.mstep = 5;
        // 是否为12小时制
        $scope.ismeridian = false;
        //获取标签对应名称
        User.resource.getTagIdName(function(res){
            if(res.code == 200){
                $scope.tagName = res.data;
            }
        })
        //用户对应组
        User.resource.getGroupIdName(function(res){
            if(res.code == 200){
                $scope.groupIdName = res.data;
            }
        })

        $scope.formatDate = function(obj){
            if(!obj){
                return obj;
            }
            var time = obj.getFullYear() + '-'
                + (obj.getMonth() + 1) + '-'
                + obj.getDate() + ' '
                + obj.getHours() + ':'
                + obj.getMinutes() + ':'
                + obj.getSeconds();
            return time;
        }

        $scope.get_filter = function(){
            // 要获取的信息  地区  性别  开始日期  结束日期 昵称 内容
            // 地区判断【1 如果国家为全部，则城市全为空】
            var searchData = {};
            //如果存在国家
            if($scope.cityData.country && $scope.cityData.country != 'false'){
                searchData.country = 1;
                // 如果存在省
                if($("#province").val()){
                    searchData.province = $("#province").val();
                    // 如果存在市
                    searchData.city = $("#city").val() ? $scope.cityData.cityV : '';
                }
            }
            searchData.date1 = $scope.formatDate($scope.bootDate.dt);
            searchData.date2 = $scope.formatDate($scope.bootDate.dt2);
            searchData.sex = $scope.search.sex;
            searchData.nickname = $scope.search.nickname;
            searchData.user_openid = $scope.search.user_openid;
            searchData.content = $scope.search.content;
            return searchData;
        }

        /*----------- 对话模式获取用户的信息 ----------*/
		$scope.get_timeline_user = function (status,order) {
            //前台只显示一个按钮，默认是顺序排列，点击切换为倒序
            if(order == 'sort' && !$("#icon_arror").hasClass(".icon_arror")){
                var order="reverse";
                $("#icon_arror").addClass(".icon_arror");
                $("#icon_arror").find("i").removeClass("fa-long-arrow-up").addClass("fa-long-arrow-down");
            }else if(order == 'sort' && $("#icon_arror").hasClass(".icon_arror")){
                var order="sequence";
                $("#icon_arror").removeClass(".icon_arror");
                $("#icon_arror").find("i").removeClass("fa-long-arrow-down").addClass("fa-long-arrow-up");
            }

            if(typeof $scope.timeline_user == 'undefined'){
                $scope.timeline_user = {};
            }
            $scope.timeline_user.total_number = 1;

            WxCommunication.get_users({
                status : status,
                order : order,
				current_page: $scope.timeline_user.current_page || 1,
				items_per_page: $scope.timeline_user.items_per_page || 10
            }, function(res) {
                if (res.code == 200) {
                    $scope.timeline_user = res.data;
                    //console.log($scope.timeline_user);
                    
                    //初始时order传入的是空且如果获取到的第一个用户的openid不为空就查找第一个用户所发的信息
                    if(typeof order == 'undefined' && $scope.timeline_user.users[0]['openid'] != 'undefined'){
                        $scope.get_timeline(1,$scope.timeline_user.users[0]['openid']);
                    }
                }
            });
		}
        /*------------------- 结束 -------------------*/

		// 获取feed
        $scope.get_timeline = function (status,user_openid) {

            if(typeof $scope.timeline == 'undefined'){
                $scope.timeline = {};
            }
            $scope.timeline.total_number = 1;
			if (typeof $scope.status == 'undefined')
				$scope.status = status || 0;

            //对话模式页面点击用户名时调用get_timeline方法,同时传入用户的openid用于搜索
            if(typeof user_openid != 'undefined'){
                $scope.search.user_openid = user_openid;
                $scope.timeline.current_page = 1;
            }

            var filterData = $scope.get_filter();
			WxCommunication.get_feeds({
                filterData: filterData,
				status : $scope.status,
				current_page: $scope.timeline.current_page || 1,
				items_per_page: $scope.timeline.items_per_page || 10
			}, function(res) {
				if (res.code == 200) {
                    $scope.timeline = res.data;
					if ($scope.timeline.feeds.length > 0) {
						for (var i in $scope.timeline.feeds) {
                            if($scope.timeline.feeds[i]['content']){
                                $scope.timeline.feeds[i]['content'] = $sce.trustAsHtml(
                                    $scope.timeline.feeds[i]['content']
                                );
                            }
							$scope[$scope.timeline.feeds[i]['id']] = {
								cats:[], 
								reply:'',
                                isShowTxt: true
							};
                            if($scope.timeline.feeds[i].tag_id != undefined && $scope.timeline.feeds[i].tag_id.indexOf(',')){
                                $scope.timeline.feeds[i].tag_id = $scope.timeline.feeds[i].tag_id.split(',');
                            }
                        	for (var j in $scope.timeline.feeds[i].replies) {
                        		var reply = $scope.timeline.feeds[i].replies[j];
	                            if(reply.type != 'text') 
	                                reply.content = $.parseJSON(reply.content);
                        	}
							// 切出视频文件和缩略图的地址
							if ($scope.timeline.feeds[i]['type'] == 'video') {
								var arr = $scope.timeline.feeds[i]['picurl'].split('<|>');
								$scope.timeline.feeds[i]['video'] = arr[0];
								$scope.timeline.feeds[i]['thumb'] = arr[1];
							}
						}
					}
				} else {
                    $scope.timeline.feeds = null;
					$scope.empty = res.message;
				}

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

		/* 加载视频音频 */
		$scope.loadSrc = function (id) 
		{
			for (var i in $scope.timeline.feeds) 
			{
				if ($scope.timeline.feeds[i]['id'] == id) {
					var item = $scope.timeline.feeds[i];
					if (item.type == 'video') {
						if (item['videosrc'] == undefined) 
							$scope.timeline.feeds[i]['videosrc'] = $scope.timeline.feeds[i]['video'];
					} else if (item.type == 'audio') {
						if (item['audiosrc'] == undefined)
							$scope.timeline.feeds[i]['audiosrc'] = $scope.timeline.feeds[i]['picurl'];
					}
					break;
				}
			}
		}

		/* 分类一条记录 */
		$scope.categorize = function (id) 
		{
			var id = parseInt(id);

			/* 获取选中的分类 */
			var cats = $scope[id]['cats'];
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
				url : _c.appPath + 'mex/operation/categorize/' + id + '/' + cats.join('_')
			}).success(function(res){
				if (res.code == 200) {
					$scope['showOperationBtn' + id] = true;
					$.gritter.add({
						title : '成功', 
						text : '分类成功！！', 
						time : 2000, 
						class_name : 'gritter-success gritter-center'
					});
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
		}

		/* 回复一条微信 */
		$scope.reply = function (id,types) 
		{
			id = parseInt(id);
			if (id <= 0) 
				return false;
			if ($scope[id].media_type == 'news') {
				var news = $scope.common.selectedMedia.news;
				if (news.length > 6) {
					alert('请不要回复超过6条图文！');
					return false;
				}
				$scope[id].media_id = [];
				for (var i in news) 
					$scope[id].media_id.push(news[i].mid);
			}

			$http.post(
				_c.appPath + 'mex/operation/reply/' + id, 
				$scope[id]
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({
						title : '成功', 
						text : '回复成功', 
						time : 1000, 
						class_name : 'gritter-success gritter-center'
					});
					$scope.get_timeline();
                    if(types == 'categorized_talk'){
                        $scope.get_timeline_user(1,'keep');
                    }
				}
				else {
					$.gritter.add({
						title : '错误', 
						text : res.message, 
						time : 1000, 
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function(){
				alert('回复失败，请稍后尝试！');
			});
		}

		/* 显示挂起窗口 */
		$scope.show_suspend = function (id,types) {
			$scope.set_time = new Date();
			$scope.set_desc = '';
			$scope.suspending_item = id;
			$scope.suspending_types = types;
			$('#suspendbox').modal("show");
        }

		/* 挂起一条信息 */
		$scope.suspend = function () 
		{
			var id = $scope.suspending_item;
			var types = $scope.suspending_types;
            var dt = $scope.set_time;
            var set_time = dt.getFullYear() + '-' 
                    + (dt.getMonth() + 1) + '-' 
                    + dt.getDate() + ' ' 
                    + dt.getHours() + ':'
                    + dt.getMinutes() + ':'
                    + dt.getSeconds(); 
			$http.post(
				_c.appPath + 'mex/operation/suspend/' + id,
				{
                    set_time : set_time, 
                    desc : $scope.set_desc
                }
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ text : '挂起成功！', time : 1000, class_name : 'gritter-success gritter-center' });

                    if(types == 'categorized_talk'){
                        $scope.get_timeline_user(1,'keep');
                    }

					$scope[id].isCollapsed = true;
					$scope.SS.get_tasks();
					return true;
				} else {
					$.gritter.add({ text : res.message, time : 1000, class_name : 'gritter-warning gritter-center' });
					return false;
				}
			}).error(function(){
				$.gritter.add({ text : '操作失败，请稍后尝试！', time : 1000, class_name : 'gritter-warning gritter-center' });
				return false;
			});
		}

		/* @func unsuspend [取消挂起{sid:挂起表中的ID}] */
		$scope.unsuspend = function(sid) {
			$http.post(
				_c.appPath + 'mex/operation/unsuspend/' + sid
			).success(function(res){
				if (res.code == 200) {
					$.gritter.add({ text : '取消挂起成功！', time : 1000, class_name : 'gritter-success gritter-center' });
					$scope.get_timeline();
					$scope.SS.get_tasks();
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

		/* 获取素材库数据 */
		$scope.type_names = {image:'图片', voice:'语音', news:'图文'};
		function get_library (){
			$http.get(
				_c.appPath + 'mex/media/get_media_data'
			).success(function(res){
				if (res.code == 200) {
					if (res.data.data.length > 0) {
						$scope.library = {};
						for (var item in res.data) {
							if ($scope.library[res.data[item].type] == undefined)
								$scope.library[res.data[item].type] = [res.data.data[item]];
							else 
								$scope.library[res.data[item].type].push(res.data.data[item]);
						}
					}
				} else {
					$scope.library_empty = res.message;
				}
			}).error(function(){
				$scope.library_empty = '无法获取素材库数据！';
			});
		};
		get_library();

		/* 获取素材库回复素材 */
		$scope.show_library = function (cmn_id) {
			$scope.current_cmn_id = cmn_id;
			$('#library').modal('show');
		}

        // 显示文本
        $scope.showText = function(cmn_id){
            $scope.common = {};
            $scope[cmn_id].type = 'text';
            $scope[cmn_id].media_id = 0;
            $scope[cmn_id].media_type = null;
            $scope[cmn_id].isShowTxt = true;
        }


        // 素材回复选择弹框
        $scope.showMediaModal = function (type,cmn_id) {
            $scope.common.cmnReply = $scope[cmn_id];
            // 点击选择素材时
            var resolve = {
                common: function () {
                    return $scope.common;
                },
                search:function(){
                    return $scope.search;
                }
            }
            var is_multi = (type == 'news') ? true : false;
            Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve, type, is_multi);
        }

        var mediaModalInstanceCtrl = ['$scope', '$modalInstance','common','search','mediaData','itemSelect','type','get_list', function ($scope, $modalInstance, common,search,mediaData,itemSelect,type,get_list) {
            $scope.type = type;
            $scope.search = search;
            $scope.common = common;
            $scope.mediaData = mediaData;
            $scope.itemSelect = itemSelect;
            $scope.get_list = get_list;
            $scope.get_media_list = function(pageNum){
                $scope.params = {};
                $scope.params.type = type;
                $scope.params.page = pageNum;
                if(type=='news'){
                    $scope.params.tag = $scope.search.status;
                }
                $scope.params.title = $scope.search.title;
                $scope.mediaData = $scope.get_list($scope.params)
            }

            // 搜索
            $scope.media_search = function(){
                $scope.params = {};
                $scope.params.type = type;
                if(type=='news'){
                    $scope.params.tag = $scope.search.status;
                }
                $scope.params.title = $scope.search.title;
                $scope.mediaData = $scope.get_list($scope.params)
            }

            // 取消
            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 确定
            $scope.ok = function () {
                $scope.common.cmnReply.media_id = $scope.common.selectedMediaId[$scope.type][0];
                $scope.common.cmnReply.media_type = $scope.type;
                $scope.common.cmnReply.isShowTxt = false;

                if ($scope.type == 'voice'){
                    $scope.common.cmnReply.is_voice = true;
                }else if($scope.type == 'image'){
                    $scope.common.cmnReply.title = '';
                    $scope.common.cmnReply.is_voice = false;
                }else{
                    $scope.common.cmnReply.is_voice = false;
                }

//                if($scope.type == 'articles'){
//                    $scope.common.cmnReply = $scope.common.selectedMedia;
//                }

                if($scope.common.selectedMedia[$scope.type][0]['title'] != undefined) 
                    $scope.common.cmnReply.title = $scope.common.selectedMedia[$scope.type][0]['title'];

                if($scope.common.selectedMedia[$scope.type][0]['filepath'] != undefined)
                    $scope.common.cmnReply.filepath = $scope.common.selectedMedia[$scope.type][0]['filepath'];

                $modalInstance.close();
            }

        }];

		$scope.use_item = function (media_id, type) {
			if ($scope[$scope.current_cmn_id].media_id == media_id) {
				$scope[$scope.current_cmn_id].media_id = 0;
				$scope[$scope.current_cmn_id].media_type = null;
			} else {
				$scope[$scope.current_cmn_id].media_id = media_id;
				$scope[$scope.current_cmn_id].media_type = type;
			}
		}

		$scope.clear_item = function () {
			$scope[$scope.current_cmn_id].media_id = 0;
			$scope[$scope.current_cmn_id].media_type = null;
		}

		/* 载入腾讯地图JS ['浏览器阻止直接在DOM中二次异步载入，请勿尝试修改'] */
		// function loadMapJS (
		// 	$http.get(
		// 		'http://open.map.qq.com/apifiles/v1.0/app.js?v=1.0.140508.1'
		// 	).success(function(res){
		// 		// 
		// 	}).error(function(){
		// 		// 
		// 	});
		// )

		// $scope.show_map = function (x, y, zoom, id, label) 
		// {
		// 	var pos = new soso.maps.LatLng(y, x);
		// 	var map = new soso.maps.Map(
		// 		$('#map' + id),
		// 		{
		// 			center : pos,
		// 			zoomLevel : zoom,
		// 			draggable : false,
		// 			scrollWheel : false,
		// 			zoomInByDblClick : false
		// 		}
		// 	);

		// 	var marker = new soso.maps.Marker({
		// 		position : pos,
		// 		content : label,
		// 		map : map
		// 	});
		// 	$('#map' + id).show();
		// }

		/* 忽略一条 */
		$scope.ignore = function(id,types) {
			var id = parseInt(id);

			$http({
				url : _c.appPath + 'mex/operation/ignore/' + id
			}).success(function(res){
				if (res.code == 200) {
					$.gritter.add({
						title : '成功', 
						text : '操作成功！', 
						time : 2000, 
						class_name : 'gritter-success gritter-center'
					});

                    if(types == 'categorized_talk'){
                        $scope.get_timeline_user(1,'keep');
                        $scope.get_timeline();
                    }

					// 隐藏操作成功的微博
					$scope[id].isCollapsed = true;
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
		$scope.unignore = function(id) {
			var id = parseInt(id);

			$http({
				url : _c.appPath + 'mex/operation/unignore/' + id
			}).success(function(res) {
				if (res.code == 200) {
					$.gritter.add({
						title : '成功', 
						text : '操作成功！', 
						time : 2000, 
						class_name : 'gritter-success gritter-center'
					});
					// 隐藏操作成功的微博
					$scope[id].isCollapsed = true;
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

		/* 置顶一条 */
		$scope.pintotop = function(item) {
			var id = item.id;
			$http({
				url : _c.appPath + 'mex/operation/pin/' + id
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
			var id = item.id;
			$http({
				url : _c.appPath + 'mex/operation/unpin/' + id
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

		// 获取沟通记录
		$scope.showCmnHistory = function (wxUserId) {
			var modalInstance = $modal.open({
                templateUrl: 'assets/html/mex/cmn-history-modal.html',
                controller: cmnHistoryModalInstance,
                size: 'lg',
                resolve: {
                    common: function () {
                        return $scope.common;
                    },
                    wxUserId: function () {
                    	return wxUserId;
                    }
                }
            });
		}

        var cmnHistoryModalInstance = ['$scope', '$modalInstance', 'common', 'wxUserId', function ($scope, $modalInstance, common, wxUserId) {
            $scope.common = common;
            $scope.cmnHistory = {};
            $scope.getCmnHistory = function () {
                var params = {
                    wx_user_id: wxUserId,
                    current_page: $scope.cmnHistory.current_page || 1,
                    items_per_page: $scope.cmnHistory.items_per_page || 10
                };
                $http.get(
                    _c.appPath + 'mex/communication/cmn_history?' + $.param(params)
                ).success(function (res) {
                    if (res.code == 200) {
                        $scope.cmnHistory = res.data;
                        for(var j in $scope.cmnHistory.feeds){
                            if($scope.cmnHistory.feeds[j]['content']){
                                $scope.cmnHistory.feeds[j]['content'] = $sce.trustAsHtml(
                                    $scope.cmnHistory.feeds[j]['content']
                                );
                            }

                            /* 回复内容 */
                        	for (var k in $scope.cmnHistory.feeds[j].replies) {
                        		var reply = $scope.cmnHistory.feeds[j].replies[k];
                        		if (reply.type != 'text')
	                                reply.content = $.parseJSON(reply.content);
                        	}
                        }
                    } else if (res.code == 204) {
                        $scope.cmnHistory = {
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
                        text : '无法获取沟通记录', 
                        time : 2000, 
                        class_name : 'gritter-warning gritter-center'
                    });
                });
            }

            $scope.getCmnHistory();

            $scope.cancel = function () {
                $modalInstance.close();
            };
        }];


	    $scope.collapseReplyBox = function (item) {
            for(var i in $scope.timeline.feeds) {
                $scope.timeline.feeds[i].showReplyBox = false;
            }
            item.showReplyBox = !item.showReplyBox;

	    	// 表情
        	$('.icon-emotions').emotion({
        		'textarea': 'text-reply-' + item.id,
        		'ngModel': $scope[item.id]
        	});
	    }


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



        // 弹出用户详情
        $scope.showUserModal = function (wxUserId, $event) {
            // 阻止冒泡
            if ($event.stopPropagation) $event.stopPropagation();
            if ($event.preventDefault) $event.preventDefault();

            var resolve = {
                common: function () {
                    return $scope.common;
                },
                wxUserId: function () {
                    return wxUserId;
                },
                bootDate:function(){
                    return $scope.bootDate;
                }
            };
            var type = 'weixin';
            User.showUserModal($scope, userModalInstanceCtrl, resolve, type, wxUserId);
        }

        var userModalInstanceCtrl = ['$scope', '$modalInstance', 'common', 'type', 'userData', 'wxUserId','bootDate',  function ($scope, $modalInstance, common, type, userData, wxUserId,bootDate) {
            $scope.bootDate = bootDate;
            $scope.common = common;
            $scope.userData = userData;
            $scope.userData.cmnHistory = {};

            $scope.getCmnHistory = function () {
                var params = {
                    wx_user_id: wxUserId,
                    current_page: $scope.userData.cmnHistory.current_page || 1,
                    items_per_page: $scope.userData.cmnHistory.items_per_page || 10
                };
                $http.get(
                    _c.appPath + 'mex/communication/cmn_history?' + $.param(params)
                ).success(function (res) {
                    if (res.code == 200) {
                        $scope.userData.cmnHistory = res.data;
                        for(var cmni in $scope.userData.cmnHistory.feeds){
                        	for (var k in $scope.userData.cmnHistory.feeds[cmni].replies) {
                        		var reply = $scope.userData.cmnHistory.feeds[cmni].replies[k];
                        		if (reply.type != 'text')
	                                reply.content = $.parseJSON(reply.content);
                        	}
                        }
                    } else if (res.code == 204) {
                        $scope.userData.cmnHistory = {
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
                        text : '无法获取沟通记录',
                        time : 2000,
                        class_name : 'gritter-warning gritter-center'
                    });
                });
            }

            $scope.getCmnHistory();




            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 点击编辑
            $scope.edit = function () {
                $scope.showEditInput = true;
                $scope.oriUserData = angular.copy($scope.userData);
            };

            // 确认编辑
            $scope.confirmEdit = function () {
                var exec_time = $scope.formatDate($scope.bootDate.dt);
                User.resource.edit({
                    'type': type,
                    'id': wxUserId,
                    'data': {
                        full_name: $scope.userData.full_name,
                        birthday: exec_time,
                        sex:$scope.userData.sex,
                        blood_type: $scope.userData.blood_type,
                        constellation: $scope.userData.constellation,
                        address1: $scope.userData.address1,
                        tel1: $scope.userData.tel1,
                        email1: $scope.userData.email1,
                        qq1: $scope.userData.qq1
                    }
                },function (data) {
                    if (data.code == 200) {
                        $.gritter.add({
                            title : '成功',
                            text : '修改成功',
                            time : 1000,
                            class_name : 'gritter-success gritter-center'
                        });
                    }
                    // $modalInstance.close();
                    $scope.showEditInput = false;
                });
            }

            // 取消编辑
            $scope.cancelEdit = function () {
                $scope.userData = $scope.oriUserData;
                $scope.showEditInput = false;
            }
        }];


        // 日期操作
        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            $scope[type] = true;
        };
        /* 分配 */
        /* 随机分配 */
		$scope.assignRandom = function(item_id, staffs) {
            //随机取一个员工
            var staff = staffs[Math.floor(Math.random()*staffs.length)];
			$http({
				url : _c.appPath + 'mex/operation/assign/' + item_id + '/' + staff.id
			}).success(function(res){
                //console.log(res);
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
				url : _c.appPath + 'mex/operation/assign/' + item_id + '/' + staff_id
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

		/* 重分配一条微信 */
		$scope.reAssign = function (id, types) 
		{
			id = parseInt(id);
			if (id <= 0) 
				return false;

			$http.post(
				_c.appPath + 'mex/operation/reAssign/' + id, 
				$scope[id]
			).success(function(res){
                //console.log(res);
				if (res.code == 200) {
					$.gritter.add({
						title : '成功', 
						text : '重分配成功', 
						time : 1000, 
						class_name : 'gritter-success gritter-center'
					});
					$scope.get_timeline();
                    if(types == 'categorized_talk'){
                        $scope.get_timeline_user(1,'keep');
                    }
				}
				else {
                //console.log(res);
					$.gritter.add({
						title : '错误', 
                        text : res.message, 
						time : 1000, 
						class_name : 'gritter-danger gritter-center'
					});
				}
			}).error(function(){
				alert('重分配失败，请稍后尝试！');
			});
		};
	}]);
});
