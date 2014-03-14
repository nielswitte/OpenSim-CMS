/**
 * When touching this prim the chats will be logged
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
 * @date March 14th, 2014
 * @version 0.1
 */
// Config values
string serverUrl = "http://127.0.0.1/OpenSim-CMS/api";
integer debug = 1;              // Enables showing debugging comments
string APIUsername = "OpenSim"; // API user name to be used
string APIPassword = "OpenSim"; // API password
integer serverId = 1;           // The ID of this server in OpenSim-CMS

// Some general parameters
integer channel = 0;            // Channel to listen to
string APIToken;                // The token to be used for the API
list Listener;                  // The navigation listener
list messages;                  // List to store the chat in
key userUuid = NULL_KEY;        // The toucher's UUID (default the owner)
key objectUuid;                 // The uuid of this object

// HTTP requests
key http_request_api_token;     // API token request
key http_request_send_chat;     // Response on linking avatar to user

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
        string body = "{ \"meetingId\": 1, \"messages\": [";
        string body_messages = "";
        // Parse messages
        for(i = 0; i < llGetListLength(messages); i=i+3) {
            body_messages = body_messages + "{ \"timestamp\": \""+ llList2String(messages, i) +"\", \"uuid\": \""+ llList2String(messages, i+1) +"\", \"message\": \""+ llList2String(messages, i+2) +"\"}";

            // Add comma when not last element
            if(i+3 < llGetListLength(messages)) {
                body_messages = body_messages + ",";
            }
        }
        // Close array
        body = body + body_messages + "]}";
        // Empty list
        messages = [];
        http_request_send_chat = llHTTPRequest(serverUrl +"/meeting/"+ 1 +"/log/?token="+ APIToken, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/json"], body);
    }
}

queueMessage(string timestamp, string uuid, string message) {
    messages += [timestamp, uuid, message];
}

/**
 * Removes all listeners
 */
removeListeners() {
    integer i;
    for (i = 0; i < llGetListLength(Listener); i++ ) {
        llListenRemove(llList2Integer(Listener, i));
    }
    Listener = [];
}

/**
 * The default state
 */
default {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        // SET COLOR RED
        llSetColor(<255, 0, 0>, ALL_SIDES);

        removeListeners();

        // Get the object's UUID
        objectUuid = llGetKey();
    }

    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);

        state logging;
    }
}

state logging {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        // SET COLOR GREEN
        llSetColor(<0, 255, 0>, ALL_SIDES);
        // Listen to everything
        Listener += llListen( 0, "", NULL_KEY, "" );
        // Get API Key
        request_api_token();
        // Start processing messages
        llSetTimerEvent(10.0);
    }

    /**
     * Actions performed when user touches the object
     */
    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Send last messages
        request_send_chat();
        // Go back to off
        state default;
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);
            // Send a more specific and meaningful response to the user
            if(request_id == http_request_api_token) {
                llInstantMessage(userUuid, "Invalid username/password combination used.");
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
        // Link avatar to username response?
        } else if(request_id = http_request_send_chat) {
            if(debug) llInstantMessage(userUuid, "[Debug] Messages stored: "+ body);
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        queueMessage(llGetTimestamp(), (string) id, message);
    }

    // Loop through requests
    timer() {
        if(debug) llInstantMessage(userUuid, "[Debug] Timer fired");
        request_send_chat();

        llSetTimerEvent(10.0);
    }
}