<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) . '/simpleModel.php';
require_once dirname(__FILE__) . '/meetingParticipants.php';
require_once dirname(__FILE__) . '/meetingAgenda.php';
require_once dirname(__FILE__) . '/meetingDocuments.php';
require_once dirname(__FILE__) . '/meetingMinutes.php';
/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.4a
 * @date April 10th, 2014
 * @since February 25th, 2014
 */
class Meeting implements simpleModel {
    private $id;
    private $name;
    private $room;
    private $creator;
    private $startDate;
    private $endDate;
    private $participants;
    private $agenda;
    private $documents;
    private $minutes;

    /**
     * Constructs a new meeting with the given ID
     *
     * @param integer $id
     * @param timestamp $startDate - [Optional]
     * @param timestamp $endDate - [Optional]
     * @param \Models\User $creator - [Optional]
     * @param \Models\MeetingRoom $room - [Optional]
     * @param string $name - [Optional]
     * @param \Models\MeetingAgenda $agenda - [Optional]
     * @param \Models\MeetingDocuments $documents - [Optional]
     * @param \Models\MeetingMinutes $minutes - [Optional]
     */
    public function __construct($id, $startDate = '0000-00-00 00:00:00', $endDate = '0000-00-00 00:00:00', \Models\User $creator = NULL, \Models\MeetingRoom $room = NULL, $name = '', \Models\MeetingAgenda $agenda = NULL, \Models\MeetingDocuments $documents = NULL, \Models\MeetingMinutes $minutes = NULL) {
        $this->id           = $id;
        $this->creator      = $creator;
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
        $this->room         = $room;
        $this->name         = $name;
        $this->agenda       = $agenda;
        $this->documents    = $documents;
        $this->minutes      = $minutes;
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
        $meeting    = $db->getOne('meetings m');

        // Meeting found?
        if($meeting) {
            $this->room         = new \Models\MeetingRoom($meeting['roomId']);
            $this->creator      = new \Models\User($meeting['userId'], $meeting['username'], $meeting['email'], $meeting['firstName'], $meeting['lastName'], $meeting['lastLogin']);
            $this->startDate    = $meeting['startDate'];
            $this->endDate      = $meeting['endDate'];
            $this->name         = $meeting['name'];
        } else {
            throw new \Exception('Meeting not found', 1);
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
            $participant    = new \Models\User($result['id'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $participants->addParticipant($participant);
        }
    }

    /**
     * Gets the agenda for this meeting from the database
     */
    public function getAgendaFromDabatase() {
        $db = \Helper::getDB();
        $db->where('meetingId', $db->escape($this->getId()));
        $db->orderBy('parentId', 'ASC');
        $db->orderBy('sort', 'ASC');
        $results = $db->get('meeting_agenda_items');

        $agenda = new \Models\MeetingAgenda($this);
        $this->setAgenda($agenda);

        // Add all items to the agenda
        foreach($results as $result) {
            $agenda->addAgendaItem($result['id'], $result['value'], $result['sort'], $result['parentId']);
        }
    }

    /**
     * Gets the documents for this meeting from the database
     */
    public function getDocumentsFromDabatase() {
        $db = \Helper::getDB();
        $db->join('documents d', 'md.documentId = d.id', 'LEFT');
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('md.meetingId', $db->escape($this->getId()));
        $db->orderBy('LOWER(d.title)', 'ASC');
        $results = $db->get('meeting_documents md', NULL, '*, d.id AS documentId, u.id AS userId');

        $documents = new \Models\MeetingDocuments($this);
        $this->setDocuments($documents);

        // Add all items to the agenda
        foreach($results as $result) {
            $user     = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $file     = new \Models\File($result['documentId'], $result['type'], $result['title'], $user, $result['creationDate'], $result['modificationDate']);
            $documents->addDocument($file);
        }
    }

    /**
     * Gets the minutes for this meeting from the database
     * Ordered by agenda item and timestamp
     */
    public function getMinutesFromDatabase() {
        $db = \Helper::getDB();
        $db->where('meetingId', $db->escape($this->getId()));
        $db->orderBy('agendaId', 'ASC');
        $db->orderBy('timestamp', 'ASC');
        $results = $db->get('meeting_minutes');

        // New meetings instance
        $minutes = new \Models\MeetingMinutes($this);
        $this->setMinutes($minutes);

        // Save results
        foreach($results as $result) {
            // Match UUID to a user
            $userByUuid = $this->getParticipants()->getParticipantByUuid($result['uuid']);
            $user       = $userByUuid !== FALSE ? $userByUuid : NULL;

            $minutes->addMinute($result['id'], $result['timestamp'], $result['agendaId'], $result['uuid'], $result['name'], $result['message'], $user);
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
     * Returns the name of this meeting
     *
     * @return string
     */
    public function getName() {
        return $this->name;
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
     * Returns the MeetingParticipants instance that contains the list with participants
     *
     * @return \Models\MeetingParticipants
     */
    public function getParticipants(){
        return $this->participants;
    }

    /**
     * Returns the agenda instance for this meeting
     *
     * @return \Models\MeetingAgenda
     */
    public function getAgenda() {
        return $this->agenda;
    }

    /**
     * Adds the given item to the agenda for this meeting
     *
     * @param integer $id
     * @param string $item
     * @param integer $order
     * @param integer $parentId
     */
    public function addAgendaItem($id, $item, $order, $parentId = 0) {
        $this->getAgenda()->addAgendaItem($id, $item, $order, $parentId);
    }

    /**
     * Sets the agenda for this meeting
     *
     * @param \Models\MeetingAgenda $agenda
     */
    public function setAgenda(\Models\MeetingAgenda $agenda) {
        $this->agenda = $agenda;
    }

    /**
     * Sets the list with documents
     *
     * @param \Models\MeetingDocuments $documents
     */
    public function setDocuments(\Models\MeetingDocuments $documents) {
        $this->documents = $documents;
    }

    /**
     * Returns the MeetingDocuments instance that contains the list with documents
     *
     * @return \Models\MeetingDocuments
     */
    public function getDocuments() {
        return $this->documents;
    }

    /**
     * Retuns the meeting minutes instance that contains the minutes for this meeting
     *
     * @return \Models\MeetingMinutes
     */
    public function getMinutes() {
        return $this->minutes;
    }

    /**
     * Sets the minutes instance for this meeting
     *
     * @param \Models\MeetingMinutes $minutes
     */
    public function setMinutes(\Models\MeetingMinutes $minutes) {
        $this->minutes = $minutes;
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