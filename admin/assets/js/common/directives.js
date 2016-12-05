'use strict';

define(['me'], function (me) {
    // hello world test
    me.directive('helloWorld', function () {
        return function ($scope, elm, attrs) {
            elm.text("1.0.0");
        }
    })

    // slideToggle
    .directive('slideToggle', function () {
        return {
            restrict: 'A',
            scope: {
                'isShown': '=slideToggle'
            },
            link: function ($scope, elm, attrs) {
                // 初始为隐藏状态
                elm.hide();
                // 设置滑动速度
                var slideDuration = parseInt(attrs.slideToggleDuration, 10) || 200;
                // 使 Angular 监测 isShown 值的改变，控制菜单展现收缩
                $scope.$watch('isShown', function (newVal, oldVal) {
                    if (newVal !== oldVal) {
                        elm.stop().slideToggle(slideDuration);
                    }
                });
            }
        };
    })

    // 上传uploadify改写为angularJS适配
    .directive('meUploadify', function () {
        return {
            require: '?ngModel',
            restrict: 'A',
            link: function ($scope, element, attrs, ngModel) {
                var opts = angular.extend({}, $scope.$eval(attrs.nlUploadify));
                element.uploadify({
                    'fileObjName': opts.fileObjName || 'upfile',
                    'auto': opts.auto!=undefined?opts.auto:true,
                    'swf': opts.swf || 'assets/lib/uploadify/uploadify.swf',
                    // 临时上传测试方法
                    'uploader': opts.uploader || _c.appPath + 'common/attachment',//图片上传方法
                    'buttonText': opts.buttonText || '本地图片',
                    'buttonClass': 'btn btn-primary btn-sm',
                    'width': opts.width || 78,
                    'height': opts.height || 34,
                    'onUploadSuccess': function (file, d, response) {
                        if (ngModel) {
                            var data = eval("[" + d + "]")[0];  // console.dir(data);
                            if (data.code == 200) {
                                var path = data.data.upload_path;
                                $scope.$apply(function() {
                                    ngModel.$setViewValue(path);
                                });
                            } else { // 弹出上传错误提示
                                $.gritter.add({
                                    title : '错误', 
                                    text : data.message || '上传出错，请稍后再试！', 
                                    time : 2000, 
                                    class_name : 'gritter-warning gritter-center' 
                                });
                            }
                        }
                    }
                });
            }
        };
    })

    // 沟通处理中，用户信息弹出卡片
    .directive('userPopInfo', function () {
        return {
            restrict: 'EA',
            transclude: true,
            template: '',
            replace: true,
            link: function (scope, element, attr) {

            }
        }
    });
});
