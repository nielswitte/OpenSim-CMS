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
     * @param integer $number
     * @param string $path
     * @param string $uuid
     * @param string $uuidUpdated (format: yyyy-mm-dd hh:mm:ss)
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
     * @return integer
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Returns the UUID of the given slide
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Returns the UUID updated datetime
     *
     * @return string
     */
    public function getUuidUpdated() {
        return $this->uuidUpdated;
    }

    /**
     * Checks if the UUID is expired based on the OS_ASSET_CACHE_EXPIRES value from the config
     *
     * @return boolean - True when expired
     */
    public function isUuidExpired() {
        return !(strtotime($this->getUuidUpdated()) > strtotime('-'. OS_ASSET_CACHE_EXPIRES)) ? 1 : 0;
    }

    /**
     * Returns the local filesystem path to the slide
     *
     * @return string
     */
    public function getPath() {
        return $this->getPath() . DS . $this->number .'.jpg';
    }
}
