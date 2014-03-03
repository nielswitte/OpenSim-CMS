<?php
namespace Models;

if (EXEC != 1) {
    die('Invalid request');
}

require_once dirname(__FILE__) . '/simpleModel.php';
require_once dirname(__FILE__) . '/meetingParticipants.php';
/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 25th, 2014
 */
class Meeting implements simpleModel {
    private $id;
    private $room;
    private $creator;
    private $startDate;
    private $endDate;
    private $participants;

    /**
     * Constructs a new meeting with the given ID
     *
     * @param integer $id
     * @param timestamp $startDate - [Optional]
     * @param timestamp $endDate - [Optional]
     * @param \Models\User $creator - [Optional]
     * @param \Models\MeetingRoom $room - [Optional]
     */
    public function __construct($id, $startDate = '0000-00-00 00:00:00', $endDate = '0000-00-00 00:00:00', \Models\User $creator = NULL, \Models\MeetingRoom $room = NULL) {
        $this->id           = $id;
        $this->creator      = $creator;
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
        $this->room         = $room;
    }

    /**
     * Retrieves all information from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db         = \Helper::getDB();
        $db->join('users u', 'm.userId = u.id', 'LEFT');
        $db->where('m.id', $db->escape($this->getId()));
        $meeting    = $db->get('meetings m', 1);

        // Meeting found?
        if(isset($meeting[0])) {
            $this->room         = new \Models\MeetingRoom($meeting[0]['roomId']);
            $this->creator      = new \Models\User($meeting['0']['userId'], $meeting['0']['username'], $meeting['0']['email'], $meeting['0']['firstName'], $meeting['0']['lastName']);
            $this->startDate    = $meeting[0]['startDate'];
            $this->endDate      = $meeting[0]['endDate'];
        } else {
            throw new Exception("Meeting not found", 1);
        }
    }

    /**
     * Gets the participants for this meeting from the database and adds them to the list
     */
    public function getParticipantsFromDatabase() {
        $db         = \Helper::getDB();
        $db->join('users u', 'm.userId = u.id', 'LEFT');
        $db->where('m.meetingId', $db->escape($this->getId()));
        $db->orderBy('LOWER(u.lastName)', 'ASC');
        $db->orderBy('LOWER(u.firstName)', 'ASC');
        $results    = $db->get('meeting_participants m');

        // Create a new participants list and set it to this meeting
        $participants   = new \Models\MeetingParticipants($this);
        $this->setParticipants($participants);

        // Get the users on the list
        foreach($results as $result) {
            $participant    = new \Models\User($result['id'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
            $participants->addParticipant($participant);
        }
    }

    /**
     * Returns the meeting ID
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the meeting room
     *
     * @return \Models\MeetingRoom
     */
    public function getRoom() {
        return $this->room;
    }

    /**
     * Returns the creator of this meeting
     *
     * @return \Models\User
     */
    public function getCreator() {
        return $this->creator;
    }

    /**
     * Returns the start timestamp of the meeting
     *
     * @return timestamp (yyyy-mm-dd hh:mm:ss)
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Returns the end timestamp of the meeting
     *
     * @return timestamp (yyyy-mm-dd hh:mm:ss)
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Sets the list with participants
     *
     * @param \Models\MeetingParticipants $participants
     */
    public function setParticipants(\Models\MeetingParticipants $participants) {
        $this->participants = $participants;
    }

    /**
     * Adds the givne user to the list with participants
     *
     * @param \Models\User $user
     */
    public function addParticipant(\Models\User $user) {
        $this->getParticipants()->addParticipant($user);
    }

    /**
     * Returns the MeetingParticipants instance that contains the list with participants
     *
     * @return \Models\MeetingParticipants
     */
    public function getParticipants(){
        return $this->participants;
    }

    /**
     * Returns the API url to the full meeting information
     *
     * @return string
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/meeting/'. $this->getId() .'/';
    }
}