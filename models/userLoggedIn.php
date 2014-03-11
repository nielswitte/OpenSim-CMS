<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}

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
    private $rights = array();

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
     * Gets the users rights
     *
     * @return array
     */
    public function getRights() {
        if(empty($this->rights)) {
            // Default rights
            $this->rights = array(
                'auth'              => (int) \Auth::NONE, // 0
                'document'          => (int) \Auth::NONE, // 0
                'grid'              => (int) \Auth::NONE, // 0
                'meeting'           => (int) \Auth::NONE, // 0
                'meetingroom'       => (int) \Auth::NONE, // 0
                'presentation'      => (int) \Auth::NONE, // 0
                'user'              => (int) \Auth::NONE  // 0
            );

            // Get user's permissions from DB
            $db = \Helper::getDB();
            $db->where('userId', $db->escape($this->getId()));
            $result = $db->get('user_permissions', 1);

            if(isset($result[0])) {
                $this->rights = array(
                    'auth'              => $result[0]['auth'],
                    'document'          => $result[0]['document'],
                    'grid'              => $result[0]['grid'],
                    'meeting'           => $result[0]['meeting'],
                    'meetingroom'       => $result[0]['meetingroom'],
                    'presentation'      => $result[0]['presentation'],
                    'user'              => $result[0]['user'],
                );
            }
        }
        return $this->rights;
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