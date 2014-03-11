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
 * @version 0.2
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
        $this->api->addRoute("/documents\/?$/",                "getDocuments",         $this, "GET",    TRUE);  // Get list with 50 documents
        $this->api->addRoute("/documents\/(\d+)\/?$/",         "getDocuments",         $this, "GET",    TRUE);  // Get list with 50 documents starting at the given offset
        $this->api->addRoute("/document\/?$/",                 "createDocument",       $this, "POST",   TRUE);  // Create a document
        $this->api->addRoute("/document\/(\d+)\/?$/",          "getDocumentById",      $this, "GET",    TRUE);  // Select specific document
        $this->api->addRoute("/document\/(\d+)\/?$/",          "deleteDocumentById",   $this, "DELETE", TRUE);  // Delete specific document
        $this->api->addRoute("/documents\/cache\/?$/",         "deleteExpiredCache",   $this, "DELETE", TRUE);  // Removes all expired cached assets
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
     * Creates a new document with the given POST data
     *
     * @param array $args
     * @return array
     */
    public function createDocument($args) {
        $data   = FALSE;
        $input  = \Helper::getInput(TRUE);
        // Presentations are handled by the presentations module
        if($input['type'] == 'presentation') {
            $presentation = $this->api->getModule('presentation')->createPresentation($args);
            $data = is_array($presentation) ? $presentation['id'] : $data;
        // Process other files
        } else {
            // @todo
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'id'        => ($data !== FALSE ? $data : 0)
        );

        return $result;
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
     * Removes the given document from the CMS
     *
     * @param array $args
     * @return array
     */
    public function deleteDocumentById($args) {
        $document     = new \Models\Document($args[1]);
        $document->getInfoFromDatabase();
        $documentCtrl = new \Controllers\DocumentController($document);
        $data         = $documentCtrl->removeDocument();
        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE)
        );
        return $result;
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

    /**
     * Removes all expired assets from the cache of the CMS
     * Returns the number of removed assets
     *
     * @param array $args
     * @return array
     */
    public function deleteExpiredCache($args) {
        $documentCtrl = new \Controllers\DocumentController(NULL);
        $data         = $documentCtrl->removeExpiredCache();

        // Format the result
        $result = array(
            'success'       => ($data !== FALSE ? TRUE : FALSE),
            'removedAssets' => $data
        );

        return $result;
    }
}
