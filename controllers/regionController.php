<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

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
     * @param \Models\Region $region
     */
    public function __construct(\Models\Region $region) {
        $this->region = $region;
    }
}
