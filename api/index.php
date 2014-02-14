<?php
error_reporting(E_ALL);

require_once dirname(__FILE__) .'/../config.php';

$result = '';
try {
	// Input
	$get    = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
    $put    = $_SERVER['REQUEST_METHOD'] === "PUT" ? TRUE : FALSE;
    $post   = $_SERVER['REQUEST_METHOD'] === "POST" ? TRUE : FALSE;

	if($get !== FALSE && $get != '') {
		$parameters = explode('/', trim($get, '/'));
		switch ($parameters[0]) {
// Presentation handlers **************************************************************************
			case 'presentation':
				require_once dirname(__FILE__) .'/models/presentation.php';
                require_once dirname(__FILE__) .'/models/slide.php';

				if(Presentation::validateParameters($parameters)) {
// Presentation JSON ------------------------------------------------------------------------------
                    if(count($parameters) == 2) {
                        $presentation = new Presentation($parameters[1]);

                        $data = array();
                        $data['type']               = 'presentation';
                        $data['title']              = $presentation->getTitle();
                        $data['presentationId']     = $presentation->getPresentationId();
                        $data['ownerUuid']          = $presentation->getOwnerUuid();
                        $slides     = array();
                        $openSim    = array();
                        $x          = 1;
                        foreach($presentation->getSlides() as $slide) {
                            $slides[$x] = array(
                                            'number'        => $slide->getNumber(),
                                            'image'         => $presentation->getApiUrl() .'slide/'.  $slide->getNumber() .'/image/',
                                            'uuid'          => $slide->getUuid(),
                                            'uuidUpdated'   => $slide->getUuidUpdated(),
                                            'uuidExpired'   => $slide->isUuidExpired()
                                    );
                            $x++;
                        }

                        $data['slides']             = $slides;
                        $data['slidesCount']        = $presentation->getNumberOfSlides();
                        $data['creationDate']       = $presentation->getCreationDate();
                        $data['modificationDate']   = $presentation->getModificationDate();
                        $result = $data;
// Slide details ----------------------------------------------------------------------------------
                    } elseif(count($parameters) == 4) {
                        // Run post or get requests
                        $postUuid = filter_input(INPUT_POST, 'uuid', FILTER_SANITIZE_SPECIAL_CHARS);

                        // Get presentation and slide details
                        $presentation   = new Presentation($parameters[1]);
                        $slide          = $presentation->getSlide($parameters[3]);

                        // Update UUID of image
                        if($postUuid !== FALSE && $postUuid !== NULL) {
                            require_once dirname(__FILE__) .'/controllers/slideController.php';
                            $slide      = $presentation->getSlide($parameters[3]);
                            $slideCtrl  = new SlideController($slide);
                            $data       = $slideCtrl->setUuid($postUuid);
                            $result     = $data;
                        } else {
                            $data           = array(
                                                'number'        => $slide->getNumber(),
                                                'image'         => $presentation->getApiUrl() .'slide/'.  $slide->getNumber() .'/image/',
                                                'uuid'          => $slide->getUuid(),
                                                'uuidUpdated'   => $slide->getUuidUpdated(),
                                                'uuidExpired'   => $slide->isUuidExpired()
                                            );
                            $result = $data;
                        }
// Slide image ------------------------------------------------------------------------------------
                    } elseif(count($parameters) == 5 && $parameters[4] == 'image') {
                        // Get presentation and slide details
                        $presentation   = new Presentation($parameters[1], $parameters[3]);
                        $slidePath      = $presentation->getPath() . DS . $presentation->getCurrentSlide() .'.jpg';

                        // Show image if exists
                        if(file_exists($slidePath)) {
                            require_once dirname(__FILE__) .'/../includes/class.Images.php';
                            $resize = new Image($slidePath);
                            // resize when needed
                            if($resize->getWidth() > IMAGE_WIDTH || $resize->getHeight() > IMAGE_HEIGHT) {
                                $resize->resize(1024,1024,'fit');
                                $resize->save($presentation->getSlideId(), FILES_LOCATION . DS . PRESENTATIONS . DS . $presentation->getPresentationId(), 'jpg');
                            }
                            unset($resize);

                            // Fill remaining of image with black
                            $image = new Image(FILES_LOCATION . DS . PRESENTATIONS . DS .'background.jpg');
                            $image->addWatermark($slidePath);
                            $image->writeWatermark(100, 0, 0, 'c', 'c');
                            $image->resize(1024,1024,'fit');
                            $image->display();
                        } else {
                            throw new Exception("Requested slide does not exists", 5);
                        }
                    }
				}
			break;
// User data handlers *****************************************************************************
            case "user":
                require_once dirname(__FILE__) .'/models/user.php';

                // Create a user
                if($put) {
                    require_once dirname(__FILE__) .'/controllers/userController.php';
                    $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
                    $parsedUserData = (Helper::parsePutRequest($putUserData));
                    // Check if the parameters are valid for creating a user
                    if(UserController::validateParametersCreate($parsedUserData)) {
                        $userCtrl       = new UserController();
                        // Do request and fetch result
                        $data           = $userCtrl->createUser($parsedUserData['firstName'], $parsedUserData['lastName'], $parsedUserData['email'], $parsedUserData['password'], $parsedUserData['startRegionX'], $parsedUserData['startRegionY']);
                        // Do something with data?

                        // Set result
                        $result = $data;
                    }
                // Get data of specific user
                } elseif(User::validateParameters($parameters)) {
                    // Run post or get requests
                    $postUserName   = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_SPECIAL_CHARS);

                    // Set UUID of posted username
                    if($postUserName !== FALSE && $postUserName !== NULL) {
                        require_once dirname(__FILE__) .'/controllers/userController.php';
                        $userCtrl   = new UserController();
                        $data       = $userCtrl->setUuid($postUserName, $parameters[1]);
                        echo stripslashes(json_encode($data));
                    // Load user information
                    } else {
                        $user = new User($parameters[1]);

                        $data = array();
                        $data['uuid']               = $user->getUuid();
                        $data['userName']           = $user->getUserName();
                        $data['firstName']          = $user->getFirstName();
                        $data['lastName']           = $user->getLastName();
                        $data['email']              = $user->getEmail();
                        $data['presentationIds']    = $user->getPresentationIds();
                        $result = $data;
                    }
                }
            break;
			default:
				// Other scenario
			break;
		}
	}
// Catch any exception that occured
} catch (Exception $e) {
    header("HTTP/1.0 400 Bad Request");
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