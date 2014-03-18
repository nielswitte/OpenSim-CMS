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
integer channelListen = -9;     // Channel to listen to
integer channelChat = 0;        // Channel to log chat on
string APIToken;                // The token to be used for the API
list Listener;                  // The navigation listener
list messages;                  // List to store the chat in
key userUuid = NULL_KEY;        // The toucher's UUID (default the owner)
key objectUuid;                 // The uuid of this object
key jsonMeeting;                // JSON meeting
list avatarsPresent;            // List to keep track of all avatars currently present
integer meetingAreaSize = 25;   // Size of the meeting room to detect avatars in

// HTTP requests
key http_request_api_token;     // API token request
key http_request_send_chat;     // Response on linking avatar to user
key http_request_meetings;      // Get the overview from coming meetings
key http_request_meeting;       // Get a specific meeting

/**
 * Requesting a new API token for this session
 */
request_api_token() {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting new API token");
    string body = "username="+ APIUsername +"&password="+ APIPassword;
    http_request_api_token = llHTTPRequest(serverUrl +"/auth/username/", [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

/**
 * Requests the meetings for today
 */
request_meetings() {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting meetings");
    string date = llGetDate();
    http_request_meetings = llHTTPRequest(serverUrl +"/meetings/"+ date +"/?token="+ APIToken, [], "");
}

/**
 * Request a specific meeting
 * @param intger id
 */
request_meeting(integer id) {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting meeting with id: "+ id);
    http_request_meeting = llHTTPRequest(serverUrl +"/meeting/"+ id +"/?token="+ APIToken, [], "");
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

/**
 * Adds the message with timestamp and sender uuid to the queue
 *
 * @param string timestamp
 * @param string uuid
 * @param string message
 */
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
        jsonMeeting = "";
        avatarsPresent = [];

        // SET COLOR RED
        llSetColor(<255, 0, 0>, ALL_SIDES);

        removeListeners();

        // Get the object's UUID
        objectUuid = llGetKey();
    }

    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);

        state startup;
    }
}

state startup {
    state_entry() {
        // SET COLOR ORANGE
        llSetColor(<255, 200, 0>, ALL_SIDES);

        // Get API Key
        request_api_token();
    }

    /**
     * Actions to be performed on touch start
     */
    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Listen for message
        Listener += llListen(channelListen, "", userUuid, "");
        // Show options
        list options = ["Load", "Quit"];
        llDialog(userUuid, "To start the logging of a meeting, you need to select a meeting.", options, channelListen);
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
                llInstantMessage(userUuid, "Invalid username/password combination used.");
            } else if(request_id == http_request_meetings) {
                llInstantMessage(userUuid, "Unable to retrieve meetings from the server.");
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
        // Received meetings
        } else if(request_id == http_request_meetings) {
            key json_body   = JsonCreateStore(body);
            integer meetingsLength = JsonGetArrayLength(json_body, "");
            if(debug) llInstantMessage(userUuid, "[Debug] Found: "+ meetingsLength +" meetings");
            integer x;
            string today = llGetDate();
            list meetingsToday = [];
            // At least 1 meeting found?
            if(meetingsLength >= 1) {
                // Get from each slide the URL or the UUID
                for (x = 1; x <= meetingsLength; x++) {
                    string meetingStartDay = llGetSubString(JsonGetValue(json_body, "["+ (meetingsLength - x) +"].startDate"), 0, 9);

                    // Only load meetings that start today
                    if(meetingStartDay == today) {
                        meetingsToday += [ JsonGetValue(json_body, "["+ (meetingsLength - x) +"].id") +") "+ JsonGetValue(json_body, "["+ (meetingsLength - x) +"].name") ];
                    }
                }

                llDialog(userUuid, "The following meetings will take place today:", meetingsToday , channelListen);
            // No meetings
            } else {
                llInstantMessage(userUuid, "No meetings scheduled for today.");
            }
        // Load a specific meeting
        } else if(request_id == http_request_meeting) {
            jsonMeeting = JsonCreateStore(body);
            llSay(channelChat, "Loaded meeting: "+ JsonGetValue(jsonMeeting, "name"));
            state logging;
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        // Get a specific ID
        if(channel == channelListen) {
            // Close the logger
            if(message == "Quit") {
                state default;
            } else if(message == "Load") {
                // Request meetings
                request_meetings();

            // Load a meeting
            } else {
                integer endOfId = llSubStringIndex(message, ")");
                integer meetingId = (integer) llGetSubString(message, 0, endOfId - 1);
                if(meetingId > 0) {
                    // Request a meeting
                    request_meeting(meetingId);
                }
            }
        }
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
        Listener += llListen(channelChat, "", NULL_KEY, "" );

        // Broadcast meeting Agenda
        llSay(channelChat, "Meeting Agenda: \n"+ JsonGetValue(jsonMeeting, "agenda"));
        llSensor("", NULL_KEY, AGENT, meetingAreaSize, PI);

        // Start processing messages
        llSetTimerEvent(10.0);
    }

    /**
     * Detects avatars within the meeting area
     * @param vIntFound
     */
    sensor(integer vIntFound) {
        if(debug) llInstantMessage(userUuid, "[Debug] Searching for avatars");
        integer vIntCounter = 0;
        //-- loop through all avatars found
        do {
            string avatarName   = llDetectedName(vIntCounter);
            string avatarUuid   = (string) llDetectedKey(vIntCounter);
            if(llListFindList(avatarsPresent, [avatarUuid]) == -1) {
                avatarsPresent += [avatarUuid];
                llSay(0, "Avatar entered the meeting: "+ avatarName +" ("+ avatarUuid +")");
                queueMessage(llGetTimestamp(), "", "Avatar entered the meeting: "+ avatarName +" ("+ avatarUuid +")");
            }
        } while (++vIntCounter < vIntFound);
    }

    // sensor does not detect owner if it's attached

    no_sensor() {
        // Nothing
    }

    /**
     * Actions performed when user touches the object
     */
    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Listen for message
        Listener += llListen(channelListen, "", userUuid, "");
        // Show options
        list options = ["Previous", "Next", "Quit"];
        llDialog(userUuid, "Press Quit to stop logging the meeting. Or use Previous and Next to go to the other agenda items.", options, channelListen);
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);
            // Send a more specific and meaningful response to the user

            // @todo

            return;
        }

        if(request_id = http_request_send_chat) {
            if(debug) llInstantMessage(userUuid, "[Debug] Messages stored: "+ body);
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        // Get a specific ID
        if(channel == channelListen) {
            // Close the logger
            if(message == "Quit") {
                request_send_chat();
                state default;
            // Go to the previous agenda item
            } else if(message == "Previous") {

            // Go to the next agenda item
            } else if(message == "Next") {

            }
        } else {
            queueMessage(llGetTimestamp(), (string) id, message);
        }
    }

    // Loop through requests
    timer() {
        if(debug) llInstantMessage(userUuid, "[Debug] Timer fired");
        request_send_chat();
        llSensor("", NULL_KEY, AGENT, meetingAreaSize, PI);

        llSetTimerEvent(10.0);
    }
}
