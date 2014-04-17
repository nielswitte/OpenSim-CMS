<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Meeting controller
 *
 * @author Niels Witte
 * @version 0.3d
 * @date April 16th, 2014
 * @since March 13th, 2014
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
     * Returns the meeting instance for this controller
     *
     * @return \Models\Meeting
     */
    public function getMeeting() {
        return $this->meeting;
    }

    /**
     * Creates a new meeting and sends an email to all participants including an ICS invite
     *
     * @param array $parameters
     *              * string startDate - YYYY-MM-DD HH:mm:ss
     *              * string endDate - YYYY-MM-DD HH:mm:ss
     *              * integer or array room - Room ID or array which contains id => roomId
     *              * string name - The meeting name
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
            'roomId'    => $db->escape($room),
            'name'      => $db->escape($parameters['name'])
        );
        $meetingId = @$db->insert('meetings', $data);
        // Create a new meeting object for this meeting
        $this->meeting = new \Models\Meeting($meetingId);
        // Attach a new meeting participants list
        $participants  = new \Models\MeetingParticipants($this->getMeeting());
        $this->getMeeting()->setParticipants($participants);

        // Participants are a array of ids or an array of users?
        if(isset($parameters['participants'][0]) && is_numeric($parameters['participants'][0])) {
            $participants = $parameters['participants'];
        } else {
            $participants = array();
            foreach($parameters['participants'] as $participant) {
                $participants[] = $participant['id'];
            }
        }

        // Sets the participants for this meeting
        $this->setParticipants($participants);

        // Attach a new documents list
        $documents = new \Models\MeetingDocuments($this->getMeeting());
        $this->getMeeting()->setDocuments($documents);

        // Documents are a array of ids or an array of documents?
        if(isset($parameters['documents'][0]) && is_numeric($parameters['documents'][0])) {
            $documents = $parameters['documents'];
        } else {
            $documents = array();
            foreach($parameters['documents'] as $document) {
                $documents[] = $document['id'];
            }
        }
        $this->setDocuments($documents);

        // Create the agenda
        $agenda = $this->parseAgendaString($parameters['agenda']);
        $this->setAgenda($agenda);

        // Mail participants for this meeting
        $this->mailParticipants();

        return $meetingId;
    }

    /**
     * Updates this meeting
     *
     * @param array $parameters
     *              * string startDate - YYYY-MM-DD HH:mm:ss
     *              * string endDate - YYYY-MM-DD HH:mm:ss
     *              * integer or array room - Room ID or array which contains id => roomId
     *              * string name - The meeting name
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
            'roomId'    => $db->escape($room),
            'name'      => $db->escape($parameters['name'])
        );
        $db->where('id', $db->escape($this->getMeeting()->getId()));
        $update = $db->update('meetings', $data);

        $participants = $this->updateParticipants($parameters['participants']);

        // Update the documents list
        $documentsRemove    = $this->removeDocuments();

        // Documents are a array of ids or an array of documents?
        if(isset($parameters['documents'][0]) && is_numeric($parameters['documents'][0])) {
            $documents = $parameters['documents'];
        } else {
            $documents = array();
            foreach($parameters['documents'] as $document) {
                $documents[] = $document['id'];
            }
        }
        $documentsAdd = $this->setDocuments($documents);

        // Update the agenda
        $agenda = $this->parseAgendaString($parameters['agenda']);
        $this->removeAgenda();
        $this->setAgenda($agenda);

        // Were any updates made?
        if($update || $participants || $documentsRemove || $documentsAdd) {
            return TRUE;
        } else {
            throw new \Exception('No changes were made to this meeting', 6);
        }
    }

    /**
     * Removes all agenda items from this meeting
     *
     * @return boolean
     */
    private function removeAgenda() {
        $db = \Helper::getDB();
        $db->where('meetingId', $db->escape($this->getMeeting()->getId()));
        return $db->delete('meeting_agenda_items');
    }

    /**
     * Inserts the parsed array into the database
     *
     * @param array $agenda
     * @return boolean
     */
    private function setAgenda($agenda) {
        $db = \Helper::getDB();
        $result = FALSE;
        foreach($agenda as $item) {
            $item['parentId']  = ($item['parentId'] == 0 ? NULL : $item['parentId']);
            $item['meetingId'] = $this->getMeeting()->getId();
            $result = $db->insert('meeting_agenda_items', $item);
        }
        return $result;
    }

    /**
     * Removes all documents from this meeting
     *
     * @return boolean
     */
    private function removeDocuments() {
        // Create new empty documents list
        $documents = new \Models\MeetingDocuments($this->getMeeting());
        $this->getMeeting()->setDocuments($documents);

        // Add documents to DB
        $db = \Helper::getDB();
        $db->where('meetingId', $db->escape($this->getMeeting()->getId()));
        return $db->delete('meeting_documents');
    }

    /**
     * Inserts the documents array into the database
     *
     * @param array $files - List with file IDs
     * @return boolean
     */
    private function addDocuments($files) {
        $db = \Helper::getDB();
        $result = FALSE;
        foreach($files as $fileId) {
            $file = new \Models\File($fileId);
            $this->getMeeting()->getDocuments()->addDocument($file);

            // DB data
            $data = array(
                'meetingId'     => $db->escape($this->getMeeting()->getId()),
                'documentId'    => $db->escape($file->getId())
            );
            $result = $db->insert('meeting_documents', $data);
        }
        return $result;
    }

    /**
     * Updates the list with participants for this meeting
     *
     * @param array $participants
     * @return boolean - TRUE when something changes
     */
    public function updateParticipants($participants) {
        // Update the participants list
        // Participants are a array of ids or an array of users?
        if(isset($participants[0]) && is_numeric($participants[0])) {
            $newParticipantIds = $participants;
        } else {
            $newParticipantIds = array();
            foreach($participants as $participant) {
                $newParticipantIds[] = $participant['id'];
            }
        }

        $oldParticipantIds = array();
        $this->meeting->getParticipantsFromDatabase();
        foreach($this->meeting->getParticipants() as $participant) {
            $oldParticipantIds[] = $participant->getId();
        }

        $removeParticipants = array_diff($oldParticipantIds, $newParticipantIds);
        $addParticipants    = array_diff($newParticipantIds, $oldParticipantIds);

        // Remove old
        $participantsRemove = $this->removeParticipants($removeParticipants);
        // Add new participants
        $participantsAdd    = $this->addParticipants($addParticipants);

        return $participantsAdd || $participantsRemove;
    }

    /**
     * Removes the participants for this meeting
     *
     * @return boolean
     */
    private function removeParticipants($participants) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($participants as $participant) {
            // Remove user instances from meeting
            $user = new \Models\User($participant);
            $this->getMeeting()->getParticipants()->removeParticipant($user);

            // Remove from database
            $db->where('meetingId', $db->escape($this->getMeeting()->getId()));
            $db->where('userId', $db->escape($user->getId()));
            $result = $db->delete('meeting_participants');
        }

        return $result;
    }

    /**
     * Sets the participants for this meeting
     *
     * @param array $participants - Array with participant IDs
     * @return boolean
     */
    private function addParticipants($participants) {
        $result = FALSE;
        $db     = \Helper::getDB();
        foreach($participants as $participant) {
            // Add user instances to meeting
            $user = new \Models\User($participant);
            $this->getMeeting()->getParticipants()->addParticipant($user);

            // DB data
            $data = array(
                'meetingId'     => $db->escape($this->getMeeting()->getId()),
                'userId'        => $db->escape($user->getId())
            );
            $result = $db->insert('meeting_participants', $data);
        }
        return $result;
    }

    /**
     * Sends a mail to all participants of this meeting with the
     * name, date, agenda, participants, documents of this meeting
     */
    public function mailParticipants() {
        $meeting = $this->getMeeting();
        // Get actual meeting data from database (so only final data is used)
        $meeting->getInfoFromDatabase();
        $startDate = strtotime($meeting->getStartDate());
        $endDate   = strtotime($meeting->getEndDate());

        // Get Grid/Region/Room details
        $meeting->getRoom()->getInfoFromDatabase();

        // Get the actual up-to-date list of participants
        $meeting->getParticipantsFromDatabase();
        $participants = $meeting->getParticipants()->getParticipants();

        // Get the actual up-to-date agenda
        $meeting->getAgendaFromDabatase();

        // Get the actual up-to-date documents list
        $meeting->getDocumentsFromDabatase();

        // Prepare email-template
        $html   = file_get_contents(dirname(__FILE__) .'/../templates/email/default.html');

        $osUrl  = 'opensim://'.
                    $meeting->getRoom()->getRegion()->getGrid()->getOsIp() .':'.
                    $meeting->getRoom()->getRegion()->getGrid()->getOsPort() .'/'.
                    urlencode($meeting->getRoom()->getRegion()->getName()) .'/'.
                    $meeting->getRoom()->getX() .'/'.
                    $meeting->getRoom()->getY() .'/'.
                    $meeting->getRoom()->getZ();

        $hopUrl = str_replace('opensim://', 'hop://', $osUrl);
        $data   = array(
            '{{title}}'     => $meeting->getName(),
            '{{body}}'      => \Helper::linkIt(
                '<p>An meeting has been scheduled for '. date('l F j', $startDate) .' at '. date('H:i', $startDate) .' until '. date('H:i', $endDate) .'.</p>'
                .'<h2>Agenda</h2>'
                . str_replace(' ', '&nbsp;', nl2br($meeting->getAgenda()->toString(), FALSE))
                .'<h2>Participants</h2>'
                .'<p>'. nl2br($meeting->getParticipants()->toString(), FALSE) .'</p>'
                .'<div style="text-align: center;">'
                //.'  <a href="'. $osUrl .'" class="btn btn-lg">Go to Meeting</a><br>'
                //.'  <small>'. $osUrl .'</small><br><br>'
                .'  <small>Copy and paste this URL in the address bar of your OpenSim viewer: <br>'. $hopUrl .'</small>'
                .'</div>'
            )
        );
        $html = str_replace(array_keys($data), array_values($data), $html);

        // Create ICS
        $pathToICS = \Helper::getICS(
            $meeting->getStartDate(),
            $meeting->getEndDate(),
            $meeting->getName(),
            $meeting->getAgenda()->toString(),
            $meeting->getRoom()->getRegion()->getName(),
            $meeting->getCreator()->getFirstName() .' '. $meeting->getCreator()->getLastName(),
            $meeting->getCreator()->getEmail(),
            $meeting->getParticipants()->getEmails()
        );

        // Mail all participants
        foreach($participants as $participant) {
            $mail = \Helper::getMailer();
            $mail->addAddress($participant->getEmail(), $participant->getFirstName() .' '. $participant->getLastName());
            $mail->Subject = '[OpenSim-CMS] Meeting: '. $meeting->getName();
            // Attach ICS
            $mail->addAttachment($pathToICS, 'invite.ics', 'base64', 'application/ics');
            // Add template
            $mail->msgHTML($html, '', TRUE);
            // Send the email
            $result = $mail->send();
        }

        // Remove the ICS file when done
        unlink($pathToICS);
        return $result;
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
     * Parses a numbered list to an array with parent child references
     *
     * @example:
     *  1. Opening
     *  2. Comments
     *    2.1. Others
     *    2.2. From the board
     *  3. Minutes
     *    3.1. Test
     *      3.2.1 Test 3
     *    3.2. Test 2
     *
     * @param string $agendaString
     * @return array
     */
    public function parseAgendaString($agendaString) {
        // Empty output
        $agenda     = array();
        // Split in lines
        $lines      = explode("\n", trim($agendaString));
        // Starting ID
        $id         = 1;
        // Starting Depth
        $depth      = 1;
        // Starting list with parents
        $parents    = array(0);
        // Process lines
        foreach($lines as $line) {
            // Split index and subject
            $item = explode(' ', trim($line), 2);
            // Get index
            $item[0] = explode('.', trim($item[0], '. '));
            // See if the element is lower than the previous
            if(count($item[0]) > $depth) {
                $depth      = count($item[0]);
                $parents[]  = $id-1;
                $parentId   = end($parents);
            // Element is higher than previous
            } elseif(count($item[0]) < $depth) {
                $depth      = count($item[0]);
                $parents    = array_slice($parents, 0, $depth);
                $parentId   = end($parents);
            // Element is on same level
            } else {
                $parentId   = end($parents);
            }

            // Can find an agenda item on this line?
            if(count($item) >= 2) {
                // Create agenda Item
                $agendaItem = array(
                    'id'        => $id,
                    'parentId'  => $parentId,
                    'value'     => $item[1],
                    'sort'      => end($item[0])
                );

                $agenda[] = $agendaItem;
                // Increase ID
                $id++;
            }
        }
        return $agenda;
    }

    /**
     * Saves the array with messages in the meeting
     *
     * @param array $parameters
     *              * string timestamp - The timestamp as YYYY-MM-DDThh:mm:ss.ff..fZ
     *              * string uuid - The sender's UUID
     *              * string name - The sender's name
     *              * integer agendaId - The current agenda ID
     *              * string message - The message
     * @returns boolean
     */
    public function saveChat($parameters) {
        $result = FALSE;
        $db = \Helper::getDB();

        // Store all results separate
        foreach($parameters as $item) {
            $item['meetingId']  = $this->getMeeting()->getId();
            $item['message']    = strip_tags($item['message'], '<p><i><b><u><br>');
            $result = $db->insert('meeting_minutes', $item);
        }

        return $result;
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

        if(count($parameters) < 6) {
            throw new \Exception('Expected atleast 6 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['name']) || strlen($parameters['name']) == 0) {
            throw new \Exception('Missing parameter (string) "name"', 2);
        } elseif(!isset($parameters['agenda'])) {
            throw new \Exception('Missing parameter (string) "agenda"', 7);
        } elseif(!isset($parameters['startDate']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameters['startDate'])) {
            throw new \Exception('Missing parameter (string) "startDate", which should be in the format YYYY-MM-DD HH:mm:ss', 3);
        } elseif(!isset($parameters['endDate']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameters['endDate']) || strtotime($parameters['endDate']) <=  strtotime($parameters['startDate'])) {
            throw new \Exception('Missing parameter (string) "endDate", which should be in the format YYYY-MM-DD HH:mm:ss and past "startDate"', 4);
        } elseif(!isset($parameters['room']) || (!isset($parameters['room']['id']) && !is_numeric($parameters['room']))) {
            throw new \Exception('Missing parameter (integer or array) "room", which should be a room id or a room array which contains a room id ', 5);
        } elseif(!isset($parameters['participants']) || !is_array($parameters['participants']) || empty($parameters['participants']) || (!isset($parameters['participants'][0]['id']) && !is_numeric($parameters['participants'][0]))) {
            throw new \Exception('Missing parameter (array) "participants", which should be array which contains ids of the participants or contains an array of users which have an id ', 6);
        } elseif(isset($parameters['documents']) && (!empty($parameters['documents']) && !isset($parameters['documents'][0]['id']) && !is_numeric($parameters['documents'][0]))) {
            throw new \Exception('Missing parameter (array) "documents", which should be array which contains ids of the documents or contains an array of documents which have an id ', 9);
        } elseif($this->meetingOverlap($parameters['startDate'], $parameters['endDate'], (isset($parameters['room']['id']) ? $parameters['room']['id'] : $parameters['room']), ($this->getMeeting() !== NULL ? $this->getMeeting()->getId() : 0))) {
            throw new \Exception('Meeting overlaps with an existing meeting', 8);
        } else {
            $result = TRUE;
        }

        return $result;
    }

    /**
     * Validates the parameters for the savechat function
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersChat($parameters) {
        $result = FALSE;
        $count  = 0;
        foreach($parameters as $parameter) {
            if(count($parameter) != 5) {
                throw new \Exception('Expected 5 parameters, '. count($parameter) .' given at row '. $count, 1);
            } elseif(!isset($parameter['timestamp']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameter['timestamp'])) {
                throw new \Exception('Missing parameter (string) "timestamp" at row '. $count .' which should be in the format YYYY-MM-DD HH:mm:ss', 2);
            } elseif(!isset($parameter['uuid']) || (strlen($parameter['uuid']) > 1 && !\Helper::isValidUuid($parameter['uuid']))) {
                throw new \Exception('Missing parameter (string) "uuid", which needs to be empty, 0 or a valid UUID at row '. $count, 3);
            } elseif(!isset($parameter['name'])) {
                throw new \Exception('Missing parameter (string) "name" at row '. $count, 4);
            } elseif(!isset($parameter['agendaId'])) {
                throw new \Exception('Missing parameter (integer) "agendaId" at row '. $count, 5);
            } elseif(!isset($parameter['message'])) {
                throw new \Exception('Missing parameter (string) "message" at row '. $count, 6);
            } else {
                $result = TRUE;
            }
            $count++;
        }

        return $result;
    }
}