<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of auth
 *
 * @author Niels
 */
class Auth {

    /**
     * Checks if the user is allowed to use the API
     * All users of the protected functions require a token
     *
     * @param type $token
     * @return boolean
     */
    public static function validate($token) {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_ENCODED);
        $result = self::checkToken($token);

        return $result;
    }

    /**
     * Validates the given token and updates its expiration time
     *
     * @param string $token
     * @return boolean
     */
    private static function checkToken($token) {
        $db = Helper::getDB();
        $params = array($token, $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s'));
        $result = $db->rawQuery('SELECT userId, COUNT(*) AS count FROM tokens WHERE token = ? AND ip = ? AND expires >= ? LIMIT 1', $params);

        // Extend token expiration time
        if($result[0]['count'] == 1) {
            $db->where('token', $db->escape($token));
            $data['expires']    = date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES));
            $db->update('tokens', $data);
        }

        return $result[0]['count'] == 1 ? TRUE : FALSE;
    }
}
