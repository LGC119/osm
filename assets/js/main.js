'use strict';

require.config({
    // 定义基础URL
    baseUrl: 'assets/js',
    // 定义模块及其对应的js路径
    paths: {
        'jquery': '../lib/jquery/jquery.min',
        'uploadify': '../lib/uploadify/jquery.uploadify.min',
        'bootstrap': '../lib/bootstrap/bootstrap.min',
        'angular': '../lib/angular/angular.min',
        'angular-route': '../lib/angular/angular-route.min',
        'angular-resource': '../lib/angular/angular-resource.min',
        'angular_zh-cn': '../lib/angular/angular-locale_zh-cn',
        'ui.bootstrap': '../lib/angular/ui-bootstrap-tpls-0.11.0.min',
        'me': 'app',
        'header': 'common/HeaderController',
        'menu': 'common/MenuController',
        'AccountService': 'system/AccountService',
        'SuspendingService': 'common/SuspendingService',
        'filters': 'common/filters',
        'directives': 'common/directives',
        // 'ace-extra': '../lib/ace/ace-extra',
        'chosen': '../lib/jquery/chosen.jquery',
        'gritter': '../lib/gritter/jquery.gritter.min',
        /*'ace': '../lib/ace/ace',
        'ace-elements': '../lib/ace/ace-elements',*/
        /*'dataTables': '../lib/jquery/jquery.dataTables.min',
        'dataTablesBootstrap': '../lib/jquery/jquery.dataTables.bootstrap',*/
        'ng-table': '../lib/angular/ng-table'

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
        'AccountService': {
            deps: ['angular', 'angular-resource', 'me']
        },
        // 'SuspendingService': {
        //     deps: ['angular', 'angular-resource', 'me']
        // },
        'header': {
            deps: ['angular', 'bootstrap', 'jquery', 'me', 'SuspendingService']
        },
        'menu': {
            deps: ['angular', 'bootstrap', 'jquery', 'me']
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
        /*'ace': {
            deps: ['jquery', 'bootstrap', 'menu', 'header']
        },
        'ace-elements': {
            deps: ['jquery', 'bootstrap', 'ace']
        },*/
        /*'dataTables': {
            deps: ['jquery']
        },
        'dataTablesBootstrap': {
            deps: ['dataTables']
        }*/
    },
    priority: [
        'angular'
    ],
    urlArgs: 'v=1.1'
});

require([
    /*'angular',
    'angular-route',*/
    'jquery',
    'uploadify',
    'ui.bootstrap',
    'me',
    'angular_zh-cn',
    'AccountService',
    'header',
    'menu',
    'filters',
    'directives',
    /*'ace-extra',
    'ace',
    'ace-elements',*/
    // 'chosen',
    'gritter',
    // 'dataTablesBootstrap'
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