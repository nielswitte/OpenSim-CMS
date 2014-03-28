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
        $this->api->addRoute("/^\/comments\/([a-z]+)\/(\d+)\/?$/",                         "getComments",         $this, "GET",    \Auth::READ);    // Get list with 50 comments
        $this->api->addRoute("/^\/comments\/([a-z]+)\/(\d+)\/(\d+)\/?$/",                  "getComments",         $this, "GET",    \Auth::READ);    // Get list with 50 comments starting at the given offset
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
        $offset = isset($args[3]) ? $args[3] : 0;

        if($args[1] == 'document') {
            $parent = new \Models\Document($id);
        } elseif($args[1] == 'slide') {
            $parent = new \Models\Slide($args[2], 1, '');
        }else {
            $parent = FALSE;
            throw new \Exception('Type not implemented yet', 1);
        }

        $comments = new \Models\Comments($parent);
        $comments->getInfoFromDatabase($offset, 50);

        return $this->getCommentData($comments);
    }

    /**
     * Formats the comments to a nice array which can be converted to the output format
     *
     * @param \Models\Comments $comments
     * @return array
     */
    public function getCommentData(\Models\Comments $comments) {
        $data = array();
        foreach($comments->getComments() as $comment) {
            $data[] = array(
                'id'        => $comment->getId(),
                'user'      => array(
                    'id'        => $comment->getUser()->getId(),
                    'username'  => $comment->getUser()->getUsername(),
                    'firstName' => $comment->getUser()->getFirstName(),
                    'lastName'  => $comment->getUser()->getLastName(),
                    'email'     => $comment->getUser()->getEmail()
                ),
                'timestamp' => $comment->getTimestamp(),
                'message'   => $comment->getMessage()
            );
        }

        return $data;
    }
}