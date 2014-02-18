<?php
error_reporting(E_ALL);

require_once dirname(__FILE__) .'/../config.php';
require_once dirname(__FILE__) .'/api.php';

/**
 * This class is catches the API calls and searches for the matching function
 *
 * @author Niels Witte
 * @version 0.3
 * @date February 10th, 2014
 */

$result = '';
// Try to parse the requested URL and paramters to a function of the API
try {
	// Input
	$get    = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);

    // List with URL selectors and the corresponding functions to be used for each request type
    $selectors = array(
        "/presentations\/?$/"                                => array("GET"  => "getPresentations"),        // Get list with presentations
        "/presentations\/(\d+)\/?$/"                         => array("GET"  => "getPresentations"),        // Get list with presentations with an offset
        "/presentation\/(\d+)\/?$/"                          => array("GET"  => "getPresentationById"),     // Select specific presentation
        "/presentation\/(\d+)\/slide\/(\d+)\/?$/"            => array("GET"  => "getSlideById",             // Get slide from presentation
                                                                      "PUT"  => "updateSlideUuid"),         // Update slide UUID for given slide of presentation
        "/presentation\/(\d+)\/slide\/(\d+)\/image\/?$/"     => array("GET"  => "getSlideImageById"),       // Get only the image of a given presentation slide
        "/user\/([a-z0-9-]{36})\/?$/"                        => array("GET"  => "getUserByUuid"),           // Get a user by UUID
        "/user\/([a-z0-9-]{36})\/teleport\/?$/"              => array("PUT"  => "teleportUserByUuid"),      // Teleports a user
        "/user\/([a-z0-9-]{36})\/uuid\/?$/"                  => array("PUT"  => "updateUserUuid"),          // Update the UUID of a user to match an avatar
        "/user\/avatar\/?$/"                                 => array("POST" => "createAvatar"),            // Create an avatar
        "/region\/([a-z0-9-]{36})\/?$/"                      => array("GET"  => "getRegionByUuid"),         // Get information about the given region
        "/region\/([a-z0-9-]{36})\/image\/?$/"               => array("GET"  => "getRegionImageByUuid"),    // Get the map of the region
    );

    $ok = FALSE;
    // Search for match
    foreach ($selectors as $regex => $funcs) {
        if (preg_match($regex, $get, $args)) {
            $method = $_SERVER['REQUEST_METHOD'];
            if (isset($funcs[$method])) {
                $result = API::$funcs[$method]($args);
                $ok = TRUE;
            }
         }
    }

    // No matching function found?
    if(!$ok) {
        throw new Exception("Invalid API URL used", 1);
    }

// Catch any exception that occured
} catch (Exception $e) {
    header("HTTP/1.1 400 Bad Request");
    echo '<pre>';
	echo $e;
    echo '</pre>';
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