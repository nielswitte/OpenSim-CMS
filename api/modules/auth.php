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
        $db                     = \Helper::getDB();
        // Input parameters
        $input                  = \Helper::getInput(TRUE);
        $username               = isset($input['username']) ? $input['username'] : '';
        $password               = isset($input['password']) ? $input['password'] : '';
        $ip                     = isset($input['ip']) ? $input['ip'] : FALSE;

        // Default settings
        $userId = ($username == "OpenSim" ? 0 : -1);

        // Basic output data
        $data['token']          = $db->escape(\Helper::generateToken(48));
        $data['ip']             = ($ip !== FALSE && $ip !== NULL) ? $ip : $_SERVER['REMOTE_ADDR'];
        $isGrid                 = \Auth::isGrid($userId, $data['ip']);
        // OpenSim sessions are valid longer
        $expireTime             = $isGrid ? SERVER_API_TOKEN_EXPIRES2 : SERVER_API_TOKEN_EXPIRES;
        $data['expires']        = $db->escape(date('Y-m-d H:i:s', strtotime('+'. $expireTime)));

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
        } else {
            // Store token
            $result = !$db->insert('tokens', $data);
            // Query should affect one row, if not something went wrong
            if($result != 1) {
                throw new \Exception('Storing token at the server side failed', 3);
            }
        }

        return $data;
    }
}