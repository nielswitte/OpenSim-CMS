<?php
if(EXEC != 1) {
	die('Invalid request');
}

// Include all model classes
require_once dirname(__FILE__) .'/../models/avatar.php';
require_once dirname(__FILE__) .'/../models/grid.php';
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
 * @version 0.4
 * @date February 18th, 2014
 */
class API {
    private $routes = array();

    /**
     * Creates a new API with optional a list of routes
     *
     * @param array $routes
     */
    public function __construct($routes = array()) {
        $this->routes = $routes;
    }

    /**
     * Adds the given regex/function pair to the list of routes
     *
     * @param string $regex - Regular expression to match the route
     * @param string $function - Name of the function to execute
     * @param string $method - [Optional] Define if the function is accessed by GET, POST, PUT or DELETE (Default: GET)
     * @param boolean $auth - [Optional] Is authorization required for this function? (Default: FALSE)
     */
    public function addRoute($regex, $function, $method = 'GET', $auth = FALSE) {
        $this->routes[$regex][$method]['AUTH']      = $auth;
        $this->routes[$regex][$method]['FUNCTION']  = $function;
    }

   /**
     * Checks if the given url can be matched to a function
     *
     * @param string $url - URL to check
     * @param boolean $authorized - [Optional] Is the user authorized
     * @return mixed - The result of the function if a match is found, FALSE when no match found
     * @throws Exception
     */
    public function getRoute($url, $authorized = FALSE) {
        $result = FALSE;
        // Search for match
        foreach ($this->routes as $regex => $funcs) {
            // Method found for this URL?
            if (preg_match($regex, $url, $args)) {
                $method = $_SERVER['REQUEST_METHOD'];
                // Has access to this method?
                if (isset($funcs[$method]) && ($authorized >= $funcs[$method]['AUTH'])) {
                    $result = API::$funcs[$method]['FUNCTION']($args);
                } else {
                    $result = TRUE;
                    header("HTTP/1.1 401 Unauthorized");
                    throw new Exception("Unauthorized to access this API URL");
                }
            }
        }
        return $result;
    }

    /**
     * Returns an array with all routes
     *
     * @return array
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Authenticates the user based on the given post data
     *
     * @throws Exception
     * @returns array
     */
    public function authUser($args) {
        $headers                = getallheaders();
        $db                     = Helper::getDB();
        $userName               = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_ENCODED);
        $password               = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_ENCODED);
        $ip                     = filter_input(INPUT_POST, 'ip', FILTER_SANITIZE_ENCODED);

        // Basic output data
        $data['token']          = Helper::generateToken(48);
        $data['ip']             = (($ip !== FALSE && $ip !== NULL) ? $ip : $_SERVER['REMOTE_ADDR']);
        $data['expires']        = date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES));

        // Check server IP to grid list
        $db->where('osIp', $db->escape($data['ip']));
        $grids = $db->get('grids', 1);

        // Request from OpenSim? Add this additional check because of the access rights of OpenSim
        if(isset($headers['HTTP_X_SECONDLIFE_SHARD']) && isset($grids[0])) {
            $userId             = -1;
        } elseif($userName != "OpenSim") {
            $userId             = 0;
        } else {
            throw new Exception("Not allowed to login as OpenSim outside the Grid", 2);
        }
        $user           = new User($userId, $userName);
        $user->getInfoFromDatabase();
        $userCtrl       = new UserController($user);
        $validRequest   = $userCtrl->checkPassword($password);
        $data['userId'] = $user->getId();
        if(!$validRequest) {
            throw new Exception("Invalid username/password combination used", 1);
        }

        if($validRequest) {
            $db->insert('tokens', $data);
        }

        return $data;
    }

    /**
     * Gets a list of presentations starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public function getPresentations($args) {
        $db             = Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array('presentation', $args[1], 50);
        $resutls        = $db->rawQuery("SELECT * FROM documents WHERE type = ? ORDER BY creationDate DESC LIMIT ?, ?", $params);
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
    public function getPresentationById($args) {
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
    private function getPresentationData(Presentation $presentation) {
        $data = array();
        $data['type']               = 'presentation';
        $data['title']              = $presentation->getTitle();
        $data['presentationId']     = $presentation->getPresentationId();
        $data['ownerId']            = $presentation->getOwnerId();
        $slides     = array();
        $x          = 1;
        foreach($presentation->getSlides() as $slide) {
            $slides[$x] = $this->getSlideData($presentation, $slide);
            $x++;
        }

        $data['slides']             = $slides;
        $data['slidesCount']        = $presentation->getNumberOfSlides();
        $data['creationDate']       = $presentation->getCreationDate();
        $data['modificationDate']   = $presentation->getModificationDate();

        return $data;
    }

    /**
     * Formats the data for the given slide
     *
     * @param Presentation $presentation
     * @param Slide $slide
     * @return array
     */
    private function getSlideData(Presentation $presentation, Slide $slide) {
        $cachedTextures = array();
        foreach($slide->getCache() as $cache) {
            $cachedTextures[$cache['gridId']] = array(
                'uuid'      => $cache['uuid'],
                'expires'   => $cache['uuidExpires'],
                'isExpired' => $cache['uuidExpires'] > time() ? 1 : 0
            );
        }
        $data = array(
            'number' => $slide->getNumber(),
            'image' => $presentation->getApiUrl() . 'slide/' . $slide->getNumber() . '/image/',
            'cache' => $cachedTextures
        );
        return $data;
    }

    /**
     * Get slide details for the given slide
     *
     * @param array $args
     * @return array
     */
    public function getSlideById($args) {
        $presentation   = new Presentation($args[1]);
        $slide          = $presentation->getSlide($args[2]);
        $data           = $this->getSlideData($presentation, $slide);
        return $data;
    }

    /**
     * Get slide image for the given slide
     *
     * @param array $args
     * @throws Exception
     */
    public function getSlideImageById($args) {
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
    public function updateSlideUuid($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (Helper::parsePutRequest($putUserData));
        $gridId         = isset($parsedPutData['gridId']) ? $parsedPutData['gridId'] : '';
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get presentation and slide details
        $presentation   = new Presentation($args[1]);
        $slide          = $presentation->getSlide($args[2]);

        // Get grid details
        $grid           = new Grid($gridId);
        $grid->getInfoFromDatabase();

        // Update
        $slideCtrl      = new SlideController($slide);
        $data           = $slideCtrl->setUuid($postUuid, $grid);

        return $data;
    }

    /**
     * Searches the database for the given (partial) username and returns a list with users
     *
     * @param array $args
     * @return array
     */
    public function getUsersByUserName($args) {
        $db             = Helper::getDB();
        $params         = array("%". $args[1] ."%");
        $results        = $db->rawQuery('SELECT * FROM users WHERE userName LIKE ? ORDER BY userName ASC', $params);
        $data           = array();
        $count          = 0;
        foreach($results as $result) {
            $count++;
            $user           = new User($result['id']);
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
    public function getUserById($args) {
        $user = new User($args[1]);
        $user->getInfoFromDatabase();
        return self::getUserData($user);
    }

    /**
     * Gets an User by its Avatar on the Grid
     * Needs args[1] to be the Grid ID and args[2] the avatar UUID
     *
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function getUserByAvatar($args) {
        $data = array();
        if(Helper::isValidUuid($args[2])) {
            $db = Helper::getDB();
            $db->where('uuid', $args[2]);
            $db->where('gridId', $args[1]);
            $avatarQuery = $db->get('avatars', 1);
            if(isset($avatarQuery[0])) {
                $user = new User($avatarQuery[0]['userId']);
                $user->getInfoFromDatabase();
                $data = $this->getUserData($user);
            } else {
                throw new Exception("Avatar not found on this Grid", 2);
            }
        } else {
            throw new Exception("Invalid UUID provided", 1);
        }
        return $data;
    }

    /**
     * Formats the user data to a nice array
     *
     * @param User $user
     * @return array
     */
    private function getUserData(User $user) {
        $data['id']                 = $user->getId();
        $data['userName']           = $user->getUserName();
        $data['firstName']          = $user->getFirstName();
        $data['lastName']           = $user->getLastName();
        $data['email']              = $user->getEmail();
        $data['presentationIds']    = $user->getPresentationIds();
        $avatars                    = array();
        $x = 1;
        foreach($user->getAvatars() as $avatar) {
            $avatars[$x] = array(
                'uuid'          => $avatar->getUuid(),
                'gridId'        => $avatar->getGrid()->getId(),
                'gridName'      => $avatar->getGrid()->getName(),
                'online'        => $avatar->getOnline(),
                'lastRegion'    => $avatar->getLastRegionUuid(),
                'lastLogin'     => $avatar->getLastLogin(),
                'lastPosition'  => $avatar->getLastPosition()
            );
            $x++;
        }
        $data['avatars']            = $avatars;
        return $data;
    }

    /**
     * Updates the UUID of the given user
     *
     * @param array $args
     * @return boolean
     */
    public function matchAvatarToUser($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (Helper::parsePutRequest($putUserData));
        $userName       = isset($parsedPutData['userName']) ? $parsedPutData['userName'] : '';

        $userCtrl       = new UserController();
        $data           = $userCtrl->setUuid($userName, $args[1], $args[2]);
        return $data;
    }

    /**
     * Teleport the user to given location
     *
     * @param array $args
     * @return array
     */
    public function teleportAvatarByUuid($args) {
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
    public function createAvatar($args) {
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

    public function getGrids($args) {
        $db = Helper::getDB();
        $db->orderBy('name', 'asc');
        $grids  = $db->get('grids');
        // Process al grids
        $data   = array();
        foreach($grids as $gridId) {
            $grid = new Grid($gridId['id']);
            $grid->getInfoFromDatabase();
            $data[] = $this->getGridData($grid);
        }
        return $data;
    }

    /**
     * Gets information about a grid by its ID
     *
     * @param array $args
     * @return array
     */
    public function getGridById($args) {
        $grid       = new Grid($args[1]);
        $grid->getInfoFromDatabase();

        return $this->getGridData($grid);
    }

    /**
     * Formats the grid data
     *
     * @param Grid $grid
     * @return array
     */
    private function getGridData(Grid $grid) {
        $data['isOnline']           = $grid->getOnlineStatus() ? 1 : 0;
        $data['id']                 = $grid->getId();
        $data['name']               = $grid->getName();

        // Get information about the number of users
        if($grid->getOnlineStatus() !== FALSE) {
            $data['totalUsers']     = $grid->getTotalUsers();
            $data['activeUsers']    = $grid->getActiveUsers();
        }
        // OpenSim info
        $data['openSim'] = array(
            'protocol'              => $grid->getOsProtocol(),
            'ip'                    => $grid->getOsIp(),
            'port'                  => $grid->getOSPort()
        );
        // Remote Admin info
        $data['remoteAdmin'] = array(
            'url'                   => $grid->getRaUrl(),
            'port'                  => $grid->getRaPort()
        );
        // Regions
        $data['cacheTime']          = $grid->getCacheTime();
        $data['defaultRegionUuid']  = $grid->getDefaultRegionUuid();
        $data['regionCount']        = count($grid->getRegions());
        foreach($grid->getRegions() as $region) {
            $data['regions'][$region->getUuid()] = $this->getRegionData($region);
        }

        return $data;
    }

    /**
     * Formats the region data
     *
     * @param Region $region
     * @return array
     */
    private function getRegionData(Region $region) {
        $data['uuid']           = $region->getUuid();
        $data['name']           = $region->getName();
        $data['image']          = $region->getApiUrl() .'image/';
        $data['serverStatus']   = $region->getOnlineStatus() ? 1 : 0;

        // Additional information
        if($region->getOnlineStatus() !== FALSE && $region->getTotalUsers() > 0) {
            $data['totalUsers']     = $region->getTotalUsers();
            $data['activeUsers']    = $region->getActiveUsers();
        }

        return $data;
    }

    /**
     * Gets information about the region
     *
     * @param array $args
     * @return array
     */
    public function getRegionByUuid($args) {
        $grid       = new Grid($args[1]);
        $grid->getInfoFromDatabase();
        $region     = $grid->getRegionByUuid($args[2]);
        $data       = '';
        if($region !== FALSE) {
            $data = $this->getRegionData($region);
        } else {
            throw new Exception("Region not found", 1);
        }

        return $data;
    }

    /**
     * Shows the region image map as JPEG
     *
     * @param array $args
     */
    public function getRegionImageByUuid($args) {
        if(!Helper::isValidUuid($args[2])) {
            throw new Exception("Invalid UUID used", 1);
        } else {
            $grid       = new Grid($args[1]);
            $grid->getInfoFromDatabase();
            if($grid->getRegionByUuid($args[2]) !== FALSE) {
                header('Content-Type: image/jpeg');
                echo file_get_contents($grid->getOsProtocol() .'://'. $grid->getOsIp() .':'. $grid->getOSPort() .'/index.php?method=regionImage'. str_replace('-', '', $args[2]));
            } else {
                throw new Exception("UUID isn't a region on this server", 2);
            }
        }
    }
}
