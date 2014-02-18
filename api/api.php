<?php
if(EXEC != 1) {
	die('Invalid request');
}

// Include all model classes
require_once dirname(__FILE__) .'/../models/presentation.php';
require_once dirname(__FILE__) .'/../models/region.php';
require_once dirname(__FILE__) .'/../controllers/regionController.php';
require_once dirname(__FILE__) .'/../models/slide.php';
require_once dirname(__FILE__) .'/../controllers/slideController.php';
require_once dirname(__FILE__) .'/../models/user.php';
require_once dirname(__FILE__) .'/../controllers/userController.php';

/**
 * This class is hosts all API calls and matches them to the corresponding model/controller functions
 *
 * @author Niels Witte
 * @version 0.2
 * @date February 18th, 2014
 */
class API {
    /**
     * Gets a list of presentations starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public static function getPresentations($args) {
        $db             = Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array($args[1], 50);
        $resutls        = $db->rawQuery("SELECT * FROM presentations ORDER BY creationDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        $x              = 1;
        foreach($resutls as $result) {
            $presentation = new Presentation($result['id'], 0, $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate']);
            $data[$x]     = self::getPresentationData($presentation);
            $x++;
        }
        return $data;
    }

    /**
     * Get presentation details for the given presentation
     *
     * @param array $args
     * @return array
     */
    public static function getPresentationById($args) {
        $presentation = new Presentation($args[1]);
        $presentation->getInfoFromDatabase();
        return self::getPresentationData($presentation);
    }

    /**
     * Format the presentation data to the desired format
     *
     * @param Presentation $presentation
     * @return array
     */
    private static function getPresentationData(Presentation $presentation) {
        $data = array();
        $data['type']               = 'presentation';
        $data['title']              = $presentation->getTitle();
        $data['presentationId']     = $presentation->getPresentationId();
        $data['ownerId']            = $presentation->getOwnerId();
        $slides     = array();
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

        return $data;
    }

    /**
     * Get slide details for the given slide
     *
     * @param array $args
     * @return array
     */
    public static function getSlideById($args) {
        $presentation   = new Presentation($args[1]);
        $slide          = $presentation->getSlide($args[2]);

        $data           = array(
                                'number'        => $slide->getNumber(),
                                'image'         => $presentation->getApiUrl() .'slide/'.  $slide->getNumber() .'/image/',
                                'uuid'          => $slide->getUuid(),
                                'uuidUpdated'   => $slide->getUuidUpdated(),
                                'uuidExpired'   => $slide->isUuidExpired()
                            );
        return $data;
    }

    /**
     * Get slide image for the given slide
     *
     * @param array $args
     * @throws Exception
     */
    public static function getSlideImageById($args) {
        // Get presentation and slide details
        $presentation   = new Presentation($args[1], $args[2]);
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

    /**
     * Updates the slide with the given UUID
     *
     * @param array $args
     * @return boolean
     */
    public static function updateSlideUuid($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (Helper::parsePutRequest($putUserData));
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get presentation and slide details
        $presentation   = new Presentation($args[1]);
        $slide          = $presentation->getSlide($args[2]);
        // Update
        $slideCtrl      = new SlideController($slide);
        $data           = $slideCtrl->setUuid($postUuid);

        return $data;
    }

    /**
     * Searches the database for the given (partial) username and returns a list with users
     *
     * @param array $args
     * @return array
     */
    public static function getUsersByUserName($args) {
        $db             = Helper::getDB();
        $params         = array("%". $args[1] ."%");
        $results        = $db->rawQuery('SELECT * FROM users WHERE userName LIKE ? ORDER BY userName ASC', $params);
        $data           = array();
        $count          = 0;
        foreach($results as $result) {
            $count++;
            $user           = new User($result['id'], $result['uuid']);
            $user->getInfoFromDatabase();
            $data[$count]   = self::getUserData($user);
        }
        return $data;
    }

    /**
     * Get the details of an user by its ID
     *
     * @param array $args
     * @return array
     */
    public static function getUserById($args) {
        $user = new User($args[1]);
        $user->getInfoFromDatabase();
        return self::getUserData($user);
    }

    /**
     * Get the details of an user by its UUID
     *
     * @param array $args
     * @return array
     */
    public static function getUserByUuid($args) {
        $user = new User(0, $args[1]);
        $user->getInfoFromDatabase();
        return self::getUserData($user);
    }

    /**
     * Formats the user data to a nice array
     *
     * @param User $user
     * @return array
     */
    private static function getUserData(User $user) {
        $data = array();
        $data['id']                 = $user->getId();
        $data['uuid']               = $user->getUuid();
        $data['userName']           = $user->getUserName();
        $data['firstName']          = $user->getFirstName();
        $data['lastName']           = $user->getLastName();
        $data['email']              = $user->getEmail();
        $data['presentationIds']    = $user->getPresentationIds();

        // Extra information
        if(OS_DB_ENABLED) {
            $data['online']             = $user->getOnline();
            $data['lastLogin']          = $user->getLastLogin();
            $data['lastPosition']       = $user->getLastPosition();
            $data['lastRegionUuid']     = $user->getLastRegionUuid();
        }
        return $data;
    }

    /**
     * Updates the UUID of the given user
     *
     * @param array $args
     * @return boolean
     */
    public static function updateUserUuid($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (Helper::parsePutRequest($putUserData));
        $userName       = isset($parsedPutData['userName']) ? $parsedPutData['userName'] : '';

        $userCtrl       = new UserController();
        $data           = $userCtrl->setUuid($userName, $args[1]);
        return $data;
    }

    /**
     * Teleport the user to given location
     *
     * @param array $args
     * @return array
     */
    public static function teleportUserByUuid($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (Helper::parsePutRequest($putUserData));

        // use UUID from GET request
        $parsedPutData['agentUuid']  = $args[1];
        $result                 = '';
        // Teleport a user
        if(UserController::validateParametersTeleport($parsedPutData)) {
            $userCtrl           = new UserController();
            // Do request and fetch result
            $data               = $userCtrl->teleportUser($parsedPutData);
            // Set result
            $result = $data;
        }
        return $result;
    }

    /**
     * Create an avatar with the given PUT information
     *
     * @param array $args
     * @return array
     */
    public static function createAvatar($args) {
        $result         = '';
        $userData       = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

        // Check if the parameters are valid for creating a user
        if(UserController::validateParametersCreate($userData)) {
            $userCtrl       = new UserController();
            // Do request and fetch result
            $data = $userCtrl->createAvatar($userData);

            // Set result
            $result = $data;
        }
        return $result;
    }

    /**
     * Gets information about the region
     *
     * @param array $args
     * @return array
     */
    public static function getRegionByUuid($args) {
        $region     = new Region($args[1]);

        $region->getInfoFromDatabase();
        $data['uuid']           = $region->getUuid();
        $data['name']           = $region->getName();
        $data['image']          = $region->getApiUrl() .'image/';
        $data['serverStatus']   = $region->getOnlineStatus() ? 1 : 0;

        // Additional information
        if(OS_DB_ENABLED) {
            $data['totalUsers']     = $region->getTotalUsers();
            $data['activeUsers']    = $region->getActiveUsers();
        }
        return $data;
    }

    /**
     * Shows the region image map as JPEG
     *
     * @param array $args
     */
    public static function getRegionImageByUuid($args) {
        header('Content-Type: image/jpeg');
        echo file_get_contents(OS_SERVER_URL .'/index.php?method=regionImage'. str_replace('-', '', $args[1]));
    }
}
