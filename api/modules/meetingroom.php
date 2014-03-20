<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../../models/meetingRoom.php';

/**
 * Implements the functions for rooms
 *
 * @author Niels Witte
 * @version 0.2
 * @since February 26th, 2014
 */
class MeetingRoom extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('meetingroom');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/grid\/(\d+)\/rooms\/?$/",                                    "getRoomsByGrid",       $this, "GET",  \Auth::READ);  // Get rooms for the given grid
        $this->api->addRoute("/grid\/(\d+)\/room\/(\d+)\/?$/",                              "getRoomById",          $this, "GET",  \Auth::READ);  // Get the given room on the grid
        $this->api->addRoute("/grid\/(\d+)\/region\/([a-z0-9-]{36})\/rooms\/?$/",           "getRoomsByGridRegion", $this, "GET",  \Auth::READ);  // Get the given room on the grid
    }

    /**
     * Returns a list with all rooms on the given Grid
     *
     * @param array $args
     * @return array
     */
    public function getRoomsByGrid($args) {
        $db         = \Helper::getDB();
        $db->join('grid_regions r', 'mr.regionUuid = r.uuid AND r.gridId = mr.gridId', 'LEFT');
        $db->where("mr.id", $db->escape($args[1]));
        $columns    = array(
            'mr.id as roomId',
            'mr.regionUuid as regionUuid',
            'mr.gridId as gridId',
            'mr.description as description',
            'mr.x as x',
            'mr.y as y',
            'mr.z as z',
            'r.name as regionName'
        );
        $results    = $db->get('meeting_rooms mr', NULL, $columns);

        // Create the grid
        $grid       = new \Models\Grid($results[0]['gridId']);

        // Process results
        $data       = array();
        foreach($results as $result) {
            // Only set the region once, reuse it for other rooms in the same region
            $region = $grid->getRegionByUuid($result['regionUuid']);
            if($region == FALSE) {
                $region = new \Models\Region($result['regionUuid'], $grid);
                $region->setName($result['regionName']);
                $grid->addRegion($region);
            }
            $room       = new \Models\MeetingRoom($result['roomId'], $region, $result['description'], $result['x'], $result['y'], $result['z']);
            $data[]     = $this->getRoomData($room, FALSE);
        }

        return $data;
    }

    /**
     * Gets the rooms for the given grid region
     *
     * @param array $args
     * @return array
     */
    public function getRoomsByGridRegion($args) {
        $db      = \Helper::getDB();
        $db->join('grid_regions r', 'mr.regionUuid = r.uuid AND r.gridId = mr.gridId', 'LEFT');
        $db->join('grids g', 'g.id = mr.gridId', 'LEFT');
        $db->where("mr.id", $db->escape($args[1]));
        $db->where("mr.regionUuid", $db->escape($args[2]));
        $columns = array(
            'mr.id as roomId',
            'mr.regionUuid as regionUuid',
            'mr.gridId as gridId',
            'mr.description as description',
            'mr.x as x',
            'mr.y as y',
            'mr.z as z',
            'r.name as regionName',
            'g.name as gridName',
            'g.osProtocol as osProtocol',
            'g.osIp as osIp',
            'g.osPort as osPort'
        );
        $results    = $db->get('meeting_rooms mr', NULL, $columns);

        $data       = array();
        // Rooms found?
        if(isset($results[0])) {
            // Create the grid
            $grid       = new \Models\Grid($results[0]['gridId'], $results[0]['gridName'], $results[0]['osProtocol'], $results[0]['osIp'], $results[0]['osPort']);

            // Create region and add it to grid
            $region     = new \Models\Region($args[2], $grid);
            $region->setName($results[0]['regionName']);
            $grid->addRegion($region);

            // Process results
            foreach($results as $result) {
                // Only set the region once, reuse it for other rooms in the same region
                $room       = new \Models\MeetingRoom($result['roomId'], $region, $result['description'], $result['x'], $result['y'], $result['z']);
                $data[]     = $this->getRoomData($room, TRUE);
            }
        }

        return $data;
    }

    /**
     * Gets the information for the given room
     *
     * @param array $args
     * @return array
     */
    public function getRoomById($args) {
        $db      = \Helper::getDB();
        $db->join('grid_regions r', 'mr.regionUuid = r.uuid AND r.gridId = mr.gridId', 'LEFT');
        $db->join('grids g', 'g.id = mr.gridId', 'LEFT');
        $db->where("mr.id", $db->escape($args[1]));
        $columns = array(
            'mr.id as roomId',
            'mr.regionUuid as regionUuid',
            'mr.gridId as gridId',
            'mr.description as description',
            'mr.x as x',
            'mr.y as y',
            'mr.z as z',
            'r.name as regionName',
            'g.name as gridName',
            'g.osProtocol as osProtocol',
            'g.osIp as osIp',
            'g.osPort as osPort'
        );
        $results    = $db->get('meeting_rooms mr', 1, $columns);
        $data       = array();

        // Room found?
        if(isset($results[0])) {
            // Create the grid
            $grid       = new \Models\Grid($results[0]['gridId'], $results[0]['gridName'], $results[0]['osProtocol'], $results[0]['osIp'], $results[0]['osPort']);

            // Create region and add it to Grid
            $region     = new \Models\Region($results[0]['regionUuid'], $grid);
            $region->setName($results[0]['regionName']);
            $grid->addRegion($region);

            // Create the room
            $room       = new \Models\MeetingRoom($results[0]['roomId'], $region, $results[0]['description'], $results[0]['x'], $results[0]['y'], $results[0]['z']);

            // Process results
            $data       = $this->getRoomData($room, TRUE);
        }

        return $data;
    }

    /**
     * Formats the result data
     *
     * @param \Models\Room $room
     * @param boolean $full - [Optional] Whether or not to show specific information
     * @return array
     */
    public function getRoomData(\Models\MeetingRoom $room, $full = TRUE) {
        $data['id']             = $room->getId();
        if($full) {
            $data['grid']       = array(
                'id'    => $room->getRegion()->getGrid()->getId(),
                'name'  => $room->getRegion()->getGrid()->getName(),
                'openSim'       => array(
                    'protocol'      => $room->getRegion()->getGrid()->getOsProtocol(),
                    'ip'            => $room->getRegion()->getGrid()->getOsIp(),
                    'port'          => $room->getRegion()->getGrid()->getOSPort()
                )
            );
        } else {
            $data['gridId']         = $room->getRegion()->getGrid()->getId();
        }
        $data['region']         = array(
                'uuid'  => $room->getRegion()->getUuid(),
                'name'  => $room->getRegion()->getName()
            );
        $data['description']   = $room->getDescription();
        $data['coordinates']   = array(
                'x'     => $room->getX(),
                'y'     => $room->getY(),
                'z'     => $room->getZ()
            );

        return $data;
    }
}
