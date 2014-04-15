/**
 * Makes a image presentation screen of any surface.
 * Uses JSON to retrieve presentation data from server and store it
 * in a temporary cache
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
 * @date February 11th, 2014
 * @version 0.9
 */
// Config values
string serverUrl = "http://127.0.0.1/OpenSim-CMS/api";
integer debug = 0;              // Enables showing debugging comments
string APIUsername = "OpenSim"; // API user name to be used
string APIPassword = "OpenSim"; // API password
integer serverId = 1;           // The ID of this server in OpenSim-CMS
integer width = 1024;           // Texture width
integer height = 1024;          // Texture height

// Some general parameters
string APIToken;                // The token to be used for the API
integer mListener;              // The main listener
integer gListener;              // The navigation listener
key userUuid = NULL_KEY;        // The toucher's UUID (default the owner)
key objectUuid;                 // The object's UUID
integer channel = 7;            // The channel to be used
integer media = 0;              // Media type [0 = off, 1 = presentation]
list textureCache;              // Cache the textures to only require loading once
integer item = 1;               // The current page/slide
integer totalItems = 0;         // Total number of pages/slides
list itemsList;                 // List with all pages/slides
list itemIds;                   // List with IDs of all pages/slides
string itemTitle;               // Title of the document/presentation
string itemId;                  // The ID of the document/presentation

// HTTP stuff
key http_request_api_token;     // HTTP Request for fetching API token
key http_request_id;            // HTTP Request for loading file
key http_request_files;         // HTTP Request for loading user's files
key http_request_set;           // HTTP Request to set UUID of object for future use
key http_request_comments;      // HTTP Request for loading comments

// Menu's
string mainNavigationText           = "What type of content do you want to use?";
list mainNavigationButtons          = ["Presentation", "Document", "Image", "Quit"];
string presentationNavigationText   = "Slide show navigation";
list presentationNavigationButtons  = ["First", "Back", "Next", "Quit", "New", "Comments"];
string documentNavigationText       = "Document navigation";
list documentNavigationButtons      = ["First", "Back", "Next", "Quit", "New", "Comments"];
string imageNavigationText          = "Image navigation";
list imageNavigationButtons         = ["Quit", "New", "Comments"];

/**
 * Opens a dialog in OpenSim for the given user with a text message and a list of buttons
 *
 * @param key userUuid - UUID of the user to display this dialog to
 * @param string inputString - Text to display in the dialog
 * @param list inputList - List with buttons to display
 */
open_menu(key inputKey, string inputString, list inputList) {
    gListener = llListen(channel, "", inputKey, "");
    // Send a dialog to that person. We'll use a fixed negative channel number for simplicity
    llDialog(inputKey, inputString, inputList , channel);
    llSetTimerEvent(300.0);
}

/**
 * Closes the menu and removes the listener to save memory
 */
close_menu() {
    llSetTimerEvent(0.0);// you can use 0 as well to save memory
    llListenRemove(gListener);
    llListenRemove(mListener);
}

/**
 * Function to validate a key
 *
 * @param key in - Key to validate
 * @return boolean - 1 if valid key and NULL_KEY 2 if valid and not NULL_KEY, else 0
 */
integer isKey(key in) {//by: Strife Onizuka
    if(in) return 2;          // key is valid AND not equal NULL_KEY; the distinction is important in some cases (return value of 2 is still evaluated as unary boolean TRUE)
    return (in == NULL_KEY);  // key is valid AND equal to NULL_KEY (return 1 or TRUE), or is not valid (return 0 or FALSE)
}

/**
 * Checks whether the given string contains only integers
 * @param string
 * @return boolean
 */
integer IsInteger(string var) {
    integer i;
    for (i = 0; i < llStringLength(var); i++) {
        if(!~llListFindList(["1","2","3","4","5","6","7","8","9","0"], [llGetSubString(var, i, i)])) {
            return FALSE;
        }
    }
    return TRUE;
}
/**
 * Sets the UUID of the given element
 *
 * @param string type - [slide, page, image]
 * @param integer id - number of the element, for example slide number
 * @param key uuid - the element's UUID
 */
set_uuid_of_object(string type, integer id, key uuid) {
    if(debug) llInstantMessage(userUuid, "[Debug] Update "+ type +": "+ id + " to UUID:"+ uuid);
    string body = "uuid="+ (string)uuid +"&gridId="+ (string)serverId;
    if(type == "slide") {
        http_request_set = llHTTPRequest(serverUrl +"/presentation/"+ itemId +"/slide/number/"+ id +"/?token="+ APIToken, [HTTP_METHOD, "PUT", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    } else if(type == "page") {
        http_request_set = llHTTPRequest(serverUrl +"/document/"+ itemId +"/page/number/"+ id +"/?token="+ APIToken, [HTTP_METHOD, "PUT", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    } else if(type == "image") {
        http_request_set = llHTTPRequest(serverUrl +"/file/"+ itemId +"/image/?token="+ APIToken, [HTTP_METHOD, "PUT", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    }
}

/**
 * Requesting a new API token for this session
 */
request_api_token() {
    if(debug) llInstantMessage(userUuid, "[Debug] Requesting new API token");
    string body = "username="+ APIUsername +"&password="+ APIPassword;
    http_request_api_token = llHTTPRequest(serverUrl +"/auth/username/", [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

/**
 * Load the user's files
 */
load_users_files() {
    llInstantMessage(userUuid, "Searching for your documents... Please be patient");
    http_request_files = llHTTPRequest(serverUrl +"/grid/"+ serverId +"/avatar/"+ userUuid +"/files/?token="+ APIToken, [], "");
}


/**
 * Load the comments for the selected item
 *
 * @param integer id
 * @param string type
 */
load_item_comments(integer index, string type) {
    if(debug) llInstantMessage(userUuid, "Loading comments for "+ type +" with ID: "+ llList2String(itemIds, index));
    http_request_comments = llHTTPRequest(serverUrl +"/comments/"+ type +"/"+ llList2String(itemIds, index) +"/?token="+ APIToken, [], "");
}

/**
 * Processes all comments and return them as a string
 *
 * @param string rawComments
 */
parse_comments(string rawComments) {
    if(debug) llInstantMessage(userUuid, "Parsing comments: JSON");
    key json        = JsonCreateStore(rawComments);
    integer count   = (integer) JsonGetValue(json, "commentCount");
    string buffer   = "";
    integer i       = 0;
    llSay(0, "Showing "+ count +" comments --------------------------------------------------------");

    for(i = 0; i < JsonGetArrayLength(json, "comments"); i++) {
        if(debug) llInstantMessage(userUuid, "Processing comment "+ (i + 1) + " of "+ count);

        buffer += "["+ JsonGetValue(json, "comments["+ i +"].timestamp") +"] "+ JsonGetValue(json, "comments["+ i +"].user.{username}") +" ("+ JsonGetValue(json, "comments["+ i +"].user.{firstName}") +" "+ JsonGetValue(json, "comments["+ i +"].user.{firstName}") + "):\n"+ JsonGetValue(json, "comments["+ i +"].message") +"\n\n";

        if((integer) JsonGetValue(json, "comments["+ i +"].childrenCount") > 0) {
            buffer += parse_comment_childs(JsonGetJson(json, "comments["+ i +"].children"), 1);
        }
    }

    // Output the buffer
    integer length = 0;
    i  = 0;
    while(llStringLength(buffer) > 1 && i < count) {
        if(llStringLength(buffer) > 1000) {
            length = 1000;
        } else {
            length = llStringLength(buffer);
        }

        string partial  = llGetSubString(buffer, 0, length-1);
        buffer          = llGetSubString(buffer, llStringLength(partial)-1, llStringLength(buffer) -1);
        llSay(0, "\n"+ partial);
        i++;
    }
    llSay(0, "-------------------------------------------------------------------------------------");
}

/**
 * Recursive children parse function
 *
 * @param string comments
 * @param integer level
 * @return string
 */
string parse_comment_childs(string comments, integer level) {
    key json        = JsonCreateStore(comments);
    integer count   = JsonGetArrayLength(json, "");
    integer i       = 0;
    string tab      = "";
    string result   = "";
    for(i = 0; i < level; i++) {
        tab = tab +"\t\t";
    }

    for(i = 0; i < count; i++) {
        result += tab + strReplace(strReplace("["+ JsonGetValue(json, "["+ i +"].timestamp") +"] "+ JsonGetValue(json, "["+ i +"].user.{username}") +" ("+ JsonGetValue(json, "["+ i +"].user.{firstName}") +" "+ JsonGetValue(json, "["+ i +"].user.{firstName}") + "):\n"+ JsonGetValue(json, "["+ i +"].message"), "\n", "\r"), "\r", "\n"+ tab) +"\n\n";

        if((integer) JsonGetValue(json, "["+ i +"].childrenCount") > 0) {
            result += parse_comment_childs(JsonGetJson(json, "["+ i +"].children"), (level+1));
        }
    }
    return result;
}

/**
 * String replace function
 *
 * @source http://lslwiki.net/lslwiki/wakka.php?wakka=LibraryStringReplace
 * @param source
 * @param pattern
 * @param replace
 * @return
 */
string strReplace(string source, string pattern, string replace) {
    integer last = -1;
    while (llSubStringIndex(source, pattern) > -1) {
        integer len = llStringLength(pattern);
        integer pos = llSubStringIndex(source, pattern);
        if (llStringLength(source) == len) { source = replace; }
        else if (pos == 0) { source = replace+llGetSubString(source, pos+len, -1); }
        else if (pos == llStringLength(source)-len) { source = llGetSubString(source, 0, pos-1)+replace; }
        else { source = llGetSubString(source, 0, pos-1)+replace+llGetSubString(source, pos+len, -1); }
    }
    return source;
}

/**
 * Loads the given slide number
 * @param integer next
 * @param string type
 */
nav(integer next, string type) {
    // Check if item is not out of bounds
    if(next < 1) { next = 1; }
    // Allow totalItems+1 for black
    if(type == "slide" && next > totalItems) {
        item = totalItems + 1;
        llSetText("Presentation Ended", <0,0,1>, 1.0);
        llSetColor(ZERO_VECTOR, ALL_SIDES);
    // Allow totalItems+1 for black
    } else if(type == "page" && next > totalItems) {
        item = totalItems + 1;
        llSetText("Document Ended", <0,0,1>, 1.0);
        llSetColor(ZERO_VECTOR, ALL_SIDES);
    // All fine, show item
    } else {
        // Remove black screen when returning to presentation or document
        if(((type == "page" && item == (totalItems+1)) || (type == "slide" && item == (totalItems+1))) && next < item) {
            // Remove black screen
            llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
        }

        // Update item number
        item        = next;
        string url  = llList2String(itemsList, next-1);
        integer res = llListFindList(textureCache, [itemId, next]);
        string params = "width:"+ width +",height:"+ height;

        // Check if texture is found in cache, only required on first usage
        if(res > -1) {
            string texture = llList2String(textureCache, res+2);
            if(debug) llInstantMessage(userUuid, "[Debug] Loading "+ type +" "+ item +" by local uuid from cache (" + texture +")");
            llSetTexture(texture, 1);
        // Check if requested image has a valid UUID in the database
        } else if(isKey(url) == 2 && llGetSubString(url, 0, 3) != "http") {
            if(debug) llInstantMessage(userUuid, "[Debug] Loading "+ type +" "+ item +" by remote uuid from cache (" + url +")");
            llSetTexture(url, 1);
        // Load texture from remote server
        } else {
            if(debug) llInstantMessage(userUuid, "[Debug] Loading "+ type +" "+ item +" by url (" + url +")");
            // Previous texture
            string oldtexture = llGetTexture(0);

            // Load new image
            string texture = osSetDynamicTextureURL("", "image", url +"?token="+ APIToken, params, 0);

            if(debug) llInstantMessage(userUuid, "[Debug] Loaded slide/page");
            // Keep trying to fetch the new texture from object
            while((texture = llGetTexture(0)) == oldtexture)
                llSleep(1.0);

            // Remove previous texture
            llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
            llSetTexture(TEXTURE_BLANK, ALL_SIDES);
            llSetTexture(texture, 1);

            // add new texture to list in format [presentation ID, slide number, texture UUID]
            textureCache += [itemId, next, texture];
            textureCache = llListSort(textureCache, 3, TRUE);
            // Update UUID in remote database
            set_uuid_of_object(type,  item, texture);
        }

        llSetText(type +" "+ (item) +" of "+ totalItems, <0,0,1>, 1.0);
    }
}

/**
 * The default state, when the object is turned on, but no type of content is selected
 */
default {
    /**
     * Actions performed when entering the default state
     */
    state_entry() {
        // Message the surroundings
        if(debug) llSay(0, "turning on!");

        llSetText("", <0,0,0>, 0);
        // Set color to white and remove textures
        llSetTexture(TEXTURE_BLANK, ALL_SIDES);
        llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
        // Get new API token
        request_api_token();

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");
        // Open main menu
        open_menu(userUuid, mainNavigationText, mainNavigationButtons);
    }

    /**
     * Actions performed when user touches the object
     */
    touch_start(integer totalNumber) {
        // Close any open menu's
        close_menu();
        // Remove old main listener
        llListenRemove(mListener);

        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Get the object's UUID
        objectUuid = llGetKey();

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");
        // Open main menu
        open_menu(userUuid, mainNavigationText, mainNavigationButtons);
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
        }
    }

    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        list commands = llParseString2List(message, " ", []);
        if(llList2String(commands, 0) == "Presentation") {
            llInstantMessage(userUuid, "Entering presentation mode");
            state presentation;
        } else if(llList2String(commands, 0) == "Document") {
            llInstantMessage(userUuid, "Entering Document mode");
            state document;
        } else if(llList2String(commands, 0) == "Image") {
            llInstantMessage(userUuid, "Entering Image mode");
            state image;
        } else if(llList2String(commands, 0) == "UUID") {
            llInstantMessage(userUuid, "Object's UUID is: "+ objectUuid);
        // Shutdown
        } else if(llList2String(commands, 0) == "Quit") {
            media = 0;
            // Close any open menu's
            close_menu();
            state off;
        } else {

        }
    }

    /**
     * Actions performed when timer is finished
     */
    timer() {
        close_menu();
    }
}

/**
 * State of the object when presentation mode is selected
 */
state presentation {
    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        list commands = llParseString2List(message, " ", []);

        // When only a number has been given
        if(IsInteger(llList2String(commands, 0))) {
            string loadId   = llList2String(commands, 0);
            commands        = ["Load", loadId];
        }

        // Load a presentation by ID
        if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) != "#") {
            media = 1;
            // Output
            if(debug) llInstantMessage(userUuid, "[Debug] Loading presentation: "+ llList2String(commands, 1));
            // Sets presentation Id
            itemId = llList2String(commands, 1);
            // Loads JSON from server
            http_request_id = llHTTPRequest(serverUrl +"/presentation/"+ itemId +"/?token="+ APIToken, [], "");
        // Show dialog to load a specific presentation
        } else if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) == "#") {
            llTextBox(userUuid, "Enter the ID of the presentation you want to load.\nFor example if you want to load a presentation with ID 32 enter the number 32 and press Send", channel);
        // Shutdown
        } else if(llList2String(commands, 0) == "Quit") {
            media = 0;
            // Close any open menu's
            close_menu();
            state off;
        // Back to main menu
        } else if(llList2String(commands, 0) == "Main") {
            state default;
        // New document
        } else if(llList2String(commands, 0) == "New") {
            load_users_files();
        }

        // Only execute when a media object has been loaded
        if(media == 1) {
            // Previous Slide
            if(llList2String(commands, 0) == "Back") {
                nav(item - 1, "slide");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // Next Slide
            } else if(llList2String(commands, 0) == "Next") {
                nav(item + 1, "slide");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // First Slide
            } else if(llList2String(commands, 0) == "First") {
                nav(1, "slide");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            } else if(llList2String(commands, 0) == "Comments") {
                load_item_comments((item - 1), "slide");
            // Invalid command
            } else {
                // Other
            }
        }
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);

            // API key expired
            if(status == 401) {
                llInstantMessage(userUuid, "The API key expired, turn the presenter off and on again: ");
                open_menu(userUuid, mainNavigationText, mainNavigationButtons);
            } else {
                // Send a more specific and meaningful response to the user
                if(request_id == http_request_files) {
                    llInstantMessage(userUuid, "User not found");
                } else if(request_id == http_request_id) {
                    llInstantMessage(userUuid, "Presentation not found");
                }
            }
            return;
        }

        // Loaded presentation
        if(request_id == http_request_id) {
            // Parse the returned body to JSON
            key json_body       = JsonCreateStore(body);

            // Error occured during loading of presentation?
            if(JsonGetValue(json_body, "error") != "") {
                llInstantMessage(userUuid, "Presentation not found");
                return;
            }

            string slides_body  = JsonGetJson(json_body, "slides");
            // Parse the slides section
            key json_slides     = JsonCreateStore(slides_body);
            // Empty slides and cache list
            list empty          = [];
            textureCache        = empty;
            itemsList           = empty;
            itemIds             = empty;
            if(debug) llInstantMessage(userUuid, "[Debug] Slide list is currently: "+ (string) itemsList);
            integer x;
            integer length      = (integer) JsonGetValue(json_body, "slidesCount");
            // Get from each slide the URL or the UUID
            for (x = 0; x < length; x++) {
                string slideUuid     = "";
                string slideExpired  = "";
                // Only process if there is a cache (to prevent console warnings)
                if(JsonGetJson(json_slides, "["+ x +"].cache") != "[]") {
                    slideUuid        = JsonGetValue(json_slides, "["+ x +"].cache.{"+ serverId +"}.uuid");
                    slideExpired     = JsonGetValue(json_slides, "["+ x +"].cache.{"+ serverId +"}.isExpired");
                }
                string slideUrl      = JsonGetValue(json_slides, "["+ x +"].image");
                itemIds             += [JsonGetValue(json_slides, "["+ x +"].id")];
                // UUID set and not expired?
                if(slideUuid != "" && slideExpired == "0") {
                    itemsList += [(key) slideUuid];
                    if(debug) llInstantMessage(userUuid, "[Debug] use UUID ("+ slideUuid +") for slide: "+ (x+1));
                // Use URL
                } else {
                    itemsList += [slideUrl];
                    if(debug) llInstantMessage(userUuid, "[Debug] use URL ("+ slideUrl +") for slide: "+ (x+1));
                }
            }

            // Count the slides
            totalItems = (integer)JsonGetValue(json_body, "slidesCount");
            // Get presentation title
            itemTitle  = JsonGetValue(json_body, "title");
            // Show loaded message
            llInstantMessage(userUuid, "Loaded presentation: "+ itemTitle);
            // loads the first slide
            nav(1, "slide");
            // Open navigation dialog
            open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
        // Loaded user's documents
        } else if(request_id == http_request_files) {
            key json_body               = JsonCreateStore(body);
            integer presentationCount   = 0;
            string json_presentations   = JsonGetJson(json_body, "");
            integer filesCount          = JsonGetArrayLength(json_body, "");
            list presentations          = [];
            // Create buttons for max 12 presentations
            list presentationButtons;
            if(debug) llInstantMessage(userUuid, "[Debug] Found "+ filesCount + " files.");
            // List with presentations is not empty?
            if(filesCount > 0) {
                integer x;

                // List all presentations
                for(x = 0; x < filesCount; x++) {
                    if(JsonGetValue(json_body, "["+ x +"].type") == "presentation") {
                        presentations += [JsonGetValue(json_body, "["+ x +"].id")];
                    }
                }

                // Newest presentations first
                presentations = llListSort(presentations, 1, FALSE);

                // Count presentations
                presentationCount = llGetListLength(presentations);
                if(debug) llInstantMessage(userUuid, "[Debug] Containing "+ presentationCount + " presentations.");

                // Create buttons for presentations
                for (x = 0; x < presentationCount && x < 13; x++) {
                    presentationButtons += ["Load "+ llList2String(presentations, x)];
                }
            }

            presentationButtons += ["Main","Quit","Load #"];
            // Open presentation selection menu
            open_menu(userUuid, "Found "+ presentationCount +" presentation(s).\nShowing only the latest 9 presentations below.\nCommand: '/"+ channel +" Load <#>' can be used to load a presentation that is not listed.\nIf your avatar is not linked to your CMS user account, the list will be empty." , presentationButtons);
        // Update slide uuid
        } else if(request_id = http_request_set) {
            if(debug) llInstantMessage(userUuid, "[Debug] UUID set for slide "+ item +": "+ (string) body);
        // Loaded comments
        } else if(request_id = http_request_comments) {
            parse_comments(body);
        // HTTP response which isn't requested?
        } else {
            return;
        }
    }

    /**
     * Initial actions when entering the presentation state
     */
    state_entry() {
        // Close any open menu's
        close_menu();
        // Remove old main listener
        llListenRemove(mListener);

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Load files
        load_users_files();
    }

    /**
     * Actions performed when a user touches the object
     */
    touch_start(integer totalNumber) {
        // Close any open menu's
        close_menu();

        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Get the object's UUID
        objectUuid = llGetKey();

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Open menu when continuing usage
        if(media == 1) {
            open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
        // Open presentations menu
        } else {
            load_users_files();
        }
    }

    /**
     * Actions performed when timer is finished
     */
    timer() {
        close_menu();
    }
}

/**
 * State of object when viewing a document
 */
state document {
    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        list commands = llParseString2List(message, " ", []);

        // When only a number has been given
        if(IsInteger(llList2String(commands, 0))) {
            string loadId   = llList2String(commands, 0);
            commands        = ["Load", loadId];
        }

        // Load presentation by ID
        if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) != "#") {
            media = 1;
            // Output
            if(debug) llInstantMessage(userUuid, "[Debug] Loading document: "+ llList2String(commands, 1));
            // Sets document Id
            itemId = llList2String(commands, 1);
            // Loads JSON from server
            http_request_id = llHTTPRequest(serverUrl +"/document/"+ itemId +"/?token="+ APIToken, [], "");
        // Show dialog to load a specific document
        } else if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) == "#") {
            llTextBox(userUuid, "Enter the ID of the document you want to load.\nFor example if you want to load a document with ID 32 enter the number 32 and press Send", channel);
        // Shutdown
        } else if(llList2String(commands, 0) == "Quit") {
            media = 0;
            // Close any open menu's
            close_menu();
            state off;
        // Back to main menu
        } else if(llList2String(commands, 0) == "Main") {
            state default;
        // New document
        } else if(llList2String(commands, 0) == "New") {
            load_users_files();
        }

        // Only execute when a media object has been loaded
        if(media == 1) {
            // Previous Slide
            if(llList2String(commands, 0) == "Back") {
                nav(item - 1, "page");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // Next Slide
            } else if(llList2String(commands, 0) == "Next") {
                nav(item + 1, "page");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // First Slide
            } else if(llList2String(commands, 0) == "First") {
                nav(1, "page");
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            } else if(llList2String(commands, 0) == "Comments") {
                load_item_comments((item - 1), "page");
            // Invalid command
            } else {
                // Other
            }
        }
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);

            // API key expired
            if(status == 401) {
                llInstantMessage(userUuid, "The API key expired, turn the presenter off and on again: ");
                open_menu(userUuid, mainNavigationText, mainNavigationButtons);
            } else {
                // Send a more specific and meaningful response to the user
                if(request_id == http_request_files) {
                    llInstantMessage(userUuid, "User not found");
                } else if(request_id == http_request_id) {
                    llInstantMessage(userUuid, "Document not found");
                }
            }
            return;
        }

        // Loaded documents
        if(request_id == http_request_id) {
            // Parse the returned body to JSON
            key json_body       = JsonCreateStore(body);

            // Error occured during loading of document?
            if(JsonGetValue(json_body, "error") != "") {
                llInstantMessage(userUuid, "Document not found");
                return;
            }

            string pages_body  = JsonGetJson(json_body, "pages");
            // Parse the pages section
            key json_pages     = JsonCreateStore(pages_body);
            // Empty pages and cache list
            list empty          = [];
            textureCache        = empty;
            itemsList           = empty;
            itemIds             = empty;
            if(debug) llInstantMessage(userUuid, "[Debug] Page list is currently: "+ (string) itemsList);
            integer x;
            integer length      = (integer) JsonGetValue(json_body, "pagesCount");
            // Get from each page the URL or the UUID
            for (x = 0; x < length; x++) {
                string pageUuid     = "";
                string pageExpired  = "";
                // Only process if there is a cache (to prevent console warnings)
                if(JsonGetJson(json_pages, "["+ x +"].cache") != "[]") {
                    pageUuid        = JsonGetValue(json_pages, "["+ x +"].cache.{"+ serverId +"}.uuid");
                    pageExpired     = JsonGetValue(json_pages, "["+ x +"].cache.{"+ serverId +"}.isExpired");
                }
                string pageUrl      = JsonGetValue(json_pages, "["+ x +"].image");
                itemIds             += [JsonGetValue(json_pages, "["+ x +"].id")];

                // UUID set and not expired?
                if(pageUuid != "" && pageExpired == "0") {
                    itemsList += [(key) pageUuid];
                    if(debug) llInstantMessage(userUuid, "[Debug] use UUID ("+ pageUuid +") for page: "+ (x+1));
                // Use URL
                } else {
                    itemsList += [pageUrl];
                    if(debug) llInstantMessage(userUuid, "[Debug] use URL ("+ pageUrl +") for page: "+ (x+1));
                }
            }

            // Count the pages
            totalItems = (integer)JsonGetValue(json_body, "pagesCount");
            // Get documents title
            itemTitle  = JsonGetValue(json_body, "title");
            // Show loaded message
            llInstantMessage(userUuid, "Loaded document: "+ itemTitle);
            // loads the first page
            nav(1, "page");
            // Open navigation dialog
            open_menu(userUuid, documentNavigationText, documentNavigationButtons);
        // Loaded user's documents
        } else if(request_id == http_request_files) {
            key json_body               = JsonCreateStore(body);
            string json_documents       = JsonGetJson(json_body, "");
            integer documentCount       = 0;
            integer filesCount          = JsonGetArrayLength(json_body, "");
            list documents              = [];
            // Create buttons for max 12 documents
            list documentButtons;
            if(debug) llInstantMessage(userUuid, "[Debug] Found "+ filesCount + " files.");
            // List with documents is not empty?
            if(filesCount > 0) {
                integer x;
                // List all presentations
                for(x = 0; x < filesCount; x++) {
                    if(JsonGetValue(json_body, "["+ x +"].type") == "document") {
                        documents += [JsonGetValue(json_body, "["+ x +"].id")];
                    }
                }

                // Newest presentations first
                documents = llListSort(documents, 1, FALSE);

                // Count presentations
                documentCount = llGetListLength(documents);
                if(debug) llInstantMessage(userUuid, "[Debug] Containing "+ documentCount + " documents.");

                // Create buttons for presentations
                for (x = 0; x < documentCount && x < 11; x++) {
                    documentButtons += ["Load "+ llList2String(documents, x)];
                }
            }
            documentButtons += ["Main","Quit","Load #"];
            // Open presentation selection menu
            open_menu(userUuid, "Found "+ documentCount +" document(s).\nShowing only the latest 9 documents below.\nCommand: '/"+ channel +" Load <#>' can be used to load a document that is not listed.\nIf your avatar is not linked to your CMS user account, the list will be empty." , documentButtons);
        // Update page uuid
        } else if(request_id = http_request_set) {
            if(debug) llInstantMessage(userUuid, "[Debug] UUID set for page "+ item +": "+ (string) body);
        // Loaded comments
        } else if(request_id = http_request_comments) {
            parse_comments(body);
        // HTTP response which isn't requested?
        } else {
            return;
        }
    }

    /**
     * Initial actions when entering the document state
     */
    state_entry() {
        // Close any open menu's
        close_menu();
        // Remove old main listener
        llListenRemove(mListener);

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Load documents
        load_users_files();
    }

    /**
     * Actions performed when a user touches the object
     */
    touch_start(integer totalNumber) {
        // Close any open menu's
        close_menu();

        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Get the object's UUID
        objectUuid = llGetKey();

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Open menu when continuing usage
        if(media == 1) {
            open_menu(userUuid, documentNavigationText, documentNavigationButtons);
        // Open documents menu
        } else {
            load_users_files();
        }
    }

    /**
     * Actions performed when timer is finished
     */
    timer() {
        close_menu();
    }
}

/**
 * State of object when viewing an image
 */
state image {
    /**
     * Listen and fetch certain commands
     */
    listen(integer channel, string name, key id, string message) {
        list commands = llParseString2List(message, " ", []);

        // When only a number has been given
        if(IsInteger(llList2String(commands, 0))) {
            string loadId   = llList2String(commands, 0);
            commands        = ["Load", loadId];
        }

        // Load presentation by ID
        if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) != "#") {
            media = 1;
            // Output
            if(debug) llInstantMessage(userUuid, "[Debug] Loading image: "+ llList2String(commands, 1));
            // Sets document Id
            itemId = llList2String(commands, 1);
            // Loads JSON from server
            http_request_id = llHTTPRequest(serverUrl +"/file/"+ itemId +"/?token="+ APIToken, [], "");
        // Show dialog to load a specific document
        } else if(llList2String(commands, 0) == "Load" && llList2String(commands, 1) == "#") {
            llTextBox(userUuid, "Enter the ID of the image you want to load.\nFor example if you want to load an image with ID 32 enter the number 32 and press Send", channel);
        // Shutdown
        } else if(llList2String(commands, 0) == "Quit") {
            media = 0;
            // Close any open menu's
            close_menu();
            state off;
        // Back to main menu
        } else if(llList2String(commands, 0) == "Main") {
            state default;
        // New document
        } else if(llList2String(commands, 0) == "New") {
            load_users_files();
        }

        // Only execute when a media object has been loaded
        if(media == 1) {
            if(llList2String(commands, 0) == "Comments") {
                load_item_comments((item - 1), "file");
            // Invalid command
            } else {
                // Other
            }
        }
    }

    /**
     * Actions to be taken when a HTTP request gets a response
     */
    http_response(key request_id, integer status, list metadata, string body) {
        // Catch errors
        if(status != 200) {
            if(debug) llInstantMessage(userUuid, "[Debug] HTTP Request returned status: " + status);

            // API key expired
            if(status == 401) {
                llInstantMessage(userUuid, "The API key expired, turn the presenter off and on again: ");
                open_menu(userUuid, mainNavigationText, mainNavigationButtons);
            } else {
                // Send a more specific and meaningful response to the user
                if(request_id == http_request_files) {
                    llInstantMessage(userUuid, "User not found");
                } else if(request_id == http_request_id) {
                    llInstantMessage(userUuid, "Image not found");
                }
            }
            return;
        }

        // Loaded image
        if(request_id == http_request_id) {
            // Parse the returned body to JSON
            key json_body       = JsonCreateStore(body);

            // Error occured during loading of presentation?
            if(JsonGetValue(json_body, "error") != "") {
                llInstantMessage(userUuid, "Image not found");
                return;
            }

            // Empty pages and cache list
            list empty          = [];
            textureCache        = empty;
            itemsList           = empty;
            itemIds             = empty;

            string imageUuid     = "";
            string imageExpired  = "";
            // Only process if there is a cache (to prevent console warnings)
            if(JsonGetJson(json_body, "cache") != "[]") {
                imageUuid        = JsonGetValue(json_body, "cache.{"+ serverId +"}.uuid");
                imageExpired     = JsonGetValue(json_body, "cache.{"+ serverId +"}.isExpired");
            }
            string imageUrl      = JsonGetValue(json_body, "url") +"image/";
            itemIds             += [JsonGetValue(json_body, "id")];
            // UUID set and not expired?
            if(imageUuid != "" && imageExpired == "0") {
                itemsList += [(key) imageUuid];
                if(debug) llInstantMessage(userUuid, "[Debug] use UUID ("+ imageUuid +") for image");
            // Use URL
            } else {
                itemsList += [imageUrl];
                if(debug) llInstantMessage(userUuid, "[Debug] use URL ("+ imageUrl +") for image");
            }

            // Count the pages
            totalItems = 1;
            // Get presentation title
            itemTitle  = JsonGetValue(json_body, "title");
            // Show loaded message
            llInstantMessage(userUuid, "Loaded image: "+ itemTitle);
            // loads image
            nav(1, "image");
            // Open navigation dialog
            open_menu(userUuid, imageNavigationText, imageNavigationButtons);
        // Loaded user's images
        } else if(request_id == http_request_files) {
            key json_body               = JsonCreateStore(body);
            string json_images          = JsonGetJson(json_body, "");
            integer imagesCount         = 0;
            integer filesCount          = JsonGetArrayLength(json_body, "");
            list images                 = [];
            // Create buttons for max 12 images
            list imagesButtons;
            if(debug) llInstantMessage(userUuid, "[Debug] Found "+ filesCount + " files.");
            // List with files is not empty?
            if(filesCount > 0) {
                integer x;
                // List all presentations
                for(x = 0; x < filesCount; x++) {
                    if(JsonGetValue(json_body, "["+ x +"].type") == "image") {
                        images += [JsonGetValue(json_body, "["+ x +"].id")];
                    }
                }

                // Newest presentations first
                images = llListSort(images, 1, FALSE);

                // Count presentations
                imagesCount = llGetListLength(images);
                if(debug) llInstantMessage(userUuid, "[Debug] Containing "+ imagesCount + " images.");

                // Create buttons for presentations
                for (x = 0; x < imagesCount && x < 11; x++) {
                    imagesButtons += ["Load "+ llList2String(images, x)];
                }
            }
            imagesButtons += ["Main","Quit","Load #"];
            // Open presentation selection menu
            open_menu(userUuid, "Found "+ imagesCount +" document(s).\nShowing only the latest 9 images below.\nCommand: '/"+ channel +" Load <#>' can be used to load an image that is not listed.\nIf your avatar is not linked to your CMS user account, the list will be empty." , imagesButtons);
        // Update image uuid
        } else if(request_id = http_request_set) {
            if(debug) llInstantMessage(userUuid, "[Debug] UUID set for image "+ itemId +": "+ (string) body);
        } else if(request_id = http_request_comments) {
            parse_comments(body);
        // HTTP response which isn't requested?
        } else {
            return;
        }
    }

    /**
     * Initial actions when entering the presentation state
     */
    state_entry() {
        // Close any open menu's
        close_menu();
        // Remove old main listener
        llListenRemove(mListener);

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Load presentations
        load_users_files();
    }

    /**
     * Actions performed when a user touches the object
     */
    touch_start(integer totalNumber) {
        // Close any open menu's
        close_menu();

        // Get the toucher's UUID
        userUuid = llDetectedKey(0);
        // Get the object's UUID
        objectUuid = llGetKey();

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

        // Open menu when continuing usage
        if(media == 1) {
            open_menu(userUuid, imageNavigationText, imageNavigationButtons);
        // Open images menu
        } else {
            load_users_files();
        }
    }

    /**
     * Actions performed when timer is finished
     */
    timer() {
        close_menu();
    }
}
/**
 * State when object is turned off
 */
state off {
    /**
     * Actions performed when entering the off state
     */
    state_entry() {
        llSetText("", <0,0,0>, 0);
        llSay(0, "turning off!");

        // Clear cache
        list empty = [];
        textureCache = empty;
        // Set color to black
        llSetColor(ZERO_VECTOR, ALL_SIDES);
        llSetTexture(TEXTURE_BLANK, ALL_SIDES);
        llListenRemove(gListener);
        llListenRemove(mListener);
    }

    /**
     * Actions performed when user touches the object
     * Turn it on!
     */
    touch_start(integer totalNumber) {
        state default;
    }
}