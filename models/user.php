<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the user model
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 10th, 2014
 */
class User implements SimpleModel {
    private $id;
    private $username;
    private $firstName, $lastName;
    private $email;
    private $presentationIds = array();
    private $avatars = array();

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
        // Get user information
        if($this->getId() > -1) {
            $db->where('id', $db->escape($this->getId()));
        }
        if($this->getUsername() != '') {
            $db->where('username', $db->escape($this->getUsername()));
        }
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
     */
    public function getAvatarsFromDatabase() {
        $db = \Helper::getDB();
        // Get avatars
        $db->where('userId', $db->escape($this->getId()));
        $avatars = $db->get('avatars');
        $i = 1;
        foreach($avatars as $avatar) {
            $grid = new \Models\Grid($avatar['gridId']);
            $grid->getInfoFromDatabase();
            $newAvatar = new \Models\Avatar($grid, $avatar['uuid']);
            $newAvatar->setConfirmation($avatar['confirmed']);
            $newAvatar->getInfoFromDatabase();
            $this->avatars[$i] = $newAvatar;
            $i++;
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
     * @return Avatar
     */
    public function getAvatars() {
        return $this->avatars;
    }
}
