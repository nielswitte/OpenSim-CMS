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

$result = '';
// Try to parse the requested URL and paramters to a function of the API
try {
	// Input
	$get        = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
    $token      = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
    $selectors  = array();

    $auth       = new Auth();
    $auth->setToken($token);
    $authorized = $auth->validate();

    // List with URL selectors and the corresponding functions to be used for each request type
    // Authorized selectors (require token)
    $selectors["/presentations\/?$/"]                               = array("AUTH" => TRUE, "GET"  => "getPresentations");        // Get list with 50 presentations
    $selectors["/presentations\/(\d+)\/?$/"]                        = array("AUTH" => TRUE, "GET"  => "getPresentations");        // Get list with 50 presentations starting at the given offset
    $selectors["/presentation\/(\d+)\/?$/"]                         = array("AUTH" => TRUE, "GET"  => "getPresentationById");     // Select specific presentation
    $selectors["/presentation\/(\d+)\/slide\/(\d+)\/?$/"]           = array("AUTH" => TRUE, "GET"  => "getSlideById",             // Get slide from presentation
                                                                                            "PUT"  => "updateSlideUuid");         // Update slide UUID for given slide of presentation
    $selectors["/presentation\/(\d+)\/slide\/(\d+)\/image\/?$/"]    = array("AUTH" => TRUE, "GET"  => "getSlideImageById");       // Get only the image of a given presentation slide
    $selectors["/users\/([a-zA-Z0-9-_]{3,}+)\/?$/"]                 = array("AUTH" => TRUE, "GET"  => "getUsersByUserName");      // Gets a list of all users with usernames matching the search of atleast 3 characters
    $selectors["/user\/(\d+)\/?$/"]                                 = array("AUTH" => TRUE, "GET"  => "getUserById");             // Get a user by ID
    $selectors["/user\/([a-z0-9-]{36})\/?$/"]                       = array("AUTH" => TRUE, "GET"  => "getUserByUuid");           // Get a user by UUID
    $selectors["/user\/([a-z0-9-]{36})\/teleport\/?$/"]             = array("AUTH" => TRUE, "PUT"  => "teleportUserByUuid");      // Teleports a user
    $selectors["/user\/([a-z0-9-]{36})\/uuid\/?$/"]                 = array("AUTH" => TRUE, "PUT"  => "updateUserUuid");          // Update the UUID of a user to match an avatar
    $selectors["/user\/avatar\/?$/"]                                = array("AUTH" => TRUE, "POST" => "createAvatar");            // Create an avatar
    $selectors["/region\/([a-z0-9-]{36})\/?$/"]                     = array("AUTH" => TRUE, "GET"  => "getRegionByUuid");         // Get information about the given region
    $selectors["/region\/([a-z0-9-]{36})\/image\/?$/"]              = array("AUTH" => TRUE, "GET"  => "getRegionImageByUuid");    // Get the map of the region
    // Public selectors

    $selectors["/auth\/user\/?$/"]                                  = array("AUTH" => FALSE, "POST" => "authUser");                // Authenticate the given user


    $ok = FALSE;
    // Search for match
    foreach ($selectors as $regex => $funcs) {
        // Method found for this URL?
        if (preg_match($regex, $get, $args)) {
            $ok = TRUE;
            $method = $_SERVER['REQUEST_METHOD'];
            // Has access to this method?
            if (isset($funcs[$method]) && ($authorized >= $funcs['AUTH'])) {
                $result = API::$funcs[$method]($args);
            } else {
                header("HTTP/1.1 401 Unauthorized");
                throw new Exception("Unauthorized to access this API URL");
            }
         }
    }

    // Bad request
    if(!$ok) {
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