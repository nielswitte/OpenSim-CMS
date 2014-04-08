<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) . '/simpleModel.php';

/**
 * This class provides information about the Grid
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 3rd, 2014
 * @since February 21th, 2014
 */
class Grid implements SimpleModel {
    /**
     * The ID of this grid
     * @var integer
     */
    private $id;
    /**
     * A recognizable name for this grid
     * @var string
     */
    private $name;
    /**
     * The protocol used by the server (often HTTP or HTTPS)
     * @var string
     */
    private $osProtocol;
    /**
     * The IP or URL of the server
     * @var string
     */
    private $osIp;
    /**
     * The port used by OpenSim
     * @var integer
     */
    private $osPort;
    /**
     * The Remote Admin URL for OpenSim
     * @var string
     */
    private $raUrl;
    /**
     * The port used by Remote Admin
     * @var integer
     */
    private $raPort;
    /**
     * The password required for Remote Admin
     * @var string
     */
    private $raPassword;
    /**
     * The MySQL database URL for OpenSim
     * @var string
     */
    private $dbUrl;
    /**
     * The port used by MySQL
     * @var integer
     */
    private $dbPort;
    /**
     * The OpenSim MySQL username
     * @var string
     */
    private $dbUsername;
    /**
     * The OpenSim MySQL password
     * @var string
     */
    private $dbPassword;
    /**
     * The OpenSim MySQL database name
     * @var string
     */
    private $dbName;
    /**
     * The time cache remains valid in OpenSim
     * Can be found in: "FlotsamCache.ini" use the value for: "FileCacheTimeout"
     * Value needs to be parsable by strtotime, so i.e. "48 hours"
     * @var string
     */
    private $cachetime;
    /**
     * List with regions attached to this grid
     * @var array
     */
    private $regions = array();
    /**
     * The UUID of the default region
     * @var string
     */
    private $defaultRegionUuid;
    /**
     * Boolean to save the online status of the grid
     * @var boolean
     */
    private $online = FALSE;
    /**
     * Boolean to store if the grid timedout by performing a remote query (database or remote admin)
     * @var boolean
     */
    private $timedOut = FALSE;

    /**
     * Constructs a new grid with the given ID
     *
     * @param integer $id
     * @param string $name - [Optional] The grid's name
     * @param string $osProtocol - [Optional] The protocol used by this grid for OpenSim [HTTP/HTTPS]
     * @param string $osIp - [Optional] The IP address of the grid used by OpenSim
     * @param integer $osPort - [Optional] The port to access the grid used by OpenSim
     */
    public function __construct($id, $name = '', $osProtocol = 'http', $osIp = '127.0.0.1', $osPort = '9000') {
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
     * @param boolean $full - [Optional] set to FALSE when you do not want to load all Region data
     * @throws \Exception
     */
    public function getInfoFromDatabase($full = TRUE) {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $result = $db->getOne('grids');

        // Found a result?
        if($result) {
            $this->name                 = $result['name'];
            $this->osProtocol           = $result['osProtocol'];
            $this->osIp                 = $result['osIp'];
            $this->osPort               = $result['osPort'];
            $this->raUrl                = $result['raUrl'];
            $this->raPort               = $result['raPort'];
            $this->raPassword           = $result['raPassword'];
            $this->dbUrl                = $result['dbUrl'];
            $this->dbPort               = $result['dbPort'];
            $this->dbUsername           = $result['dbUsername'];
            $this->dbPassword           = $result['dbPassword'];
            $this->dbName               = $result['dbName'];
            $this->cachetime            = $result['cacheTime'];
            $this->defaultRegionUuid    = $result['defaultRegionUuid'];

            // Add Grid's regions to list
            $db->where('gridId', $db->escape($this->getId()));
            $regions = $db->get('grid_regions');

            foreach($regions as $region) {
                $newRegion = new \Models\Region($region['uuid'], $this);
                // Get additional data?
                if($full) {
                    $newRegion->getInfoFromDatabase();
                }
                $newRegion->setName($region['name']);
                $this->addRegion($newRegion);
            }
        } else {
            throw new \Exception('Grid ID does not exist', 1);
        }
    }

    /**
     * Did one of the remote access requests time out on this server,
     * set this to TRUE to prevent more timeouts during this session
     *
     * @param boolean $timedOut
     */
    public function setTimedOut($timedOut){
        $this->timedOut = $timedOut;
    }

    /**
     * Returns TRUE when the server timedout on a request
     *
     * @return boolean
     */
    public function getTimedOut() {
        return $this->timedOut;
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
            throw new \Exception('Invalid UUID provided', 1);
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
    public function getOsPort() {
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
            if($region->getTotalUsers() < 0) {
                $users = 0;
            } else {
                $users = $region->getTotalUsers();
            }
            $total += $users;
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
            if($region->getTotalUsers() < 0) {
                $users = 0;
            } else {
                $users = $region->getActiveUsers();
            }

            $total += $users;
        }
        return $total;
    }
}
