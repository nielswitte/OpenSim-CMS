<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for users
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 24th, 2014
 */
class User extends Module {
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/users\/([a-zA-Z0-9-_]{3,}+)\/?$/",                 "getUsersByUsername",     $this, "GET",  TRUE);  // Gets a list of all users with usernames matching the search of atleast 3 characters
        $this->api->addRoute("/user\/?$/",                                        "createUser",             $this, "POST", TRUE);  // Create a new CMS user
        $this->api->addRoute("/user\/(\d+)\/?$/",                                 "getUserById",            $this, "GET",  TRUE);  // Get a user by ID
        $this->api->addRoute("/user\/(\d+)\/password\/?$/",                       "updateUserPasswordById", $this, "PUT",  TRUE);  // Updates the user's password
        $this->api->addRoute("/user\/([a-z0-9-]{36})\/teleport\/?$/",             "teleportAvatarByUuid",   $this, "PUT",  TRUE);  // Teleports a user
        $this->api->addRoute("/user\/avatar\/?$/",                                "createAvatar",           $this, "POST", TRUE);  // Create an avatar
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "getUserByAvatar",        $this, "GET",  TRUE);  // Gets an user by the avatar of this grid
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "matchAvatarToUser",      $this, "PUT",  TRUE);  // Update the UUID of a user to match an avatar
    }

    /**
     * Creates a new user with the given POST parameters
     *
     * @param array $args
     * @return array
     */
    public function createUser($args) {
        $userData               = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        // Overwrite these values because they may contain special chars
        $userData['password']   = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        $userData['password2']  = filter_input(INPUT_POST, 'password2', FILTER_UNSAFE_RAW);

        $userId         = FALSE;
        $userCtrl       = new \Controllers\UserController();
        // Check if the parameters are valid for creating a user
        if($userCtrl->validateParametersCreate($userData)) {
            $userId     = $userCtrl->createUser($userData);
        }

        // Format the result
        $result = array(
            'success' => ($userId !== FALSE ? TRUE : FALSE),
            'userId' => ($userId !== FALSE ? $userId : 0)
        );

        return $result;
    }

    /**
     * Updates the password for the given user
     *
     * @param array $args
     * @return array
     */
    public function updateUserPasswordById($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (\Helper::parsePutRequest($putUserData));

        $user           = new \Models\User($args[1]);
        $userCtrl       = new \Controllers\UserController($user);
        $data           = FALSE;
        if($userCtrl->validateParameterPassword($parsedPutData)) {
            $data     = $userCtrl->setPassword($parsedPutData['password']);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Searches the database for the given (partial) username and returns a list with users
     *
     * @param array $args
     * @return array
     */
    public function getUsersByUsername($args) {
        $db             = \Helper::getDB();
        $params         = array("%". $db->escape($args[1]) ."%");
        $results        = $db->rawQuery('SELECT * FROM users WHERE username LIKE ? ORDER BY username ASC', $params);
        $data           = array();
        $count          = 0;
        foreach($results as $result) {
            $count++;
            $user           = new \Models\User($result['id']);
            $user->getInfoFromDatabase();
            $data[$count]   = self::getUserData($user, FALSE);
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
        $user = new \Models\User($args[1]);
        $user->getInfoFromDatabase();
        $user->getAvatarsFromDatabase();
        $user->getPresentationsFromDatabase();
        return self::getUserData($user, TRUE);
    }

    /**
     * Gets an User by its Avatar on the Grid
     * Needs args[1] to be the Grid ID and args[2] the avatar UUID
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getUserByAvatar($args) {
        $data = array();
        if(\Helper::isValidUuid($args[2])) {
            $db = \Helper::getDB();
            $db->where('uuid', $db->escape($args[2]));
            $db->where('gridId', $db->escape($args[1]));
            $avatarQuery = $db->get('avatars', 1);
            if(isset($avatarQuery[0])) {
                $user = new \Models\User($avatarQuery[0]['userId']);
                $user->getInfoFromDatabase();
                $user->getAvatarsFromDatabase();
                $user->getPresentationsFromDatabase();
                $data = $this->getUserData($user, TRUE);
            } else {
                throw new \Exception("Avatar not found on this Grid", 2);
            }
        } else {
            throw new \Exception("Invalid UUID provided", 1);
        }
        return $data;
    }

    /**
     * Formats the user data to a nice array
     *
     * @param \Models\User $user
     * @param boolean $full - [Optional] Return all user information?
     * @return array
     */
    private function getUserData(\Models\User $user, $full = TRUE) {
        $data['id']                 = $user->getId();
        $data['username']           = $user->getUsername();
        $data['firstName']          = $user->getFirstName();
        $data['lastName']           = $user->getLastName();
        $data['email']              = $user->getEmail();

        if($full) {
            $data['presentationIds']    = $user->getPresentationIds();
        }
        if($full) {
            $avatars                    = array();
            $x = 1;
            foreach($user->getAvatars() as $avatar) {
                $avatars[$x] = array(
                    'uuid'          => $avatar->getUuid(),
                    'gridId'        => $avatar->getGrid()->getId(),
                    'gridName'      => $avatar->getGrid()->getName(),
                    'confirmed'     => $avatar->getConfirmation(),
                    'online'        => $avatar->getOnline(),
                    'lastRegion'    => $avatar->getLastRegionUuid(),
                    'lastLogin'     => $avatar->getLastLogin(),
                    'lastPosition'  => $avatar->getLastPosition()
                );
                $x++;
            }
            $data['avatars']            = $avatars;
        }
        return $data;
    }

    /**
     * Updates the UUID of the given user
     *
     * @param array $args
     * @return array
     */
    public function matchAvatarToUser($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (\Helper::parsePutRequest($putUserData));
        $username       = isset($parsedPutData['username']) ? $parsedPutData['username'] : '';

        $userCtrl       = new \Controllers\UserController();
        $data           = $userCtrl->setUuid($username, $args[1], $args[2]);

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Teleport the avatar to given location
     *
     * @param array $args
     * @return array
     */
    public function teleportAvatarByUuid($args) {
        $putAvatarData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (\Helper::parsePutRequest($putAvatarData));

        // use UUID from GET request
        $parsedPutData['agentUuid']  = $args[1];
        $result                 = '';
        // Teleport an avatar
        if(\Controllers\AvatarController::validateParametersTeleport($parsedPutData)) {
            $avatarCtrl           = new \Controllers\AvatarController();
            // Do request and fetch result
            $data               = $avatarCtrl->teleportUser($parsedPutData);
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
        $avatarData       = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

        // Check if the parameters are valid for creating a user
        if(\Controllers\AvatarController::validateParametersCreate($avatarData)) {
            $avatarCtrl       = new \Controllers\AvatarController();
            // Do request and fetch result
            $data = $avatarCtrl->createAvatar($userData);

            // Set result
            $result = $data;
        }
        return $result;
    }
}
