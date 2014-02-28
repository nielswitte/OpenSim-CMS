/**
 * When touching this prim the user gets a dialog to link his avatar to a user account
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
 * @date February 28th, 2014
 * @version 0.1
 */
// Config values
string serverUrl = "http://127.0.0.1/OpenSim-CMS/api";
integer debug = 1;              // Enables showing debugging comments
string APIUsername = "OpenSim"; // API user name to be used
string APIPassword = "OpenSim"; // API password
integer serverId = 1;           // The ID of this server in OpenSim-CMS

// Some general parameters
integer channel = -2141453;     // Channel to listen to
string APIToken;                // The token to be used for the API
integer gListener;              // The navigation listener
key userUuid = NULL_KEY;        // The toucher's UUID (default the owner)
key objectUuid;                 // The uuid of this object

// HTTP requests
key http_request_api_token;     // API token request
key http_request_set;           // Response on linking avatar to user


/**
 * Requesting a new API token for this session
 */
request_api_token() {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting new API token");
    string body = "username="+ APIUsername +"&password="+ APIPassword;
    http_request_api_token = llHTTPRequest(serverUrl +"/auth/username/", [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

/**
 * Links the given avatar uuid to the given user on this grid
 * @param key uuid
 * @param string username
 */
request_set_avatar(key uuid, string username) {
    if(debug) llInstantMessage(userUuid, "[Debug] Linking avatar: "+ (string) uuid + " to user: "+ username);

    string body = "username="+ username;
    http_request_set = llHTTPRequest(serverUrl +"/grid/"+ serverId +"/avatar/"+ (string)uuid +"/?token="+ APIToken, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

/**
 * Closes the menu and removes the listener to save memory
 */
close_menu() {
    llSetTimerEvent(0.0);// you can use 0 as well to save memory
    llListenRemove(gListener);
}

/**
 * The default state
 */
default {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        // Close any open menu's
        close_menu();

        // Remove old main listener
        llListenRemove(gListener);

        // Get the object's UUID
        objectUuid = llGetKey();
    }

    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);

        state linking;
    }
}

state linking {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        request_api_token();

        // Remove old main listener
        llListenRemove(gListener);

        if(debug) llSay(0, "Avatar linking to username enabled");

        // Listen at channel
        gListener = llListen(channel, "", userUuid, "");
        llTextBox(userUuid, "This will link your avatar to our CMS user account. To do so, type your username in the textbox below and press send.", channel);
    }

    /**
     * Actions performed when user touches the object
     */
    touch_start(integer totalNumber) {
        // Get the toucher's UUID
        userUuid = llDetectedKey(0);

        // Listen at channel
        gListener = llListen(channel, "", userUuid, "");
        llTextBox(userUuid, "This will link your avatar to our CMS user account. To do so, type your username in the textbox below and press send.", channel);
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
        } else if(request_id = http_request_set) {
            key json_body = JsonCreateStore(body);
            string success = JsonGetValue(json_body, "success");
            if(debug) llInstantMessage(userUuid, "[Debug] Server response on linking avatar ("+ success +"): "+ body);
            // Link successeeded ?
            if(success == 1) {
                llInstantMessage(userUuid, "Avatar linked to user, the user still needs to confirm this link in the CMS");
            } else {
                string error = JsonGetValue(json_body, "error");
                llInstantMessage(userUuid, "Linking avatar failed: "+ error);
            }
            state default;
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        request_set_avatar(userUuid, message);
    }
}