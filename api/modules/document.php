<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/document.php';
require_once dirname(__FILE__) .'/../controllers/documentController.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.3
 * @date April 1st, 2014
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
        $this->api->addRoute("/^\/documents\/?$/",                         'getDocuments',         $this, 'GET',    \Auth::READ);    // Get list with 50 documents
        $this->api->addRoute("/^\/documents\/(\d+)\/?$/",                  'getDocuments',         $this, 'GET',    \Auth::READ);    // Get list with 50 documents starting at the given offset
        $this->api->addRoute("/^\/documents\/([a-zA-Z0-9-_ ]{3,}+)\/?$/",  'getDocumentsByTitle',  $this, 'GET',    \Auth::READ);    // Search for documents by title
        $this->api->addRoute("/^\/document\/?$/",                          'createDocument',       $this, 'POST',   \Auth::EXECUTE); // Create a document
        $this->api->addRoute("/^\/document\/(\d+)\/?$/",                   'getDocumentById',      $this, 'GET',    \Auth::READ);    // Select specific document
        $this->api->addRoute("/^\/document\/(\d+)\/?$/",                   'deleteDocumentById',   $this, 'DELETE', \Auth::EXECUTE); // Delete specific document
        $this->api->addRoute("/^\/documents\/cache\/?$/",                  'deleteExpiredCache',   $this, 'DELETE', \Auth::EXECUTE); // Removes all expired cached assets
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
        $resutls        = $db->get('documents', array($args[1], 50));

        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $document       = new \Models\Document($result['id'], $result['type'], $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate']);
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
        $params         = array("%". strtolower($db->escape($args[1])) ."%");
        $results        = $db->rawQuery('SELECT * FROM documents WHERE LOWER(title) LIKE ? ORDER BY LOWER(title) ASC', $params);
        $data           = array();
        foreach($results as $result) {
            $document   = new \Models\Document($result['id']);
            $document->getInfoFromDatabase();
            $data[]     = $this->getDocumentData($document, FALSE);
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
            $documentCtrl   = new \Controllers\DocumentController();
            if($documentCtrl->validateParametersCreate($input)) {
                $data = $documentCtrl->createDocument($input);
            }
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

        // Only allow when the user has write access or wants to update his/her own documents
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $document->getOwnerId() != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to update this user.', 6);
        }

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
