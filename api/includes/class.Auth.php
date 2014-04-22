<?php
defined('EXEC') or die('Config not loaded');

/**
 * This class is used to check authorization tokens
 *
 * @author Niels Witte
 * @version 0.4
 * @data April 22nd, 2014
 * @since February 19th, 2014
 */
class Auth {
    private static $token = '';
    private static $ip;
    private static $timestamp;
    private static $userId;
    private static $user;
    private static $groupFiles;
    /**
     * Array containing all files of the currently loggedIn user
     * @var array
     */
    private static $userFiles;

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
     * Checks if the given fileId is owned by the user
     *
     * @param integer $fileId
     * @return boolean
     */
    public static function checkUserFiles($fileId) {
        if(!is_array(self::$userFiles)) {
            $db                 = \Helper::getDB();
            // Get all files owned by the current user
            $db->where('ownerId', $db->escape(\Auth::getUser()->getId()));
            $files              = $db->get('documents');
            self::$userFiles    = array();
            foreach($files as $file) {
                self::$userFiles[] = $file['id'];
            }
        }

        return in_array($fileId, self::$userFiles);
    }

    /**
     * Checks if the user is part of atleast one group which can access the given file
     *
     * @param integer $fileId
     * @return boolean
     */
    public static function checkGroupFile($fileId) {
        $user = self::getUser();
        $db = \Helper::getDB();

        if(!is_array(self::$groupFiles)) {
            $db->where('u.userId', $db->escape($user->getId()));
            $db->join('group_users u', 'u.groupId = d.groupId', 'INNER');
            $results = $db->get('group_documents d', NULL, 'd.documentId');
            self::$groupFiles = array();
            // Convert to one dimensional array
            foreach($results as $result) {
                self::$groupFiles[] = $result['documentId'];
            }
        }

        return in_array($fileId, self::$groupFiles);
    }

    /**
     * Validates the given token and updates its expiration time
     *
     * @return boolean
     */
    private static function checkToken() {
        $db = \Helper::getDB();
        $db->where('token', $db->escape(self::$token));
        $db->where('ip', $db->escape(self::$ip));
        $db->where('expires', array('>=' => $db->escape(self::$timestamp)));
        $result = $db->getOne('tokens', 'COUNT(*) AS count, userId');

        // Extend token expiration time
        if($result && $result['count'] == 1) {
            self::$userId       = $result['userId'];
            $db->where('token', $db->escape(self::$token));
            // OpenSim uses EXPIRES2
            if($result['userId'] == 0) {
                $data['expires']    = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES2)));
            } else {
                $data['expires']    = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES)));
            }
            $db->update('tokens', $data);
        }

        return $result['count'] == 1 ? TRUE : FALSE;
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
