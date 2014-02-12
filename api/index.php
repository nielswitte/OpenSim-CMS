<?php
error_reporting(E_ALL);

require_once dirname(__FILE__) .'/../config.php';

$result = '';
try {
	// Input
	$get    = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);

	if($get !== FALSE && $get != '') {
		$parameters = explode('/', trim($get, '/'));
		switch ($parameters[0]) {
// Presentation handlers **************************************************************************
			case 'presentation':
				require_once dirname(__FILE__) .'/libs/presentation.php';
                require_once dirname(__FILE__) .'/libs/slide.php';

				if(Presentation::validateParameters($parameters)) {
// Presentation JSON ------------------------------------------------------------------------------
                    if(count($parameters) == 2) {
                        $presentation = new Presentation($parameters[1], $parameters[3]);

                        $data = array();
                        $data['type']               = 'presentation';
                        $data['title']              = $presentation->getTitle();
                        $data['presentationId']     = $presentation->getPresentationId();
                        $data['ownerUuid']          = $presentation->getOwnerUuid();
                        $slides     = array();
                        $openSim    = array();
                        foreach($presentation->getSlides() as $slide) {
                            $slides[] = array(
                                            'number'        => (string) $slide->getNumber(),
                                            'uuid'          => $slide->getUuid(),
                                            'uuidUpdated'   => $slide->getUuidUpdated(),
                                            'uuidExpired'   => (string) $slide->isUuidExpired(),
                                            'url'           => $presentation->getApiUrl() .'slide/'.  $slide->getNumber() .'/'
                                    );
                            if(Helper::isValidUuid($slide->getUuid()) && !$slide->isUuidExpired()) {
                                $openSim[] = $slide->getUuid();
                            } else {
                                $openSim[] = $presentation->getApiUrl() .'slide/'.  $slide->getNumber() .'/';
                            }
                        }

                        $data['slides']             = $slides;
                        $data['openSim']            = $openSim;
                        $data['slidesCount']        = (string) $presentation->getNumberOfSlides();
                        $data['creationDate']       = $presentation->getCreationDate();
                        $data['modificationDate']   = $presentation->getModificationDate();
                        echo stripslashes(json_encode($data));
// Slide image ------------------------------------------------------------------------------------
                    } else {
                        $presentation   = new Presentation($parameters[1], $parameters[3]);
                        $slidePath      = $presentation->getPath() . DS . $presentation->getCurrentSlide() .'.jpg';
                        if(file_exists($slidePath)) {

                            // Run post or get requests
                            $post = filter_input(INPUT_POST, 'uuid', FILTER_SANITIZE_SPECIAL_CHARS);

                            // Update UUID of image
                            if($post !== FALSE && $post !== NULL) {
                                $slide = $presentation->getSlide($parameters[3]);
                                $data = $slide->setUuid($post);
                                echo stripslashes(json_encode($data));
                            // Load image
                            } else {
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
                            }
                        } else {
                            throw new Exception("Requested slide does not exists", 5);
                        }
                    }
				}
			break;
// User data handlers *****************************************************************************
            case "user":
                require_once dirname(__FILE__) .'/libs/user.php';

                // Get user data
                if(User::validateParameters($parameters)) {
                    $user = new User($parameters[1]);

                    $data = array();
                    $data['username']           = $user->getUserName();
                    $data['uuid']               = $user->getUuid();
                    $data['presentationIds']    = $user->getPresentationIds();
                    echo stripslashes(json_encode($data));
                }
            break;
			default:
				// Other scenario
			break;
		}
	}
} catch (Exception $e) {
	echo $e;
}

/*
$headers = getallheaders();
$json = json_encode($headers);
$phpStringArray = str_replace(array("{","}",":"), array("array(","}","=>"), $json);
file_put_contents('headers.txt', $phpStringArray ."\n\r", FILE_APPEND);
*/