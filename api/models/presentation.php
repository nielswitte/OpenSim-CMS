<?php
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
    private $ownerUuid;

    /**
     * Constructs a new presentation with the given id and optional the given slide
     *
     * @param Integer $id - ID of this presentation
     * @param Integer $slide [optional] - slide to show
     */
	public function __construct($id, $slide = 0) {
		$this->presentationId = $id;
		$this->currentSlide = $slide;
        $this->getInfoFromDatabase();
	}

    /**
     * Fetches the meta data from the database
     *
     * @throws Exception
     */
    public function getInfoFromDatabase() {
        $db = Helper::getDB();
        $db->where('id', (int) $this->getPresentationId());
        $results = $db->get('presentations', 1);

        if(!empty($results)) {
            $this->title            = $results[0]['title'];
            $this->creationDate     = $results[0]['creationDate'];
            $this->modificationDate = $results[0]['modificationDate'];
            $this->ownerUuid          = $results[0]['ownerUuid'];
        } else {
            throw new Exception("Presentation not found", 5);
        }
    }

    /**
     * Returns the title from this presentation
     *
     * @return String
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Returns the ID of this presentation
     *
     * @return Integer
     */
	public function getPresentationId() {
		return $this->presentationId;
	}

    /**
     * Returns the slide number, starts at 0
     *
     * @return Integer
     */
	public function getCurrentSlide() {
		return $this->currentSlide;
	}

    /**
     * Get the UUID of the owner of this presentation
     *
     * @return String
     */
    public function getOwnerUuid() {
        return $this->ownerUuid;
    }

    /**
     * Returns an array with slides of this presentation
     *
     * @return Array
     */
    public function getSlides() {
        if(empty($this->slides)) {
            $db = Helper::getDB();
            $params = array($this->getPresentationId(), 'number');
            $results = $db->rawQuery("SELECT * FROM presentation_slides WHERE presentationId = ? ORDER BY ? ASC", $params);

            foreach($results as $result) {
                $this->slides[] = new Slide($result['number'], $this->getPath(). DS . $result['number'] .'.jpg', $result['uuid'], $result['uuidUpdated']);
            }
        }

        return $this->slides;
    }

    /**
     * Get the slide with the given number
     *
     * @param Integer $number
     * @return Slide
     * @throws Exception
     */
    public function getSlide($number) {
        if(empty($this->slides)) {
            $this->getSlides();
        }

        $slide = FALSE;
        // Slide exists? (array starts counting at 0)
        if(isset($this->slides[($number-1)])) {
            $slide = $this->slides[($number-1)];
        } else {
            throw new Exception("Slide does not exist", 6);
        }
        return $slide;
    }

    /**
     * Returns the local path to the presentation's folder
     *
     * @return String
     */
    public function getPath() {
        return FILES_LOCATION . DS . PRESENTATIONS . DS . $this->presentationId;
    }

    /**
     * Returns the API url of this presentation
     * This can be extended by adding: 'slide/x/'
     * to retrieve slide number x
     *
     * @return String
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/presentation/'. $this->getPresentationId() .'/';
    }

    /**
     * Counts the number of slides
     *
     * @return Integer
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
     * @return String yyyy-mm-dd hh:mm:ss
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Returns the modification date of this presentation
     *
     * @return String yyyy-mm-dd hh:mm:ss
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

	/**
	 * Function to validate parameters array
	 *
	 * @param Array $parameters
	 * @return Boolean true when all checks passed
     * @throws Exception
	 */
	public static function validateParameters($parameters) {
        // Slide image
		if(count($parameters) == 4) {
            // Check number of parameters and if presentation id is a number
			if(is_numeric($parameters[1]) && $parameters[1] > 0) {
                // Check if parameter slide is given
				if($parameters[2] == 'slide') {
                    // Check if slide is represented by a number
					if(is_numeric($parameters[3]) && $parameters[3] >= 0) {
						return true;
					} else {
						throw new Exception("Expects parameter two to be integer, string given", 4);
					}
				} else {
					throw new Exception("Expects parameter three to be (string) 'slide', '". htmlentities($parameter[2]) ."' given", 3);
				}
			} else {
				throw new Exception("Expects parameter two to be integer, string given", 2);
			}
        // Presentation data
		} elseif(count($parameters) == 2) {
            // Check number of parameters and if presentation id is a number
            if(is_numeric($parameters[1]) && $parameters[1] > 0) {
                return true;
			} else {
				throw new Exception("Expects parameter two to be integer, string given", 2);
			}

        } else {
			throw new Exception("Invalid number of parameters", 1);
		}
		return false;
	}
}


