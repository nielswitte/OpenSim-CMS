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
    private $userName;
    private $firstName, $lastName;
    private $email;
    private $presentationIds = array();
    private $avatars = array();

    /**
     * Construct a new User with the given UUID
     *
     * @param integer $id - [Optional] The ID of the user
     * @param string $userName - [Optional] the user's user name
     */
    public function __construct($id = 0, $userName = '') {
        $this->id       = $id;
        $this->userName = $userName;
    }

    /**
     * Gets data from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        // Get user information
        if($this->getId() != 0) {
            $db->where('id', $db->escape($this->getId()));
        }
        if($this->getUserName() != '') {
            $db->where('userName', $db->escape($this->getUserName()));
        }
        $user = $db->get('users', 1);

        // Results!
        if(isset($user[0])) {
            $this->id               = $user[0]['id'];
            $this->userName         = $user[0]['userName'];
            $this->firstName        = $user[0]['firstName'];
            $this->lastName         = $user[0]['lastName'];
            $this->email            = $user[0]['email'];

            // Get user's presentations
            $db->where("ownerId", $this->getId());
            $db->where('type', 'presentation');
            $presentations = $db->get("documents");

            // Convert presentation Ids to array
            if(!empty($presentations)) {
                foreach($presentations as $presentation) {
                    $this->presentationIds[] = $presentation['id'];
                }
            }

            $db->where('userId', $this->getId());
            $avatars = $db->get('avatars');
            $i = 1;
            foreach($avatars as $avatar) {
                $grid = new \Models\Grid($avatar['gridId']);
                $grid->getInfoFromDatabase();
                $newAvatar = new \Models\Avatar($grid, $avatar['uuid']);
                $newAvatar->getInfoFromDatabase();
                $this->avatars[$i] = $newAvatar;
                $i++;
            }
        } else {
            throw new \Exception("User not found", 3);
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
    public function getUserName() {
        return $this->userName;
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
