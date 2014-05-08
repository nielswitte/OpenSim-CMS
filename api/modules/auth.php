<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for authentication
 *
 * @author Niels Witte
 * @version 0.3a
 * @date April 30, 2014
 * @since February 24th, 2014
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
        $this->api->addRoute("/^\/auth\/email\/?$/",        'authEmail',    $this, 'POST', \Auth::NONE); // Authenticate the given user
        $this->api->addRoute("/^\/auth\/user\/?$/",         'authUser',     $this, 'POST', \Auth::NONE); // Authenticate the given user
        $this->api->addRoute("/^\/auth\/username\/?$/",     'authUsername', $this, 'POST', \Auth::NONE); // Authenticate the given user
    }

    /**
     * Authenticate the user by username
     *
     * @param array $args
     * @return array
     */
    public function authUsername($args) {
        // Input parameters
        $input                  = \Helper::getInput(TRUE);
        $data['user']           = isset($input['username']) ? $input['username'] : '';
        $data['password']       = isset($input['password']) ? $input['password'] : '';
        $data['ip']             = isset($input['ip']) ? $input['ip'] : FALSE;
        return $this->auth($data);
    }

    /**
     * Authenticate the user by email
     *
     * @param array $args
     * @return array
     */
    public function authEmail($args) {
        // Input parameters
        $input                  = \Helper::getInput(TRUE);
        $data['user']           = isset($input['email']) ? $input['email'] : '';
        $data['password']       = isset($input['password']) ? $input['password'] : '';
        $data['password']       = $input['password'];
        $data['ip']             = isset($input['ip']) ? $input['ip'] : FALSE;
        return $this->auth($data);
    }

    /**
     * Authenticates the user based on the given post data
     *
     * @param array
     * @returns array
     */
    public function authUser($args) {
        // Input parameters
        $input                  = \Helper::getInput(TRUE);
        $data['user']           = isset($input['user']) ? $input['user'] : '';
        $data['password']       = isset($input['password']) ? $input['password'] : '';
        $data['ip']             = isset($input['ip']) ? $input['ip'] : FALSE;
        return $this->auth($data);
    }

    /**
     * Authenticates the user on email or username
     *
     * @param array $input
     * @return array
     * @throws \Exception
     */
    public function auth($input) {
        // Login with username (no valid email address used)
        $username               = (isset($input['user']) && !filter_var($input['user'], FILTER_VALIDATE_EMAIL) ? $input['user'] : '');
        // Login with emailaddress
        $email                  = (isset($input['user']) && filter_var($input['user'], FILTER_VALIDATE_EMAIL) ? $input['user'] : '');
        // Get password and IP
        $password               = isset($input['password']) ? $input['password'] : '';
        $ip                     = isset($input['ip']) ? $input['ip'] : FALSE;

        // Default settings
        $userId = ($username == 'OpenSim' ? 1 : -1);

        $db                     = \Helper::getDB();
        // Basic output data
        $data['token']          = $db->escape(\Helper::generateToken(48));
        $data['ip']             = ($ip !== FALSE && $ip !== NULL) ? $ip : getenv('REMOTE_ADDR');
        $isGrid                 = \Auth::isGrid($userId, $data['ip']);
        // OpenSim sessions are valid longer
        $expireTime             = $isGrid ? SERVER_API_TOKEN_EXPIRES2 : SERVER_API_TOKEN_EXPIRES;
        $data['expires']        = $db->escape(date('Y-m-d H:i:s', strtotime('+'. $expireTime)));

        // Attempt to access with OpenSim from outside the Grid
        if($userId == 1 && !$isGrid) {
            throw new \Exception('Not allowed to login as OpenSim outside the Grid', 2);
        }

        $user                   = new \Models\UserLoggedIn($userId, $username, $email);
        $user->getInfoFromDatabase();
        $userCtrl               = new \Controllers\UserController($user);
        $validRequest           = $userCtrl->checkPassword($password);
        $data['userId']         = $db->escape($user->getId());

        // Can't login?
        if(!$validRequest) {
            throw new \Exception('Invalid username/password combination used', 1);
        // Can login
        } else {
            // User has permission to use Auth?
            if($user->checkRights($this->getName(), \Auth::READ)) {
                // Store token
                $result             = $db->insert('tokens', $data);
                // Get the last login timestamp
                $data['lastLogin']  = $user->getLastLogin();
                // Query should affect one row, if not something went wrong
                if($result === FALSE) {
                    throw new \Exception('Storing token at the server side failed', 3);
                } else {
                    $db->where('id', $db->escape($user->getId()));
                    $db->update('users', array('lastLogin' => $db->now()));
                }
            // User lacks permission
            } else {
                throw new \Exception('You do not have permission to use the API', 2);
            }
        }

        return $data;
    }
}