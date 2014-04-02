<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/file.php';
require_once dirname(__FILE__) .'/../models/document.php';
require_once dirname(__FILE__) .'/../models/presentation.php';
require_once dirname(__FILE__) .'/../controllers/fileController.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.4
 * @date April 2nd, 2014
 * @since March 3rd, 2014
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
        $this->setName('document');
        $this->api->addModule($this->getName(), $this);
        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/^\/documents\/?$/",                         'getDocuments',          $this, 'GET',       \Auth::READ);      // Get list with 50 documents
        $this->api->addRoute("/^\/documents\/(\d+)\/?$/",                  'getDocuments',          $this, 'GET',       \Auth::READ);      // Get list with 50 documents starting at the given offset
        $this->api->addRoute("/^\/documents\/([a-zA-Z0-9-_ ]{3,}+)\/?$/",  'getDocumentsByTitle',   $this, 'GET',       \Auth::READ);      // Search for documents by title
        $this->api->addRoute("/^\/document\/(\d+)\/?$/",                   'getDocumentById',       $this, 'GET',       \Auth::READ);      // Select specific document
        $this->api->addRoute("/^\/document\/?$/",                          'createDocument',        $this, 'POST',      \Auth::EXECUTE);   // Create a document
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
        $db->orderBy('creationDate', 'DESC');
        $db->where('type', 'document');
        $resutls        = $db->get('documents', array($args[1], 50));

        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $document       = new \Models\Document($result['id'], 1, $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate'], $result['file']);
            $data[]         = $this->getDocumentData($document, FALSE);
        }
        return $data;
    }

    /**
     * Searches the database for the given (partial) title and returns a list with documents
     *
     * @param array $args
     * @return array
     */
    public function getDocumentsByTitle($args) {
        $db             = \Helper::getDB();
        $params         = array("%". strtolower($db->escape($args[1])) ."%", 'document');
        $results        = $db->rawQuery('SELECT * FROM documents WHERE LOWER(title) LIKE ? AND type = ? ORDER BY LOWER(title) ASC', $params);
        $data           = array();
        foreach($results as $result) {
            $document   = new \Models\Document($result['id']);
            $document->getInfoFromDatabase();
            $data[]     = $this->getDocumentData($document, FALSE);
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
        $document = new \Models\File($args[1]);
        $document->getInfoFromDatabase();

        return $this->getDocumentData($document, TRUE);
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
        $fileCtrl   = new \Controllers\FileController();
        if($fileCtrl->validateParametersCreate($input)) {
            $data = $fileCtrl->createFile($input);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'id'        => ($data !== FALSE ? $data : 0)
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
        $data = array(
            'id'                => $document->getId(),
            'type'              => $document->getType(),
            'title'             => $document->getTitle(),
            'ownerId'           => $document->getOwnerId(),
            'creationDate'      => $document->getCreationDate(),
            'modificationDate'  => $document->getModificationDate(),
            'sourceFile'        => $document->getFile(),
            'url'               => $document->getApiUrl()
        );

        return $data;
    }
}
