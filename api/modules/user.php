<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/avatar.php';
require_once dirname(__FILE__) .'/../controllers/avatarController.php';
require_once dirname(__FILE__) .'/../models/group.php';
require_once dirname(__FILE__) .'/../controllers/groupController.php';
require_once dirname(__FILE__) .'/../models/user.php';
require_once dirname(__FILE__) .'/../models/userLoggedIn.php';
require_once dirname(__FILE__) .'/../controllers/userController.php';

/**
 * Implements the functions for users
 *
 * @author Niels Witte
 * @version 0.9
 * @date April 21st, 2014
 * @since February 24th, 2014
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
        $this->setName('user');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/^\/users\/?$/",                                          'getUsers',                 $this, 'GET',    \Auth::READ);     // Gets a list of 50 users
        $this->api->addRoute("/^\/users\/(\d+)\/?$/",                                   'getUsers',                 $this, 'GET',    \Auth::READ);     // Gets a list of 50 users starting at the given offset
        $this->api->addRoute("/^\/users\/([a-zA-Z0-9-_\.]{3,}+)\/?$/",                  'getUsersByUsername',       $this, 'GET',    \Auth::READ);     // Gets a list of all users with usernames matching the search of atleast 3 characters
        $this->api->addRoute("/^\/user\/?$/",                                           'createUser',               $this, 'POST',   \Auth::WRITE);    // Create a new CMS user
        $this->api->addRoute("/^\/user\/(\d+)\/?$/",                                    'getUserById',              $this, 'GET',    \Auth::READ);     // Get a user by ID
        $this->api->addRoute("/^\/user\/(\d+)\/?$/",                                    'updateUserById',           $this, 'PUT',    \Auth::READ);     // Update the given user
        $this->api->addRoute("/^\/user\/(\d+)\/?$/",                                    'deleteUserById',           $this, 'DELETE', \Auth::WRITE);    // Delete the given user
        $this->api->addRoute("/^\/user\/(\d+)\/files\/?$/",                             'getUserFilesByUserId',     $this, 'GET',    \Auth::READ);     // Load all files for the user
        $this->api->addRoute("/^\/user\/(\d+)\/meetings\/?$/",                          'getUserMeetingsByUserId',  $this, 'GET',    \Auth::READ);     // Load 50 meetings for the user
        $this->api->addRoute("/^\/user\/(\d+)\/meetings\/(\d+)\/?$/",                   'getUserMeetingsByUserId',  $this, 'GET',    \Auth::READ);     // Load 50 meetings for the user with offset
        $this->api->addRoute("/^\/user\/(\d+)\/meetings\/calendar\/?$/",                'getUserMeetingsCalendarByUserId',  $this, 'GET', \Auth::READ); // Load all meetings for the user
        $this->api->addRoute("/^\/user\/(\d+)\/picture\/?$/",                           'getUserPictureById',       $this, 'GET',    \Auth::READ);     // Shows the user's profile picture
        $this->api->addRoute("/^\/user\/(\d+)\/picture\/?$/",                           'updateUserPictureByUserID',$this, 'PUT',    \Auth::READ);     // Updates the user's profile picture with the given image
        $this->api->addRoute("/^\/user\/(\d+)\/password\/?$/",                          'updateUserPasswordById',   $this, 'PUT',    \Auth::READ);     // Updates the user's password
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/teleport\/?$/", 'teleportAvatarByUuid',     $this, 'PUT',    \Auth::READ);     // Teleports a user
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",           'getUserByAvatar',          $this, 'GET',    \Auth::READ);     // Gets an user by the avatar of this grid
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",           'linkAvatarToUser',         $this, 'POST',   \Auth::EXECUTE);  // Add this avatar to the user's avatar list
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",           'confirmAvatar',            $this, 'PUT',    \Auth::READ);     // Confirms the avatar for the authenticated user
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/?$/",           'unlinkAvatar',             $this, 'DELETE', \Auth::READ);     // Removes the avatar for the authenticated user's avatar list
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/?$/",                            'createAvatar',             $this, 'POST',   \Auth::EXECUTE);  // Create an avatar
        $this->api->addRoute("/^\/grid\/(\d+)\/avatar\/([a-z0-9-]{36})\/files\/?$/",    'getUserFilesByAvatar',     $this, 'GET',    \Auth::READ);     // Load all files for the user accociated with the avatar UUID on the given grid
        $this->api->addRoute("/^\/groups\/?$/",                                         'getGroups',                $this, 'GET',    \Auth::READ);     // Gets a list with groups 50 groups
        $this->api->addRoute("/^\/groups\/(\d+)\/?$/",                                  'getGroups',                $this, 'GET',    \Auth::READ);     // Gets a list with groups 50 groups starting at given offset
        $this->api->addRoute("/^\/groups\/([a-zA-Z0-9-_ \.\(\)\[\]]{3,}+)\/?$/",        'getGroupsByName',          $this, 'GET',    \Auth::READ);     // Gets a list with groups
        $this->api->addRoute("/^\/group\/?$/",                                          'createGroup',              $this, 'POST',   \Auth::EXECUTE);  // Create a new group
        $this->api->addRoute("/^\/group\/(\d+)\/?$/",                                   'getGroupById',             $this, 'GET',    \Auth::READ);     // Gets a group bu ID
        $this->api->addRoute("/^\/group\/(\d+)\/?$/",                                   'updateGroupById',          $this, 'PUT',    \Auth::EXECUTE);  // Update group by ID
        $this->api->addRoute("/^\/group\/(\d+)\/?$/",                                   'removeGroupById',          $this, 'DELETE', \Auth::EXECUTE);  // Remove group by ID
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
     * Update the information on the given user and when allowed its permissions
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateUserById($args) {
        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $args[1] != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to update this user.', 6);
        }

        $putUserData    = \Helper::getInput(TRUE);
        $user           = new \Models\User($args[1]);
        $userCtrl       = new \Controllers\UserController($user);
        $data           = FALSE;
        $permissions    = FALSE;

        if($userCtrl->validateParameterUpdateUser($putUserData)) {
            $data     = $userCtrl->updateUser($putUserData);
            // Allowed to change user permissions, user permissions set and valid?
            if(\Auth::checkRights($this->getName(), \Auth::WRITE) && isset($putUserData['permissions']) && $userCtrl->validatePermissions($putUserData['permissions'])) {
                $permissions = $userCtrl->updateUserPermissions($putUserData['permissions']);
            }

            // No changes made
            if($data !== TRUE && $permissions !== TRUE) {
                throw new \Exception('No rows updated, did you really made any changes?', 5);
            }
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE || $permissions !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Sets the profile picture for the given user
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateUserPictureByUserID($args) {
        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $args[1] != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to update this user.', 6);
        }
        $data       = FALSE;
        // Create user object to validate userId
        $user       = new \Models\User($args[1]);
        $user->getInfoFromDatabase();
        $userCtrl   = new \Controllers\UserController($user);
        // Process data
        $input      = \Helper::getInput(TRUE);
        if($userCtrl->validateParameterPicture($input)) {
            $data       = $userCtrl->updateUserPicture($input);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE)
        );
        return $result;
    }

    /**
     * Removes the given user
     * @param array $args
     * @return array
     */
    public function deleteUserById($args) {
        $user     = new \Models\User($args[1]);
        $userCtrl = new \Controllers\UserController($user);
        $data     =  $userCtrl->removeUser();

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
        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $args[1] != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to update this user.', 6);
        }

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
        // Get 50 users from the given offset
        $db->orderBy('LOWER(username)', 'ASC');
        $resutls        = $db->get('users', array($args[1], 50));
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $user       = new \Models\User($result['id'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
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
        $params         = array("%". strtolower($db->escape($args[1])) ."%");
        $results        = $db->rawQuery('SELECT * FROM users WHERE LOWER(username) LIKE ? ORDER BY LOWER(username) ASC', $params);
        $data           = array();
        foreach($results as $result) {
            $user       = new \Models\User($result['id']);
            $user->getInfoFromDatabase();
            $data[]     = $this->getUserData($user, FALSE);
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
        $user->getGroupsFromDatabase();
        $user->getAvatarsFromDatabase();
        return $this->getUserData($user, TRUE);
    }

    /**
     * Returns the user's profile picture
     *
     * @param array $args
     */
    public function getUserPictureById($args) {
        $user = new \Models\User($args[1]);
        $user->getInfoFromDatabase();
        require_once dirname(__FILE__) .'/../includes/class.Images.php';
        $image = new \Image($user->getPicture());
        $image->display();
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
            $avatarQuery = $db->getOne('avatars');
            if($avatarQuery) {
                $user = new \Models\User($avatarQuery['userId']);
                $user->getInfoFromDatabase();
                $user->getAvatarsFromDatabase();
                $data = $this->getUserData($user, TRUE);
            } else {
                throw new \Exception('Avatar not found on this Grid', 2);
            }
        } else {
            throw new \Exception('Invalid UUID provided', 1);
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
        $data['picture']            = $user->getPicture() !== FALSE ? $user->getPictureApiUrl() : FALSE;
        $data['lastLogin']          = $user->getLastLogin();

        if($full) {
            $data['permissions']        = $user->getRights();
            $avatars                    = array();
            // Only when avatars available
            if($user->getAvatars() !== NULL) {
                foreach($user->getAvatars() as $avatar) {
                    $avatars[] = array(
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
                }
            }
            $data['avatars']            = $avatars;
            $groups                     = array();
            foreach($user->getGroups() as $group) {
                $groups[]               = $this->getGroupData($group);
            }
            $data['groups']             = $groups;
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

        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $username != \Auth::getUser()->getUsername()) {
            throw new \Exception('You do not have permissions to link avatars to this user.', 6);
        }
        $user           = new \Models\User(-1, $username);
        $user->getInfoFromDatabase();
        $userCtrl       = new \Controllers\UserController($user);
        $data           = $userCtrl->linkAvatar($args[1], $args[2]);

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Confirms that the given avatar UUID on the given grid is owned by the
     * linked user
     *
     * @param array $args
     * @return array
     */
    public function confirmAvatar($args) {
        $grid           = new \Models\Grid($args[1]);
        $avatar         = new \Models\Avatar($grid, $args[2]);
        $user           = new \Models\User($avatar->getUserId());

        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $user->getId() != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to confirm avatars for this user.', 6);
        }

        $userCtrl       = new \Controllers\UserController($user);
        $data           = $userCtrl->confirmAvatar($avatar);
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    /**
     * Deletes the link between the user and the given avatar UUID on the given grid
     *
     * @param array $args
     * @return array
     */
    public function unlinkAvatar($args) {
        $grid           = new \Models\Grid($args[1]);
        $avatar         = new \Models\Avatar($grid, $args[2]);
        $user           = new \Models\User($avatar->getUserId());

        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $user->getId() != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to unlink avatars for this user.', 6);
        }

        $userCtrl       = new \Controllers\UserController($user);
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
        // Only allow when the user has write access or wants to update his/her own profile
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $args[1] != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to teleport this user.', 6);
        }

        $parsedPutData  = \Helper::getInput(TRUE);

        // use UUID from GET request
        $parsedPutData['agentUuid'] = $args[2];
        $parsedPutData['gridId']    = $args[1];
        $result                     = '';
        $avatarCtrl                 = new \Controllers\AvatarController();
        // Teleport an avatar
        if($avatarCtrl->validateParametersTeleport($parsedPutData)) {
            // Do request and fetch result
            $data                   = $avatarCtrl->teleportAvatar($parsedPutData);
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
        $avatarData['gridId']       = $args[1];
        $avatarCtrl                 = new \Controllers\AvatarController();
        // Check if the parameters are valid for creating a user
        if($avatarCtrl->validateParametersCreate($avatarData)) {
            $avatarExists = $avatarCtrl->AvatarExists(array('gridId' => $avatarData['gridId'], 'firstName' => $avatarData['firstName'], 'lastName' => $avatarData['lastName']));

            // No existsing avatar found?
            if(!$avatarExists['success']) {
                // Do request and fetch result
                $data = $avatarCtrl->createAvatar($avatarData);
                // Set result
                $result = $data;
            } else {
                throw new \Exception('An avatar already exists on this grid with the given first and last name', 1);
            }
        }
        return $result;
    }

    /**
     * Checks the grid to see if the avatar already exists
     *
     * @param array $args
     * @return array
     */
    public function avatarExists($args) {
        $result                     = '';
        $avatarData                 = \Helper::getInput(TRUE);
        $avatarData['gridId']       = $args[1];
        $avatarCtrl                 = new \Controllers\AvatarController();
        // Check if the parameters are valid for creating a user
        if($avatarCtrl->validateParametersAvatarExists($avatarData)) {

            // Do request and fetch result
            $data = $avatarCtrl->avatarExists($avatarData);
            // Set result
            $result = $data;
        }
        return $result;
    }

    /**
     * Returns a formatted list with documents owned by this user or shared with the user.
     *
     * @param array $args
     * @return array
     */
    public function getUserFilesByUserId($args) {
        $data = array();
        $db = \Helper::getDB();
        $params = array(
            $db->escape($args[1]),
            $db->escape($args[1]),
        );
        // This query fails when written as DB object
        // Retrieve all documents the user can access as the member of a group
        // or as documents owned by the user self
        $documents = $db->rawQuery('
                    SELECT DISTINCT
                        d.*,
                        u.*,
                        d.id AS documentId,
                        u.id AS userId
                    FROM
                        group_documents gd,
                        group_users gu,
                        documents d
                    LEFT JOIN
                        users u
                    ON
                        d.ownerId = u.id
                    WHERE (
                        gd.documentId = d.id
                    AND
                        gd.groupId = gu.groupId
                    AND
                        gu.userId = ?
                    ) OR
                        d.ownerId = ?
                    ORDER BY
                        d.creationDate DESC'
            , $params);

        foreach($documents as $document) {
            $user   = new \Models\User($document['userId'], $document['username'], $document['email'], $document['firstName'], $document['lastName'], $document['lastLogin']);
            $file   = new \Models\File($document['documentId'], $document['type'], $document['title'], $user, $document['creationDate'], $document['modificationDate'], $document['file']);
            $data[] = $this->api->getModule('file')->getFileData($file);
        }

        return $data;
    }

    /**
     * Gets a list with files for the user attached to the given avatar on the given grid
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getUserFilesByAvatar($args) {
        if(\Helper::isValidUuid($args[2])) {
            $db = \Helper::getDB();
            $db->where('uuid', $db->escape($args[2]));
            $db->where('gridId', $db->escape($args[1]));
            $avatarQuery = $db->getOne('avatars');
            if($avatarQuery) {
                return $this->getUserFilesByUserId(array('', $avatarQuery['userId']));
            } else {
                throw new \Exception('Avatar not found on this Grid', 2);
            }
        } else {
            throw new \Exception('Invalid UUID provided', 1);
        }
    }

    /**
     * Returns a list with 50 meetings starting at the given offset for the currently logged in user
     * in reversed chronological order
     *
     * @param array $args
     * @return array
     */
    public function getUserMeetingsByUserId($args) {
        $offset     = isset($args[2]) ? $args[2] : 0;
        $limit      = array($offset, 50);

        // Get info from DB
        $db         = \Helper::getDB();
        $db->where('mp.userId', $db->escape($args[1]));
        $db->join('meeting_participants mp', 'mp.meetingId = m.id', 'LEFT');
        $db->orderBy('m.startDate', 'DESC');
        $results = $db->get('meetings m', $limit);
        // Get user info
        $user = \Auth::getUser();
        // Process results
        $data = array();
        foreach($results as $result) {
            $room    = new \Models\MeetingRoom($result['roomId']);
            $meeting = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room, $result['name']);
            $data[]  = $this->api->getModule('meeting')->getMeetingData($meeting, FALSE);
        }

        return $data;
    }

    /**
     * Returns a list with 50 meetings starting at the given offset for the currently logged in user
     * in reversed chronological order
     *
     * @param array $args
     * @return array
     */
    public function getUserMeetingsCalendarByUserId($args) {
        // Get info from DB
        $db     = \Helper::getDB();
        $db->where('mp.userId', $db->escape($args[1]));
        $db->join('meeting_participants mp', 'mp.meetingId = m.id', 'LEFT');
        $db->orderBy('m.startDate', 'DESC');
        $results = $db->get('meetings m');
        // Get user info
        $user = \Auth::getUser();
        // Process results
        $data = array();
        foreach($results as $result) {
            $room    = new \Models\MeetingRoom($result['roomId']);
            $meeting = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room, $result['name']);
            $data[]  = $this->api->getModule('meeting')->getMeetingData($meeting, FALSE, TRUE);
        }

        return $data;
    }

    /**
     * Returns a list with 50 groups starting at optionally the given offset
     *
     * @param array $args
     * @return array
     */
    public function getGroups($args) {
        $offset = isset($args[1]) ? $args[1] : 0;
        $db     = \Helper::getDB();
        $db->orderBy('LOWER(name)', 'ASC');
        $groups = $db->get('groups', array($offset, 50));
        $data   = array();
        // Process all groups
        foreach($groups as $group) {
            $group = new \Models\Group($group['id'], $group['name']);
            $data[] = $this->getGroupData($group);
        }

        return $data;
    }

    /**
     * Will search for a group by its name
     *
     * @param array $args
     * @return array
     */
    public function getGroupsByName($args) {
        $db             = \Helper::getDB();
        $params         = array("%". strtolower($db->escape($args[1])) ."%");
        $groups         = $db->rawQuery('SELECT * FROM groups WHERE LOWER(name) LIKE ? ORDER BY LOWER(name) ASC', $params);
        $data           = array();
        foreach($groups as $group) {
            $group = new \Models\Group($group['id'], $group['name']);
            $data[] = $this->getGroupData($group);
        }
        return $data;
    }

    /**
     * Gets a specific group by its ID
     *
     * @param array $args
     * @return array
     */
    public function getGroupById($args){
        $group = new \Models\Group($args[1]);
        $group->getInfoFromDatabase();
        $group->getGroupFilesFromDatabase();
        $group->getGroupUsersFromDatabase();
        return $this->getGroupData($group, TRUE);
    }

    /**
     * Parses the group data to a nice format
     *
     * @param \Models\Group $group
     * @param boolean $full - [Optional] Show all group details?
     * @return array
     */
    public function getGroupData(\Models\Group $group, $full = FALSE) {
        $data = array(
            'id'    => $group->getId(),
            'name'  => $group->getName(),
        );

        // Get additional details
        if($full) {
            // Add all files
            $files = array();
            foreach($group->getFiles() as $file) {
                $files[] = $this->api->getModule('file')->getFileData($file, FALSE);
            }
            $data['files'] = $files;

            // Add all useres
            $users = array();
            foreach($group->getUsers() as $user) {
                $users[] = $this->api->getModule('user')->getUserData($user, FALSE);
            }
            $data['users'] = $users;
        }
        return $data;
    }

    /**
     * Creates a new group with the given POST data
     *
     * @param array $args
     * @return array
     */
    public function createGroup($args) {
        $input      = \Helper::getInput(TRUE);
        $groupCtrl  = new \Controllers\GroupController();
        $data       = FALSE;
        if($groupCtrl->validateParametersCreate($input)) {
            $data = $groupCtrl->createGroup($input);
        }

        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
            'groupId' => ($data !== FALSE ? $data : 0)
        );

        return $result;
    }

    public function updateGroupById($args){
        $input = \Helper::getInput(TRUE);
        $group = new \Models\Group($args[1]);
        $groupCtrl = new \Controllers\GroupController($group);
        if($groupCtrl->validateParametersUpdate($input)) {
            $data = $groupCtrl->updateGroup($input);
        }

        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE)
        );

        return $result;
    }

    public function deleteGroupById($args){

    }
}
