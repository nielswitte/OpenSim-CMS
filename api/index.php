<?php
require_once dirname(__FILE__) .'/../config.php';
require_once dirname(__FILE__) .'/auth.php';
require_once dirname(__FILE__) .'/api.php';

/**
 * This class is catches the API calls and searches for the matching function
 *
 * @author Niels Witte
 * @version 0.3
 * @date February 10th, 2014
 */

// Remove expired tokens
Auth::removeExpiredTokens();

// Try to parse the requested URL and paramters to a function of the API
try {
	// Input
	$get        = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
    $token      = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
    $selectors  = array();

    // Auth user
    $auth       = new Auth();
    $auth->setToken($token);
    $authorized = $auth->validate();

    // Create new API
    $api        = new API();
    // List with URL selectors and the corresponding functions to be used for each request type
    // Authorized selectors (require token)
    $api->addRoute("/presentations\/?$/",                               "getPresentations",     "GET",  TRUE);  // Get list with 50 presentations
    $api->addRoute("/presentations\/(\d+)\/?$/",                        "getPresentations",     "GET",  TRUE);  // Get list with 50 presentations starting at the given offset
    $api->addRoute("/presentation\/(\d+)\/?$/",                         "getPresentationById",  "GET",  TRUE);  // Select specific presentation
    $api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/?$/",           "getSlideById",         "GET",  TRUE);  // Get slide from presentation
    $api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/?$/",           "updateSlideUuid",      "PUT",  TRUE);  // Update slide UUID for given slide of presentation
    $api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/image\/?$/",    "getSlideImageById",    "GET",  TRUE);  // Get only the image of a given presentation slide
    $api->addRoute("/users\/([a-zA-Z0-9-_]{3,}+)\/?$/",                 "getUsersByUserName",   "GET",  TRUE);  // Gets a list of all users with usernames matching the search of atleast 3 characters
    $api->addRoute("/user\/(\d+)\/?$/",                                 "getUserById",          "GET",  TRUE);  // Get a user by ID
    $api->addRoute("/user\/([a-z0-9-]{36})\/teleport\/?$/",             "teleportAvatarByUuid", "PUT",  TRUE);  // Teleports a user
    $api->addRoute("/user\/avatar\/?$/",                                "createAvatar",         "POST", TRUE);  // Create an avatar
    $api->addRoute("/grid\/(\d+)\/?$/",                                 "getGridById",          "GET",  TRUE);  // Get grid information by ID
    $api->addRoute("/grid\/(\d+)\/region\/([a-z0-9-]{36})\/?$/",        "getRegionByUuid",      "GET",  TRUE);  // Get information about the given region
    $api->addRoute("/grid\/(\d+)\/region\/([a-z0-9-]{36})\/image\/?$/", "getRegionImageByUuid", "GET",  TRUE);  // Get the map of the region
    $api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "getUserByAvatar",      "GET",  TRUE);  // Gets an user by the avatar of this grid
    $api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "matchAvatarToUser",    "PUT",  TRUE);  // Update the UUID of a user to match an avatar

    // Public selectors
    $api->addRoute("/auth\/user\/?$/",                                  "authUser",             "POST", FALSE); // Authenticate the given user

    // Match the route to a function
    $result = $api->getRoute($get, $authorized);

    // Wrong request?
    if($result === FALSE) {
        header("HTTP/1.1 400 Bad Request");
        throw new Exception("Invalid API URL used", 1);
    }
// Catch any exception that occured
} catch (Exception $e) {
	$result["Exception"]    = $e->getMessage();
    if(SERVER_DEBUG) {
        $result["Code"]     = $e->getCode();
        $result["File"]     = $e->getFile();
        $result["Line"]     = $e->getLine();
        $result["Trace"]    = $e->getTrace();
    }
}

$headers = getallheaders();

// Any result to parse?
if($result != '') {
    header('Content-Type: application/json');

    // Output to human readable or compact fast parsable code?
    if(isset($headers['User-Agent'])) {
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo json_encode($result);
    }
}

/*
$data = json_encode($_SERVER, JSON_PRETTY_PRINT);
foreach($headers as $header => $value) {
    $data .= $header .': '. $value ."\n";
}
file_put_contents('headers.txt', $data);
*/