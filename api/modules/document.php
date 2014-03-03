<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 3rd, 2014
 */
class Document extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->api->addModule('document', $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/documents\/?$/",                "getDocuments",         $this, "GET",  TRUE);  // Get list with 50 documents
        $this->api->addRoute("/documents\/(\d+)\/?$/",         "getDocuments",         $this, "GET",  TRUE);  // Get list with 50 documents starting at the given offset
        $this->api->addRoute("/document\/(\d+)\/?$/",          "getDocumentById",      $this, "GET",  TRUE);  // Select specific document
    }


    /**
     * Gets a list of documents starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public function getDocuments($args) {
        $db             = \Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array($db->escape($args[1]), 50);
        $resutls        = $db->rawQuery("SELECT * FROM documents ORDER BY creationDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $document       = new \Models\Document($result['id'], $result['type'], $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate']);
            $data[]         = $this->getDocumentData($document, FALSE);
        }
        return $data;
    }

    /**
     * Get document details for the given document
     *
     * @param array $args
     * @return array
     */
    public function getDocumentById($args) {
        $document = new \Models\Document($args[1]);
        $document->getInfoFromDatabase();

        // If the given document is a presentation, return it as a presentation
        if($document->getType() == 'presentation') {
            $presentation = new \Models\Presentation($document->getId(), 0, $document->getTitle(), $document->getOwnerId(), $document->getCreationDate(), $document->getModificationDate());
            return $this->api->getModule('presentation')->getPresentationData($presentation, TRUE);
        // Return it as a document
        } else {
            return $this->getDocumentData($document, TRUE);
        }
    }


    /**
     * Format the presentation data to the desired format
     *
     * @param \Models\Document $document
     * @param boolean $full - [Optional] Show all information about the presentation and slides
     * @return array
     */
    public function getDocumentData(\Models\Document $document, $full = TRUE) {
        $data = array();
        $data['id']                 = $document->getId();
        $data['type']               = $document->getType();
        $data['title']              = $document->getTitle();
        $data['ownerId']            = $document->getOwnerId();
        $data['creationDate']       = $document->getCreationDate();
        $data['modificationDate']   = $document->getModificationDate();

        return $data;
    }
}