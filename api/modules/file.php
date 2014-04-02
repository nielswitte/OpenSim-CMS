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
class File extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('file');
        $this->api->addModule($this->getName(), $this);
        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/^\/files\/?$/",                         'getFiles',              $this, 'GET',       \Auth::READ);      // Get list with 50 files
        $this->api->addRoute("/^\/files\/(\d+)\/?$/",                  'getFiles',              $this, 'GET',       \Auth::READ);      // Get list with 50 files starting at the given offset
        $this->api->addRoute("/^\/files\/([a-zA-Z0-9-_ ]{3,}+)\/?$/",  'getFilesByTitle',       $this, 'GET',       \Auth::READ);      // Search for files by title
        $this->api->addRoute("/^\/files\/cache\/?$/",                  'deleteExpiredCache',    $this, 'DELETE',    \Auth::EXECUTE);   // Removes all expired cached assets
        $this->api->addRoute("/^\/file\/?$/",                          'createFile',            $this, 'POST',      \Auth::EXECUTE);   // Create a files
        $this->api->addRoute("/^\/file\/(\d+)\/?$/",                   'getFileById',           $this, 'GET',       \Auth::READ);      // Select specific files
        $this->api->addRoute("/^\/file\/(\d+)\/?$/",                   'deleteFileById',        $this, 'DELETE',    \Auth::EXECUTE);   // Delete specific files
        $this->api->addRoute("/^\/file\/(\d+)\/image\/?$/",            'getFileImageById',      $this, 'GET',       \AUTH::READ);      // Retrieves an image files type
        $this->api->addRoute("/^\/file\/(\d+)\/source\/?$/",           'getFileSourceById',     $this, 'GET',       \AUTH::READ);      // Retrieves the original file
    }

    /**
     * Gets a list of files starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public function getFiles($args) {
        $db             = \Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $db->orderBy('creationDate', 'DESC');
        $resutls        = $db->get('documents', array($args[1], 50));

        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $file       = new \Models\File($result['id'], $result['type'], $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate'], $result['file']);
            $data[]         = $this->getFileData($file, FALSE);
        }
        return $data;
    }

    /**
     * Searches the database for the given (partial) title and returns a list with files
     *
     * @param array $args
     * @return array
     */
    public function getFilesByTitle($args) {
        $db             = \Helper::getDB();
        $params         = array("%". strtolower($db->escape($args[1])) ."%");
        $results        = $db->rawQuery('SELECT * FROM documents WHERE LOWER(title) LIKE ? ORDER BY LOWER(title) ASC', $params);
        $data           = array();
        foreach($results as $result) {
            $file   = new \Models\File($result['id']);
            $file->getInfoFromDatabase();
            $data[]     = $this->getFileData($file, FALSE);
        }
        return $data;
    }

    /**
     * Get document details for the given document
     *
     * @param array $args
     * @return array
     */
    public function getFileById($args) {
        $file = new \Models\File($args[1]);
        $file->getInfoFromDatabase();

        // If the given file is a presentation, return it as a presentation
        if($file->getType() == 'presentation') {
            $presentation = new \Models\Presentation($file->getId(), 0, $file->getTitle(), $file->getOwnerId(), $file->getCreationDate(), $file->getModificationDate());
            return $this->api->getModule('presentation')->getPresentationData($presentation, TRUE);
        // If the given file is a document, return it as a document
        } elseif($file->getType() == 'document') {
            $document = new \Models\Document($file->getId(), 0, $file->getTitle(), $file->getOwnerId(), $file->getCreationDate(), $file->getModificationDate());
            return $this->api->getModule('document')->getDocumentData($document, TRUE);
        // Return it as a file
        } else {
            return $this->getFileData($file, TRUE);
        }
    }

    /**
     * Creates a new document with the given POST data
     *
     * @param array $args
     * @return array
     */
    public function createFile($args) {
        $data   = FALSE;
        $input  = \Helper::getInput(TRUE);
        // Presentations are handled by the presentations module
        if($input['type'] == 'presentation') {
            $presentation = $this->api->getModule('presentation')->createPresentation($args);
            $data = is_array($presentation) ? $presentation['id'] : $data;
        // Process other files
        } else {
            $fileCtrl   = new \Controllers\FileController();
            if($fileCtrl->validateParametersCreate($input)) {
                $data = $fileCtrl->createFile($input);
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
     * Removes the given document from the CMS
     *
     * @param array $args
     * @return array
     */
    public function deleteFileById($args) {
        $file     = new \Models\File($args[1]);
        $file->getInfoFromDatabase();

        // Only allow when the user has write access or wants to update his/her own files
        if(!\Auth::checkRights($this->getName(), \Auth::WRITE) && $file->getOwnerId() != \Auth::getUser()->getId()) {
            throw new \Exception('You do not have permissions to update this user.', 6);
        }

        $fileCtrl = new \Controllers\FileController($file);
        $data         = $fileCtrl->removeFile();
        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE)
        );
        return $result;
    }

    /**
     * Format the presentation data to the desired format
     *
     * @param \Models\File $file
     * @param boolean $full - [Optional] Show all information about the presentation and slides
     * @return array
     */
    public function getFileData(\Models\File $file, $full = TRUE) {
        $data = array(
            'id'                => $file->getId(),
            'type'              => $file->getType(),
            'title'             => $file->getTitle(),
            'ownerId'           => $file->getOwnerId(),
            'creationDate'      => $file->getCreationDate(),
            'modificationDate'  => $file->getModificationDate(),
            'sourceFile'        => $file->getFile(),
            'url'               => $file->getApiUrl()
        );

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
        $fileCtrl = new \Controllers\FileController(NULL);
        $data         = $fileCtrl->removeExpiredCache();

        // Format the result
        $result = array(
            'success'       => ($data !== FALSE ? TRUE : FALSE),
            'removedAssets' => $data
        );

        return $result;
    }

    /**
     * Loads an image for the document with type = image
     *
     * @param array $args
     * @throws \Exception
     */
    public function getFileImageById($args) {
        $file = new \Models\File($args[1]);
        $file->getInfoFromDatabase();
        if($file->getType() != 'image') {
            throw new \Exception('File with ID '+ $args[1] +' is not an image.');
        }
        require_once dirname(__FILE__) .'/../includes/class.Images.php';
        $image = new \Image($file->getPath() . DS . $file->getId() .'.'. IMAGE_TYPE);
        $image->display();
    }

    /**
     * Outputs the original source file
     *
     * @param array $args
     */
    public function getFileSourceById($args) {
        $file = new \Models\File($args[1]);
        $file->getInfoFromDatabase();
        $file->getOriginalFile();
    }
}
