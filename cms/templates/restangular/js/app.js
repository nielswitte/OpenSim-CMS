/**
 * Contains all AngularJS settings and main controllers for the page to work
 */
var loading = 0;

var angularRest = angular.module('OpenSim-CMS', [
    'restangular',
    'ngRoute'
]).config(function($routeProvider, $locationProvider) {
    $routeProvider.when('/user/:userId', {
        templateUrl: partial_path +'/user.html',
        controller: 'userController'
    }).when('/users', {
        templateUrl: partial_path +'/users.html',
        controller: 'usersController'
    }).when('/grids', {
        templateUrl: partial_path +'/grids.html',
        controller: 'gridsController'
    }).when('/grid/:gridId', {
        templateUrl: partial_path +'/grid.html',
        controller: 'gridController'

    }).otherwise({
        redirectTo: '/'
    });

    // configure html5 to get links working on jsfiddle
    $locationProvider.html5Mode(true);
});

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

angularRest.config(["$compileProvider", function($compileProvider) {
        $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|opensim):/);
        // Angular before v1.2 uses $compileProvider.urlSanitizationWhitelist(...)

    }]
);
function MainCntl($scope, $route, $routeParams, $location) {
    $scope.$route = $route;
    $scope.$location = $location;
    $scope.$routeParams = $routeParams;
};