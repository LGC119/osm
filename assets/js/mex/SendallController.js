'use strict';

define(['me'], function (me) {
    me.controller('SendallController', ['$scope', '$sce', '$http', '$route', '$routeParams', '$modal', '$log', 'WeixinUserGroup', 'Media', 'Tag', function ($scope, $sce, $http, $route, $routeParams, $modal, $log, WeixinUserGroup, Media, Tag) {

        // 初始化
        $scope.getUserInfo = false;
        // 搜索条件
        $scope.search = {};
        $scope.search.tag = [];
        $scope.search.status = '';
        $scope.search.title = '';

        // 获取标签列表
        Tag.resource.query({}, function (data) {
            $scope.search.tags = data;
            var obj = {};
            for (var tagI = 0; tagI < $scope.search.tags.data.length; tagI++) {
                var searchid = "1" + $scope.search.tags.data[tagI]['id'];
                var name = $scope.search.tags.data[tagI]['tag_name'];
                obj['id'] = searchid
                obj['name'] = name;
                $scope.search.tag.push(obj);
                obj = {};
                if ($scope.search.tags.data[tagI]['tags'].length > 0) {
                    for (var tagsI = 0; tagsI < $scope.search.tags.data[tagI]['tags'].length; tagsI++) {
                        searchid = $scope.search.tags.data[tagI]['tags'][tagsI]['id'];
                        name = "- - -" + $scope.search.tags.data[tagI]['tags'][tagsI]['tag_name'];
                        obj['id'] = searchid
                        obj['name'] = name;
                        $scope.search.tag.push(obj);
                        obj = {};
                    }
                }
            }
        });


        // 群发对象相关变量
        $scope.sendValue = {
            sGroup: false,
            sValue: '',
            sType: 'text',
            isImageDiv: false,
            isNewsDiv: false,
            isVoiceeDiv: false,
            isText: true,
            imageSrc: '',
            imageDate: '',
            voiceSrc: '',
            voiceDate: '0000-00-00 00:00:00',
            newsData: '',
            sProvince: _c.get_city
        };

        $scope.data = {};

        // 活动类型与活动行业默认值
        $scope.event = {};
        $scope.event.type = 0;
        $scope.event.name = "";
        $scope.event.industry = 0;
        // 初始化
        $scope.cfmStatus = false;
        $scope.cfmAllStatus = false;

        $scope.getList = function () {
            WeixinUserGroup.getList({status:1,items_per_page:100},function (res) {
                if (res.code == 200) {
                    $scope.groupData = res.data.groups;
                    $scope.data.group = $scope.groupData[0]['id'];
                    $scope.group_id = $routeParams.group_id;
                    for (var ii in $scope.groupData) {
                        if ($scope.groupData[ii]['id'] == $scope.group_id) {
                            $scope.sendValue.sGroup = "true";
                            $scope.data.group = $scope.group_id;
                            return;
                        }
                    }
                }
            });
        }
        $scope.getList();

        // 默认选中组

        $scope.common = {};


        $scope.common.showDtPiker = false,
//        $scope.common.dt = new Date(),
            $scope.common.minDate = new Date();
        // 设置时间调整步进
        $scope.common.hstep = 1;
        $scope.common.mstep = 5;
        // 是否为12小时制
        $scope.common.ismeridian = false;


        $scope.filter = {
            sCountry: false,
            pro: false,
            city: false
        }

        $scope.a = 1;
        // 文字
        $scope.textBox = function () {
            $scope.common = {};
            $scope.common.hstep = 1;
            $scope.common.mstep = 5;
            $scope.sendValue.isImageDiv = false;
            $scope.sendValue.isNewsDiv = false;
            $scope.sendValue.isVoiceDiv = false;
            $scope.sendValue.isText = true;
            $scope.sendValue.sType = 'text';
        }


        // 弹出框图文 图片 语音
        $scope.showBox = function (type) {
            // 图文
            $scope.common = {};
            $scope.common.hstep = 1;
            $scope.common.mstep = 5;
//            $scope.common.selectedMedia= {};
//            $scope.common.selectedMediaId = {};
            var resolve = {
                common: function () {
                    return $scope.common;
                },
                sendValue: function () {
                    return $scope.sendValue;
                },
                search: function () {
                    return $scope.search;
                }
            }
            var status = true;
            if (type == 'articles') {
                status = false;
            }
            Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve, type, status);
        }

        // 确定让$scope.cfmStatus 为true
        $scope.cfm = function () {
            $scope.cfmStatus = true;
            $("#cfm").modal('hide');
            $scope.send();
        }

        // 确定让$scope.cfmAllStatus 为true
        $scope.cfmAll = function () {
            $scope.cfmAllStatus = true;
            $("#cfmAll").modal('hide');
            $scope.send();
        }

        // 刷新获取人数
        $scope.refresh = function () {
            var group = '';
            if ($scope.data.group && $scope.sendValue.sGroup && $scope.sendValue.sGroup != 'false') {
                group = $scope.data.group;
            }
            var country = $scope.filter.sCountry;
            if (country && country != 'false') {
                var province = $("#province").val() ? $("#province").val() : '';
                var city = $("#city").val() ? $scope.filter.city : '';
            } else {
                var province = '';
                var city = '';
            }
            var sex = $("#sex").val() ? $("#sex").val() : '';
            var send_num = $("#send_num").val() ? $("#send_num").val() : 99;
            $http.post(
                _c.appPath + 'mex/user/get_user_openid',
                {
                    group: group,
                    sex: sex,
                    country: country,
                    province: province,
                    city: city,
                    count: true,
                    send_num: send_num
                }
            ).success(function (data) {
                    if (data) {
//                        var strLen = data.split(',');
//                        var sLen = strLen.length;
                        $("#selecNum font").text(data);
                    } else {
                        $("#selecNum font").text('0');
                    }
                });
        }

        // 读取选中的用户信息
        $scope.getUser = function () {
            $scope.getUserInfo = true;
            var group = '';
            if ($scope.data.group && $scope.sendValue.sGroup && $scope.sendValue.sGroup != 'false') {
                group = $scope.data.group;
            }
            var country = $scope.filter.sCountry;
            if (country && country != 'false') {
                var province = $("#province").val() ? $("#province").val() : '';
                var city = $("#city").val() ? $scope.filter.city : '';
            } else {
                var province = '';
                var city = '';
            }
            var sex = $("#sex").val() ? $("#sex").val() : '';
            var send_num = $("#send_num").val() ? $("#send_num").val() : 99;
            $http.post(
                _c.appPath + 'mex/user/get_user_info',
                {
                    group: group,
                    sex: sex,
                    country: country,
                    province: province,
                    city: city,
                    send_num: send_num
                }
            ).success(function (data) {
                    $scope.userInfoALl = data.data;
                    $http.post(
                        _c.appPath + 'mex/send/get_send_num', {}
                    ).success(function (dataopenid) {
                            $scope.useropenid = dataopenid.data;
                        });
                });
        }

        // 群发 发布
        $scope.send = function () {
            if ($scope.sendValue.sType == 'text') {
                $scope.sendValue.sValue = $scope.weixinText ? $scope.weixinText : ($("#msg-content").val() ? $("#msg-content").val() : '');
            }
            if (!$scope.sendValue.sValue) {
                $.gritter.add({
                    title: '没有发送内容',
                    time: '500',
                    class_name: 'gritter-error gritter-center'
                });
                return;
            }
            var group = '';
            if ($scope.data.group && $scope.sendValue.sGroup && $scope.sendValue.sGroup != 'false') {
                group = $scope.data.group;
            }
            // var exec_time = $("#exec_time").text() ? $("#exec_time").text() : '';
            if ($scope.common.dt == undefined) {
                var exec_time = '';
            } else {
                var exec_time = $scope.common.dt.getFullYear() + '-'
                    + ($scope.common.dt.getMonth() + 1) + '-'
                    + $scope.common.dt.getDate() + ' '
                    + $scope.common.dt.getHours() + ':'
                    + $scope.common.dt.getMinutes() + ':'
                    + $scope.common.dt.getSeconds();
            }
            var country = $scope.filter.sCountry;
            if (country && country != 'false') {
                var province = $("#province").val() ? $("#province").val() : '';
                var city = $("#city").val() ? $scope.filter.city : '';
            } else {
                var province = '';
                var city = '';
            }
            var sex = $("#sex").val() ? $("#sex").val() : '';
            var send_num = $("#send_num").val() ? $("#send_num").val() : 99;
            // 通过AJAX获取到用户openid
            $http.post(
                _c.appPath + 'mex/user/get_user_openid',
                {
                    group: group,
                    sex: sex,
                    country: country,
                    province: province,
                    city: city,
                    send_num: send_num
                }
            ).success(function (data) {
                    if (data.data) {

                        if (!exec_time) {
                            // 当前时间
//                exec_time = '';
                            if (!$scope.cfmStatus) {
                                $("#cfm").modal('show');
                                return;
                            }
                        }
                        if (typeof data.status != 'undefined' && data.status == 'all') {
                            if (!$scope.cfmAllStatus) {
                                $("#cfmAll").modal('show');
                                return;
                            }
                        }
                        var sOpenid = data.data;
                        $http.post(
                            _c.appPath + 'mex/send/send_openid',
                            {
                                openids: sOpenid,
                                type: $scope.sendValue.sType,
                                value: $scope.sendValue.sValue,
                                exec_time: exec_time,
                                event_type: $scope.event.type,
                                event_name: $scope.event.name,
                                event_industry: $scope.event.industry
                            }
                        ).success(function (res) {
//                            console.log(res);return;
                                if (res.code == 200) {
                                    $.gritter.add({
                                        title: res.message,
                                        time: '500',
                                        class_name: 'gritter-success gritter-center'
                                    });
                                } else {
                                    $.gritter.add({
                                        title: res.message,
                                        time: '1000',
                                        class_name: 'gritter-error gritter-center'
                                    });
                                }
                                $route.reload();
                                $scope.cfmStatus = false;
                            });
                    } else {
                        $.gritter.add({
                            title: '无群发对象！请重新选择',
                            time: '1000',
                            class_name: 'gritter-error gritter-center'
                        });
                    }
                });
        }
        var mediaModalInstanceCtrl = ['$scope', '$modalInstance', 'get_list', 'common', 'sendValue', 'mediaData', 'type', 'itemSelect', 'search', function ($scope, $modalInstance, get_list, common, sendValue, mediaData, type, itemSelect, search) {

            $scope.common = common;
            $scope.search = search;

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
//                console.log($scope.common.selectedMediaId)
                if (jQuery.isEmptyObject($scope.common.selectedMediaId)) {
                    $.gritter.add({
                        title: '请选择!',
//                    text: '请上传图片',
                        time: '1000',
                        class_name: 'gritter-error gritter-center'
                    });
                    return;
                } else {
                    // 判断类型 以及是否选中  还有【图文可多传，但不可超过10个，图片与语音只能上传一个】
//                    $scope.sendValue.sValue = $scope.rule.content;
                    for (var i in $scope.common.selectedMediaId) {
                        if ($scope.common.selectedMediaId[i].length == 0) {
                            status = false;
                            break;
                        } else {
                            sLen = $scope.common.selectedMediaId[i].length;
                            $scope.sendValue.sType = i;
                        }
//                        console.log($scope.common.selectedMedia)
                        $scope.sendValue.sValue = $scope.common.selectedMediaId[i];
//                        console.log($scope.sendValue.sValue);
                    }
                    if (status) {
                        if ($scope.sendValue.sType == 'news') {
                            // 图文不可以超过6个
                            if (sLen > 6) {
                                $.gritter.add({
                                    title: '多图文不可超过6个!',
                                    time: '1000',
                                    class_name: 'gritter-error gritter-center'
                                });
                                return;
                            }
                        } else {
                            // 不是图文 只能上传一个
                            if (sLen > 1) {
                                $.gritter.add({
                                    title: '只能上传一个!',
                                    time: '1000',
                                    class_name: 'gritter-error gritter-center'
                                });
                                return;
                            }
                        }
                        // 正确处理
                        $scope.sendValue.isImageDiv = false;
                        $scope.sendValue.isNewsDiv = false;
                        $scope.sendValue.isVoiceDiv = false;
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

                        if ($scope.sendValue.sType == 'voice') {
                            $scope.sendValue.isVoiceDiv = true;
                            $scope.sendValue.voiceSrc = $scope.common.selectedMedia[$scope.sendValue.sType][0]['filepath'];
                            $scope.sendValue.voiceDate = $scope.common.selectedMedia[$scope.sendValue.sType][0]['created_at'];
                        }

//                        console.log($scope.sendValue.newsData);
                        $scope.sendValue.isText = false;

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
        // 表情
        $('.icon-emotions').emotion({'textarea': 'msg-content'});
    }]);
});

