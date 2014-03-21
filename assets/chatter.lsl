/**
 * When touching this prim the chat will be linked to the CMS
 *
 * Do not forget to enable the following settings in your OpenSim configuration
 * For loading dynamic textures and enable JSON support:
 * [XEngine]
 *      AllowOSFunctions = true
 *      AllowMODFunctions = true
 * [JsonStore]
 *      Enabled = true
 *
 * @author Niels Witte
 * @date March 21st, 2014
 * @version 0.1
 */
// Config values
string serverUrl = "http://127.0.0.1/OpenSim-CMS/api";
integer debug = 1;              // Enables showing debugging comments
string APIUsername = "OpenSim"; // API user name to be used
string APIPassword = "OpenSim"; // API password
integer serverId = 1;           // The ID of this server in OpenSim-CMS

// Some general parameters
integer channelChat = 0;        // Channel to log chat on
string APIToken;                // The token to be used for the API
list messages;                  // List to store the chat in
key userUuid = NULL_KEY;        // The toucher's UUID
key objectUuid;                 // The object's UUID
list userUuidLinks;             // List with the links between CMS users and Avatars
integer Listener;               // Listen to chat
integer lastUpdate;             // Unix timestamp from the last update

// HTTP requests
key http_request_api_token;     // API token request
key http_request_send_chat;     // Response on sending chat messages to the server
key http_request_receive_chat;  // Response on requesting chat messages from the server
key http_request_avatar;        // The user linked to the requested avatar

/**
 * Requesting a new API token for this session
 */
request_api_token() {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting new API token");
    string body = "username="+ APIUsername +"&password="+ APIPassword;
    http_request_api_token = llHTTPRequest(serverUrl +"/auth/username/", [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

/**
 * Links the message to the meeting
 */
request_send_chat() {
    if(debug) {
        // Count the number of messages
        integer count = llGetListLength(messages);
        if(count >= 3) {
            count = count / 3;
        } else {
            count = 0;
        }
        llInstantMessage(userUuid, "[Debug] Sending ("+ count +") messages");
    }

    // Only send when there are messages
    if(llGetListLength(messages) > 0) {
        integer i;
        string body = "[";
        string body_messages = "";
        // Parse messages
        for(i = 0; i < llGetListLength(messages); i=i+3) {
            body_messages = body_messages + "{ \"timestamp\": \""+ llList2String(messages, i) +"\", \"userId\": \""+ llList2String(messages, i+1) +"\", \"message\": \""+ llEscapeURL(llList2String(messages, i+2)) +"\"}";

            // Add comma when not last element
            if(i+3 < llGetListLength(messages)) {
                body_messages = body_messages + ",";
            }
        }
        // Close array
        body = body + body_messages + "]";
        // Empty list
        messages = [];
        http_request_send_chat = llHTTPRequest(serverUrl +"/grid/"+ serverId +"/chats/?token="+ APIToken, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/json"], body);
    }
}

/**
 * Loads the chats from the server
 */
request_receive_chat() {
    http_request_receive_chat = llHTTPRequest(serverUrl +"/grid/"+ serverId +"/chats/"+ lastUpdate +"/?token="+ APIToken, [], "");
    lastUpdate = llGetTimestamp();
}

/**
 * Searches for an user by avatar uuid
 *
 * @param key uuid
 * @return integer
 */
integer request_avatar_by_uuid (key uuid) {
    integer res = llListFindList(userUuidLinks, [uuid]);
    // Cached match found?
    if(res > -1) {
        if(debug) llInstantMessage(userUuid, "[Debug] Looking up UUID: "+ uuid + " and matched to cached result");
        return res+1;
    } else {
        if(debug) llInstantMessage(userUuid, "[Debug] Looking up UUID: "+ uuid + " and need to search server");
        // Request user by avatar
        http_request_avatar = llHTTPRequest(serverUrl +"/grid/"+ serverId +"/avatar/"+ uuid +"?token="+ APIToken, [], "");

        // Store UUID
        userUuidLinks += [uuid];
        return -1;
    }
}

/**
 * Adds the message with timestamp and sender uuid to the queue
 *
 * @param integer timestamp
 * @param integer userId
 * @param string message
 */
queueMessage(integer timestamp, integer userId, string message) {
    messages += [timestamp, userId, message];
}

/**
 * The default state
 */
default {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        if(debug) llInstantMessage(userUuid, "[Debug] Chatter disabled.");
        llSetText("", <0,0,1>, 1.0);

        // SET COLOR RED
        llSetColor(<255, 0, 0>, ALL_SIDES);

        // Get the object's UUID
        objectUuid = llGetKey();
    }

    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);

        state startup;
    }
}
/**
 * State when starting up a meeting,
 * used when loading today's meetings
 */
state startup {
    state_entry() {
        if(debug) llInstantMessage(userUuid, "[Debug] Chatter starting up!");
        // SET COLOR ORANGE
        llSetColor(<255, 200, 0>, ALL_SIDES);

        // Get API Key
        request_api_token();
    }

    /**
     * Actions to be performed on touch start
     */
    touch_start(integer totalNumber) {
        userUuid = llDetectedKey(0);

        state default;
    }

    /**
     * Server responses
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);
            // Send a more specific and meaningful response to the user
            if(request_id == http_request_api_token) {
                llInstantMessage(userUuid, "[Meeting] Invalid username/password combination used.");
            }

            return;
        }

        // Received API token
        if(request_id == http_request_api_token) {
            // Parse the returned body to JSON
            if(debug) llInstantMessage(userUuid, "[Debug] Received API token");
            key json_body   = JsonCreateStore(body);
            APIToken        = JsonGetValue(json_body, "token");
            if(debug) llInstantMessage(userUuid, "[Debug] Storing API token: "+ APIToken);
            state chatting;
        }
    }
}

/**
 * Active state, where the logging happens
 */
state chatting {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        if(debug) llInstantMessage(userUuid, "[Debug] Chatting enabled!");
        // SET COLOR GREEN
        llSetColor(<0, 255, 0>, ALL_SIDES);
        // Listen to everything
        Listener = llListen(channelChat, "", NULL_KEY, "" );

        // Start processing messages
        llSetTimerEvent(2.0);
    }

    /**
     * Actions performed when user touches the object
     */
    touch_start(integer totalNumber) {
        userUuid = llDetectedKey(0);
        state default;
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);

            return;
        }

        // Store messages
        if(request_id == http_request_send_chat) {
            if(debug) llInstantMessage(userUuid, "[Debug] Chat sent, server response: "+ body);
        } else if(request_id == http_request_avatar) {
            key json_body   = JsonCreateStore(body);
            integer userId  = (integer) JsonGetValue(json_body, "id");
            if(debug) llInstantMessage(userUuid, "[Debug] Requested user by avatar and got response: ID = "+ userId);

            // Got a result?
            if(userId > 0) {
                userUuidLinks += [userId];
            // No match found
            } else {
                userUuidLinks += [-1];
            }
        } else if(request_id == http_request_receive_chat) {
            key json_body       = JsonCreateStore(body);
            integer chatLength  = JsonGetArrayLength(json_body, "");
            if(meetingsLength >= 1) {
                // Say all messages
                for (x = 0; x < chatLength; x++) {
                    string name = JsonGetValue(json_body, "[x].user.username");
                    string msg  = JsonGetValue(json_body, "[x].message");

                    llSay(0, "["+ name +"] "+ msg);
                }
            }
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        integer userId = request_avatar_by_uuid(id);
        // Prepend message with Avatar name when unknown user
        if(userId == -1) {
            message = "[" + name + "] " + message;
        }

        queueMessage(llGetUnixTime(), userId, message);
    }

    // Loop through requests
    timer() {
        request_send_chat();
        request_receive_chat();
        llSetTimerEvent(2.0);
    }
}
