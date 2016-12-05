'use strict';

define(['me'], function (me) {
	// 获取图文信息
	me.factory('Media',  ['$resource', '$modal', function ($resource, $modal){

        // 请求资源Resource
		var url = _c.appPath + 'mex/media/:action';
        var resource = $resource(url, {}, {
            get_list: {
                method: 'GET',
                params: {
                    'action': 'get_media_data',
                    'type':''
                }
            },
            delete: {
                method: 'POST',
                params: {
                    'action': 'delete'
                }
            },
            get_tag_id: {
                method: 'GET',
                params: {
                    'action': 'media_to_tag'
                }
            }

        });

        // 呈现图文选择模态框方法
        var showMediaModal = function ($scope, MediaModalInstanceCtrl, resolve, type, is_multi,newType) {

            $scope.params = {};
            $scope.params.type = type;


            is_multi = is_multi === false ? false : true;   // 默认是多选
            $scope.get_list = function(params){
                return resource.get_list(params);
            }
            $scope.mediaData = $scope.get_list($scope.params);

            $scope.common.selectedMedia = $scope.common.selectedMedia || {};
            $scope.common.selectedMediaId = $scope.common.selectedMediaId || {};

            var mediaTpl = {
                'articles': 'assets/html/common/articles2-choose-tpl.html',
                'news': 'assets/html/common/articles-choose-tpl.html',
                'image': 'assets/html/common/image-choose-tpl.html',
                'text': 'assets/html/common/text-choose-tpl.html',
                'voice': 'assets/html/common/voice-choose-tpl.html'
            }

            var itemSelect = function (mediaId,item) {

                if (is_multi) {     // 多选模式
                    // $scope.common['itemSelected' + mediaId] = !$scope.common['itemSelected' + mediaId];
                    // 添加
                    if (typeof $scope.common['itemSelected' + mediaId] == 'undefined' || $scope.common['itemSelected' + mediaId] == false) {
                        $scope.common['itemSelected' + mediaId] = true;
                        $scope.common.selectedMediaId[type] = add($scope.common.selectedMediaId[type], mediaId);
                        $scope.common.selectedMedia[type] = add($scope.common.selectedMedia[type], item);
                    } else {
                        // 取消
                        var checkNum = $("#news_"+mediaId+" .item-selected-icon").text();
                        var num = typeof $scope.common.selectedMediaId.news == 'undefined' ? 0 : $scope.common.selectedMediaId.news.length;
                        if(checkNum != num){
                            $.gritter.add({
                                title: '请先取消最大值!',
                                time:'1000',
                                class_name:'gritter-error gritter-center'
                            });
                            return;
                        }else{
                            $scope.common.selectedMediaId[type] = remove($scope.common.selectedMediaId[type], mediaId);
                            $scope.common.selectedMedia[type] = remove($scope.common.selectedMedia[type], item);
                            $scope.common['itemSelected' + mediaId] = false;
                        }
                    }
                    if(newType && newType == 'articles'){
                        var num = typeof $scope.common.selectedMediaId.news == 'undefined' ? 0 : $scope.common.selectedMediaId.news.length;
                        $(".item-selected-icon").css({background:'none'});
                        $("#news_"+mediaId+" .item-selected-icon").text(num);
                    }
                } else {            // 单选模式
                    $scope.common['itemSelected' + mediaId] = !$scope.common['itemSelected' + mediaId];
                    if ($scope.common['itemSelected' + mediaId]) {
                        /* 清除其他选中项 */
                        for (var k in $scope.common) 
                            if (/^itemSelected[\d]+$/i.test(k) && k != ('itemSelected' + mediaId)) 
                                delete($scope.common[k]);
                        /* 清除其他选中项END */
                        $scope.common.selectedMediaId[type] = [mediaId];
                        $scope.common.selectedMedia[type] = [item];
                    } else {
                        $scope.common.selectedMediaId[type] = remove($scope.common.selectedMediaId[type], mediaId);
                        $scope.common.selectedMedia[type] = remove($scope.common.selectedMedia[type], item);
                    }
                }
            }

            // add
            function add(arr, item) {
                arr = angular.isArray(arr) ? arr : [];
                for (var i = 0; i < arr.length; i++) {
                    if (angular.equals(arr[i], item)) {
                        return arr;
                    }
                }
                arr.push(item);
                return arr;
            }

            // remove
            function remove(arr, item) {
                if (angular.isArray(arr)) {
                    for (var i = 0; i < arr.length; i++) {
                        if (angular.equals(arr[i], item)) {
                            arr.splice(i, 1);
                            break;
                        }
                    }
                }
                return arr;
            }

            // 处理resolve
            var defaultResolve = {
                mediaData: function () {
                    return $scope.mediaData;
                },
                get_list:function(){
                    return $scope.get_list;
                },
                type: function () {
                    return type;
                },
                itemSelect: function () {
                    return itemSelect;
                }
            }

            var resolve = angular.extend(defaultResolve, resolve);
            var modalOpt = {
                templateUrl: mediaTpl[type],
                controller: MediaModalInstanceCtrl,
                size: 'lg',
                resolve: resolve
            };
            var modalInstance = $modal.open(modalOpt);
        }


		return {
            resource: resource,
            showMediaModal: showMediaModal
        }
	}]);

});