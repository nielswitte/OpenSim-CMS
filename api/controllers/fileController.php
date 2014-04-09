<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the File controller
 *
 * @author Niels Witte
 * @version 0.2c
 * @date April 9th, 2014
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
        $commentCtrl->removeCommentsByItem($this->file->getType(), $this->file->getId());

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
            'title'         => $db->escape($parameters['title']),
            'type'          => $db->escape($parameters['type']),
            'ownerId'       => $db->escape(\Auth::getUser()->getId()),
            'creationDate'  => $db->escape($db->now()),
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
        } elseif(!isset($parameters['title'])) {
            throw new \Exception('Missing parameter (string) "title"', 2);
        } elseif(!isset($parameters['type'])) {
            throw new \Exception('Missing parameter (string) "type"', 3);
        } elseif(!isset($parameters['file'])) {
            throw new \Exception('Missing parameter (file) "file" with a valid file type', 4);
        } elseif(in_array($parameters['type'], array('document', 'presentation')) && !in_array(\Helper::getBase64Header($parameters['file']), array('application/pdf'))) {
            throw new \Exception('Type set to "'. $parameters['type'] .'" but file isn\'t a PDF', 5);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}