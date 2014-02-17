<?php
if(EXEC != 1) {
	die('Invalid request');
}

/**
 * Interface for most Models
 * Includes some basic functions that need to be implemented
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 11th, 2014
 */
interface SimpleModel {

    function getInfoFromDatabase();

    /**
	 * Function to validate parameters array
	 *
	 * @param array $parameters
	 *
	 * @return boolean true when all checks passed
	 */
    static function validateParameters($parameters);
}
