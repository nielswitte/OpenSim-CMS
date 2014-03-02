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
                        addAlert('success', '<strong>Logged in!</strong> You are now logged in as '+ userResponse.username);

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

// gridsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("gridsController", ["Restangular", "$scope", function(Restangular, $scope) {
        var grids = Restangular.one('grids').get({ token: sessionStorage.token }).then(function(gridsResponse) {
            $scope.gridsList = gridsResponse;
        });

        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };
    }]
);

// gridController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("gridController", ["Restangular", "$scope", "$routeParams", function(Restangular, $scope, $routeParams) {
        var grid = Restangular.one('grid').one($routeParams.gridId).get({ token: sessionStorage.token }).then(function(gridResponse) {
            $scope.grid = gridResponse;
            $scope.api_token = sessionStorage.token;
        });

        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };
    }]
);

// meetingsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller("meetingsController", ["Restangular", "$scope", function(Restangular, $scope) {
        var date = new Date(new Date - (1000*60*60*24*14));

        var meetings = Restangular.one('meetings').one(date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).one('calendar').get({ token: sessionStorage.token }).then(function(meetingsResponse) {
            $scope.meetings = meetingsResponse;

            // Insert the events into the calendar
            $('#calendar').fullCalendar({
                defaultView: 'agendaWeek',
                height: 650,
                header: {
                    left:   'title',
                    center: 'agendaDay,agendaWeek,month',
                    right:  'today prev,next'
                },
                events: meetingsResponse,
                eventClick: function(event) {
                    // Create a pop over for this event
                    $(this).popover({
                        placement: 'auto top',
                        title: event.title,
                        content: event.description
                    });
                    // Imediatly show it
                    $(this).popover('show');
                    return false;
                }
            });
        });
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

        $scope.confirmAvatar = function(index, avatar) {
            var confirm = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).put({ token: sessionStorage.token }).then(function(confirmationResponse) {
                if(confirmationResponse.error) {
                    addAlert('danger', '<strong>Error!</strong> '+ confirmationResponse.error);
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    addAlert('success', '<strong>Avatar confirmed!</strong> The avatar is confirmed user.');
                }
            });
        };
    }]
);

