<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the user model
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 20th, 2014
 * @since February 10th, 2014
 */
class User implements SimpleModel {
    private $id;
    private $username;
    private $firstName, $lastName;
    private $email;
    private $presentationIds = array();
    private $avatars;
    private $rights = array();

    /**
     * Construct a new User with the given UUID
     *
     * @param integer $id - [Optional] The ID of the user
     * @param string $username - [Optional] the user's user name
     * @param string $email - [Optional] the user's email address
     * @param string $firstName - [Optional] the user's first name
     * @param string $lastName- [Optional] the user's last name
     */
    public function __construct($id = -1, $username = '', $email = '', $firstName = '', $lastName = '') {
        $this->id           = $id;
        $this->username     = $username;
        $this->email        = $email;
        $this->firstName    = $firstName;
        $this->lastName     = $lastName;
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
            $user = $db->get('users', 1);

            // Results!
            if(isset($user[0])) {
                $this->id               = $user[0]['id'];
                $this->username         = $user[0]['username'];
                $this->firstName        = $user[0]['firstName'];
                $this->lastName         = $user[0]['lastName'];
                $this->email            = $user[0]['email'];
            } else {
                throw new \Exception("User not found", 3);
            }
        } else {
            throw new \Exception("No username or ID provided", 4);
        }
    }

    /**
     * Gets the user's presentations from the database
     */
    public function getPresentationsFromDatabase() {
        $db = \Helper::getDB();
        // Get user's presentations
        $db->where("ownerId", $db->escape($this->getId()));
        $db->where('type', $db->escape('presentation'));
        $presentations = $db->get("documents");

        // Convert presentation Ids to array
        if(!empty($presentations)) {
            foreach($presentations as $presentation) {
                $this->presentationIds[] = $presentation['id'];
            }
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
     * Returns an array with presentation IDs of the user
     *
     * @return array
     */
    public function getPresentationIds() {
        return $this->presentationIds;
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
                'document'          => (int) \Auth::NONE, // 0
                'grid'              => (int) \Auth::NONE, // 0
                'meeting'           => (int) \Auth::NONE, // 0
                'meetingroom'       => (int) \Auth::NONE, // 0
                'presentation'      => (int) \Auth::NONE, // 0
                'user'              => (int) \Auth::NONE  // 0
            );

            // Get user's permissions from DB
            $db = \Helper::getDB();
            $db->where('userId', $db->escape($this->getId()));
            $result = $db->get('user_permissions', 1);

            if(isset($result[0])) {
                $this->rights = array(
                    'auth'              => $result[0]['auth'],
                    'chat'              => $result[0]['chat'],
                    'document'          => $result[0]['document'],
                    'grid'              => $result[0]['grid'],
                    'meeting'           => $result[0]['meeting'],
                    'meetingroom'       => $result[0]['meetingroom'],
                    'presentation'      => $result[0]['presentation'],
                    'user'              => $result[0]['user'],
                );
            }
        }
        return $this->rights;
    }
}