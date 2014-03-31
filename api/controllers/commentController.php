<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Comment controller
 *
 * @author Niels Witte
 * @version 0.1
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
}