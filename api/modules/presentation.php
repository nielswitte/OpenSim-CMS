<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

require_once dirname(__FILE__) .'/module.php';
require_once dirname(__FILE__) .'/../models/slide.php';
require_once dirname(__FILE__) .'/../controllers/slideController.php';
require_once dirname(__FILE__) .'/../models/presentation.php';
require_once dirname(__FILE__) .'/../controllers/presentationController.php';

/**
 * Implements the functions for presentations
 *
 * @author Niels Witte
 * @version 0.4c
 * @date April 10th, 2014
 * @since February 24th, 2014
 */
class Presentation extends Module {
    /**
     * The API to add this module to
     * @var \API\API
     */
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
        $this->api->addRoute("/^\/presentations\/?$/",                                         'getPresentations',             $this, 'GET',  \Auth::READ);  // Get list with 50 presentations
        $this->api->addRoute("/^\/presentations\/(\d+)\/?$/",                                  'getPresentations',             $this, 'GET',  \Auth::READ);  // Get list with 50 presentations starting at the given offset
        $this->api->addRoute("/^\/presentation\/?$/",                                          'createPresentation',           $this, 'POST', \Auth::EXECUTE); // Create a presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/?$/",                                   'getPresentationById',          $this, 'GET',  \Auth::READ);  // Select specific presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/source\/?$/",                           'getPresentationSourceById',    $this, 'GET',  \Auth::READ);  // Download this presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/slide\/(\d+)\/?$/",                     'getSlideById',                 $this, 'GET',  \Auth::READ);  // Get slide from presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/slide\/number\/(\d+)\/?$/",             'getSlideByNumber',             $this, 'GET',  \Auth::READ);  // Get slide from presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/slide\/number\/(\d+)\/?$/",             'updateSlideUuidByNumber',      $this, 'PUT',  \Auth::WRITE); // Update slide UUID for given slide of presentation
        $this->api->addRoute("/^\/presentation\/(\d+)\/slide\/number\/(\d+)\/image\/?$/",      'getSlideImageByNumber',        $this, 'GET',  \Auth::READ);  // Get only the image of a given presentation slide
        $this->api->addRoute("/^\/presentation\/(\d+)\/slide\/number\/(\d+)\/thumbnail\/?$/",  'getSlideThumbnailByNumber',    $this, 'GET',  \Auth::READ);  // Get only the image of a given presentation slide
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
        $db->join('users u', 'd.ownerId = u.id', 'LEFT');
        $db->where('type', 'presentation');
        $db->orderBy('creationDate', 'DESC');
        $resutls        = $db->get('documents d', array($args[1], 50), '*, d.id AS documentId, u.id AS userId');
        // Process results
        $data           = array();
        foreach($resutls as $result) {
            $user           = new \Models\User($result['userId'], $result['username'], $result['email'], $result['firstName'], $result['lastName'], $result['lastLogin']);
            $presentation   = new \Models\Presentation($result['documentId'], 0, $result['title'], $user, $result['creationDate'], $result['modificationDate'], $result['file']);
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
        $data       = $this->api->getModule('file')->getFileData($presentation);
        // Include all data?
        if($full) {
            $slides     = array();
            foreach($presentation->getSlides() as $slide) {
                $slides[] = $this->getSlideData($presentation, $slide, $full);
            }
            $data['slides']         = $slides;
        }
        $data['slidesCount']        = $presentation->getNumberOfSlides();
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
            'id'            => $slide->getId(),
            'number'        => $slide->getNumber(),
            'total'         => $presentation->getNumberOfSlides(),
            'hasComments'   => $slide->hasComments(),
            'image'         => $presentation->getApiUrl() . 'slide/number/' . $slide->getNumber() . '/image/',
            'thumbnail'     => $presentation->getApiUrl() . 'slide/number/' . $slide->getNumber() . '/thumbnail/'
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
            // Retrieve title if not available
            if($presentation->getTitle() == '') {
                $presentation->getInfoFromDatabase();
            }
            $data['presentationTitle']  = $presentation->getTitle();
            $data['cache']              = $cachedTextures;
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
        $presentation->getSlides();
        $slidenr        = str_pad($presentation->getCurrentSlide(), strlen($presentation->getNumberOfSlides()), '0', STR_PAD_LEFT);
        $slidePath      = $presentation->getPath() . DS .'slide-'. $slidenr .'.'. IMAGE_TYPE;

        if(!\Helper::imageResize($slidePath, $slidePath, IMAGE_HEIGHT, IMAGE_WIDTH)) {
            throw new \Exception('Requested slide does not exists', 5);
        } else {
            require_once dirname(__FILE__) .'/../includes/class.Images.php';
            $image = new \Image($slidePath);
            $image->display();
        }
    }

    /**
     * Returns the thumbnail for the given slide
     *
     * @param array $args
     * @throws \Exception
     */
    public function getSlideThumbnailByNumber($args) {
        $presentation   = new \Models\Presentation($args[1], $args[2]);
        $presentation->getSlides();
        $slidenr        = str_pad($presentation->getCurrentSlide(), strlen($presentation->getNumberOfSlides()), '0', STR_PAD_LEFT);
        $slidePath      = $presentation->getPath() . DS .'slide-'. $slidenr .'.'. IMAGE_TYPE;
        $thumbPath      = $presentation->getThumbnailPath() . DS .'slide-'. $slidenr .'.jpg';

        if(!\Helper::imageResize($slidePath, $thumbPath, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_WIDTH)) {
            throw new \Exception('Requested slide does not exists', 5);
        } else {
            require_once dirname(__FILE__) .'/../includes/class.Images.php';
            $image = new \Image($thumbPath);
            $image->display();
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

    /**
     * Gets the presentation's source file
     *
     * @param array $args
     */
    public function getPresentationSourceById($args) {
        $this->api->getModule('file')->getFileSourceById($args);
    }
}
