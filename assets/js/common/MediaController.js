'use strict';

define(['me'], function (me) {
    me.controller('MediaController', ['$scope','$route', '$http', '$sce', '$location', '$routeParams', 'Media', 'Tag', '$modal', function($scope,$route, $http, $sce, $location, $routeParams, Media, Tag, $modal) {
        $scope.type = $routeParams.type;
        $scope.newsTitle = '新建图文';
        $scope.mediaid = '';
        $scope.voiceMediaId = 0;
        $scope.voiceTitle = '';
        $scope.voiceDesc = '';
        $scope.imageTitle = '';
        $scope.imageDesc = '';
        $scope.voiceIsDisabled = true;

        //  多图文展开与缩起
        $scope.articlesCheck = function(item){
            if(item.articlesText == '展开'){
                item.artiStatus = 10;
                item.articlesText = '收起';
            }else{
                item.artiStatus = 3;
                item.articlesText = '展开';
            }

        }


        // 搜索条件
        $scope.search = {};
        $scope.search.tag = [];
        $scope.search.status = '';
        $scope.search.title = '';

        Tag.resource.query({},function(data) {
            $scope.search.tags = data;
            var obj = {};
            for( var tagI=0;tagI<$scope.search.tags.data.length;tagI++ ){
                var searchid = $scope.search.tags.data[tagI]['id'];
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

        // 分页信息
        $scope.params = {};

        // 获取素材列表
        $scope.getMediaList = function () {
            $scope.params.type = $scope.type;
            $scope.params.page = $scope.params.page || 1;
            $scope.params.perpage = $scope.params.perpage || 8;
            $scope.params.tag = $scope.search.status;
            $scope.params.title = $scope.search.title;
            Media.resource.get_list($scope.params, function (data) {
                $scope.mediaData = data;
                if(typeof data['data'][0] != 'undefined' && typeof data['data'][0]['mediaid'] != 'undefined'){
                    for(var i in $scope.mediaData.data){
                        if(typeof $scope.mediaData.data[i] == 'object'){
                            $scope.mediaData.data[i]['artiStatus'] = 3;
                            $scope.mediaData.data[i]['articlesText'] = '展开';
                        }
                    }
                }
                $scope.params.page = typeof data.data.page!='undefined' ? data.data.page : 1;
                $scope.params.perpage = typeof data.data.perpage!='undefined' ? data.data.perpage : 8;
                $scope.params.sum = typeof data.data.sum!='undefined' ? data.data.sum : 0;
            });
            $scope.media_tag_id = Media.resource.get_tag_id({
                'type': 'news'
            });
        }

        $scope.getMediaList();

        $scope.initUMEditor = function ()
        {
            if(typeof $scope.ue1 == 'undefined'){
                $scope.newsTitle = '新建图文';
                window.UMEDITOR_CONFIG.toolbar= [
                    'source | undo redo | bold italic underline strikethrough | forecolor backcolor | selectall cleardoc fontfamily fontsize',
                    '| justifyleft justifycenter justifyright justifyjustify | link unlink | image'
                ];

                // 设置编辑器的宽度和高度
                window.UMEDITOR_CONFIG.initialFrameWidth = "760";
                // 允许最大字符数
                window.UMEDITOR_CONFIG.maximumWords = 10000;
                window.UMEDITOR_CONFIG.initialFrameHeight = 200;
                $scope.ue1 = UM.getEditor('myeditor',{
                    autoHeightEnabled:true,
                    autoFloatEnabled: false
                });
            }else{
            }
        }

        $scope.loadUMEditor = function ()
        {
            var script = document.createElement("script");
            script.setAttribute("type","text/javascript");

            script.onload = script.onreadystatechange = function() {
                $scope.initUMEditor();
            }
            script.src = "assets/lib/umeditor/umeditor.min.js";
            document.body.appendChild(script);
        }


        // 弹出单图文框
        $scope.newsBox = function(news) {
            $scope.newsTitle = '新建图文';
            if($scope.type == 'news'){
                $scope.loadUMEditor();
            }
            if (news) {
                $scope.newsTitle = '修改图文';
                $scope.mediaid = news.mediaid;
                $("input[name='imgname']").val(news.filename);
                $('#imgNews').attr('src', news.filepath);
                $("input[name='author']").val(news.author);
                $("input[name='title']").val(news.title);
                $("input[name='content_source_url']").val(news.content_source_url);
                if(typeof $scope.ue1 == 'undefined'){
                    var setNum = 0;
                    var dt = setInterval(function(){
                        if(setNum > 10){
                           clearInterval(dt);
                            return;
                        }
                        setNum++;
                        if(typeof $scope.ue1 != 'undefined'){
                            $scope.ue1.ready(function() {
                                //设置编辑器的内容
                                $scope.ue1.setContent(news.content);
                            });
                        }
                    },200);
                }else{
                    $scope.ue1.ready(function() {
                        //设置编辑器的内容
                        $scope.ue1.setContent(news.content);
                    });
                }

                $("textarea[name='digest']").val(news.digest);
                if($scope.media_tag_id.data[news.mediaid]){
                    var tag_id = $scope.media_tag_id.data[news.mediaid].id;
                }else{
                    var tag_id = false;
                }
                var sTag = '';
                // 拼接给默认标签 common.tempTags的
                if(tag_id){
                    for(var i in tag_id){
                        sTag += '"'+tag_id[i]+'"'+":true,"
                    }
                    sTag = sTag.substr(0,sTag.length -1);
                    sTag = '{' + sTag + '}';
                    sTag = $.parseJSON(sTag);
                }
                // 拼接给标签数据到数据库的checkTagId的
                var sTag2 = '';
                if(tag_id){
                    for(var i in tag_id){
                        sTag2 += '"'+tag_id[i]+'":"'+tag_id[i]+'",';
                    }
                    sTag2 = sTag2.substr(0,sTag2.length -1);
                    sTag2 = '{' + sTag2 + '}';
                    sTag2 = $.parseJSON(sTag2);

                }

                // 默认标签
                $scope.common.tempTags = sTag;
                $scope.checkTagId.tags = sTag2;
                if($scope.media_tag_id.data[news.mediaid]){
                    $scope.checkTagName.tags = $scope.media_tag_id.data[news.mediaid].name;
                }
            }else{
                // 防止用户点击修改后，再点击添加
                $("input[name='imgname']").val('');
                $('#imgNews').attr('src', '');
                $("input[name='author']").val('');
                $("input[name='title']").val('');
                $("input[name='content_source_url']").val('');
                $("textarea[name='content']").val('');
                $("textarea[name='digest']").val('');
                $scope.mediaid = '';
                $scope.common.tempTags = {};
                $scope.checkTagId.tags = {};
                $scope.checkTagName.tags = {};
            }

            // 弹出框之后的操作
            $('#newsBox').one('shown.bs.modal', function (e) {
                $scope.uploadify("#uploadNews","#imgNews", '60');
            })

            $("#newsBox").modal({
                show:true
            });
        }

        // 弹出多图文框
        $scope.articlesBox = function(news) {
            var resolve = {
                common: function () {
                    return $scope.common;
                },
                search:function(){
                    return $scope.search;
                },
                newData: function () {
                    return $scope.newData;
                }
            }
            var type = 'news';
            var newType = 'articles';
            Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve,type,true,newType);
        }
        var mediaModalInstanceCtrl = ['$scope', '$modalInstance','get_list','common','search','newData', 'mediaData', 'itemSelect', function ($scope, $modalInstance, get_list,common,search,newData, mediaData,  itemSelect) {
            var type = 'news';
            $scope.common = common;
            $scope.search = search;
            $scope.mediaData = mediaData;
            $scope.get_list = get_list;
            $scope.itemSelect = itemSelect;


            $scope.params = {};
            $scope.params.type = type;

            // $scope.mediaData = $scope.get_list($scope.params)
            $scope.get_media_list = function(pageNum){
                // $scope.params = {};
                $scope.params.type = type;
                $scope.params.page = pageNum;
                $scope.params.title = $scope.search.title;
                if (type=='news') { $scope.params.tag = $scope.search.status; }
                $scope.mediaData = $scope.get_list($scope.params)
            }

            /* 搜索函数！！！ */
            $scope.media_search = function () { $scope.get_media_list(); }

            $scope.cancel = function () {
                $modalInstance.close();
            };

            $scope.ok = function () {
                var status = true;
                var sLen = 0;
                if(jQuery.isEmptyObject($scope.common.selectedMediaId)){
                    $.gritter.add({
                        title: '请选择!',
                        time:'1000',
                        class_name:'gritter-error gritter-center'
                    });
                    return;
                }else{
                    for(var i in $scope.common.selectedMediaId){
                        if($scope.common.selectedMediaId[i].length == 0){
                            status = false;
                            break;
                        }else{
                            sLen = $scope.common.selectedMediaId[i].length;
                        }
                        if(sLen > 6){
                            $.gritter.add({
                                title: '多图文不可超过6个!',
                                time:'1000',
                                class_name:'gritter-error gritter-center'
                            });
                            return;
                        }

                    }

                    var mediaIds = $scope.common.selectedMediaId['news'].join(',');
                    $http.post(
                        _c.appPath+'mex/media/spell',
                        {
                            mediaIds: mediaIds
                        }
                    ).success(function(res){
                        if(res.code == 200){
                            $.gritter.add({
                                title: '拼接成功！',
                                time:'500',
                                class_name:'gritter-success gritter-center'
                            });
                            $route.reload();
                        }
                        return;
                    }).error(function(){
                        $.gritter.add({
                            title: '网络繁忙，请稍后重试!',
                            time:'1000',
                            class_name:'gritter-error gritter-center'
                        });
                        return;
                    });
                }
                $modalInstance.close();
            }

            $route.reload();
        }];

        $scope.imageBox = function(){
            $scope.imageTitle = "";
            $scope.imageDesc = "";
            $("#imgImage").attr("src","");
            // 弹出框之后的操作
            $('#imageBox').one('shown.bs.modal', function (e) {
                // 上传图片应小于800k，微信接口为1M
                $scope.uploadify("#uploadImage","#imgImage", '');
            })
            $("#imageBox").modal('show');
        }

        $scope.voiceBox = function(){
            $scope.voiceTitle = "";
            $scope.voiceDesc = "";
            $("#tips").text('');
            $scope.voiceIsDisabled = true;
            // 弹出框之后的操作
            $('#voiceBox').on('shown.bs.modal', function (e) {
                $("#uploadVoice").uploadify({
                    'width'         : 110,
                    'height'        : 30,
                    'queueID'       : 'some_file_queue',
                    'buttonClass'   : 'btn',
                    'swf'           : 'assets/lib/uploadify/uploadify.swf',
                    'uploader'      : 'app/index.php/mex/media/upload_wx_voice',
                    'auto'     : true,//关闭自动上传
                    'removeTimeout' : 0,//文件队列上传完成1秒后删除
                    'removeCompleted':true,
                    'method'   : 'post',//方法，服务端可以用$_POST数组获取数据
                    'buttonText' : '上传语音',//设置按钮文本
                    'fileTypeExts' : '*.mp3;*.amr;',//限制允许上传的图片后缀
                    'fileSizeLimit' : '30000KB',//限制上传的图片不得超过3M
                    'onUploadSuccess' : function(file, data, response){ //每次成功上传后执行的回调函数，从服务端返回数据到前端
                        var data = jQuery.parseJSON(data);
                        if(typeof data.code != 'undefined' && data.code == '200'){
                            $.gritter.add({
                                title: '上传语音成功!',
                                time:'1000',
                                class_name:'gritter-success'
                            });
                            $scope.voiceMediaId = data.data;
                            $("#tips").html("<font color='green'>上传成功</font>");
                            $scope.voiceIsDisabled = false;
                            $scope.getMediaList();
                        }else{
                            $.gritter.add({
                                title: '上传语音失败!',
                                time:'2000',
                                class_name:'gritter-error'
                            });
                            $("#tips").html("<font color='red'>上传失败</font>")
                        }
                    }
                });
            })
            $("#voiceBox").modal('show');
        }


        // 弹出框之后的操作
        $scope.shown = function (uploadStr, toImgStr) {
            // 弹出框之后的操作
            $('#alertBox').one('shown.bs.modal', function (e) {
                $scope.uploadify(uploadStr, toImgStr);
            })
            // 弹出框之前的操作
            $('#alertBox').one('show.bs.modal', function (e) {
                $(toImgStr).removeAttr("src");
                // 因为参数是公用的，所以用完清空
                $("input[name='imgname']").val("");
            })
        };

        // 确定上传图文信息
        $scope.newsCfm = function () {
            var imgname = $("input[name='imgname']").val();
            var author = $("input[name='author']").val();
            var title = $("input[name='title']").val();
            var content_source_url = $("input[name='content_source_url']").val();

            if($scope.ue1){
                var content = $scope.ue1.getContent();
            }else{
                var content = $("#myEditor").html();
            }
            var digest = $("textarea[name='digest']").val();
            var tags = $scope.checkTagId.tags;
            if ($.isEmptyObject(tags)) {
                $.gritter.add({
                    title: '请选择标签!',
                    time: '500',
                    class_name: 'gritter-warning'
                });
                return;
            }
            if (imgname) {
                $http.post(
                    _c.appPath + 'mex/media/upload_wx_news',
                    {
                        imgname: imgname,
                        author: author,
                        title: title,
                        tags: tags,
                        content_source_url: content_source_url,
                        content: content,
                        digest: digest,
                        mediaid: $scope.mediaid
                    },
                    {
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        transformRequest: function (data) {
                            return $.param(data);
                        }
                    }
                ).success(function (res) {
                        if (res.code == '200') {
                            $.gritter.add({
                                title: '上传成功!',
                                time: '1000',
                                class_name: 'gritter-success'
                            });
                            $("#newsBox").modal('hide');
                            $scope.getMediaList();
                        } else {
                            $.gritter.add({
                                title: res.message,
                                time: '2000',
                                class_name: 'gritter-error'
                            });
                        }
                    })
            } else {
                $.gritter.add({
                    title: '请上传图片!',
                    time: '500',
                    class_name: 'gritter-warning'
                });
                return;
            }
        };

        // 确定上传图片信息
        $scope.imageCfm = function () {
            var imgname = $("input[name='imgname']").val();
            if (imgname) {
                $http.post(
                    _c.appPath + 'mex/media/upload_wx_image',
                    {
                        filename: imgname,
                        imageTitle: $scope.imageTitle,
                        imageDesc: $scope.imageDesc
                    },
                    {
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        transformRequest: function (data) {
                            return $.param(data);
                        }
                    }
                ).success(function (res) {
                        if (res.code == '200') {
                            $.gritter.add({
                                title: res.message,
                                time: '1000',
                                class_name: 'gritter-success'
                            });
                            $("#imageBox").modal('hide');
                            $scope.getMediaList();
                        } else {
                            $.gritter.add({
                                title: res.message,
                                time: '2000',
                                class_name: 'gritter-error'
                            });
                            $("#imageBox").modal('hide');
                        }
                    });
            } else {
                $.gritter.add({
                    title: '请上传图片!',
                    time: '2000',
                    class_name: 'gritter-warning'
                });
            }
        };

        // 确定上传语音信息
        $scope.voiceCfm = function(){
            if(!$scope.voiceTitle){
                $.gritter.add({
                    title: '请输入标题',
                    time:'500',
                    class_name:'gritter-error'
                });
                return;
            }
            if(!$scope.voiceDesc){
                $.gritter.add({
                    title: '请输入语音描述',
                    time:'500',
                    class_name:'gritter-error'
                });
                return;
            }
            if(parseInt($scope.voiceMediaId) <=0){
                $.gritter.add({
                    title: '请重新上传语音',
                    time:'500',
                    class_name:'gritter-error'
                });
                return;
            }

            $http.post(
                _c.appPath+'mex/media/post_voice',
                {
                    voiceTitle : $scope.voiceTitle,
                    voiceDesc : $scope.voiceDesc,
                    voiceMediaId   : $scope.voiceMediaId
                },
                {
                    headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest : function (data) {return $.param(data);}
                }
            ).success(function(res){
                    if(res.code == '200'){
                        $("#voiceBox").modal('hide');
                        $.gritter.add({
                            title: res.message,
                            time:'500',
                            class_name:'gritter-success'
                        });
                        $("#voiceBox").modal('hide');
                        $scope.getMediaList();
                    }else{
                        $.gritter.add({
                            title: res.message,
                            time:'1000',
                            class_name:'gritter-error'
                        });
                    }
                });

            $scope.voiceTitle = '';
            $scope.voiceDesc = '';
            $scope.mediaId = '';
        }

        // 上传图片
        $scope.uploadify = function(uploadStr,toImgStr, fileSizeLimit) {
            if (!fileSizeLimit) {
                var fileSizeLimit = '800';
            }
            $(uploadStr).uploadify({
                'width'         : 110,
                'height'        : 30,
                'queueID'       : 'some_file_queue',
                'buttonClass'   : 'btn',
                'swf'           : 'assets/lib/uploadify/uploadify.swf',
                'uploader'      : _c.appPath+'mex/media/upload_image_local',
                'auto'          : true,//关闭自动上传
                'removeTimeout' : 0,//文件队列上传完成1秒后删除
                'removeCompleted':true,
                'method'   : 'post',//方法，服务端可以用$_POST数组获取数据
                'buttonText' : '上传图片',//设置按钮文本
                'multi'    : false,//允许同时上传多张图片
                'uploadLimit' : 20,//一次最多只允许上传张图片
                'fileTypeDesc' : 'Image Files',//只允许上传图像
                'fileTypeExts' : '*.jpg;*.jpeg;',//限制允许上传的图片后缀
                'fileSizeLimit' : fileSizeLimit,//限制上传的图片不得超过800KB
                'formData':{
                    'size': fileSizeLimit
                },
                'onUploadSuccess' : function(file, data, response){ //每次成功上传后执行的回调函数，从服务端返回数据到前端
                    var data;
                    data = jQuery.parseJSON(data);
                    if(data.code == '200'){
                        $.gritter.add({
                            title: '上传图片成功!',
                            time:'500',
                            class_name:'gritter-success'
                        });
                        $(toImgStr).attr("src",'uploads/images/'+data['data']['filename']);
                        $("input[name='imgname']").val(data['data']['filename']);
                    }else{
                        $.gritter.add({
                            title: '上传图片失败!',
                            time:'1000',
                            class_name:'gritter-error'
                        });
                    }
                },
                'onUploadError' : function(file, errorCode, errorMsg, errorString) {
                }
            });
        }

        $scope.common = {
            tempTags: {}
        };
        $scope.checkTagId = {
            tags:{}
        };
        $scope.checkTagName = {
            tags:{}
        };
        // 为图文添加标签
        $scope.addLabel = function(){
            var resolve =  {
                tags: function () {
                    return $scope.tags
                },
                common: function () {
                    return $scope.common
                },
                checkTagId: function () {
                    return $scope.checkTagId
                },
                checkTagName: function () {
                    return $scope.checkTagName
                }
            };
            Tag.showTagModal($scope, tagModalInstanceCtrl, resolve,'xs');
        }

        // 标签选择弹框控制器
        var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'common','checkTagId', 'checkTagName', function ($scope, $modalInstance,tags,common,checkTagId,checkTagName) {
            $scope.common = common
            $scope.tags = tags;
            $scope.checkTagId = checkTagId;
            $scope.checkTagName = checkTagName;

            // 将选中的标签添加入待发的标签数组
            $scope.pushTag = function (tagIds, tagName) {
                var tagId = parseInt(tagIds);
                // 点击时checkbox的选中状态为点击前的状态
                if (!$scope.common.tempTags[tagId]) {
                    $scope.checkTagId.tags[tagId] = tagId;
                    $scope.checkTagName.tags = _c.arrayAddItem($scope.checkTagName.tags,tagName);
                } else {
                    delete $scope.checkTagId.tags[tagId];
                    $scope.checkTagName.tags = _c.arrayRemoveItem($scope.checkTagName.tags,tagName);
                }
            }


            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 确认选择标签，并发布H5页面
            $scope.ok = function () {
                $modalInstance.close();
            }
        }];

        // 删除单图文弹框
        $scope.showDeleteModal = function (mediaId) {
            var modalInstance = $modal.open({
                templateUrl: 'mediaDeleteModal',
                controller: mediaDeleteCtrl,
                size: 'xs',
                resolve: {
                    mediaId: function () {
                        return mediaId;
                    },
                    getMediaList: function () {
                        return $scope.getMediaList
                    },
                    type: function () {
                        return $scope.type
                    }
                }
            });
        }

        var mediaDeleteCtrl = ['$scope', '$modalInstance', 'mediaId', 'Media', 'getMediaList', 'type', function ($scope, $modalInstance,mediaId, Media, getMediaList, type) {
            $scope.getMediaList = getMediaList;
            $scope.type = type;
            $scope.ok = function () {
                Media.resource.delete({
                    'id': mediaId
                }, function (data) {
                    if (data.code == 200) {
                        $.gritter.add({
                            title: '删除成功!',
                            time:'500',
                            class_name:'gritter-success'
                        });
                        $scope.getMediaList();
                        $modalInstance.close();
                    }
                    else
                    {
                        $.gritter.add({
                            title: data.message,
                            time:'500',
                            class_name:'gritter-danger'
                        });
                    }
                });
            }
            $scope.cancel = function () {
                $modalInstance.close();
            }
        }];

        $scope.chk_script = function(){
            var status = false;
            $("body script").each(function(){
                if(typeof $(this).attr('src') != 'undefined'){
                    if($(this).attr('src').indexOf('umeditor.min.js') != -1){
                        status = true;
                    }
                }
            })
            return status;
        }

        $scope.media_search = function(){
            $scope.getMediaList();
        }

    }]);
});