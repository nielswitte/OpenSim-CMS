<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the slide model
 *
 * @author Niels Witte
 * @version 0.2
 * @since February 12th, 2014
 */
class Slide {
    private $id;
    private $number;
    private $path;

    /**
     * Constructs a new slide with the given parameters
     *
     * @param integer $id
     * @param integer $number
     * @param string $path
     */
    public function __construct($id, $number, $path) {
        $this->id           = $id;
        $this->number       = $number;
        $this->path         = $path;
    }

    /**
     * Returns the slide id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
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
        $db         = \Helper::getDB();
        $db->join('cached_assets c', 'dc.cacheId = c.id', 'LEFT');
        $db->where('dc.slideId', $db->escape($this->getId()));
        $results    = $db->get('document_slides_cache dc');

        return $results;
    }

    /**
     * Returns the local filesystem path to the slide
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }
}
