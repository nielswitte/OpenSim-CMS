<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for authentication
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 24th, 2014
 */
class Auth extends Module {
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/auth\/user\/?$/", "authUser", $this, "POST", FALSE); // Authenticate the given user
    }

    /**
     * Authenticates the user based on the given post data
     *
     * @throws Exception
     * @returns array
     */
    public function authUser($args) {
        $headers                = getallheaders();
        $db                     = \Helper::getDB();
        $userName               = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_ENCODED);
        $password               = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_ENCODED);
        $ip                     = filter_input(INPUT_POST, 'ip', FILTER_SANITIZE_ENCODED);

        // Basic output data
        $data['token']          = \Helper::generateToken(48);
        $data['ip']             = (($ip !== FALSE && $ip !== NULL) ? $ip : $_SERVER['REMOTE_ADDR']);
        $data['expires']        = date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES));

        // Check server IP to grid list
        $db->where('osIp', $db->escape($data['ip']));
        $grids = $db->get('grids', 1);

        // Request from OpenSim? Add this additional check because of the access rights of OpenSim
        if(isset($headers['HTTP_X_SECONDLIFE_SHARD']) && isset($grids[0])) {
            $userId             = -1;
        } elseif($userName != "OpenSim") {
            $userId             = 0;
        } else {
            throw new \Exception("Not allowed to login as OpenSim outside the Grid", 2);
        }
        $user           = new \Models\User($userId, $userName);
        $user->getInfoFromDatabase();
        $userCtrl       = new \Controllers\UserController($user);
        $validRequest   = $userCtrl->checkPassword($password);
        $data['userId'] = $user->getId();
        if(!$validRequest) {
            throw new \Exception("Invalid username/password combination used", 1);
        }

        if($validRequest) {
            $db->insert('tokens', $data);
        }

        return $data;
    }
}