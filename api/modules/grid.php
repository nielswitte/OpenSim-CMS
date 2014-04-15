<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/grid.php';
require_once dirname(__FILE__) .'/../controllers/gridController.php';
require_once dirname(__FILE__) .'/../models/region.php';
require_once dirname(__FILE__) .'/../controllers/regionController.php';


/**
 * Implements the functions called on the Grid
 *
 * @author Niels Witte
 * @version 0.3s
 * @date April 14th, 2014
 * @since February 24th, 2014
 */
class Grid extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('grid');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/^\/grids\/?$/",                                       'getGrids',             $this, 'GET',  \Auth::READ);  // Get a list with grids
        $this->api->addRoute("/^\/grid\/(\d+)\/?$/",                                 'getGridById',          $this, 'GET',  \Auth::READ);  // Get grid information by ID
        $this->api->addRoute("/^\/grid\/(\d+)\/regions\/?$/",                        'loadRegionsFromGrid',  $this, 'POST', \Auth::EXECUTE);  // Attempts to automatically get regions from the given grid
        $this->api->addRoute("/^\/grid\/(\d+)\/region\/([a-z0-9-]{36})\/?$/",        'getRegionByUuid',      $this, 'GET',  \Auth::READ);  // Get information about the given region
        $this->api->addRoute("/^\/grid\/(\d+)\/region\/([a-z0-9-]{36})\/image\/?$/", 'getRegionImageByUuid', $this, 'GET',  \Auth::READ);  // Get the map of the region
    }

    /**
     * Gets a list of grids
     *
     * @param array $args
     * @return array
     */
    public function getGrids($args) {
        $db = \Helper::getDB();
        $db->orderBy('LOWER(name)', 'asc');
        $grids  = $db->get('grids');
        // Process al grids
        $data   = array();
        foreach($grids as $gridId) {
            $grid = new \Models\Grid($gridId['id']);
            $grid->getInfoFromDatabase();
            $data[] = $this->getGridData($grid);
        }
        return $data;
    }

    /**
     * Gets information about a grid by its ID
     *
     * @param array $args
     * @return array
     */
    public function getGridById($args) {
        $grid       = new \Models\Grid($args[1]);
        $grid->getInfoFromDatabase();

        return $this->getGridData($grid);
    }

    /**
     * Formats the grid data
     *
     * @param \Models\Grid $grid
     * @return array
     */
    public function getGridData(\Models\Grid $grid) {
        $data['isOnline']           = $grid->getOnlineStatus() ? 1 : 0;
        $data['id']                 = $grid->getId();
        $data['name']               = $grid->getName();

        // Get information about the number of users
        if($grid->getOnlineStatus() !== FALSE) {
            $data['totalUsers']     = $grid->getTotalUsers();
            $data['activeUsers']    = $grid->getActiveUsers();
        }
        // OpenSim info
        $data['openSim'] = array(
            'protocol'              => $grid->getOsProtocol(),
            'ip'                    => $grid->getOsIp(),
            'port'                  => $grid->getOsPort()
        );
        // Remote Admin info
        $data['remoteAdmin'] = array(
            'url'                   => $grid->getRaUrl(),
            'port'                  => $grid->getRaPort()
        );
        // Regions
        $data['cacheTime']          = $grid->getCacheTime();
        $data['defaultRegionUuid']  = $grid->getDefaultRegionUuid();
        $data['regionCount']        = count($grid->getRegions());
        foreach($grid->getRegions() as $region) {
            $data['regions'][] = $this->getRegionData($region);
        }

        return $data;
    }

    /**
     * Formats the region data
     *
     * @param \Models\Region $region
     * @return array
     */
    public function getRegionData(\Models\Region $region) {
        $data['uuid']           = $region->getUuid();
        $data['name']           = $region->getName();
        $data['image']          = $region->getApiUrl() .'image/';
        $data['serverStatus']   = $region->getOnlineStatus() ? 1 : 0;

        // Additional information
        if($region->getOnlineStatus() !== FALSE && $region->getTotalUsers() >= 0) {
            $data['totalUsers']     = $region->getTotalUsers();
            $data['activeUsers']    = $region->getActiveUsers();
        }

        return $data;
    }

    /**
     * Gets information about the region
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getRegionByUuid($args) {
        $grid       = new \Models\Grid($args[1]);
        $grid->getInfoFromDatabase();
        $region     = $grid->getRegionByUuid($args[2]);
        $data       = '';
        if($region !== FALSE) {
            $data = $this->getRegionData($region);
        } else {
            throw new \Exception('Region not found', 1);
        }

        return $data;
    }

    /**
     * Shows the region image map as JPEG
     *
     * @param array $args
     * @throws \Exception
     */
    public function getRegionImageByUuid($args) {
        if(!\Helper::isValidUuid($args[2])) {
            throw new \Exception('Invalid UUID used', 1);
        } else {
            $grid       = new \Models\Grid($args[1]);
            $grid->getInfoFromDatabase();
            if($grid->getRegionByUuid($args[2]) !== FALSE) {
                header('Content-Type: image/jpeg');
                echo file_get_contents($grid->getOsProtocol() .'://'. $grid->getOsIp() .':'. $grid->getOsPort() .'/index.php?method=regionImage'. str_replace('-', '', $args[2]));
            } else {
                throw new \Exception('UUID isn\'t a region on this server', 2);
            }
        }
    }

    public function loadRegionsFromGrid($args) {
        $gridId     = $args[1];
        $grid       = new \Models\Grid($gridId);
        $grid->getInfoFromDatabase(FALSE);
        $gridCtrl   = new \Controllers\GridController($grid);
        $data       = $gridCtrl->loadRegionDataFromOpenSim();

        // Format the result
        $result = array(
            'success'           => ($data !== FALSE ? TRUE : FALSE),
            'regionsUpdated'    => ($data !== FALSE ? $data : 0)
        );

        return $result;
    }
}