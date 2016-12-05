'use strict';

define(['me'], function (me) {
    me.controller('ShopplaceController', ['$scope', '$http', '$sce', '$location','$route', 'ShopplaceService',function($scope, $http, $sce, $location,$route, ShopplaceService) {
        $scope.shopplaceData = {};
        $scope.shopplaceData.data = {};
        //过滤条件
        $scope.filterData = {};
        // 存一些临时变量
        $scope.post = {
            country:''
        };
        //存一个店铺的信息
        $scope.shop = {};
        /* 基础筛选参数 */
        $scope.filters = {
            'province': _c.get_city
        };
        //获取店铺位置列表
        $scope.get_shopplace_data = function () {
            $scope.shopplaceData = ShopplaceService.get_list({
                current_page: $scope.shopplaceData.data.current_page || 1,
                items_per_page: 12,
                name: $scope.filterData.name || ''
            });
        }
        $scope.get_shopplace_data();
        //添加店铺弹窗
        $scope.createBox = function(){
            $scope.post = {};
            $('#createBox').modal('show');
        }
        //删除店铺弹窗
        $scope.deleteBox = function(id){
            $("#deleteBox").modal('show');
            $scope.shop_id = id;
        }

        //暂停店铺弹窗
        $scope.stopBox = function(id){
            $("#stopBox").modal('show');
            $scope.shop_id = id;
        }

        //编辑店铺弹窗
        $scope.updateBox = function(id){
            $scope.shop_id = id;
            $http.post(
                _c.appPath+"common/shopplace/get_shopplace_by_id",
                {id:$scope.shop_id}
            ).success(function(res){
                 if(res.code == '200'){
                    res.data[0]['display_tel'] = parseInt(res.data[0]['display_tel']);
                    var index = res.data[0]['longitude_latitude'].indexOf(',');
                     res.data[0]['location_x'] = parseFloat(res.data[0]['longitude_latitude'].substring(0,index));
                     res.data[0]['location_y'] = parseFloat(res.data[0]['longitude_latitude'].substr(index+1));
                    $scope.shop = res.data[0];
                    $("#updateBox").modal('show');

                    }else{
                        $.gritter.add({
                            title: '数据不存在',
                            time:'2000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
            });
        }

        //更新店铺信息方法
        $scope.updateCfm = function(){
            var verified = verify_shop_info(2);
             if (typeof verified == 'string') 
            {
                $.gritter.add({
                    title: verified, 
                    time:'2000', 
                    message: verified || '店铺信息填写不正确！', 
                    class_name:'gritter-error gritter-center'
                })
                return false;
            }
            if ($scope.shop_id){
                $scope.shop['id'] = $scope.shop_id;
                 $http.post(
                     _c.appPath+"common/shopplace/update",
                    $scope.shop
                ).success(function(res){
                    if(res.code == '200'){
                            $.gritter.add({
                                title: '更新成功!',
                                time:'1000',
                                class_name:'gritter-success gritter-center'
                            });
                            $scope.get_shopplace_data();
                        }else{
                            $.gritter.add({
                                title: '更新失败!',
                                time:'2000',
                                class_name:'gritter-error gritter-center'
                            });
                        }
                        $("#updateBox").modal('hide');
                });   
            }
            
        }

        //添加店铺方法
        $scope.createCfm = function(){
            var verified = verify_shop_info(1);
            if (typeof verified == 'string') 
            {
                $.gritter.add({
                    title: verified, 
                    time:'2000', 
                    message: verified || '店铺信息填写不正确！', 
                    class_name:'gritter-error gritter-center'
                })
                return false;
            }
            /* 创建店铺 */
            $http.post(
                _c.appPath+"common/shopplace/create",
                $scope.post
            ).success(function(res){
                    if(res.code == '200'){
                        $.gritter.add({
                            title: '创建成功!',
                            time:'1000',
                            class_name:'gritter-success gritter-center'
                        });
                        $scope.get_shopplace_data();
                        $("#createBox").modal('hide');
                    }else{
                        $.gritter.add({
                            title: '创建失败!',
                            time:'2000',
                            class_name:'gritter-error gritter-center'
                        });
                    }
                })
        }

        /* 添加店铺验证 */
        var verify_shop_info = function(num) 
        {
            if(1==num){
                 if (typeof $scope.post.name == 'undefined' || $scope.post.name.trim() == '') 
                    return '请填写店铺名称！';

                if (typeof $scope.post.country == 'undefined' || $scope.post.country.trim() == '') 
                    return '请选择所在地区！';

                if (typeof $scope.post.detail == 'undefined' || $scope.post.detail.trim() == '') 
                    return '请填写详细地址！';

                if (typeof $scope.post.telephone != 'undefined' && 
                    ! /^[\s]*$/.test($scope.post.telephone) && 
                    ! $scope.isTel($scope.post.telephone)) 
                    return '电话号码格式不正确！';

                 if((typeof $scope.post.location_y) == 'number' ){
                    if(typeof $scope.post.location_x != 'number' ){
                        return '经度和纬度必须同时填写';
                    }
                }

                if((typeof $scope.post.location_x) == 'number' ){
                    if(typeof $scope.post.location_y != 'number' ){
                        return '经度和纬度必须同时填写';
                    }
                }
                    
                if($scope.post.location_y != '' && $scope.post.location_x!= ''){
                    if($scope.post.location_x >90 || $scope.post.location_x<-90){
                        return '纬度必须在-90度到90度之间';
                    }
                    if($scope.post.location_y>180 || $scope.post.location_y <-180){
                        return '经度必须在-180度到180度之间';
                    }
                }
            }

            if(2==num){
                 if (typeof $scope.shop.display_name == 'undefined' || $scope.shop.display_name.trim() == '') 
                    return '请填写店铺名称！';

                if (typeof $scope.shop.display_address == 'undefined' || $scope.shop.display_address.trim() == '') 
                    return '请填写详细地址！';

                if (typeof $scope.post.telephone != 'undefined' && 
                    ! /^[\s]*$/.test($scope.post.telephone) && 
                    ! $scope.isTel($scope.post.telephone)) 
                    return '电话号码格式不正确！';

                if((typeof $scope.shop.location_y) == 'number' ){
                    if(typeof $scope.shop.location_x != 'number' ){
                        return '经度和纬度必须同时填写';
                    }
                }

                if((typeof $scope.shop.location_x) == 'number' ){
                    if(typeof $scope.shop.location_y != 'number' ){
                        return '经度和纬度必须同时填写';
                    }
                }

                if($scope.shop.location_x!= ''){
                    if($scope.shop.location_x >90 || $scope.shop.location_x<-90){
                        return '纬度必须在-90度到90度之间';
                    }
                    if($scope.shop.location_y>180 || $scope.shop.location_y <-180){
                        return '经度必须在-180度到180度之间';
                    }
                }
            }
           

            return true;
        }

        //验证电话
        $scope.isTel = function(number){
            // //座机
            // var patrn_1=new RegExp('/^1[3|5|7|8|][0-9]{9}$/');
            // //手机
            // var patrn_2=new RegExp('/^[+]{0,1}(\d){1,3}[ ]?([-]?((\d)|[ ]){1,12})+$/');
            // if (!patrn_1.test(number) && !patrn_2.test(number)) return false
            // return true
            var patrn =/(^(\d{3,4}-)?\d{7,8})$|(1[35768][0-9]{9})/;
            if(!patrn.exec(number)) return false;
            return true;
        }

        //删除确认店铺
        $scope.deleteCfm = function () {  
            if ($scope.shop_id) {
                $http.post(
                    _c.appPath+'common/shopplace/delete',{
                        id:$scope.shop_id
                    },
                    {
                        headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                        transformRequest : function (data) {return $.param(data);}
                    }
                ).success(function(res){
                    if(res.code == '200'){
                            $.gritter.add({
                                title: '删除成功!',
                                time:'1000',
                                class_name:'gritter-success gritter-center'
                            });
                            $("#deleteBox").modal('hide');
                            $scope.get_shopplace_data();
                        } else {
                            $.gritter.add({
                                title: '删除失败!',
                                time:'2000',
                                class_name:'gritter-error gritter-center'
                            });
                            $("#deleteBox").modal('hide');
                        }
                })
            }else{
                $.gritter.add({
                    title: '数据不存在!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            }
        }

        //暂停商铺
         $scope.stopCfm = function () {  
            if ($scope.shop_id) {
                $http.post(
                    _c.appPath+'common/shopplace/stop',{
                        id:$scope.shop_id
                    },
                    {
                        headers : { 'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'},
                        transformRequest : function (data) {return $.param(data);}
                    }
                ).success(function(res){
                    if(res.code == '200'){
                            $.gritter.add({
                                title: '暂停成功!',
                                time:'1000',
                                class_name:'gritter-success gritter-center'
                            });
                            $("#stopBox").modal('hide');
                            $scope.get_shopplace_data();
                        } else {
                            $.gritter.add({
                                title: '暂停失败!',
                                time:'2000',
                                class_name:'gritter-error gritter-center'
                            });
                            $("#stopBox").modal('hide');
                        }
                })
            }else{
                $.gritter.add({
                    title: '数据不存在!',
                    time:'500',
                    class_name:'gritter-warning gritter-center'
                });
                return;
            }
        }
        
    }]);
});

