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
        $this->api->addRoute("/^\/comments\/([a-z]+)\/(\d+)\/?$/",                         "getComments",         $this, "GET",    \Auth::READ);    // Get list with comments
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

        if($args[1] == 'document') {
            $parent = new \Models\Document($id);
        } elseif($args[1] == 'slide') {
            $parent = new \Models\Slide($args[2], 1, '');
        }else {
            $parent = FALSE;
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
}