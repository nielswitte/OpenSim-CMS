<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/meeting.php';
require_once dirname(__FILE__) .'/../controllers/meetingController.php';

/**
 * Implements the functions for meetings
 *
 * @author Niels Witte
 * @version 0.4
 * @date April 2nd, 2014
 * @since February 25th, 2014
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
        $this->api->addRoute("/^\/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/?$/",            'getMeetingsByDate',        $this, 'GET',  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/^\/meetings\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/calendar\/?$/",  'getMeetingsByDate',        $this, 'GET',  \Auth::READ);  // Get all meetings that start after the given date
        $this->api->addRoute("/^\/meetings\/?$/",                                              'getMeetings',              $this, 'GET',  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC
        $this->api->addRoute("/^\/meetings\/(\d+)\/?$/",                                       'getMeetings',              $this, 'GET',  \Auth::READ);  // Get list with 50 meetings ordered by startdate DESC starting at the given offset
        $this->api->addRoute("/^\/meeting\/?$/",                                               'createMeeting',            $this, 'POST', \AUTH::EXECUTE); //Create a new meeting
        $this->api->addRoute("/^\/meeting\/(\d+)\/?$/",                                        'getMeetingById',           $this, 'GET',  \Auth::READ);  // Select a specific meeting
        $this->api->addRoute("/^\/meeting\/(\d+)\/agenda\/?$/",                                'getMeetingAgendaById',     $this, 'GET',  \Auth::READ);  // Select a specific meeting and only get the agenda
        $this->api->addRoute("/^\/meeting\/(\d+)\/?$/",                                        'updateMeetingById',        $this, 'PUT',  \Auth::EXECUTE); // Update a specific meeting
        $this->api->addRoute("/^\/meeting\/(\d+)\/minutes\/?$/",                               'saveMinutesByMeetingId',   $this, 'POST', \Auth::WRITE); // Save meeting minutes
        $this->api->addRoute("/^\/meeting\/(\d+)\/minutes\/?$/",                               'getMinutesByMeetingId',    $this, 'GET',  \Auth::READ); // Get meeting minutes
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
        $db->where('u.id', 'm.userId');
        $db->orderBy('m.startDate', 'DESC');
        $results = $db->get('meetings m, users u', array($db->escape($args[1]), 50), '*, m.id as meetingId');

        // Process results
        $data           = array();
        foreach($results as $result) {
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
        // Get presentations past the given date
        $db->where('u.id', 'm.userId');
        $db->where('m.startDate', array('>=' => $db->escape($args[1])));
        $db->orderBy('m.startDate', 'DESC');
        $results        = $db->get('meetings m, users u', NULL, '*, m.id as meetingId');

        // Process results
        $data           = array();

        foreach($results as $result) {
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

        // Only one item? make it a sub multidemensional array
        if(!isset($input[0])) {
            $input = array($input);
        }

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
        $meeting->getAgendaFromDabatase();
        $meeting->getParticipantsFromDatabase();
        $meeting->getDocumentsFromDabatase();
        $meeting->getMinutesFromDatabase();
        $minutes = $meeting->getMinutes()->getMinutes();

        // Process and format results
        $results            = $this->getMeetingData($meeting, FALSE);
        $results['agenda']  = $meeting->getAgenda()->toString();

        // Format the Minutes
        $agendaId = 0;
        foreach($minutes as $minute) {
            // Get the agenda item for this minute, but only if changed
            if($agendaId != $minute['agendaId']) {
                $agendaItem = $meeting->getAgenda()->getAgendaItemById($minute['agendaId']);
            }

            $results['minutes'][] = array(
                'id'         => $minute['id'],
                'timestamp'  => $minute['timestamp'],
                'agenda'     => array(
                    'id'            => $agendaItem['id'],
                    'parentId'      => $agendaItem['parentId'],
                    'sort'          => $agendaItem['sort'],
                    'value'         => $agendaItem['value']
                ),
                'uuid'       => $minute['uuid'],
                'name'       => $minute['name'],
                'message'    => $minute['message'],
                // User match is found?
                'user'       => ($minute['user'] ? $this->api->getModule('user')->getUserData($minute['user'], FALSE) : '')
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
            'creator'   => $this->api->getModule('user')->getUserData($meeting->getCreator(), FALSE)
        );
        // Show detailed information?
        if($full) {
            $room = $meeting->getRoom();
            $grid = $room->getRegion()->getGrid();
            $data['room'] = array(
                'id'            => $room->getId(),
                'name'          => $room->getName(),
                'grid'          => array(
                    'id'        => $grid->getId(),
                    'name'      => $grid->getName(),
                    'openSim'   => array(
                        'protocol'  => $grid->getOsProtocol(),
                        'ip'        => $grid->getOsIp(),
                        'port'      => $grid->getOsPort()
                    ),
                ),
                'region'        => array(
                    'name'          => $room->getRegion()->getName(),
                    'uuid'          => $room->getRegion()->getUuid()
                ),
                'description'   => $room->getDescription(),
                'coordinates'   => array(
                    'x'             => $room->getX(),
                    'y'             => $room->getY(),
                    'z'             => $room->getZ()
                ),
            );
            // Make a list of participants
            $participants = array();
            foreach($meeting->getParticipants()->getParticipants() as $user) {
                $participants[] = $this->api->getModule('user')->getUserData($user, FALSE);
            }
            $data['participants'] = $participants;
            $data['agenda']       = $meeting->getAgenda()->toString();

            // Make a list of documents
            $documents = array();
            foreach($meeting->getDocuments()->getDocuments() as $document) {
                $documents[] = $this->api->getModule('document')->getDocumentData($document, FALSE);
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