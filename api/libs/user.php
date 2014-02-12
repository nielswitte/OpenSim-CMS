<?php

require_once dirname(__FILE__) .'/simplelibrary.php';

/**
 * This class is the user model
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 10th, 2014
 */
class User implements SimpleLibrary {
    private $uuid;
    private $userName;
    private $presentationIds;

    /**
     * Construct a new User with the given UUID
     *
     * @param String $userUUID
     */
    public function __construct($userUUID) {
        $this->uuid = $userUUID;

        $this->getInfoFromDatabase();
    }

    /**
     * Gets data from the database
     *
     * @throws Exception
     */
    public function getInfoFromDatabase() {
        $db = Helper::getDB();
        // Get user information
        $db->where("Uuid", $db->escape($this->getUuid()));
        $user = $db->get("users", 1);
        // Get user's presentations
        $db->where("ownerUuid", $db->escape($this->getUuid()));
        $presentations = $db->get("presentations");

        // Convert presentation Ids to array
        $presentationIds = array();
        if(!empty($presentations)) {
            foreach($presentations as $presentation) {
                $presentationIds[] = (string) $presentation['id'];
            }
        }

        // Results!
        if(!empty($user)) {
            $this->userName         = $user[0]['userName'];
            $this->presentationIds  = $presentationIds;
        } else {
            throw new Exception("User not found", 3);
        }
    }

    /**
     * Returns the user's UUID
     *
     * @return String
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Returns the user's user name
     *
     * @return String
     */
    public function getUserName() {
        return $this->userName;
    }

    /**
     * Returns an array with presentation IDs of the user
     *
     * @return Array
     */
    public function getPresentationIds() {
        return $this->presentationIds;
    }

	/**
	 * Function to validate parameters array
	 *
	 * @param Array $parameters
	 *
	 * @return Boolean true when all checks passed
	 */
    public static function validateParameters($parameters) {
        if(count($parameters) == 2) {
            if(Helper::isValidUuid($parameters[1])) {
                return true;
            } else {
                throw new Exception("Paramater two expected to be UUID", 2);
            }
        } else {
            throw new Exception("Invalid number of parameters", 1);
        }
        return false;
    }
}
