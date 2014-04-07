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
        // Clear the comment form
        $scope.clearComment = function() {
            $scope.comment = {
                user: {
                    id: sessionStorage.id
                },
                type: $scope.commentType,
                itemId: $scope.commentItemId,
                parentId: 0,
                message: ""
            };
            $scope.replyTo = "";
        };

        // Clear comment form on init
        $scope.clearComment();

        // Removes a comment from the database
        $scope.deleteComment = function(id) {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.one('comment', id).remove().then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Comment removed!', content: $sce.trustAsHtml('The comment with ID '+ id +' has been removed from the CMS.'), type: 'success'});
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
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Comment added!', content: $sce.trustAsHtml('Comment has been posted with ID: '+ resp.commentId +'.'), type: 'success'});
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
            $scope.message  = "";
            updateForm      = 0;
        };

        // Update a comment
        $scope.updateComment = function(id) {
            Restangular.one('comment', id).customPUT({message: this.message}).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Comment updated!', content: $sce.trustAsHtml('Comment with ID: '+ id +' has been updated.'), type: 'success'});
                    $scope.updateCommentReset();
                    Cache.clearCache();
                    $route.reload();
                }
            });
        };

        // Check if the user is allowed to update this comment
        $scope.allowUpdate = function(userId) {
            return $scope.allowDelete(userId);
        };

        // Checks if the user has permission to remove a comment
        $scope.allowDelete = function(userId) {
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

        // Trust the message as safe markdown html
        $scope.markdown = function(message) {
            return $sce.trustAsHtml(markdown.toHTML(""+ message));
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
 *   _    _
 *  | |  | |
 *  | |__| | ___  _ __ ___   ___
 *  |  __  |/ _ \| '_ ` _ \ / _ \
 *  | |  | | (_) | | | | | |  __/
 *  |_|  |_|\___/|_| |_| |_|\___|
 *
 */
// homeController -----------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('homeController', ['Restangular', '$scope', 'Page', function(Restangular, $scope, Page) {
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
                        $alert({title: 'Logged In!', content: $sce.trustAsHtml('You are now logged in as '+ userResponse.username +'. Your previous authentication was on '+ authResponse.lastLogin), type: 'success'});
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
// documentsController ------------------------------------------------------------------------------------------------------------------------------
angularRest.controller('dashboardController', ['Restangular', 'RestangularCache', '$scope', 'Page', function(Restangular, RestangularCache, $scope, Page) {
        // Load all meetings the user is a participant for
        RestangularCache.one('user', sessionStorage.id).getList('meetings').then(function(meetingsResponse) {
            $scope.meetings = meetingsResponse;
        });
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
angularRest.controller('documentsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$alert', '$modal', '$sce', 'Cache', '$route',
    function(Restangular, RestangularCache, $scope, Page, $alert, $modal, $sce, Cache, $route) {
        $scope.orderByField         = 'title';
        $scope.reverseSort          = false;
        var requestDocumentsUrl     = '';
        $scope.documentsList        = [];
        $scope.types                = ['document', 'image', 'presentation'];

        // Show loading screen
        jQuery('#loading').show();

        // Get a list with documents
        RestangularCache.all('files').getList().then(function(documentsResponse) {
            $scope.documentsList = documentsResponse;
            Page.setTitle('Files');
            requestDocumentsUrl = documentsResponse.getRequestedUrl();

            // Remove loading screen
            jQuery('#loading').hide();
        });

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
                $alert({title: 'Error!', content: $sce.trustAsHtml('Processing file failed.'), type: 'danger'});
            };
        });

        // Save the new document
        function saveDocument() {
            // Show loading screen
            jQuery('#loading').show();

            Restangular.all('file').post($scope.document).then(function(resp) {
                if(!resp.success) {
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'File created!', content: $sce.trustAsHtml('The file: '+ $scope.document.title + ' has been created with ID: '+ resp.id +'.'), type: 'success'});
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
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    var index = getDocumentIndexById(id);
                    if(index !== false) {
                        $alert({title: 'Document removed!', content: $sce.trustAsHtml('The document (#'+ $scope.documentsList[index].id +') '+ $scope.documentsList[index].title +' has been removed from the CMS.'), type: 'success'});
                        $scope.documentsList.splice(index, 1);
                    } else {
                        $alert({title: 'Document removed!', content: $sce.trustAsHtml('The document has been removed from the CMS. However, some unexpected events happend. Check if everything is still OK!'), type: 'success'});
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
                    $alert({title: 'Error!', content: $sce.trustAsHtml('No expired cache items to clear. Try again later.'), type: 'danger'});
                } else {
                    $alert({title: 'Cache cleared!', content: $sce.trustAsHtml('Cleared '+ resp.removedAssets +' expired items from the cache.'), type: 'success'});
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
angularRest.controller('documentController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', 'Page', '$modal', '$sce', function(Restangular, RestangularCache, $scope, $routeParams, Page, $modal, $sce) {
        // Show loading screen
        jQuery('#loading').show();
        // List with comments
        $scope.comments = {
            comments: [],
            commentCount: 0
        };

        // Load comments
        RestangularCache.all('comments').one('document', $routeParams.documentId).get().then(function(commentResponse) {
            $scope.comments = commentResponse;
        });

        // Show comments and set the comment Type to: meeting and the id of the meeting
        $scope.showComments = function() {
            $scope.commentType      = 'document';
            $scope.commentItemId    = $routeParams.documentId;
            return partial_path +'/comment/commentContainer.html';
        };

        // Get document from API
        RestangularCache.one('file', $routeParams.documentId).get().then(function(documentResponse) {
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
                title: $sce.trustAsHtml('Preview '+ number),
                content: $sce.trustAsHtml('<img src="'+ url+'?token='+ sessionStorage.token +'" class="img-responsive">'),
                html: true,
                show: true,
                template: 'templates/restangular/html/bootstrap/modalDialogBasic.html',
                scope: $scope
            });
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
angularRest.controller('gridController', ['RestangularCache', '$scope', '$routeParams', 'Page', function(RestangularCache, $scope, $routeParams, Page) {
        // Show loading screen
        jQuery('#loading').show();

        RestangularCache.one('grid', $routeParams.gridId).get().then(function(gridResponse) {
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
angularRest.controller('meetingsController', ['Restangular', 'RestangularCache', '$scope', 'Page', '$modal', '$tooltip', '$sce', 'Cache', '$location',  function(Restangular, RestangularCache, $scope, Page, $modal, $tooltip, $sce, Cache, $location) {
        var date = new Date(new Date - (1000*60*60*24*14));
        var modal;
        var meeting;
        var eventId;
        var meetingRequestUrl;

        // Get all meetings for the calendar
        RestangularCache.one('meetings', date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate()).getList('calendar').then(function(meetingsResponse) {
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
                            $alert({title: 'Teleported!', content: $sce.trustAsHtml('Avatar teleported to '+ $scope.meeting.room.name +' in region: '+ $scope.meeting.room.region.name +' on grid '+ $scope.meeting.room.grid.name), type: 'success'});
                            return true;
                        });
                    }
                }
                // No match found?
                if(!avatarFound) {
                    $alert({title: 'No avatar found!', content: $sce.trustAsHtml('Currently there is no avatar online, linked to your user account, on this grid to teleport.'), type: 'danger'});
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
                    $alert({title: 'Error!', content: $sce.trustAsHtml(resp.error), type: 'danger'});
                } else {
                    $alert({title: 'Meeting updated!', content: $sce.trustAsHtml('The meeting has been updated.'), type: 'success'});
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
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The document '+ documentSearchResults[i].title + ' is already added to this meeting'), type: 'warning'});
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
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The user '+ usernameSearchResults[i].username + ' is already a participant for this meeting'), type: 'warning'});
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
            Page.setTitle('Meeting '+ meetingResponse.name);
            meetingRequestUrl       = meetingResponse.getRequestedUrl();
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

                // Set the dates and times
                setDateTimes();

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
            // Remove loading screen
            jQuery('#loading').hide();
        });
    }]
);

// meetingMinutesController -------------------------------------------------------------------------------------------------------------------------
angularRest.controller('meetingMinutesController', ['Restangular', 'RestangularCache', '$scope', '$routeParams', 'Page', '$alert', '$sce', 'Cache', '$location', '$compile', function(Restangular, RestangularCache, $scope, $routeParams, Page, $alert, $sce, Cache, $location, $compile) {
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
                var html        = '<strong>Votes: '+ totalVotes +'</strong><br>';
                html += '<div class="progress" title="Approved"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[0]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[0]) / totalVotes) * 100) +'%;">'+ parseInt(votes[0]) +'</div></div>';
                html += '<div class="progress" title="Rejected"><div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[1]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[1]) / totalVotes) * 100) +'%;">'+ parseInt(votes[1]) +'</div></div>';
                html += '<div class="progress" title="Blank"><div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[2]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[2]) / totalVotes) * 100) +'%;">'+ parseInt(votes[2]) +'</div></div>';
                html += '<div class="progress" title="None"><div class="progress-bar progress-bar-default" role="progressbar" aria-valuenow="'+ Math.round((parseInt(votes[3]) / totalVotes) * 100) +'" aria-valuemin="0" aria-valuemax="100" style="width: '+ Math.round((parseInt(votes[3]) / totalVotes) * 100) +'%;">'+ parseInt(votes[3]) +'</div></div>';
                return $sce.trustAsHtml(html);
            } else {
                return $sce.trustAsHtml(msg);
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
            agenda: '1. Opening\n',
            participants: [],
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
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The document '+ documentSearchResults[i].title + ' is already added to this meeting'), type: 'warning'});
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
                        $alert({title: 'Duplicate!', content: $sce.trustAsHtml('The user '+ usernameSearchResults[i].username + ' is already a participant for this meeting'), type: 'warning'});
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
                    $location.path('meeting/'+ resp.meetingId);
                }
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
angularRest.controller('userController', ['Restangular', 'RestangularCache', '$scope', '$route', '$routeParams', 'Page', '$alert', '$modal', '$sce', 'Cache', '$location', function(Restangular, RestangularCache, $scope, $route, $routeParams, Page, $alert, $modal, $sce, Cache, $location) {
        var userRequestUrl   = '';
        var userOld          = {};
        $scope.grids         = [];

        // Show loading screen
        jQuery('#loading').show();

        // Get all information about this user
        RestangularCache.one('user', $routeParams.userId).get().then(function(userResponse) {
            if(userResponse.success !== false) {
                Page.setTitle(userResponse.username);
                $scope.user             = userResponse;
                angular.copy($scope.user, userOld);
                $scope.user.avatarCount = Object.keys(userResponse.avatars).length;
                userRequestUrl          = userResponse.getRequestedUrl();
            } else {
                $alert({title: 'Loading user failed!', content: $sce.trustAsHtml(userResponse.error), type: 'danger'});
            }

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

