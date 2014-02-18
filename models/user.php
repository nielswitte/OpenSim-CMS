<?php
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
    private $uuid;
    private $userName;
    private $firstName, $lastName;
    private $email;
    private $presentationIds;
    private $online = FALSE;
    private $lastPosition = '<0,0,0>';
    private $lastLogin = 0;
    private $lastRegionUuid = 0;

    /**
     * Construct a new User with the given UUID
     *
     * @param string $userUUID
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
        $user = $db->getOne("users");
        // Get user's presentations
        $db->where("ownerId", $db->escape($user['id']));
        $presentations = $db->get("presentations");

        // Convert presentation Ids to array
        $presentationIds = array();
        if(!empty($presentations)) {
            foreach($presentations as $presentation) {
                $presentationIds[] = $presentation['id'];
            }
        }

        // Results!
        if(!empty($user)) {
            $this->id               = $user['id'];
            $this->userName         = $user['userName'];
            $this->firstName        = $user['firstName'];
            $this->lastName         = $user['lastName'];
            $this->email            = $user['email'];
            $this->presentationIds  = $presentationIds;

            if(OS_DB_ENABLED) {
                $osdb = Helper::getOSDB();
                // Get user's additional information
                $osdb->where("UserID", $osdb->escape($this->getUuid()));
                $results = $osdb->getOne("GridUser");

                $this->online           = $results['Online'];
                $this->lastLogin        = $results['Login'];
                $this->lastPosition     = $results['LastPosition'];
                $this->lastRegionUuid   = $results['LastRegionID'];

            }
        } else {
            throw new Exception("User not found", 3);
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
     * Returns the user's UUID
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
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
     * Returns the user's online status
     *
     * @return integer
     */
    public function getOnline() {
        return $this->online == 'True' ? 1 : 0;
    }

    /**
     * Returns the user's last login datetime
     *
     * @return string
     */
    public function getLastLogin() {
        return $this->lastLogin > 0 ? date('Y-m-d H:i:s', $this->lastLogin) : 0;
    }

    /**
     * Returns the user's last position as <x,y,z>
     *
     * @return string
     */
    public function getLastPosition() {
        return $this->lastPosition;
    }

    /**
     * Returns the region UUID of the last position
     *
     * @return string
     */
    public function getLastRegionUuid() {
        return $this->lastRegionUuid;
    }
}
