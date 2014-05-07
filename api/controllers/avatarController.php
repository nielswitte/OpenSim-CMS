<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the user controller
 *
 * @author Niels Witte
 * @version 0.2a
 * @date May 7, 2014
 * @since February 27, 2014
 */
class AvatarController {
    private $avatar;
    private $notAllowedChars =  array('?', '{', '}', '<', '>', ';', '"', ' ', '\'', '[', ']', '/', '\\');

    /**
     * Constructs a new AvatarController
     *
     * @param \Models\Avatar $avatar - [Optional] Use this avatar in the controller
     */
    public function __construct($avatar = NULL) {
        $this->avatar = $avatar;
    }

    /**
     * Creates a new avatar with the given parameters
     * Make sure Remote PC is enabled in OpenSim and the method admin_create_user is allowed
     *
     * @param array $parameters - Array with parameters used to create the user
     *              * integer $gridId - The ID of the Grid used to create this avatar in
     *              * string $firstName - The user's first name
     *              * string $lastName - The user's last name
     *              * string $email - the user's email address
     *              * string $password - the password for the user
     *              * integer $start_region_x - [Optional] region x coordinate, default 0
     *              * integer $start_region_y - [Optional] region y coordinate, default 0
     * @return xml
     */
    public function createAvatar($parameters) {
        // Retrieve grid information for remote admin
        $gridId = $parameters['gridId'];
        $grid   = new \Models\Grid($gridId);
        $grid->getInfoFromDatabase();

        // Call the Grid's remote admin
        $raXML = new \OpenSimRPC($grid->getRaUrl(), $grid->getRaPort(), $grid->getRaPassword(), 15);
        $callData = array(
            'user_firstname'    => $parameters['firstName'],
            'user_lastname'     => $parameters['lastName'],
            'user_password'     => $parameters['password'],
            'user_email'        => $parameters['email'],
            'start_region_x'    => (isset($parameters['startRegionX']) ? $parameters['startRegionX'] : 0),
            'start_region_y'    => (isset($parameters['startRegionY']) ? $parameters['startRegionY'] : 0)
        );

        $result = $raXML->call('admin_create_user', $callData);

        return $result;
    }

    /**
     * Checks if the given parameters are valid for a good request
     *
     * @param array $parameters - See createAvatar()
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) < 5 && count($parameters) > 7) {
            throw new \Exception('Invalid number of parameters, uses 5 to 7 parameters', 4);
        } elseif(!isset($parameters['gridId'])) {
            throw new \Exception('Missing parameter (integer) "gridId"', 5);
        } elseif(!isset($parameters['firstName'])) {
            throw new \Exception('Missing parameter (string) "firstName"', 6);
        } elseif(!isset($parameters['lastName'])) {
            throw new \Exception('Missing parameter (string) "lastName"', 7);
        } elseif(!isset($parameters['password'])) {
            throw new \Exception('Missing parameter (string) "password"', 8);
        } elseif(isset($parameters['password']) && $this->checkPasswordForIlligalCharacters($parameters['password'])) {
            throw new \Exception('Parameter (string) "password" contains one of the following characters: '. implode('', $this->notAllowedChars), 8);
        } elseif(!isset($parameters['email'])) {
            throw new \Exception('Missing parameter (string) "email"', 9);
        } elseif(isset($parameters['startRegionY']) && (!isset($parameters['startRegionX']) || !is_numeric($parameters['startRegionX']))) {
            throw new \Exception('Missing parameter (integer) "startRegionX"', 10);
        } elseif(isset($parameters['startRegionX']) && (!isset($parameters['startRegionY']) || !is_numeric($parameters['startRegionY']))) {
            throw new \Exception('Missing parameter (integer) "startRegionY"', 11);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Teleports a avatar to the given position with the given view
     *
     * @param array $parameters - The parameters to be used by this function
     *              * integer $gridId - The ID of the Grid where this region is part of
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
    public function teleportAvatar($parameters) {
        // Retrieve grid information for remote admin
        $gridId = $parameters['gridId'];
        $grid   = new \Models\Grid($gridId);
        $grid->getInfoFromDatabase();

        // Call the Grid's remote admin
        $raXML = new \OpenSimRPC($grid->getRaUrl(), $grid->getRaPort(), $grid->getRaPassword());
        $callData = array(
            // Get default's region name when no region is given
            'region_name'       => (isset($parameters['regionName']) ? $parameters['regionName'] : $grid->getDefaultRegion()->getName()),
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
        if(isset($parameters['firstName']) && isset($parameters['lastName'])) {
            $callData['agent_first_name']  = $parameters['firstName'];
            $callData['agent_last_name']   = $parameters['lastName'];
        }

        $result = $raXML->call('admin_teleport_agent', $callData);

        return $result;
    }

    /**
     * Validates the parameters in the array
     *
     * @param array $parameters - List with teleport parameters, see teleportUser()
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersTeleport($parameters) {
        $result = FALSE;
        if(count($parameters) < 2 && count($parameters) > 11) {
            throw new \Exception('Invalid number of parameters, uses 1 to 10 parameters', 12);
        } elseif(!isset($parameters['agentUuid']) || !\Helper::isValidUuid($parameters['agentUuid'])) {
            throw new \Exception('Missing valid UUID for parameter (string) "agentUuid"', 13);
        } elseif(isset($parameters['lastName']) && $parameters['lastName'] != '' && !isset($parameters['firstName'])) {
            throw new \Exception('Missing parameter (string) "firstName"', 14);
        } elseif(isset($parameters['firstName']) && $parameters['firstName'] != '' && !isset($parameters['lastName'])) {
            throw new \Exception('Missing parameter (string) "lastName"', 15);
        } elseif((isset($parameters['posY']) || isset($parameters['posZ'])) && (!isset($parameters['posX']) || !is_numeric($parameters['posX']))) {
            throw new \Exception('Missing parameter (float) "posX"', 16);
        } elseif((isset($parameters['posX']) || isset($parameters['posZ'])) && (!isset($parameters['posY']) || !is_numeric($parameters['posY']))) {
            throw new \Exception('Missing parameter (float) "posY"', 17);
        } elseif((isset($parameters['posX']) || isset($parameters['posY'])) && (!isset($parameters['posZ']) || !is_numeric($parameters['posZ']))) {
            throw new \Exception('Missing parameter (float) "posZ"', 18);
        } elseif((isset($parameters['lookatY']) || isset($parameters['lookatZ'])) && (!isset($parameters['lookatX']) || !is_numeric($parameters['lookatX']))) {
            throw new \Exception('Missing parameter (float) "lookatX"', 19);
        } elseif((isset($parameters['lookatX']) || isset($parameters['lookatZ'])) && (!isset($parameters['lookatY']) || !is_numeric($parameters['lookatY']))) {
            throw new \Exception('Missing parameter (float) "lookatY"', 20);
        } elseif((isset($parameters['lookatX']) || isset($parameters['lookatY'])) && (!isset($parameters['lookatZ']) || !is_numeric($parameters['lookatZ']))) {
            throw new \Exception('Missing parameter (float) "lookatZ"', 21);
        } elseif(!isset($parameters['gridId'])) {
            throw new \Exception('Missing parameter (integer) "gridId"', 22);
        }  else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Checks to see if the given fistName and lastName are already in use on the selected grid
     *
     * @param array $parameters
     *              * integer gridId - The ID of the grid in the API
     *              * string firstName - Avatars first name
     *              * string lastName - Avatars last name
     * @return array
     */
    public function avatarExists($parameters) {
        // Retrieve grid information for remote admin
        $gridId = $parameters['gridId'];
        $grid   = new \Models\Grid($gridId);
        $grid->getInfoFromDatabase();

        // Call the Grid's remote admin
        $raXML = new \OpenSimRPC($grid->getRaUrl(), $grid->getRaPort(), $grid->getRaPassword());
        $callData = array(
            'user_firstname'    => $parameters['firstName'],
            'user_lastname'     => $parameters['lastName'],
        );

        $result = $raXML->call('admin_exists_user', $callData);

        return $result;
    }

    /**
     * Validates the parameters for the avatar exists check
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersAvatarExists($parameters) {
        $result = FALSE;
        if(count($parameters) != 2) {
            throw new \Exception('Expected 2 parameters, only got '. count($parameters), 23);
        } elseif(!isset($parameters['firstName']) && strlen($parameters['firstName']) > 0) {
            throw new \Exception('Missing parameter (string) "firstName"', 24);
        } elseif(!isset($parameters['lastName']) && strlen($parameters['lastName']) > 0) {
            throw new \Exception('Missing parameter (string) "lastName"', 25);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Checks the given string for not allowed characters
     *
     * @param string $password
     * @return boolean
     */
    public function checkPasswordForIlligalCharacters($password) {
        foreach($this->notAllowedChars as $char) {
            if (stripos($password, $char) !== false) {
                return true;
            }
        }
        return false;
    }
}