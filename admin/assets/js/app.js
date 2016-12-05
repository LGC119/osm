// 'use strict';

define(['routes', 'common/dependencyResolverFor'], function (config, dependencyResolverFor) {
	var app = angular.module('me', ['ngRoute', 'ngResource', 'ngTable', 'ui.bootstrap']);

    app.config([
        '$routeProvider',
        '$locationProvider',
        '$controllerProvider',
        '$compileProvider',
        '$filterProvider',
        '$provide',
        '$httpProvider',

        function($routeProvider, $locationProvider, $controllerProvider, $compileProvider, $filterProvider, $provide, $httpProvider) {
	        app.controller = $controllerProvider.register;
	        app.directive  = $compileProvider.directive;
	        app.filter     = $filterProvider.register;
	        app.factory    = $provide.factory;
	        app.service    = $provide.service;
            $locationProvider.html5Mode(false);

            if(config.routes !== undefined)
            {
                angular.forEach(config.routes, function(route, path)
                {
                    $routeProvider.when(path, {templateUrl:route.templateUrl, resolve:dependencyResolverFor(route.dependencies)});
                });
            }

            if(config.defaultRoutePath !== undefined)
            {
                $routeProvider.otherwise({redirectTo:config.defaultRoutePath});
            }
            $httpProvider.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
            $httpProvider.defaults.transformRequest = function (data) {
                if (data === undefined) return data;  
                return $.param(data);
            } 
        }
    ]);

	return app;
});

// 一些全局变量定义_c(config)
_c = {
    appPath : 'admin.php/'
};

function index($scope, $http) {
	$scope.location = '当前位置 : 首页 ›';
}
