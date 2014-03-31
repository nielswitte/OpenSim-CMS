<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Comment controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 31st, 2014
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
     * Creates the new comment
     *
     * @param array $parameters
     * @return integer commentId or boolean FALSE on failure
     */
    public function createComment($parameters) {
        return FALSE;
    }

    /**
     * Checks if the create parameters for a new comment are correct
     *
     * @param array $parameters
     * @return boolean
     */
    public function validateParametersCreate($parameters) {
        return FALSE;
    }
}