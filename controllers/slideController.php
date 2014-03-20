<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the slide controller
 *
 * @author Niels Witte
 * @version 0.1
 * @since February 12th, 2014
 */
class SlideController {
    private $slide;

    /**
     * Constructs a new controller for the given slide
     *
     * @param \Models\Slide $slide
     */
    public function __construct(\Models\Slide $slide) {
        $this->slide = $slide;
    }

    /**
     * Updates the UUID of the slide to the given value
     *
     * @param string $uuid - The UUID of the slide
     * @param \Models\Grid $grid - The grid the texture is used on
     * @return boolean
     * @throws \Exception
     */
    public function setUuid($uuid, \Models\Grid $grid) {
        $results = FALSE;
        if(\Helper::isValidUuid($uuid)) {
            $db = \Helper::getDB();
            $cacheData = array(
                'gridId'        => $db->escape($grid->getId()),
                'uuid'          => $db->escape($uuid),
                'uuidExpires'   => $db->escape(date('Y-m-d H:i:s', strtotime('+'. $grid->getCacheTime())))
            );

            $cacheId = $db->insert('cached_assets', $cacheData);
            $cacheSlideData = array(
                'slideId'       => $db->escape($this->slide->getId()),
                'cacheId'       => $db->escape($cacheId)
            );

            $results = $db->insert('document_slides_cache', $cacheSlideData);
        } else {
            throw new \Exception("Invalid UUID provided", 2);
        }

        if($results === FALSE) {
            throw new \Exception("Updating UUID failed", 1);
        }
        return $results !== FALSE;
    }
}
