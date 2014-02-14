<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the user controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 12th, 2014
 */
class UserController {
    private $user;

    /**
     * Constructs a new controller for the given user
     * 
     * @param User $user
     */
    public function __construct(User $user = NULL) {
        $this->user = $user;
    }

    /**
     * Sets the user's UUID and matches it to the username
     *
     * @param String $username - User name to match
     * @param String $uuid - UUID to use
     * @return Boolean
     * @throws Exception
     */
    public function setUuid($username, $uuid) {
        $results = FALSE;
        if(Helper::isValidUuid($uuid)) {
            $db = Helper::getDB();
            // Check if UUID not in use
            $db->where("Uuid", $db->escape($uuid));
            $user = $db->get("users", 1);

            // Not used?
            if(empty($user)) {
                $updateData = array(
                    'uuid'          => $db->escape($uuid)
                );
                $db->where('userName', $db->escape($username));
                $results = $db->update('users', $updateData);
            } else {
                throw new Exception("UUID already in use, used by: ". $user[0]['userName'], 3);
            }
        } else {
            throw new Exception("Invalid UUID provided", 2);
        }

        if(!$results) {
            throw new Exception("Updating UUID failed, check username", 1);
        }
        return $results;
    }


    /**
     * Creates a new user with the given parameters
     *
     * @param String $firstName
     * @param String $lastName
     * @param String $email
     * @param String $password
     * @param Integer $start_region_x
     * @param Integer $start_region_y
     * @return Boolean
     */
    public function createUser($firstName, $lastName, $email, $password, $start_region_x = 128, $start_region_y = 128) {
        $raXML = new OpenSimRPC();
        $parameters = array(
            'user_firstname'    => $firstName,
            'user_lastname'     => $lastName,
            'user_password'     => $password,
            'user_email'        => $email,
            'start_region_x'    => $start_region_x,
            'start_region_y'    => $start_region_y
        );

        $result = $raXML->call(OS_REMOTE_ADMIN_PASSWORD, $parameters);

        return $result;
    }
}
