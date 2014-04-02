<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/file.php';
/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.4
 * @date April 2nd, 2014
 * @since February 10th, 2014
 */
class Document extends File {
    /**
     * Constructs a new document with the given id and optional the given slide
     *
     * @param integer $id - ID of this presentation
     * @param integer $type - [Optional] document type
     * @param string $title - [Optional] Title of document
     * @param integer $ownerId - [Optional] ID of the owner
     * @param string $creationDate - [Optional] Creation date time, YYYY-MM-DD HH:mm:ss
     * @param string $modificationDate - [Optional] Date of last modification, YYYY-MM-DD HH:mm:ss
     * @param string $file - [Optional] The file name and extension of this source file
     */
	public function __construct($id, $type = '', $title = '', $ownerId = '', $creationDate = '', $modificationDate = '', $file = '') {
        parent::__construct($id, $type, $title, $ownerId, $creationDate, $modificationDate, $file);
    }

    /**
     * Gets the cached textures from the database (if any available) for this slide
     *
     * @return array
     */
    public function getCache() {
        $db         = \Helper::getDB();
        $db->join('cached_assets c', 'dc.cacheId = c.id', 'LEFT');
        $db->where('dc.documentId', $db->escape($this->getId()));
        $results    = $db->get('documents_cache dc');

        return $results;
    }
}