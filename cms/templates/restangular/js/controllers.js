// loginController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("loginController", ["Restangular", "$scope", function(Restangular, $scope) {
        $scope.login = function(user) {
            // Fix for autocomplete
            if(!user) {
                user = { username: jQuery('#LoginUsername').val(), password: jQuery('#LoginPassword').val() };
            }

            var formData = jQuery.param(user);
            var auth = Restangular.one("auth").post('username', formData).then(function(authResponse) {
                // Successful auth?
                if(authResponse.token) {
                    var user = Restangular.one('user', authResponse.userId).get({ token: authResponse.token }).then(function(userResponse) {
                        sessionStorage.username    = userResponse.username;
                        sessionStorage.email       = userResponse.email;
                        sessionStorage.id          = userResponse.id;
                        sessionStorage.firstName   = userResponse.firstName;
                        sessionStorage.lastName    = userResponse.lastName;

                        // Finally store token
                        sessionStorage.token    = authResponse.token;
                        addAlert('success', '<strong>Logged in!</strong> You are now logged in as '+ formData.username);

                        // Reload toolbar
                        $scope.getUserToolbar();
                    });
                // Failed auth
                } else {
                    sessionStorage.clear();
                    addAlert('danger', '<strong>Error!</strong> '+ authResponse.error);
                }
            });
        };

        if(sessionStorage.token){
            $scope.user = {
                username: sessionStorage.username,
                email: sessionStorage.email,
                userId: sessionStorage.id
            };
        }

        $scope.logout = function() {
            sessionStorage.clear();
            addAlert('success', '<strong>Logged Out!</strong> You are now logged out.');
            $scope.getUserToolbar();
        };
    }]
);

// toolbarController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("toolbarController", ["Restangular", "$scope", function(Restangular, $scope) {
        $scope.getUserToolbar = function() {
            if(sessionStorage.token){
                return partial_path +'/userToolbarLoggedIn.html';
            } else {
                return partial_path +'/userToolbarLoggedOut.html';
            }
        };

        $scope.getMainNavigation = function() {
            if(sessionStorage.token){
                return partial_path +'/mainNavigationLoggedIn.html';
            } else {
                return partial_path +'/mainNavigationLoggedOut.html';
            }
        };
    }]
);

// usersController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("usersController", ["Restangular", "$scope", function(Restangular, $scope) {
        var users = Restangular.one('users').get({ token: sessionStorage.token }).then(function(usersResponse) {
            $scope.usersList = usersResponse;
        });
    }]
);
// userController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("userController", ["Restangular", "$scope", "$routeParams", function(Restangular, $scope, $routeParams) {
        var user = Restangular.one('user').one($routeParams.userId).get({ token: sessionStorage.token }).then(function(userResponse) {
            $scope.user = userResponse;
        });

        $scope.confirmAvatar = function(avatar) {
            var confirm = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).put({ token: sessionStorage.token }).then(function(confirmationResponse) {
                if(confirmationResponse.error) {
                    addAlert('danger', '<strong>Error!</strong> '+ confirmationResponse.error);
                } else {

                    angular.forEach($scope.user.avatars, function(value, key){
                        if(avatar === value) {
                            $scope.user.avatars[key].confirmed = 1;
                            alert($scope.user.avatars[key].uuid);
                        }
                    });
                    addAlert('success', '<strong>Avatar confirmed!</strong> The avatar is confirmed user.');
                }
            });
        };
    }]
);

