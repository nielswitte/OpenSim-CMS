<?php
namespace API;

if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is hosts all API calls and matches them to the corresponding model/controller functions
 *
 * @author Niels Witte
 * @version 0.5
 * @date February 18th, 2014
 */
class API {
    private $routes = array();
    private $modules = array();

    /**
     * Creates a new API with optional a list of routes
     *
     * @param array $routes
     */
    public function __construct($routes = array()) {
        $this->routes = $routes;
    }

    /**
     * Add the given module to the list of API modules
     *
     * @param String $name
     * @param \API\Modules\Module $module
     */
    public function addModule($name, \API\Modules\Module $module) {
        $this->modules[$name] = $module;
    }

    /**
     * Returns the module with the given name
     *
     * @param string $name
     * @return \API\Modules\Module
     */
    public function getModule($name) {
        return $this->modules[$name];
    }

    /**
     * Adds the given regex/function pair to the list of routes
     *
     * @param string $regex - Regular expression to match the route
     * @param string $function - Name of the function to execute
     * @param \API\Modules\Module $module - The instance of the module to use
     * @param string $method - [Optional] Define if the function is accessed by GET, POST, PUT or DELETE (Default: GET)
     * @param integer $auth - [Optional] Minimal user permissions required to access this function? (Default: \Auth::NONE)
     */
    public function addRoute($regex, $function, $module, $method = 'GET', $auth = \Auth::NONE) {
        $this->routes[$regex][$method]['module']    = $module;
        $this->routes[$regex][$method]['function']  = $function;
        $this->routes[$regex][$method]['auth']      = $auth;
    }

   /**
     * Checks if the given url can be matched to a function
     *
     * @param string $url - URL to check
     * @return mixed - The result of the function if a match is found, FALSE when no match found
     * @throws \Exception
     */
    public function getRoute($url) {
        $result = FALSE;
        // Search for match
        foreach ($this->routes as $regex => $funcs) {
            // Method found for this URL?
            if (preg_match($regex, $url, $args)) {
                $method = $_SERVER['REQUEST_METHOD'];
                $module = isset($funcs[$method]) ? $funcs[$method]['module']->getName() : '';
                // Has access to this method?
                if (isset($funcs[$method]) && \Auth::checkRights($module, $funcs[$method]['auth'])) {
                    $result = $funcs[$method]['module']->$funcs[$method]['function']($args);
                // Method found but no access?
                } elseif (isset($funcs[$method]) && !(\Auth::checkRights($module, $funcs[$method]['auth']))) {
                    $result = TRUE;
                    header("HTTP/1.1 401 Unauthorized");
                    throw new \Exception("Unauthorized to access this API URL");
                // Someting else gone wrong?
                } else {
                    // No match @todo find use case
                }
            }
        }
        return $result;
    }

    /**
     * Returns an array with all routes
     *
     * @return array
     */
    public function getRoutes() {
        return $this->routes;
    }
}
