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
     * @param integer $id - [Optional] The ID of the user
     * @param string $userUUID - [Optional] the user's UUID
     * @param string $userName - [Optional] the user's user name
     */
    public function __construct($id = 0, $userUUID = 0, $userName = '') {
        $this->id       = $id;
        $this->uuid     = $userUUID;
        $this->userName = $userName;
    }

    /**
     * Gets data from the database
     *
     * @throws Exception
     */
    public function getInfoFromDatabase() {
        $db = Helper::getDB();
        // Get user information
        if($this->getId() != 0) {
            $db->where("id", $db->escape($this->getId()));
        }
        if(Helper::isValidUuid($this->getUuid())) {
            $db->where("uuid", $db->escape($this->getUuid()));
        }
        if($this->getUserName() != '') {
            $db->where("userName", $db->escape($this->getUserName()));
        }

        $user = $db->getOne("users");

        // Results!
        if(!empty($user)) {
            $this->id               = $user['id'];
            $this->uuid             = $user['uuid'];
            $this->userName         = $user['userName'];
            $this->firstName        = $user['firstName'];
            $this->lastName         = $user['lastName'];
            $this->email            = $user['email'];

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

            $this->presentationIds  = $presentationIds;

            // Get additional information if possible
            if(OS_DB_ENABLED && $this->getUuid() != 0) {
                $osdb = Helper::getOSDB();
                $osdb->where("UserID", $osdb->escape($this->getUuid()));
                $results = $osdb->getOne("GridUser");
                if(!empty($results)) {
                    $this->online           = $results['Online'];
                    $this->lastLogin        = $results['Login'];
                    $this->lastPosition     = $results['LastPosition'];
                    $this->lastRegionUuid   = $results['LastRegionID'];
                }
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
