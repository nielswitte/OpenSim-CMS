<?php
namespace Models;

if (EXEC != 1) {
    die('Invalid request');
}

require_once dirname(__FILE__) . '/simpleModel.php';

/**
 * This class represents a meeting room
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 25th, 2014
 */
class MeetingRoom implements simpleModel {
    private $id;
    private $region;
    private $description;

    /**
     * Constructs a new meeting room with the given id
     *
     * @param integer $id - The id of the room
     * @param \Models\Region $region - [Optional] Region this room is in
     * @param string $description - [Optional] Description of the room
     */
    public function __construct($id, $region = NULL, $description = '') {
        $this->id               = $id;
        $this->region           = $region;
        $this->description      = $description;
    }

    /**
     * Retrieves information from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $room = $db->get('meeting_rooms', 1);
        // Match found?
        if(isset($room[0])) {
            $grid               = new \Models\Grid($room[0]['gridId']);
            $grid->getInfoFromDatabase();
            $this->region       = $grid->getRegionByUuid($room[0]['regionUuid']);
            $this->description  = $room[0]['description'];
        } else {
            throw new \Exception("Meeting room not found", 1);
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
}
