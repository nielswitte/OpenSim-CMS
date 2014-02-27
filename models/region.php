<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class represents a region
 *
 * @author Niels Witte
 * @version 0.2
 * @date February 17th, 2014
 */
class Region implements SimpleModel {
    private $name;
    private $uuid;
    private $grid;
    private $online = FALSE;
    private $activeUsers = -1;
    private $totalUsers = -1;

    /**
     * Creates a new region with the given name and uuid
     *
     * @param string $uuid - Region UUID
     * @param \Models\Grid $gird - The grid where this region is part of
     */
    public function __construct($uuid, \Models\Grid $grid) {
        $this->uuid = $uuid;
        $this->grid = $grid;
    }

    /**
     * Sets the region's name
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Returns the region name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the region UUID
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Gets information about the region
     */
    public function getInfoFromDatabase() {
        $raXML  = new \OpenSimRPC($this->grid->getRaUrl(), $this->grid->getRaPort(), $this->grid->getRaPassword());
        $result = $raXML->call('admin_region_query', array('region_id' => $this->getUuid()));

        if(isset($result['error'])) {
            throw new Exception($result['error'], 3);
        }

        $this->setOnlineStatus($result['success']);

        // Updates the online status of the grid, when so far the status is offline
        // This means that if one region responds it is online, the grid is online, however not all regions
        if(!$this->getGrid()->getOnlineStatus()) {
            $this->getGrid()->setOnlineStatus($result['success']);
        }

        // Additional actions when MySQL database is accessable
        if($this->grid->getDbUrl() && $this->getOnlineStatus()) {
            $osdb = new \MysqliDb($this->grid->getDbUrl(), $this->grid->getDbUsername(), $this->grid->getDbPassword(), $this->grid->getDbName(), $this->grid->getDbPort());
            // Get user's
            $osdb->where("LastRegionID", $osdb->escape($this->getUuid()));
            $results = $osdb->get("GridUser");

            $this->activeUsers  = 0;
            $this->totalUsers   = 0;

            // Count active and total users
            foreach($results as $result) {
                if($result['Online'] == 'True') {
                    $this->activeUsers++;
                }
                $this->totalUsers++;
            }

        }
    }

    /**
     * Returns the grid where this region is part of
     *
     * @return Grid
     */
    public function getGrid() {
        return $this->grid;
    }

    /**
     * Returns the server's online status
     *
     * @return boolean
     */
    public function getOnlineStatus() {
        return $this->online;
    }

    /**
     * Sets the server's status
     *
     * @param boolean $status
     */
    public function setOnlineStatus($status) {
        $this->online = $status;
    }

    /**
     * Returns the API url to this region
     *
     * @return string
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/grid/'. $this->grid->getId() .'/region/'. $this->uuid .'/';
    }

    /**
     * Returns the number of currently active users
     *
     * @return integer
     */
    public function getActiveUsers() {
        return $this->activeUsers;
    }

    /**
     * Returns the total number of users
     *
     * @return integer
     */
    public function getTotalUsers() {
        return $this->totalUsers;
    }
}