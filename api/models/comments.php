<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/simpleModel.php';
require_once dirname(__FILE__) .'/comment.php';

/**
 * This class handles comments on a given object
 *
 * @author Niels Witte
 * @version 0.1
 * @since March 28st, 2014
 */
class Comments implements SimpleModel {
    /**
     * The class instance to which these comments are linked, for example a Document
     * @var object
     */
    private $parent;
    /**
     * An array which contains the comments
     * @var array
     */
    private $comments;

    /**
     * The instance to which these comments are linked
     *
     * @param object $parent
     * @param array $comments
     */
    public function __construct($parent, $comments = array()) {
        $this->parent   = $parent;
        $this->comments = $comments;
    }

    /**
     * Gets all or partial selection of comments from the database and adds them to the list
     *
     * @param integer $offset - [Optional] Starting point for retrieving data
     * @param integer $limit - [Optional] Number of items to get from database
     */
    public function getInfoFromDatabase($offset = 0, $limit = 0) {
        // Parent is a Document?
        if($this->getParent() instanceof \Models\Document) {
            $type = 'document';
        } elseif($this->getParent() instanceof \Models\Slide) {
            $type = 'slide';
        } else {
            $type = FALSE;
        }

        // Valid type is used?
        if($type !== FALSE) {
            // Get comments and users from database
            $db = \Helper::getDB();
            $db->join('users u', 'c.userId = u.id', 'LEFT');
            $db->where('type', $db->escape($type));
            $db->where('itemId', $db->escape($this->getParent()->getId()));
            $db->orderBy('timestamp', 'DESC');

            // Determine the limit
            if($limit > 0) {
                $limit = array($db->escape($offset), $db->escape($limit));
            } else {
                $limit = null;
            }

            $results = $db->get('comments c', $limit, '*, u.id as userId, c.id as commentId');
            // Found results?
            if(isset($results[0])) {

                // Save all comments
                foreach($results as $result) {
                    $user    = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
                    $comment = new \Models\Comment($result['commentId'], $user, $result['type'], $result['timestamp'], $result['message']);
                    $this->addComment($comment);
                }
            }
        }
    }

    /**
     * Returns the instance to which the comments are linked
     *
     * @return object
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Adds a comment to this class
     *
     * @param \Models\Comment $comment
     */
    public function addComment(\Models\Comment $comment) {
        $this->comments[] = $comment;
    }

    /**
     * Get comments
     *
     * @return array
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Gets the number of comments
     *
     * @return integer
     */
    public function getCommentCount() {
        return count($this->getComments());
    }
}
