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
     * @param string $username - User name to match
     * @param string $uuid - UUID to use
     * @return boolean
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
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @param integer $start_region_x
     * @param integer $start_region_y
     * @return boolean
     */
    public function createUser($firstName, $lastName, $email, $password, $start_region_x = 128, $start_region_y = 128) {
        $raXML = new OpenSimRPC();
        $parameters = array(
            'password'          => OS_REMOTE_ADMIN_PASSWORD,
            'user_firstname'    => $firstName,
            'user_lastname'     => $lastName,
            'user_password'     => $password,
            'user_email'        => $email,
            'start_region_x'    => $start_region_x,
            'start_region_y'    => $start_region_y
        );

        $result = $raXML->call('admin_create_user', $parameters);

        return $result;
    }

    /**
     * Checks if the given parameters are valid for a good request
     *
     * @param array $parameters
     * @return boolean
     * @throws Exception
     */
    public static function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) != 6) {
            throw new Exception("Invalid number of parameters", 4);
        } elseif(!isset($parameters['firstName'])) {
            throw new Exception("Missing parameter firstName", 5);
        } elseif(!isset($parameters['lastName'])) {
            throw new Exception("Missing parameter lastName", 6);
        } elseif(!isset($parameters['password'])) {
            throw new Exception("Missing parameter password", 7);
        } elseif(!isset($parameters['email'])) {
            throw new Exception("Missing parameter email", 8);
        } elseif(!isset($parameters['startRegionX'])) {
            throw new Exception("Missing parameter startRegionX", 9);
        } elseif(!isset($parameters['startRegionY'])) {
            throw new Exception("Missing parameter startRegionY", 10);
        } else {
            $result = TRUE;
        }
        return $result;
    }
}
