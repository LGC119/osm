'use strict';

define(['me'], function (me) {
    me.controller('MsgOtherruleController', ['$scope','$http','Rule', function ($scope,$http,Rule) {
        (function(){
            Rule.wbres.select_other({
                metype: 'meo'
            },function(res){
                if(res.code == 200){
                    $scope.subscribedReply = res.data['subscribed_reply'];
                    $scope.noKeywordReply  = res.data['nokeyword_reply'];
                }
            });
        })();
        // 更新其他规则
        $scope.updateReply = function(){
            $http.post(
                _c.appPath+'meo/rule/update_other_rule',
                {
                    metype : 'meo',
                    subscribedReply     : $scope.subscribedReply,
                    noKeywordReply    : $scope.noKeywordReply
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
    }]);
});

