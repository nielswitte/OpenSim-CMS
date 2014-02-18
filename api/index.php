<?php
error_reporting(E_ALL);

require_once dirname(__FILE__) .'/../config.php';
require_once dirname(__FILE__) .'/api.php';

$result = '';
// Try to parse the requested URL and paramters to a function of the API
try {
	// Input
	$get    = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);

    // List with URL selectors and the corresponding functions to be used for each request type
    $selectors = array(
        "/presentation\/(\d+)\/slide\/(\d+)\/image\/?$/"     => array("GET"  => "getSlideImageById"),
        "/presentation\/(\d+)\/slide\/(\d+)\/?$/"            => array("GET"  => "getSlideById",
                                                                     "POST" => "updateSlideUuid"),
        "/presentation\/(\d+)\/?$/"                          => array("GET"  => "getPresentationById"),
        "/user\/([a-z0-9-]{36})\/?$/"                        => array("GET"  => "getUserByUuid"),
        "/user\/([a-z0-9-]{36})\/teleport\/?$/"              => array("POST" => "teleportUserByUuid"),
        "/user\/([a-z0-9-]{36})\/uuid\/?$/"                  => array("POST" => "updateUserUuid"),
        "/user\/avatar\/?$/"                                 => array("PUT"  => "createAvatar"),
        "/region\/([a-z0-9-]{36})\/?$/"                      => array("GET"  => "getRegionByUuid"),
        "/region\/([a-z0-9-]{36})\/image\/?$/"               => array("GET"  => "getRegionImageByUuid"),
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

// Log headers for debug purpose
/*
$json = json_encode($headers);
$phpStringArray = str_replace(array("{","}",":"), array("array(","}","=>"), $json);
file_put_contents('headers.txt', $phpStringArray ."\n\r", FILE_APPEND);
 */