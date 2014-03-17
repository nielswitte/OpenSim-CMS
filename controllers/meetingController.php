<?php
namespace Controllers;

if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the Meeting controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 13th, 2014
 */
class MeetingController {
    private $meeting;

    /**
     * Constructs a new controller with the given Meeting
     *
     * @param \Models\Meeting $meeting
     */
    public function __construct(\Models\Meeting $meeting = NULL) {
        $this->meeting = $meeting;
    }

    /**
     * Creates a new meeting
     *
     * @param array $parameters
     *              * string startDate - YYYY-MM-DD HH:mm:ss
     *              * string endDate - YYYY-MM-DD HH:mm:ss
     *              * integer or array room - Room ID or array which contains id => roomId
     *              * array participants - An array with User IDs or with users which contain id => userId
     * @return integer - meetingId or boolean FALSE on failure
     * @throws \Exception
     */
    public function createMeeting($parameters) {
        $db = \Helper::getDB();

        // Room is a number or an object?
        if(is_numeric($parameters['room'])) {
            $room = $parameters['room'];
        } else {
            $room = $parameters['room']['id'];
        }

        // Update the meeting itself
        $data = array(
            'userId'    => \Auth::getUser()->getId(),
            'startDate' => $db->escape($parameters['startDate']),
            'endDate'   => $db->escape($parameters['endDate']),
            'roomId'    => $db->escape($room)
        );
        $meetingId = $db->insert('meetings', $data);

        // Create a new meeting object for this meeting
        $this->meeting = new \Models\Meeting($meetingId);

        // Participants are a array of ids or an array of users?
        if(isset($parameters['participants'][0]) && is_numeric($parameters['participants'][0])) {
            $participants = $parameters['participants'];
        } else {
            $participants = array();
            foreach($parameters['participants'] as $participant) {
                $participants[] = $participant['id'];
            }
        }

        $this->setParticipants($participants);

        return $meetingId;
    }

    /**
     * Updates this meeting
     *
     * @param array $parameters
     *              * string startDate - YYYY-MM-DD HH:mm:ss
     *              * string endDate - YYYY-MM-DD HH:mm:ss
     *              * integer or array room - Room ID or array which contains id => roomId
     *              * array participants - An array with User IDs or with users which contain id => userId
     * @return boolean
     * @throws \Exception
     */
    public function updateMeeting($parameters) {
        $db = \Helper::getDB();

        // Room is a number or an object?
        if(is_numeric($parameters['room'])) {
            $room = $parameters['room'];
        } else {
            $room = $parameters['room']['id'];
        }

        // Update the meeting itself
        $data = array(
            'startDate' => $db->escape($parameters['startDate']),
            'endDate'   => $db->escape($parameters['endDate']),
            'roomId'    => $db->escape($room)
        );
        $db->where('id', $db->escape($this->meeting->getId()));
        $update = $db->update('meetings', $data);

        // Update the participants list
        $participantsRemove = $this->removeParticipants();

        // Participants are a array of ids or an array of users?
        if(isset($parameters['participants'][0]) && is_numeric($parameters['participants'][0])) {
            $participants = $parameters['participants'];
        } else {
            $participants = array();
            foreach($parameters['participants'] as $participant) {
                $participants[] = $participant['id'];
            }
        }
        $participantsAdd = $this->setParticipants($participants);

        // Were any updates made?
        if($update || $participantsRemove || $participantsAdd) {
            return TRUE;
        } else {
            throw new \Exception('No changes were made to this meeting', 6);
        }
    }

    /**
     * Removes the participants for this meeting
     *
     * @return boolean
     */
    private function removeParticipants() {
        $db = \Helper::getDB();
        $db->where('meetingId', $db->escape($this->meeting->getId()));
        return $db->delete('meeting_participants');
    }

    /**
     * Sets the participants for this meeting
     *
     * @param array $participants - Array with participant IDs
     * @return boolean
     */
    private function setParticipants($participants) {
        $result = FALSE;
        $db     = \Helper::getDB();
        foreach($participants as $participant) {
            $data = array(
                'meetingId'     => $db->escape($this->meeting->getId()),
                'userId'        => $db->escape($participant)
            );
            $result = $db->insert('meeting_participants', $data);
        }
        return $result !== FALSE ? TRUE : FALSE;
    }

    /**
     * Checks to see if the given room is available during the given interval
     * Optionally specify a meetingId to exclude the selected meeting (for example when performing an update)
     *
     * @param timestamp $startDate - Start date time of the meeting (YYYY-MM-DD HH:mm:ss)
     * @param timestamp $endDate - End date time of the meeting (YYYY-MM-DD HH:mm:ss)
     * @param integer $roomId - Meeting Room ID
     * @param integer $meetingId - [Optional]
     * @return boolean
     */
    public function meetingOverlap($startDate, $endDate, $roomId, $meetingId = 0) {
        $db = \Helper::getDB();
        $params = array(
            $db->escape($startDate),
            $db->escape($endDate),
            $db->escape($startDate),
            $db->escape($endDate),
            $db->escape($roomId)
        );
        if($meetingId == 0) {
            $result = $db->rawQuery('
                    SELECT
                        COUNT(*) AS count
                    FROM
                        meetings
                    WHERE (
                            startDate BETWEEN ? AND ?
                        OR
                            ? BETWEEN startDate AND endDate
                        OR
                            ? BETWEEN startDate AND endDate
                    ) AND
                        roomId = ?', $params);
        } else {
            $params[] = $db->escape($meetingId);
            $result = $db->rawQuery('
                    SELECT
                        COUNT(*) AS count
                    FROM
                        meetings
                    WHERE (
                            startDate BETWEEN ? AND ?
                        OR
                            ? BETWEEN startDate AND endDate
                        OR
                            ? BETWEEN startDate AND endDate
                    ) AND
                        roomId = ?
                    AND
                        id != ?', $params);
        }

        return $result[0]['count'] > 0 ? TRUE : FALSE;
    }

    /**
     * Validates the parameters for creating a meeting
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = $this->validateParametersUpdate($parameters);
        return $result;
    }

    /**
     * Validates the parameters for updating a meeting
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersUpdate($parameters) {
        $result = FALSE;

        // @todo prevent overlapping meetings in same room
        if(count($parameters) < 4) {
            throw new \Exception('Expected atleast 4 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['startDate']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameters['startDate'])) {
            throw new \Exception('Missing parameter (string) "startDate", which should be in the format YYYY-MM-DD HH:mm:ss', 2);
        } elseif(!isset($parameters['endDate']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameters['endDate']) || strtotime($parameters['endDate']) <=  strtotime($parameters['startDate'])) {
            throw new \Exception('Missing parameter (string) "endDate", which should be in the format YYYY-MM-DD HH:mm:ss and past "startDate"', 3);
        } elseif(!isset($parameters['room']) || (!isset($parameters['room']['id']) && !is_numeric($parameters['room']))) {
            throw new \Exception('Missing parameter (integer or array) "room", which should be roomId or a room array which contains an roomId ', 4);
        } elseif(!isset($parameters['participants'])) {
            throw new \Exception('Missing parameter (array) "participants", which should be array which contains userIds of the participants ', 5);
        } else {
            $result = TRUE;
        }
        return $result;
    }
}