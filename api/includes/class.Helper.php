<?php
defined('EXEC') or die('Config not loaded');

/**
 * Helper class to support the CMS and API
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 2nd, 2014
 * @since February 12th, 2014
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
     * @param \MysqliDb $db
     */
    public static function setDB(\MysqliDb $db){
        self::$db = $db;
    }

    /**
     * Retuns the database class
     *
     * @return \MysqliDb
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
        $base64_start   = ';base64,';
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
        $base64_start   = ';base64,';
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
            case "image/jpeg":
                return 'jpg';
            case "image/png":
                return 'png';
            case "image/gif":
                return 'gif';
            default:
                return 'txt';
        }
    }

    /**
     * Saves the given data to the given file on the given location
     *
     * @param string $filename - Filename and extension
     * @param string $location - File location
     * @param string $data - Data to store in file
     * @return string - full path to file or boolean when failed
     */
    public static function saveFile($filename, $location, $data) {
        $filepath = $location .DS. $filename;
        return @file_put_contents($filepath, $data) !== FALSE ? $filepath : FALSE;
    }

    /**
     * Moves the source file to the target file
     *
     * @param string $source - File path, name and extension of source
     * @param string $destination - File path, name and extension of target
     * @return boolean
     */
    public static function moveFile($source, $destination) {
        return rename($source, $destination);
    }

    /**
     * Converts the given pdf to IMAGE_TYPE for each slide
     *
     * @param string $file
     * @param string $destination
     */
    public static function pdf2jpeg($file, $destination) {
        // Create the full path if needed
        $path    = dirname($destination);
        mkdir($path, 0777, TRUE);
        // Command requires jpeg instead of jpg
        $image_type = IMAGE_TYPE == 'jpg' ? 'jpeg' : IMAGE_TYPE;
        // Exec the command uses the larges of the image width or height as limit
        $command = 'pdftoppm -'. $image_type .' -scale-to '. (IMAGE_WIDTH > IMAGE_HEIGHT ? IMAGE_WIDTH : IMAGE_HEIGHT) .' -aa yes -aaVector yes '. $file .' '. $destination;
        exec($command);
    }

    /**
     * Removes direcotry and its contents
     *
     * @param string $dir
     */
    public static function removeDirAndContents($dir) {
        if(file_exists($dir)) {
            foreach (glob($dir . '/*') as $file) {
                if (is_dir($file)) {
                    self::removeDirAndContents($file);
                } else {
                    unlink($file);
                }
            } rmdir($dir);
        }
    }

    /**
     * Insert an element at a given position into the array
     *
     * @param array $array - The array to insert the new element in
     * @param type $index - The position to insert the element
     * @param type $element - The element to insert
     * @return array - The new array
     */
    public static function insertArrayIndex($array, $index, $element) {
        $start      = array_slice($array, 0, $index);
        $end        = array_slice($array, $index);
        $start[]    = $element;

        return array_merge($start, $end);
    }

    /**
     * Creates a resized image of the given source and saves it at the destination
     * Also fills the background overflow after cropping with black or white depending on
     * the color of the corners of the image.
     *
     * @param string $source - The file to use as a source
     * @param string $destination - The destination to save the file, including filename and extension
     * @param integer $height - [Optional] Height in pixels
     * @param integer $width - [Optional] Width in pixels
     * @return boolean
     */
    public static function imageResize($source, $destination, $height = IMAGE_HEIGHT, $width = IMAGE_WIDTH) {
        $destinationDir     = dirname($destination);
        $destinationExt     = @end(explode('.', $destination));
        $destinationFile    = @end(explode(DS, preg_replace("/\\.[^.\\s]{3,4}$/", "", $destination)));
        $resizeRequired     = FALSE;

        // Create thumbnail directory
        if(!file_exists($destinationDir)) {
            mkdir($destinationDir, 0777, TRUE);
        }

        // Create thumbnail if not exist
        if(file_exists($source)) {
            require_once dirname(__FILE__) .'/class.Images.php';
            // Load the required image
            $resize = new \Image($source);

            // Image needs to be resized and overwritten
            if($source == $destination) {
                if($resize->getHeight() != $height || $resize->getWidth() != $width) {
                    $resizeRequired = TRUE;
                }
            }

            // Resize is required?
            if(!file_exists($destination) || $resizeRequired) {
                // Load the background
                $averageColor = $resize->getAverageColor();
                if((($averageColor['red'] + $averageColor['green'] + $averageColor['blue']) / 3) >= 128) {
                    $image = new \Image(FILES_LOCATION . DS . 'background_light.jpg');
                } else {
                    $image = new \Image(FILES_LOCATION . DS . 'background_dark.jpg');
                }

                // resize when needed
                if($resize->getWidth() > $width || $resize->getHeight() > $height) {
                    $resize->resize($width, $height, 'fit');
                    $resize->save($destinationFile, $destinationDir, $destinationExt);
                    // Now use destination as source, since it is resized
                    $source = $destination;
                }
                unset($resize);

                // Fill remaining of image with black
                $image->resize($width, $height, 'fit');
                $image->addWatermark($source);
                $image->writeWatermark(100, 0, 0, 'c', 'c');
                return $image->save($destinationFile, $destinationDir, $destinationExt);
            // Thumbnail already exists?
            } else {
                return true;
            }
        // Source and destination files do not exist
        } else {
            return false;
        }
    }

    /**
     * Converts all URLs in the given text to HTML links
     *
     * @source: http://buildinternet.com/2010/05/how-to-automatically-linkify-text-with-php-regular-expressions/
     * @param string $text
     * @return string
     */
    public static function linkIt($text) {
        // Convert full links starting with http, https, ftp, ftps
        $text = preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t&lt;]*)/is", "$1$2<a href=\"$3\">$3</a>", $text);
        // Convert links starting with www. or ftp.
        $text = preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r&lt;]*)/is", "$1$2<a href=\"http://$3\">$3</a>", $text);
        // Convert mail links, containing an @
        $text = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text);
        return($text);
    }

    /**
     * Constructs an iCal file with the given parameters
     *
     * @source: https://gist.github.com/jakebellacera/635416
     * @param string $startDate - YYYY-MM-DD HH:mm:ss
     * @param string $endDate - YYYY-MM-DD HH:mm:ss
     * @param string $subject - Subject
     * @param string $description - Description of the event
     * @param string $location - Location where the event takes place
     * @param string $creatorName - The name of the creator of this event
     * @param string $creatorEmail - The email address of the creator
     * @param array $attendees - List with name => email pairs
     * @return string $filename
     */
    public static function getICS($startDate, $endDate, $subject, $description, $location, $creatorName, $creatorEmail, $attendees) {
        $start  = strtotime($startDate);
        $end    = strtotime($endDate);
        $ical   =
"BEGIN:VCALENDAR
PRODID:-//OpenSim-CMS v0.1//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART:". date('Ymd\THis\Z', $start) ."
DTEND:". date('Ymd\THis\Z', $end) ."
DTSTAMP:". date('Ymd\THis\Z') ."
ORGANIZER;CN=". $creatorName .":mailto:". $creatorEmail ."
UID:". uniqid();
        // Add attendees
        foreach($attendees as $name => $email) {
            $ical   .= "
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=". $name .":MAILTO:". $email;
        }
        // Continue ICS creation
        $ical   .= "
DESCRIPTION:". str_replace("\n", "\n ", preg_replace('/([\,;])/','\\\$1', $description)) ."
LOCATION:". preg_replace('/([\,;])/','\\\$1', $location) ."
SEQUENCE:0
STATUS:NEEDS-ACTION
SUMMARY:". preg_replace('/([\,;])/','\\\$1', $subject) ."
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR";

        // Generate a random filename
        $filename = \Helper::generateToken(16) .'.ics';
        file_put_contents(FILES_LOCATION . DS .'ical'. DS . $filename, $ical);

        return FILES_LOCATION . DS .'ical'. DS . $filename;
    }

    /**
     * Gets an instance of the comment's parent
     *
     * @param string $type - Type of the parent
     * @param integer $id - The parent's ID
     * @return \Models\Documents or other \Models\* instance or boolean FALSE when type not found
     */
    public static function getCommentType($type, $id) {
        if($type == 'document') {
            $parent = new \Models\Document($id);
        } elseif($type == 'slide') {
            $parent = new \Models\Slide($id, 1, '');
        } elseif($type == 'meeting') {
            $parent = new \Models\Meeting($id);
        } else {
            $parent = FALSE;
        }
        return $parent;
    }
}
