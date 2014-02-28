// Perform the login/logout requests
angularRest.controller("loginController", ["Restangular", "$scope", function(Restangular, $scope) {
        $scope.login = function(user) {
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
                    addAlert('danger', '<strong>Error!</strong> '+ data.error);
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
        }
    }]
);
