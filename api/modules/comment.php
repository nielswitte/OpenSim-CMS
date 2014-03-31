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
 * @version 0.1
 * @date March 31st, 2014
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
        $this->api->addRoute("/^\/comments\/([a-z]+)\/(\d+)\/?$/",                      "getComments",           $this, "GET",       \Auth::READ);    // Get list with comments
        $this->api->addRoute("/^\/comment\/([a-z]+)\/(\d+)\/?$/",                       "createComment",         $this, "POST",      \Auth::EXECUTE); // Create a new comment
        $this->api->addRoute("/^\/comment\/(\d+)\/?$/",                                 "deleteCommentById",     $this, "DELETE",    \Auth::READ);    // Removes the given comment
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
                'message'       => $comment->getMessage(),
                'childrenCount' => count($comment->getChildren()),
                'children'      => $this->getCommentData($comment)
            );
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
                'timestamp'     => $comment->getTimestamp(),
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
        if($type !== FALSE) {
            $parameters = \Helper::getInput(TRUE);
            $commentCtrl = new \Controllers\CommentController();
            if($commentCtrl->validateParametersCreate($parameters)) {
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
     * Removes the given comment from the database
     * WARNING: This will also remove all responses to this comment (CASCADE)
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function deleteCommentById($args) {
        // Get commnet data
        $db         = \Helper::getDB();
        $db->where('id', $db->escape($args[1]));
        $result     = $db->get('comments');
        if(isset($result[0])) {
            $user       = new \Models\User($result[0]['userId']);
            $comment    = new \Models\Comment($result[0]['id'], $result[0]['parentId'], 1, $user, $result[0]['type'], $result[0]['timestamp'], $result[0]['message']);

            // Only allow when the user has write access or wants to update his/her own comment
            if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $comment->getUser()->getId() != \Auth::getUser()->getId()) {
                throw new \Exception('You do not have permissions to update this user.', 6);
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