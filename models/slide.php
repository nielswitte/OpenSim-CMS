<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the slide model
 *
 * @author Niels Witte
 * @version 0.2
 * @date February 12th, 2014
 */
class Slide {
    private $number;
    private $path;

    /**
     * Constructs a new slide with the given parameters
     *
     * @param integer $number
     * @param string $path
     */
    public function __construct($number, $path) {
        $this->number       = $number;
        $this->path         = $path;
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
     * Gets the cached textures from the database (if any available) for this slide
     *
     * @return array
     */
    public function getCache() {
        $db = \Helper::getDB();
        $params = array($this->getNumber());
        $results = $db->rawQuery('SELECT c.* FROM cached_assets c, document_slides_cache dc WHERE dc.cacheId = c.id AND dc.slideId = ?', $params);

        return $results;

    }

    /**
     * Checks if the UUID is expired based on the OS_ASSET_CACHE_EXPIRES value from the config
     *
     * @return boolean - True when expired
     */
    public function isUuidExpired() {
        //return !(strtotime($this->getUuidUpdated()) > strtotime('-'. OS_ASSET_CACHE_EXPIRES)) ? 1 : 0;
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
