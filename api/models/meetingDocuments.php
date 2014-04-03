<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class takes care of the documents/files attached to a meeting
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 3rd, 2014
 * @since March 18th, 2014
 */
class MeetingDocuments {
    private $meeting;
    private $documents = array();

    /**
     * Constructs a new documents list for the given meeting
     *
     * @param \Models\Meeting $meeting
     * @param array $documents - [Optional]
     */
    public function __construct(\Models\Meeting $meeting, $documents = array()) {
        $this->meeting      = $meeting;
        $this->documents   = $documents;
    }

    /**
     * Returns the array with file instances
     *
     * @return array
     */
    public function getDocuments() {
        return $this->documents;
    }

    /**
     * Adds a file to the list
     *
     * @param \Models\File $file
     */
    public function addDocument(\Models\File $file) {
        $this->documents[] = $file;
    }
}
