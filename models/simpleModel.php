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
    /**
     * Retrieves model information from the database
     * @throws Exception on failure
     */
    function getInfoFromDatabase();
}
