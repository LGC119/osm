'use strict';

define(['me'], function (me) {
    me.controller('OtherruleController', ['$scope','$http','Rule','Media', function ($scope,$http,Rule,Media) {
		$scope.sendValue = {};
		$scope.sendValue2 = {};
		$scope.sendValue.isText = true;
		$scope.sendValue2.isText = true;
		$scope.sendValue.sType = 'text';
		$scope.sendValue2.sType = 'text';
        (function(){
            Rule.wxres.select_other(function(res){
                if(res.code == 200){
                    $scope.subscribedReply = res.data['subscribed_reply'];
                    $scope.noKeywordReply  = res.data['nokeyword_reply'];
                    $scope.type  = res.data['type_reply'];
                    $scope.type2  = res.data['type2_reply'];
					if($scope.type == 'image'){
						$scope.sendValue.isText = false;
						$scope.sendValue.isImageDiv = true;
						$scope.sendValue.sType = $scope.type;
						$scope.sendValue.sValue = {};
						$scope.sendValue.sValue[0] = $scope.subscribedReply;
						$scope.sendValue.imageSrc = "uploads/images/"+res.data.data[0]['filename'];
						$scope.sendValue.imageDate = res.data.data[0]['created_at']
					}
					if($scope.type2 == 'image'){
						$scope.sendValue2.isText = false;
						$scope.sendValue2.isImageDiv = true;
						$scope.sendValue2.sType = $scope.type2;
						$scope.sendValue2.sValue = $scope.noKeywordReply;
						$scope.sendValue2.imageSrc = "uploads/images/"+res.data.data2[0]['filename'];
						$scope.sendValue2.imageDate = res.data.data2[0]['created_at']
					}

					if($scope.type == 'news'){
						$scope.sendValue.isText = false;
						$scope.sendValue.isNewsDiv = true;
						$scope.sendValue.sType = $scope.type;
						$scope.sendValue.sValue = {};
						$scope.sendValue.sValue[0] = $scope.subscribedReply;
						res.data.data[0]['filepath'] = "uploads/images/"+res.data.data[0]['filename'];
						$scope.sendValue.newsData = res.data.data;
					}
					if($scope.type2 == 'news'){
						$scope.sendValue2.isText = false;
						$scope.sendValue2.isNewsDiv = true;
						$scope.sendValue2.sType = $scope.type2;
						$scope.sendValue2.sValue = $scope.noKeywordReply;
						res.data.data2[0]['filepath'] = "uploads/images/"+res.data.data2[0]['filename'];
						$scope.sendValue2.newsData = res.data.data2;
					}
					if($scope.type == 'articles'){
						$scope.sendValue.isText = false;
						$scope.sendValue.isArticlesDiv = true;
						$scope.sendValue.sType = $scope.type;
						$scope.sendValue.sValue = {};
						$scope.sendValue.sValue[0] = $scope.subscribedReply;
						$scope.sendValue.articlesData = res.data;
					}
					if($scope.type2 == 'articles'){
						$scope.sendValue2.isText = false;
						$scope.sendValue2.isArticlesDiv = true;
						$scope.sendValue2.sType = $scope.type2;
						$scope.sendValue2.sValue = $scope.noKeywordReply;
						$scope.sendValue2.articlesData = res.data;
					}
					if($scope.type == 'text'){
						$scope.sendValue.isText = true;
					}
					if($scope.type2 == 'text'){
						$scope.sendValue2.isText = true;
					}
                }
            });
        })();

        // 更新其他规则
        $scope.updateReply = function(){
        	var sendValue2 = {
        		sType : $scope.sendValue2.sType || 'text', 
        		sValue : $scope.sendValue2.sValue || 0
        	};
            $http.post(
                _c.appPath+'mex/rule/update_other_rule',
                {
                    subscribedReply     : $scope.subscribedReply,
                    noKeywordReply    : $scope.noKeywordReply,
					type: $scope.sendValue.sType,
					value: typeof $scope.sendValue.sValue!='undefined' ? $scope.sendValue.sValue[0] : '',
					sendValue2:sendValue2
                }
            ).success(function(data){
                if(data.code == '200'){
                    $.gritter.add({
                        title: '更新成功!',
                        time:'500',
                        class_name:'gritter-success gritter-center'
                    });
                }else{
                    $.gritter.add({
                        title: '更新失败!',
                        time:'1000',
                        class_name:'gritter-error gritter-center'
                    });
                }
            }).error(function(){
            });
        }

        // 关注微信帐号 弹出框图文 图片 语音
        $scope.showBox = function (type) {
            // 图文
            $scope.common = {};
            var resolve = {
                common: function () {
                    return $scope.common;
                },
                sendValue: function () {
                    return $scope.sendValue;
                },
				subscribedReply: function(){
					return $scope.subscribedReply;
				},
                search: function () {
                    return $scope.search;
                }
            }
            Media.showMediaModal($scope, mediaModalInstanceCtrl1, resolve, type, false);
        }
        var mediaModalInstanceCtrl1 = ['$scope', '$modalInstance', 'get_list', 'common', 'sendValue', 'mediaData', 'type', 'itemSelect', 'search','subscribedReply', function ($scope, $modalInstance, get_list, common, sendValue, mediaData, type, itemSelect, search,subscribedReply) {
            $scope.common = common;
            $scope.search = search;
			$scope.subscribedReply = subscribedReply;
            // 搜索
            $scope.media_search = function () {
                $scope.params = {};
                $scope.params.type = type;
//                $scope.params.page = pageNum;
                if (type == 'news') {
                    $scope.params.tag = $scope.search.status;
                }
                $scope.params.title = $scope.search.title;
                $scope.mediaData = $scope.get_list($scope.params)
            }


            $scope.mediaData = mediaData;
            $scope.get_list = get_list;
            $scope.get_media_list = function (pageNum) {
                $scope.params = {};
                $scope.params.type = type;
                $scope.params.page = pageNum;
                if (type == 'news') {
                    $scope.params.tag = $scope.search.status;
                }
                $scope.params.title = $scope.search.title;
                $scope.mediaData = $scope.get_list($scope.params)
            }
            $scope.sendValue = sendValue;
            // 点击选择素材的方法
            $scope.itemSelect = itemSelect;
            // 把通用对象中的已选择素材存入规则对象中
            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 确认选择媒体
            $scope.ok = function () {
                var status = true;
                var sLen = 0;
                if (jQuery.isEmptyObject($scope.common.selectedMediaId)) {
                    $.gritter.add({
                        title: '请选择!',
                        time: '1000',
                        class_name: 'gritter-error gritter-center'
                    });
                    return;
                } else {
                    // 判断类型 以及是否选中  还有【图文可多传，但不可超过10个，图片与语音只能上传一个】
                    for (var i in $scope.common.selectedMediaId) {
                        if ($scope.common.selectedMediaId[i].length == 0) {
                            status = false;
                            break;
                        } else {
                            sLen = $scope.common.selectedMediaId[i].length;
                            $scope.sendValue.sType = i;
                        }
                        $scope.sendValue.sValue = $scope.common.selectedMediaId[i];
                    }
                    if (status) {
                        // 正确处理
                        $scope.sendValue.isImageDiv = false;
                        $scope.sendValue.isNewsDiv = false;
                        $scope.sendValue.isArticlesDiv = false;

                        if ($scope.sendValue.sType == 'image') {
                            $scope.sendValue.isImageDiv = true;
                            $scope.sendValue.imageSrc = $scope.common.selectedMedia[$scope.sendValue.sType][0]['filepath'];
                            $scope.sendValue.imageDate = $scope.common.selectedMedia[$scope.sendValue.sType][0]['created_at'];
                        }

                        if ($scope.sendValue.sType == 'news') {
                            $scope.sendValue.isNewsDiv = true;
                            $scope.sendValue.newsData = $scope.common.selectedMedia[$scope.sendValue.sType];
                        }

                        if ($scope.sendValue.sType == 'articles') {
                            $scope.sendValue.isArticlesDiv = true;
                            $scope.sendValue.articlesData = $scope.common.selectedMedia[$scope.sendValue.sType][0];
                        }

                        $scope.sendValue.isText = false;
//						console.log($scope.sendValue)

                        // 关闭弹出框
                        $modalInstance.close();
                    } else {
                        $.gritter.add({
                            title: '请选择!',
                            time: '1000',
                            class_name: 'gritter-error gritter-center'
                        });
                        return;
                    }
                }
            }
        }];

		// 无匹配关键词 弹出框图文 图片 语音
		$scope.showBox2 = function (type) {
			// 图文
			$scope.common = {};
			var resolve = {
				common: function () {
					return $scope.common;
				},
				sendValue2: function () {
					return $scope.sendValue2;
				},
				noKeywordReply: function(){
					return $scope.noKeywordReply;
				},
				search: function () {
					return $scope.search;
				}
			}
			Media.showMediaModal($scope, mediaModalInstanceCtrl2, resolve, type, false);
		}

		var mediaModalInstanceCtrl2 = ['$scope', '$modalInstance', 'get_list', 'common', 'sendValue2', 'mediaData', 'type', 'itemSelect', 'search','noKeywordReply', function ($scope, $modalInstance, get_list, common, sendValue2, mediaData, type, itemSelect, search,noKeywordReply) {
			$scope.common = common;
			$scope.search = search;
			$scope.noKeywordReply = noKeywordReply;
			// 搜索
			$scope.media_search = function () {
				$scope.params = {};
				$scope.params.type = type;
//                $scope.params.page = pageNum;
				if (type == 'news') {
					$scope.params.tag = $scope.search.status;
				}
				$scope.params.title = $scope.search.title;
				$scope.mediaData = $scope.get_list($scope.params)
			}


			$scope.mediaData = mediaData;
			$scope.get_list = get_list;
			$scope.get_media_list = function (pageNum) {
				$scope.params = {};
				$scope.params.type = type;
				$scope.params.page = pageNum;
				if (type == 'news') {
					$scope.params.tag = $scope.search.status;
				}
				$scope.params.title = $scope.search.title;
				$scope.mediaData = $scope.get_list($scope.params)
			}
			$scope.sendValue2 = sendValue2;
			// 点击选择素材的方法
			$scope.itemSelect = itemSelect;
			// 把通用对象中的已选择素材存入规则对象中
			$scope.cancel = function () {
				$modalInstance.close();
			};

			// 确认选择媒体
			$scope.ok = function () {
				var status = true;
				var sLen = 0;
				if (jQuery.isEmptyObject($scope.common.selectedMediaId)) {
					$.gritter.add({
						title: '请选择!',
						time: '1000',
						class_name: 'gritter-error gritter-center'
					});
					return;
				} else {
					// 判断类型 以及是否选中  还有【图文可多传，但不可超过10个，图片与语音只能上传一个】
					for (var i in $scope.common.selectedMediaId) {
						if ($scope.common.selectedMediaId[i].length == 0) {
							status = false;
							break;
						} else {
							sLen = $scope.common.selectedMediaId[i].length;
							$scope.sendValue2.sType = i;
						}
						$scope.sendValue2.sValue = $scope.common.selectedMediaId[i][0];
					}
					if (status) {
						// 正确处理
						$scope.sendValue2.isImageDiv = false;
						$scope.sendValue2.isNewsDiv = false;
						$scope.sendValue2.isArticlesDiv = false;

						if ($scope.sendValue2.sType == 'image') {
							$scope.sendValue2.isImageDiv = true;
							$scope.sendValue2.imageSrc = $scope.common.selectedMedia[$scope.sendValue2.sType][0]['filepath'];
							$scope.sendValue2.imageDate = $scope.common.selectedMedia[$scope.sendValue2.sType][0]['created_at'];
						}

						if ($scope.sendValue2.sType == 'news') {
							$scope.sendValue2.isNewsDiv = true;
							$scope.sendValue2.newsData = $scope.common.selectedMedia[$scope.sendValue2.sType];
						}

						if ($scope.sendValue2.sType == 'articles') {
							$scope.sendValue2.isArticlesDiv = true;
							$scope.sendValue2.articlesData = $scope.common.selectedMedia[$scope.sendValue2.sType][0];
						}


						$scope.sendValue2.isText = false;

						// 关闭弹出框
						$modalInstance.close();
					} else {
						$.gritter.add({
							title: '请选择!',
							time: '1000',
							class_name: 'gritter-error gritter-center'
						});
						return;
					}
				}
			}
		}];


		// 关注微信帐号
		$scope.textBox = function(){
			$scope.sendValue.isText = true;
			$scope.sendValue.isImageDiv = false;
			$scope.sendValue.isNewsDiv = false;
			$scope.sendValue.isArticlesDiv = false;
			$scope.sendValue.sType = 'text';
			$scope.subscribedReply = '';
		}

		// 无匹配关键词的回复
		$scope.textBox2 = function(){
			$scope.sendValue2.isText = true;
			$scope.sendValue2.isImageDiv = false;
			$scope.sendValue2.isNewsDiv = false;
			$scope.sendValue2.isArticlesDiv = false;
			$scope.sendValue2.sType = 'text';
			$scope.noKeywordReply = '';
		}
    }]);
});

