<?php
namespace API;

require_once dirname(__FILE__) .'/config.php';
require_once dirname(__FILE__) .'/includes/class.Auth.php';
require_once dirname(__FILE__) .'/api.php';
require_once dirname(__FILE__) .'/modules/auth.php';
require_once dirname(__FILE__) .'/modules/chat.php';
require_once dirname(__FILE__) .'/modules/comment.php';
require_once dirname(__FILE__) .'/modules/document.php';
require_once dirname(__FILE__) .'/modules/file.php';
require_once dirname(__FILE__) .'/modules/grid.php';
require_once dirname(__FILE__) .'/modules/meeting.php';
require_once dirname(__FILE__) .'/modules/meetingroom.php';
require_once dirname(__FILE__) .'/modules/presentation.php';
require_once dirname(__FILE__) .'/modules/user.php';

/**
 * This class is catches the API calls and searches for the matching function
 *
 * @author Niels Witte
 * @version 0.8
 * @date May 8, 2014
 * @since February 10, 2014
 */

// Remove expired tokens
\Auth::removeExpiredTokens();

// Try to parse the requested URL and paramters to a function of the API
try {
    // Validate input before doing anything else
    $inputValidation = \Helper::checkInput();

	// Input
	$get                = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
    $token              = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
    $selectors          = array();

    // Auth user
    \Auth::setToken($token);
    $authorized         = \Auth::validate();

    // Create new API
    $api                = new \API\API();

    // Add modules
    $chatApi            = new \API\Modules\Chat($api);
    $commentsApi        = new \API\Modules\Comment($api);
    $documentApi        = new \API\Modules\Document($api);
    $fileApi            = new \API\Modules\File($api);
    $gridApi            = new \API\Modules\Grid($api);
    $meetingsApi        = new \API\Modules\Meeting($api);
    $presentationApi    = new \API\Modules\Presentation($api);
    $roomApi            = new \API\Modules\MeetingRoom($api);
    $userApi            = new \API\Modules\User($api);
    $authApi            = new \API\Modules\Auth($api);

    // Match the route to a function
    $result             = $api->getRoute($get);

    // Wrong request?
    if($result === FALSE) {
        header("HTTP/1.1 400 Bad Request");
        throw new \Exception("Invalid API URL used", 1);
    }
// Catch any exception that occured
} catch (\Exception $e) {
    $result["success"]      = FALSE;
	$result["error"]        = $e->getMessage();

    // Do we want to show debug information?
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
    // Content is JSON
    header('Content-Type: application/json');

    // Output to human readable or compact fast parsable code?
    if(isset($headers['User-Agent'])) {
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo json_encode($result);
    }
}

// Log requests to a file
if(SERVER_DEBUG) {
    file_put_contents('logs/access.log', '['. date('Y-m-d H:i:s') .'] '. getenv('REMOTE_ADDR') .' '. getenv('REQUEST_METHOD') .' /api'. $get ."\n\r", FILE_APPEND);
}