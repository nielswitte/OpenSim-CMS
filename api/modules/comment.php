<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/comments.php';
require_once dirname(__FILE__) .'/../controllers/commentController.php';

/**
 * Implements the functions for comments
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since March 28th, 2014
 */
class Comment extends Module {
    /**
     * The API to add this module to
     * @var \API\API
     */
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('comment');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/^\/comments\/([a-z]+)\/(\d+)\/?$/",                      'getComments',           $this, 'GET',       \Auth::READ);    // Get list with comments
        $this->api->addRoute("/^\/comment\/([a-z]+)\/(\d+)\/?$/",                       'createComment',         $this, 'POST',      \Auth::EXECUTE); // Create a new comment
        $this->api->addRoute("/^\/comment\/(\d+)\/?$/",                                 'updateCommentById',     $this, 'PUT',       \Auth::READ);    // Updates the given comment
        $this->api->addRoute("/^\/comment\/(\d+)\/?$/",                                 'deleteCommentById',     $this, 'DELETE',    \Auth::READ);    // Removes the given comment
    }

    /**
     * Gets the comments for the given type with the given id
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getComments($args) {
        $id     = $args[2];

        // Get comment type instance
        $parent = \Helper::getCommentType($args[1], $id);
        if($parent === FALSE) {
            throw new \Exception('Type not implemented yet', 1);
        }

        $comments = new \Models\Comments($parent);
        $comments->getInfoFromDatabase();

        return $this->getCommentsData($comments);
    }

    /**
     * Used for formatting the root Level
     *
     * @param \Models\Comments $comments
     * @return array
     */
    public function getCommentsData(\Models\Comments $comments) {
        $data = array();
        foreach($comments->getCommentsThreaded($comments->getComments()) as $comment) {
            $data["comments"][] = array(
                'id'            => $comment->getId(),
                'parentId'      => $comment->getParentId(),
                'number'        => $comment->getNumber(),
                'user'          => array(
                    'id'        => $comment->getUser()->getId(),
                    'username'  => $comment->getUser()->getUsername(),
                    'firstName' => $comment->getUser()->getFirstName(),
                    'lastName'  => $comment->getUser()->getLastName(),
                    'email'     => $comment->getUser()->getEmail()
                ),
                'timestamp'     => $comment->getTimestamp(),
                'editTimestamp' => $comment->getEditTimestamp(),
                'message'       => $comment->getMessage(),
                'childrenCount' => count($comment->getChildren()),
                'children'      => $this->getCommentData($comment)
            );
        }
        $data['commentCount']   = $comments->getCommentCount();
        if($comments->getParentParent()) {
            $data['parentId']   = $comments->getParentParent()->getId();
        }

        return $data;
    }

    /**
     * Formats the children comments
     *
     * @param \Models\Comment $commentParent
     * @return array
     */
    public function getCommentData(\Models\Comment $commentParent) {
        $data = array();
        foreach($commentParent->getChildren() as $comment) {
            $data[] = array(
                'id'            => $comment->getId(),
                'parentId'      => $comment->getParentId(),
                'number'        => $comment->getNumber(),
                'user'          => array(
                    'id'        => $comment->getUser()->getId(),
                    'username'  => $comment->getUser()->getUsername(),
                    'firstName' => $comment->getUser()->getFirstName(),
                    'lastName'  => $comment->getUser()->getLastName(),
                    'email'     => $comment->getUser()->getEmail()
                ),
                'timestamp'     => $comment->getTimestamp(),
                'editTimestamp' => $comment->getEditTimestamp(),
                'message'       => $comment->getMessage(),
                'childrenCount' => count($comment->getChildren()),
                'children'      => $this->getCommentData($comment)
            );
        }

        return $data;
    }

    /**
     * Creates a new comment for the given type and id
     * with the post parameters.
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function createComment($args) {
        $type = \Helper::getCommentType($args[1], $args[2]);
        $data = FALSE;
        if($type !== FALSE) {
            $parameters = \Helper::getInput(TRUE);
            $parameters['type']     = $args[1];
            $parameters['itemId']   = $args[2];
            $commentCtrl = new \Controllers\CommentController();
            // Validate parameters
            if($commentCtrl->validateParametersCreate($parameters)) {
                // Create comment
                $data = $commentCtrl->createComment($parameters);
            }
        } else {
            throw new \Exception('Type not implemented yet', 1);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'commentId' => ($data !== FALSE ? $data : 0)
        );
        return $result;
    }

    /**
     * Updates the given comment message
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateCommentById($args) {
        $data = FALSE;
        // Get commnet data
        $db         = \Helper::getDB();
        $db->where('id', $db->escape($args[1]));
        $query      = $db->getOne('comments');
        // Comment exists
        if($query) {
            $user       = new \Models\User($query['userId']);
            $comment    = new \Models\Comment($query['id'], $query['parentId'], 1, $user, $query['type'], $query['timestamp'], $query['message']);

            // Only allow when the user has write access or wants to update his/her own comment
            if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $comment->getUser()->getId() != \Auth::getUser()->getId()) {
                throw new \Exception('You do not have permissions to update this comment.', 6);
            }
            $commentCtrl = new \Controllers\CommentController($comment);
            $parameters  = \Helper::getInput(TRUE);
            // Validate parameters
            if($commentCtrl->validateParametersUpdate($parameters)) {
                // Update comment
                $data = $commentCtrl->updateComment($parameters);
            }
        } else {
            throw new \Exception('Cound not find comment with ID: '. $args[1], 2);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE)
        );
        return $result;
    }

    /**
     * Removes the given comment from the database
     * WARNING: This will also remove all responses to this comment (CASCADE)
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function deleteCommentById($args) {
        $data = FALSE;
        // Get commnet data
        $db         = \Helper::getDB();
        $db->where('id', $db->escape($args[1]));
        $query     = $db->getOne('comments');
        if($query) {
            $user       = new \Models\User($query['userId']);
            $comment    = new \Models\Comment($query['id'], $query['parentId'], 1, $user, $query['type'], $query['timestamp'], $query['message']);

            // Only allow when the user has write access or wants to update his/her own comment
            if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $comment->getUser()->getId() != \Auth::getUser()->getId()) {
                throw new \Exception('You do not have permissions to remove this comment.', 6);
            }

            // Delete!
            $commentCtrl = new \Controllers\CommentController($comment);
            $data        = $commentCtrl->removeComment();
        } else {
            throw new \Exception('Cound not find comment with ID: '. $args[1], 2);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE)
        );
        return $result;
    }
}