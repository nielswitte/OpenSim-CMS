<?php
namespace Models;

if (EXEC != 1) {
    die('Invalid request');
}

/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 25th, 2014
 */
class MeetingParticipants {
    private $meeting;
    private $participants;

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
     * Returns the meeting instance for this list
     *
     * @return \Models\Meeting
     */
    public function getMeeting() {
        return $this->meeting;
    }
}
