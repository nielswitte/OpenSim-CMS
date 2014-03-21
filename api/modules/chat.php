<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../../models/chat.php';
require_once dirname(__FILE__) .'/../../controllers/chatController.php';

/**
 * Implements the functions called on the Grid
 *
 * @author Niels Witte
 * @version 0.1
 * @since March 21st, 2014
 */
class Chat extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('chat');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/grid\/(\d+)\/chats\/?$/",             "getChats",      $this, "GET",  \Auth::READ);  // Get last 50 entries of chatlog
        $this->api->addRoute("/grid\/(\d+)\/chats\/(\d+)\/?$/",      "getChats",      $this, "GET",  \Auth::READ);  // Get first 50 entries of chatlog past the given unix timestamp
        $this->api->addRoute("/grid\/(\d+)\/chats\/?$/",             "addChats",      $this, "POST", \Auth::WRITE); // Add the given chat or array of chats to the database
    }

    /**
     * Returns the list with chats optionally starting at the given offset
     * Chats are in order, starting at the latest.
     *
     * @param array $args
     * @return array
     */
    public function getChats($args) {

        // Check for offset?
        $args[2]    = isset($args[2]) ? $args[2] : 0;
        $grid       = new \Models\Grid($args[1]);
        $chat       = new \Models\Chat($grid);
        // Set offset if used
        if($args[1] > 0) {
            $chat->setStartingTimestamp(date('Y-m-d H:i:s', $args[1]));
        }
        $chat->getInfoFromDatabase();

        $result     = $this->getChatData($chat);

        return $result;
    }

    /**
     * Converts the chat to a nice array
     *
     * @param \Models\Chat $chat
     * @return array
     */
    public function getChatData(\Models\Chat $chat) {
        $results = array();
        foreach($chat->getChatMessages() as $message) {
            $results[]  = array(
                'id'        => $message->getId(),
                'user'      => array(
                    'id'        => $message->getUser()->getId(),
                    'username'  => $message->getUser()->getUsername(),
                    'firstName' => $message->getUser()->getFirstName(),
                    'lastName'  => $message->getUser()->getLastName(),
                    'email'     => $message->getUser()->getEmail()
                ),
                'message'   => $message->getMessage(),
                'timestamp' => $message->getTimestamp(),
            );
        }
        return $results;
    }

    /**
     * Adds the given chat to the database
     *
     * @param array $args
     * @return array
     */
    public function addChats($args) {
        $data       = FALSE;
        $grid       = new \Models\Grid($args[1]);
        $chat       = new \Models\Chat($grid);
        $chatCtrl   = new \Controllers\ChatController($chat);
        $input      = \Helper::getInput(TRUE);

        // Validate parameters before inserting
        if($chatCtrl->validateParametersCreate($input)) {
            $data = $chatCtrl->addChats($input);
        }

        // Format the result
        $result = array(
            'success'   => $data !== FALSE ? TRUE : FALSE,
            'id'        => $data !== FALSE ? $data : 0
        );

        return $result;
    }
}