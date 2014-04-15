<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.2a
 * @date April 10th, 2014
 * @since April 2nd, 2014
 */
class File implements SimpleModel {
    /**
     * Document ID
     * @var integer
     */
    private $id;
    /**
     * Title of document
     * @var string
     */
    private $title;
    /**
     * Creation date time, YYYY-MM-DD HH:mm:ss
     * @var string
     */
    private $creationDate;
    /**
     * Date of last modification, YYYY-MM-DD HH:mm:ss
     * @var string
     */
    private $modificationDate;
    /**
     * The id of the owner
     * @var \Models\User
     */
    private $user;
    /**
     * Type of document
     * @var string
     */
    private $type;
    /**
     * Document source file (extension)
     * @var string
     */
    private $file;

    /**
     * Constructs a new document with the given id and optional the given slide
     *
     * @param integer $id - ID of this presentation
     * @param string $type - [Optional] document type
     * @param string $title - [Optional] Title of document
     * @param \Models\User $user - [Optional] the owner of this document
     * @param string $creationDate - [Optional] Creation date time, YYYY-MM-DD HH:mm:ss
     * @param string $modificationDate - [Optional] Date of last modification, YYYY-MM-DD HH:mm:ss
     * @param string $file - [Optional] The file name and extension of this source file
     */
	public function __construct($id, $type = '', $title = '', $user = NULL, $creationDate = '', $modificationDate = '', $file = '') {
		$this->id               = $id;
        $this->type             = $type;
        $this->title            = $title;
        $this->creationDate     = $creationDate;
        $this->modificationDate = $modificationDate;
        $this->user             = $user;
        $this->file             = $file;
	}

    /**
     * Fetches the meta data from the database
     *
     * @throws Exception
     */
    public function getInfoFromDatabase() {
        $db = \Helper::getDB();
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('d.id', $db->escape((int) $this->getId()));
        // Prevent getting a document with a presentation ID or some other wrong combination
        if($this->getType() != '') {
            $db->where('d.type', $db->escape($this->getType()));
        }
        $result = $db->getOne('documents d', '*, d.id AS documentId, u.id AS userId');

        if($result) {
            $this->title            = $result['title'];
            $this->type             = $result['type'];
            $this->creationDate     = $result['creationDate'];
            $this->modificationDate = $result['modificationDate'];
            $this->user             = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $this->file             = $result['file'];
        } else {
            throw new \Exception('Document not found', 5);
        }
    }

    /**
     * Returns the title from this document
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Returns the ID of this document
     *
     * @return integer
     */
	public function getId() {
		return $this->id;
	}

    /**
     * Get the owner of this document
     *
     * @return \Models\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Returns the local path to the document's folder
     *
     * @return string
     */
    public function getPath() {
        return FILES_LOCATION . DS . $this->getType() . DS . $this->getId();
    }

    /**
     * Returns the type of document
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns the source file for this document
     *
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Returns the API url of this document
     * This can be extended by adding: 'slide/x/' to retrieve slide number x for a presentation
     * or with 'image/' to retrieve the resized image of an image document
     *
     * @return string
     */
    public function getApiUrl() {
        if(in_array($this->getType(), array('presentation', 'document'))) {
            return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/'. $this->getType() .'/'. $this->getId() .'/';
        } else {
            return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/file/'. $this->getId() .'/';
        }
    }

    /**
     * Returns the creation date of this document
     *
     * @return string yyyy-mm-dd hh:mm:ss
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Returns the modification date of this document
     *
     * @return string yyyy-mm-dd hh:mm:ss
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * Returns the file with headers to the browser
     * WARNING: Because of the headers and everything, this can only be used when not outputting any content
     */
    public function getOriginalFile() {
        $file       = $this->getPath() . DS . $this->getFile();
        $finfo      = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype   = finfo_file($finfo, $file);
        finfo_close($finfo);
        header("Content-Type: ". $mimetype);
        readfile($file);
    }

    /**
     * Gets the cached textures from the database (if any available) for this file
     *
     * @return array
     */
    public function getCache() {
        $db         = \Helper::getDB();
        $db->join('cached_assets c', 'dc.cacheId = c.id', 'LEFT');
        $db->where('dc.fileId', $db->escape($this->getId()));
        $results    = $db->get('document_files_cache dc');

        return $results;
    }
}
