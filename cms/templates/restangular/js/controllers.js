// MainController
function MainCntl($scope, $route, $routeParams, $location, Page) {
    $scope.$route       = $route;
    $scope.$location    = $location;
    $scope.$routeParams = $routeParams;
    $scope.Page         = Page;
};

// homeController ------------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('homeController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        Page.setTitle('Home');
    }]
);

// loginController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('loginController', ['Restangular', '$scope', '$alert', '$sce', function(Restangular, $scope, $alert, $sce) {
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

                        // Set an error interceptor
                        Restangular.setErrorInterceptor(function(resp) {
                            $alert({title: 'Error!', content: $sce.trustAsHtml(resp.data.error), type: 'danger'});
                            jQuery('#loading').hide();

                            // Session check? Logout if expired
                            if(sessionStorage.tokenTimeOut < new Date().getTime()) {
                                //sessionStorage.clear();
                                $alert({title: 'Session Expired!', content: $sce.trustAsHtml('You have been logged out because your session has expired'), type: 'warning'});
                            }
                            return false; // stop the promise chain
                        });

                        // Token is valid for half an hour
                        sessionStorage.tokenTimeOut = new Date(new Date + (1000*60*30)).getTime();
                        $alert({title: 'Logged In!', content: $sce.trustAsHtml('You are now logged in as '+ userResponse.username), type: 'success'});
                    });
                // Failed auth
                } else {
                    sessionStorage.clear();
                    $alert({title: 'Error!', content: $sce.trustAsHtml(authResponse.error +'.'), type: 'danger'});
                }
            });
        };

        if(sessionStorage.token){
            $scope.user = {
                username: sessionStorage.username,
                email: sessionStorage.email,
                userId: sessionStorage.id
            };
            $scope.getUserToolbar();
        }

        $scope.logout = function() {
            sessionStorage.clear();
            $alert({title: 'Logged Out!', content: $sce.trustAsHtml('You are now logged out'), type: 'success'});
            $scope.toggleLoginForm();
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

        $scope.hideLoginForm = true;
        $scope.toggleLoginForm = function() {
            $scope.hideLoginForm = !$scope.hideLoginForm;
            return $scope.hideLoginForm;
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

            var calendar = jQuery('#calendar').calendar({
                language: 'en-US',
                events_source: meetingsResponse,
                tmpl_path: 'templates/restangular/html/calendar/',
                onAfterEventsLoad: function(events) {
                    if(!events) {
                        return;
                    }
                    var list = jQuery('#eventlist');
                    list.html('');

                    jQuery.each(events, function(key, val) {
                        jQuery(document.createElement('li'))
                            .html('<a href="' + val.url + '">' + val.title + '</a>')
                            .appendTo(list);
                    });
                },
                onAfterViewLoad: function(view) {
                    jQuery('h3.month').text(this.getTitle());
                    jQuery('.btn-group button').removeClass('active');
                    jQuery('button[data-calendar-view="' + view + '"]').addClass('active');
                },
                first_day: 1,
                holidays: {
                    '01-01':     'Nieuwjaarsdag',
                    '06-01':     'Drie koningen',
                    'easter-2':  'Goede vrijdag',
                    'easter':    '1e paasdag',
                    'easter+1':  '2e paasdag',
                    '26-04':     'Koningsdag',
                    '05-05':     'Bevrijdingsdag',
                    'easter+39': 'Hemelvaartsdag',
                    'easter+49': '1e pinksterdag',
                    'easter+50': '2e pinksterdag',
                    '25-12':     '1e kerstdag',
                    '26-12':     '2e kerstdag'
                }
            });

            jQuery('.btn-group button[data-calendar-nav]').each(function() {
                jQuery(this).click(function() {
                    calendar.navigate(jQuery(this).data('calendar-nav'));
                });
            });

            jQuery('.btn-group button[data-calendar-view]').each(function() {
                jQuery(this).click(function() {
                    calendar.view(jQuery(this).data('calendar-view'));
                });
            });

            jQuery('#first_day').change(function(){
                var value = jQuery(this).val();
                value = value.length ? parseInt(value) : null;
                calendar.setOptions({first_day: value});
                calendar.view();
            });

            jQuery('#language').change(function(){
                calendar.setLanguage(jQuery(this).val());
                calendar.view();
            });

            jQuery('#events-in-modal').change(function(){
                var val = jQuery(this).is(':checked') ? jQuery(this).val() : null;
                calendar.setOptions({modal: val});
            });

            jQuery('#events-modal .modal-header, #events-modal .modal-footer').click(function(e){
                //e.preventDefault();
                //e.stopPropagation();
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
angularRest.controller('userController', ['Restangular', '$scope', '$routeParams', 'Page', '$alert', '$sce', function(Restangular, $scope, $routeParams, Page, $alert, $sce) {
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
                    $alert({title: 'Error!', content: $sce.trustAsHtml(confirmationResponse.error), type: 'danger'});
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    $alert({title: 'Avatar confirmed!', content: $sce.trustAsHtml('The avatar is confirmed user.'), type: 'success'});
                }
            });
        };

        $scope.unlinkAvatar = function(index, avatar) {
            var unlink = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).remove().then(function(unlinkResponse) {
                if(unlinkResponse.error) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(unlinkResponse.error), type: 'danger'});
                } else {
                    delete $scope.user.avatars[index];
                    $scope.user.avatarCount--;
                    $alert({title: 'Avatar unlinked!', content: $sce.trustAsHtml('The avatar is no longer linked to this user.'), type: 'success'});
                }
            });
        };
    }]
);

