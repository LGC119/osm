'use strict';

define(['me'], function (me) {
    me.controller('RuleController', ['$scope', '$sce', '$http', '$route', '$modal', 'Media', 'Rule', 'Tag', function ($scope, $sce, $http, $route, $modal, Media, Rule, Tag) {
        // 初始化
        $scope.params = {};
        $scope.keywordEdit = true;
        $scope.oldKeyword = '';

        // 搜索条件
        $scope.search = {};
        $scope.search.tag = [];
        $scope.search.status = '';
        $scope.search.title = '';

        // 获取规则列表
        $scope.getRulesList = function () {
            $scope.params.page = $scope.params.page || 1;
            $scope.params.perpage = $scope.params.perpage || 8;
            Rule.wxres.selectRules($scope.params,function(data) {
                for (var i in data.data) {
                    data.data[i]['isCollapsed'] = true;
                }
                $scope.rules = data;
                $scope.params.page = data.data.page.page;
                $scope.params.perpage = data.data.page.perpage;
                $scope.params.sum = data.data.page.sum;
                for(var j in $scope.rules.data){
                    if( j == 'page'){
                        // 删除掉最后一个不是真正的数据
                        delete $scope.rules.data[j];
                    }
                }
            });
        };

        $scope.getRulesList();

        // 获取标签列表
        Tag.resource.query({},function(data) {
            $scope.search.tags = data;
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

        $scope.rule = { };
        $scope.newData = { }
        $scope.text = { textBox:false }

        $scope.common = { tempTags: {} };
        $scope.checkTagId = { tags:{} };
        $scope.checkTagName = { tags:{} };

        // 点击下拉时的操作
        $scope.isDown = function(rule){
            if (!rule) {
                // 新建规则清除内存中的rule
                    $scope.rule = {};
                $scope.common.tempTags = {}; 
                $scope.checkTagId.tags = {};
                $scope.checkTagName.tags = {};
                $scope.showNewRule = !$scope.showNewRule;
                return false;
            }
            $scope.showNewRule = false;
            rule['isCollapsed'] = !rule['isCollapsed'];
            $scope.rule = rule;
            // 标签默认属性
            var sObj = '';
            if($scope.rule.tags){
               for(var k in $scope.rule.tags){
                   sObj +='"'+$scope.rule.tags[k]['id']+'":'+'true,';
               }
            }
            sObj = sObj.substr(0,sObj.length-1);
            sObj = '{'+sObj+'}';
            sObj = $.parseJSON(sObj);

            // 标签id
            var sObjId = '';
            if($scope.rule.tags){
                for(var f in $scope.rule.tags){
                    sObjId +='"'+$scope.rule.tags[f]['id']+'":"'+$scope.rule.tags[f]['id']+'",';
                }
            }
            sObjId = sObjId.substr(0,sObjId.length-1);
            sObjId = '{'+sObjId+'}';
            sObjId = $.parseJSON(sObjId);
            // 标签名称
            var sObjName = '';
            if($scope.rule.tags){
                for(var g in $scope.rule.tags){
                    sObjName +='"'+$scope.rule.tags[g]['name']+'",';
                }
            }
            sObjName = sObjName.substr(0,sObjName.length-1);
            sObjName = '['+sObjName+']';
            sObjName = $.parseJSON(sObjName);
            $scope.common.tempTags = sObj;
            $scope.checkTagId.tags = sObjId;
            $scope.checkTagName.tags = sObjName;
        }


        // 修改已存在规则的时候所用到的规则对象，格式为：
        // 一级是key是ruleid的对象，对象内是要修改的mediaid数组，规则名name，关键词数组keyword，内容字符串content（mediaid存在时才可能会出现）
        // 如果是新建规则，则一级key是字符串create

        // 素材回复选择弹框
        $scope.showMediaModal = function (type, ruleId) {
            if(type == 'text'){
                $scope.text = {
                    textBox:''
                }
            }
            // 点击选择素材时
            var resolve = {
                rule: function () {
                    return $scope.rule;
                },
                ruleId: function () {
                    return ruleId;
                },
                common: function () {
                    return $scope.common;
                },
                newData: function () {
                    return $scope.newData;
                },
                text: function () {
                    return $scope.text;
                },
                search:function(){
                    return $scope.search;
                }
            }
            var status = true;
            if(type == 'image' || type == 'voice'){
                status = false;
            }
            Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve, type,status);
        }

        var mediaModalInstanceCtrl = ['$scope', '$modalInstance','get_list', 'rule','ruleId','common','newData','text', 'mediaData', 'type', 'itemSelect','search', function ($scope, $modalInstance, get_list,rule,ruleId ,common,newData,text, mediaData, type, itemSelect,search) {
            $scope.search = search;
            $scope.mediaData = mediaData;
            $scope.rule = rule;
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
            if(typeof $scope.rule['media']=='undefined'){
                $scope.rule['media'] = {
                    news:[],
                    text:[],
                    image:[],
                    voice:[],
                    articles:[]
                };
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
            $scope.text = text;
            $scope.common = common;

            $scope.showEmotions = function () {
                // 表情
                $('.icon-emotions').emotion({'textarea': 'text-content'});
                $('.icon-emotions').trigger('click');
            }

            // 点击选择素材的方法
            $scope.itemSelect = itemSelect;
            $scope.type = type;
            // 把通用对象中的已选择素材存入规则对象中

            $scope.cancel = function () {
                $modalInstance.close();
            };

            $scope.ok = function () {
                if(type == 'text'){
                    if($scope.text.textBox.length > 600){
                        $.gritter.add({
                            title: '长度不可超过600个字!',
                            time:'500',
                            class_name:'gritter-error gritter-center'
                        });
                        $modalInstance.close();
                        return;
                    }else{
                        $scope.rule['media']['text'].push({content:$scope.text.textBox,created_at:new Date(),wx_media_id:'time'+new Date().getTime()});
                    }

                }
                if(type == 'news' && (typeof $scope.common.selectedMediaId['news'] != 'undefined')){
                    if($scope.common.selectedMediaId['news'].length >6){
                        $.gritter.add({
                            title: '图文最多选6个!',
                            time:'800',
                            class_name:'gritter-error gritter-center'
                        });
                        return;
                    }

                }

                for(var j in $scope.common.selectedMedia[type]){
                    if(typeof $scope.rule['media'][type] == 'undefined'){
                        $scope.rule['media'][type] = [];
                    }
                    $scope.rule['media'][type].push($scope.common.selectedMedia[type][j]);
                }
                $modalInstance.close();
            }

        }];

        
        // 为规则添加标签
        $scope.showTagModal = function(){
            var resolve =  {
                tags: function () {
                    return $scope.tags
                },
                rule:function(){
                    return $scope.rule
                },
                common: function () {
                    return $scope.common
                },
                checkTagId: function () {
                    return $scope.checkTagId;
                },
                checkTagName: function () {
                    return $scope.checkTagName
                }
            };
            Tag.showTagModal($scope, tagModalInstanceCtrl, resolve);
        }

        // 标签选择弹框控制器
        var tagModalInstanceCtrl = ['$scope', '$modalInstance', 'tags', 'common','checkTagId', 'checkTagName','rule', function ($scope, $modalInstance,tags,common,checkTagId,checkTagName,rule) {
            $scope.common = common
            $scope.tags = tags;
            $scope.rule = rule;
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
                _syncRuleTags();
            }


            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 确认选择标签，并发布H5页面
            $scope.ok = function () {
                $modalInstance.close();
            }
        }];

        // 点击标签的X按钮，删除已选择的标签
        $scope.removeSelectedTag = function (tag) {
            if ($scope.common.checkTagName) {
                _c.arrayRemoveItem($scope.common.checkTagName, tag.tagName);
                delete($scope.common.checkTagId[tag.id]);
                _syncRuleTags();
            } else {
                _c.arrayRemoveItem($scope.rule.tags, tag);
            }
            delete($scope.common.tempTags[tag.id]);
            // console.dir($scope.rule);
        }

        // 同步规则标签
        var _syncRuleTags = function () {
            var tags = [];
            for(var l in $scope.checkTagName.tags){
                tags.push({name:$scope.checkTagName.tags[l]});
            }
            $scope.rule.tags = tags;
        }


        // 添加关键词
        $scope.addKeyword = function(s,rule){
            var sS = s ? s : '';
            if(!sS){
                $.gritter.add({
                    title: '关键词不可为空!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            var bUnque = true;
            if(typeof $scope.rules!= 'undefined' && typeof $scope.rules.data != 'undefined' ){
                angular.forEach($scope.rules.data,function(v,k){
                    angular.forEach(v.keywords,function(v1,k1){
                        if(v1.name == sS){
                            bUnque = false;
                        }
                    })
                });
            }
            if(rule.keywords != undefined){
                angular.forEach(rule.keywords,function(v,k){
                    if(sS == v['name']){
                        bUnque = false;
                    }
                });
            }

            if(!bUnque){
                $.gritter.add({
                    title: '关键词已经存在!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }

            var idV = new Date().getTime();
            if(rule.keywords){
                rule.keywords.push({id:'time'+idV,name:sS});
            }else{
                rule.keywords = [];
                rule.keywords.push({id:'time'+idV,name:sS});
            }
        }

        // 修改时点击确定的时候验证关键词【添加与修改二部份】
        $scope.check_keywords = function(keyword,rule){
            var sS = keyword.name ? keyword.name : '';
            if(!sS){
                $.gritter.add({
                    title: '关键词不可为空!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            var bUnque = 0;
            if(rule.keywords != undefined){
                angular.forEach(rule.keywords,function(v,k){
                    if(sS == v['name']){
                        bUnque ++;
                    }
                });
            }

            if(typeof $scope.rules!= 'undefined' && typeof $scope.rules.data!= 'undefined')
            angular.forEach($scope.rules.data,function(v,k){
                angular.forEach(v.keywords,function(v1,k1){
                    if(rule.ruleid > 0){
                        if(v1.name == sS && v['ruleid']!=rule.ruleid){
                            bUnque +=2;
                        }
                    }else{
                        if(v1.name == sS){
                            bUnque +=2;
                        }
                    }
                })
            });

            if(bUnque >= 2){
                $.gritter.add({
                    title: '关键词已经存在!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                keyword.name = $scope.oldKeyword;
                $scope.oldKeyword = '';
                return;
            }
        }
        // 修改时 取消
        $scope.check_cancel = function(keyword){
            keyword.name = $scope.oldKeyword;
            $scope.oldKeyword = '';
        }

        // 点击编辑时，先复制一份关键词，为还原准备
        $scope.check_edit = function(oldKeyword){
            $scope.oldKeyword = oldKeyword;
        }

        // 删除关键词
        $scope.deleteKeyword = function(id){
            for(var i in $scope.rule.keywords){
                if($scope.rule.keywords[i]['id'] == id){
                    $scope.rule.keywords.splice(i,1);
                }
            }
        }

        // 删除素材
        $scope.deleteMedia = function(type,wx_media_id){
            if(type == 'articles'){
                for(var i in $scope.rule.media[type]){
                    if($scope.rule.media[type][i]['mediaid'] == wx_media_id){
                        $scope.rule.media[type].splice(i,1);
                    }
                }
            }else{
                for(var i in $scope.rule.media[type]){
                    if($scope.rule.media[type][i]['wx_media_id'] == wx_media_id){
                        $scope.rule.media[type].splice(i,1);
                    }
                }
            }
        }

        //保存规则 【修改】
        $scope.save_rule = function(rule){
            if(rule.rulename == undefined || !rule.rulename){
                $.gritter.add({
                    title: '规则名不可以为空!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            var bUnque = true;
            if(typeof $scope.rules!= 'undefined' && typeof $scope.rules.data!= 'undefined'){
                angular.forEach($scope.rules.data,function(v,k){
                    if(v['rulename'] == rule.rulename && v['ruleid'] != rule.ruleid){
                        bUnque = false;
                    }
                });
            }
            if(!bUnque){
                $.gritter.add({
                    title: '规则名已经存在!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            if($.isEmptyObject($scope.checkTagId.tags)){
                $.gritter.add({
                    title: '请添加标签!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            }
            rule.tag = $scope.checkTagId.tags;
            rule = angular.copy(rule);
            $http.post(
                _c.appPath+'mex/rule/update_rule',rule
            ).success(function(data){
                    if(data.code == '200'){
                        $scope.getRulesList();
                        $.gritter.add({
                            title: '保存成功!',
                            time:'500',
                            class_name:'gritter-success gritter-center'
                        });
                    }else{
                        $.gritter.add({
                            title: '保存失败!',
                            time:'1000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
                }).error(function(){

                });
        }

        // 创建规则
        $scope.create_rule = function(rule){
            if(rule.rulename == undefined || !rule.rulename){
                $.gritter.add({
                    title: '规则名不可以为空!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            var bUnque = true;
            if(typeof $scope.rules!= 'undefined' && typeof $scope.rules.data != 'undefined'){
                angular.forEach($scope.rules.data,function(v,k){
                    if(v['rulename'] == rule.rulename){
                        bUnque = false;
                    }
                });
            }
            if(!bUnque){
                $.gritter.add({
                    title: '规则名已经存在!',
                    time:'500',
                    class_name:'gritter-error gritter-center'
                });
                return;
            }
            if($.isEmptyObject(rule.tags)){
                $.gritter.add({
                    title: '请添加标签!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            }
            rule.tag = $scope.checkTagId.tags;
            rule = angular.copy(rule);
            $http.post(
                _c.appPath+'mex/rule/create_rule',rule
            ).success(function(data){

                if(data.code == '200'){
                    $.gritter.add({
                        title: '保存成功!',
                        time:'500',
                        class_name:'gritter-success gritter-center'
                    });
                    $route.reload();
                }else{
                    $.gritter.add({
                        title: '保存失败!',
                        time:'1000',
                        class_name:'gritter-error gritter-center'
                    });
                }
            })
        }

        // 删除规则
        $scope.delete_rule = function(rule_id){
            var modalInstance = $modal.open({
                templateUrl: 'delete-rule-modal',
                controller: delModalInstanceCtrl,
                size: 'sm',
                resolve: {
                    rule_id: function () {
                        return rule_id;
                    }
                }
            });
        }

        var delModalInstanceCtrl = ['$scope', '$modalInstance', 'rule_id', function ($scope, $modalInstance, rule_id) {
            $scope.rule_ok = function () {
                $modalInstance.close();
                $http.post(
                    _c.appPath+'mex/rule/delete_rule',
                    { id:rule_id }
                ).success(function(data){
                        if(data.code == '200'){
                            $route.reload();
                            $.gritter.add({
                                title: '删除成功!',
                                time:'500',
                                class_name:'gritter-success gritter-center'
                            });
                            $route.reload();
                        }else{
                            $.gritter.add({
                                title: '删除失败!',
                                time:'1000',
                                class_name:'gritter-error gritter-center'
                            });
                        }
                    })
            };
            $scope.rule_cancel = function () {
                $modalInstance.close();
            };
        }];

        $scope.getObjLength = function(obj){
            var n=0;
            for(var i in obj){
                n++;
            }
            return n;
        }

    }]);
});

