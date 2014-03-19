<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Document controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 10th, 2014
 */
class DocumentController {
    private $document;

    /**
     * Constructs a new controller with the given presentation
     *
     * @param \Models\Presentation $presentation
     */
    public function __construct(\Models\Document $document = NULL) {
        $this->document = $document;
    }

    /**
     * Removes this document from the system
     *
     * @return boolean - FALSE when failed
     * @throws \Exception
     */
    public function removeDocument() {
        $db = \Helper::getDB();
        // First slides before parent document!
        $db->where('documentId', $db->escape($this->document->getId()));
        $db->delete('document_slides');

        $db->where('id', $db->escape($this->document->getId()));
        $result = $db->delete('documents');

        if($result === FALSE) {
            throw new \Exception('Given Document ('. $this->document->getId() .') could not be removed from the CMS. The document is probably being used in a meeting.', 1);
        } else {
            // Clear files after deletion
            \Helper::removeDirAndContents($this->document->getPath());
        }

        return $result;
    }

    /**
     * Removes all expired cache files
     *
     * @return integer (number of cached assets removed) or boolean FALSE when failed
     */
    public function removeExpiredCache() {
        $db = \Helper::getDB();

        $db->where('uuidExpires', array( '<' =>  'NOW()'));
        $results = $db->delete('cached_assets');

        return $results;
    }
}