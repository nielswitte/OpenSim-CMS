<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the user model
 *
 * @author Niels Witte
 * @version 0.4
 * @date April 9th, 2014
 * @since February 10th, 2014
 */
class User implements SimpleModel {
    private $id;
    private $username;
    private $firstName, $lastName;
    private $email;
    private $lastLogin;
    private $avatars;
    private $rights = array();

    /**
     * Construct a new User with the given UUID
     *
     * @param integer $id - [Optional] The ID of the user
     * @param string $username - [Optional] The user's user name
     * @param string $email - [Optional] The user's email address
     * @param string $firstName - [Optional] The user's first name
     * @param string $lastName - [Optional] The user's last name
     * @param string $lastLogin - [Optional] The last timestamp the user logged in (format: YYYY-MM-DD HH:mm:ss)
     */
    public function __construct($id = -1, $username = '', $email = '', $firstName = '', $lastName = '', $lastLogin = '0000-00-00 00:00:00') {
        $this->id           = $id;
        $this->username     = $username;
        $this->email        = $email;
        $this->firstName    = $firstName;
        $this->lastName     = $lastName;
        $this->lastLogin    = $lastLogin;
    }

    /**
     * Gets data from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        // Get user information based on ID
        if($this->getId() > -1) {
            $db->where('id', $db->escape($this->getId()));
        }
        // Based on Username
        if($this->getUsername() != '') {
            $db->where('username', $db->escape($this->getUsername()));
        }
        if($this->getUsername() != '' || $this->getId() > -1) {
            $user = $db->getOne('users');

            // Results!
            if($user) {
                $this->id               = $user['id'];
                $this->username         = $user['username'];
                $this->firstName        = $user['firstName'];
                $this->lastName         = $user['lastName'];
                $this->email            = $user['email'];
                $this->lastLogin        = $user['lastLogin'];
            } else {
                throw new \Exception("User not found", 3);
            }
        } else {
            throw new \Exception("No username or ID provided", 4);
        }
    }

    /**
     * Gets the user's avatars from the database
     *
     * @param boolean $full - Also get information from Grid if possible
     */
    public function getAvatarsFromDatabase($full = TRUE) {
        $db = \Helper::getDB();
        // Get avatars
        $db->where('userId', $db->escape($this->getId()));
        $avatars = $db->get('avatars');

        foreach($avatars as $avatar) {
            $grid       = new \Models\Grid($avatar['gridId']);
            $newAvatar  = new \Models\Avatar($grid, $avatar['uuid']);
            $newAvatar->setConfirmation($avatar['confirmed']);
            // Get additional data from Grid?
            if($full) {
                $grid->getInfoFromDatabase();
                $newAvatar->getInfoFromDatabase();
            }
            $this->addAvatar($newAvatar);
        }
    }

    /**
     * Returns the user's ID
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the user's user name
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Returns the user's first name
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * Returns the user's last name
     *
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * Returns the user's email address
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Returns the last login timestamp of the user
     *
     * @return string - Format YYYY-MM-DD HH:mm:ss
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * Returns a list with avatars for this user
     *
     * @return array
     */
    public function getAvatars() {
        return $this->avatars;
    }

    /**
     * Adds the given avatar to the user
     *
     * @param \Models\Avatar $avatar
     */
    public function addAvatar(\Models\Avatar $avatar) {
        $this->avatars[] = $avatar;
    }

    /**
     * Searches the list of avatars for the given UUID
     *
     * @param string $uuid
     * @return \Models\Avatar or boolean FALSE when no match is found
     */
    public function getAvatarByUuid($uuid) {
        // Only when not NULL or not FALSE
        if(is_array($this->getAvatars())) {
            foreach($this->getAvatars() as $avatar) {
                if($avatar->getUuid() == $uuid) {
                    return $avatar;
                }
            }
        }
        return FALSE;
    }

    /**
     * Gets the users rights
     *
     * @return array
     */
    public function getRights() {
        if(empty($this->rights)) {
            // Default rights
            $this->rights = array(
                'auth'              => (int) \Auth::NONE, // 0
                'chat'              => (int) \Auth::NONE, // 0
                'comment'           => (int) \Auth::NONE, // 0
                'document'          => (int) \Auth::NONE, // 0
                'file'              => (int) \Auth::NONE, // 0
                'grid'              => (int) \Auth::NONE, // 0
                'meeting'           => (int) \Auth::NONE, // 0
                'meetingroom'       => (int) \Auth::NONE, // 0
                'presentation'      => (int) \Auth::NONE, // 0
                'user'              => (int) \Auth::NONE  // 0
            );

            // Get user's permissions from DB
            $db = \Helper::getDB();
            $db->where('userId', $db->escape($this->getId()));
            $result = $db->getOne('user_permissions');

            if($result) {
                $this->rights = array(
                    'auth'              => $result['auth'],
                    'chat'              => $result['chat'],
                    'comment'           => $result['comment'],
                    'document'          => $result['document'],
                    'grid'              => $result['grid'],
                    'file'              => $result['file'],
                    'meeting'           => $result['meeting'],
                    'meetingroom'       => $result['meetingroom'],
                    'presentation'      => $result['presentation'],
                    'user'              => $result['user'],
                );
            }
        }
        return $this->rights;
    }

    /**
     * If file exists show the path to the file or return FALSE when no profile picture exists
     *
     * @return string or boolean FALSE when file does not exists
     */
    public function getPicture() {
        $path = FILES_LOCATION . DS .'users'. DS . $this->getId() . DS . $this->getId() .'.'. IMAGE_TYPE;
        if(file_exists($path)) {
            $result = $path;
        } else {
            $result = FALSE;
        }

        return $result;
    }

    /**
     * Returns the path to retrieve the picture for this user
     *
     * @return string
     */
    public function getPictureApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/user/'. $this->getId() .'/picture/';
    }
}
