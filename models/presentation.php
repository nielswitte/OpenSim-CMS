<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}

require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 10th, 2014
 */
class Presentation implements SimpleModel {
	private $presentationId;
	private $currentSlide;
    private $slides = array();
    private $title;
    private $creationDate;
    private $modificationDate;
    private $ownerId;

    /**
     * Constructs a new presentation with the given id and optional the given slide
     *
     * @param integer $id - ID of this presentation
     * @param integer $slide - [optional] Slide to show
     * @param string $title - [optional] Title of presentation
     * @param integer $ownerId - [optional] ID of the owner
     * @param datetime $creationDate - [optional] Creation date time, yyyy-mm-dd hh:mm:ss
     * @param datetime $modificationDate - [optional] Date of last modification, yyyy-mm-dd hh:mm:ss
     */
	public function __construct($id, $slide = 0, $title = '', $ownerId = '', $creationDate = '', $modificationDate = '') {
		$this->presentationId   = $id;
		$this->currentSlide     = $slide;
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
        $db->where('id', (int) $this->getPresentationId());
        $db->where('type', 'presentation');
        $results = $db->get('documents', 1);

        if(!empty($results)) {
            $this->title            = $results[0]['title'];
            $this->creationDate     = $results[0]['creationDate'];
            $this->modificationDate = $results[0]['modificationDate'];
            $this->ownerId          = $results[0]['ownerId'];
        } else {
            throw new \Exception("Presentation not found", 5);
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
	public function getPresentationId() {
		return $this->presentationId;
	}

    /**
     * Returns the slide number, starts at 0
     *
     * @return integer
     */
	public function getCurrentSlide() {
		return $this->currentSlide;
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
     * Returns an array with slides of this presentation
     *
     * @return array
     */
    public function getSlides() {
        if(empty($this->slides)) {
            $db = \Helper::getDB();
            $db->where('documentId', $this->getPresentationId());
            $db->orderBy('id', 'asc');
            $results = $db->get('document_slides');

            $i = 1;
            foreach($results as $result) {
                $this->slides[$i] = new \Models\Slide($result['id'], $i, $this->getPath(). DS . $i .'.jpg');
                $i++;
            }
        }

        return $this->slides;
    }

    /**
     * Get the slide with the given number
     *
     * @param integer $number
     * @return \Models\Slide
     * @throws \Exception
     */
    public function getSlideByNumber($number) {
        if(empty($this->slides)) {
            $this->getSlides();
        }

        $slide = FALSE;
        // Slide exists? (array starts counting at 1)
        if(isset($this->slides[$number])) {
            $slide = $this->slides[$number];
        } else {
            throw new \Exception("Slide does not exist", 6);
        }
        return $slide;
    }

    /**
     * Get the slide with the given id
     *
     * @param integer $id
     * @return \Models\Slide
     * @throws \Exception
     */
    public function getSlideById($id) {
        if(empty($this->slides)) {
            $this->getSlides();
        }

        $result = FALSE;
        // Slide exists? (array starts counting at 1)
        if(count($this->getSlides()) > 0) {
            foreach($this->getSlides() as $slide) {
                if($slide->getId() == $id) {
                    $result = $slide;
                    break;
                }
            }
        } else {
            throw new \Exception("Slide does not exist", 6);
        }
        return $result;
    }

    /**
     * Returns the local path to the presentation's folder
     *
     * @return string
     */
    public function getPath() {
        return FILES_LOCATION . DS . PRESENTATIONS . DS . $this->presentationId;
    }

    /**
     * Returns the API url of this presentation
     * This can be extended by adding: 'slide/x/'
     * to retrieve slide number x
     *
     * @return string
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/presentation/'. $this->getPresentationId() .'/';
    }

    /**
     * Counts the number of slides
     *
     * @return integer
     */
    public function getNumberOfSlides() {
        if(empty($this->slides)) {
            $this->getSlides();
        }
        return count($this->slides);
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