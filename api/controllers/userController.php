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
     * Make sure Remote PC is enabled in OpenSim and the method admin_create_user is allowed
     *
     * @param array $parameters - Array with parameters used to create the user
     *              * string $firstName - The user's first name
     *              * string $lastName - The user's last name
     *              * string $email - the user's email address
     *              * string $password - the password for the user
     *              * integer $start_region_x - [Optional] region x coordinate, default 0
     *              * integer $start_region_y - [Optional] region y coordinate, default 0
     * @return xml
     */
    public function createUser($parameters) {
        $raXML = new OpenSimRPC();
        $parameters = array(
            'password'          => OS_REMOTE_ADMIN_PASSWORD,
            'user_firstname'    => $parameters['firstName'],
            'user_lastname'     => $parameters['lastName'],
            'user_password'     => $parameters['password'],
            'user_email'        => $parameters['email'],
            'start_region_x'    => (isset($parameters['startRegionX']) ? $parameters['startRegionX'] : 0),
            'start_region_y'    => (isset($parameters['startRegionY']) ? $parameters['startRegionY'] : 0)
        );

        $result = $raXML->call('admin_create_user', $parameters);

        return $result;
    }

    /**
     * Checks if the given parameters are valid for a good request
     *
     * @param array $parameters - See createUser()
     * @return boolean
     * @throws Exception
     */
    public static function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) < 4 && count($parameters) > 6) {
            throw new Exception("Invalid number of parameters, uses 4 to 6 parameters", 4);
        } elseif(!isset($parameters['firstName'])) {
            throw new Exception("Missing parameter (string) firstName", 5);
        } elseif(!isset($parameters['lastName'])) {
            throw new Exception("Missing parameter (string) lastName", 6);
        } elseif(!isset($parameters['password'])) {
            throw new Exception("Missing parameter (string) password", 7);
        } elseif(!isset($parameters['email'])) {
            throw new Exception("Missing parameter (string) email", 8);
        } elseif(isset($parameters['startRegionY']) && (!isset($parameters['startRegionX']) || !is_numeric($parameters['startRegionX']))) {
            throw new Exception("Missing parameter (integer) startRegionX", 9);
        } elseif(isset($parameters['startRegionX']) && (!isset($parameters['startRegionY']) || !is_numeric($parameters['startRegionY']))) {
            throw new Exception("Missing parameter (integer) startRegionY", 10);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Teleports a user to the given position with the given view
     *
     * @param array $parameters - The parameters to be used by this function
     *              * string $agentUuid - The user's UUID
     *              * string $firstName - [Optional] The user's first name
     *              * string $lastName - [Optional] The user's last name
     *              * string $regionName - [Optional] The region name, default from config.php
     *              * float $posX - [Optional] The user's new position x, default: 128
     *              * float $posY - [Optional] The user's new position y, default: 128
     *              * float $posZ - [Optional] The user's new position z, default: 25
     *              * float $lookAtX - [Optional] The user's new view z, default: 0
     *              * float $lookAtY - [Optional] The user's new view z, default: 0
     *              * float $lookAtZ - [Optional] The user's new view z, default: 0
     * @return XML
     */
    public function teleportUser($parameters) {
        $raXML = new OpenSimRPC();
        $parameters = array(
            'password'          => OS_REMOTE_ADMIN_PASSWORD,
            'region_name'       => (isset($parameters['regionName']) ? $parameters['regionName'] : OS_DEFAULT_REGION),
            'agent_id'          => $parameters['agentUuid'],
            // Needed to be cast to string, server can't cast from php/xml float to c# float...
            'pos_x'             => (string) (isset($parameters['posX']) ? $parameters['posX'] : 128),
            'pos_y'             => (string) (isset($parameters['posY']) ? $parameters['posY'] : 128),
            'pos_z'             => (string) (isset($parameters['posZ']) ? $parameters['posZ'] : 25),
            'lookat_x'          => (string) (isset($parameters['lookAtX']) ? $parameters['lookAtX'] : 0),
            'lookat_y'          => (string) (isset($parameters['lookAtY']) ? $parameters['lookAtY'] : 0),
            'lookat_z'          => (string) (isset($parameters['lookAtZ']) ? $parameters['lookAtZ'] : 0)
        );

        // Only when set
        if(isset($parameters['$firstName']) && isset($parameters['$lastName'])) {
            $parameters['agent_first_name']  = $parameters['$firstName'];
            $parameters['agent_last_name']   = $parameters['$lastName'];
        }

        $result = $raXML->call('admin_teleport_agent', $parameters);

        return $result;
    }

    /**
     * Validates the parameters in the array
     *
     * @param array $parameters - List with teleport parameters, see teleportUser()
     * @return boolean
     * @throws Exception
     */
    public static function validateParametersTeleport($parameters) {
        $result = FALSE;
        if(count($parameters) < 1 && count($parameters) > 10) {
            throw new Exception("Invalid number of parameters, uses 1 to 10 parameters", 11);
        } elseif(!isset($parameters['agentUuid']) || !Helper::isValidUuid($parameters['agentUuid'])) {
            throw new Exception("Missing valid UUID for parameter (string) agentUuid", 13);
        } elseif(isset($parameters['lastName']) && $parameters['lastName'] != '' && !isset($parameters['firstName'])) {
            throw new Exception("Missing parameter (string) firstName", 14);
        } elseif(isset($parameters['firstName']) && $parameters['firstName'] != '' && !isset($parameters['lastName'])) {
            throw new Exception("Missing parameter (string) lastName", 15);
        } elseif((isset($parameters['posY']) || isset($parameters['posZ'])) && (!isset($parameters['posX']) || !is_numeric($parameters['posX']))) {
            throw new Exception("Missing parameter (float) posX", 16);
        } elseif((isset($parameters['posX']) || isset($parameters['posZ'])) && (!isset($parameters['posY']) || !is_numeric($parameters['posY']))) {
            throw new Exception("Missing parameter (float)  posY", 17);
        } elseif((isset($parameters['posX']) || isset($parameters['posY'])) && (!isset($parameters['posZ']) || !is_numeric($parameters['posZ']))) {
            throw new Exception("Missing parameter (float)  poZ", 18);
        } elseif((isset($parameters['lookatY']) || isset($parameters['lookatZ'])) && (!isset($parameters['lookatX']) || !is_numeric($parameters['lookatX']))) {
            throw new Exception("Missing parameter (float)  lookatX", 19);
        } elseif((isset($parameters['lookatX']) || isset($parameters['lookatZ'])) && (!isset($parameters['lookatY']) || !is_numeric($parameters['lookatY']))) {
            throw new Exception("Missing parameter (float)  lookatY", 20);
        } elseif((isset($parameters['lookatX']) || isset($parameters['lookatY'])) && (!isset($parameters['lookatZ']) || !is_numeric($parameters['lookatZ']))) {
            throw new Exception("Missing parameter (float)  lookatZ", 21);
        } else {
            $result = TRUE;
        }
        return $result;
    }
}
