'use strict';

require.config({
    // 定义基础URL
    baseUrl: 'assets/js/',
    // 定义模块及其对应的js路径
    paths: {
        'jquery': '../../../assets/lib/jquery/jquery.min',
        'uploadify': '../../../assets/lib/uploadify/jquery.uploadify.min',
        'bootstrap': '../../../assets/lib/bootstrap/bootstrap.min',
        'angular': '../../../assets/lib/angular/angular.min',
        'angular-route': '../../../assets/lib/angular/angular-route.min',
        'angular-resource': '../../../assets/lib/angular/angular-resource.min',
        'angular_zh-cn': '../../../assets/lib/angular/angular-locale_zh-cn',
        'ui.bootstrap': '../../../assets/lib/angular/ui-bootstrap-tpls-0.11.0.min',
        'me': 'app',
        'filters': 'common/filters',
        'directives': 'common/directives',
        'header': 'common/HeaderController',
        'chosen': '../../../assets/lib/jquery/chosen.jquery',
        'gritter': '../../../assets/lib/gritter/jquery.gritter.min',
        'ng-table': '../../../assets/lib/angular/ng-table'

    },
    // 定义模块的依赖关系和自身暴露的属性
    shim:{
        'jquery': {
            exports: '$'
        },
        'bootstrap': {
            deps: ['jquery']
        },
        'ui.bootstrap': {
            deps: ['angular']
        },
        'angular': {
            exports: 'angular'
        },
        'angular-route': {
            deps: ['angular']
        },
        'angular-resource': {
            deps: ['angular']
        },
        'angular_zh-cn': {
            deps: ['angular']
        },
        'me': {
            deps: ['jquery', 'angular', 'angular-route','angular-resource', 'bootstrap']
        },
        'header': {
            deps: ['jquery', 'angular', 'angular-route']
        },
        'filters' : {
            deps: ['angular', 'me']
        },
        'directives': {
            deps: ['angular', 'bootstrap', 'jquery', 'me', 'uploadify']
        },
        'chosen': {
            deps: ['jquery']
        }
    },
    priority: [
        'angular'
    ],
    urlArgs: 'v=1.1'
});

require([
    'jquery',
    'uploadify',
    'ui.bootstrap',
    'me',
    'header',
    'angular_zh-cn',
    'filters',
    'directives',
    'gritter',
    'ng-table'
], function (me) {
    //This function will be called when all the dependencies
    //listed above are loaded. Note that this function could
    //be called before the page is loaded.
    //This callback is optional.

    $(document).ready(function () {
        angular.bootstrap(document, ['me']);

    });
});