<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the group model
 * Which adds users and files to a group
 *
 * @author Niels Witte
 * @version 0.1
 * @since April 17th, 2014
 */
class Group implements SimpleModel {
    /**
     * The groupId
     * @var integer
     */
    private $id;
    /**
     * The group name
     * @var string
     */
    private $name;
    /**
     * List containing all group members as \Models\User
     * @var array
     */
    private $users = array();
    /**
     * A list containing all files for this group as \Models\File
     * @var array
     */
    private $files = array();

    /**
     * Creates a group with ID and optionally a name
     *
     * @param integer $id
     * @param string $name - [Optional]
     */
    public function __construct($id, $name = '') {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Retrieves the group name from the database
     *
     * @throws \Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->getId()));
        $result = $db->getOne('groups');
        if($result) {
            $this->name = $result->name;
        } else {
            throw new \Exception('Group does not exist', 1);
        }
    }

    /**
     * Retrieves the list with all group users from the database
     */
    public function getGroupUsersFromDatabase() {
        $db = \Helper::getDB();
        $db->where('g.groupId', $db->escape($this->getId()));
        $db->join('users u', 'u.id = g.userId', 'LEFT');
        $results = $db->get('group_users g', NULL, 'u.*');
        // Process all users
        foreach($results as $user) {
            $user = new \Models\User($user['id'], $user['username'], $user['email'], $user['firstName'], $user['lastName'], $user['lastLogin']);
            $this->addUser($user);
        }
    }

    /**
     * Returns the list with files attached to this group
     */
    public function getGroupFilesFromDatabase() {
        $db = \Helper::getDB();
        $db->where('g.groupId', $db->escape($this->getId()));
        $db->join('documents d', 'd.id = g.documentId', 'LEFT');
        $db->join('users u', 'u.id = d.ownerId', 'LEFT');
        $results = $db->get('group_files g', NULL, 'd.*, u.*, d.id AS fileId, u.id AS userId');
        // Process all files and their owners
        foreach($results as $result) {
            $user = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $file = new \Models\File($result['fileId'], $result['type'], $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
            $this->addFile($file);
        }
    }

    /**
     * Returns the group ID
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the group name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Add an user to the list with group users
     *
     * @param \Models\User $user
     */
    public function addUser(\Models\User $user) {
        $this->users[] = $user;
    }

    /**
     * Returns the array containing the user objects
     *
     * @return array
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Add a file to the list with group files
     *
     * @param \Models\File $file
     */
    public function addFile(\Models\File $file) {
        $this->files[] = $file;
    }

    /**
     * Returns an array containing the file objets
     *
     * @return array
     */
    public function getFiles() {
        return $this->files;
    }
}
