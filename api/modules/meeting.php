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
        $this->setName('meeting');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/?$/",            "getMeetingsByDate",   $this, "GET",  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/calendar\/?$/",  "getMeetingsByDate",   $this, "GET",  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/meetings\/?$/",                                              "getMeetings",         $this, "GET",  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC
        $this->api->addRoute("/meetings\/(\d+)\/?$/",                                       "getMeetings",         $this, "GET",  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC starting at the given offset
        $this->api->addRoute("/meeting\/(\d+)\/?$/",                                        "getMeetingById",      $this, "GET",  \Auth::READ);  // Select specific meeting
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
        $params         = array($db->escape($args[1]), 50);
        $resutls        = $db->rawQuery("SELECT *, m.id as meetingId FROM meetings m, users u WHERE u.id = m.userId ORDER BY m.startDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $user       = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
            $room       = new \Models\MeetingRoom($result['roomId']);
            $meeting    = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room);
            $data[]     = $this->getMeetingData($meeting, FALSE);
        }
        return $data;
    }

    /**
     * Gets all meetings after the given date
     *
     * @param array $args
     * @return array
     */
    public function getMeetingsByDate($args) {
        $db             = \Helper::getDB();
        // Get 50 presentations from the given offset
        $params         = array($db->escape($args[1]));
        $resutls        = $db->rawQuery("SELECT *, m.id as meetingId FROM meetings m, users u WHERE u.id = m.userId AND m.startDate >= ? ORDER BY m.startDate DESC", $params);
        // Process results
        $data           = array();

        foreach($resutls as $result) {
            $user       = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
            $room       = new \Models\MeetingRoom($result['roomId']);
            $meeting    = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room);
            if(strpos($args[0], 'calendar') !== FALSE) {
                $startTimestamp = strtotime($meeting->getStartDate());
                $endTimestamp   = strtotime($meeting->getEndDate());

                // use the format for JSON Event Objects (@source: https://github.com/Serhioromano/bootstrap-calendar#feed-url )
                $data[]   = array(
                    'id'            => $meeting->getId(),
                    'start'         => $startTimestamp * 1000,
                    'end'           => $endTimestamp * 1000,
                    'url'           => $meeting->getApiUrl(),
                    // Meeting has started but not ended (in progress) ? => event-success
                    // Meeting has ended ? => event-default
                    // Meeting still has to start => event-info
                    'class'         => ($startTimestamp < time() && $endTimestamp > time() ? 'event-success' : ($startTimestamp > time() ? 'event-info' : 'event-default')),
                    'title'         => 'Room: '. $meeting->getRoom()->getId(),
                    'description'   => 'Reservation made by: '. $meeting->getCreator()->getUsername()
                );
            } else {
                $data[] = $this->getMeetingData($meeting, FALSE);
            }
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
        $meeting = new \Models\Meeting($args[1]);
        $meeting->getInfoFromDatabase();
        $meeting->getCreator()->getInfoFromDatabase();
        $meeting->getRoom()->getInfoFromDatabase();
        $meeting->getParticipantsFromDatabase();

        return $this->getMeetingData($meeting, TRUE);
    }

    /**
     * Formats the meeting data to a nice array
     *
     * @param \Models\Meeting $meeting
     * @param boolean $full - [Optional]
     * @return array
     */
    public function getMeetingData(\Models\Meeting $meeting, $full = TRUE) {
        $data       = array(
            'id'        => $meeting->getId(),
            'startDate' => $meeting->getStartDate(),
            'endDate'   => $meeting->getEndDate(),
            'creator'   => array(
                'id'            => $meeting->getCreator()->getId(),
                'username'      => $meeting->getCreator()->getUsername(),
                'firstName'     => $meeting->getCreator()->getFirstName(),
                'lastName'      => $meeting->getCreator()->getLastName(),
                'email'         => $meeting->getCreator()->getEmail()
                )
            );
        if($full) {
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

            $i = 1;
            $participants = array();
            foreach($meeting->getParticipants()->getParticipants() as $user) {
                $participants[$i] = array(
                    'id'            => $user->getId(),
                    'username'      => $user->getUsername(),
                    'firstName'     => $user->getFirstName(),
                    'lastName'      => $user->getLastName(),
                    'email'         => $user->getEmail()
                );
                $i++;
            }
            $data['participants'] = $participants;
        } else {
            $data['roomId']     = $meeting->getRoom()->getId();
            // URL to full view
            $data['url']        = $meeting->getApiUrl();
        }

        return $data;
    }
}