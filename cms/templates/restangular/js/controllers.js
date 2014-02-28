var angularExample = angular.module('OpenSim-CMS', ["restangular"]);

angularExample.config(["RestangularProvider", function(RestangularProvider) {
        RestangularProvider.setBaseUrl(''+ server_address + base_url +'/api');
RestangularProvider.setDefaultHeaders({'Content-Type': 'application/x-www-form-urlencoded'});
    }]);
angularExample.controller("MainCtrl", ["Restangular", "$scope", function(Restangular, $scope) {
        var auth = Restangular.one("auth").customPOST("username=nielswitte&password=compuserve", 'username', {}, {}).then(function(data) {
            console.log(data.token);
        });
    }]);
