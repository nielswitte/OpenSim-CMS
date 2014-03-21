<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/simpleModel.php';
require_once dirname(__FILE__) .'/chatMessage.php';
require_once dirname(__FILE__) .'/grid.php';
require_once dirname(__FILE__) .'/user.php';

/**
 * This class represents the chat model
 * Used for fetching chatmessages for the specifie grid
 *
 * @author Niels Witte
 * @version 0.1
 * @since March 21st, 2014
 */
class Chat implements SimpleModel {
    private $messages = array();
    private $grid;
    private $startingId;
    private $startingTimestamp;

    /**
     * Create a new chat instance for the given grid
     *
     * @param \Models\Grid $grid
     * @param array $messages - [Optional]
     */
    public function __construct($grid, $messages = array()) {
        $this->grid     = $grid;
        $this->messages = $messages;
    }

    /**
     * Sets the starting ID for the results, should be done before calling
     * getInforFromDatabase()
     *
     * @param integer $id
     */
    public function setStartingId($id) {
        $this->startingId = $id;
    }

    /**
     * Sets the starting timestamp for the results, should be done before calling
     * getInforFromDatabase()
     *
     * @param string $timestamp - format: YYYY-MM-DD HH:mm:ss
     */
    public function setStartingTimestamp($timestamp) {
        $this->startingTimestamp = $timestamp;
    }

    /**
     * Gets the chats and the users sending the message from the database
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->join('users u', 'u.id = c.userId', 'LEFT');
        $limit = 50;
        // A starting ID is set?
        if(isset($this->startingId)) {
            $db->where('c.id', array('>=' => $db->escape($this->startingId)));
            $limit = NULL;
        }
        // A starting timestamp is set?
        if(isset($this->startingTimestamp)) {
            $db->where('c.timestamp', array('>=' => $db->escape($this->startingTimestamp)));
            $limit = NULL;
        }

        // Only from selected Grid
        $db->where('c.gridId', $db->escape($this->getGrid()->getId()));

        // Newest first
        $db->orderBy('c.timestamp', 'DESC');

        // Get results
        $chats = $db->get('chats c', $limit, 'c.*, u.*, c.id as id');
        foreach($chats as $chat) {
            // User is known?
            if($chat['userId'] != NULL) {
                $user   = new \Models\User($chat['userId'], $chat['username'], $chat['email'], $chat['firstName'], $chat['lastName']);
            // Unknown user
            } else {
                $user   = $user = new \Models\User(-1, 'Anonymous', 'Anonymous', '', '');
            }
            $message    = new \Models\ChatMessage($chat['id'], $this->getGrid(), $user, $chat['message'], $chat['timestamp']);
            $this->addChatMessage($message);
        }
    }

    /**
     * Adds the message to end of the chatlog
     *
     * @param \Models\ChatMessage $message
     */
    public function addChatMessage(\Models\ChatMessage $message) {
        $this->messages[] = $message;
    }

    /**
     * Returns the array with chat messages
     *
     * @return array
     */
    public function getChatMessages() {
        return $this->messages;
    }

    /**
     * Returns the grid for this chat
     *
     * @return \Models\Grid
     */
    public function getGrid() {
        return $this->grid;
    }
}
