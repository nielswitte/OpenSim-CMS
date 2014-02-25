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
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $meeting = $db->get('meetings', 1);
        // Meeting found?
        if(isset($meeting[0])) {
            $this->room         = new \Models\MeetingRoom($meeting[0]['roomId']);
            $this->creator      = new \Models\User($meeting['0']['userId']);
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
        $db             = \Helper::getDB();
        $params         = array($db->escape($this->getId()));
        $results        = $db->rawQuery('SELECT u.* FROM meeting_participants mp, users u WHERE mp.meetingId = ? AND mp.userId = u.id ORDER BY u.lastName ASC, u.firstName ASC', $params);

        // Create a new participants list and set it to this meeting
        $participants   = new \Models\MeetingParticipants($this);
        $this->setParticipants($participants);

        // Get the users on the list
        foreach($results as $result) {
            $participant    = new \Models\User($result['id'], $result['userName'], $result['email'], $result['firstName'], $result['lastName']);
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
}