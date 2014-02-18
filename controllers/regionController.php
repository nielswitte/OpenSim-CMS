<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the region controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 17th, 2014
 */
class regionController {
    private $region;

    /**
     * Creates a new controller for the given region
     *
     * @param Region $region
     */
    public function __construct(Region $region) {
        $this->region = $region;
    }
}
