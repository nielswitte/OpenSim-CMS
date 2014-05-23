<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class represents a region
 *
 * @author Niels Witte
 * @version 0.5
 * @date May 23, 2014
 * @since February 17, 2014
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
     *
     * Prefers Remote Admin and MySQL,
     * but can also try XML API requests to load some simple information
     */
    public function getInfoFromDatabase() {
        // Only when no timeout occured this instance
        if(!$this->getGrid()->getTimedOut()) {
            $raXML  = FALSE;
            $xml    = FALSE;

            // Use remote admin
            if($this->grid->getRaUrl() != '' && $this->grid->getRaPort() != '' && $this->grid->getRaPassword() != '') {
                $raXML  = new \OpenSimRPC($this->grid->getRaUrl(), $this->grid->getRaPort(), $this->grid->getRaPassword());
                $result = $raXML->call('admin_region_query', array('region_id' => $this->getUuid()));
            // Use API
            } else {
                $xml = @file_get_contents($this->grid->getOsProtocol() .'://'. $this->grid->getOsIp() .':'. $this->grid->getOsPort() .'/monitorstats/'. $this->getUuid());
                if($xml !== FALSE) {
                    $xml    = simplexml_load_string($xml);
                    $result = array('success' => TRUE);
                }
            }

            // Requesting XML went wrong?
            if($result === FALSE) {
                $this->getGrid()->setTimedOut(TRUE);
            }

            // Catch error returned by OpenSim
            if(isset($result['error'])) {
                throw new Exception($result['error'], 3);
            }

            // Set the online status
            if(isset($result['success'])) {
                $this->setOnlineStatus($result['success']);
            } else {
                $this->setOnlineStatus(FALSE);
            }

            // Updates the online status of the grid, when so far the status is offline
            // This means that if one region responds it is online, the grid is online, however just not all regions
            // Without this, when the last region to be checked is offline, the grid will respond it is offline eventhough
            // all previous regions can be online
            if(!$this->getGrid()->getOnlineStatus()) {
                $this->getGrid()->setOnlineStatus($this->getGrid()->getOnlineStatus());
            }

            // Additional actions when region is online
            if($this->getOnlineStatus()) {
                $this->activeUsers  = 0;
                $this->totalUsers   = 0;

                //MySQL database is configured?
                if($this->grid->getDbUrl() != '' && $this->grid->getDbName() != '' && $this->grid->getDbPassword() != '' && $this->grid->getDbPort() != '') {
                    $osdb = new \MysqliDb($this->grid->getDbUrl(), $this->grid->getDbUsername(), $this->grid->getDbPassword(), $this->grid->getDbName(), $this->grid->getDbPort());
                    // Get user's
                    $osdb->where('LastRegionID', $osdb->escape($this->getUuid()));
                    $results = $osdb->get('GridUser');

                    // Count active and total users
                    foreach($results as $result) {
                        if($result['Online'] == 'True') {
                            $this->activeUsers++;
                        }
                        $this->totalUsers++;
                    }
                // Load active users from XML
                } else {
                    // Loaded with remote admin, but no MySQL available?
                    if($raXML !== FALSE) {
                        $xml    = file_get_contents($this->grid->getOsProtocol() .'://'. $this->grid->getOsIp() .':'. $this->grid->getOsPort() .'/monitorstats/'. $this->getUuid());
                        $xml    = simplexml_load_string($xml);
                    }

                    if($xml !== FALSE) {
                        $this->activeUsers = (int) $xml->AgentCountMonitor;
                    }
                }
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