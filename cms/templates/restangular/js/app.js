/**
 * Contains all AngularJS settings and main controllers for the page to work
 */
var loading = 0;

// Routing
var angularRest = angular.module('OpenSim-CMS', [
    'restangular',
    'ngRoute'
]).config(function($routeProvider, $locationProvider) {
    $routeProvider.when('/grids', {
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
    }).otherwise({
        redirectTo: '/'
    });

    // configure html5 to get links working on jsfiddle
    $locationProvider.html5Mode(true);
});

// Restangular settings
angularRest.config(["RestangularProvider", function(RestangularProvider) {
        RestangularProvider.setBaseUrl('' + server_address + base_url + '/api');
        RestangularProvider.setDefaultHeaders({'Content-Type': 'application/x-www-form-urlencoded'});
        RestangularProvider.setErrorInterceptor(function(resp) {
            console.log(resp);
            addAlert('danger', '<strong>Error!</strong> '+ resp.data.error);
            jQuery('#loading').hide();
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
function MainCntl($scope, $route, $routeParams, $location) {
    $scope.$route = $route;
    $scope.$location = $location;
    $scope.$routeParams = $routeParams;
};