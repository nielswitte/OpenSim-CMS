<?php
/**
 * Description of class
 *
 * @author Niels
 */
class Helper {
    private static $db;

    /**
     * Validates the UUID v4 string provided
     *
     * @param String $uuid
     * @return Boolean
     */
    public static function isValidUuid($uuid) {
        $matches = array();
        preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i", $uuid, $matches);
        return !empty($matches);
    }

    public static function setDB($db){
        self::$db = $db;
    }

    public static function getDB() {
        return self::$db;
    }
}
