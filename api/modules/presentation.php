<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/module.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 24th, 2014
 */
class Presentation extends Module{
    private $api;

    /**
     * Constructs a new module for the given API
     *
     * @param \API\API $api
     */
    public function __construct(\API\API $api) {
        $this->api = $api;
        $this->setName('presentation');
        $this->api->addModule($this->getName(), $this);

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/presentations\/?$/",                                     "getPresentations",         $this, "GET",  \Auth::READ);  // Get list with 50 presentations
        $this->api->addRoute("/presentations\/(\d+)\/?$/",                              "getPresentations",         $this, "GET",  \Auth::READ);  // Get list with 50 presentations starting at the given offset
        $this->api->addRoute("/presentation\/?$/",                                      "createPresentation",       $this, "POST", \Auth::WRITE); // Create a presentation
        $this->api->addRoute("/presentation\/(\d+)\/?$/",                               "getPresentationById",      $this, "GET",  \Auth::READ);  // Select specific presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/?$/",                 "getSlideById",             $this, "GET",  \Auth::READ);  // Get slide from presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/number\/(\d+)\/?$/",         "getSlideByNumber",         $this, "GET",  \Auth::READ);  // Get slide from presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/number\/(\d+)\/?$/",         "updateSlideUuidByNumber",  $this, "PUT",  \Auth::WRITE); // Update slide UUID for given slide of presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/number\/(\d+)\/image\/?$/",  "getSlideImageByNumber",    $this, "GET",  \Auth::READ);  // Get only the image of a given presentation slide
    }


    /**
     * Gets a list of presentations starting at the given argument offset
     *
     * @param array $args
     * @return array
     */
    public function getPresentations($args) {
        $db             = \Helper::getDB();
        // Offset parameter given?
        $args[1]        = isset($args[1]) ? $args[1] : 0;
        // Get 50 presentations from the given offset
        $params         = array('presentation', $db->escape($args[1]), 50);
        $resutls        = $db->rawQuery("SELECT * FROM documents WHERE type = ? ORDER BY creationDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $presentation   = new \Models\Presentation($result['id'], 0, $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate']);
            $data[]         = $this->getPresentationData($presentation, FALSE);
        }
        return $data;
    }

    /**
     * Get presentation details for the given presentation
     *
     * @param array $args
     * @return array
     */
    public function getPresentationById($args) {
        $presentation = new \Models\Presentation($args[1]);
        $presentation->getInfoFromDatabase();
        return $this->getPresentationData($presentation);
    }

    /**
     * Creates a new presentation based on the given POST data
     *
     * @param array $args
     * @return array
     */
    public function createPresentation($args) {
        $input              = \Helper::getInput(TRUE);
        $presentationCtrl   = new \Controllers\PresentationController();
        if($presentationCtrl->validateParametersCreate($input)) {
            $data = $presentationCtrl->createPresentation($input);
        }

        // Format the result
        $result = array(
            'success'   => ($data !== FALSE ? TRUE : FALSE),
            'id'        => ($data !== FALSE ? $data : 0)
        );

        return $result;
    }

    /**
     * Format the presentation data to the desired format
     *
     * @param \Models\Presentation $presentation
     * @param boolean $full - [Optional] Show all information about the presentation and slides
     * @return array
     */
    public function getPresentationData(\Models\Presentation $presentation, $full = TRUE) {
        $data = array();
        $data['id']                 = $presentation->getId();
        $data['type']               = 'presentation';
        $data['title']              = $presentation->getTitle();
        $data['ownerId']            = $presentation->getOwnerId();
        $slides     = array();
        $x          = 1;
        foreach($presentation->getSlides() as $slide) {
            $slides[$x] = $this->getSlideData($presentation, $slide, $full);
            $x++;
        }

        $data['slides']             = $slides;
        $data['slidesCount']        = $presentation->getNumberOfSlides();
        $data['creationDate']       = $presentation->getCreationDate();
        $data['modificationDate']   = $presentation->getModificationDate();

        return $data;
    }

    /**
     * Formats the data for the given slide
     *
     * @param \Models\Presentation $presentation
     * @param \Models\Slide $slide
     * @param boolean $full - [Optional] Show all information about the slide
     * @return array
     */
    public function getSlideData(\Models\Presentation $presentation, \Models\Slide $slide, $full = TRUE) {
        $data = array(
            'id'    => $slide->getId(),
            'number'=> $slide->getNumber(),
            'image' => $presentation->getApiUrl() . 'slide/number/' . $slide->getNumber() . '/image/'
        );

        // Show additional information
        if($full) {
            $cachedTextures = array();
            foreach($slide->getCache() as $cache) {
                $cachedTextures[$cache['gridId']] = array(
                    'uuid'      => $cache['uuid'],
                    'expires'   => $cache['uuidExpires'],
                    'isExpired' => strtotime($cache['uuidExpires']) > time() ? 0 : 1
                );
            }

            $data['cache'] = $cachedTextures;
        }
        return $data;
    }

    /**
     * Get slide details for the given slide
     *
     * @param array $args
     * @return array
     */
    public function getSlideByNumber($args) {
        $presentation   = new \Models\Presentation($args[1]);
        $slide          = $presentation->getSlideByNumber($args[2]);
        $data           = $this->getSlideData($presentation, $slide);
        return $data;
    }

    /**
     * Get slide details for the given slide
     *
     * @param array $args
     * @return array
     */
    public function getSlideById($args) {
        $presentation   = new \Models\Presentation($args[1]);
        $slide          = $presentation->getSlideById($args[2]);
        $data           = $this->getSlideData($presentation, $slide);
        return $data;
    }

    /**
     * Get slide image for the given slide
     *
     * @param array $args
     * @throws \Exception
     */
    public function getSlideImageByNumber($args) {
        // Get presentation and slide details
        $presentation   = new \Models\Presentation($args[1], $args[2]);
        $slidePath      = $presentation->getPath() . DS .'slide-'. ($presentation->getCurrentSlide() < 10 ? '0'. $presentation->getCurrentSlide() : $presentation->getCurrentSlide()) .'.'. IMAGE_TYPE;

        // Show image if exists
        if(file_exists($slidePath)) {
            require_once dirname(__FILE__) .'/../../includes/class.Images.php';
            $resize = new \Image($slidePath);
            // resize when needed
            if($resize->getWidth() > IMAGE_WIDTH || $resize->getHeight() > IMAGE_HEIGHT) {
                $resize->resize(IMAGE_WIDTH,IMAGE_HEIGHT,'fit');
                $resize->save('slide-'. ($presentation->getCurrentSlide() < 10 ? '0'. $presentation->getCurrentSlide() : $presentation->getCurrentSlide()), $presentation->getPath(), IMAGE_TYPE);
            }
            unset($resize);

            // Fill remaining of image with black
            $image = new \Image(FILES_LOCATION . DS . $presentation->getType() . DS .'background.jpg');
            $image->resize(IMAGE_WIDTH,IMAGE_HEIGHT,'fit');
            $image->addWatermark($slidePath);
            $image->writeWatermark(100, 0, 0, 'c', 'c');
            $image->display();
        } else {
            throw new Exception("Requested slide does not exists", 5);
        }
    }

    /**
     * Updates the slide with the given UUID
     *
     * @param array $args
     * @return boolean
     */
    public function updateSlideUuidByNumber($args) {
        $parsedPutData  = \Helper::getInput(TRUE);
        $gridId         = isset($parsedPutData['gridId']) ? $parsedPutData['gridId'] : '';
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get presentation and slide details
        $presentation   = new \Models\Presentation($args[1]);
        $slide          = $presentation->getSlideByNumber($args[2]);
        if($slide !== FALSE) {
            // Get grid details
            $grid           = new \Models\Grid($gridId);
            $grid->getInfoFromDatabase();

            // Update
            $slideCtrl      = new \Controllers\SlideController($slide);
            $data           = $slideCtrl->setUuid($postUuid, $grid);
        } else {
            throw new \Exception('Slide does not exist', 6);
        }

        // Format the result
        $result = array(
            'success' => ($data !== FALSE ? TRUE : FALSE),
        );

        return $result;
    }
}
