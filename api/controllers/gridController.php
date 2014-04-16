<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the grid controller
 *
 * @author Niels Witte
 * @version 0.2
 * @data April 16th, 2014
 * @since April 14th, 2014
 */
class GridController {
    private $grid;

    /**
     * Constructs a new controller for the given Grid
     *
     * @param \Models\Grid $grid
     */
    public function __construct(\Models\Grid $grid) {
        $this->grid = $grid;
    }

    /**
     * Attempts to load grid information from the database of OpenSim
     *
     * @return integer $count - The number of Regions updated or boolean FALSE when no regions found
     * @throws \Exception
     */
    public function loadRegionDataFromOpenSim() {
        if($this->grid->getDbUrl()) {
            $osdb       = new \MysqliDb($this->grid->getDbUrl(), $this->grid->getDbUsername(), $this->grid->getDbPassword(), $this->grid->getDbName(), $this->grid->getDbPort());
            $osdb->join('assets a', "a.name = CONCAT('terrainImage_', t.regionUUID)", 'LEFT');
            $regions    = $osdb->get('terrain t', NULL, 'DISTINCT t.regionUUID AS uuid, a.description AS name');
            $db         = \Helper::getDB();
            $count      = FALSE;
            // Process all regions
            foreach($regions as $region) {
                // Database data
                $data['gridId'] = $db->escape($this->grid->getId());
                $data['name']   = $db->escape($region['name']);
                $data['uuid']   = $db->escape($region['uuid']);
                // Existing region
                $gridRegion = $this->grid->getRegionByUuid($region['uuid']);
                if($gridRegion !== FALSE) {
                    $db->where('uuid', $db->escape($region['uuid']));
                    $db->where('gridId', $db->escape($this->grid->getId()));
                    $result = $db->update('grid_regions', $data);
                // New region
                } else {
                    $result = $db->insert('grid_regions', $data);
                }

                // First region?
                if($count !== FALSE && $result !== FALSE) {
                    $count++;
                } else {
                    // Update successful or failed?
                    $count = $result !== FALSE ? 1 : 0;
                }
            }
            return $count;
        } else {
            throw new \Exception('Can not connect to the database of the selected Grid.', 1);
        }
    }

    /**
     * Retrieves the XML grid info from the OpenSim Grid
     * Updates the Grid's name to the actual name
     *
     * @return boolean
     */
    public function loadGridDataFromOpenSim() {
        $result = FALSE;
        $xml    = file_get_contents($this->grid->getOsProtocol() .'://'. $this->grid->getOsIp() .':'. $this->grid->getOsPort() .'/get_grid_info');
        if($xml !== FALSE) {
            $xml    = simplexml_load_string($xml);
            $db     = \Helper::getDB();

            $data   = array(
                'name'  => $db->escape($xml->gridname)
            );

            $db->where('id', $db->escape($this->grid->getId()));
            $db->update('grids', $data);
            $result = TRUE;
        }

        return $result;
    }
}
