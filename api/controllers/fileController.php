<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the File controller
 *
 * @author Niels Witte
 * @version 0.4a
 * @date April 22nd, 2014
 * @since March 10th, 2014
 */
class FileController {
    private $file;

    /**
     * Constructs a new controller with the given presentation
     *
     * @param \Models\File $file
     */
    public function __construct(\Models\File $file = NULL) {
        $this->file = $file;
    }

    /**
     * Removes this file from the system
     *
     * @return boolean - FALSE when failed
     * @throws \Exception
     */
    public function removeFile() {
        $db = \Helper::getDB();
        // Type is not available?
        if($this->file->getType() == '') {
            $this->file->getInfoFromDatabase();
        }

        // Remove comments for this file
        $commentCtrl = new \Controllers\CommentController();
        $commentCtrl->removeCommentsByItem('file', $this->file->getId());

        // If it's a presentation, remove all comments for slides as well
        if($this->file->getType() == 'presentation') {
            $presentation = new \Models\Presentation($this->file->getId());

            foreach($presentation->getSlides() as $slide) {
                $commentCtrl->removeCommentsByItem('slide', $slide->getId());
            }

            // First slides before parent file!
            $db->where('documentId', $db->escape($this->file->getId()));
            $db->delete('document_slides');
        // If it's a document, remove all comments from pages as well
        } elseif($this->file->getType() == 'document') {
            $document = new \Models\Document($this->file->getId());

            foreach($document->getPages() as $page) {
                $commentCtrl->removeCommentsByItem('page', $page->getId());
            }

            // First slides before parent file!
            $db->where('documentId', $db->escape($this->file->getId()));
            $db->delete('document_pages');
        }

        // Remove the file itself
        $db->where('id', $db->escape($this->file->getId()));
        $result = $db->delete('documents');

        // Something went wrong?
        if($result === FALSE) {
            throw new \Exception('Given File ('. $this->file->getId() .') could not be removed from the CMS. The file is probably being used in a meeting.', 1);
        } else {
            // Clear files after deletion
            \Helper::removeDirAndContents($this->file->getPath());
        }

        return $result;
    }

    /**
     * Removes all expired cache files
     *
     * @return boolean - TRUE when actual items are removed
     */
    public function removeExpiredCache() {
        $db = \Helper::getDB();

        $db->where('uuidExpires', array( '<' =>  'NOW()'));
        $results = $db->delete('cached_assets');

        return $results;
    }

    /**
     * Creates a new file
     *
     * @param array $parameters
     *              * string file - Base64 encoded file
     *              * string title - The file title
     *              * string type - Name of the type of file
     * @return integer or boolean FALSE on failure
     * @throws \Exception
     */
    public function createFile($parameters) {
        $result         = FALSE;
        // Prepare additional information
        $file           = \Helper::getBase64Content($parameters['file'], TRUE);
        $header         = \Helper::getBase64Header($parameters['file']);
        $extension      = \Helper::getExtentionFromContentType($header);
        $db             = \Helper::getDB();

        // Insert main file data into database to get a autoincrement ID
        $data           = array(
            'title'         => $db->escape(\Helper::filterString($parameters['title'])),
            'type'          => $db->escape($parameters['type']),
            'ownerId'       => $db->escape(\Auth::getUser()->getId()),
            'creationDate'  => $db->now(),
            'file'          => $db->escape('source.'. $extension)
        );
        $fileId = $db->insert('documents', $data);
        // Insert successful?
        if($fileId !== FALSE) {
            $filename   = \Helper::saveFile($fileId .'.'. $extension, TEMP_LOCATION, $file);
            // File saved successful to temp?
            if($filename !== FALSE && file_exists($filename)) {
                $targetDir  = FILES_LOCATION . DS . $parameters['type'] . DS . $fileId;

                // Finally process images
                if($data['type'] == 'image' && in_array($extension, array('png', 'jpg', 'jpeg', 'gif'))) {
                    $destination = $targetDir . DS . $fileId .'.'. IMAGE_TYPE;
                    $result = \Helper::imageResize($filename, $destination);
                } else {
                    $result = TRUE;
                }

                // Move the file to target directory
                if($result) {
                    \Helper::moveFile($filename, $targetDir . DS .'source.'. $extension);
                }
            }
        }

        // Not a valid result? Undo everything!
        if($result === FALSE) {
            if($fileId !== FALSE) {
                // Temp file still existing?
                if(file_exists($filename)) {
                    unlink($filename);
                    throw new \Exception('Failed to move file to storage', 6);
                }

                // Remove file from DB
                $db->where('id', $db->escape($fileId));
                $db->delete('documents');

                throw new \Exception('Failed to save file to temp storage', 6);
            } else {
                throw new \Exception('Failed to insert file into database', 5);
            }
        }

        return $result !== FALSE ? $fileId : $result;
    }

    /**
     * Parses the array with parameters to check whether or not a presentation will be created
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) < 3) {
            throw new \Exception('Expected 3 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['title']) || strlen($parameters['title']) < 3) {
            throw new \Exception('Missing parameter (string) "title" with a minimum length of 3 characters', 2);
        } elseif(!isset($parameters['type'])) {
            throw new \Exception('Missing parameter (string) "type"', 3);
        } elseif(!isset($parameters['file'])) {
            throw new \Exception('Missing parameter (file) "file" with a valid file type', 4);
        } elseif(in_array($parameters['type'], array('document', 'presentation')) && !in_array(\Helper::getBase64Header($parameters['file']), array('application/pdf'))) {
            throw new \Exception('Type set to "'. $parameters['type'] .'" but file isn\'t a PDF', 5);
        } elseif($parameters['type'] == 'image' && !in_array(\Helper::getBase64Header($parameters['file']), array('image/jpeg', 'image/png', 'image/gif'))) {
            throw new \Exception('Type set to "'. $parameters['type'] .'" but file isn\'t a JPG, PNG or GIF', 6);
        } else {
            $result = TRUE;
        }

        return $result;
    }

    /**
     * Updates the UUID of the file (image) to the given value
     *
     * @param string $uuid - The UUID of the image
     * @param \Models\Grid $grid - The grid the texture is used on
     * @return boolean
     * @throws \Exception
     */
    public function setUuid($uuid, \Models\Grid $grid) {
        $results = FALSE;
        if(\Helper::isValidUuid($uuid)) {
            $db = \Helper::getDB();
            $cacheData = array(
                'gridId'        => $db->escape($grid->getId()),
                'uuid'          => $db->escape($uuid),
                'uuidExpires'   => $db->escape(date('Y-m-d H:i:s', strtotime('+'. $grid->getCacheTime())))
            );

            $cacheId = $db->insert('cached_assets', $cacheData);
            $cacheFileData = array(
                'fileId'        => $db->escape($this->file->getId()),
                'cacheId'       => $db->escape($cacheId)
            );

            $results = $db->insert('document_files_cache', $cacheFileData);
        } else {
            throw new \Exception('Invalid UUID provided', 2);
        }

        if($results === FALSE) {
            throw new \Exception('Updating UUID failed', 1);
        }
        return $results !== FALSE;
    }

    /**
     * Updates the groups for this file
     *
     * @param array $parameters
     *              * array groups - A list with group objects or a list of groupIds
     * @return boolean
     */
    public function updateFileGroups($parameters) {
        $groups = array();
        if(isset($parameters['groups'][0]) && is_array($parameters['groups'][0])) {
            foreach($parameters['groups'] as $group) {
                $groups[] = $group['id'];
            }
        } else {
            $group = $parameters['groups'];
        }

        // Get difference between existing groups and new groups
        $oldGroups = array();
        $this->file->getGroupsFromDatabase();
        foreach($this->file->getGroups() as $group) {
            $oldGroups[] = $group->getId();
        }

        // Remove unset groups
        $removeIds  = array_diff($oldGroups, $groups);
        $remove     = $this->removeGroups($removeIds);
        // Add new groups
        $addIds     = array_diff($groups, $oldGroups);
        $add        = $this->addGroups($addIds);

        return $remove || $add;
    }

    /**
     * Add file to given groups
     *
     * @param array $groupIds
     * @return boolean
     */
    public function addGroups($groupIds) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($groupIds as $id) {
            $data = array(
                'groupId'       => $db->escape($id),
                'documentId'    => $db->escape($this->file->getId())
            );
            $result = $db->insert('group_documents', $data);
        }
        return $result;
    }

    /**
     * Removes the file from the groups with the given groupIds
     *
     * @param array $groupIds
     * @return boolean
     */
    public function removeGroups($groupIds) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($groupIds as $id) {
            $db->where('groupId', $db->escape($id));
            $result = $db->delete('group_documents');
        }
        return $result;
    }

    /**
     * Validates the parameters used to update groups
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersGroups($parameters) {
        $result = FALSE;
        if(!isset($parameters['groups']) || !is_array($parameters['groups']) || (!empty($parameters['groups']) && !is_array($parameters['groups'][0]) && !is_numeric($parameters['groups'][0]))) {
            throw new \Exception('Parameter "groups" (array) not set.');
        } else {
            $result = TRUE;
        }

        return $result;
    }
}