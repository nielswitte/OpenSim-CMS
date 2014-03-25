<?php
defined('EXEC') or die('Config not loaded');

/**
 * This class is used to check authorization tokens
 *
 * @author Niels Witte
 * @version 0.2
 * @since February 19th, 2014
 */
class Auth {
    private static $token = '';
    private static $ip;
    private static $timestamp;
    private static $userId;
    private static $user;

    const NONE      = 0b000; // 0 - No rights
    const READ      = 0b100; // 4 - Read access
    const EXECUTE   = 0b101; // 5 - Allows to read and execute functions (i.e. create events, confirm avatar links, clear cache)
    const WRITE     = 0b110; // 6 - Allows to read and modify data
    const ALL       = 0b111; // 7 - All above

    /**
     * Request from OpenSim? Add this additional check because of the access rights of OpenSim
     *
     * @return boolean TRUE when IP matches a grid
     */
    public static function isGrid($id, $ip) {
        $result     = FALSE;
        $headers    = getallheaders();
        if(isset($headers["X-SecondLife-Shard"]) && $id == 0) {
            // Check server IP to grid list
            $db     = \Helper::getDB();
            $grids  = $db->get('grids');

            // Check all grids
            foreach($grids as $grid) {
                $osIp = $grid['osIp'];
                // Check if grid uses IP or hostname
                if(!filter_var($osIp, FILTER_VALIDATE_IP)) {
                    $osIp = gethostbyname($osIp);
                }
                // Match found? Stop!
                if($osIp == $ip || $osIp == "127.0.0.1") {
                    $result = TRUE;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Sets the token to be used in this class
     *
     * @param string $token
     */
    public static function setToken($token) {
        self::$token     = $token;
        self::$ip        = $_SERVER['REMOTE_ADDR'];
        self::$timestamp = date('Y-m-d H:i:s');
    }

    /**
     * Checks if the user is allowed to use the API
     * All users of the protected functions require a token
     *
     * @return boolean
     */
    public static function validate() {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_ENCODED);
        $result = self::checkToken($token);

        return $result;
    }

    /**
     * Tries to create the user class from the given data
     * Only possible when the token has been validated successful!
     *
     * @return \Models\UserLoggedIn or boolean when false
     */
    public static function getUser() {
        $result = FALSE;
        if(isset(self::$userId)) {
            if(!isset(self::$user)) {
                self::$user = new \Models\UserLoggedIn(self::$userId);
                self::$user->getInfoFromDatabase();
            }
            $result = self::$user;
        }
        return $result;
    }

    /**
     * Checks if the current user has the required rights for this module
     *
     * @param string $module - Name of the module
     * @param integer $rightsRequired - The required rights 4 = read only own data to 7 = read,write,execute everything
     * @return boolean TRUE when user has rights
     */
    public static function checkRights($module, $rightsRequired) {
        $user = self::getUser();
        if($user !== FALSE) {
            $result = $user->checkRights($module, $rightsRequired);
        } else {
            $result = $rightsRequired == 0 ? TRUE : FALSE;
        }

        return $result;
    }

    /**
     * Validates the given token and updates its expiration time
     *
     * @return boolean
     */
    private static function checkToken() {
        $db = \Helper::getDB();
        $params = array(
            $db->escape(self::$token),
            $db->escape(self::$ip),
            $db->escape(self::$timestamp)
        );
        $result = $db->rawQuery('SELECT count(*) AS count, userId FROM tokens WHERE token = ? AND ip = ? AND expires >= ? LIMIT 1', $params);

        // Extend token expiration time
        if($result[0]['count'] == 1) {
            self::$userId       = $result[0]['userId'];
            $db->where('token', $db->escape(self::$token));
            // OpenSim uses EXPIRES2
            if($result[0]['userId'] == 0) {
                $data['expires']    = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES2)));
            } else {
                $data['expires']    = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES)));
            }
            $db->update('tokens', $data);
        }

        return $result[0]['count'] == 1 ? TRUE : FALSE;
    }

    /**
     * Removes all expired tokens from the database
     *
     * @return boolean
     */
    public static function removeExpiredTokens() {
        $db = \Helper::getDB();
        $db->where('expires', array('<' => date('Y-m-d H:i:s')));
        return $db->delete('tokens');
    }
}
