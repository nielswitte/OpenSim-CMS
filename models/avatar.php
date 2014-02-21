<?php

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
    private $online = FALSE;
    private $lastPosition = '<0,0,0>';
    private $lastLogin = 0;
    private $lastRegionUuid = 0;

    /**
     * Constructs a new avatar on the given Grid and with the given UUID
     *
     * @param Grid $grid
     * @param string $uuid
     */
    public function __construct(Grid $grid, $uuid) {
        $this->grid = $grid;
        $this->uuid = $uuid;
    }

    /**
     * Gets the avatar information from the database and if possible from the grid
     */
    public function getInfoFromDatabase() {
        // Get additional information if possible
        if($this->grid->getDbUrl() && $this->grid->getOnlineStatus() && Helper::isValidUuid($this->getUuid())) {
            $osdb = new MysqliDb($this->grid->getDbUrl(), $this->grid->getDbUserName(), $this->grid->getDbPassword(), $this->grid->getDbName(), $this->grid->getDbPort());
            $osdb->where("UserID", $osdb->escape($this->getUuid()));
            $results = $osdb->getOne("GridUser");
            if(!empty($results)) {
                $this->online           = $results['Online'];
                $this->lastLogin        = $results['Login'];
                $this->lastPosition     = $results['LastPosition'];
                $this->lastRegionUuid   = $results['LastRegionID'];
            }
        }
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
