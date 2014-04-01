<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/document.php';
require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.2
 * @date April 1st, 2014
 * @since February 10th, 2014
 */
class Document implements SimpleModel {
	private $id;
    private $title;
    private $creationDate;
    private $modificationDate;
    private $ownerId;
    private $type;

    /**
     * Constructs a new document with the given id and optional the given slide
     *
     * @param integer $id - ID of this presentation
     * @param integer $type - [optional] document type
     * @param string $title - [optional] Title of document
     * @param integer $ownerId - [optional] ID of the owner
     * @param datetime $creationDate - [optional] Creation date time, yyyy-mm-dd hh:mm:ss
     * @param datetime $modificationDate - [optional] Date of last modification, yyyy-mm-dd hh:mm:ss
     */
	public function __construct($id, $type = '', $title = '', $ownerId = '', $creationDate = '', $modificationDate = '') {
		$this->id               = $id;
        $this->type             = $type;
        $this->title            = $title;
        $this->creationDate     = $creationDate;
        $this->modificationDate = $modificationDate;
        $this->ownerId          = $ownerId;
	}

    /**
     * Fetches the meta data from the database
     *
     * @throws Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape((int) $this->getId()));
        $result = $db->getOne('documents');

        if($result) {
            $this->title            = $result['title'];
            $this->type             = $result['type'];
            $this->creationDate     = $result['creationDate'];
            $this->modificationDate = $result['modificationDate'];
            $this->ownerId          = $result['ownerId'];
        } else {
            throw new \Exception('Document not found', 5);
        }
    }

    /**
     * Returns the title from this presentation
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Returns the ID of this presentation
     *
     * @return integer
     */
	public function getId() {
		return $this->id;
	}

    /**
     * Get the UUID of the owner of this presentation
     *
     * @return string
     */
    public function getOwnerId() {
        return $this->ownerId;
    }

    /**
     * Returns the local path to the presentation's folder
     *
     * @return string
     */
    public function getPath() {
        return FILES_LOCATION . DS . $this->getType() . DS . $this->getId();
    }

    public function getType() {
        return $this->type;
    }

    /**
     * Returns the API url of this presentation
     * This can be extended by adding: 'slide/x/'
     * to retrieve slide number x
     *
     * @return string
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/'. $this->getType() .'/'. $this->getId() .'/';
    }

    /**
     * Returns the creation date of this presentation
     *
     * @return string yyyy-mm-dd hh:mm:ss
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Returns the modification date of this presentation
     *
     * @return string yyyy-mm-dd hh:mm:ss
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }
}