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
        $this->api->addModule('user', $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/users\/?$/",                                       "getUsers",               $this, "GET",    TRUE);  // Gets a list of all users
        $this->api->addRoute("/users\/(\d+)\/?$/",                                "getUsers",               $this, "GET",    TRUE);  // Gets a list of all users starting at the given offset
        $this->api->addRoute("/users\/([a-zA-Z0-9-_]{3,}+)\/?$/",                 "getUsersByUsername",     $this, "GET",    TRUE);  // Gets a list of all users with usernames matching the search of atleast 3 characters
        $this->api->addRoute("/user\/?$/",                                        "createUser",             $this, "POST",   TRUE);  // Create a new CMS user
        $this->api->addRoute("/user\/(\d+)\/?$/",                                 "getUserById",            $this, "GET",    TRUE);  // Get a user by ID
        $this->api->addRoute("/user\/(\d+)\/?$/",                                 "updateUserById",         $this, "PUT",    TRUE);  // Update the given user
        $this->api->addRoute("/user\/(\d+)\/password\/?$/",                       "updateUserPasswordById", $this, "PUT",    TRUE);  // Updates the user's password
        $this->api->addRoute("/user\/([a-z0-9-]{36})\/teleport\/?$/",             "teleportAvatarByUuid",   $this, "PUT",    TRUE);  // Teleports a user
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "getUserByAvatar",        $this, "GET",    TRUE);  // Gets an user by the avatar of this grid
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "linkAvatarToUser",       $this, "POST",   TRUE);  // Add this avatar to the user's avatar list
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "confirmAvatar",          $this, "PUT",    TRUE);  // Confirms the avatar for the authenticated user
        $this->api->addRoute("/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",        "unlinkAvatar",           $this, "DELETE", TRUE);  // Removes the avatar for the authenticated user's avatar list
        $this->api->addRoute("/grid\/(\d+)\/avatar\/?$/",                         "createAvatar",           $this, "POST",   TRUE);  // Create an avatar
    }

    /**
     * Creates a new user with the given POST parameters
     *
     * @param array $args
     * @return array
     */
    public function createUser($args) {
        $userData = \Helper::getInput(TRUE);

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
     * Update the information on the given user
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateUserById($args) {
        $putUserData    = \Helper::getInput(TRUE);

        $user           = new \Models\User($args[1]);
        $userCtrl       = new \Controllers\UserController($user);
        $data           = FALSE;
        if($userCtrl->validateParameterUpdateUser($putUserData)) {
            $data     = $userCtrl->updateUser($putUserData);
            // No changes made
            if($data !== TRUE) {
                throw new \Exception("No rows updated, did you really made any changes?", 5);
            }
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
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
        $putUserData    = \Helper::getInput(TRUE);

        $user           = new \Models\User($args[1]);
        $userCtrl       = new \Controllers\UserController($user);
        $data           = FALSE;
        if($userCtrl->validateParameterPassword($putUserData)) {
            $data     = $userCtrl->setPassword($putUserData['password']);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Retuns a list with users, optionally starting at the given offset
     *
     * @param array $args
     * @return array
     */
    public function getUsers($args) {
        $db             = \Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array($db->escape($args[1]), 50);
        $resutls        = $db->rawQuery("SELECT * FROM users ORDER BY LOWER(username) ASC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $user       = new \Models\User($result['id'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
            $data[]     = $this->getUserData($user, FALSE);
        }
        return $data;
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
        $results        = $db->rawQuery('SELECT * FROM users WHERE username LIKE ? ORDER BY LOWER(username) ASC', $params);
        $data           = array();
        foreach($results as $result) {
            $user       = new \Models\User($result['id']);
            $user->getInfoFromDatabase();
            $data[]     = self::getUserData($user, FALSE);
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
    public function getUserData(\Models\User $user, $full = TRUE) {
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
                    'firstName'     => $avatar->getFirstName(),
                    'lastName'      => $avatar->getLastName(),
                    'email'         => $avatar->getEmail(),
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
    public function linkAvatarToUser($args) {
        $putUserData    = \Helper::getInput(TRUE);
        $username       = isset($putUserData['username']) ? $putUserData['username'] : '';

        $userCtrl       = new \Controllers\UserController();
        $data           = $userCtrl->linkAvatar($username, $args[1], $args[2]);

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Confirms that the given avatar UUID on the given grid is owned by the
     * currently loggedin user
     *
     * @param array $args
     * @return array
     */
    public function confirmAvatar($args) {
        $user           = \API\Auth::getUser();
        $userCtrl       = new \Controllers\UserController($user);
        $grid           = new \Models\Grid($args[1]);
        $avatar         = new \Models\Avatar($grid, $args[2]);
        $data           = $userCtrl->confirmAvatar($avatar);
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Deletes the link between the currently loggedin user
     * and the given avatar UUID on the given grid
     *
     * @param array $args
     * @return array
     */
    public function unlinkAvatar($args) {
        $user           = \API\Auth::getUser();
        $userCtrl       = new \Controllers\UserController($user);
        $grid           = new \Models\Grid($args[1]);
        $avatar         = new \Models\Avatar($grid, $args[2]);
        $data           = $userCtrl->unlinkAvatar($avatar);
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
        $parsedPutData  = \Helper::getInput(TRUE);

        // use UUID from GET request
        $parsedPutData['agentUuid'] = $args[1];
        $result                     = '';
        // Teleport an avatar
        if(\Controllers\AvatarController::validateParametersTeleport($parsedPutData)) {
            $avatarCtrl             = new \Controllers\AvatarController();
            // Do request and fetch result
            $data                   = $avatarCtrl->teleportUser($parsedPutData);
            // Set result
            $result                 = $data;
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
        $result                     = '';
        $avatarData                 = \Helper::getInput(TRUE);
        //$avatarData['email']        = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        //$avatarData['password']     = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        //$avatarData['firstName']    = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        //$avatarData['lastName']     = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        //$avatarData['startRegionX'] = filter_input(INPUT_POST, 'startRegionX', FILTER_SANITIZE_NUMBER_FLOAT);
        //$avatarData['startRegionY'] = filter_input(INPUT_POST, 'startRegionY', FILTER_SANITIZE_NUMBER_FLOAT);
        $avatarData['gridId']       = $args[1];

        // Check if the parameters are valid for creating a user
        if(\Controllers\AvatarController::validateParametersCreate($avatarData)) {
            $avatarCtrl       = new \Controllers\AvatarController();
            // Do request and fetch result
            $data = $avatarCtrl->createAvatar($avatarData);
            echo $avatarData['password'];
            // Set result
            $result = $data;
        }
        return $result;
    }
}
