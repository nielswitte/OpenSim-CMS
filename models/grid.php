<?php
namespace Models;

if (EXEC != 1) {
    die('Invalid request');
}

require_once dirname(__FILE__) . '/simpleModel.php';

/**
 * This class provides information about the Grid
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 21th, 2014
 */
class Grid implements SimpleModel {
    private $id;
    private $name;
    private $osProtocol, $osIp, $osPort;
    private $raUrl, $raPort, $raPassword;
    private $dbUrl, $dbPort, $dbUsername, $dbPassword, $dbName;
    private $cachetime;
    private $regions = array();
    private $defaultRegionUuid;
    private $online = FALSE;

    /**
     * Constructs a new grid with the given ID
     *
     * @param integer $id
     * @param string $name - [Optional] The grid's name
     * @param string $osProtocol - [Optional] The protocol used by this grid for OpenSim [HTTP/HTTPS]
     * @param string $osIp - [Optional] The IP address of the grid used by OpenSim
     * @param integer $osPort - [Optional] The port to access the grid used by OpenSim
     */
    public function __construct($id, $name = '', $osProtocol = '', $osIp = '', $osPort = '') {
        $this->id           = $id;
        $this->name         = $name;
        $this->osProtocol   = $osProtocol;
        $this->osIp         = $osIp;
        $this->osPort       = $osPort;
    }

    /**
     * Updates the online status
     *
     * @param boolean $online
     */
    public function setOnlineStatus($online) {
        $this->online = $online;
    }

    /**
     * Gets the information about the grid from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $result = $db->get('grids', 1);

        // Found a result?
        if(isset($result[0])) {
            $this->name                 = $result[0]['name'];
            $this->osProtocol           = $result[0]['osProtocol'];
            $this->osIp                 = $result[0]['osIp'];
            $this->osPort               = $result[0]['osPort'];
            $this->raUrl                = $result[0]['raUrl'];
            $this->raPort               = $result[0]['raPort'];
            $this->raPassword           = $result[0]['raPassword'];
            $this->dbUrl                = $result[0]['dbUrl'];
            $this->dbPort               = $result[0]['dbPort'];
            $this->dbUsername           = $result[0]['dbUsername'];
            $this->dbPassword           = $result[0]['dbPassword'];
            $this->dbName               = $result[0]['dbName'];
            $this->cachetime            = $result[0]['cacheTime'];
            $this->defaultRegionUuid    = $result[0]['defaultRegionUuid'];

            // Add Grid's regions to list
            $db->where('gridId', $db->escape($this->getId()));
            $regions = $db->get('grid_regions');

            foreach($regions as $region) {
                $newRegion = new \Models\Region($region['uuid'], $this);
                $newRegion->getInfoFromDatabase();
                $newRegion->setName($region['name']);
                $this->addRegion($newRegion);
            }
        } else {
            throw new Exception("Grid ID does not exist", 1);
        }
    }

    /**
     * Saves the region to this grid's regions list
     *
     * @param \Models\Region $region
     */
    public function addRegion(\Models\Region $region) {
        $this->regions[$region->getUuid()] = $region;
    }

    /**
     * Returns an array with the grid's regions
     *
     * @return array
     */
    public function getRegions() {
        return $this->regions;
    }

    /**
     * Searches for a region by name
     *
     * @param string $name - Region name to search for
     * @return Region or boolean FALSE when not found
     */
    public function getRegionByName($name) {
        foreach($this->regions as $uuid => $region) {
            if($region->getName() == $name) {
                return $region;
            }
        }
        return FALSE;
    }

    /**
     * Returns the region matching the UUID or FALSE when not found
     *
     * @param string $uuid
     * @return \Models\Region or boolean FALSE when not found
     * @throws \Exception
     */
    public function getRegionByUuid($uuid) {
        if(!\Helper::isValidUuid($uuid)) {
            throw new \Exception("Invalid UUID provided", 1);
        }
        return isset($this->regions[$uuid]) ? $this->regions[$uuid] : FALSE;
    }

    /**
     * Returns the grid id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the grid name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the protocol used by OpenSim
     *
     * @return string [http or https]
     */
    public function getOsProtocol() {
        return $this->osProtocol;
    }

    /**
     * Returns the ip used by OpenSim
     *
     * @return string
     */
    public function getOsIp(){
        return $this->osIp;
    }

    /**
     * Returns the port used by OpenSim
     *
     * @return integer
     */
    public function getOSPort() {
        return $this->osPort;
    }

    /**
     * Returns the url used by OpenSim for remote admin
     *
     * @return string
     */
    public function getRaUrl() {
        return $this->raUrl;
    }

    /**
     * Returns the port used by OpenSim for remote admin
     *
     * @return integer
     */
    public function getRaPort(){
        return $this->raPort;
    }

    /**
     * Returns the password used by OpenSim for remote admin
     *
     * @return string
     */
    public function getRaPassword () {
        return $this->raPassword;
    }

    /**
     * Returns the url used by OpenSim for its MySQL database
     *
     * @return string
     */
    public function getDbUrl() {
        return $this->dbUrl;
    }

    /**
     * Returns the port used by OpenSim for its MySQL database
     *
     * @return integer
     */
    public function getDbPort() {
        return $this->dbPort;
    }

    /**
     * Returns the username used by OpenSim for its MySQL database
     *
     * @return string
     */
    public function getDbUsername() {
        return $this->dbUsername;
    }

    /**
     * Returns the password used by OpenSim for its MySQL database
     *
     * @return string
     */
    public function getDbPassword() {
        return $this->dbPassword;
    }
    /**
     * Returns the database name used by OpenSim for its MySQL database
     *
     * @return string
     */
    public function getDbName() {
        return $this->dbName;
    }
    /**
     * Returns the time cache is valid on the OpenSim server
     * Formatted as a string parseable by strtotime()
     *
     * @return string
     */
    public function getCacheTime() {
        return $this->cachetime;
    }

    /**
     * Returns the grid's status
     *
     * @return boolean
     */
    public function getOnlineStatus() {
        return $this->online;
    }

    /**
     * Returns the UUID of the default Grid region
     *
     * @return type
     */
    public function getDefaultRegionUuid() {
        return $this->defaultRegionUuid;
    }

    /**
     * Retuns the region that is default on this Grid
     *
     * @return \Models\Region
     */
    public function getDefaultRegion() {
        return $this->getRegionByUuid($this->getDefaultRegionUuid());
    }

    /**
     * Counts the total number of users in all regions on this server
     *
     * @return integer
     */
    public function getTotalUsers() {
        $total = 0;
        foreach($this->getRegions() as $region) {
            $total += $region->getTotalUsers();
        }
        return $total;
    }

    /**
     * Counts the total number of active users in all regions on this server
     *
     * @return integer
     */
    public function getActiveUsers() {
        $total = 0;
        foreach($this->getRegions() as $region) {
            $total += $region->getActiveUsers();
        }
        return $total;
    }
}
