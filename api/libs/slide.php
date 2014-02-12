<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the slide model
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 12th, 2014
 */
class Slide {
    private $number;
    private $uuid;
    private $path;
    private $uuidUpdated;

    /**
     * Constructs a new slide with the given parameters
     *
     * @param Integer $number
     * @param String $path
     * @param String $uuid
     * @param String $uuidUpdated (format: yyyy-mm-dd hh:mm:ss)
     */
    public function __construct($number, $path, $uuid = '0', $uuidUpdated = '0') {
        $this->number       = $number;
        $this->path         = $path;
        $this->uuid         = $uuid;
        $this->uuidUpdated  = $uuidUpdated;
    }

    /**
     * Returns the slide number
     *
     * @return Integer
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Updates the UUID of the slide to the given value
     *
     * @param String $uuid
     * @return Boolean
     * @throws Exception
     */
    public function setUuid($uuid) {
        $results = FALSE;
        if(Helper::isValidUuid($uuid)) {
            $db = Helper::getDB();
            $updateData = array(
                'uuid'          => $db->escape($uuid),
                'uuidUpdated'   => date('Y-m-d H:i:s')
            );
            $db->where('number', $this->getNumber());

            $results = $db->update('presentation_slides', $updateData);
        } else {
            throw new Exception("Invalid UUID provided", 2);
        }

        if(!$results) {
            throw new Exception("Updating UUID failed", 1);
        }
        return $results;
    }

    /**
     * Returns the UUID of the given slide
     *
     * @return String
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Returns the UUID updated datetime
     *
     * @return String
     */
    public function getUuidUpdated() {
        return $this->uuidUpdated;
    }

    /**
     * Checks if the UUID is expired based on the OS_ASSET_CACHE_EXPIRES value from the config
     *
     * @return Boolean - True when expired
     */
    public function isUuidExpired() {
        return !(strtotime($this->getUuidUpdated()) > strtotime('-'. OS_ASSET_CACHE_EXPIRES)) ? 1 : 0;
    }

    /**
     * Returns the local filesystem path to the slide
     *
     * @return String
     */
    public function getPath() {
        return $this->getPath() . DS . $this->number .'.jpg';
    }
}
