<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Meeting controller
 *
 * @author Niels Witte
 * @version 0.2
 * @date June 4, 2014
 * @since March 21, 2014
 */
class ChatController {
    private $chat;

    /**
     * Constructs a new controller with the given Chat
     *
     * @param \Models\Chat $chat
     */
    public function __construct(\Models\Chat $chat = NULL) {
        $this->chat = $chat;
    }

    /**
     * Adds a new chat to the database with the given data
     *
     * @param array $parameters
     *              * integer $userId (-1 = anonymous)
     *              * string $message
     *              * string $timestamp - format YYYY-MM-DD HH:mm:ss
     * @return integer - The ID of the last inserted item
     */
    public function addChats($parameters) {
        $db     = \Helper::getDB();
        $result = FALSE;

        // Process individual parameters
        foreach($parameters as $parameter) {
            // Anonymouse user
            if($parameter['userId'] == -1) {
                $parameter['userId'] = null;
            }
            $parameter['message'] = strip_tags($parameter['message']);
            $parameter['gridId']  = $this->chat->getGrid()->getId();
            $result = $db->insert('chats', $parameter);
        }

        return $result;
    }

    /**
     * Parses the array with parameters to check whether or not the chat can be added to the DB
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;

        // Process individual parameters
        foreach($parameters as $parameter) {
            if(count($parameter) < 3) {
                throw new \Exception('Expected 3 parameters, '. count($parameter) .' given', 1);
            } elseif(!isset($parameter['userId']) || !is_numeric($parameter['userId'])) {
                throw new \Exception('Missing parameter (integer) "userId"', 2);
            } elseif(!isset($parameter['message']) || strlen($parameter['message']) == 0) {
                throw new \Exception('Missing parameter (string) "message" with a minumum length of 1 character', 3);
            } elseif(!isset($parameter['timestamp']) || !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameter['timestamp'])) {
                throw new \Exception('Missing parameter (string) "timestamp", which should be in the format YYYY-MM-DD HH:mm:ss', 4);
            } elseif(isset($parameter['fromCMS']) && $parameter['fromCMS'] != 1 && $parameter['fromCMS'] != 0) {
                throw new \Exception('Invalid parameter (integer) "fromCMS", which should be a 1 (for TRUE) or 0 (for FALSE)', 5);
            } else {
                $result = TRUE;
            }
        }

        return $result;
    }
}