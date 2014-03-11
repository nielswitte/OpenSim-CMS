<?php
namespace Models;

if (EXEC != 1) {
    die('Invalid request');
}

require_once dirname(__FILE__) . '/simpleModel.php';

/**
 * This class provides information about the user's avatar
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 21th, 2014
 */
class Avatar implements simpleModel {
    private $grid;
    private $uuid;
    private $firstName = '', $lastName = '', $email = '';
    private $online = FALSE;
    private $lastPosition = '<0,0,0>';
    private $lastLogin = 0;
    private $lastRegionUuid = 0;
    private $confirmed = FALSE;

    /**
     * Constructs a new avatar on the given Grid and with the given UUID
     *
     * @param \Models\Grid $grid
     * @param string $uuid
     * @throws \Exception
     */
    public function __construct(\Models\Grid $grid, $uuid) {
        $this->grid = $grid;
        if(!\Helper::isValidUuid($uuid)) {
            throw new \Exception("Invalid UUID provided", 1);
        }
        $this->uuid = $uuid;
    }

    /**
     * Gets the avatar information from the database and if possible from the grid
     */
    public function getInfoFromDatabase() {
        // Get additional information if possible
        if($this->grid->getDbUrl() && $this->grid->getOnlineStatus() && \Helper::isValidUuid($this->getUuid())) {
            $osdb = new \MysqliDb($this->grid->getDbUrl(), $this->grid->getDbUsername(), $this->grid->getDbPassword(), $this->grid->getDbName(), $this->grid->getDbPort());
            $osdb->join('UserAccounts u', 'u.PrincipalID = g.UserID', 'LEFT');
            $osdb->where("g.UserID", $osdb->escape($this->getUuid()));
            $results = $osdb->getOne("GridUser g");
            if(!empty($results)) {
                $this->firstName        = $results['FirstName'];
                $this->lastName         = $results['LastName'];
                $this->email            = $results['Email'];
                $this->online           = $results['Online'];
                $this->lastLogin        = $results['Login'];
                $this->lastPosition     = $results['LastPosition'];
                $this->lastRegionUuid   = $results['LastRegionID'];
            }
        }
    }

    /**
     * Returns the userId of the owner of this avatar
     *
     * @return integer or boolean FALSE on failure
     */
    public function getUserId() {
        $db = \Helper::getDB();
        $db->where('uuid', $db->escape($this->getUuid()));
        $db->where('gridId', $db->escape($this->getGrid()->getId()));
        $id = $db->get('avatars', 1);

        return isset($id[0]) ? $id[0]['userId'] : FALSE;
    }

    /**
     * Set to true when an avatar is confirmed
     *
     * @param boolean $confirmed
     */
    public function setConfirmation($confirmed) {
        $this->confirmed = $confirmed;
    }

    /**
     * Returns true when the avatar is confirmed by the user
     *
     * @return boolean
     */
    public function getConfirmation() {
        return $this->confirmed;
    }

    /**
     * Returns the UUID of the avatar
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * The avatar's first name
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

     /**
     * The avatar's last name
     *
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * The avatar's email address
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Returns the grid on which the avatar is located
     *
     * @return Grid
     */
    public function getGrid() {
        return $this->grid;
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
