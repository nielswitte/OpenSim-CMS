<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class represents a meeting room
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since February 25th, 2014
 */
class MeetingRoom implements simpleModel {
    private $id;
    private $region;
    private $name;
    private $description;
    private $x, $y, $z;

    /**
     * Constructs a new meeting room with the given id
     *
     * @param integer $id - The id of the room
     * @param \Models\Region $region - [Optional] Region this room is in
     * @param string $name - [Optional] The name of the room
     * @param string $description - [Optional] Description of the room
     * @param float $x - [Optional] The X-coordinate of the room
     * @param float $y - [Optional] The Y-coordinate of the room
     * @param float $z - [Optional] The Z-coordinate of the room
     */
    public function __construct($id, $region = NULL, $name = '', $description = '', $x = 0, $y = 0, $z = 0) {
        $this->id               = $id;
        $this->region           = $region;
        $this->name             = $name;
        $this->description      = $description;
        $this->x                = $x;
        $this->y                = $y;
        $this->z                = $z;
    }

    /**
     * Retrieves information from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $room = $db->getOne('meeting_rooms');
        // Match found?
        if($room) {
            $grid               = new \Models\Grid($room['gridId']);
            $grid->getInfoFromDatabase(FALSE);
            $this->region       = $grid->getRegionByUuid($room['regionUuid']);
            $this->name         = $room['name'];
            $this->description  = $room['description'];
            $this->x            = $room['x'];
            $this->y            = $room['y'];
            $this->z            = $room['z'];
        } else {
            throw new \Exception('Meeting room not found', 1);
        }
    }

    /**
     * Returns the meeting room id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the name of this room
     *
     * @return string
     */
    public function getName() {
       return $this->name;
    }

    /**
     * Returns the description of the room
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns the region the room is in
     *
     * @return \Models\Region
     */
    public function getRegion(){
        return $this->region;
    }

    /**
     * Returns the X-coordinate of the room
     *
     * @return float
     */
    public function getX() {
        return $this->x;
    }

    /**
     * Returns the X-coordinate of the room
     *
     * @return float
     */
    public function getY() {
        return $this->y;
    }

    /**
     * Returns the X-coordinate of the room
     *
     * @return float
     */
    public function getZ() {
        return $this->z;
    }
}
