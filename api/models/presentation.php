<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/file.php';

/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.3
 * @date April 3rd, 2014
 * @since February 10th, 2014
 */
class Presentation extends File {
	private $currentSlide;
    private $slides = array();

    /**
     * Constructs a new presentation with the given id and optional the given slide
     *
     * @param integer $id - ID of this presentation
     * @param integer $slide - [optional] Slide to show
     * @param string $title - [optional] Title of presentation
     * @param \Models\User $user - [optional] The owner of this document
     * @param datetime $creationDate - [optional] Creation date time, yyyy-mm-dd hh:mm:ss
     * @param datetime $modificationDate - [optional] Date of last modification, yyyy-mm-dd hh:mm:ss
     * @param string $file - [Optional] The file name and extension of this source file
     */
	public function __construct($id, $slide = 0, $title = '', $user = NULL, $creationDate = '', $modificationDate = '', $file = '') {
		$this->currentSlide     = $slide;
        parent::__construct($id, 'presentation', $title, $user, $creationDate, $modificationDate, $file);
	}

    /**
     * Returns the slide number, starts at 1
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
            $db->orderBy('s.id', 'asc');
            $db->join('comments c', 'c.itemId = s.id AND c.type = "slide"', 'LEFT');
            $db->groupBy('s.id');
            $results = $db->get('document_slides s', NULL, '*, count(c.id) as commentsCount, s.id as id');

            $i = 1;
            foreach($results as $result) {
                $hasComments = $result['commentsCount'] > 0 ? TRUE : FALSE;
                $this->slides[$i] = new \Models\Slide($result['id'], $i, $this->getPath(). DS . $i .'.'. IMAGE_TYPE, $hasComments);
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
            throw new \Exception('Slide does not exist', 6);
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

        // Slide exists? (array starts counting at 1)
        if(count($this->getSlides()) > 0) {
            foreach($this->getSlides() as $slide) {
                if($slide->getId() == $id) {
                    return $slide;
                }
            }
        }
        throw new \Exception('Slide does not exist', 6);
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
     * Returns the path to the thumbnails of this presentation
     *
     * @return string
     */
    public function getThumbnailPath() {
        return $this->getPath() . DS . 'thumbs';
    }
}