<?php
namespace Models;

defined('EXEC') or die('Invalid request');

/**
 * This class represents a single comment
 *
 * @author Niels Witte
 * @version 0.1
 * @since March 28st, 2014
 */
class Comment {
    /**
     * The ID of the comment
     * @var integer
     */
    private $id;
    /**
     * The user instance of the author of this message
     * @var \Models\User
     */
    private $user;
    /**
     * The type of comment, for example 'document'
     * @var string
     */
    private $type;
    /**
     * Datetime string YYYY-MM-DD HH:mm:ss
     * @var string
     */
    private $date;
    /**
     * The actual comment
     * @var string
     */
    private $msg;

    /**
     * Constructs a new comment
     *
     * @param integer $id
     * @param \Models\User $user
     * @param string $type
     * @param string $date
     * @param string $msg
     */
    public function __construct($id, \Models\User $user, $type, $date, $msg) {
        $this->id   = $id;
        $this->user = $user;
        $this->type = $type;
        $this->date = $date;
        $this->msg  = $msg;
    }

    /**
     * Returns the ID of this comment
     *
     * @return integer
     */
    public function getId() {
       return $this->id;
    }

    /**
     * Returns the user instance of the author
     *
     * @return \Models\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * The type of comment, for example a 'document' comment
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns the date when this comment was posted
     *
     * @return string
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Returns the actual comment message
     *
     * @return string
     */
    public function getMessage() {
        return $this->msg;
    }
}
