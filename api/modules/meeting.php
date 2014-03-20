<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../../models/meeting.php';
require_once dirname(__FILE__) .'/../../controllers/meetingController.php';

/**
 * Implements the functions for meetings
 *
 * @author Niels Witte
 * @version 0.3
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
        $this->api->addRoute("/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/?$/",            "getMeetingsByDate",        $this, "GET",  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/calendar\/?$/",  "getMeetingsByDate",        $this, "GET",  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/meetings\/?$/",                                              "getMeetings",              $this, "GET",  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC
        $this->api->addRoute("/meetings\/(\d+)\/?$/",                                       "getMeetings",              $this, "GET",  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC starting at the given offset
        $this->api->addRoute("/meeting\/?$/",                                               "createMeeting",            $this, "POST", \AUTH::EXECUTE); //Create a new meeting
        $this->api->addRoute("/meeting\/(\d+)\/?$/",                                        "getMeetingById",           $this, "GET",  \Auth::READ);  // Select a specific meeting
        $this->api->addRoute("/meeting\/(\d+)\/agenda\/?$/",                                "getMeetingAgendaById",     $this, "GET",  \Auth::READ);  // Select a specific meeting and only get the agenda
        $this->api->addRoute("/meeting\/(\d+)\/?$/",                                        "updateMeetingById",        $this, "PUT",  \Auth::EXECUTE); // Update a specific meeting
        $this->api->addRoute("/meeting\/(\d+)\/minutes\/?$/",                               "saveMinutesByMeetingId",   $this, "POST", \Auth::WRITE); // Save meeting minutes
        $this->api->addRoute("/meeting\/(\d+)\/minutes\/?$/",                               "getMinutesByMeetingId",    $this, "GET",  \Auth::READ); // Get meeting minutes
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
            $meeting    = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room, $result['name']);
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
            $meeting    = new \Models\Meeting($result['meetingId'], $result['startDate'], $result['endDate'], $user, $room, $result['name']);
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
                    'title'         => $meeting->getName() .' (Room: '. $meeting->getRoom()->getId() .')',
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
        $meeting->getAgendaFromDabatase();
        $meeting->getCreator()->getInfoFromDatabase();
        $meeting->getRoom()->getInfoFromDatabase();
        $meeting->getParticipantsFromDatabase();
        $meeting->getDocumentsFromDabatase();

        return $this->getMeetingData($meeting, TRUE);
    }

    /**
     * Gets the meeting agenda for the given meeting
     *
     * @param array $args
     * @return array
     */
    public function getMeetingAgendaById($args) {
        $meeting = new \Models\Meeting($args[1]);
        $meeting->getInfoFromDatabase();
        $meeting->getAgendaFromDabatase();

        return $this->getMeetingAgendaData($meeting);
    }

    /**
     * Creates a new meeting
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function createMeeting($args) {
        $data           = FALSE;
        $meetingCtrl    = new \Controllers\MeetingController();
        $input          = \Helper::getInput(TRUE);

        if($meetingCtrl->validateParametersCreate($input)) {
            $data = $meetingCtrl->createMeeting($input);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'meetingId' => ($data !== FALSE ? $data : 0)
        );

        return $result;
    }

    /**
     * Updates the given meeting
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateMeetingById($args) {
        $data           = FALSE;
        $meeting        = new \Models\Meeting($args[1]);
        $meetingCtrl    = new \Controllers\MeetingController($meeting);
        $input          = \Helper::getInput(TRUE);

        if($meetingCtrl->validateParametersUpdate($input)) {
            $data = $meetingCtrl->updateMeeting($input);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }

    /**
     * Processes the chatlogs
     *
     * @param array $args
     * @return array
     */
    public function saveMinutesByMeetingId($args) {
        $data           = FALSE;
        $meeting        = new \Models\Meeting($args[1]);
        $meetingCtrl    = new \Controllers\MeetingController($meeting);
        $input          = \Helper::getInput(TRUE);

        // Convert UNIX to timestamp, which is used when the request is from the OpenSim Server
        foreach($input as $key => $row) {
            if(isset($row['timestamp']) && is_numeric($row['timestamp'])) {
                $input[$key]['message']   = urldecode($row['message']);
                $input[$key]['timestamp'] = date('Y-m-d H:i:s', $row['timestamp']);
            }
        }

        // Validate parameters and if valid save them
        if($meetingCtrl->validateParametersChat($input)) {
            $data = $meetingCtrl->saveChat($input);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }

    /**
     * Gets the minutes meetings
     *
     * @param array $args
     */
    public function getMinutesByMeetingId($args){
        $meeting  = new \Models\Meeting($args[1]);
        $meeting->getInfoFromDatabase();
        //$meeting->getRoom()->getInfoFromDatabase();
        $meeting->getAgendaFromDabatase();
        $meeting->getParticipantsFromDatabase();
        $meeting->getDocumentsFromDabatase();
        $meeting->getMinutesFromDatabase();
        $minutes  = $meeting->getMinutes()->getMinutes();

        // Process and format results
        $results  = array();
        $agendaId = 0;
        foreach($minutes as $minute) {
            // Get the agenda item for this minute, but only if changed
            if($agendaId != $minute['agendaId']) {
                $agendaItem = $meeting->getAgenda()->getAgendaItemById($minute['agendaId']);
            }

            $results[] = array(
                'id'         => $minute['id'],
                'timestamp'  => $minute['timestamp'],
                'agenda'     => array(
                    'agendaId'      => $agendaItem['id'],
                    'parentId'      => $agendaItem['parentId'],
                    'sort'          => $agendaItem['sort'],
                    'value'         => $agendaItem['value']
                ),
                'uuid'       => $minute['uuid'],
                'name'       => $minute['name'],
                'message'    => $minute['message'],
                'user'       => ($minute['user'] ? array(
                    // When a user is found
                    'id'        => $minute['user']->getId(),
                    'firstName' => $minute['user']->getFirstName(),
                    'lastName'  => $minute['user']->getLastName(),
                    'username'  => $minute['user']->getUsername()
                    )
                    // No user found
                    : ''
                )
            );
        }

        return $results;
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
            'name'      => $meeting->getName(),
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
        // Show detailed information?
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
            // Make a list of participants
            $participants = array();
            foreach($meeting->getParticipants()->getParticipants() as $user) {
                $participants[] = array(
                    'id'            => $user->getId(),
                    'username'      => $user->getUsername(),
                    'firstName'     => $user->getFirstName(),
                    'lastName'      => $user->getLastName(),
                    'email'         => $user->getEmail()
                );
            }
            $data['participants'] = $participants;
            $data['agenda']       = $meeting->getAgenda()->toString();
            // Make a list of documents
            $documents = array();
            foreach($meeting->getDocuments()->getDocuments() as $document) {
                $documents[] = array(
                    'id'                => $document->getId(),
                    'title'             => $document->getTitle(),
                    'type'              => $document->getType(),
                    'ownerId'           => $document->getOwnerId(),
                    'creationDate'      => $document->getCreationDate(),
                    'modificationDate'  => $document->getModificationDate()
                );
            }

            $data['documents']    = $documents;
        // Show minimal information
        } else {
            $data['roomId']     = $meeting->getRoom()->getId();
            // URL to full view
            $data['url']        = $meeting->getApiUrl();
        }

        return $data;
    }

    /**
     * Returns the agenda for the meeting as an json array
     *
     * @param \Models\Meeting $meeting
     * @return array
     */
    public function getMeetingAgendaData(\Models\Meeting $meeting) {
        return $meeting->getAgenda()->buildAgenda();
    }
}