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
    .when('/dashboard', {
        templateUrl: partial_path +'/dashboard.html',
        controller: 'dashboardController',
        requireLogin: true
    }).when('/documents', {
        templateUrl: partial_path +'/document/documents.html',
        controller: 'documentsController',
        requireLogin: true
    }).when('/document/:documentId', {
        templateUrl: partial_path +'/document/document.html',
        controller: 'documentController',
        requireLogin: true
    }).when('/document/:documentId/slide/:slideId', {
        templateUrl: partial_path +'/document/slide.html',
        controller: 'slideController',
        requireLogin: true
    }).when('/document/:documentId/page/:pageId', {
        templateUrl: partial_path +'/document/page.html',
        controller: 'pageController',
        requireLogin: true
    }).when('/grids', {
        templateUrl: partial_path +'/grid/grids.html',
        controller: 'gridsController',
        requireLogin: true
    }).when('/grid/:gridId', {
        templateUrl: partial_path +'/grid/grid.html',
        controller: 'gridController',
        requireLogin: true
    }).when('/login', {
        templateUrl: partial_path +'/login.html',
        controller: 'loginController',
        requireLogin: false
    }).when('/meetings', {
        templateUrl: partial_path +'/meeting/meetingsCalendar.html',
        controller: 'meetingsController',
        css: 'templates/restangular/css/bootstrap-calendar.min.css',
        requireLogin: true
    }).when('/meetings/new', {
        templateUrl: partial_path +'/meeting/meetingNew.html',
        controller: 'meetingNewController',
        css: 'templates/restangular/css/bootstrap-calendar.min.css',
        requireLogin: true
    }).when('/meeting/:meetingId/edit', {
        templateUrl: partial_path +'/meeting/meetingEdit.html',
        controller: 'meetingController',
        css: 'templates/restangular/css/bootstrap-calendar.min.css',
        requireLogin: true
    }).when('/meeting/:meetingId/minutes', {
        templateUrl: partial_path +'/meeting/meetingMinutes.html',
        controller: 'meetingMinutesController',
        requireLogin: true
    }).when('/meeting/:meetingId', {
        templateUrl: partial_path +'/meeting/meeting.html',
        controller: 'meetingController',
        requireLogin: true
    }).when('/user/:userId/edit', {
        templateUrl: partial_path +'/user/userEdit.html',
        controller: 'userController',
        requireLogin: true
    }).when('/user/:userId', {
        templateUrl: partial_path +'/user/user.html',
        controller: 'userController',
        requireLogin: true
    }).when('/users', {
        templateUrl: partial_path +'/user/users.html',
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
    $locationProvider.html5Mode(true).hashPrefix('!');
});

// Authentication check on run
angularRest.run(['$rootScope', 'Restangular', '$location', '$alert', '$sce', 'Cache', '$anchorScroll', function ($rootScope, Restangular, $location, $alert, $sce, Cache, $anchorScroll) {
        $rootScope.$on("$routeChangeStart", function(event, next, current) {
            if (next.requireLogin && !sessionStorage.token) {
                $alert({title: 'Error!', content: $sce.trustAsHtml('This page requires authentication.'), type: 'danger'});
                $location.path('/login');
            }

            // Scroll back to the top of the page
            $anchorScroll();
        });

        var errorCount = 0;
        // Set an error interceptor for Restangular
        Restangular.setErrorInterceptor(function(resp) {
            jQuery('#loading').hide();

            // To many errors is auto logout
            errorCount++;
            if(errorCount >= 10) {
                errorCount = 0;
                sessionStorage.clear();
                $alert({title: 'Session Terminated', content: $sce.trustAsHtml('You have been logged out because you caused to many errors.'), type: 'danger'});
                $location.path('/login');
            }

            // Session check? Logout if expired
            if(sessionStorage.tokenTimeOut < moment().unix()) {
                sessionStorage.clear();
                Cache.clearCache();
                $alert({title: 'Session Expired!', content: $sce.trustAsHtml('You have been logged out because your session has expired'), type: 'warning'});
                $location.path('/login');
            }
            // Unauthorized
            if(resp.status == 401) {
                $alert({title: 'Unauthorized!', content: $sce.trustAsHtml('You have insufficient privileges to access this API.'), type: 'danger'});
            // Other errors
            } else {
                $alert({title: 'Error!', content: $sce.trustAsHtml(resp.data.error), type: 'danger'});
            }
            return false; // stop the promise chain
        });
    }]
);

// Clear cache
angularRest.service('Cache', ['$cacheFactory', function($cacheFactory) {
        var cache = $cacheFactory.get('$http');

        this.info = function() {
            return cache.info();
        };

        // Option to clear the cache
        this.clearCache = function() {
            cache.removeAll();
        };

        // Option to clear specific cache
        this.clearCachedUrl = function(url) {
            cache.remove(url);
        };
    }]
);

// Handeling of the page title
angularRest.factory('Page', function() {
    var title = '';
    return {
        title: function() {
            return title +' - OpenSim-CMS';
        },
        setTitle: function(newTitle) {
            title = newTitle;
        }
    };
});

// Restangular settings
angularRest.config(['RestangularProvider', function(RestangularProvider) {
        var timeout;
        RestangularProvider.setBaseUrl('' + server_address + base_url + '/api');
        RestangularProvider.setDefaultHttpFields({cache: false, timeout: 30000});

        // Add token to request when available (this line is required for page refreshes to keep the token)
        if(sessionStorage.token && sessionStorage.tokenTimeOut >= moment().unix()) {
            RestangularProvider.setDefaultRequestParams({token: sessionStorage.token});
        }

        RestangularProvider.addRequestInterceptor(function() {
            // Hide loading screen after 30 seconds
            timeout = setTimeout(function() { jQuery('#loading').hide(); }, 30000);
        });

        RestangularProvider.setErrorInterceptor(function(resp) {
            // Clear session when expired
            if(sessionStorage.tokenTimeOut < moment().unix()) {
                sessionStorage.clear();
            }
        });

        RestangularProvider.addResponseInterceptor(function(data) {
            // Increase token validaty
            sessionStorage.tokenTimeOut = moment().add(30, 'minutes').unix();
            clearTimeout(timeout);
            return data;
        });
    }]
);

// Restangular service with cache
angularRest.factory('RestangularCache', function(Restangular) {
    return Restangular.withConfig(function(RestangularProvider) {
        RestangularProvider.setDefaultHttpFields({cache: true, timeout: 30000});
    });
});

// AngularStrap configuration
angularRest.config(function($alertProvider, $tooltipProvider, $timepickerProvider, $datepickerProvider, $typeaheadProvider) {
    angular.extend($alertProvider.defaults, {
        animation: 'am-fade-and-slide-top',
        placement: 'top-right',
        container: '#alerts',
        duration: 10
    });

    angular.extend($tooltipProvider.defaults, {
        animation: 'am-flip-x',
        trigger: 'hover'
    });

    angular.extend($timepickerProvider.defaults, {
        timeFormat: 'HH:mm',
        length: 5,
        dateType: 'date'
    });

    angular.extend($datepickerProvider.defaults, {
        dateFormat: 'yyyy/MM/dd',
        startWeek: 1,
        dateType: 'date'
    });

    angular.extend($typeaheadProvider.defaults, {
        minLength: 3,
        limit: 8,
        animation: 'am-flip-x',
        delay: { show: 500, hide: 100 }
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
 *
 * @param $rootScope
 * @param $compile
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

/**
 * Confirmation on click before executing
 *
 * @source: http://stackoverflow.com/a/18313962
 */
angularRest.directive('ngConfirmClick', [
    function() {
        return {
            link: function (scope, element, attr) {
                var msg = attr.ngConfirmClick || "Are you sure?";
                var clickAction = attr.confirmedClick;
                element.bind('click',function (event) {
                    if ( window.confirm(msg) ) {
                        scope.$eval(clickAction);
                    }
                });
            }
        };
    }
]);

/**
 * Create a range filter for select options
 * @example: <select ng-options="n for n in [] | range:1:30"></select>
 *
 * @source: http://stackoverflow.com/a/11161353
 * @param {integer} start
 * @param {integer} stop
 */
angularRest.filter('range', function() {
    return function(input, min, max) {
        min = parseInt(min); //Make string input int
        max = parseInt(max);
        for (var i = min; i <= max; i++)
            input.push(i);
        return input;
    };
});