<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * Helper class to support the CMS and API
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 12th, 2014
 */
class Helper {
    private static $db;

    /**
     * Validates the UUID v4 string provided
     *
     * @param string $uuid
     * @return boolean
     */
    public static function isValidUuid($uuid) {
        $matches = array();
        preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i", $uuid, $matches);
        return !empty($matches);
    }

    /**
     * Sets the database to use so it can be retrieved by other components
     *
     * @param MysqlDb $db
     */
    public static function setDB($db){
        self::$db = $db;
    }

    /**
     * Retuns the database class
     *
     * @return MysqlDb
     */
    public static function getDB() {
        return self::$db;
    }

    /**
     * Hashes the given string
     *
     * @param string $string
     * @return string
     */
    public static function Hash($string) {
        return password_hash($string, PASSWORD_DEFAULT);
    }

    /**
     * Generates an unique token
     *
     * @param integer $length - [Optional] The length of the token to be generated
     * @return string
     */
    public static function generateToken($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Helper function to parse PUT requests
     * @source: https://stackoverflow.com/questions/5483851/manually-parse-raw-http-data-with-php/5488449#5488449
     *
     * @param string $input
     * @return array
     */
    public static function parsePutRequest($input) {
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        // Is multipart form?
        if(isset($matches[1])) {
            $boundary = $matches[1];

            // split content by boundary and get rid of last -- element
            $a_blocks = preg_split("/-+$boundary/", $input);
            array_pop($a_blocks);

            // loop data blocks
            foreach ($a_blocks as $id => $block) {
                if (empty($block))
                    continue;

                // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
                // parse uploaded files
                if (strpos($block, 'application/octet-stream') !== FALSE) {
                    // match "name", then everything after "stream" (optional) except for prepending newlines
                    preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                }
                // parse all other fields
                else {
                    // match "name" and optional value in between newline sequences
                    preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                }
                $a_data[$matches[1]] = $matches[2];
            }
        // Try to parse as key value pairs
        } else {
            parse_str($input, $a_data);
        }
        return $a_data;
    }
}
