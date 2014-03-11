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
angularRest.controller('loginController', ['Restangular', '$scope', '$alert', '$sce', 'Cache', function(Restangular, $scope, $alert, $sce, Cache) {
        $scope.isLoggedIn = false;

        // Check login
        $scope.isLoggedInCheck = function() {
            if(sessionStorage.token) {
                $alert({title: 'Already logged in!', content: $sce.trustAsHtml('You are already logged in as '+ sessionStorage.username), type: 'warning'});
                $scope.isLoggedIn = true;
            } else {
                $scope.isLoggedIn = false;
            }
        };

        // Perform login check
        $scope.isLoggedInCheck();

        // Handle forum submit
        $scope.login = function(user) {
            // Fix for autocomplete
            if(!user) {
                user = { username: jQuery('#LoginUsername').val(), password: jQuery('#LoginPassword').val() };
            }

            var auth = Restangular.one('auth').post('username', user).then(function(authResponse) {
                // Successful auth?
                if(authResponse.token) {
                    var user = Restangular.one('user', authResponse.userId).get({ token: authResponse.token }).then(function(userResponse) {
                        // Store basic user data
                        sessionStorage.username     = userResponse.username;
                        sessionStorage.email        = userResponse.email;
                        sessionStorage.id           = userResponse.id;
                        sessionStorage.firstName    = userResponse.firstName;
                        sessionStorage.lastName     = userResponse.lastName;

                        // Store permissions
                        sessionStorage.authPermission          = userResponse.permissions.auth;
                        sessionStorage.documentPermission      = userResponse.permissions.document;
                        sessionStorage.gridPermission          = userResponse.permissions.grid;
                        sessionStorage.meetingPermission       = userResponse.permissions.meeting;
                        sessionStorage.meetingroomPermission   = userResponse.permissions.meetingroom;
                        sessionStorage.presentationPermission  = userResponse.permissions.presentation;
                        sessionStorage.userPermission          = userResponse.permissions.user;

                        // Finally store token
                        sessionStorage.token        = authResponse.token;

                        // Set token as default request parameter
                        Restangular.setDefaultRequestParams({token: sessionStorage.token});

                        // Token is valid for half an hour
                        sessionStorage.tokenTimeOut = moment().add(30, 'minutes').unix();
                        $alert({title: 'Logged In!', content: $sce.trustAsHtml('You are now logged in as '+ userResponse.username), type: 'success'});
                        // Remove all cached items (if any)
                        Cache.clearCache();
                        // Back to previous page
                        window.history.back();
                    });
                // Failed auth
                } else {
                    sessionStorage.clear();
                    $alert({title: 'Error!', content: $sce.trustAsHtml(authResponse.error +'.'), type: 'danger'});
                }
            });
        };
    }]
);

// toolbarController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('toolbarController', ['$scope', '$sce', 'Cache', '$location', '$alert', function($scope, $sce, Cache, $location, $alert) {
         $scope.accountDropdown = [
            {text: 'Profile', href: 'profile'},
            {divider: true},
            {text: 'Log Out', click: 'logout()'}
        ];

        $scope.currentLocation = $location.path();

        $scope.logout = function() {
            sessionStorage.clear();
            Cache.clearCache();
            $alert({title: 'Logged Out!', content: $sce.trustAsHtml('You are now logged out'), type: 'success'});
            $scope.getUserToolbar();
            $location.path('#/home');
        };

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

        // Restore session from storage
        if(sessionStorage.token){
            $scope.user = {
                username:   sessionStorage.username,
                email:      sessionStorage.email,
                userId:     sessionStorage.id
            };
            $scope.getUserToolbar();
        }
    }]
);

// documentsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$alert', '$modal', '$sce', 'Cache', '$route',
    function(Restangular, RestangularCache, $scope, Page, $alert, $modal, $sce, Cache, $route) {
        $scope.orderByField         = 'title';
        $scope.reverseSort          = false;
        $scope.requestDocumentsUrl  = '';
        $scope.documentsList        = {};

        var documents = RestangularCache.all('documents').getList().then(function(documentsResponse) {
            $scope.documentsList = documentsResponse;
            Page.setTitle('Documents');
            $scope.requestDocumentsUrl = documentsResponse.getRequestedUrl();
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };

        // Process file input type on change
        jQuery('body').on('change', '#inputFile', function(e) {
             // Process File
            var reader = new FileReader();
            reader.readAsDataURL(jQuery('#inputFile')[0].files[0], "UTF-8");
            reader.onload = function (e) {
                $scope.document.file = e.target.result;
            };
            reader.onerror = function(e) {
                $alert({title: 'Error!', content: $sce.trustAsHtml('Processing file failed.'), type: 'danger'});
            };
        });

        $scope.saveDocument = function() {
            Restangular.all('document').post($scope.document).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Document created!', content: $sce.trustAsHtml('The document: '+ $scope.document.title + ' has been created with ID: '+ resp.id +'.'), type: 'success'});
                    $scope.document.id                  = resp.id;
                    $scope.document.ownerId             = sessionStorage.id;
                    $scope.document.creationDate        = new moment().format('YYYY-MM-DD HH:mm:ss');
                    $scope.document.modificationDate    = new moment().format('YYYY-MM-DD HH:mm:ss');
                    $scope.documentsList.push($scope.document);

                    Cache.clearCachedUrl($scope.requestDocumentsUrl);
                    modal.hide();
                }
            });
        };

        $scope.allowCreate = function() {
             return sessionStorage.documentPermission >= 6;
        };

        // Show delete button only when allowed to delete
        $scope.allowDelete = function(ownerId) {
            if(ownerId == sessionStorage.id) {
                return true;
            } else if(sessionStorage.documentPermission >= 6) {
                return true;
            } else {
                return false;
            }
         };

        // Remove a document
        $scope.deleteDocument = function(index) {
            Restangular.one('document', $scope.documentsList[index].id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Document removed!', content: $sce.trustAsHtml('The document '+ $scope.documentsList[index].title +' has been removed from the CMS.'), type: 'success'});
                    delete $scope.documentsList[index];
                    Cache.clearCachedUrl($scope.requestDocumentsUrl);
                    $route.reload();
                }
            });
        };

        // Dialog function handler
        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            } else if(func == 'createDocument') {
                $scope.saveDocument();
            }
        };

        // New document dialog creation
        $scope.newDocument = function() {
            $scope.template         = partial_path +'/documentNewForm.html';
            $scope.document         = {};
            $scope.buttons          = [{
                        text: 'Create',
                        func: 'createDocument',
                        type: 'primary'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        type: 'danger'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
        };
    }]
);

// documentController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentController', ['Restangular', '$scope', '$routeParams', 'Page', function(Restangular, $scope, $routeParams, Page) {
        var document = Restangular.one('document', $routeParams.documentId).get().then(function(documentResponse) {
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
angularRest.controller('gridsController', ['RestangularCache', '$scope', 'Page', function(RestangularCache, $scope, Page) {
        $scope.orderByField     = 'name';
        $scope.reverseSort      = false;

        var grids = RestangularCache.all('grids').getList().then(function(gridsResponse) {
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
        var grid = Restangular.one('grid', $routeParams.gridId).get().then(function(gridResponse) {
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
angularRest.controller('meetingsController', ['RestangularCache', '$scope', 'Page', '$modal', '$tooltip', '$sce', function(RestangularCache, $scope, Page, $modal, $tooltip, $sce) {
        var date = new Date(new Date - (1000*60*60*24*14));
        var modal;

        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            }
        };

        function BootstrapModalDialog(event) {
            var eventId = jQuery(this).data('event-id');
            var meeting = RestangularCache.one('meeting', eventId).get().then(function(meetingResponse) {
                $scope.title            = $sce.trustAsHtml(moment(meetingResponse.startDate).format('dddd H:mm') +' - Room '+ meetingResponse.room.id);
                $scope.template         = partial_path +'/meetingDetails.html';
                $scope.meeting          = meetingResponse;
                $scope.startDateTime    = moment(meetingResponse.startDate).toDate();
                $scope.endDateTime      = moment(meetingResponse.endDate).toDate();
                $scope.buttons          = [{
                        text: 'Ok',
                        func: 'hide',
                        type: 'default'
                    }
                ];
                modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
            });
            return false;
        }

        var meetings = RestangularCache.one('meetings', date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).all('calendar').getList().then(function(meetingsResponse) {
            $scope.meetings = meetingsResponse;
            Page.setTitle('Meetings');

            var calendar = jQuery('#calendar').calendar({
                language:       'en-US',
                events_source:  meetingsResponse,
                tmpl_cache:     true,
                view:           'week',
                tmpl_path:      'templates/restangular/html/calendar/',
                first_day:      1,
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
                },
                onAfterEventsLoad: function(events) {
                    if(!events) {
                        return;
                    }
                },
                onAfterViewLoad: function(view) {
                    jQuery('h3.month').text(this.getTitle());
                    jQuery('.btn-group button').removeClass('active');
                    jQuery('button[data-calendar-view="' + view + '"]').addClass('active');

                    // Process all links in the calendar
                    jQuery('#calendar a.bsDialog').each(function(index){
                        // Manually add tooltips (does not work when using template tags because jQuery loads the templates not AngularJS)
                        $tooltip(jQuery(this), { title: $sce.trustAsHtml(jQuery(this).attr('title')) });
                    });
                }
            });

            // Add these items additionally (somehow they are not catched by the other on selector)
            jQuery('#calendar').on('mousedown click', 'a.bsDialog', BootstrapModalDialog);

            // Navigation and View calendar buttons
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
        });
    }]
);

// usersController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('usersController', ['RestangularCache', 'Restangular', '$scope', 'Page', '$modal', '$alert', '$sce', 'Cache', '$route', function(RestangularCache, Restangular, $scope, Page, $modal, $alert, $sce, Cache, $route) {
        $scope.orderByField     = 'username';
        $scope.reverseSort      = false;
        $scope.requestUsersUrl  = '';
        $scope.usersList        = {};

        var users = RestangularCache.all('users').getList().then(function(usersResponse) {
            $scope.usersList = usersResponse;
            Page.setTitle('Users');
            $scope.requestUsersUrl = usersResponse.getRequestedUrl();
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };

        $scope.saveUser = function() {
            Restangular.all('user').post($scope.user).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'User created!', content: $sce.trustAsHtml('The user: '+ $scope.user.username + ' has been created with ID: '+ resp.userId +'.'), type: 'success'});
                    $scope.user.id = resp.userId;
                    $scope.usersList.push($scope.user);

                    Cache.clearCachedUrl($scope.requestUsersUrl);
                    modal.hide();
                }
            });
        };

        // Show delete button only when allowed to delete
        $scope.allowDelete = function(userId) {
            if(userId != sessionStorage.id && userId != 0 && sessionStorage.userPermission >= 6) {
                return true;
            } else {
                return false;
            }
         };

         $scope.allowCreate = function() {
             return sessionStorage.userPermission >= 6;
         };

        // Remove a user
        $scope.deleteUser = function(index) {
            Restangular.one('user', $scope.usersList[index].id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'User removed!', content: $sce.trustAsHtml('The user '+ $scope.usersList[index].username +' has been removed from the CMS.'), type: 'success'});
                    delete $scope.usersList[index];
                    Cache.clearCachedUrl($scope.requestUsersUrl);
                    $route.reload();
                }
            });
        };

        // Dialog function handler
        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            } else if(func == 'createUser') {
                $scope.saveUser();
            }
        };

        // New User dialog creation
        $scope.newUser = function() {
            $scope.template         = partial_path +'/userNewForm.html';
            $scope.user             = {};
            $scope.buttons          = [{
                        text: 'Create',
                        func: 'createUser',
                        type: 'primary'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        type: 'danger'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
        };
    }]
);

// userController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('userController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', 'Page', '$alert', '$sce', 'Cache', function(Restangular, RestangularCache, $scope, $routeParams, Page, $alert, $sce, Cache) {
        $scope.userRequestUrl   = '';
        $scope.userOld          = {};

        var user = RestangularCache.one('user', $routeParams.userId).get().then(function(userResponse) {
            Page.setTitle(userResponse.username);
            $scope.user             = userResponse;
            angular.copy($scope.user, $scope.userOld);
            $scope.user.avatarCount = Object.keys(userResponse.avatars).length;
            $scope.userRequestUrl   = userResponse.getRequestedUrl();
        });

        $scope.allowUpdate = function() {
            if(sessionStorage.userPermission >= 6) {
                return true;
            } else if(sessionStorage.userPermission >= 4 && $routeParams.userId == sessionStorage.id) {
                return true;
            } else {
                return false;
            }
        };

        $scope.updateUser = function() {
            $scope.user.put().then(function(putResponse) {
                angular.copy($scope.user, $scope.userOld);
                if(!putResponse.success) {
                    $alert({title: 'User updating failed!', content: $sce.trustAsHtml(putResponse.error), type: 'danger'});
                } else {
                    $alert({title: 'User updated!', content: $sce.trustAsHtml('The user information has been updated.'), type: 'success'});
                    Cache.clearCachedUrl($scope.userRequestUrl);
                }
            });
        };

        $scope.resetUser = function() {
            angular.copy($scope.userOld, $scope.user);
        };

        $scope.isConfirmed = function(index) {
            return $scope.user.avatars[index].confirmed === 1 ? true : false;
        };

        $scope.confirmAvatar = function(index, avatar) {
            var confirm = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).put().then(function(confirmationResponse) {
                if(!confirmationResponse.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(confirmationResponse.error), type: 'danger'});
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    $alert({title: 'Avatar confirmed!', content: $sce.trustAsHtml('The avatar is confirmed user.'), type: 'success'});
                    Cache.clearCachedUrl($scope.userRequestUrl);
                }
            });
        };

        $scope.unlinkAvatar = function(index, avatar) {
            var unlink = Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).remove().then(function(unlinkResponse) {
                if(!unlinkResponse.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(unlinkResponse.error), type: 'danger'});
                } else {
                    delete $scope.user.avatars[index];
                    $scope.user.avatarCount--;
                    $alert({title: 'Avatar unlinked!', content: $sce.trustAsHtml('The avatar is no longer linked to this user.'), type: 'success'});
                    Cache.clearCachedUrl($scope.userRequestUrl);
                }
            });
        };
    }]
);

