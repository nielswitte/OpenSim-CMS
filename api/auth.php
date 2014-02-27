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
    private $token = '';
    private $ip;
    private $timestamp;
    private $userId;
    private $user;

    /**
     * Constructs a new Auth instance
     */
    public function __construct() {
        $this->ip           = $_SERVER['REMOTE_ADDR'];
        $this->timestamp    = date('Y-m-d H:i:s');
    }

    /**
     * Sets the token to be used in this class
     *
     * @param string $token
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * Checks if the user is allowed to use the API
     * All users of the protected functions require a token
     *
     * @return boolean
     */
    public function validate() {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_ENCODED);
        $result = self::checkToken($this->token);

        return $result;
    }

    /**
     * Tries to create the user class from the given data
     * Only possible when the token has been validated successful!
     *
     * @return \Models\UserLoggedIn or boolean when false
     */
    public function getUser() {
        $result = FALSE;
        if(isset($this->userId)) {
            if(!isset($this->user)) {
                $this->user = new \Models\UserLoggedIn($this->userId);
                $this->user->getInfoFromDB();
            }
            $result = $this->user;
        }
        return $result;
    }

    /**
     * Validates the given token and updates its expiration time
     *
     * @return boolean
     */
    private function checkToken() {
        $db = \Helper::getDB();
        $params = array($db->escape($this->token), $db->escape($this->ip), $db->escape($this->timestamp));
        $result = $db->rawQuery('SELECT COUNT(*) AS count, userId FROM tokens WHERE token = ? AND ip = ? AND expires >= ? LIMIT 1', $params);

        // Extend token expiration time
        if($result[0]['count'] == 1) {
            $this->userId = $result[0]['userId'];
            $db->where('token', $db->escape($this->token));
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
