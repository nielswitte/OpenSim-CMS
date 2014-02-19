<?php
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
class UserLoggedIn extends User {
    /**
     * Creates a new user with the given ID and UUID
     *
     * @param integer $id - [Optional]
     * @param string $userUUID - [Optional]
     */
    public function __construct($id = 0, $userUUID = '') {
        parent::__construct($id, $userUUID);
        $this->getInfoFromDatabase();
    }
}