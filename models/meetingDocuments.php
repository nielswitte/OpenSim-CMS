<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 18th, 2014
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
     * Returns the array with document instances
     *
     * @return array
     */
    public function getDocuments() {
        return $this->documents;
    }

    /**
     * Adds a document to the list
     *
     * @param \Models\Document $document
     */
    public function addDocument(\Models\Document $document) {
        $this->documents[] = $document;
    }
}
