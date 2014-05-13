<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/document.php';
require_once dirname(__FILE__) .'/../models/file.php';
require_once dirname(__FILE__) .'/../models/group.php';
require_once dirname(__FILE__) .'/../models/presentation.php';
require_once dirname(__FILE__) .'/../controllers/fileController.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.8
 * @date May 13, 2014
 * @since March 3, 2014
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
        $this->api->addRoute("/^\/files\/([a-zA-Z0-9-_ \.\(\)\[\]]{3,}+)\/?$/",'getFilesByTitle', $this, 'GET',     \Auth::READ);      // Search for files by title
        $this->api->addRoute("/^\/files\/cache\/?$/",                  'deleteExpiredCache',    $this, 'DELETE',    \Auth::EXECUTE);   // Removes all expired cached assets
        $this->api->addRoute("/^\/file\/?$/",                          'createFile',            $this, 'POST',      \Auth::EXECUTE);   // Create a files
        $this->api->addRoute("/^\/file\/(\d+)\/?$/",                   'getFileById',           $this, 'GET',       \Auth::READ);      // Select specific files
        $this->api->addRoute("/^\/file\/(\d+)\/?$/",                   'deleteFileById',        $this, 'DELETE',    \Auth::EXECUTE);   // Delete specific files
        $this->api->addRoute("/^\/file\/(\d+)\/groups\/?$/",           'updateFileGroupsById',  $this, 'PUT',       \AUTH::EXECUTE);   // Set the groups for the given file
        $this->api->addRoute("/^\/file\/(\d+)\/image\/?$/",            'getFileImageById',      $this, 'GET',       \AUTH::READ);      // Retrieves an image files type
        $this->api->addRoute("/^\/file\/(\d+)\/image\/?$/",            'updateImageUuidById',   $this, 'PUT',       \AUTH::ALL);       // Updates the image's UUID
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
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->orderBy('d.creationDate', 'DESC');

        // User does not have all permissions? -> Can only see own or group documents
        if(!\Auth::checkRights($this->getName(), \Auth::ALL)) {
            // Retrieve all documents the user can access as the member of a group
            // or as documents owned by the user self
            $db->orwhere('d.ownerId', $db->escape(\Auth::getUser()->getId()));
            $db->orWhere('d.id IN (SELECT gd.documentId FROM group_documents gd, group_users gu WHERE gu.userId = ? AND gu.groupId = gd.groupId)', array($db->escape(\Auth::getUser()->getId())));
            $results = $db->get('documents d', array($db->escape($args[1]), 50), 'DISTINCT d.*, u.*, d.id AS documentId, u.id AS userId');
        // No extra filtering required
        } else {
            // Get 50 presentations from the given offset
            $results = $db->get('documents d', array($args[1], 50), '*, d.id AS documentId, u.id AS userId');
        }

        // Process results
        $data           = array();
        foreach($results as $result) {
            $user       = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $file       = new \Models\File($result['documentId'], $result['type'], $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
            $data[]     = $this->getFileData($file, FALSE);
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
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('LOWER(d.title)', array('LIKE' => "%". strtolower($db->escape($args[1])) ."%"));
        $db->orderBy('LOWER(d.title)', 'ASC');
        $results        = $db->get('documents d', NULL, 'DISTINCT *, d.id AS documentId, u.id AS userId');

        $data           = array();
        foreach($results as $result) {
            // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
            if($result['userId'] == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($result['documentId'])) {
                $user       = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
                $file       = new \Models\File($result['documentId'], $result['type'], $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
                $data[]     = $this->getFileData($file, FALSE);
            }
        }
        return $data;
    }

    /**
     * Get document details for the given document
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getFileById($args) {
        $data = array();
        // User has access to this file?
        $file = new \Models\File($args[1]);
        $file->getInfoFromDatabase();

        // Only allow access to specific files, files owned by user, when user has all rights or when file is part of a group the user is in
        if($file->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($file->getId())) {
            // If the given file is a presentation, return it as a presentation
            if($file->getType() == 'presentation') {
                $presentation = new \Models\Presentation($file->getId(), 0, $file->getTitle(), $file->getUser(), $file->getCreationDate(), $file->getModificationDate());
                $data = $this->api->getModule('presentation')->getPresentationData($presentation, TRUE);
            // If the given file is a document, return it as a document
            } elseif($file->getType() == 'document') {
                $document = new \Models\Document($file->getId(), 0, $file->getTitle(), $file->getUser(), $file->getCreationDate(), $file->getModificationDate());
                $data = $this->api->getModule('document')->getDocumentData($document, TRUE);
            // Return it as a file
            } else {
                $data = $this->getFileData($file, TRUE);
            }
        } else {
            throw new \Exception('You do not have permissions to view this file.', 7);
        }
        return $data;
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
        // Documents are handled by the documents module
        } elseif($input['type'] == 'document') {
            $document = $this->api->getModule('document')->createDocument($args);
            $data = is_array($document) ? $document['id'] : $data;
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
     * @throws \Exception
     */
    public function deleteFileById($args) {
        $file     = new \Models\File($args[1]);
        $file->getInfoFromDatabase();

        // User does not have ALL permissions
        if(!\Auth::checkRights($this->getName(), \Auth::ALL) &&
            // User has WRITE permissions and is in group
            (!\Auth::checkRights($this->getName(), \Auth::WRITE) && !\Auth::checkGroupFile($file->getId())) &&
            // User owns the file and has EXECUTE permissions (minimal required to access this function)
            ($file->getUser()->getId() != \Auth::getUser()->getId())) {
            throw new \Exception('You do not have permissions to delete this file.', 6);
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
            'user'              => $this->api->getModule('user')->getUserData($file->getUser(), FALSE),
            'title'             => stripslashes($file->getTitle()),
            'creationDate'      => $file->getCreationDate(),
            'modificationDate'  => $file->getModificationDate(),
            'sourceFile'        => $file->getFile(),
            'url'               => $file->getApiUrl()
        );

        // Get full details
        if($full) {
            // Group data not retrieved yet?
            if($file->getGroups() === NULL) {
                // Get group data
                $file->getGroupsFromDatabase();
            }

            // Get all groups
            $groups = array();

            foreach($file->getGroups() as $group) {
                $groups[] = array(
                    'id'    => $group->getId(),
                    'name'  => $group->getName()
                );
            }
            $data['groups'] = $groups;

            // Get image details on full request
            if($file->getType() == 'image') {
               $cachedTextures = array();
                foreach($file->getCache() as $cache) {
                    $cachedTextures[$cache['gridId']] = array(
                        'uuid'      => $cache['uuid'],
                        'expires'   => $cache['uuidExpires'],
                        'isExpired' => strtotime($cache['uuidExpires']) > time() ? 0 : 1
                    );
                }
                $data['cache']      = $cachedTextures;
            }
        }

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
        $data     = $fileCtrl->removeExpiredCache();

        // Format the result
        $result = array(
            'success' => $data
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
        if($file->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($file->getId())) {
            if($file->getType() != 'image') {
                throw new \Exception('File with ID '+ $args[1] +' is not an image.');
            }
            require_once dirname(__FILE__) .'/../includes/class.Images.php';
            $image = new \Image($file->getPath() . DS . $file->getId() .'.'. IMAGE_TYPE);
            $image->display();
        } else {
            throw new \Exception('You do not have permissions to view this file.', 7);
        }
    }

    /**
     * Outputs the original source file
     *
     * @param array $args
     * @throws \Exception
     */
    public function getFileSourceById($args) {
        $file = new \Models\File($args[1]);
        $file->getInfoFromDatabase();
        if($file->getUser()->getId() == \Auth::getUser()->getId() || \Auth::checkRights($this->getName(), \Auth::ALL) || \Auth::checkGroupFile($file->getId())) {
            $file->getOriginalFile();
        } else {
            throw new \Exception('You do not have permissions to view this file.', 7);
        }
    }

    /**
     * Sets the UUID of this image
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateImageUuidById($args) {
        $parsedPutData  = \Helper::getInput(TRUE);
        $gridId         = isset($parsedPutData['gridId']) ? $parsedPutData['gridId'] : '';
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get document and page details
        $file       = new \Models\File($args[1]);
        $file->getInfoFromDatabase();
        if($file->getType() == "image") {
            // Get grid details
            $grid       = new \Models\Grid($gridId);
            $grid->getInfoFromDatabase(FALSE);

            // Update
            $fileCtrl   = new \Controllers\FileController($file);
            $data       = $fileCtrl->setUuid($postUuid, $grid);
        } else {
            throw new \Exception('Image does not exist', 6);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }

    /**
     * Set file groups
     *
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function updateFileGroupsById($args) {
        $file       = new \Models\File($args[1]);
        $fileCtrl   = new \Controllers\FileController($file);
        $input      = \Helper::getInput(TRUE);
        $data       = FALSE;

        // Check if user has permission to update this file's groups
        if(!\Auth::checkRights($this->getName(), \Auth::ALL) && !\Auth::checkUserFiles($file->getId()) && !\Auth::checkGroupFile($file->getId())) {
            throw new \Exception('You do not have permission to change the sharing settings of this file', 10);
        }

        // Validate parameters for setting groups
        if($fileCtrl->validateParametersGroups($input)) {
            $data   = $fileCtrl->updateFileGroups($input);

            // Nothing updated?
            if(!$data) {
                throw new \Exception('No changes made, did you actually change the groups?', 9);
            }
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }
}
