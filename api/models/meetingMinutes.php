<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class represents the minutes of a meeting
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 20th, 2014
 * @since March 19th, 2014
 */
class MeetingMinutes {
    private $meeting;
    private $minutes = array();

    /**
     * Creates a new minutes overview for the given meeting
     *
     * @param \Models\Meeting $meeting
     */
    public function __construct(\Models\Meeting $meeting = null) {
        $this->meeting = $meeting;
    }

    /**
     * Adds the given line to the minutes list
     *
     * @param integer $id - The ID of this line of minutes
     * @param string $timestamp - Time stamp when the message took place YYYY-MM-DD HH:mm:ss
     * @param integer $agendaId - The ID of the corresponding agenda item
     * @param string $uuid - The uuid is shown on the opensim server
     * @param string $name - The name as shown on the opensim server
     * @param string $message - The message
     * @param \Models\User $user - [Optional] Reference to the user that issued this line
     */
    public function addMinute($id, $timestamp, $agendaId, $uuid, $name, $message, \Models\User $user = null) {
        $this->minutes[] = array(
            'id'            => $id,
            'timestamp'     => $timestamp,
            'agendaId'      => $agendaId,
            'uuid'          => $uuid,
            'name'          => $name,
            'message'       => $message,
            'user'          => $user
        );
    }

    /**
     * Returns an array with all the minutes of this meeting in it.
     * Well at least if $meeting->getMinutesFromDatabase() is called before.
     *
     * @return array
     */
    public function getMinutes() {
        return $this->minutes;
    }
}
