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
     * Gets the input data from the request
     *
     * @param boolean $parse - Parse the input data to an array or return it raw string?
     * @return string or array on success, or boolean FALSE when failed
     */
    public static function getInput($parse = FALSE) {
        $request = $_SERVER['REQUEST_METHOD'];
        // Only for PUT and POST requests
        if($request == 'PUT' || $request == 'POST') {
            $input  = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
            $output = $parse ? self::parseInput($input) : $input;
            return $output;
        } else {
            return FALSE;
        }
    }

    /**
     * Helper function to parse PUT requests
     * @source: https://stackoverflow.com/questions/5483851/manually-parse-raw-http-data-with-php/5488449#5488449
     *
     * @param string $input
     * @return array
     */
    public static function parseInput($input) {
        $headers = getallheaders();
        // Parse JSON
        if((isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== FALSE) || (substr($input, 0, 1) == "{" && substr($input, -1) == "}")) {
            $a_data = json_decode($input, TRUE);
        // Parse post data the default way
        } elseif(count($_POST) > 0) {
            $a_data = filter_input_array(INPUT_POST);
        // Parse other form types
        } else {
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
        }

        return $a_data;
    }

    /**
     * Attempts to decode string as base64
     *
     * @param string $base64
     * @param boolean $decode - [Optional] Whether to decode the contents or not
     * @return string or boolean when not base64
     */
    public static function getBase64Content($base64, $decode = TRUE) {
        $result         = FALSE;
        $base64_start   = ";base64,";
        // Get position of base64 tag
        $base64_offset  = strpos($base64, $base64_start);
        // Is a base64 string?
        if($base64_offset !== FALSE) {
            // Get base64 content
            $base64_content = substr($base64, $base64_offset + strlen($base64_start));
            $result         = $decode ? base64_decode($base64_content) : $base64_content;
        }
        return $result;
    }

    /**
     * Attempts to get the header from the base64 string
     *
     * @param string $base64
     * @return string or boolean when not base64 or no header found
     */
    public static function getBase64Header($base64) {
        $result         = FALSE;
        $base64_start   = ";base64,";
        // Get position of base64 tag
        $base64_offset  = strpos($base64, $base64_start);
        if($base64_offset !== FALSE) {
            // base64 string starts with "data:", hence the 5.
            $base64_header  = substr($base64, 5, $base64_offset - 5);
            $result         = $base64_header;
        }
        return $result;
    }

    /**
     * Attempts to match a file content type to an extension
     *
     * @param string $type
     * @return string - Will return .txt if no other match is found
     */
    public static function getExtentionFromContentType($type) {
        switch ($type) {
            case "application/pdf":
                return 'pdf';
            break;
            default:
                return 'txt';
            break;
        }
    }

    /**
     * Saves the given data to the given file on the given location
     *
     * @param string $filename - Filename and extension
     * @param string $location - File location
     * @param string $data - Data to store in file
     * @return string full path to file or boolean when failed
     */
    public static function saveFile($filename, $location, $data) {
        $filepath = $location .DS. $filename;
        return @file_put_contents($filepath, $data) !== FALSE ? $filepath : FALSE;
    }
}
