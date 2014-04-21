<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the group controller
 *
 * @author Niels Witte
 * @version 0.1
 * @since April 21st, 2014
 */
class GroupController {
    private $group;

    /**
     * Constructs a new controller for the given group
     *
     * @param \Models\Group $group
     */
    public function __construct(\Models\Group $group = NULL) {
        $this->group = $group;
    }

    /**
     * Insert the given group name to the groups database
     *
     * @param array $parameters
     *          * string name - The group name
     * @return integer with the GroupID or boolean FALSE when failed
     */
    public function createGroup($parameters) {
        $db = \Helper::getDB();
        $data = array('name' => $parameters['name']);
        return $db->insert('groups', $data);
    }

    /**
     * Validates the create parameters for creating a group
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;
        if(!isset($parameters['name']) || strlen($parameters['name']) < 1) {
            throw new \Exception('Missing parameter (string) "name" with at least one character content', 1);
        } else {
            $result = TRUE;
        }
        return $result;
    }
}