<?php
if(EXEC != 1) {
	die('Invalid request');
}

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
     * Helper function to parse PUT requests
     * @source: https://stackoverflow.com/questions/5483851/manually-parse-raw-http-data-with-php/5488449#5488449
     *
     * @param string $input
     * @return array
     */
    public static function parsePutRequest($input) {
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
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
        return $a_data;
    }

}
