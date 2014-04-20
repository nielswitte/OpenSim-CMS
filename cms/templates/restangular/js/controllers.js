// MainController
function MainCntl($scope, $route, $routeParams, $location, Page) {
    $scope.$route       = $route;
    $scope.$location    = $location;
    $scope.$routeParams = $routeParams;
    $scope.Page         = Page;
};
/****************************************************************************************************************************************************
 *    _____ _           _
 *   / ____| |         | |
 *  | |    | |__   __ _| |_
 *  | |    | '_ \ / _` | __|
 *  | |____| | | | (_| | |_
 *   \_____|_| |_|\__,_|\__|
 *
 */
// chatController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('chatController', ['Restangular', 'RestangularCache', '$scope', '$aside', '$timeout', '$alert', function(Restangular, RestangularCache, $scope, $aside, $timeout, $alert) {
        $scope.grids          = [];
        var lastMsgTimestamp;
        $scope.chats          = [];
        $scope.minimizedChat  = false;
        var autoScroll        = true;
        var showChatButton    = true;
        var timer;
        var chatAside;
        var selectedGridId;
        var updateInterval    = 2000;

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
                    timer = setInterval(updateChat, updateInterval);
                }
            }

            // Get a list of grids
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
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                }
            });
        };

        // Load chat for the selected Grid
        $scope.selectGrid = function(gridId) {
            // Clear existing timers
            clearInterval(timer);
            // Set the grid
            selectedGridId   = gridId;
            // Empty chat when switching grids and show message chat enabled
            $scope.chats     = [{
                    timestamp: moment().format('YYYY-MM-DD HH:mm:ss'),
                    fromCMS: 0,
                    user: {
                        id: 0,
                        firstName: 'Server',
                        lastName: ''
                    },
                    message: 'Chat enabled'
            }];
            // Reset last msg timestmap
            lastMsgTimestamp = moment().subtract('minutes', 30).unix();
            // Get last chat entries for past one hour
            updateChat();
            // Set autoscroll to true (overwrites it when switching grid)
            autoScroll       = true;
            // Start auto refreshing chat
            timer            = setInterval(updateChat, updateInterval);
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
                timer            = setInterval(updateChat, updateInterval);
            } else {
                // Change update to 5 seconds when in background
                clearInterval(timer);
                timer            = setInterval(updateChat, updateInterval*5);
            }
        };
    }]
);


/****************************************************************************************************************************************************
 *   _____                                     _
 *  / ____|                                   | |
 * | |     ___  _ __ ___  _ __ ___   ___ _ __ | |_ ___
 * | |    / _ \| '_ ` _ \| '_ ` _ \ / _ \ '_ \| __/ __|
 * | |___| (_) | | | | | | | | | | |  __/ | | | |_\__ \
 *  \_____\___/|_| |_| |_|_| |_| |_|\___|_| |_|\__|___/
 *
 */
// chatController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('commentsController', ['Restangular', '$scope', '$sce', '$route', '$alert', 'Cache', function(Restangular, $scope, $sce, $route, $alert, Cache) {
        $scope.token = sessionStorage.token;

        // Clear the comment form
        $scope.clearComment = function() {
            $scope.comment = {
                user: {
                    id: sessionStorage.id
                },
                type: $scope.commentType,
                itemId: $scope.commentItemId,
                parentId: 0,
                message: ''
            };
            $scope.replyTo = '';
        };

        // Clear comment form on init
        $scope.clearComment();

        // Removes a comment from the database
        $scope.deleteComment = function(id) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('comment', id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'Comment removed!', content: 'The comment with ID '+ id +' has been removed from the CMS.', type: 'success'});
                    Cache.clearCache();
                    $route.reload();
                }

                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // User has sufficient permissions to add comment?
        $scope.allowComments = function() {
            if(sessionStorage.commentPermission >= EXECUTE) {
                return true;
            } else {
                return false;
            }
        };

        // Create new comment
        $scope.newComment = function(id) {
            // Scroll to textarea
            jQuery('body').scrollTop(jQuery('#commentForm').offset().top);
            // Set quote text
            if(id > 0) {
                $scope.comment.parentId = id;
                $scope.replyTo          = jQuery('#comment-'+ id +' header a').text();
                var message  = "> **"+ $scope.replyTo;
                message     += " @ "+ jQuery('#comment-'+ id +' header time').text() +"**  \n";
                message     += "> "+ jQuery('#comment-'+ id +' span.message').data('message').replace(/\n/g, "\n> ");
                message     += "\n\n";

                // Strip dubbel quoted messages, max for 250 lines
                var count       = 0;
                var dubbelQoute = message.indexOf("\n> > ");
                while(dubbelQoute > -1 && count < 250) {
                    var endOfLine   = message.indexOf("\n", dubbelQoute + 5);
                    // First occurenace replace with "[...]"
                    if(count == 0) {
                        message     = message.slice(0, dubbelQoute) +"\n> [...]"+ message.slice(endOfLine);
                    } else {
                        message     = message.slice(0, dubbelQoute) + message.slice(endOfLine);
                    }

                    dubbelQoute     = message.indexOf("\n> >");
                    count++;
                }
                // Update scope
                $scope.comment.message = message;
            }
            // put cursor in textarea
            jQuery('#commentForm textarea').focus();
        };

        // Submits the new comment to the API
        $scope.commentSubmit = function() {
            // Show loading screen
            jQuery('#loading').show();

            // Post comment
            Restangular.one('comment', $scope.commentType).all($scope.comment.itemId).post($scope.comment).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'Comment added!', content: 'Comment has been posted with ID: '+ resp.commentId +'.', type: 'success'});
                    Cache.clearCache();
                    $route.reload();
                }
            });

            // Remove loading screen
            jQuery('#loading').hide();
        };

        // Is the update form visible?
        var updateForm = 0;
        $scope.showUpdateForm = function(id) {
            return updateForm === id;
        };

        // Sets the update form
        $scope.editComment = function(id) {
            updateForm     = id;
            $scope.message = jQuery('#commentUpdate-'+ id).data('message');
            jQuery('#commentUpdate-'+ id).val($scope.message);
        };

        // Hides the form and resets the message
        $scope.updateCommentReset = function() {
            $scope.message  = '';
            updateForm      = 0;
        };

        // Update a comment
        $scope.updateComment = function(id) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('comment', id).customPUT({message: this.message}).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'Comment updated!', content: 'Comment with ID: '+ id +' has been updated.', type: 'success'});
                    $scope.updateCommentReset();
                    Cache.clearCache();
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });

        };

        // Check if the user is allowed to update this comment
        $scope.allowCommentUpdate = function(userId) {
            return $scope.allowCommentDelete(userId);
        };

        // Checks if the user has permission to remove a comment
        $scope.allowCommentDelete = function(userId) {
            // Read permissions or higher and own comment?
            if(sessionStorage.id == userId && sessionStorage.commentPermission >= READ) {
                return true;
            // Write permission
            } else if(sessionStorage.commentPermission >= WRITE) {
                return true;
            // Insufficient permissions
            } else {
                return false;
            }
        };

        // Is the comment posted after the previous login of the user?
        $scope.isNewComment = function(timestamp) {
            return new moment(timestamp, 'YYYY-MM-DD HH:mm:ss').unix() > moment(sessionStorage.lastLogin, 'YYYY-MM-DD HH:mm:ss').unix() ? 'highlighted' : '';
        };

        // Trust the message as safe markdown html
        $scope.markdown = function(message) {
            return $sce.trustAsHtml(markdown.toHTML(''+ message));
        };

        // Toggle MarkDown help
        var showMDHelp = false;
        $scope.toggleMDHelp = function() {
            showMDHelp = !showMDHelp;
        };

        // Show or hide MarkDown Help
        $scope.showMDHelp = function() {
            return showMDHelp;
        };
    }]
);
/****************************************************************************************************************************************************
 *  _____            _             _   _
 * |  __ \          (_)           | | (_)
 * | |__) |_ _  __ _ _ _ __   __ _| |_ _  ___  _ __
 * |  ___/ _` |/ _` | | '_ \ / _` | __| |/ _ \| '_ \
 * | |  | (_| | (_| | | | | | (_| | |_| | (_) | | | |
 * |_|   \__,_|\__, |_|_| |_|\__,_|\__|_|\___/|_| |_|
 *              __/ |
 *             |___/
 */
// paginationController -----------------------------------------------------------------------------------------------------------------------------
angularRest.controller('paginationController', ['RestangularCache', '$scope', '$sce', '$route', '$alert', 'Cache', function(RestangularCache, $scope, $sce, $route, $alert, Cache) {
        $scope.pagination = $scope.$parent.pagination;

        // Add previous and current page to pagination
        for(var i = 1; i <= $scope.pagination.start; i++) {
            $scope.pagination.pages.push({page: i});
        }

        var getNextPaginationList = function(offset) {
            RestangularCache.one($scope.pagination.type, offset).get().then(function(resp) {
                if(resp.length > 0) {
                    var pageNr = $scope.pagination.pages.length + 1;
                    $scope.pagination.pages.push({page: pageNr});
                }
                if(resp.length >= $scope.pagination.perPage) {
                    getNextPaginationList(offset + $scope.pagination.perPage);
                }
            });
        };

        // Load the next page
        getNextPaginationList($scope.pagination.start * $scope.pagination.perPage);
    }]
);

/****************************************************************************************************************************************************
 *   _    _
 *  | |  | |
 *  | |__| | ___  _ __ ___   ___
 *  |  __  |/ _ \| '_ ` _ \ / _ \
 *  | |  | | (_) | | | | | |  __/
 *  |_|  |_|\___/|_| |_| |_|\___|
 *
 */
// homeController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('homeController', ['Page', function(Page) {
        Page.setTitle('Home');
    }]
);

/****************************************************************************************************************************************************
 *   _                 _
 *  | |               (_)
 *  | |     ___   __ _ _ _ __
 *  | |    / _ \ / _` | | '_ \
 *  | |___| (_) | (_| | | | | |
 *  |______\___/ \__, |_|_| |_|
 *                __/ |
 *               |___/
 *
 */
// loginController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('loginController', ['Restangular', 'RestangularCache', '$scope', '$alert', 'Cache', function(Restangular, RestangularCache, $scope, $alert, Cache) {
        $scope.isLoggedIn = false;

        // Check login
        $scope.isLoggedInCheck = function() {
            if(sessionStorage.token && sessionStorage.id) {
                $alert({title: 'Already logged in!', content: 'You are already logged in as '+ sessionStorage.username, type: 'warning'});
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

            var auth = Restangular.one('auth').post('user', user).then(function(authResponse) {
                // Successful auth?
                if(authResponse.token) {
                    var user = Restangular.one('user', authResponse.userId).get({ token: authResponse.token }).then(function(userResponse) {
                        // Store basic user data
                        sessionStorage.username     = userResponse.username;
                        sessionStorage.email        = userResponse.email;
                        sessionStorage.id           = userResponse.id;
                        sessionStorage.firstName    = userResponse.firstName;
                        sessionStorage.lastName     = userResponse.lastName;
                        sessionStorage.lastLogin    = authResponse.lastLogin;

                        // Store permissions
                        sessionStorage.authPermission          = userResponse.permissions.auth;
                        sessionStorage.chatPermission          = userResponse.permissions.chat;
                        sessionStorage.commentPermission       = userResponse.permissions.comment;
                        sessionStorage.documentPermission      = userResponse.permissions.document;
                        sessionStorage.filePermission          = userResponse.permissions.file;
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
                        $alert({title: 'Logged In!', content: 'You are now logged in as '+ userResponse.username +'. Your previous authentication was on '+ authResponse.lastLogin, type: 'success'});
                        // Remove all cached items (if any)
                        Cache.clearCache();
                        // Back to previous page
                        window.history.back();
                    });
                // Failed auth
                } else {
                    sessionStorage.clear();
                    $alert({title: 'Error!', content: authResponse.error +'.', type: 'danger'});
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };
    }]
);
/****************************************************************************************************************************************************
 *   _______          _ _
 *  |__   __|        | | |
 *     | | ___   ___ | | |__   __ _ _ __
 *     | |/ _ \ / _ \| | '_ \ / _` | '__|
 *     | | (_) | (_) | | |_) | (_| | |
 *     |_|\___/ \___/|_|_.__/ \__,_|_|
 *
 */
// toolbarController --------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('toolbarController', ['$scope', 'Cache', '$location', '$alert', function($scope, Cache, $location, $alert) {
        $scope.currentLocation = $location.path();

        // Handle logout events
        $scope.logout = function() {
            sessionStorage.clear();
            Cache.clearCache();
            $alert({title: 'Logged Out!', content: 'You are now logged out', type: 'success'});
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
            jQuery('#bs-navbar').on('click', 'a[href^="#!"]', function() {
                jQuery('#bs-navbar').addClass('collapse');
            });
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
/****************************************************************************************************************************************************
 *  _____            _     _                         _
 * |  __ \          | |   | |                       | |
 * | |  | | __ _ ___| |__ | |__   ___   __ _ _ __ __| |
 * | |  | |/ _` / __| '_ \| '_ \ / _ \ / _` | '__/ _` |
 * | |__| | (_| \__ \ | | | |_) | (_) | (_| | | | (_| |
 * |_____/ \__,_|___/_| |_|_.__/ \___/ \__,_|_|  \__,_|
 *
 */
// dashboardController ------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('dashboardController', ['RestangularCache', '$scope', 'Page', '$sce', '$location', function(RestangularCache, $scope, Page, $sce, $location) {
        $scope.files     = [];
        $scope.meetings  = [];
        $scope.comments  = { comments: [] };
        $scope.token     = sessionStorage.token;
        Page.setTitle('Dashboard');

        // Get last login time or use yesterday as time
        var lastLogin = function() {
            if(sessionStorage.lastLogin > 0) {
                return moment(sessionStorage.lastLogin, 'YYYY-MM-DD HH:mm:ss').unix();
            } else {
                return new moment().subtract('days', 1).unix();
            }
        };

        // Load all comments since the user last visited
        RestangularCache.one('comments', lastLogin()).get().then(function(commentsResponse) {
            $scope.comments = commentsResponse;
        });

        // Load all files
        RestangularCache.all('files').getList().then(function(filesResponse) {
            $scope.files = filesResponse;
        });

        // Load all meetings the user is a participant for
        RestangularCache.one('user', sessionStorage.id).getList('meetings').then(function(meetingsResponse) {
            $scope.meetings = meetingsResponse;
        });

        // Calculate the total number of pages
        $scope.getTotalPages = function(type) {
            var pages = 0;
            if(type == 'comments' && $scope.comments.comments !== undefined) {
                pages = Math.ceil(parseInt($scope.comments.comments.length) / parseInt(stepSizeComments));
            } else if(type == 'files') {
                pages = Math.ceil(parseInt($scope.files.length) / parseInt(stepSizeFiles));
            } else if(type == 'meetings') {
                pages = Math.ceil(parseInt($scope.meetings.length) / parseInt(stepSizeMeetings));
            }

            // Always show at least one page
            if(pages == 0) {
                pages = 1;
            }

            return $sce.trustAsHtml(''+ pages);
        };

        // Get the current page number
        $scope.getCurrentPage = function(type) {
            var page = 1;
            if(type == 'comments') {
                page = Math.ceil(parseInt($scope.commentOffset) / parseInt(stepSizeComments)) + 1;
            } else if(type == 'files') {
                page = Math.ceil(parseInt($scope.fileOffset) / parseInt(stepSizeFiles)) + 1;
            } else if(type == 'meetings') {
                page = Math.ceil(parseInt($scope.meetingOffset) / parseInt(stepSizeMeetings)) + 1;
            }

            return $sce.trustAsHtml(''+ page);
        };


        // Comment offsets
        $scope.commentOffset = 0;
        var stepSizeComments = 4;
        // File offsets
        $scope.fileOffset = 0;
        var stepSizeFiles = 6;
        // Meeting offsets
        $scope.meetingOffset = 0;
        var stepSizeMeetings = 8;

        // Set offset
        $scope.setOffset = function(type, next) {
            if(type == 'comments') {
                if(next) {
                    var newOffset = parseInt($scope.commentOffset) + parseInt(stepSizeComments);
                    if(newOffset >= $scope.comments.comments.length) {
                        newOffset = $scope.comments.comments.length - parseInt(stepSizeComments);
                        if(newOffset < 0) {
                            newOffset = 0;
                        }
                    }

                    $scope.commentOffset = newOffset;
                } else {
                    var newOffset = parseInt($scope.commentOffset) - parseInt(stepSizeComments);
                    if(newOffset < 0) {
                        newOffset = 0;
                    }
                    $scope.commentOffset = newOffset;
                }
            } else if(type == 'files') {
                if(next) {
                    var newOffset = parseInt($scope.fileOffset) + parseInt(stepSizeFiles);
                    if(newOffset >= $scope.files.length) {
                        newOffset = $scope.files.length - parseInt(stepSizeFiles);
                        if(newOffset < 0) {
                            newOffset = 0;
                        }
                    }

                    $scope.fileOffset = newOffset;
                } else {
                    var newOffset = parseInt($scope.fileOffset) - parseInt(stepSizeFiles);
                    if(newOffset < 0) {
                        newOffset = 0;
                    }
                    $scope.fileOffset = newOffset;
                }
            } else {
                if(next) {
                    var newOffset = parseInt($scope.meetingOffset) + parseInt(stepSizeMeetings);
                    if(newOffset >= $scope.meetings.length) {
                        newOffset = $scope.meetings.length - parseInt(stepSizeMeetings);
                        if(newOffset < 0) {
                            newOffset = 0;
                        }
                    }
                    $scope.meetingOffset = newOffset;
                } else {
                    var newOffset = parseInt($scope.meetingOffset) - parseInt(stepSizeMeetings);
                    if(newOffset < 0) {
                        newOffset = 0;
                    }
                    $scope.meetingOffset = newOffset;
                }
            }
        };

        // Get starting point
        $scope.getFrom = function(type) {
            if(type == 'comments') {
                return $scope.commentOffset;
            } else if(type == 'files') {
                return $scope.fileOffset;
            } else {
                return $scope.meetingOffset;
            }
        };

        // Get ending point
        $scope.getTo = function(type) {
            if(type == 'comments') {
                return parseInt($scope.commentOffset) + parseInt(stepSizeComments);
            } else if(type == 'files') {
                return parseInt($scope.fileOffset) + parseInt(stepSizeFiles);
            } else {
                return parseInt($scope.meetingOffset) + parseInt(stepSizeMeetings);
            }
        };

        // Check to see if the current page is the last page
        $scope.isLastPage = function(type) {
            return parseInt($scope.getTotalPages(type)) == parseInt($scope.getCurrentPage(type));
        };

        // Check to see if the current page is the first page
        $scope.isFirstPage = function(type) {
            return parseInt($scope.getCurrentPage(type)) == 1;
        };

        // Option to convert the timestamp to the given moment.js format
        $scope.convertTimestamp = function(timestamp, format) {
            return new moment(timestamp, 'YYYY-MM-DD HH:mm:ss').format(format);
        };

        // Determine if the given timestamp is in the future
        $scope.inFuture = function(timestamp) {
            return new moment(timestamp, 'YYYY-MM-DD HH:mm:ss').unix() > new moment().unix();
        };

        // Trust the message as safe markdown html
        $scope.markdown = function(message) {
            return $sce.trustAsHtml(markdown.toHTML(''+ message));
        };

        // Returns the list with the full path to the comment in context
        $scope.showComment = function(id) {
            RestangularCache.one('comment', id).one('parents').get().then(function(parentsResponse) {
                if(parentsResponse[0] == 'presentation' || parentsResponse[0] == 'document' || parentsResponse[0] == 'file') {
                    if(parentsResponse[2] == 'slide') {
                        $location.path('document/'+ parentsResponse[1] +'/slide/'+ parentsResponse[3]);
                    } else if(parentsResponse[2] == 'page') {
                        $location.path('document/'+ parentsResponse[1] +'/page/'+ parentsResponse[3]);
                    } else {
                        $location.path('document/'+ parentsResponse[1]);
                    }
                } else {
                    $location.path(parentsResponse[0] +'/'+ parentsResponse[1]);
                }
            });
        };
    }]
);
/****************************************************************************************************************************************************
 *   _____                                        _
 *  |  __ \                                      | |
 *  | |  | | ___   ___ _   _ _ __ ___   ___ _ __ | |_ ___
 *  | |  | |/ _ \ / __| | | | '_ ` _ \ / _ \ '_ \| __/ __|
 *  | |__| | (_) | (__| |_| | | | | | |  __/ | | | |_\__ \
 *  |_____/ \___/ \___|\__,_|_| |_| |_|\___|_| |_|\__|___/
 *
 */
// documentsController ------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('documentsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$alert', '$modal', 'Cache', '$route', '$routeParams', '$location',
    function(Restangular, RestangularCache, $scope, Page, $alert, $modal, Cache, $route, $routeParams, $location) {
        $scope.orderByField         = 'title';
        $scope.reverseSort          = false;
        var requestDocumentsUrl     = '';
        $scope.documentsList        = [];
        $scope.types                = ['document', 'image', 'presentation'];

        // For loading more than the first page
        var paginationOffset = 1;
        var perPage          = 50;
        if($routeParams.paginationPage !== undefined) {
            paginationOffset = $routeParams.paginationPage;
        }

        // Show pagination and set the type
        $scope.showPagination = function() {
            if($scope.documentsList.length >= perPage || paginationOffset > 1) {
                $scope.pagination = {
                    type: 'files',
                    url: 'documents',
                    perPage: perPage,
                    start: paginationOffset,
                    pages: []
                };
                return 'templates/restangular/html/bootstrap/pagination.html';
            } else {
                return false;
            }
        };

        // Show loading screen
        jQuery('#loading').show();

        // Get a list with documents
        RestangularCache.one('files', (paginationOffset - 1) * perPage).getList().then(function(documentsResponse) {
            $scope.documentsList = documentsResponse;
            Page.setTitle('Files');
            requestDocumentsUrl = documentsResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });


        // Search for the given document title
        var documentSearchResults = [];
        $scope.documentBySearch   = '';
        $scope.getDocumentByTitle = function($viewValue) {
            var results = '';
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('files', $viewValue).get().then(function(documentsResponse) {
                    documentSearchResults = documentsResponse;
                    return documentsResponse;
                });
            }
            return results;
        };

        // When selecting a document
        $scope.selectDocument = function() {
            for(var i = 0; i < documentSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(documentSearchResults[i].title == $scope.documentBySearch) {
                    $location.path('document/'+ documentSearchResults[i].id);
                }
            }
        };

        // Show/hide filters
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
                $alert({title: 'Error!', content: 'Processing file failed.', type: 'danger'});
            };
        });

        // Save the new document
        function saveDocument() {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.all('file').post($scope.document).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'File created!', content: 'The file: '+ $scope.document.title + ' has been created with ID: '+ resp.id +'.', type: 'success'});
                    $scope.document.id                  = resp.id;
                    $scope.document.ownerId             = sessionStorage.id;
                    $scope.document.creationDate        = new moment().format('YYYY-MM-DD HH:mm:ss');
                    $scope.document.modificationDate    = new moment().format('YYYY-MM-DD HH:mm:ss');
                    $scope.documentsList.push($scope.document);

                    Cache.clearCachedUrl(requestDocumentsUrl);
                    modal.hide();
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Check if a user is allowed to create a new document
        $scope.allowCreate = function() {
            return sessionStorage.filePermission >= EXECUTE;
        };

        // Show delete button only when allowed to delete
        $scope.allowDelete = function(ownerId) {
            if(ownerId == sessionStorage.id && sessionStorage.filePermission >= EXECUTE) {
                return true;
            } else if(sessionStorage.filePermission >= WRITE) {
                return true;
            } else {
                return false;
            }
        };

        // Get document index by ID
        var getDocumentIndexById = function(id) {
            for(var i = 0; i < $scope.documentsList.length; i++) {
                if($scope.documentsList[i].id == id) {
                    return i;
                }
            }
            return false;
        };

        // Remove a document
        $scope.deleteDocument = function(id) {
            // Show loading screen
            jQuery('#loading').show();

            // Remove document by ID
            Restangular.one('file', id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    var index = getDocumentIndexById(id);
                    if(index !== false) {
                        $alert({title: 'Document removed!', content: 'The document (#'+ $scope.documentsList[index].id +') '+ $scope.documentsList[index].title +' has been removed from the CMS.', type: 'success'});
                        $scope.documentsList.splice(index, 1);
                    } else {
                        $alert({title: 'Document removed!', content: 'The document has been removed from the CMS. However, some unexpected events happend. Check if everything is still OK!', type: 'success'});
                    }
                    Cache.clearCachedUrl(requestDocumentsUrl);
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Clear expired cache
        $scope.clearExpiredCache = function() {
            Restangular.one('files', 'cache').remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: 'No expired cache items to clear. Try again later.', type: 'danger'});
                } else {
                    $alert({title: 'Cache cleared!', content: 'Cleared all expired items from the cache.', type: 'success'});
                }
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
angularRest.controller('documentController', ['RestangularCache', '$scope', '$routeParams', 'Page', '$modal', '$alert', '$location',
    function(RestangularCache, $scope, $routeParams, Page, $modal, $alert, $location) {
        var groupSearchResults;
        $scope.groupname        = '';

        // Show loading screen
        jQuery('#loading').show();
        // List with comments
        $scope.comments = {
            comments: [],
            commentCount: 0
        };

        // Load comments
        RestangularCache.all('comments').one('file', $routeParams.documentId).get().then(function(commentResponse) {
            $scope.comments = commentResponse;
        });

        // Show comments and set the comment Type to: meeting and the id of the meeting
        $scope.showComments = function() {
            $scope.commentType      = 'file';
            $scope.commentItemId    = $routeParams.documentId;
            return partial_path +'/comment/commentContainer.html';
        };

        // Get document from API
        RestangularCache.one('file', $routeParams.documentId).get().then(function(documentResponse) {
            // Error occured?
            if(documentResponse.error) {
                $alert({title: 'Error!', content: documentResponse.error, type: 'danger'});
                // Back to documents overview page
                $location.path('documents');
            } else {
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
            }
            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Loads the image when document details have loaded
        $scope.getDocumentImage = function() {
            if($scope.document !== undefined && $scope.document.type == 'image') {
                return $scope.document.url +'image/?token='+ sessionStorage.token;
            } else {
                return '';
            }
        };

        // Open dialog with the Slide preview
        $scope.lightbox = function(number, url) {
            $modal({
                title: 'Preview '+ number,
                content: '<img src="'+ url+'?token='+ sessionStorage.token +'" class="img-responsive">',
                html: true,
                show: true,
                template: 'templates/restangular/html/bootstrap/modalDialogBasic.html',
                scope: $scope
            });
        };

        // Search for the given group
        $scope.getGroupsByName = function($viewValue) {
            var results = '';
            $scope.groupname = $viewValue;
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('groups', $viewValue).get().then(function(groupsResponse) {
                    groupSearchResults = groupsResponse;
                    return groupsResponse;
                });
            }
            return results;
        };

        // Adds the currently selected group to the list
        $scope.addGroup = function() {
            for(var i = 0; i < groupSearchResults.length; i++) {
                console.log($scope.groupname);
                // Only add user when match found and not already listed
                if(groupSearchResults[i].name == $scope.groupname) {
                    if(!isDuplicateGroup()) {
                        $scope.document.groups.push(groupSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The document is already a member of the group '+ groupSearchResults[i].name, type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate groups
        function isDuplicateGroup() {
            for(var i = 0; i < $scope.document.groups.length; i++) {
                if($scope.document.groups[i].name == $scope.groupname) {
                    return true;
                }
            }
            return false;
        };

        // Removes the group with the given id the list
        $scope.removeGroup = function(groupId) {
            for(var i = 0; i < $scope.document.groups.length; i++) {
                if($scope.document.groups[i].id == groupId) {
                    $scope.document.groups.splice(i, 1);
                    return true;
                }
            }
            return false;
        };

        // Actions performed when updating share information
        $scope.shareDocument = function() {

        };

        // Dialog function handler
        $scope.call = function(func) {
            if(func == 'hide') {
                modal.hide();
            } else if(func == 'shareDocument') {
                shareDocument();
            }
        };

        // Share document options
        $scope.showShareOptions = function() {
            $scope.template         = partial_path +'/document/documentShareForm.html';
            $scope.formSubmit       = 'shareDocument';
            $scope.share            = [];
            $scope.buttons          = [{
                        text: 'Share',
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
/****************************************************************************************************************************************************
 *    _____      _     _
 *   / ____|    (_)   | |
 *  | |  __ _ __ _  __| |___
 *  | | |_ | '__| |/ _` / __|
 *  | |__| | |  | | (_| \__ \
 *   \_____|_|  |_|\__,_|___/
 *
 */
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
angularRest.controller('gridController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', '$route', '$alert', 'Page', 'Cache', function(Restangular, RestangularCache, $scope, $routeParams, $route, $alert, Page, Cache) {
        var gridRequestUrl;

        // Show loading screen
        jQuery('#loading').show();

        // Load grid information
        RestangularCache.one('grid', $routeParams.gridId).get().then(function(gridResponse) {
            Page.setTitle(gridResponse.name);
            $scope.grid = gridResponse;
            // Token required to request grid images
            $scope.api_token = sessionStorage.token;

            // Save request URL
            gridRequestUrl = gridResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Encode URLs
        $scope.urlEncode = function(target){
            return encodeURIComponent(target);
        };

        // Check if the user is allowed to update the Grid
        $scope.allowUpdate = function() {
            return sessionStorage.gridPermission >= EXECUTE;
        };

        // Update grid from API
        $scope.updateGrid = function() {
            Cache.clearCachedUrl(gridRequestUrl);
            Restangular.one('grid', $routeParams.gridId).one('opensim').post().then(function(gridResponse) {
                 if(!gridResponse.success) {
                    $alert({title: 'Error!', content: gridResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'Grid updated!', content: 'The grid data has been updated with the information available from OpenSim. (You may need to refresh the page to see the changes)', type: 'success'});
                    $route.reload();
                }
            });
        };

        // Update grid data from API
        $scope.updateGridRegions = function() {
            Cache.clearCachedUrl(gridRequestUrl);
            Restangular.one('grid', $routeParams.gridId).one('regions').post().then(function(regionsResponse) {
                 if(!regionsResponse.success) {
                    $alert({title: 'Error!', content: regionsResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'Regions updated!', content: 'Changes are made to '+ regionsResponse.regionsUpdated +' regions. The others were already up-to-date. (You may need to refresh the page to see the changes)', type: 'success'});
                    $route.reload();
                }
            });
        };

        // Teleports the avatar of the user to the meeting location
        $scope.teleportUser = function(name) {
            var avatarFound = false;
            // Search for avatars from the currently logged in user
            Restangular.one('user', sessionStorage.id).get().then(function(userResponse) {
                for(var i = 0; i < userResponse.avatars.length; i++) {
                    // Avatar on grid and online?
                    if(userResponse.avatars[i].gridId == $scope.grid.id && userResponse.avatars[i].online == 1) {
                        avatarFound = true;
                        // Teleport the found avatar
                        Restangular.one('grid', $scope.grid.id).one('avatar', userResponse.avatars[i].uuid).customPUT({
                            posX: 128,
                            posY: 128,
                            posZ: 25,
                            regionName: name
                        }, 'teleport').then(function(teleportResponse) {
                            if(!teleportResponse.success) {
                                $alert({title: 'Error!', content: teleportResponse.error, type: 'danger'});
                                return false;
                            } else {
                                $alert({title: 'Teleported!', content: 'Avatar teleported to region: '+ name +' on grid '+ $scope.grid.name, type: 'success'});
                                return true;
                            }
                        });
                    }
                }
                // No match found?
                if(!avatarFound) {
                    $alert({title: 'No avatar found!', content: 'Currently there is no avatar online, linked to your user account, on this grid to teleport.', type: 'danger'});
                }
            });
            return false;
        };
    }]
);
/****************************************************************************************************************************************************
 *   __  __           _   _
 *  |  \/  |         | | (_)
 *  | \  / | ___  ___| |_ _ _ __   __ _ ___
 *  | |\/| |/ _ \/ _ \ __| | '_ \ / _` / __|
 *  | |  | |  __/  __/ |_| | | | | (_| \__ \
 *  |_|  |_|\___|\___|\__|_|_| |_|\__, |___/
 *                                 __/ |
 *                                |___/
 */
// meetingsController -------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingsController', ['RestangularCache', '$scope', 'Page', '$tooltip', '$sce',  function(RestangularCache, $scope, Page, $tooltip, $sce) {
        var date = new Date(new Date - (1000*60*60*24*14));
        Page.setTitle('Meetings');

        // Create the calendar
        var calendar = jQuery('#calendar').calendar({
            language:       'en-US',
            events_source:  [],
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


        // Toggle all or only own meetings
        $scope.showAllMeetings = false;
        $scope.toggleAllMeetings = function() {
            // toggle show all/show own
            $scope.showAllMeetings = !$scope.showAllMeetings;

            // Show loading screen
            jQuery('#loading').show();

            // Get all meetings
            if($scope.showAllMeetings) {
                // Get all meetings for the calendar
                RestangularCache.one('meetings', date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).getList('calendar').then(function(meetingsResponse) {
                    // Set to all meetings
                    calendar.setOptions({
                        events_source: meetingsResponse
                    });
                    calendar.view();

                    // Remove loading screen
                    jQuery('#loading').hide();
                });
            // Get own meetings
            } else {
                // Get all meetings for the calendar
                RestangularCache.one('user', sessionStorage.id).one('meetings', 'calendar').get().then(function(meetingsResponse) {
                    // Set to all meetings
                    calendar.setOptions({
                        events_source: meetingsResponse
                    });
                    calendar.view();

                    // Remove loading screen
                    jQuery('#loading').hide();
                });
            }
        };

        // Actually load the meetings
        $scope.toggleAllMeetings();

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
            room: {
                grid: {
                    id: 1
                }
            },
            creator: { id: -1 },
            participants: []
        };
        var meetingOld                  = {};
        $scope.grids                    = [];
        $scope.rooms                    = [];
        $scope.participant              = '';
        var usernameSearchResults       = [];
        $scope.document                 = '';
        var documentSearchResults       = [];
        // List with comments
        $scope.comments = {
            comments: [],
            commentCount: 0
        };

        // Load comments
        RestangularCache.all('comments').one('meeting', $routeParams.meetingId).get().then(function(commentResponse) {
            $scope.comments = commentResponse;
        });

        // Show comments and set the comment Type to: meeting and the id of the meeting
        $scope.showComments = function() {
            $scope.commentType      = 'meeting';
            $scope.commentItemId    = $routeParams.meetingId;
            return partial_path +'/comment/commentContainer.html';
        };

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

        // Teleports the avatar of the user to the meeting location
        $scope.teleportUser = function() {
            var avatarFound = false;
            // Search for avatars from the currently logged in user
            Restangular.one('user', sessionStorage.id).get().then(function(userResponse) {
                for(var i = 0; i < userResponse.avatars.length; i++) {
                    // Avatar on grid and online?
                    if(userResponse.avatars[i].gridId == $scope.meeting.room.grid.id && userResponse.avatars[i].online == 1) {
                        avatarFound = true;
                        // Teleport the found avatar
                        Restangular.one('grid', $scope.meeting.room.grid.id).one('avatar', userResponse.avatars[i].uuid).customPUT({
                            posX: $scope.meeting.room.coordinates.x,
                            posY: $scope.meeting.room.coordinates.y,
                            posZ: $scope.meeting.room.coordinates.z,
                            regionName: $scope.meeting.room.region.name
                        }, 'teleport').then(function(teleportResponse) {
                            $alert({title: 'Teleported!', content: 'Avatar teleported to '+ $scope.meeting.room.name +' in region: '+ $scope.meeting.room.region.name +' on grid '+ $scope.meeting.room.grid.name, type: 'success'});
                            return true;
                        });
                    }
                }
                // No match found?
                if(!avatarFound) {
                    $alert({title: 'No avatar found!', content: 'Currently there is no avatar online, linked to your user account, on this grid to teleport.', type: 'danger'});
                }
            });
            return false;
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
            RestangularCache.one('grid', $scope.meeting.room.grid.id).one('region', $scope.meeting.room.region.uuid).getList('rooms').then(function(roomsResponse){
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
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'Meeting updated!', content: 'The meeting has been updated.', type: 'success'});
                    Cache.clearCache();
                    $location.path('meeting/'+ $scope.meeting.id);
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
            $scope.startDateString  = new moment($scope.meeting.startDate, 'YYYY-MM-DD HH:mm:ss').format('YYYY/MM/DD');
            $scope.startTimeString  = new moment($scope.meeting.startDate, 'YYYY-MM-DD HH:mm:ss').format('HH:mm');
            $scope.endDateString    = new moment($scope.meeting.endDate, 'YYYY-MM-DD HH:mm:ss').format('YYYY/MM/DD');
            $scope.endTimeString    = new moment($scope.meeting.endDate, 'YYYY-MM-DD HH:mm:ss').format('HH:mm');
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

        // Search for the given documents
        $scope.getDocumentByTitle = function($viewValue) {
            var results = '';
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('files', $viewValue).get().then(function(documentsResponse) {
                    documentSearchResults = documentsResponse;
                    return documentsResponse;
                });
            }
            return results;
        };

        // Adds the currently selected documents to the list
        $scope.addDocument = function() {
            for(var i = 0; i < documentSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(documentSearchResults[i].title == $scope.document) {
                    if(!isDuplicateDocument()) {
                        $scope.meeting.documents.push(documentSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The document '+ documentSearchResults[i].title + ' is already added to this meeting', type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate documents
        function isDuplicateDocument() {
            for(var i = 0; i < $scope.meeting.documents.length; i++) {
                if($scope.meeting.documents[i].title == $scope.document) {
                    return true;
                }
            }
            return false;
        };

        // Removes the documents with the given ID from the list
        $scope.removeDocument = function(id) {
            for(var i = 0; i < $scope.meeting.documents.length; i++) {
                if($scope.meeting.documents[i].id == id) {
                    $scope.meeting.documents.splice(i, 1);
                    return true;
                }
            }
            return false;
        };

        // Search for the given username
        $scope.getUserByUsername = function($viewValue) {
            var results = '';
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('users', $viewValue).get().then(function(usersResponse) {
                    usernameSearchResults = usersResponse;
                    return usersResponse;
                });
            }
            return results;
        };

        // Adds the currently selected participant to the list
        $scope.addParticipant = function() {
            for(var i = 0; i < usernameSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(usernameSearchResults[i].username == $scope.participant) {
                    if(!isDuplicateParticipant()) {
                        $scope.meeting.participants.push(usernameSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The user '+ usernameSearchResults[i].username + ' is already a participant for this meeting', type: 'warning'});
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
            // Error occured?
            if(meetingResponse.error) {
                $alert({title: 'Error!', content: meetingResponse.error, type: 'danger'});
                // Back to meetings overview page
                $location.path('meetings');
            } else {
                $scope.meeting          = meetingResponse;
                angular.copy($scope.meeting, meetingOld);
                // Page and content titles
                $scope.title            = $sce.trustAsHtml(moment(meetingResponse.startDate).format('dddd H:mm') +' - Room '+ meetingResponse.room.id);
                Page.setTitle('Meeting '+ meetingResponse.name);
                meetingRequestUrl       = meetingResponse.getRequestedUrl();

                // Set the dates and times
                setDateTimes();

                // When not editing, reformat the agenda
                if($location.path().indexOf('/edit') == -1) {
                    $scope.meeting.agenda   = $sce.trustAsHtml(meetingResponse.agenda.replace(/\n/g, '<br>').replace(/\ /g, '&nbsp;'));
                // When editing, load additional information
                } else {
                    // Get additional information about the Grids
                    RestangularCache.all('grids').getList().then(function(gridsResponse) {
                        gridsRequestUrl = gridsResponse.getRequestedUrl();
                        $scope.grids    = gridsResponse;
                    });

                    // Get additional meeting rooms
                    $scope.getMeetingRooms();

                    // Load meetings on same day
                    var date = new moment().subtract('week', 2).format('YYYY-MM-DD');
                    Restangular.one('meetings', date).getList('calendar').then(function(meetingsResponse) {
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
                }
            }

            // Remove loading screen
            jQuery('#loading').hide();
        });
    }]
);

// meetingMinutesController -------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingMinutesController', ['RestangularCache', '$scope', '$routeParams', 'Page', '$sce', function(RestangularCache, $scope, $routeParams, Page, $sce) {
        $scope.meeting = {};
        RestangularCache.one('meeting', $routeParams.meetingId).one('minutes').get().then(function(meetingResponse) {
            meetingResponse.agenda = $sce.trustAsHtml(meetingResponse.agenda.replace(/\n/g, '<br>').replace(/\ /g, '&nbsp;'));
            $scope.meeting = meetingResponse;
            Page.setTitle('Minutes '+ meetingResponse.name);
        });

        // From the datetime string only show the time
        $scope.timeOnly = function(string) {
            return $sce.trustAsHtml(string.substr(11));
        };

        // Whether or not to show guests in the minutes
        $scope.showGuests = true;
        $scope.toggleGuests = function() {
            $scope.showGuests = !$scope.showGuests;
            return $scope.showGuests;
        };

        // Show heading?
        $scope.showAgendaNextItemHeading = function(index) {
            if(index == 0 || $scope.meeting.minutes[index].agenda.id != $scope.meeting.minutes[index-1].agenda.id) {
                return true;
            } else {
                return false;
            }
        };

        // Checks who said something
        $scope.labelClass = function(index) {
            var minute = $scope.meeting.minutes[index]
            // Current user is sender?
            if(minute.user !== undefined && minute.user.id == sessionStorage.id) {
                return 'success';
            // Server is sender?
            } else if(minute.name == 'Server') {
                return 'default';
            // Other
            } else {
                return 'info';
            }
        };

        // Parse the message for example to show voting results
        $scope.parseMessage = function(msg) {
            // Voting results?
            if(msg.substring(0, 17) == '[Voting Results] ') {
                var votes       = msg.substring(17).split(',');
                var totalVotes  = 0;
                for(var i = 0; i < votes.length; i++) {
                    totalVotes = (totalVotes + parseInt(votes[i]));
                }
                var html = '<strong>Votes: '+ totalVotes +'</strong><br>';
                html += '<div class="progress" title="Approved"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[0]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[0]) / totalVotes) * 100) +'%;">'+ parseInt(votes[0]) +'</div></div>';
                html += '<div class="progress" title="Rejected"><div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[1]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[1]) / totalVotes) * 100) +'%;">'+ parseInt(votes[1]) +'</div></div>';
                html += '<div class="progress" title="Blank"><div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[2]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[2]) / totalVotes) * 100) +'%;">'+ parseInt(votes[2]) +'</div></div>';
                html += '<div class="progress" title="None"><div class="progress-bar progress-bar-default" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[3]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[3]) / totalVotes) * 100) +'%;">'+ parseInt(votes[3]) +'</div></div>';
                return $sce.trustAsHtml(html);
            } else {
                return $sce.trustAsHtml(msg.replace(/\n/g,"<br>\n"));
            }
        };

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
                return '';
            }
        };
    }]
);

// meetingNewController -----------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingNewController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$location', '$alert', 'Cache', function(Restangular, RestangularCache, $scope, Page, $location, $alert, Cache) {
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
            agenda: '1. Opening\n',
            participants: [{
                    id: sessionStorage.id,
                    username: sessionStorage.username,
                    firstName: sessionStorage.firstName,
                    lastName: sessionStorage.lastName,
                    email: sessionStorage.email
            }],
            documents: []
        };
        $scope.grids                    = [];
        $scope.rooms                    = [];
        $scope.participant              = '';
        var usernameSearchResults       = [];
        $scope.document                 = '';
        var documentSearchResults       = [];

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
            RestangularCache.one('grid', $scope.meeting.room.grid.id).one('region', $scope.meeting.room.region.uuid).getList('rooms').then(function(roomsResponse){
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

        // Search for the given documents
        $scope.getDocumentByTitle = function($viewValue) {
            if($viewValue != null && $viewValue.length >= 3) {
                var results = RestangularCache.one('files', $viewValue).get().then(function(documentsResponse) {
                    documentSearchResults = documentsResponse;
                    return documentsResponse;
                });
            } else {
                var results = '';
            }
            return results;
        };

        // Adds the currently selected documents to the list
        $scope.addDocument = function() {
            for(var i = 0; i < documentSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(documentSearchResults[i].title == $scope.document) {
                    if(!isDuplicateDocument()) {
                        $scope.meeting.documents.push(documentSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The document '+ documentSearchResults[i].title + ' is already added to this meeting', type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate documents
        function isDuplicateDocument() {
            for(var i = 0; i < $scope.meeting.documents.length; i++) {
                if($scope.meeting.documents[i].title == $scope.document) {
                    return true;
                }
            }
            return false;
        };

        // Removes the documents with the given ID from the list
        $scope.removeDocument = function(id) {
            for(var i = 0; i < $scope.meeting.documents.length; i++) {
                if($scope.meeting.documents[i].id == id) {
                    $scope.meeting.documents.splice(i, 1);
                    return true;
                }
            }
            return false;
        };

        // Search for the given username
        $scope.getUserByUsername = function($viewValue) {
            if($viewValue != null && $viewValue.length >= 3) {
                var results = RestangularCache.one('users', $viewValue).get().then(function(usersResponse) {
                    usernameSearchResults = usersResponse;
                    return usersResponse;
                });
            } else {
                var results = '';
            }
            return results;
        };

        // Adds the currently selected participant to the list
        $scope.addParticipant = function() {
            for(var i = 0; i < usernameSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(usernameSearchResults[i].username == $scope.participant) {
                    if(!isDuplicateParticipant()) {
                        $scope.meeting.participants.push(usernameSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The user '+ usernameSearchResults[i].username + ' is already a participant for this meeting', type: 'warning'});
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
            jQuery('#loading').show();
            // Reformat back to the expected format for the API
            $scope.meeting.startDate   = $scope.startDateString.replace(/\//g, '-') +' '+ $scope.startTimeString +':00';
            $scope.meeting.endDate     = $scope.endDateString.replace(/\//g, '-') +' '+ $scope.endTimeString +':00';

            Restangular.all('meeting').post($scope.meeting).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'Meeting scheduled!', content: 'The meeting for '+ $scope.meeting.startDate +' has been created with ID: '+ resp.meetingId +'.', type: 'success'});
                    Cache.clearCache();
                    $location.path('meeting/'+ resp.meetingId);
                }
                jQuery('#loading').hide();
            });
        };

        // Show loading screen
        jQuery('#loading').show();

        // Load meetings on same day
        var date = new moment().format('YYYY-MM-DD');
        Restangular.one('meetings', date).getList('calendar').then(function(meetingsResponse) {
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

/****************************************************************************************************************************************************
 *  _____
 * |  __ \
 * | |__) |_ _  __ _  ___
 * |  ___/ _` |/ _` |/ _ \
 * | |  | (_| | (_| |  __/
 * |_|   \__,_|\__, |\___|
 *              __/ |
 *             |___/
 */
// pageController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('pageController', ['RestangularCache', '$scope', 'Page', '$routeParams', '$location', function(RestangularCache, $scope, Page, $routeParams, $location) {
        $scope.page = {};
        $scope.comments = {
            comments: [],
            commentCount: 0
        };
        $scope.currentPage = 0;
        $scope.documentId = $routeParams.documentId;

        // Get the page details
        RestangularCache.one('document', $routeParams.documentId).one('page', $routeParams.pageId).get().then(function(pageResponse) {
            $scope.page = pageResponse;
            Page.setTitle('Page '+ pageResponse.number);
            $scope.currentPage = pageResponse.number;

            if(pageResponse.hasComments !== false) {
                // Load comments
                RestangularCache.all('comments').one('page', pageResponse.id).get().then(function(commentResponse) {
                    $scope.comments = commentResponse;
                });
            }
        });

        // Show comments and set the comment Type to: page and the id of the page
        $scope.showComments = function() {
            $scope.commentType      = 'page';
            $scope.commentItemId    = $routeParams.pageId;
            return partial_path +'/comment/commentContainer.html';
        };

        // Convert page number to page id
        $scope.goToPageNumber = function(pageNumber) {
            var currentPageNumber  = parseInt($scope.page.number);
            var currentPageId      = parseInt($scope.page.id);

            if($scope.currentPage > pageNumber) {
                $scope.goToPageId(currentPageId - (currentPageNumber - pageNumber));
            } else {
                $scope.goToPageId(currentPageId + (pageNumber - currentPageNumber));
            }
        };

        // Go to specific page
        $scope.goToPageId = function(pageId) {
            $location.path('document/'+ $routeParams.documentId +'/page/'+ pageId);
        };

        // Go to the next page
        $scope.nextPage = function() {
            $scope.goToPageId(parseInt($routeParams.pageId) + 1);
        };

        // Go to the previous page
        $scope.previousPage = function() {
            $scope.goToPageId(parseInt($routeParams.pageId) - 1);
        };

        /**
         * Gets the page image with the token for this session
         * @returns {String}
         */
        $scope.getPageImage = function() {
            if($scope.page.image !== undefined) {
                return $scope.page.image +'?token='+ sessionStorage.token;
            } else {
                return '';
            }
        };
    }]
);

/****************************************************************************************************************************************************
 *    _____ _ _     _
 *   / ____| (_)   | |
 *  | (___ | |_  __| | ___  ___
 *   \___ \| | |/ _` |/ _ \/ __|
 *   ____) | | | (_| |  __/\__ \
 *  |_____/|_|_|\__,_|\___||___/
 *
 */
// slideController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('slideController', ['RestangularCache', '$scope', 'Page', '$routeParams', '$location', function(RestangularCache, $scope, Page, $routeParams, $location) {
        $scope.slide = {};
        $scope.comments = {
            comments: [],
            commentCount: 0
        };
        $scope.currentSlide = 0;
        $scope.documentId = $routeParams.documentId;

        // Get the slide details
        RestangularCache.one('presentation', $routeParams.documentId).one('slide', $routeParams.slideId).get().then(function(slideResponse) {
            $scope.slide = slideResponse;
            Page.setTitle('Slide '+ slideResponse.number);
            $scope.currentSlide = slideResponse.number;

            if(slideResponse.hasComments !== false) {
                // Load comments
                RestangularCache.all('comments').one('slide', slideResponse.id).get().then(function(commentResponse) {
                    $scope.comments = commentResponse;
                });
            }
        });

        // Show comments and set the comment Type to: slide and the id of the slide
        $scope.showComments = function() {
            $scope.commentType      = 'slide';
            $scope.commentItemId    = $routeParams.slideId;
            return partial_path +'/comment/commentContainer.html';
        };

        // Convert slide number to slide id
        $scope.goToSlideNumber = function(slideNumber) {
            var currentSlideNumber  = parseInt($scope.slide.number);
            var currentSlideId      = parseInt($scope.slide.id);

            if($scope.currentSlide > slideNumber) {
                $scope.goToSlideId(currentSlideId - (currentSlideNumber - slideNumber));
            } else {
                $scope.goToSlideId(currentSlideId + (slideNumber - currentSlideNumber));
            }
        };

        // Go to specific slide
        $scope.goToSlideId = function(slideId) {
            $location.path('document/'+ $routeParams.documentId +'/slide/'+ slideId);
        };

        // Go to the next slide
        $scope.nextSlide = function() {
            $scope.goToSlideId(parseInt($routeParams.slideId) + 1);
        };

        // Go to the previous slide
        $scope.previousSlide = function() {
            $scope.goToSlideId(parseInt($routeParams.slideId) - 1);
        };

        /**
         * Gets the slide image with the token for this session
         * @returns {String}
         */
        $scope.getSlideImage = function() {
            if($scope.slide.image !== undefined) {
                return $scope.slide.image +'?token='+ sessionStorage.token;
            } else {
                return '';
            }
        };
    }]
);

/****************************************************************************************************************************************************
 *   _    _
 *  | |  | |
 *  | |  | |___  ___ _ __ ___
 *  | |  | / __|/ _ \ '__/ __|
 *  | |__| \__ \  __/ |  \__ \
 *   \____/|___/\___|_|  |___/
 *
 */
// usersController ----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('usersController', ['RestangularCache', 'Restangular', '$scope', 'Page', '$modal', '$alert', 'Cache', '$route', '$routeParams', '$location',
    function(RestangularCache, Restangular, $scope, Page, $modal, $alert, Cache, $route, $routeParams, $location) {
        $scope.orderByField     = 'username';
        $scope.reverseSort      = false;
        var requestUsersUrl     = '';
        $scope.usersList        = [];

        // For loading more than the first page
        var paginationOffset = 1;
        var perPage          = 50;
        if($routeParams.paginationPage !== undefined) {
            paginationOffset = $routeParams.paginationPage;
        }

        // Show pagination and set the type
        $scope.showPagination = function() {
            if($scope.usersList.length >= perPage || paginationOffset > 1) {
                $scope.pagination = {
                    type: 'users',
                    url: 'users',
                    perPage: perPage,
                    start: paginationOffset,
                    pages: []
                };
                return 'templates/restangular/html/bootstrap/pagination.html';
            } else {
                return false;
            }
        };

        // Remove loading screen
        jQuery('#loading').show();

        // Get list with users
        RestangularCache.one('users', (paginationOffset - 1) * perPage).getList().then(function(usersResponse) {
            $scope.usersList = usersResponse;
            Page.setTitle('Users');
            requestUsersUrl = usersResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Toggle filters
        $scope.collapseFilter = true;
        $scope.toggleFilter = function() {
            $scope.collapseFilter = !$scope.collapseFilter;
            return $scope.collapseFilter;
        };

        // Search for the given username
        var usernameSearchResults = [];
        $scope.userBySearch       = '';
        $scope.getUserByUsername = function($viewValue) {
            var results = '';
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('users', $viewValue).get().then(function(usersResponse) {
                    usernameSearchResults = usersResponse;
                    return usersResponse;
                });
            }
            return results;
        };

        // When selecting an user
        $scope.selectUser = function() {
            for(var i = 0; i < usernameSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(usernameSearchResults[i].username == $scope.userBySearch) {
                    $location.path('user/'+ usernameSearchResults[i].id);
                }
            }
        };

        // Save a new user
        $scope.saveUser = function() {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.all('user').post($scope.user).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'User created!', content: 'The user: '+ $scope.user.username + ' has been created with ID: '+ resp.userId +'.', type: 'success'});
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
                    $alert({title: 'Error!', content: resp.error, type: 'danger'});
                } else {
                    $alert({title: 'User removed!', content: 'The user '+ $scope.usersList[index].username +' has been removed from the CMS.', type: 'success'});
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
angularRest.controller('userController', ['Restangular', 'RestangularCache', '$scope', '$route', '$routeParams', 'Page', '$alert', '$modal', 'Cache', '$location', function(Restangular, RestangularCache, $scope, $route, $routeParams, Page, $alert, $modal, Cache, $location) {
        var userRequestUrl      = '';
        var userOld             = {};
        $scope.user             = {
            groups: []
        };
        $scope.groupname        = '';
        var groupSearchResults  = [];

        // Show loading screen
        jQuery('#loading').show();

        // Get all information about this user
        RestangularCache.one('user', $routeParams.userId).get().then(function(userResponse) {
            if(userResponse.success !== false) {
                Page.setTitle(userResponse.username);
                $scope.user             = userResponse;
                angular.copy($scope.user, userOld);
                $scope.user.avatarCount = userResponse.avatars.length;
                userRequestUrl          = userResponse.getRequestedUrl();
            } else {
                $alert({title: 'Loading user failed!', content: userResponse.error, type: 'danger'});
            }

            // Remove loading screen
            jQuery('#loading').hide();
        });

        // Load profile picture
        $scope.getProfilePicture = function() {
            if($scope.user.picture !== undefined && $scope.user.picture !== false) {
                return $scope.user.picture +'?token='+ sessionStorage.token;
            } else {
                return '';
            }
        };

        // User is allowed to add new avatars
        $scope.allowCreate = function() {
            if(sessionStorage.userPermission >= WRITE) {
                return true;
            } else if(sessionStorage.userPermission >= EXECUTE && $routeParams.userId == sessionStorage.id) {
                return true;
            } else {
                return false;
            }
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
                    $alert({title: 'Change password failed!', content: putResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'Password changed!', content: 'The user\'s password has been updated.', type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                    modal.hide();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };

        // Process file input type on change
        jQuery('body').on('change', '#inputProfilePicture', function(e) {
             // Process File
            var reader = new FileReader();
            reader.readAsDataURL(jQuery('#inputProfilePicture')[0].files[0], "UTF-8");
            reader.onload = function (e) {
                $scope.user.picture = e.target.result;
            };
            reader.onerror = function(e) {
                $alert({title: 'Error!', content: 'Processing file failed.', type: 'danger'});
            };
        });


        // Open modal dialog to change the user's profile picture
        $scope.changePictureForm = function() {
            $scope.template         = partial_path +'/user/userPictureForm.html';
            $scope.formSubmit       = 'changePicture';
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

        // Change the user's profile picture
        $scope.changePicture = function() {
            // Show loading screen
            jQuery('#loading').show();

            $scope.user.customPUT($scope.user, 'picture').then(function(putResponse) {
                angular.copy($scope.user, $scope.userOld);
                if(!putResponse.success) {
                    $alert({title: 'Uploading picture failed!', content: putResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'Profile picture changed!', content: 'The user\'s profile picture has been updated.', type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                    $route.reload();
                    modal.hide();
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
                    $alert({title: 'User updating failed!', content: putResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'User updated!', content: 'The user information has been updated.', type: 'success'});
                    Cache.clearCache();
                    $location.path('user/'+ $routeParams.userId);
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
                        $alert({title: 'Error!', content: 'The selected Grid ('+ $scope.grids[i].name +') is offline, therefore it is not possible to create an avatar for the selected Grid.', type: 'danger'});
                    } else if($scope.grids[i].remoteAdmin.url == null) {
                        $alert({title: 'Error!', content: 'The selected Grid\'s ('+ $scope.grids[i].name +') remoteadmin is not configured, therefore it is not possible to create an avatar for the selected Grid.', type: 'danger'});
                    } else {
                        $alert({title: 'Info!', content: 'Avatars can be created on the selected grid ('+ $scope.grids[i].name +').', type: 'info'});
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
            } else if(func == 'changePicture') {
                $scope.changePicture();
            }
        };

        // Search for the given group
        $scope.getGroupsByName = function($viewValue) {
            var results = '';
            if($viewValue !== undefined && $viewValue.length >= 3) {
                results = RestangularCache.one('groups', $viewValue).get().then(function(groupsResponse) {
                    groupSearchResults = groupsResponse;
                    return groupsResponse;
                });
            }
            return results;
        };

        // Adds the currently selected group to the list
        $scope.addGroup = function() {
            for(var i = 0; i < groupSearchResults.length; i++) {
                // Only add user when match found and not already listed
                if(groupSearchResults[i].name == $scope.groupname) {
                    if(!isDuplicateGroup()) {
                        $scope.user.groups.push(groupSearchResults[i]);
                    } else {
                        $alert({title: 'Duplicate!', content: 'The user is already a member of the group '+ groupSearchResults[i].name, type: 'warning'});
                    }
                }
            }
        };

        // Checks for duplicate groups
        function isDuplicateGroup() {
            for(var i = 0; i < $scope.user.groups.length; i++) {
                if($scope.user.groups[i].name == $scope.groupname) {
                    return true;
                }
            }
            return false;
        };

        // Removes the group with the given ID from the list
        $scope.removeGroup = function(id) {
            for(var i = 0; i < $scope.user.groups.length; i++) {
                if($scope.user.groups[i].id == id) {
                    $scope.user.groups.splice(i, 1);
                    return true;
                }
            }
            return false;
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
                    $alert({title: 'Error!', content: avatarCreateResponse.error, type: 'danger'});
                } else {
                    $alert({title: 'Avatar created!', content: 'The avatar '+ $scope.avatar.firstName +' '+ $scope.avatar.lastName +' has been created.', type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);

                    // Link the avatar to this user
                    Restangular.one('grid', $scope.avatar.gridId).one('avatar', avatarCreateResponse.avatar_uuid).post('', {username: $scope.user.username}).then(function(avatarLinkResponse) {
                        if(!avatarLinkResponse.success) {
                            $alert({title: 'Error!', content: avatarLinkResponse.error, type: 'danger'});
                        } else {
                            $alert({title: 'Avatar linked!', content: 'The avatar '+ $scope.avatar.firstName +' '+ $scope.avatar.lastName +' has been linked to this user.', type: 'success'});
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
                    $alert({title: 'Error!', content: confirmationResponse.error, type: 'danger'});
                } else {
                    $scope.user.avatars[index].confirmed = 1;
                    $alert({title: 'Avatar confirmed!', content: 'The avatar is confirmed user.', type: 'success'});
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
                    $alert({title: 'Error!', content: unlinkResponse.error, type: 'danger'});
                } else {
                    delete $scope.user.avatars[index];
                    $scope.user.avatarCount--;
                    $alert({title: 'Avatar unlinked!', content: 'The avatar is no longer linked to this user.', type: 'success'});
                    Cache.clearCachedUrl(userRequestUrl);
                    $route.reload();
                }
                // Remove loading screen
                jQuery('#loading').hide();
            });
        };
    }]
);

