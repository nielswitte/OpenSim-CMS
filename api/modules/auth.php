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
        $this->setName('auth');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/auth\/username\/?$/", "authUser", $this, "POST", \Auth::NONE); // Authenticate the given user
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
        $input                  = \Helper::getInput(TRUE);

        $username               = isset($input['username']) ? $input['username'] : '';
        $password               = isset($input['password']) ? $input['password'] : '';
        $ip                     = isset($input['ip']) ? $input['ip'] : FALSE;

        // Default settings
        $userId = ($username == "OpenSim" ? 0 : -1);
        $isGrid = FALSE;

        // Basic output data
        $data['token']          = $db->escape(\Helper::generateToken(48));
        $data['ip']             = ($ip !== FALSE && $ip !== NULL) ? $ip : $_SERVER['REMOTE_ADDR'];
        $data['expires']        = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES)));

        // Request from OpenSim? Add this additional check because of the access rights of OpenSim
        if(isset($headers["X-SecondLife-Shard"]) && $userId == 0) {
            // Check server IP to grid list
            $grids  = $db->get('grids');

            // Check all grids
            foreach($grids as $grid) {
                $osIp = $grid['osIp'];
                // Check if grid uses IP or hostname
                if(!filter_var($osIp, FILTER_VALIDATE_IP)) {
                    $osIp = gethostbyname($osIp);
                }
                // Match found? Stop!
                if($osIp == $data['ip'] || $osIp == "127.0.0.1") {
                    $isGrid = TRUE;
                    break;
                }
            }
        }

        // Attempt to access with OpenSim from outside the Grid
        if($userId == 0 && !$isGrid) {
            throw new \Exception("Not allowed to login as OpenSim outside the Grid", 2);
        }

        $user                   = new \Models\UserLoggedIn($userId, $username);
        $user->getInfoFromDatabase();
        $userCtrl               = new \Controllers\UserController($user);
        $validRequest           = $userCtrl->checkPassword($password);
        $data['userId']         = $db->escape($user->getId());
        if(!$validRequest) {
            throw new \Exception("Invalid username/password combination used", 1);
        }

        if($validRequest) {
            // Store token
            $result = !$db->insert('tokens', $data);
            // Query should affect one row, if not something went wrong
            if($result != 1) {
                throw new \Exception('Storing token at the server side failed', 3);
            }

            $data['permissions']    = $user->getRights();
        }

        return $data;
    }
}