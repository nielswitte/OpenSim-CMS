<?php
namespace Models;

defined('EXEC') or die('Invalid request');

/**
 * This class represents a single comment
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since March 28st, 2014
 */
class Comment {
    /**
     * The ID of the comment
     * @var integer
     */
    private $id;
    /**
     * The ID this item is a subitem from
     * @var integer
     */
    private $parentId;
    /**
     * The number of the comment (first comment = 1)
     * @var integer
     */
    private $number;
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
    private $timestamp;
    /**
     * The actual comment
     * @var string
     */
    private $msg;
    /**
     * Datetime string YYYY-MM-DD HH:mm:ss, for when the message was last edited
     * @var string
     */
    private $editTimestamp;
    /**
     * The parent comment
     * @var \Models\Comment
     */
    private $parent;
    /**
     * List with the reactions on this comment
     * @var array
     */
    private $children = array();

    /**
     * Constructs a new comment
     *
     * @param integer $id
     * @param integer $parentId
     * @param integer $number
     * @param \Models\User $user
     * @param string $type
     * @param string $timestamp
     * @param string $msg
     * @param string $editTimestamp - [Optional]
     */
    public function __construct($id, $parentId, $number, \Models\User $user, $type, $timestamp, $msg, $editTimestamp = NULL) {
        $this->id               = $id;
        $this->parentId         = $parentId;
        $this->number           = $number;
        $this->user             = $user;
        $this->type             = $type;
        $this->timestamp        = $timestamp;
        $this->msg              = $msg;
        $this->editTimestamp    = $editTimestamp;
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
     * Returns the parent comment's ID
     *
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * Returns the number of this comment. Which represents the order of posting
     *
     * @return integer
     */
    public function getNumber() {
        return $this->number;
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
     * Returns the timestamp when this comment was posted
     *
     * @return string - YYYY-MM-DD HH:mm:ss
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Returns the actual comment message
     *
     * @return string
     */
    public function getMessage() {
        return $this->msg;
    }

    /**
     * Returns the datetime when the comment was last edited
     *
     * @return string or NULL when not set - YYYY-MM-DD HH:mm:ss
     */
    public function getEditTimestamp() {
        return $this->editTimestamp;
    }

    /**
     * Sets the parent of this commnet
     *
     * @param \Models\Comment $parent
     */
    public function setParent(\Models\Comment $parent) {
        $this->parent = $parent;
    }

    /**
     * Returns the parent of this comment
     *
     * @return \Models\Comment
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Adds the child to the comment list and sets the parent of the child to this comment
     *
     * @param \Models\Comment $child
     */
    public function addChild(\Models\Comment $child) {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Returns all children (reactions) on this comment
     *
     * @return array
     */
    public function getChildren() {
        return $this->children;
    }
}
