<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/simpleModel.php';
require_once dirname(__FILE__) .'/comment.php';

/**
 * This class handles comments on a given object
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since March 28th, 2014
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
     */
    public function getInfoFromDatabase() {
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
            $db->orderBy('timestamp', 'ASC');

            $results = $db->get('comments c', NULL, '*, u.id as userId, c.id as commentId');
            // Found results?
            if(isset($results[0])) {
                $number = 1;
                // Save all comments
                foreach($results as $result) {
                    // The author of the comment
                    $user    = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName']);
                    // Create comment
                    $comment = new \Models\Comment($result['commentId'], $result['parentId'], $number, $user, $result['type'], $result['timestamp'], $result['message'], $result['editTimestamp']);
                    // Is a reaction on another comment?
                    if($comment->getParentId() !== NULL) {
                        $parentComment = $this->getCommentById($result['parentId']);
                        // Parent comment found?
                        if($parentComment !== FALSE) {
                            $parentComment->addChild($comment);
                        }
                    }
                    $number++;
                    // Add to comments
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
     * Get comments as a flat array, which does include the parent/child objects but outputs the comments
     * as a flat list
     *
     * @return array
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Returns a threaded array with the comments,
     * actually only returns the main level, the sub levels need to be accessed by using the
     * getChildren() function
     *
     * @return array
     */
    public function getCommentsThreaded($commentsList) {
        $comments = array();
        foreach($commentsList as $comment) {
            if($comment->getParentId() == NULL) {
                $comments[] = $comment;
            }
        }

        return $comments;
    }

    /**
     * Search the comments for a comment with the given ID
     *
     * @param integer $id
     * @return \Models\Comment or boolean FALSE when no comment found with the given id
     */
    private function getCommentById($id) {
        foreach($this->getComments() as $comment) {
            if($comment->getId() == $id) {
                return $comment;
            }
        }
        return FALSE;
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
