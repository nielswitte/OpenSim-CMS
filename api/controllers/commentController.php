<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Comment controller
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 2nd, 2014
 * @since March 28th, 2014
 */
class CommentController {
    /**
     * The comment instance of this comment
     * @var \Models\Comment
     */
    private $comment;

    /**
     * Constructs a new controller with the given comment
     *
     * @param \Models\Comment $comment
     */
    public function __construct(\Models\Comment $comment = NULL) {
        $this->comment = $comment;
    }

    /**
     * Removes the selected comment from the database,
     * this also removes responses to the given comment sice the database cascades on remove
     *
     * @return boolean
     */
    public function removeComment() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->comment->getId()));
        $result = $db->delete('comments');
        return $result;
    }

    /**
     * Removes all comments for the given type and itemId
     * @example All slides for slideId 23: $commentController->removeCommentsByItem('slide', 23);
     *
     * @param string $type
     * @param integer $itemId
     * @return integer - Number of removed rows
     */
    public function removeCommentsByItem($type, $itemId) {
        $db = \Helper::getDB();
        $db->where('type', $db->escape($type));
        $db->where('itemId', $db->escape($itemId));
        return $db->delete('comments');
    }

    /**
     * Creates the new comment
     *
     * @param array $parameters
     *              * integer or array user - The userID of the poster
     *              * integer parentId - The ID of a comment for which this is a reply, 0 if not a reply
     *              * string message - The comment message
     *              * string type - Type of item this is a comment for (i.e. slide, document)
     *              * integer itemId - The ID of the item this is a comment for
     *              * string timestamp - [Optional] Timestamp in the format YYYY-MM-DD HH:mm:ss
     * @return integer commentId or boolean FALSE on failure
     */
    public function createComment($parameters) {
        $db = \Helper::getDB();
        $data = array(
            'parentId'      => $parameters['parentId'] > 0 ? $db->escape($parameters['parentId']) : NULL,
            'type'          => $db->escape($parameters['type']),
            'itemId'        => $db->escape($parameters['itemId']),
            'userId'        => is_array($parameters['user']) ? $db->escape($parameters['user']['id']) : $db->escape($parameters['user']),
            // Correct line breaks
            'message'       => str_replace('\n', "\n", $db->escape($parameters['message']))
        );

        if(isset($parameters['timestamp'])) {
            $data['timestamp'] = $db->escape($parameters['timestamp']);
        }

        $id = @$db->insert('comments', $data);
        return $id;
    }

    /**
     * Checks if the create parameters for a new comment are correct
     *
     * @param array $parameters
     * @return boolean
     * @thorws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) < 3) {
            throw new \Exception('Expected 3 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['user']) && ((!isset($parameters['user']['id']) && !is_numeric($parameters['user']['id'])) || !is_numeric($parameters['user']))) {
            throw new \Exception('Missing parameter (array) "user" or (integer) "user", which should contains an userobject with an ID or contains only the userId as integer.', 2);
        } elseif(!isset($parameters['message']) || strlen($parameters['message']) < 1) {
            throw new \Exception('Missing parameter (string) "message" which should be atleast one character long.', 3);
        } elseif(!isset($parameters['parentId']) || !is_numeric($parameters['parentId'])) {
            throw new \Exception('Missing parameter (integer) "parentId" which should be the ID the message is a reply to, or 0 when not a reply to an existing message.', 4);
        } elseif(isset($parameters['timestamp']) && !preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $parameters['timestamp'])) {
            throw new \Exception('Invalid optional parameter (string) "timestamp", which should be in the format YYYY-MM-DD HH:mm:ss', 5);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Updates this comment to the given message
     *
     * @param array $parameters
     *              * string message - The updated text for the comment
     * @return boolean
     */
    public function updateComment($parameters) {
        $db     = \Helper::getDB();
        $data   = array(
            // Correct line breaks
            'message'       => str_replace('\n', "\n", $db->escape($parameters['message'])),
            // Insert update timestamp
            'editTimestamp' => $db->escape(date('Y-m-d H:i:s'))
        );
        $db->where('id', $db->escape($this->comment->getId()));
        $result = $db->update('comments', $data);

        return $result;
    }

    /**
     * Validates the parameters for updating a comment
     *
     * @param array $parameters
     * @return boolean
     */
    public function validateParametersUpdate($parameters) {
        $result = FALSE;
        if(!isset($parameters['message']) || strlen($parameters['message']) < 1) {
            throw new \Exception('Missing parameter (string) "message" which should be atleast one character long.', 3);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}