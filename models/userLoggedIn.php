<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/user.php';

/**
 * This class is the user model for a logged in user
 * It adds additional functions for the user to support logging in and out
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 19th, 2014
 */
class UserLoggedIn extends User implements SimpleModel {
    /**
     * Creates a new user with the given ID and UUID
     *
     * @param integer $id - [Optional]
     * @param string $userUUID - [Optional]
     */
    public function __construct($id = -1, $userUUID = '') {
        parent::__construct($id, $userUUID);
    }

    /**
     * Checks if the given the user has the required rights
     *
     * @param string $module
     * @param integer $rightsRequired
     * @return boolean
     */
    public function checkRights($module, $rightsRequired) {
        $rights = $this->getRights();
        $rightsAvailable = $rights[$module];

        return $rightsAvailable >= $rightsRequired;
    }
}