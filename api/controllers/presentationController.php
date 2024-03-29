<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the presentation controller
 *
 * @author Niels Witte
 * @version 0.4
 * @date May 12, 2014
 * @since March 10, 2014
 */
class PresentationController {
    /**
     * The presentation object
     * @var \Models\Presentation
     */
    private $presentation;

    /**
     * Constructs a new controller with the given presentation
     *
     * @param \Models\Presentation $presentation
     */
    public function __construct(\Models\Presentation $presentation = NULL) {
        $this->presentation = $presentation;
    }

    /**
     * Creates a presentation
     *
     * @param array $parameters
     *              * string file - Base64 encoded file
     *              * string title - The document title
     *              * string type - Should be presentation
     * @return integer or boolean FALSE on failure
     * @throws \Exception
     */
    public function createPresentation($parameters) {
        $result         = FALSE;
        // Prepare additional information
        $file           = \Helper::getBase64Content($parameters['file'], TRUE);
        $header         = \Helper::getBase64Header($parameters['file']);
        $extension      = \Helper::getExtentionFromContentType($header);
        $db             = \Helper::getDB();

        // Insert main document data into database to get a autoincrement ID
        $data           = array(
            'title'         => $db->escape(\Helper::filterString($parameters['title'], TRUE)),
            'type'          => $db->escape($parameters['type']),
            'ownerId'       => $db->escape(\Auth::getUser()->getId()),
            'creationDate'  => $db->now(),
            'file'          => $db->escape('source.'. $extension)
        );
        $fileId = $db->insert('documents', $data);

        // Insert successful?
        if($fileId !== FALSE) {
            $filename = \Helper::saveFile($fileId .'.'. $extension, TEMP_LOCATION, $file);

            // File saved successful to temp?
            if($filename !== FALSE && file_exists($filename)) {
                // Save slides as separate JPGs
                $slidesDirectory = FILES_LOCATION . DS . $parameters['type'] . DS . $fileId;
                $slidesPath      = $slidesDirectory . DS . 'slide';
                \Helper::pdf2jpeg($filename, $slidesPath);
                // Move temp file
                \Helper::moveFile($filename, $slidesDirectory . DS .'source.'. $extension);
                // Save successful?
                $result = $this->setPresentationSlides($fileId) ? $fileId : $result;
            }
        }

        // Not a valid result? Undo everything!
        if($result === FALSE) {
            if($fileId !== FALSE) {
                // Temp file still existing?
                if(file_exists($filename)) {
                    unlink($filename);
                }

                // Remove slides from DB
                $db->where('documentId', $db->escape($fileId));
                $db->delete('document_slides');

                // Remove document from DB
                $db->where('id', $db->escape($fileId));
                $db->delete('documents');

                // Remove the created files
                if(isset($slidesDirectory)) {
                    \Helper::removeDirAndContents($slidesDirectory);

                    throw new \Exception('Failed to save slides to storage', 7);
                } else {
                    throw new \Exception('Failed to save pdf to temp storage', 6);
                }
            } else {
                throw new \Exception('Failed to insert presentation into database', 5);
            }
        }
        return $result;
    }

    /**
     * Links the slides found in the directory of the given presentation to the presentation
     *
     * @param integer $presentationId
     * @return boolean
     */
    public function setPresentationSlides($presentationId) {
        $db              = \Helper::getDB();
        $slidesDirectory = FILES_LOCATION . DS . 'presentation' . DS . $presentationId;
        $result          = FALSE;
        if (file_exists($slidesDirectory) && glob($slidesDirectory . DS . '*.' . IMAGE_TYPE) != false) {
            $slidesCount = count(glob($slidesDirectory . DS . '*.' . IMAGE_TYPE));
            // Save all slides to the database
            for ($i = 1; $i <= $slidesCount; $i++) {
                // Has to be done one by one...
                // @todo improve this for multiple insert
                $slides = array(
                    'id'         => '',
                    'documentId' => $db->escape($presentationId)
                );
                $slideId = $db->insert('document_slides', $slides);
            }
            // Finally update the result?
            $result = ($slideId !== FALSE ? TRUE : FALSE);
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
        } elseif(!isset($parameters['title']) || strlen($parameters['title']) < 3) {
            throw new \Exception('Missing parameter (string) "title", with a minimum length of 3 characters', 2);
        } elseif(!isset($parameters['type']) || $parameters['type'] != 'presentation') {
            throw new \Exception('Missing parameter (string) "type" which should be "presentation"', 3);
        } elseif (!isset($parameters['file'])) {
            throw new \Exception('Missing parameter (file) "file" with a valid file type', 4);
        } elseif(!in_array(\Helper::getBase64Header($parameters['file']), array('application/pdf'))) {
            throw new \Exception('Type set to "'. $parameters['type'] .'" but file isn\'t a PDF', 5);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}
