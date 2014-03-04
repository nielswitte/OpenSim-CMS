// MainController
function MainCntl($scope, $route, $routeParams, $location, Page) {
    $scope.$route       = $route;
    $scope.$location    = $location;
    $scope.$routeParams = $routeParams;
    $scope.Page         = Page;

    // Alerts
    $scope.alerts = [];
    $scope.addAlert = function(type, title, msg) {
        $scope.alerts.push({type: type, title: title, msg: msg});
    };

    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };
};

// homeController ------------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('homeController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        Page.setTitle('Home');
    }]
);

// loginController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('loginController', ['Restangular', '$scope', function(Restangular, $scope) {
        $scope.login = function(user) {
            // Fix for autocomplete
            if(!user) {
                user = { username: jQuery('#LoginUsername').val(), password: jQuery('#LoginPassword').val() };
            }

            var formData = jQuery.param(user);
            var auth = Restangular.one('auth').post('username', formData).then(function(authResponse) {
                // Successful auth?
                if(authResponse.token) {
                    var user = Restangular.one('user', authResponse.userId).get({ token: authResponse.token }).then(function(userResponse) {
                        sessionStorage.username     = userResponse.username;
                        sessionStorage.email        = userResponse.email;
                        sessionStorage.id           = userResponse.id;
                        sessionStorage.firstName    = userResponse.firstName;
                        sessionStorage.lastName     = userResponse.lastName;

                        // Finally store token
                        sessionStorage.token        = authResponse.token;

                        // Set token as default request parameter
                        Restangular.setDefaultRequestParams({token: sessionStorage.token});

                        // Token is valid for half an hour
                        sessionStorage.tokenTimeOut = new Date(new Date + (1000*60*30)).getTime();
                        $scope.addAlert('success', 'Logged in!', 'You are now logged in as '+ userResponse.username);

                        // Reload toolbar
                        $scope.getUserToolbar();
                    });
                // Failed auth
                } else {
                    sessionStorage.clear();
                    $scope.addAlert('danger', 'Error!', authResponse.error);
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
            $scope.addAlert('success', 'Logged Out!', 'You are now logged out.');
            $scope.getUserToolbar();
        };
    }]
);

// toolbarController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('toolbarController', ['Restangular', '$scope', '$location', function(Restangular, $scope, $location) {
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

        /**
         * Check to get the currently active menu item
         *
         * @source: http://stackoverflow.com/a/18562339
         * @param {string} viewLocation
         * @returns {Boolean}
         */
        $scope.isActive = function (viewLocation) {
            if(viewLocation.length > 1) {
                return $location.path().indexOf(viewLocation) === 0;
            } else {
                return $location.path() === viewLocation;
            }
        };
    }]
);

// documentsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentsController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        var documents = Restangular.one('documents').get().then(function(documentsResponse) {
            $scope.documentsList = documentsResponse;
            Page.setTitle('Documents');
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };
    }]
);

// documentController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentController', ['Restangular', '$scope', '$routeParams', 'Page', function(Restangular, $scope, $routeParams, Page) {
        var document = Restangular.one('document').one($routeParams.documentId).get().then(function(documentResponse) {
            $scope.document = documentResponse;
            Page.setTitle(documentResponse.title);

            // Init select2
            jQuery('#inputOwner').select2({
                placeholder: 'Search for a user',
                minimumInputLength: 3,
                ajax: {
                    url: function(term, page) {
                        return base_url +'/api/users/'+ term +'/?token='+ sessionStorage.token;
                    },
                    dataType: 'json',
                    results: function(data, page) {
                        var result = [];
                        jQuery.each(data, function(i, item) {
                            var items = {id: i, text: item.username};
                            result.push(items);
                        });

                        return {results: result};
                    }
                },
                initSelection: function(element, callback) {
                    var id = jQuery(element).val();
                    if (id !== '') {
                        jQuery.ajax(base_url +'/api/user/'+ id +'/?token='+ sessionStorage.token, {
                            dataType: 'json'
                        }).done(function(data) {
                            callback({id: data.id, text: data.username});
                        });
                    }
                }
            });

            // Trigger change and update
            jQuery('#inputOwner').select2('val', documentResponse.ownerId, true);
        });

    }]
);

// gridsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('gridsController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        var grids = Restangular.one('grids').get().then(function(gridsResponse) {
            $scope.gridsList = gridsResponse;
            Page.setTitle('Grids');
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };

        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };
    }]
);

// gridController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('gridController', ['Restangular', '$scope', '$routeParams', 'Page', function(Restangular, $scope, $routeParams, Page) {
        var grid = Restangular.one('grid').one($routeParams.gridId).get().then(function(gridResponse) {
            Page.setTitle(gridResponse.name);
            $scope.grid = gridResponse;
            $scope.api_token = sessionStorage.token;
        });

        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };
    }]
);

// meetingsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingsController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        var date = new Date(new Date - (1000*60*60*24*14));

        var meetings = Restangular.one('meetings').one(date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).one('calendar').get().then(function(meetingsResponse) {
            $scope.meetings = meetingsResponse;
            Page.setTitle('Meetings');

            // Insert the events into the calendar
            jQuery('#calendar').fullCalendar({
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
                    //@todo

                    // Imediatly show it
                    //jQuery(this).popover('show');
                    return false;
                },
                timeFormat: 'H(:mm)'
            });
        });
    }]
);

// usersController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('usersController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        var users = Restangular.one('users').get().then(function(usersResponse) {
            $scope.usersList = usersResponse;
            Page.setTitle('Users');
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };
    }]
);

// userController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('userController', ['Restangular', '$scope', '$routeParams', 'Page', function(Restangular, $scope, $routeParams, Page) {
        var user = Restangular.one('user').one($routeParams.userId).get().then(function(userResponse) {
            Page.setTitle(userResponse.username);
            $scope.user = userResponse;
            $scope.user.avatarCount = Object.keys(userResponse.avatars).length;
        });

        $scope.isConfirmed = function(index) {
            return $scope.user.avatars[index].confirmed === 1 ? true : false;
        };

        $scope.confirmAvatar = function(index, avatar) {
            var confirm = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).put().then(function(confirmationResponse) {
                if(confirmationResponse.error) {
                    $scope.addAlert('danger', 'Error!', confirmationResponse.error);
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    $scope.addAlert('success', 'Avatar confirmed!', 'The avatar is confirmed user.');
                }
            });
        };

        $scope.unlinkAvatar = function(index, avatar) {
            var unlink = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).remove().then(function(unlinkResponse) {
                if(unlinkResponse.error) {
                    $scope.addAlert('danger', 'Error!', unlinkResponse.error);
                } else {
                    delete $scope.user.avatars[index];
                    $scope.user.avatarCount--;
                    $scope.addAlert('success', 'Avatar unlinked!', 'The avatar is no longer linked to this user.');
                }
            });
        };
    }]
);

