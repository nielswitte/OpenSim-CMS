<?php
/**
 * Abstract library
 *
 * @author Niels
 */
interface SimpleLibrary {

    function getInfoFromDatabase();

    /**
	 * Function to validate parameters array
	 *
	 * @param Array $parameters
	 *
	 * @return Boolean true when all checks passed
	 */
    static function validateParameters($parameters);
}
