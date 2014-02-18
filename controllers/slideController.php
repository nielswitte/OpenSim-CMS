<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the slide controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 12th, 2014
 */
class SlideController {
    private $slide;

    /**
     * Constructs a new controller for the given slide
     *
     * @param Slide $slide
     */
    public function __construct(Slide $slide) {
        $this->slide = $slide;
    }

    /**
     * Updates the UUID of the slide to the given value
     *
     * @param string $uuid
     * @return boolean
     * @throws Exception
     */
    public function setUuid($uuid) {
        $results = FALSE;
        if(Helper::isValidUuid($uuid)) {
            $db = Helper::getDB();
            $updateData = array(
                'uuid'          => $db->escape($uuid),
                'uuidUpdated'   => date('Y-m-d H:i:s')
            );
            $db->where('number', $this->slide->getNumber());

            $results = $db->update('presentation_slides', $updateData);
        } else {
            throw new Exception("Invalid UUID provided", 2);
        }

        if(!$results) {
            throw new Exception("Updating UUID failed", 1);
        }
        return $results;
    }
}