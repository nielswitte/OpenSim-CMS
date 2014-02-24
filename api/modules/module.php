<?php
namespace API\Modules;

if(EXEC != 1) {
	die('Invalid request');
}

/**
 * Basic module
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 24th, 2014
 */
abstract class Module {
    abstract function setRoutes();
}
