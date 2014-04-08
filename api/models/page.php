<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the page model
 *
 * @author Niels Witte
 * @version 0.1
 * @since April 2nd, 2014
 */
class Page {
    private $id;
    private $number;
    private $path;
    private $hasComments;

    /**
     * Constructs a new slide with the given parameters
     *
     * @param integer $id
     * @param integer $number
     * @param string $path
     * @param boolean $hasComments - [Optional] Whether or not the Slide has comments available
     */
    public function __construct($id, $number, $path, $hasComments = FALSE) {
        $this->id           = $id;
        $this->number       = $number;
        $this->path         = $path;
        $this->hasComments  = $hasComments;
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
        $db->where('dc.pageId', $db->escape($this->getId()));
        $results    = $db->get('document_pages_cache dc');

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

    /**
     * Returns the comments or FALSE when no comments set
     *
     * @return \Models\Comments or FALSE when no comments
     */
    public function hasComments() {
        return $this->hasComments;
    }
}
