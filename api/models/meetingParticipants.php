<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 17th, 2014
 * @since February 25th, 2014
 */
class MeetingParticipants {
    private $meeting;
    private $participants;
    // Array to cache the getParticipantByUuid search results
    private $participantUuids = array();

    /**
     * Constructs a new participants list for the given meeting
     *
     * @param \Models\Meeting $meeting
     * @param array $participants - [Optional]
     */
    public function __construct(\Models\Meeting $meeting, $participants = array()) {
        $this->meeting      = $meeting;
        $this->participants = $participants;
    }

    /**
     * Add a participant to the list
     *
     * @param \Models\User $user
     */
    public function addParticipant(\Models\User $user) {
        $this->participants[] = $user;
    }

    /**
     * Get all participants for this meeting
     *
     * @return array
     */
    public function getParticipants() {
        return $this->participants;
    }

    /**
     * Returns an array with the first name and last name as key and email addresses as value of the participants
     *
     * @return array - first name last name => email
     */
    public function getEmails() {
        $emails = array();
        foreach($this->getParticipants() as $participant) {
            $emails[$participant->getFirstName() .' '. $participant->getLastName()] = $participant->getEmail();
        }
        return $emails;
    }

    /**
     * Returns the meeting instance for this list
     *
     * @return \Models\Meeting
     */
    public function getMeeting() {
        return $this->meeting;
    }

    /**
     * Searches all users' avatars for a match
     *
     * @param string $uuid
     * @return \Models\User or boolean FALSE when not found
     */
    public function getParticipantByUuid($uuid) {
        // No cached value?
        if(!isset($this->participantUuids[$uuid])) {
            foreach($this->getParticipants() as $participant) {
                // List with avatars has not yet been created?
                if($participant->getAvatars() === NULL) {
                    // Only get basic information
                    $participant->getAvatarsFromDatabase(FALSE);
                }
                // Attempt to match avatar to UUID
                if($participant->getAvatars() !== FALSE && $participant->getAvatarByUuid($uuid) !== FALSE) {
                    // Save in cache
                    $this->participantUuids[$uuid] = $participant;
                    // Return result
                    return $participant;
                }
            }
            // Save in cache
            $this->participantUuids[$uuid] = FALSE;
            return FALSE;
        // Return from cache
        } else {
            return $this->participantUuids[$uuid];
        }
    }

    /**
     * Searches the list of participants for an user with a specific id
     *
     * @param integer $id
     * @return \Models\User or boolean when not found
     */
    public function getParticipantById($id) {
        foreach($this->getParticipants() as $participant) {
            if($participant->getId() == $id) {
                return $participant;
            }
        }
        return FALSE;
    }

    /**
     * Converts the list with participants to a string
     *
     *
     * @return string
     */
    public function toString() {
        $string = '';
        foreach($this->getParticipants() as $participant) {
            $string .= $participant->getUsername() .' ('. $participant->getFirstName() .' '. $participant->getLastName() .') [ '. $participant->getEmail() .' ]' . "\n";
        }

        return $string;
    }
}
