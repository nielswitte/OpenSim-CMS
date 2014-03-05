/**
 * Contains all AngularJS settings and main controllers for the page to work
 */
var loading = 0;

// Routing
var angularRest = angular.module('OpenSim-CMS', [
    'restangular',
    'ngRoute',
    'mgcrea.ngStrap',
    'ngAnimate'
]).config(function($routeProvider, $locationProvider) {
    $routeProvider
    .when('/documents', {
        templateUrl: partial_path +'/documents.html',
        controller: 'documentsController',
        requireLogin: true
    }).when('/document/:documentId', {
        templateUrl: partial_path +'/document.html',
        controller: 'documentController',
        requireLogin: true
    }).when('/grids', {
        templateUrl: partial_path +'/grids.html',
        controller: 'gridsController',
        requireLogin: true
    }).when('/grid/:gridId', {
        templateUrl: partial_path +'/grid.html',
        controller: 'gridController',
        requireLogin: true
    }).when('/meetings', {
        templateUrl: partial_path +'/meetingsCalendar.html',
        controller: 'meetingsController',
        css: 'templates/restangular/css/bootstrap-calendar.min.css',
        requireLogin: true
    }).when('/user/:userId', {
        templateUrl: partial_path +'/user.html',
        controller: 'userController',
        requireLogin: true
    }).when('/users', {
        templateUrl: partial_path +'/users.html',
        controller: 'usersController',
        requireLogin: true
    }).when('/', {
        templateUrl: partial_path +'/home.html',
        controller: 'homeController',
        requireLogin: false
    }).otherwise({
        redirectTo: '/',
        requireLogin: false
    });

    // configure html5 to get links working on jsfiddle
    $locationProvider.html5Mode(true);
});

// Authentication check on run
angularRest.run(['$rootScope', '$location', '$alert', '$sce', function ($rootScope, $location, $alert, $sce) {
        $rootScope.$on("$routeChangeStart", function(event, next, current) {
            if (next.requireLogin && !sessionStorage.token) {
                $alert({title: 'Error!', content: $sce.trustAsHtml('This page requires authentication.'), type: 'danger'});
            }
        });
    }]
);

// Handeling of the page title
angularRest.factory('Page', function() {
    var title = 'OpenSim-CMS';
    return {
        title: function() {
            return title;
        },
        setTitle: function(newTitle) {
            title = newTitle +' - '+ title;
        }
    };
});

// Restangular settings
angularRest.config(['RestangularProvider', function(RestangularProvider) {
        RestangularProvider.setBaseUrl('' + server_address + base_url + '/api');
        RestangularProvider.setDefaultHeaders({'Content-Type': 'application/x-www-form-urlencoded'});

        // Add token to request when available (this line is required for page refreshes to keep the token)
        if(sessionStorage.token) {
            RestangularProvider.setDefaultRequestParams({token: sessionStorage.token});
        }

        RestangularProvider.setErrorInterceptor(function(resp) {
            jQuery('#loading').hide();
            return false;
        });
        RestangularProvider.addRequestInterceptor(function(element, operation, route, url) {
            // Show loading screen
            if(loading == 0) {
                jQuery('#loading').show();
            }
            loading++;
            return element;
        });
        RestangularProvider.addResponseInterceptor(function(data, operation, what, url, response, deferred) {
            loading--;
            // Hide loading screen when all requests are finished
            if(loading == 0) {
                jQuery('#loading').hide();
            }
            // Increase token validaty
            sessionStorage.tokenTimeOut = new Date(new Date + (1000*60*30));

            return data;
        });
    }]
);

// AngularStrap configuration
angularRest.config(function($alertProvider) {
    angular.extend($alertProvider.defaults, {
        animation: 'am-fade-and-slide-top',
        placement: 'top-right',
        container: '#alerts',
        duration: 10
    });
});

// Add opensim protocol to safe list
angularRest.config(["$compileProvider", function($compileProvider) {
        $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|opensim):/);
        // Angular before v1.2 uses $compileProvider.urlSanitizationWhitelist(...)
    }]
);

/**
 * Lazy loading of CSS files
 *
 * @source http://stackoverflow.com/a/20404559
 */
angularRest.directive('head', ['$rootScope','$compile',
    function($rootScope, $compile){
        return {
            restrict: 'E',
            link: function(scope, elem){
                var html = '<link rel="stylesheet" ng-repeat="(routeCtrl, cssUrl) in routeStyles" ng-href="{{cssUrl}}" />';
                elem.append($compile(html)(scope));
                scope.routeStyles = {};
                $rootScope.$on('$routeChangeStart', function (e, next, current) {
                    if(current && current.$$route && current.$$route.css){
                        if(!Array.isArray(current.$$route.css)){
                            current.$$route.css = [current.$$route.css];
                        }
                        angular.forEach(current.$$route.css, function(sheet){
                            delete scope.routeStyles[sheet];
                        });
                    }
                    if(next && next.$$route && next.$$route.css){
                        if(!Array.isArray(next.$$route.css)){
                            next.$$route.css = [next.$$route.css];
                        }
                        angular.forEach(next.$$route.css, function(sheet){
                            scope.routeStyles[sheet] = sheet;
                        });
                    }
                });
            }
        };
    }
]);
