/**
 * Makes a image presentation screen of any surface. 
 * Uses JSON to retrieve presentation data from server
 *
 * @author Niels Witte
 * @date Febraury 11th, 2014
 * @version 0.2
 */
// Config values
string serverUrl = "http://127.0.0.1:62535/CMS/api";
integer debug = 1;				// Enables showing debuggin comments

// Some general parameters
integer mListener;				// The main listener
integer gListener;             	// The navigation listener
key userUuid;                	// The toucher's UUID
key objectUuid;                	// The object's UUID
integer channel = 7;        	// The channel to be used
integer media = 0;            	// Media type [0 = off, 1 = presentation]
// Presentation stuff
string presentationId;        	// The Id of the presentation
string presentationTitle;    	// Title of the presentation
integer slide = 0;            	// Slide number (starts at 0)
integer totalslides = 0;    	// Total numnber of slides
list slides;                	// List with all slides
list textureCache;				// Cache the textures to only require loading once

// HTTP stuff
key http_request_id;        	// HTTP Request for loading presentation
key http_request_user;			// HTTP Request for loading user data
key http_request_set;			// HTTP Request to set UUID of object for future use
// Menu's
string mainNavigationText			= "What type of content do you want to use?";
list mainNavigationButtons			= ["Presentation", "Video"];
string presentationNavigationText 	= "Slideshow navigation";
list presentationNavigationButtons 	= ["First", "Back", "Next", "Quit", "New"];

open_menu(key inputKey, string inputString, list inputList) {
    gListener = llListen(channel, "", inputKey, "");
    // Send a dialog to that person. We'll use a fixed negative channel number for simplicity
    llDialog(inputKey, inputString, inputList , channel);
    llSetTimerEvent(300.0);
}
 
close_menu() {
    llSetTimerEvent(0.0);// you can use 0 as well to save memory
    llListenRemove(gListener);
}

/**
 * Function to validate keys
 */
integer isKey(key in) {//by: Strife Onizuka
    if(in) return 2;          // key is valid AND not equal NULL_KEY; the distinction is important in some cases (return value of 2 is still evaluated as unary boolean TRUE)
    return (in == NULL_KEY);  // key is valid AND equal to NULL_KEY (return 1 or TRUE), or is not valid (return 0 or FALSE)
}

/**
 * Searches JSON string for value of given key
 * Make sure all JSON values are strings or arrays (surrounded by "")
 *
 * @param string json - json string to search in
 * @param string search - key to search for
 * @returns string - Null on error
 */
string get_json_value(string json, string search) {
    string result;
    // Search for key
    integer start = llSubStringIndex(json, search + "\"");
    // After the key is found, strip everything before the key and include the leading " and tailing " ":"
    // starts counting at 0, hence the +4 - 1 = +3)
    if(start > -1) { 
        start = (start + llStringLength(search) + 3);
       // JSON value is an array
       if(llGetSubString(json, (start-1), (start-1)) == "[") {
            string remain = llGetSubString(json, (start-1), -1);
            // Search end of value
            integer end = llSubStringIndex(remain, "\"]");
            result = llGetSubString(remain, 0, (end + 1));
        // JSON value is a string
        } else {
            string remain = llGetSubString(json, start, -1);
            // Search end of value
            integer end = llSubStringIndex(remain, "\"");
            result = llGetSubString(remain, 0, (end - 1));
        }
    } else {
        result = "Null";
    }
    return result;
}

set_uuid_of_object(string type, integer id, key uuid) {
	if(type == "slide") {
		if(debug) llInstantMessage(userUuid, "[Debug] Update slide: "+ id + " to UUID:"+ uuid);
		
		string body = "uuid="+ (string)uuid;
		http_request_set = llHTTPRequest(serverUrl +"/presentation/"+ presentationId +"/slide/"+ id +"/", [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);		
	}
}

load_users_presentations() {
	llInstantMessage(userUuid, "Searching for your presentations... Please be patient");
	http_request_user = llHTTPRequest(serverUrl +"/user/"+ userUuid +"/", [], "");
}

/**
 * Loads the given slide number
 * @param integer next
 */
nav_slide(integer next) {

    // Check if slide is not out of bounds
    if(next < 0) { next = 0; }
    // Allow totalslides+1 for black
    if(next >= totalslides) { 
        slide = totalslides; 
        llSetText("Presentation Ended", <0,0,1>, 1.0);
        llSetColor(ZERO_VECTOR, ALL_SIDES);
    // All fine, show slide
    } else {
    	// Update slide number
    	slide = next;

        // Remove previous
        llSetTexture(TEXTURE_BLANK, ALL_SIDES);
        llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
        // Load slide
        string url          = llList2String(slides, next);
        string params       = "width: 1024,height:1024";
		
        integer res = llListFindList(textureCache, [presentationId, next]);
        // Check if texture is found in cache, only required on first usage
        if(res > -1) {   
        	if(debug) llInstantMessage(userUuid, "[Debug] Loading slide "+ slide +" by uuid from local cache (" + url +")");
        	llSetTexture(llList2String(textureCache, res+2), ALL_SIDES);
    	// Check if requested image has a valid UUID in the database
        } else if(isKey(url) == 2 && llGetSubString(url, 0, 3) != "http") {
        	if(debug) llInstantMessage(userUuid, "[Debug] Loading slide "+ slide +" by uuid from remote cache (" + url +")");
        	llSetTexture(url, ALL_SIDES);
    	// Load texture from remote server
        } else {
        	if(debug) llInstantMessage(userUuid, "[Debug] Loading slide "+ slide +" by url (" + url +")");
        	// Previous texture
        	string oldtexture = llGetTexture(0);

        	// Load new image
        	string texture = osSetDynamicTextureURL("", "image", url, params, 0);
			
			// Keep trying to fetch the new texture from object
			while((texture = llGetTexture(0)) == oldtexture) 
			     llSleep(1.0);

		 	// add new texture to list in format [presentation ID, slide number, texture UUID]
         	textureCache += [presentationId, next, texture];
        	textureCache = llListSort(textureCache, 3, TRUE);
        	// Update UUID in remote database
        	set_uuid_of_object("slide",  slide, texture);
        }

        llSetText("Slide "+ (slide + 1) +" of "+ totalslides, <0,0,1>, 1.0);
    }
}


default {
    state_entry() {
        // Message the surroundings
        llSay(0, "turning on!"); 
        llSetText("", <0,0,0>, 0);
        // Set color to white and remove textures
        llSetTexture(TEXTURE_BLANK, ALL_SIDES);
        llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
    }

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
     * Listen and fetch certain commands
     *
     */
    listen(integer channel, string name, key id, string message) {
    	list commands = llParseString2List(message, " ", []);
    	if(llList2String(commands, 0) == "Presentation") {
		  	llInstantMessage(userUuid, "Entering presentation mode");
    		state presentation;
    	} else if(llList2String(commands, 0) == "Video") {
			llInstantMessage(userUuid, "Entering video mode");
		} else if(llList2String(commands, 0) == "UUID") {
			llInstantMessage(userUuid, "Object's UUID is: "+ objectUuid);
		} else {

		}
    }

    timer() {    	
        close_menu();
    }
} 

state presentation {
    /**
     * Listen and fetch certain commands
     *
     */
    listen(integer channel, string name, key id, string message) {
        list commands = llParseString2List(message, " ", []);
        
        // Main commands
        if(llList2String(commands, 0) == "Load") {
            media = 1;
            // Output            
            llInstantMessage(userUuid, "Loading: "+ llList2String(commands, 1));
            // Sets presentation Id
            presentationId = llList2String(commands, 1);
            // Loads JSON from server
            http_request_id = llHTTPRequest(serverUrl +"/presentation/"+ presentationId +"/", [], "");
        // Shutdown
        } else if(llList2String(commands, 0) == "Quit") {
            media = 0;
            // Close any open menu's
            close_menu();
            state off;
        } else if(llList2String(commands, 0) == "New") {
        	load_users_presentations();
        }

      	if(media == 1) {
            // Previous Slide
            if(llList2String(commands, 0) == "Back") {
                nav_slide(slide - 1);
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // Next Slide
            } else if(llList2String(commands, 0) == "Next") {
                nav_slide(slide + 1);
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // First Slide
            } else if(llList2String(commands, 0) == "First") {
                nav_slide(0);
                open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
            // Invalid command
            } else {
                // Other
            }
        }
    }

    http_response(key request_id, integer status, list metadata, string body) {
    	if(status != 200) {
    		llInstantMessage(userUuid, "HTTP Request returned status: " + status);
    		return;
    	}
		// Loaded presentation
        if (request_id == http_request_id) {
	        string json_slides  = get_json_value(body, "openSim");
	        slides              = llParseString2List(json_slides, ["\",\"", "\"", "[", "]"], []);
	        totalslides         = (integer)get_json_value(body, "slidesCount");
	        presentationTitle   = get_json_value(body, "title");
	        // Show loaded message
	        llInstantMessage(userUuid, "Loaded presentation: "+ presentationTitle);
	        // loads the first slide
	        nav_slide(0);
	        // Open navigation dialog
	        open_menu(userUuid, presentationNavigationText, presentationNavigationButtons);
        // Loaded user's presentations
        } else if(request_id == http_request_user) {
			string json_presentations  = get_json_value(body, "presentationIds");
			list presentations = llParseString2List(json_presentations, ["\",\"", "\"", "[", "]"], []);

			// Newest presentations first
			presentations = llListSort(presentations, 1, FALSE);

			// Create buttons for 9 presentations
			list presentationButtons;
			integer x;
			for (x = 0; x < llGetListLength(presentations) && x < 10; x++) {
			    presentationButtons += "Load "+ llList2String(presentations, x);
			}

			open_menu(userUuid, "Loaded "+ llGetListLength(presentationButtons) +" presentation(s).\nCommand: '/"+ channel +" load <#>' can be used to load a presentation that is not listed below." , presentationButtons);
		// Update slide uuid
		} else if(request_id = http_request_set) {
			if(debug) llInstantMessage(userUuid, "[Debug] UUID set for slide "+ slide +": "+ (string) body);
        } else {
        	return;
        }
    }

    state_entry() {
        // Close any open menu's
        close_menu();
 		// Remove old main listener
        llListenRemove(mListener);

        // Listen at channel
        mListener = llListen(channel,"", userUuid,"");

		// Load presentations
        load_users_presentations();
    }

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
        // Oopen presentations menu
        } else {
			load_users_presentations();
        }  
    }

    timer() {
        close_menu();
    }
}

// Turn screen off
state off {
    state_entry() {
        llSetText("", <0,0,0>, 0);
        llSay(0, "turning off!");

        // Clear cache
        list empty;
        textureCache = empty;
        // Set color to black
        llSetColor(ZERO_VECTOR, ALL_SIDES);
        llSetTexture(TEXTURE_BLANK, 1);
        llListenRemove(gListener);
        llListenRemove(mListener);
    }

    touch_start(integer totalNumber) {
        state default;
    }
}