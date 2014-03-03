<?php
namespace API;

if(EXEC != 1) {
	die('Invalid request');
}
/**
 * This class is used to check authorization tokens
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 19th, 2014
 */
class Auth {
    private static $token = '';
    private static $ip;
    private static $timestamp;
    private static $userId;
    private static $user;

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
        $result = $db->rawQuery('SELECT COUNT(*) AS count, userId FROM tokens WHERE token = ? AND ip = ? AND expires >= ? LIMIT 1', $params);

        // Extend token expiration time
        if($result[0]['count'] == 1) {
            self::$userId       = $result[0]['userId'];
            $db->where('token', $db->escape(self::$token));
            $data['expires']    = $db->escape(date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES)));
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
