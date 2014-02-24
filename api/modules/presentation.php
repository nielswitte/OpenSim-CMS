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

        $this->setRoutes();
    }

    /**
     * Initiates all routes for this module
     */
    public function setRoutes() {
        $this->api->addRoute("/presentations\/?$/",                               "getPresentations",     $this, "GET",  TRUE);  // Get list with 50 presentations
        $this->api->addRoute("/presentations\/(\d+)\/?$/",                        "getPresentations",     $this, "GET",  TRUE);  // Get list with 50 presentations starting at the given offset
        $this->api->addRoute("/presentation\/(\d+)\/?$/",                         "getPresentationById",  $this, "GET",  TRUE);  // Select specific presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/?$/",           "getSlideById",         $this, "GET",  TRUE);  // Get slide from presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/?$/",           "updateSlideUuid",      $this, "PUT",  TRUE);  // Update slide UUID for given slide of presentation
        $this->api->addRoute("/presentation\/(\d+)\/slide\/(\d+)\/image\/?$/",    "getSlideImageById",    $this, "GET",  TRUE);  // Get only the image of a given presentation slide
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
        $params         = array('presentation', $args[1], 50);
        $resutls        = $db->rawQuery("SELECT * FROM documents WHERE type = ? ORDER BY creationDate DESC LIMIT ?, ?", $params);
        // Process results
        $data           = array();
        $x              = 1;
        foreach($resutls as $result) {
            $presentation = new \Models\Presentation($result['id'], 0, $result['title'], $result['ownerId'], $result['creationDate'], $result['modificationDate']);
            $data[$x]     = self::getPresentationData($presentation);
            $x++;
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
        return self::getPresentationData($presentation);
    }

    /**
     * Format the presentation data to the desired format
     *
     * @param \Models\Presentation $presentation
     * @return array
     */
    private function getPresentationData(\Models\Presentation $presentation) {
        $data = array();
        $data['type']               = 'presentation';
        $data['title']              = $presentation->getTitle();
        $data['presentationId']     = $presentation->getPresentationId();
        $data['ownerId']            = $presentation->getOwnerId();
        $slides     = array();
        $x          = 1;
        foreach($presentation->getSlides() as $slide) {
            $slides[$x] = $this->getSlideData($presentation, $slide);
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
     * @return array
     */
    private function getSlideData(\Models\Presentation $presentation, \Models\Slide $slide) {
        $cachedTextures = array();
        foreach($slide->getCache() as $cache) {
            $cachedTextures[$cache['gridId']] = array(
                'uuid'      => $cache['uuid'],
                'expires'   => $cache['uuidExpires'],
                'isExpired' => $cache['uuidExpires'] > time() ? 1 : 0
            );
        }
        $data = array(
            'number' => $slide->getNumber(),
            'image' => $presentation->getApiUrl() . 'slide/' . $slide->getNumber() . '/image/',
            'cache' => $cachedTextures
        );
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
        $slide          = $presentation->getSlide($args[2]);
        $data           = $this->getSlideData($presentation, $slide);
        return $data;
    }

    /**
     * Get slide image for the given slide
     *
     * @param array $args
     * @throws \Exception
     */
    public function getSlideImageById($args) {
        // Get presentation and slide details
        $presentation   = new \Models\Presentation($args[1], $args[2]);
        $slidePath      = $presentation->getPath() . DS . $presentation->getCurrentSlide() .'.jpg';

        // Show image if exists
        if(file_exists($slidePath)) {
            require_once dirname(__FILE__) .'/../../includes/class.Images.php';
            $resize = new \Image($slidePath);
            // resize when needed
            if($resize->getWidth() > IMAGE_WIDTH || $resize->getHeight() > IMAGE_HEIGHT) {
                $resize->resize(1024,1024,'fit');
                $resize->save($presentation->getSlideId(), FILES_LOCATION . DS . PRESENTATIONS . DS . $presentation->getPresentationId(), 'jpg');
            }
            unset($resize);

            // Fill remaining of image with black
            $image = new \Image(FILES_LOCATION . DS . PRESENTATIONS . DS .'background.jpg');
            $image->addWatermark($slidePath);
            $image->writeWatermark(100, 0, 0, 'c', 'c');
            $image->resize(1024,1024,'fit');
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
    public function updateSlideUuid($args) {
        $putUserData    = file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']);
        $parsedPutData  = (\Helper::parsePutRequest($putUserData));
        $gridId         = isset($parsedPutData['gridId']) ? $parsedPutData['gridId'] : '';
        $postUuid       = isset($parsedPutData['uuid']) ? $parsedPutData['uuid'] : '';

        // Get presentation and slide details
        $presentation   = new \Models\Presentation($args[1]);
        $slide          = $presentation->getSlide($args[2]);

        // Get grid details
        $grid           = new \Models\Grid($gridId);
        $grid->getInfoFromDatabase();

        // Update
        $slideCtrl      = new \Controllers\SlideController($slide);
        $data           = $slideCtrl->setUuid($postUuid, $grid);

        return $data;
    }
}