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
        css: 'templates/restangular/css/bootstrap-calendar.min.css'
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
angularRest.config(['RestangularProvider', function(RestangularProvider, $alert) {
        RestangularProvider.setBaseUrl('' + server_address + base_url + '/api');
        RestangularProvider.setDefaultHeaders({'Content-Type': 'application/x-www-form-urlencoded'});

        // Add token to request when available (this line is required for page refreshes to keep the token)
        if(sessionStorage.token) {
            RestangularProvider.setDefaultRequestParams({token: sessionStorage.token});
        }

        RestangularProvider.setErrorInterceptor(function(resp) {

            $alert({title: 'Error!', content: resp.data.error, placement: 'top-right', type: 'danger', show: true});
            jQuery('#loading').hide();

            // Session check? Logout if expired
            if(sessionStorage.tokenTimeOut < new Date().getTime()) {
                sessionStorage.clear();
                $alert({title: 'Session Expired!', content: 'You have been logged out because your session has expired', placement: 'top-right', type: 'warning', show: true});
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

// AngularStrap configuration
angularRest.config(function($alertProvider) {
    angular.extend($alertProvider.defaults, {
        animation: 'am-fade-and-slide-top',
        placement: 'top-right',
        container: 'body',
        duration: 5
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
