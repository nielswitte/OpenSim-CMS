<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the Document controller
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since March 10th, 2014
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

    /**
     * Creates a new document
     *
     * @param array $parameters
     *              * string file - Base64 encoded file
     *              * string title - The document title
     *              * string type - Name of the type of document
     * @return integer or boolean FALSE on failure
     * @throws \Exception
     */
    public function createDocument($parameters) {
        $result         = FALSE;
        // Prepare additional information
        $file           = \Helper::getBase64Content($parameters['file'], TRUE);
        $header         = \Helper::getBase64Header($parameters['file']);
        $extension      = \Helper::getExtentionFromContentType($header);
        $db             = \Helper::getDB();

        // Insert main document data into database to get a autoincrement ID
        $data           = array(
            'title'         => $db->escape($parameters['title']),
            'type'          => $db->escape($parameters['type']),
            'ownerId'       => $db->escape(\Auth::getUser()->getId()),
            'creationDate'  => $db->escape(date('Y-m-d H:m:s'))
        );
        $fileId = $db->insert('documents', $data);
        // Insert successful?
        if($fileId !== FALSE) {
            $filename = \Helper::saveFile($fileId .'.'. $extension, TEMP_LOCATION, $file);

            // File saved successful to temp?
            if($filename !== FALSE && file_exists($filename)) {
                // Finally process images
                if($data['type'] == 'image') {
                    $destination = FILES_LOCATION . DS .'images'. DS . $fileId . DS . $fileId .'.'. $extension;
                    $result = \Helper::imageResize($filename, $destination);
                } else {

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

                // Remove document from DB
                $db->where('id', $db->escape($fileId));
                $db->delete('documents');

                throw new \Exception('Failed to save file to temp storage', 6);
            } else {
                throw new \Exception('Failed to insert document into database', 5);
            }
        }

        return $result;
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
        } elseif (!isset($parameters['file'])) {
            throw new \Exception('Missing parameter (file) "file" with a valid file type', 4);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}