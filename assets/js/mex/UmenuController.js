'use strict';

define(['me'], function (me) {
    me.controller('UmenuController', ['$scope', '$sce','Umenu','$modal','$http','Media','Tag',function ($scope, $sce,Umenu,$modal,$http,Media,Tag) {

        // 搜索条件
        $scope.search = {};
        $scope.search.tag = [];
        $scope.search.status = '';
        $scope.search.title = '';

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



        $scope.select_umenu = function(){
            Umenu.select_umenu(function(res){
//                console.log(res)
                if(res.code == 200){
                    if(res.data){
                        var data = $.parseJSON(res.data);
                        // 接好全的菜单
                        var dataLen = data.menu.button.length;
                    }else{
                        var data = {};
                        data.menu = {};
                        data.menu.button = [];
                    }

//                    for(var i in data.menu.button){
//                        var jNum = parseInt(data.menu.button[i].sub_button.length);
//                        for(var n=jNum+1;n<=5;n++){
//                            var tempObj = {
//                                key:"menu"+(parseInt(i)+1)+"_"+n,
//                                name:'',
//                                type:'click'
//                            };
//                            data.menu.button[i].sub_button.push(tempObj);
//                        }
//                    }
                    for(var i=0;i<3;i++){
                        if(typeof data.menu.button[i] == 'undefined'){
                            data.menu.button[i] = {};
                            data.menu.button[i].type="click";
                            data.menu.button[i].key="menu"+i+1;
                            data.menu.button[i].name="";
                        }
                        if(typeof data.menu.button[i]['sub_button'] == 'undefined'){
                            data.menu.button[i]['sub_button'] = [];
                        }
                        var jNum = parseInt(data.menu.button[i].sub_button.length);
//                        console.log(jNum);
                        for(var n=jNum+1;n<=5;n++){
                            var tempObj = {
                                key:"menu"+(parseInt(i)+1)+"_"+n,
                                name:'',
                                type:'click'
                            };
                            data.menu.button[i].sub_button.push(tempObj);
                        }
                    }


                    $scope.menu = data.menu;
//                    console.log($scope.menu);
                    $scope.menuKey = data.menuKey;
                }
            });
            Umenu.select_umenu_data(function(res){
                if(res.code == 200){
                    $scope.menuData = res.data;
                }
            });
        }
        $scope.select_umenu();
        $scope.isDisabled = true;
        $scope.editText = '编辑';
        // 跳转与点击select时
        $scope.change = function(changeStatus,thisObj,menuKey){
            // 如果类型为view
            if(changeStatus == 'view'){
                $scope.showModal(thisObj);
                delete thisObj.key;
            }else{
                thisObj.key = menuKey;
                delete thisObj.url;
            }
        }
        // 选择跳转时弹出框框
        $scope.showModal = function(thisObj){
            $scope.boxValue = {
                editValue:''
            }
            var modalInstance = $modal.open({
                templateUrl: 'editModal.html',
                controller: ModalInstanceCtrl,
                size: 'sm',
                resolve: {
                    thisObj:function(){
                        return thisObj;
                    },
                    boxValue:function(){
                        return $scope.boxValue;
                    }
                }
            });
        };
        var ModalInstanceCtrl = function ($scope, $modalInstance,boxValue,thisObj) {
            $scope.thisObj = thisObj;
            $scope.boxValue = boxValue;
            $scope.ok = function(){
                if($scope.boxValue.editValue.search('http') == -1){
                    $scope.boxValue.editValue = "http://"+$scope.boxValue.editValue;
                }
                $scope.thisObj.url = $scope.boxValue.editValue;
                $modalInstance.dismiss('cancel');
            }
            $scope.cancel = function(){
                $modalInstance.close();
            }
        }


        // 点击编辑时，出现可编辑状态
        $scope.edit = function(){
            if($scope.editText == '编辑'){
                $scope.editText = '取消';
                $scope.isDisabled = false;
            }else{
                $scope.editText = '编辑';
                $scope.isDisabled = true;
            }
        }

        // 创建菜单保存
        $scope.save = function(){

            $scope.editText = '编辑';
            $scope.isDisabled = true
            $http.post(
                _c.appPath+'mex/umenu/create_umenu',
                {
                    menu:$scope.menu
                },
                {
                    headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest : function (data) {return $.param(data);}
                }
            ).success(function(data){
                    if(data.code == '200'){
                        $.gritter.add({
                            title: data.message,
                            time:'500',
                            class_name:'gritter-success gritter-center'
                        });
                    }else{
                        $.gritter.add({
                            title: data.message,
                            time:'1000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
                    // 创建成功重新刷新
                    $scope.select_umenu();
                }).error(function(){

                });

        }

        /**
         * =============================================
         * 菜单内容的操作
         * =============================================
         */
            // 素材回复选择弹框
        $scope.showMediaModal = function (type,menuKey) {
            if(type == 'text'){
                $scope.text = {
                    textBox:''
                }
            }
            $scope.menuModal = {
                menuKey:menuKey,
                menuData:$scope.menuData
            };
            $scope.common = {};
            // 点击选择素材时
            var resolve = {
                common: function () {
                    return $scope.common;
                },
    //            newData: function () {
    //                return $scope.newData;
    //            },
                menuModal: function(){
                  return $scope.menuModal;
                },
                text: function () {
                    return $scope.text;
                },
                search:function(){
                    return $scope.search;
                }
            }
            Media.showMediaModal($scope, mediaModalInstanceCtrl, resolve, type);
        }
        var mediaModalInstanceCtrl = ['$scope', '$modalInstance','get_list','mediaData','common', 'type','itemSelect', 'text','menuModal','search',function ($scope, $modalInstance,get_list,mediaData,common,type,itemSelect,text,menuModal,search) {
            $scope.search = search;
            $scope.mediaData = mediaData;
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
//                console.log($scope.mediaData)
            }
            // 搜索
            $scope.media_search = function(){
                $scope.params = {};
                $scope.params.type = type;
//                $scope.params.page = pageNum;
                if(type=='news'){
                    $scope.params.tag = $scope.search.status;
                }
                $scope.params.title = $scope.search.title;
                $scope.mediaData = $scope.get_list($scope.params)
            }
            $scope.itemSelect = itemSelect;
            $scope.common = common;
            $scope.text = text;
            $scope.menuModal = menuModal;
            $scope.type = type;
            $scope.cancel = function () {
                $modalInstance.close();
            };

            // 确认选择
            $scope.ok = function () {
//                console.log($scope.menuModal['menuData'])
                if($scope.type == 'text'){
                    if(!$scope.menuModal['menuData'][$scope.menuModal['menuKey']]){
                        $scope.menuModal['menuData'][$scope.menuModal['menuKey']] = {};
                    }
                    if(typeof $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type] == 'undefined' ){
                        $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type] = [];
                    }
                    $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type].push({content:$scope.text.textBox,created_at:new Date(),wx_media_id:'time'+new Date().getTime()});
                }
                for(var j in $scope.common.selectedMedia[$scope.type]){
                    if(!$scope.menuModal['menuData'][$scope.menuModal['menuKey']]){
                        $scope.menuModal['menuData'][$scope.menuModal['menuKey']] = {};
                    }
                    if(typeof $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type] == 'undefined'){
                        $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type] = [];
                    }
                    $scope.menuModal['menuData'][$scope.menuModal['menuKey']][$scope.type].push($scope.common.selectedMedia[$scope.type][j])
                }
//                console.log($scope.menuModal['menuData'])
                $modalInstance.close();
            }

        }];

        // 删除素材
        $scope.delete = function(type,mediaid,menuKey){
            for(var i in $scope.menuData[menuKey][type]){
                if($scope.menuData[menuKey][type][i]['mediaid'] == mediaid){
                    $scope.menuData[menuKey][type].splice(i,1);
                }
            }
        }

        // 删除规则
        $scope.delete_rule = function(menuKey){
            $http.post(
                _c.appPath+'mex/umenu/delete_rule',
                {
                    menuKey:menuKey
                },
                {
                    headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest : function (data) {return $.param(data);}
                }
            ).success(function(data){
//                    console.log(data);
//                    return;
                    if(data.code == '200'){
                        $.gritter.add({
                            title: data.message,
                            time:'500',
                            class_name:'gritter-success gritter-center'
                        });
                        // 删除成功  重新刷新
                        Umenu.select_umenu_data(function(res){
                            if(res.code == 200){
                                $scope.menuData = res.data;
                            }
                        });
                    }else{
                        $.gritter.add({
                            title: data.message,
                            time:'1000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
                }).error(function(){

                });
        }

        // 菜单事件保存
        $scope.send_save = function(menuKey){
            var obj = $scope.menuData[menuKey];
            var menu_name = $scope.menu.button;

            var l_1 = menuKey.substring(4,5) - 1;
            var l_2 = menuKey.substring(6,7) - 1;
            var name = menu_name[l_1]['sub_button'][l_2]['name'];

            var newObj = {};
            for(var k in obj){
                newObj[k] = typeof newObj[k] == 'undefined' ? [] : newObj[k];
                for(var l=0;l<obj[k].length;l++){
                    newObj[k][l] = typeof newObj[k][l] == 'undefined' ? [] : newObj[k][l];
                    if(k == 'text'){
                        newObj[k][l].push(obj[k][l]['content']);
                    }else{
                        newObj[k][l].push(obj[k][l]['mediaid']);
                    }
                }
            }

            $http.post(
                _c.appPath+'mex/umenu/save_rule',
                {
                    obj:newObj,
                    menuKey:menuKey,
                    menu_name:name
                },
                {
                    headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest : function (data) {return $.param(data);}
                }
            ).success(function(data){
//                    console.log(data);
//                    return;
                    if(data.code == '200'){
                        $.gritter.add({
                            title: data.message,
                            time:'500',
                            class_name:'gritter-success gritter-center'
                        });
                    }else{
                        $.gritter.add({
                            title: data.message,
                            time:'1000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
                }).error(function(){

                });
        }

    }]);
});

