<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for meetings
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 25th, 2014
 */
class Meeting extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/meetings\/?$/",                      "getMeetings",     $this, "GET",  TRUE);  // Get list with 50 meetings ordered by startdate DESC
        $this->api->addRoute("/meetings\/(\d+)\/?$/",               "getMeetings",     $this, "GET",  TRUE);  // Get list with 50 meetings ordered by startdate DESC starting at the given offset
        $this->api->addRoute("/meeting\/(\d+)\/?$/",                "getMeetingById",  $this, "GET",  TRUE);  // Select specific meeting
    }

    /**
     * Gets a list of meetings starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public function getMeetings($args) {
        $db             = \Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array($args[1], 50);
        $resutls        = $db->rawQuery("SELECT * FROM meetings ORDER BY startDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        $x              = $args[1];
        foreach($resutls as $result) {
            $x++;
            $user       = new \Models\User($result['userId']);
            $room       = new \Models\MeetingRoom($result['roomId']);
            $meeting    = new \Models\Meeting($result['id'], $result['startDate'], $result['endDate'], $user, $room);
            $data[$x]   = $this->getMeetingData($meeting, FALSE);
            $x++;
        }
        return $data;
    }

    /**
     * Gets the full meeting details for the given meeting
     *
     * @param array $args
     * @return array
     */
    public function getMeetingById($args) {
        $meeting    = new \Models\Meeting($args[1]);
        $meeting->getInfoFromDatabase();
        $meeting->getCreator()->getInfoFromDatabase();
        $meeting->getRoom()->getInfoFromDatabase();

        return $this->getMeetingData($meeting, TRUE);
    }

    /**
     * Formats the meeting data to a nice array
     *
     * @param \Models\Meeting $meeting
     * @param boolean $full - [Optional]
     * @return array
     */
    private function getMeetingData(\Models\Meeting $meeting, $full = TRUE) {
        $data       = array(
            'id'        => $meeting->getId(),
            'startDate' => $meeting->getStartDate(),
            'endDate'   => $meeting->getEndDate()
        );
        if($full) {
            $data['creator'] = array(
                'id'            => $meeting->getCreator()->getId(),
                'userName'      => $meeting->getCreator()->getUserName(),
                'firstName'     => $meeting->getCreator()->getFirstName(),
                'lastName'      => $meeting->getCreator()->getLastName(),
                'email'         => $meeting->getCreator()->getEmail()
            );
            $data['room'] = array(
                'id'            => $meeting->getRoom()->getId(),
                'grid'          => array(
                    'id'        => $meeting->getRoom()->getRegion()->getGrid()->getId(),
                    'name'      => $meeting->getRoom()->getRegion()->getGrid()->getName()
                ),
                'region'        => array(
                    'name'          => $meeting->getRoom()->getRegion()->getName(),
                    'uuid'          => $meeting->getRoom()->getRegion()->getUuid()
                ),
                'description'   => $meeting->getRoom()->getDescription()
            );
        } else {
            $data['creatorId'] = $meeting->getCreator()->getId();
            $data['roomId']    = $meeting->getRoom()->getId();
        }

        return $data;
    }
}