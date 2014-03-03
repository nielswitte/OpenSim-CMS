/**
 * Contains all AngularJS settings and main controllers for the page to work
 */
var loading = 0;

// Routing
var angularRest = angular.module('OpenSim-CMS', [
    'restangular',
    'ngRoute'
]).config(function($routeProvider, $locationProvider) {
    $routeProvider
    .when('/documents', {
        templateUrl: partial_path +'/documents.html',
        controller: 'documentsController'
    }).when('/document/:documentId', {
        templateUrl: partial_path +'/document.html',
        controller: 'documentController'
    }).when('/grids', {
        templateUrl: partial_path +'/grids.html',
        controller: 'gridsController'
    }).when('/grid/:gridId', {
        templateUrl: partial_path +'/grid.html',
        controller: 'gridController'
    }).when('/meetings', {
        templateUrl: partial_path +'/meetingsCalendar.html',
        controller: 'meetingsController',
        css: 'templates/restangular/css/fullcalendar.css'
    }).when('/user/:userId', {
        templateUrl: partial_path +'/user.html',
        controller: 'userController'
    }).when('/users', {
        templateUrl: partial_path +'/users.html',
        controller: 'usersController'
    }).when('/', {
        templateUrl: partial_path +'/home.html',
        controller: 'homeController'
    }).otherwise({
        redirectTo: '/'
    });

    // configure html5 to get links working on jsfiddle
    $locationProvider.html5Mode(true);
});

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
angularRest.config(["RestangularProvider", function(RestangularProvider) {
        RestangularProvider.setBaseUrl('' + server_address + base_url + '/api');
        RestangularProvider.setDefaultHeaders({'Content-Type': 'application/x-www-form-urlencoded'});
        RestangularProvider.setErrorInterceptor(function(resp) {
            addAlert('danger', '<strong>Error!</strong> '+ resp.data.error);
            jQuery('#loading').hide();

            // Session check? Logout if expired
            if(sessionStorage.tokenTimeOut < new Date().getTime()) {
                sessionStorage.clear();
                addAlert('warning', '<strong>Session Expired!</strong> You have been logged out because your session has expired');
            }
            return false; // stop the promise chain
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

// Routing
function MainCntl($scope, $route, $routeParams, $location, Page) {
    $scope.$route = $route;
    $scope.$location = $location;
    $scope.$routeParams = $routeParams;
    $scope.Page = Page;
};