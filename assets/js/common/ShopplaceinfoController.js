'use strict';

define(['me'], function (me) {
    me.controller('ShopplaceinfoController', ['$scope', '$http', '$sce', '$location','$route', 'ShopplaceService',function($scope, $http, $sce, $location,$route, ShopplaceService) {

        $scope.get_shopplaceinfo = function(){
            $http.get(
                 _c.appPath+"common/shopplace/get_shopplace_by_id",
            ).success(function(res){
                 if(res.code == '200'){
                     res.data[0]['location_x'] = parseFloat(res.data[0]['longitude_latitude'].substring(0,index));
                     res.data[0]['location_y'] = parseFloat(res.data[0]['longitude_latitude'].substr(index+1));
                    $scope.shop = res.data[0];
                    }
            });
        }

        $scope.get_shopplaceinfo();
    }]);
});

