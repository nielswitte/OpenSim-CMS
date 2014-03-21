<?php
namespace Models;

defined('EXEC') or die('Invalid request');

/**
 * This class is a chat message used by chat
 *
 * @author Niels Witte
 * @version 0.1
 * @since March 21st, 2014
 */
class ChatMessage {
    private $id;
    private $grid;
    private $user;
    private $message;
    private $timestamp;
    private $fromCMS;

    /**
     * Creates a new chat message with the given values
     * @param integer $id
     * @param \Models\Grid $grid
     * @param \Models\User $user
     * @param string $message
     * @param string $timestamp - format: YYYY-MM-DD HH:mm:ss
     * @param boolean $fromCMS - [Optional] TRUE if the message is sent from the CMS, FALSE if from OpenSim server
     */
    public function __construct($id, \Models\Grid $grid, \Models\User $user, $message, $timestamp, $fromCMS = TRUE) {
        $this->id           = $id;
        $this->grid         = $grid;
        $this->user         = $user;
        $this->message      = $message;
        $this->timestamp    = $timestamp;
        $this->fromCMS      = $fromCMS;
    }

    /**
     * Returns the ID of the message
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the grid of where the message is for/from
     *
     * @return \Models\Grid
     */
    public function getGrid() {
        return $this->grid;
    }

    /**
     * Returns the user instance of the sender
     *
     * @return \Models\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Returns the message
     *
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Returns the timestamp
     *
     * @return string - format YYYY-MM-DD HH:mm:ss
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Returns whether or not a message is sent from the CMS (TRUE)
     * or from the OpenSim server (FALSE)
     *
     * @return boolean
     */
    public function isFromCMS() {
        return $this->fromCMS;
    }
}
