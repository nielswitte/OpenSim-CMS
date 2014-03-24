// MainController
function MainCntl($scope, $route, $routeParams, $location, Page) {
    $scope.$route       = $route;
    $scope.$location    = $location;
    $scope.$routeParams = $routeParams;
    $scope.Page         = Page;
};

// chatController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('chatController', ['Restangular', 'RestangularCache', '$scope', '$aside', '$sce', '$timeout', '$alert', function(Restangular, RestangularCache, $scope, $aside, $sce, $timeout, $alert) {
        $scope.grids          = [];
        var lastMsgTimestamp;
        $scope.chats          = [];
        $scope.minimizedChat  = false;
        var autoScroll        = true;
        var showChatButton    = true;
        var timer;
        var chatAside;
        var selectedGridId;

        // Get chat template
        $scope.getChat = function() {
            return partial_path +'/chat/chat.html';
        };

        // From the datetime string only show the time
        $scope.timeOnly = function(string) {
            return string.substr(11);
        };

        // Check if message is own message or not
        $scope.ownMessage = function(fromCMS, userId) {
            if(fromCMS == 1 && userId == sessionStorage.id) {
                return 'text-right';
            } else {
                return '';
            }
        };

        // Show the chat aside
        $scope.showChat = function() {
            $scope.$broadcast('startChat');
        };

        // Show the chat button?
        $scope.showChatbutton = function() {
            return showChatButton && sessionStorage.chatPermission >= READ;
        };

        // Wait for chat to become visible
        $scope.$on('startChat', function (event, args) {
            // Only start new chat when not defined
            if(chatAside === undefined) {
                // Create aside sidebar
                chatAside = $aside({
                    scope: $scope,
                    template: partial_path +'/chat/aside.html',
                    show: false,
                    backdrop: false
                });
            // Check if grid is known and re-init chat
            } else {
                if(selectedGridId !== undefined) {
                    timer = setInterval(updateChat, 2000);
                }
            }

            RestangularCache.all('grids').getList().then(function(gridResponse) {
                $scope.grids = gridResponse;
            });

            // Show chat aside when loading is done
            chatAside.$promise.then(function() {
                chatAside.show();
                showChatButton = false;

                // Disable autoscroll when user starts to scroll
                var messagesDiv = jQuery('#chatAside .messages');
                messagesDiv.scroll(function() {
                    if(messagesDiv.scrollTop() >= (messagesDiv[0].scrollHeight - messagesDiv.height())) {
                        autoScroll = true;
                    } else {
                        autoScroll = false;
                    }
                });
            });
        });

        // Send the chat message to the server
        $scope.sendChat = function(selectedGridId, message) {
            // Clear chat message field
            jQuery('#chatMessage').val('');
            // Send message
            Restangular.one('grid', selectedGridId).post('chats', { userId: sessionStorage.id, message: message, timestamp: moment().format('YYYY-MM-DD HH:mm:ss'), fromCMS: 1 }).then(function(resp) {
                // On error show message
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                }
            });
        };

        // Load chat for the selected Grid
        $scope.selectGrid = function(gridId) {
            // Clear existing timers
            clearInterval(timer);
            // Set the grid
            selectedGridId   = gridId;
            // Empty chat when switching grids
            $scope.chats     = [];
            // Reset last msg timestmap
            lastMsgTimestamp = moment().subtract('minutes', 30).unix();
            // Get last chat entries for past one hour
            updateChat();
            // Set autoscroll to true (overwrites it when switching grid)
            autoScroll       = true;
            // Start auto refreshing chat
            timer            = setInterval(updateChat, 2000);
        };

        // Update the chat
        var updateChat = function() {
            // Append the chat with all chats send after the previous message
            Restangular.one('grid', selectedGridId).one('chats', (lastMsgTimestamp + 1)).get().then(function(chatResponse) {
                // Update last timestamp and append array if any new results
                if(chatResponse.length >= 1) {
                    lastMsgTimestamp = moment(chatResponse[0].timestamp, 'YYYY-MM-DD HH:mm:ss').unix();

                    // Add all new chats to scope
                    angular.forEach(chatResponse, function(chat) {
                        $scope.chats.push(chat);
                    });
                    // Scroll the chat
                    $timeout(scrollChat, 100);

                    // Highlight the header when minimized and new messages are loaded
                    if($scope.minimizedChat) {
                        jQuery('#chatAside .aside-header').addClass('highlight');
                    }
                }
            });
        };

        // Function to scroll the chat Down
        var scrollChat = function() {
            // See if user is currently at bottem of messages div
            var messagesDiv = jQuery('#chatAside .messages');

            // Need to auto scroll?
            if(autoScroll) {
                messagesDiv.scrollTop(messagesDiv[0].scrollHeight * 2);
            }
        };

        // Close the chat Aside
        $scope.closeChat = function() {
            clearInterval(timer);
            chatAside.hide();
            showChatButton = true;
        };

        // Toggle visiblity of chat
        $scope.toggleChat = function() {
            $scope.minimizedChat = !$scope.minimizedChat;

            // scroll back to bottom on show
            if(!$scope.minimizedChat) {
                autoScroll = true;
                $timeout(scrollChat, 100);
                jQuery('#chatAside .aside-header').removeClass('highlight');
                // Back to 2 seconds
                clearInterval(timer);
                timer            = setInterval(updateChat, 2000);
            } else {
                // Change update to 5 seconds when in background
                clearInterval(timer);
                timer            = setInterval(updateChat, 5000);
            }
        };
    }]
);

// homeController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('homeController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
        Page.setTitle('Home');
    }]
);

// loginController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('loginController', ['Restangular', 'RestangularCache', '$scope', '$alert', '$sce', 'Cache', function(Restangular, RestangularCache, $scope, $alert, $sce, Cache) {
        $scope.isLoggedIn = false;

        // Check login
        $scope.isLoggedInCheck = function() {
            if(sessionStorage.token && sessionStorage.id) {
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
            // Show loading screen
            jQuery('#loading').show();

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
                        sessionStorage.chatPermission          = userResponse.permissions.chat;
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
                        RestangularCache.setDefaultRequestParams({token: sessionStorage.token});

                        // Token is valid for half an hour
                        sessionStorage.tokenTimeOut = moment().add(30, 'minutes').unix();

                        // Feedback to user
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
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };
    }]
);

// toolbarController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('toolbarController', ['$scope', '$sce', 'Cache', '$location', '$alert', function($scope, $sce, Cache, $location, $alert) {
        $scope.currentLocation = $location.path();

        // Handle logout events
        $scope.logout = function() {
            sessionStorage.clear();
            Cache.clearCache();
            $alert({title: 'Logged Out!', content: $sce.trustAsHtml('You are now logged out'), type: 'success'});
            $scope.getUserToolbar();
            $location.path('home');
        };

        // Get the right toolbar (right area of navbar)
        $scope.getUserToolbar = function() {
            if(sessionStorage.token && sessionStorage.id){
                // Create dropdown menu
                $scope.accountDropdown = [
                    {text: 'Profile', href: '#!/user/'+ sessionStorage.id},
                    {divider: true},
                    {text: 'Log Out', click: 'logout()'}
                ];

                return partial_path +'/navbar/userToolbarLoggedIn.html';
            } else {
                return partial_path +'/navbar/userToolbarLoggedOut.html';
            }
        };

        // Toggle collapse of the navigation bar
        $scope.toggleNavigation = function() {
            jQuery('#bs-navbar').toggleClass('collapse');
        };

        // Get the right main navigation (left area of navbar)
        $scope.getMainNavigation = function() {
            if(sessionStorage.token && sessionStorage.id){
                return partial_path +'/navbar/mainNavigationLoggedIn.html';
            } else {
                return partial_path +'/navbar/mainNavigationLoggedOut.html';
            }
        };

        // Restore session from storage
        if(sessionStorage.token && sessionStorage.id){
            $scope.user = {
                username:   sessionStorage.username,
                email:      sessionStorage.email,
                userId:     sessionStorage.id
            };
            $scope.getUserToolbar();
        }
    }]
);

// documentsController ------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$alert', '$modal', '$sce', 'Cache', '$route',
    function(Restangular, RestangularCache, $scope, Page, $alert, $modal, $sce, Cache, $route) {
        $scope.orderByField         = 'title';
        $scope.reverseSort          = false;
        var requestDocumentsUrl     = '';
        $scope.documentsList        = [];

        // Show loading screen
        jQuery('#loading').show();

        RestangularCache.all('documents').getList().then(function(documentsResponse) {
            $scope.documentsList = documentsResponse;
            Page.setTitle('Documents');
            requestDocumentsUrl = documentsResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
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

        // Save the new document
        function saveDocument() {
            // Show loading screen
            jQuery('#loading').show();

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

                    Cache.clearCachedUrl(requestDocumentsUrl);
                    modal.hide();
                    $route.reload();

                    // Remove loading screen
                    jQuery('#loading').hide();
                }
            });
        };

        $scope.allowCreate = function() {
             return sessionStorage.documentPermission >= EXECUTE;
        };

        // Show delete button only when allowed to delete
        $scope.allowDelete = function(ownerId) {
            if(ownerId == sessionStorage.id && sessionStorage.documentPermission >= EXECUTE) {
                return true;
            } else if(sessionStorage.documentPermission >= WRITE) {
                return true;
            } else {
                return false;
            }
         };

        // Remove a document
        $scope.deleteDocument = function(index) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('document', $scope.documentsList[index].id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Document removed!', content: $sce.trustAsHtml('The document '+ $scope.documentsList[index].title +' has been removed from the CMS.'), type: 'success'});
                    $scope.documentsList.splice(index, 1);
                    Cache.clearCachedUrl(requestDocumentsUrl);
                    $route.reload();
                }

                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Dialog function handler
        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            } else if(func == 'createDocument') {
                saveDocument();
            }
        };

        // New document dialog creation
        $scope.newDocument = function() {
            $scope.template         = partial_path +'/document/documentNewForm.html';
            $scope.formSubmit       = 'createDocument';
            $scope.document         = {};
            $scope.buttons          = [{
                        text: 'Create',
                        func: '',
                        class: 'primary',
                        type: 'submit'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        class: 'danger',
                        type: 'button'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
        };
    }]
);

// documentController -------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentController', ['Restangular', '$scope', '$routeParams', 'Page', '$modal', '$sce', function(Restangular, $scope, $routeParams, Page, $modal, $sce) {
        // Show loading screen
        jQuery('#loading').show();

        // Get document from API
        Restangular.one('document', $routeParams.documentId).get().then(function(documentResponse) {
            $scope.document = documentResponse;
            $scope.token    = sessionStorage.token;
            Page.setTitle(documentResponse.title);

            // Init select2
            jQuery('#inputOwner').select2({
                placeholder: 'Search for an user',
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
                    if (id != '') {
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

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Open dialog with the Slide preview
        $scope.lightbox = function(number, url) {
            $modal({
                title: $sce.trustAsHtml('Slide '+ number),
                content: $sce.trustAsHtml('<img src="'+ url+'?token='+ sessionStorage.token +'" class="img-responsive">'),
                html: true,
                show: true,
                template: 'templates/restangular/html/bootstrap/modalDialogBasic.html',
                scope: $scope
            });
        };
    }]
);

// gridsController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('gridsController', ['RestangularCache', '$scope', 'Page', function(RestangularCache, $scope, Page) {
        $scope.orderByField     = 'name';
        $scope.reverseSort      = false;
        $scope.gridsList        = { regions: [] };

        // Show loading screen
        jQuery('#loading').show();

        RestangularCache.all('grids').getList().then(function(gridsResponse) {
            $scope.gridsList = gridsResponse;
            Page.setTitle('Grids');

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Searches the list with regions for the given uuid
        $scope.findRegionIndexByUuid = function(grid, uuid) {
            if(grid.regions) {
                for(var i = 0; i < grid.regions.length; i++) {
                    if(grid.regions[i].uuid == uuid) {
                        return i;
                    }
                }
            }
            return false;
        };

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

// gridController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('gridController', ['Restangular', '$scope', '$routeParams', 'Page', function(Restangular, $scope, $routeParams, Page) {
        // Show loading screen
        jQuery('#loading').show();

        Restangular.one('grid', $routeParams.gridId).get().then(function(gridResponse) {
            Page.setTitle(gridResponse.name);
            $scope.grid = gridResponse;
            // Token required to request grid images
            $scope.api_token = sessionStorage.token;

            // Remove loading screen
            jQuery('#loading').hide();
        });

        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };
    }]
);

// meetingsController -------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$modal', '$tooltip', '$sce', 'Cache', '$location',  function(Restangular, RestangularCache, $scope, Page, $modal, $tooltip, $sce, Cache, $location) {
        var date = new Date(new Date - (1000*60*60*24*14));
        var modal;
        var meeting;
        var eventId;
        var meetingRequestUrl;

        // Get all meetings for the calendar
        Restangular.one('meetings', date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).all('calendar').getList().then(function(meetingsResponse) {
            // Show loading screen
            jQuery('#loading').show();

            $scope.meetings = meetingsResponse;
            Page.setTitle('Meetings');

            // Create the calendar
            var calendar = jQuery('#calendar').calendar({
                language:       'en-US',
                events_source:  meetingsResponse,
                tmpl_cache:     true,
                view:           'week',
                tmpl_path:      'templates/restangular/html/calendar/',
                first_day:      1,
                holidays:       HOLIDAYS,
                time_start:     TIME_START,
                time_end:       TIME_END,
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

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Does the user have permission to create a new meeting?
        $scope.allowCreate = function() {
            if(sessionStorage.meetingPermission >= EXECUTE) {
                return true;
            } else {
                return false;
            }
        };
    }]
);

// meetingController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', 'Page', '$alert', '$sce', 'Cache', '$location', function(Restangular, RestangularCache, $scope, $routeParams, Page, $alert, $sce, Cache, $location) {
        var meetingRequestUrl;
        var gridsRequestUrl;
        var calendar;
        // Initial values to prevent errors
        $scope.startDateString          = new moment().format('YYYY/MM/DD');
        $scope.startTimeString          = new moment().format('HH') +':00';
        $scope.endDateString            = new moment().format('YYYY/MM/DD');
        $scope.endTimeString            = new moment().format('HH') +':00';
        $scope.meeting                  = {
            creator: { id: -1 },
            participants: []
        };
        var meetingOld                  = {};
        $scope.grids                    = [];
        $scope.rooms                    = [];
        $scope.participant              = '';
        $scope.usernameSearchResults    = [];

        // Navigate the calendar to the current date
        $scope.updateCalendar = function() {
            var currentDate = new moment(calendar.getStartDate());
            var newDate     = new moment($scope.startDateString, 'YYYY/MM/DD');
            var diff        = newDate.diff(currentDate, 'days');
            var i           = 0;
            // Navigate the calendar until the difference is 0 or a whole year has passed
            while(diff != 0 && i < 365) {
                // New date is in the future
                if(diff > 0) {
                    diff--;
                    calendar.navigate('next');
                // New date is in the past
                } else {
                    diff++;
                    calendar.navigate('prev');
                }
                i++;
            }
        };

        /**
         * Gives the index of the selected grid
         *
         * @returns {Number}
         */
        $scope.selectedGridIndex = function() {
            for (var i = 0; i < $scope.grids.length; i++) {
                var grid = $scope.grids[i];
                if (grid.id == $scope.meeting.room.grid.id) {
                    return i;
                }
            }
            return false;
        };

        // Get meeting rooms for selected region
        $scope.getMeetingRooms = function() {
            RestangularCache.one('grid', $scope.meeting.room.grid.id).one('region', $scope.meeting.room.region.uuid).all('rooms').getList().then(function(roomsResponse){
                $scope.rooms = roomsResponse;
            });
        };

        // Update the meeting with the new data
        $scope.updateMeeting = function () {
            // Reformat back to the expected format for the API
            $scope.meeting.startDate   = $scope.startDateString.replace(/\//g, '-') +' '+ $scope.startTimeString +':00';
            $scope.meeting.endDate     = $scope.endDateString.replace(/\//g, '-') +' '+ $scope.endTimeString +':00';

            $scope.meeting.put().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Meeting updated!', content: $sce.trustAsHtml('The meeting has been updated.'), type: 'success'});
                    Cache.clearCache();
                    $location.path('meetings');
                }
            });
        };

        // Restore the meeting to the original values
        $scope.resetMeeting = function() {
            angular.copy(meetingOld, $scope.meeting);
            setDateTimes();
        };

        // Parse dates to working Angular-Strap date strings (somehow Date-objects do not work with min/max date/time)
        function setDateTimes() {
            $scope.startDateString  = new moment($scope.meeting.startDate).format('YYYY/MM/DD');
            $scope.startTimeString  = new moment($scope.meeting.startDate).format('HH:mm');
            $scope.endDateString    = new moment($scope.meeting.endDate).format('YYYY/MM/DD');
            $scope.endTimeString    = new moment($scope.meeting.endDate).format('HH:mm');
            // Manually update input since angular-strap does not do this...
            jQuery('#inputStartDate').val($scope.startDateString);
            jQuery('#inputStartTime').val($scope.startTimeString);
            jQuery('#inputEndDate').val($scope.endDateString);
            jQuery('#inputEndTime').val($scope.endTimeString);
        }

        // Does the user have permission to edit this meeting?
        $scope.allowUpdate = function() {
            if(sessionStorage.meetingPermission >= EXECUTE && $scope.meeting.creator.id == sessionStorage.id) {
                return true;
            } else if(sessionStorage.meetingPermission >= WRITE) {
                return true;
            } else {
                return false;
            }
        };

        // Search for the given username
        $scope.getUserByUsername = function($viewValue) {
            if($viewValue != null && $viewValue.length >= 3) {
                var results = RestangularCache.one('users', $viewValue).get().then(function(usersResponse) {
                    $scope.usernameSearchResults = usersResponse;
                    return usersResponse;
                });
            } else {
                var results = '';
            }
            return results;
        };

        // Adds the currently selected participant to the list
        $scope.addParticipant = function() {
            for(var i = 0; i < $scope.usernameSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if($scope.usernameSearchResults[i].username == $scope.participant) {
                    if(!isDuplicateParticipant()) {
                        $scope.meeting.participants.push($scope.usernameSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The user '+ $scope.usernameSearchResults[i].username + ' is already a participant for this meeting'), type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate participants
        function isDuplicateParticipant() {
            for(var i = 0; i < $scope.meeting.participants.length; i++) {
                if($scope.meeting.participants[i].username == $scope.participant) {
                    return true;
                }
            }
            return false;
        };

        // Removes the user with the given ID from the list
        $scope.removeParticipant = function(id) {
            for(var i = 0; i < $scope.meeting.participants.length; i++) {
                if($scope.meeting.participants[i].id == id) {
                    $scope.meeting.participants.splice(i, 1);
                    return true;
                }
            }
            return false;
        };

        // Show or hide agenda help
        $scope.agendaHelp = false;
        $scope.toggleAgendaHelp = function() {
            $scope.agendaHelp = !$scope.agendaHelp;
        };

        $scope.showAgendaHelp = function() {
            return $scope.agendaHelp;
        };

        // Show loading screen
        jQuery('#loading').show();

        // Get the selected meeting
        RestangularCache.one('meeting', $routeParams.meetingId).get().then(function(meetingResponse) {
            $scope.meeting          = meetingResponse;
            angular.copy($scope.meeting, meetingOld);
            // Page and content titles
            $scope.title            = $sce.trustAsHtml(moment(meetingResponse.startDate).format('dddd H:mm') +' - Room '+ meetingResponse.room.id);
            Page.setTitle('Meeting '+ meetingResponse.id);
            meetingRequestUrl       = meetingResponse.getRequestedUrl();
            if($location.path().indexOf('/edit') == -1) {
                $scope.meeting.agenda   = $sce.trustAsHtml(meetingResponse.agenda.replace(/\n/g, '<br>').replace(/\ /g, '&nbsp;'));
            }

            // Set the dates and times
            setDateTimes();

            // Get additional information about the Grids
            RestangularCache.all('grids').getList().then(function(gridsResponse) {
                gridsRequestUrl = gridsResponse.getRequestedUrl();
                $scope.grids    = gridsResponse;
            });

            // Get additional meeting rooms
            $scope.getMeetingRooms();

            // Remove loading screen
            jQuery('#loading').hide();

            // Load meetings on same day
            var date = new moment().subtract('week', 2).format('YYYY-MM-DD');
            Restangular.one('meetings', date).all('calendar').getList().then(function(meetingsResponse) {
                calendar = jQuery('#calendar').calendar({
                    language:       'en-US',
                    events_source:  meetingsResponse,
                    tmpl_cache:     true,
                    view:           'day',
                    day:            new moment($scope.meeting.startDate).format('YYYY-MM-DD'),
                    time_start:     TIME_START,
                    time_end:       TIME_END,
                    tmpl_path:      partial_path +'/../calendar/',
                    holidays:       HOLIDAYS,
                    onAfterViewLoad: function(view) {
                        jQuery('h4.calendar-date').text(this.getTitle());

                        // Scroll halfway they calendar
                        var container = jQuery('#calendar').parent('div.calendar-container');
                        container.scrollTop(container.height() / 2);
                    }
                });
            });
        });
    }]
);

// meetingMinutesController -------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingMinutesController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', 'Page', '$alert', '$sce', 'Cache', '$location', function(Restangular, RestangularCache, $scope, $routeParams, Page, $alert, $sce, Cache, $location) {
        $scope.meeting = {};
        RestangularCache.one('meeting', $routeParams.meetingId).one('minutes').get().then(function(meetingResponse) {
            meetingResponse.agenda = $sce.trustAsHtml(meetingResponse.agenda.replace(/\n/g, '<br>').replace(/\ /g, '&nbsp;'));
            $scope.meeting = meetingResponse;
        });

        // From the datetime string only show the time
        $scope.timeOnly = function(string) {
            return string.substr(11);
        };

        // Show heading?
        $scope.showAgendaNextItemHeading = function(index) {
            if(index == 0 || $scope.meeting.minutes[index].agenda.id != $scope.meeting.minutes[index-1].agenda.id) {
                return true;
            } else {
                return false;
            }
        }

        var parents     = [];
        // Start with 2 for H2
        parents.push(2);
        var depth       = 0;
        // Checks to see if this agenda item is different from the previous
        $scope.agendaNextItemHeading = function(index, minute) {
            if(index == 0 || parseInt(minute.agenda.id) != parseInt($scope.meeting.minutes[index-1].agenda.id)) {
                var parentId  = minute.agenda.parentId;

                // Level deeper
                if(parents[parentId] == undefined) {
                    parents[parentId] = (depth+1);
                } else {
                    depth = parents[parentId];
                }

                return $sce.trustAsHtml('<h'+ depth +'>'+ minute.agenda.value +'</h'+ depth +'>');
            } else {
                return "";
            }
        };
    }]
);

// meetingNewController -----------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingNewController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$location', '$alert', '$sce', 'Cache', function(Restangular, RestangularCache, $scope, Page, $location, $alert, $sce, Cache) {
        Page.setTitle('Schedule meeting');
        var gridsRequestUrl;
        var calendar;
        // Initial values to prevent errors
        $scope.startDateString          = new moment().format('YYYY/MM/DD');
        $scope.todayDateString          = new moment().format('YYYY/MM/DD');
        $scope.startTimeString          = new moment().format('HH') +':00';
        $scope.endDateString            = new moment().format('YYYY/MM/DD');
        $scope.endTimeString            = new moment().format('HH') +':00';
        $scope.meeting                  = {
            room: {
                grid: { }
            },
            participants: []
        };
        $scope.grids                    = [];
        $scope.rooms                    = [];
        $scope.participant              = '';
        $scope.usernameSearchResults    = [];

        // Navigate the calendar to the current date
        $scope.updateCalendar = function() {
            var currentDate = new moment(calendar.getStartDate());
            var newDate     = new moment($scope.startDateString, 'YYYY/MM/DD');
            var diff        = newDate.diff(currentDate, 'days');
            var i           = 0;
            // Navigate the calendar until the difference is 0 or a whole year has passed
            while(diff != 0 && i < 365) {
                // New date is in the future
                if(diff > 0) {
                    diff--;
                    calendar.navigate('next');
                // New date is in the past
                } else {
                    diff++;
                    calendar.navigate('prev');
                }
                i++;
            }
        };

        /**
         * Gives the index of the selected grid
         *
         * @returns {Number}
         */
        $scope.selectedGridIndex = function() {
            for (var i = 0; i < $scope.grids.length; i++) {
                var grid = $scope.grids[i];
                if (grid.id == $scope.meeting.room.grid.id) {
                    return i;
                }
            }
            return false;
        };

        // Get meeting rooms for selected region
        $scope.getMeetingRooms = function() {
            RestangularCache.one('grid', $scope.meeting.room.grid.id).one('region', $scope.meeting.room.region.uuid).all('rooms').getList().then(function(roomsResponse){
                $scope.rooms = roomsResponse;
            });
        };

        // Parse dates to working Angular-Strap date strings (somehow Date-objects do not work with min/max date/time)
        function setDateTimes() {
            // Manually update input since angular-strap does not do this...
            jQuery('#inputStartDate').val($scope.startDateString);
            jQuery('#inputStartTime').val($scope.startTimeString);
            jQuery('#inputEndDate').val($scope.endDateString);
            jQuery('#inputEndTime').val($scope.endTimeString);
        }

        // Does the user have permission to edit this meeting?
        $scope.allowCreate = function() {
            if(sessionStorage.meetingPermission >= EXECUTE) {
                return true;
            } else {
                return false;
            }
        };

        // Show or hide agenda help
        $scope.agendaHelp = false;
        $scope.toggleAgendaHelp = function() {
            $scope.agendaHelp = !$scope.agendaHelp;
        };

        $scope.showAgendaHelp = function() {
            return $scope.agendaHelp;
        };

        // Search for the given username
        $scope.getUserByUsername = function($viewValue) {
            if($viewValue != null && $viewValue.length >= 3) {
                var results = RestangularCache.one('users', $viewValue).get().then(function(usersResponse) {
                    $scope.usernameSearchResults = usersResponse;
                    return usersResponse;
                });
            } else {
                var results = '';
            }
            return results;
        };

        // Adds the currently selected participant to the list
        $scope.addParticipant = function() {
            for(var i = 0; i < $scope.usernameSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if($scope.usernameSearchResults[i].username == $scope.participant) {
                    if(!isDuplicateParticipant()) {
                        $scope.meeting.participants.push($scope.usernameSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The user '+ $scope.usernameSearchResults[i].username + ' is already a participant for this meeting'), type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate participants
        function isDuplicateParticipant() {
            for(var i = 0; i < $scope.meeting.participants.length; i++) {
                if($scope.meeting.participants[i].username == $scope.participant) {
                    return true;
                }
            }
            return false;
        };

        // Removes the user with the given ID from the list
        $scope.removeParticipant = function(id) {
            for(var i = 0; i < $scope.meeting.participants.length; i++) {
                if($scope.meeting.participants[i].id == id) {
                    $scope.meeting.participants.splice(i, 1);
                    return true;
                }
            }
            return false;
        };

        // Creates the meeting by sending it to the server
        $scope.createMeeting = function() {
            // Reformat back to the expected format for the API
            $scope.meeting.startDate   = $scope.startDateString.replace(/\//g, '-') +' '+ $scope.startTimeString +':00';
            $scope.meeting.endDate     = $scope.endDateString.replace(/\//g, '-') +' '+ $scope.endTimeString +':00';

            Restangular.all('meeting').post($scope.meeting).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Meeting scheduled!', content: $sce.trustAsHtml('The meeting for '+ $scope.meeting.startDate +' has been created with ID: '+ resp.meetingId +'.'), type: 'success'});
                    Cache.clearCache();
                    $location.path('meetings');
                }
            });
        };

        // Show loading screen
        jQuery('#loading').show();

        // Load meetings on same day
        var date = new moment().format('YYYY-MM-DD');
        Restangular.one('meetings', date).all('calendar').getList().then(function(meetingsResponse) {
            calendar = jQuery('#calendar').calendar({
                language:       'en-US',
                events_source:  meetingsResponse,
                tmpl_cache:     true,
                view:           'day',
                day:            date,
                time_start:     TIME_START,
                time_end:       TIME_END,
                tmpl_path:      partial_path +'/../calendar/',
                holidays:       HOLIDAYS,
                onAfterViewLoad: function(view) {
                    jQuery('h4.calendar-date').text(this.getTitle());

                    // Scroll halfway they calendar
                    var container = jQuery('#calendar').parent('div.calendar-container');
                    container.scrollTop(container.height() / 2);
                }
            });
            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Set the dates and times
        setDateTimes();

        // Get additional information about the Grids
        RestangularCache.all('grids').getList().then(function(gridsResponse) {
            gridsRequestUrl = gridsResponse.getRequestedUrl();
            $scope.grids    = gridsResponse;
        });
    }]
);

// usersController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('usersController', ['RestangularCache', 'Restangular', '$scope', 'Page', '$modal', '$alert', '$sce', 'Cache', '$route', function(RestangularCache, Restangular, $scope, Page, $modal, $alert, $sce, Cache, $route) {
        $scope.orderByField     = 'username';
        $scope.reverseSort      = false;
        var requestUsersUrl     = '';
        $scope.usersList        = [];

        // Remove loading screen
        jQuery('#loading').show();

        RestangularCache.all('users').getList().then(function(usersResponse) {
            $scope.usersList = usersResponse;
            Page.setTitle('Users');
            requestUsersUrl = usersResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });

        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };

        $scope.saveUser = function() {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.all('user').post($scope.user).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'User created!', content: $sce.trustAsHtml('The user: '+ $scope.user.username + ' has been created with ID: '+ resp.userId +'.'), type: 'success'});
                    $scope.user.id = resp.userId;
                    $scope.usersList.push($scope.user);

                    Cache.clearCachedUrl(requestUsersUrl);
                    modal.hide();
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Allow changing general user information
        $scope.allowUpdate = function(userId) {
            if(sessionStorage.userPermission >= WRITE) {
                return true;
            } else if(sessionStorage.userPermission >= READ && userId == sessionStorage.id) {
                return true;
            } else {
                return false;
            }
        };

        // Show delete button only when allowed to delete
        $scope.allowDelete = function(userId) {
            if(userId != sessionStorage.id && userId != 0 && sessionStorage.userPermission >= WRITE) {
                return true;
            } else {
                return false;
            }
         };

         // User is allowed to add new users
         $scope.allowCreate = function() {
             return sessionStorage.userPermission >= WRITE;
         };

        // Remove a user
        $scope.deleteUser = function(index) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('user', $scope.usersList[index].id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'User removed!', content: $sce.trustAsHtml('The user '+ $scope.usersList[index].username +' has been removed from the CMS.'), type: 'success'});
                    delete $scope.usersList[index];
                    Cache.clearCachedUrl(requestUsersUrl);
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Compare passwords
        $scope.passwordDoNotMatch = function() {
            if($scope.user.password != $scope.user.password2) {
                jQuery('#inputPassword, #inputPassword2').parents('div.form-group').addClass('has-error');
            } else {
                jQuery('#inputPassword, #inputPassword2').parents('div.form-group').removeClass('has-error');
            }
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
            $scope.template         = partial_path +'/user/userNewForm.html';
            $scope.user             = {};
            $scope.formSubmit       = 'createUser';
            $scope.buttons          = [{
                        text: 'Create',
                        func: '',
                        class: 'primary',
                        type: 'submit'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        class: 'danger',
                        type: 'button'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
        };
    }]
);

// userController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('userController', ['Restangular', 'RestangularCache', '$scope', '$route', '$routeParams', 'Page', '$alert', '$modal', '$sce', 'Cache', function(Restangular, RestangularCache, $scope, $route, $routeParams, Page, $alert, $modal, $sce, Cache) {
        var userRequestUrl   = '';
        var userOld          = {};
        $scope.grids         = [];

        // Show loading screen
        jQuery('#loading').show();

        // Get all information about this user
        RestangularCache.one('user', $routeParams.userId).get().then(function(userResponse) {
            Page.setTitle(userResponse.username);
            $scope.user             = userResponse;
            angular.copy($scope.user, userOld);
            $scope.user.avatarCount = Object.keys(userResponse.avatars).length;
            userRequestUrl          = userResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // User is allowed to add new avatars
        $scope.allowCreate = function() {
            return sessionStorage.userPermission >= WRITE;
        };

        // Allow changing general user information
        $scope.allowUpdate = function() {
            if(sessionStorage.userPermission >= WRITE) {
                return true;
            } else if(sessionStorage.userPermission >= READ && $routeParams.userId == sessionStorage.id) {
                return true;
            } else {
                return false;
            }
        };

        // Open modal dialog to change the user's password
        $scope.changePasswordForm = function() {
            $scope.template         = partial_path +'/user/userPasswordForm.html';
            $scope.avatar           = {};
            $scope.formSubmit       = 'changePassword';
            $scope.buttons          = [{
                        text: 'Update',
                        func: '',
                        class: 'primary',
                        type: 'submit'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        class: 'danger',
                        type: 'button'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});
        };

        // Change the user's password
        $scope.changePassword = function() {
            // Show loading screen
            jQuery('#loading').show();

            $scope.user.customPUT($scope.user, 'password').then(function(putResponse) {
                angular.copy($scope.user, $scope.userOld);
                if(!putResponse.success) {
                    $alert({title: 'Change password failed!', content: $sce.trustAsHtml(putResponse.error), type: 'danger'});
                } else {
                    $alert({title: 'Password changed!', content: $sce.trustAsHtml('The user\'s password has been updated.'), type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Allow changing user's permissions
        $scope.allowPermissions = function() {
            if(sessionStorage.userPermission >= WRITE) {
                return true;
            } else {
                return false;
            }
        };

        // Save scope changes by submitting them to the API
        $scope.updateUser = function() {
            // Show loading screen
            jQuery('#loading').show();

            $scope.user.put().then(function(putResponse) {
                angular.copy($scope.user, $scope.userOld);
                if(!putResponse.success) {
                    $alert({title: 'User updating failed!', content: $sce.trustAsHtml(putResponse.error), type: 'danger'});
                } else {
                    $alert({title: 'User updated!', content: $sce.trustAsHtml('The user information has been updated.'), type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Reset changes
        $scope.resetUser = function() {
            angular.copy(userOld, $scope.user);
        };

        // Check if an avatar is confirmed
        $scope.isConfirmed = function(index) {
            return $scope.user.avatars[index].confirmed == 1 ? true : false;
        };

        // Compare passwords
        $scope.passwordDoNotMatchAvatar = function() {
            if($scope.avatar.password != $scope.avatar.password2) {
                jQuery('#inputAvatarPassword, #inputAvatarPassword2').parents('div.form-group').addClass('has-error');
            } else {
                jQuery('#inputAvatarPassword, #inputAvatarPassword2').parents('div.form-group').removeClass('has-error');
            }
        };

        // Compare passwords
        $scope.passwordDoNotMatchUser = function() {
            if($scope.user.password != $scope.user.password2) {
                jQuery('#inputChangePassword, #inputChangePassword2').parents('div.form-group').addClass('has-error');
            } else {
                jQuery('#inputChangePassword, #inputChangePassword2').parents('div.form-group').removeClass('has-error');
            }
        };

        // Check to see if creation is enabled on this grid
        $scope.createEnabled = function() {
            for(var i = 0; i < $scope.grids.length; i++) {
                if($scope.grids[i].id == $scope.avatar.gridId) {
                    if($scope.grids[i].isOnline == 0) {
                        $alert({title: 'Error!', content: $sce.trustAsHtml('The selected Grid ('+ $scope.grids[i].name +') is offline, therefore it is not possible to create an avatar for the selected Grid.'), type: 'danger'});
                    } else if($scope.grids[i].remoteAdmin.url == null) {
                        $alert({title: 'Error!', content: $sce.trustAsHtml('The selected Grid\'s ('+ $scope.grids[i].name +') remoteadmin is not configured, therefore it is not possible to create an avatar for the selected Grid.'), type: 'danger'});
                    } else {
                        $alert({title: 'Info!', content: $sce.trustAsHtml('Avatars can be created on the selected grid ('+ $scope.grids[i].name +').'), type: 'info'});
                    }
                    return true;
                }
            }
        };

        // Dialog function handler
        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            } else if(func == 'createAvatar') {
                $scope.saveAvatar();
            } else if(func == 'changePassword') {
                $scope.changePassword();
            }
        };

        // Open the form for creating a new avatar
        $scope.newAvatar = function() {
            $scope.template         = partial_path +'/user/userNewAvatarForm.html';
            $scope.avatar           = {};
            $scope.formSubmit       = 'createAvatar';
            $scope.buttons          = [{
                        text: 'Create',
                        func: '',
                        class: 'primary',
                        type: 'submit'
                    },
                    {
                        text: 'Cancel',
                        func: 'hide',
                        class: 'danger',
                        type: 'button'
                    }
                ];
            modal                   = $modal({scope: $scope, template: 'templates/restangular/html/bootstrap/modalDialogTemplate.html'});

            // Get additional information about the Grids
            RestangularCache.all('grids').getList().then(function(gridsResponse) {
                $scope.grids    = gridsResponse;
            });
        };

        // Save the avatar
        $scope.saveAvatar = function() {
            // Show loading screen
            jQuery('#loading').show();

            // Create the avatar
            Restangular.one('grid', $scope.avatar.gridId).all('avatar').post($scope.avatar).then(function(avatarCreateResponse) {
                if(!avatarCreateResponse.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(avatarCreateResponse.error), type: 'danger'});
                } else {
                    $alert({title: 'Avatar created!', content: $sce.trustAsHtml('The avatar '+ $scope.avatar.firstName +' '+ $scope.avatar.lastName +' has been created.'), type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);

                    // Link the avatar to this user
                    Restangular.one('grid', $scope.avatar.gridId).one('avatar', avatarCreateResponse.avatar_uuid).post('', {username: $scope.user.username}).then(function(avatarLinkResponse) {
                        if(!avatarLinkResponse.success) {
                            $alert({title: 'Error!', content: $sce.trustAsHtml(avatarLinkResponse.error), type: 'danger'});
                        } else {
                            $alert({title: 'Avatar linked!', content: $sce.trustAsHtml('The avatar '+ $scope.avatar.firstName +' '+ $scope.avatar.lastName +' has been linked to this user.'), type: 'success'});
                        }
                    });
                    modal.hide();
                    $route.reload();
                }

                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Confirm the avatar
        $scope.confirmAvatar = function(index, avatar) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).put().then(function(confirmationResponse) {
                if(!confirmationResponse.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(confirmationResponse.error), type: 'danger'});
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    $alert({title: 'Avatar confirmed!', content: $sce.trustAsHtml('The avatar is confirmed user.'), type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                }

                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Unlinking the avatar
        $scope.unlinkAvatar = function(index, avatar) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('grid', avatar.gridId).one('avatar', avatar.uuid).remove().then(function(unlinkResponse) {
                if(!unlinkResponse.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(unlinkResponse.error), type: 'danger'});
                } else {
                    delete $scope.user.avatars[index];
                    $scope.user.avatarCount--;
                    $alert({title: 'Avatar unlinked!', content: $sce.trustAsHtml('The avatar is no longer linked to this user.'), type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };
    }]
);

