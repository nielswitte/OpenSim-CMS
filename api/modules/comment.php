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
 * @version 0.3
 * @date April 7th, 2014
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
        $this->api->addRoute("/^\/comments\/(\d+)\/?$/",                                'getCommentsByTime',     $this, 'GET',       \Auth::READ);    // Get 50 comments after a given timestamp
        $this->api->addRoute("/^\/comments\/(\d+)\/(\d+)\/?$/",                         'getCommentsByTime',     $this, 'GET',       \Auth::READ);    // Get 50 comments after a given timestamp with offset
        $this->api->addRoute("/^\/comment\/([a-z]+)\/(\d+)\/?$/",                       'createComment',         $this, 'POST',      \Auth::EXECUTE); // Create a new comment
        $this->api->addRoute("/^\/comment\/(\d+)\/?$/",                                 'updateCommentById',     $this, 'PUT',       \Auth::READ);    // Updates the given comment
        $this->api->addRoute("/^\/comment\/(\d+)\/?$/",                                 'deleteCommentById',     $this, 'DELETE',    \Auth::READ);    // Removes the given comment
        $this->api->addRoute("/^\/comment\/(\d+)\/parents\/?$/",                        'getCommentParentsById', $this, 'GET',       \Auth::READ);    // Gets a list containing the parent id path to the given comment
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
     * Returns all comments since the given timestamp as a flat list (not threaded)
     *
     * @param array $args
     * @return array
     */
    public function getCommentsByTime($args) {
        $offset     = isset($args[2]) ? $args[2] : 0;
        $db         = \Helper::getDB();
        $db->where('c.timestamp', array('>=' => date('Y-m-d H:i:s', $args[1])));
        $db->join('users u', 'c.userId = u.id', 'LEFT');
        $db->orderBy('c.timestamp', 'DESC');
        $results    = $db->get('comments c', array($offset, 50), '*, c.id AS commentID, u.id AS userId');
        $comments   = new \Models\Comments(NULL);
        $number     = 1;
        foreach($results as $result) {
            $user    = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $comment = new \Models\Comment($result['id'], $result['parentId'], $number, $user, $result['type'], $result['timestamp'], $result['message'], $result['editTimestamp']);
            $comments->addComment($comment);
            $number++;
        }

        return $this->getCommentsData($comments, TRUE);
    }

    /**
     * Returns a list which represents the path from parent to child for this comment
     * @example for a comment with ID 6 which is of type slide it will return [0] => presentation, [1] => presentationId, [2] => slide, [3] => slideId
     *
     * @param array $args
     * @return array
     */
    public function getCommentParentsById($args) {
        //@todo implement

        $data = array(
            'document',
            1,
            'page',
            2
        );

        return $data;
    }

    /**
     * Used for formatting the root Level
     *
     * @param \Models\Comments $comments
     * @param boolean $flat - [Optional] Returns the comments as a flat list
     * @return array
     */
    public function getCommentsData(\Models\Comments $comments, $flat = FALSE) {
        $data = array();

        // Flat list or threaded?
        if($flat) {
            $commentsList = $comments->getComments();
        } else {
            $commentsList = $comments->getCommentsThreaded($comments->getComments());
        }

        // Process list
        foreach($commentsList as $comment) {
            $commentdata = array(
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
                'type'          => $comment->getType(),
                'timestamp'     => $comment->getTimestamp(),
                'editTimestamp' => $comment->getEditTimestamp(),
                'message'       => $comment->getMessage()
            );
            // Flat list or get children?
            if(!$flat) {
                $commentdata['childrenCount'] = count($comment->getChildren());
                $commentdata['children']      = $this->getCommentData($comment);
            }
            $data['comments'][] = $commentdata;
        }
        $data['commentCount']   = $comments->getCommentCount();

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
                'type'          => $comment->getType(),
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