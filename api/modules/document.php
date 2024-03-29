<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/document.php';
require_once dirname(__FILE__) .'/../controllers/documentController.php';
require_once dirname(__FILE__) .'/../models/page.php';
require_once dirname(__FILE__) .'/../controllers/pageController.php';

/**
 * Implements the functions for documents
 *
 * @author Niels Witte
 * @version 0.6
 * @date May 13, 2014
 * @since March 3, 2014
 */
class Document extends Module {
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
        $this->api->addRoute("/^\/documents\/?$/",                                       'getDocuments',             $this, 'GET', \Auth::READ);    // Get list with 50 documents
        $this->api->addRoute("/^\/documents\/(\d+)\/?$/",                                'getDocuments',             $this, 'GET', \Auth::READ);    // Get list with 50 documents starting at the given offset
        $this->api->addRoute("/^\/documents\/([a-zA-Z0-9-_ \.\(\)\[\]]{3,}+)\/?$/",      'getDocumentsByTitle',      $this, 'GET', \Auth::READ);    // Search for documents by title
        $this->api->addRoute("/^\/document\/(\d+)\/?$/",                                 'getDocumentById',          $this, 'GET', \Auth::READ);    // Select specific document
        $this->api->addRoute("/^\/document\/?$/",                                        'createDocument',           $this, 'POST',\Auth::EXECUTE); // Create a document
        $this->api->addRoute("/^\/document\/(\d+)\/source\/?$/",                         'getDocumentSourceById',    $this, 'GET', \Auth::READ);    // Download this document
        $this->api->addRoute("/^\/document\/(\d+)\/page\/(\d+)\/?$/",                    'getPageById',              $this, 'GET', \Auth::READ);    // Get page from document
        $this->api->addRoute("/^\/document\/(\d+)\/page\/number\/(\d+)\/?$/",            'getPageByNumber',          $this, 'GET', \Auth::READ);    // Get page from document
        $this->api->addRoute("/^\/document\/(\d+)\/page\/number\/(\d+)\/?$/",            'updatePageUuidByNumber',   $this, 'PUT', \Auth::ALL);     // Update page UUID for given page of document
        $this->api->addRoute("/^\/document\/(\d+)\/page\/number\/(\d+)\/image\/?$/",     'getPageImageByNumber',     $this, 'GET', \Auth::READ);    // Get only the image of a given document page
        $this->api->addRoute("/^\/document\/(\d+)\/page\/number\/(\d+)\/thumbnail\/?$/", 'getPageThumbnailByNumber', $this, 'GET', \Auth::READ);    // Get only the image of a given document page
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
        // Get 50 documents from the given offset
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('d.type', $db->escape('document'));
        $db->orderBy('d.creationDate', 'DESC');

        // User does not have all permissions? -> Can only see own or group documents
        if(!\Auth::checkRights($this->getName(), \Auth::ALL)) {
            // Retrieve all documents the user can access as the member of a group
            // or as documents owned by the user self
            $db->Where('(d.ownerId = ? OR d.id IN (SELECT gd.documentId FROM group_documents gd, group_users gu WHERE gu.userId = ? AND gu.groupId = gd.groupId))', array($db->escape($args[1]), $db->escape(\Auth::getUser()->getId())));
            $resutls        = $db->get('documents d', array($db->escape($args[1]), 50), 'DISTINCT d.*, u.*, d.id AS documentId, u.id AS userId');
        // No extra filtering required
        } else {
            $resutls        = $db->get('documents d', array($args[1], 50), '*, d.id AS documentId, u.id AS userId');
        }

        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $user           = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $document       = new \Models\Document($result['documentId'], 0, $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
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
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('LOWER(d.title)', array('LIKE' => "%". strtolower($db->escape($args[1])) ."%"));
        $db->where('d.type', $db->escape('document'));
        $db->orderBy('LOWER(d.title)', 'ASC');
        $results        = $db->get('documents d', NULL, 'DISTINCT *, d.id AS documentId, u.id AS userId');
        $data           = array();
        foreach($results as $result) {
            if($result['userId'] == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($result['documentId'])) {
                $user       = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
                $document   = new \Models\Document($result['documentId'], 1, $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
                $data[]     = $this->getDocumentData($document, FALSE);
            }
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

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($document->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($document->getId())) {
            return $this->getDocumentData($document, TRUE);
        } else {
            throw new \Exception('You do not have permissions to view this document.', 7);
        }
    }

    /**
     * Get page details for the given page
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getPageByNumber($args) {
        $document   = new \Models\Document($args[1]);
        $document->getInfoFromDatabase();

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($document->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($document->getId())) {
            $page = $document->getPageByNumber($args[2]);
            $data = $this->getPageData($document, $page);
        } else {
            throw new \Exception('You do not have permissions to view this page.', 7);
        }

        return $data;
    }

    /**
     * Get page details for the given page
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getPageById($args) {
        $document   = new \Models\Document($args[1]);
        $document->getInfoFromDatabase();

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($document->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($document->getId())) {
            $page = $document->getPageById($args[2]);
            $data = $this->getPageData($document, $page);
        } else {
            throw new \Exception('You do not have permissions to view this page.', 7);
        }

        return $data;
    }

    /**
     * Get page image for the given page
     *
     * @param array $args
     * @throws \Exception
     */
    public function getPageImageByNumber($args) {
        // Get document and page details
        $document       = new \Models\Document($args[1], $args[2]);
        $document->getInfoFromDatabase();

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($document->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($document->getId())) {
            $document->getPages();
            $pageNr     = str_pad($document->getCurrentPage(), strlen($document->getNumberOfPages()), '0', STR_PAD_LEFT);
            $pagePath   = $document->getPath() . DS .'page-'. $pageNr .'.'. IMAGE_TYPE;

            if(!\Helper::imageResize($pagePath, $pagePath, IMAGE_HEIGHT, IMAGE_WIDTH)) {
                throw new \Exception('Requested page does not exists', 5);
            } else {
                require_once dirname(__FILE__) .'/../includes/class.Images.php';
                $image = new \Image($pagePath);
                $image->display();
            }
        } else {
            throw new \Exception('You do not have permissions to view this page.', 7);
        }
    }

    /**
     * Returns the thumbnail for the given page
     *
     * @param array $args
     * @throws \Exception
     */
    public function getPageThumbnailByNumber($args) {
        $document       = new \Models\Document($args[1], $args[2]);
        $document->getInfoFromDatabase();

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($document->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($document->getId())) {
            $document->getPages();
            $pageNr         = str_pad($document->getCurrentPage(), strlen($document->getNumberOfPages()), '0', STR_PAD_LEFT);
            $pagePath       = $document->getPath() . DS .'page-'. $pageNr .'.'. IMAGE_TYPE;
            $thumbPath      = $document->getThumbnailPath() . DS .'page-'. $pageNr .'.jpg';

            if(!\Helper::imageResize($pagePath, $thumbPath, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_WIDTH)) {
                throw new \Exception('Requested page does not exists', 5);
            } else {
                require_once dirname(__FILE__) .'/../includes/class.Images.php';
                $image = new \Image($thumbPath);
                $image->display();
            }
        } else {
            throw new \Exception('You do not have permissions to view this page.', 7);
        }
    }

    /**
     * Updates the page with the given UUID
     *
     * @param array $args
     * @return boolean
     */
    public function updatePageUuidByNumber($args) {
        $parsedPutData  = \Helper::getInput(TRUE);
        $gridId         = isset($parsedPutData['gridId']) ? $parsedPutData['gridId'] : '';
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get document and page details
        $document       = new \Models\Document($args[1]);
        $page           = $document->getPageByNumber($args[2]);
        if($page !== FALSE) {
            // Get grid details
            $grid           = new \Models\Grid($gridId);
            $grid->getInfoFromDatabase(FALSE);

            // Update
            $pageCtrl   = new \Controllers\PageController($page);
            $data       = $pageCtrl->setUuid($postUuid, $grid);
        } else {
            throw new \Exception('Page does not exist', 6);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }

    /**
     * Gets the document's source file
     *
     * @param array $args
     */
    public function getDocumentSourceById($args) {
        $this->api->getModule('file')->getFileSourceById($args);
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
        $documentCtrl   = new \Controllers\DocumentController();
        if($documentCtrl->validateParametersCreate($input)) {
            $data = $documentCtrl->createDocument($input);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'id'        => ($data !== FALSE ? $data : 0)
        );

        return $result;
    }

    /**
     * Format the document data to the desired format
     *
     * @param \Models\Document $document
     * @param boolean $full - [Optional] Show all information about the document and pages
     * @return array
     */
    public function getDocumentData(\Models\Document $document, $full = TRUE) {
        $data       = $this->api->getModule('file')->getFileData($document, $full);
        // Include all data?
        if($full) {
            $pages  = array();
            foreach($document->getPages() as $page) {
                $pages[] = $this->getPageData($document, $page, $full);
            }
            $data['pages']         = $pages;
        }
        $data['pagesCount']        = $document->getNumberOfPages();
        return $data;
    }

    /**
     * Formats the data for the given page
     *
     * @param \Models\Document $document
     * @param \Models\Page $page
     * @param boolean $full - [Optional] Show all information about the page
     * @return array
     */
    public function getPageData(\Models\Document $document, \Models\Page $page, $full = TRUE) {
        $data = array(
            'id'            => $page->getId(),
            'number'        => $page->getNumber(),
            'total'         => $document->getNumberOfPages(),
            'hasComments'   => $page->hasComments(),
            'image'         => $document->getApiUrl() . 'page/number/' . $page->getNumber() . '/image/',
            'thumbnail'     => $document->getApiUrl() . 'page/number/' . $page->getNumber() . '/thumbnail/'
        );

        // Show additional information
        if($full) {
            $cachedTextures = array();
            foreach($page->getCache() as $cache) {
                $cachedTextures[$cache['gridId']] = array(
                    'uuid'      => $cache['uuid'],
                    'expires'   => $cache['uuidExpires'],
                    'isExpired' => strtotime($cache['uuidExpires']) > time() ? 0 : 1
                );
            }
            // Retrieve title if not available
            if($document->getTitle() == '') {
                $document->getInfoFromDatabase();
            }
            $data['documentTitle']  = $document->getTitle();
            $data['cache']          = $cachedTextures;
        }
        return $data;
    }
}
