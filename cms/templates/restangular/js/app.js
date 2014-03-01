/**
 * Contains all AngularJS settings and main controllers for the page to work
 */


var angularRest = angular.module('OpenSim-CMS', [
    'restangular',
    'ngRoute'
]).config(function($routeProvider, $locationProvider) {
    $routeProvider.when('/user/:userId', {
        templateUrl: partial_path +'/user.html',
        //controller: userController,

    }).when('/users/', {
        templateUrl: partial_path +'/users.html',
        //controller: usersController
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
            addAlert('danger', '<strong>Error!</strong> '+ resp);
            return false; // stop the promise chain
        });
    }]
);

function MainCntl($scope, $route, $routeParams, $location) {
    $scope.$route = $route;
    $scope.$location = $location;
    $scope.$routeParams = $routeParams;
};

function getMainNavigation() {

}