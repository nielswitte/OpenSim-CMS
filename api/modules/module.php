<?php
namespace API\Modules;

defined('EXEC') or die('Config not loaded');

/**
 * Basic module
 *
 * @author Niels Witte
 * @version 0.2
 * @since February 24th, 2014
 */
abstract class Module {
    private $name;

    /**
     * Returns the name of this module
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the name of this module
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    abstract function setRoutes();
}
