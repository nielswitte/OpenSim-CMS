<?php
namespace Models;

if(EXEC != 1) {
	die('Invalid request');
}

require_once dirname(__FILE__) .'/document.php';

/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 10th, 2014
 */
class Presentation extends Document {
	private $currentSlide;
    private $slides = array();

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
		$this->currentSlide     = $slide;
        $type                   = 'presentation';
        parent::__construct($id, $type, $title, $ownerId, $creationDate, $modificationDate);
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
     * Returns an array with slides of this presentation
     *
     * @return array
     */
    public function getSlides() {
        if(empty($this->slides)) {
            $db = \Helper::getDB();
            $db->where('documentId', $db->escape($this->getId()));
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
}