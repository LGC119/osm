'use strict';

define(['me'], function (me) {
    me.controller('WeixinEventController', ['$scope', '$sce', '$http', 'Event', function ($scope, $sce, $http, Event) {
        // 获取用户组
        $scope.groups = {};
        $scope.group = {};
        $scope.empty = '载入中...';
        $http.get(
            _c.appPath + 'meo/wb_group/get_list'
        ).success(function(res){
            if (res.code == 200) {
                if (res.data.length > 0) {
                    for (var i in res.data) {
                        $scope.groups[res.data[i]['id']] = res.data[i];
                    }
                } else {
                    $scope.empty = '没有设定任何用户组！';
                }
            } else {
                $scope.empty = res.message;
            }
        }).error(function(res){
            $scope.empty = '无法获取用户数据！';
        });

        // 暂时先用jquery方法
        $('#event-wizard')
        .ace_wizard({
          //step: 2 //optional argument. wizard will jump to step "2" at first
        })
        .on('change' , function(e, info) {
            //info.step
            //info.direction
        })
        .on('finished', function(e) {
           //do something when finish button is clicked
        }).on('stepclick', function(e) {
            //e.preventDefault();//this will prevent clicking and selecting steps
           
        });

        /*// chosen插件在初始化时如果select为隐藏，则width会变成0，通过以下方式强行设置width
        $('.chosen-select').chosen({allow_single_deselect:true});
        $('.chosen-container').each(function(){
            $(this).css('width', '210px');
            $(this).find('.chosen-choices').css({
                'width': '210px',
                'padding': '6px 4px'
            });
            $(this).find('a:first-child').css('width' , '210px');
            $(this).find('.chosen-drop').css('width' , '210px');
            $(this).find('.chosen-search input').css('width' , '200px');
        });*/
    }]);
});