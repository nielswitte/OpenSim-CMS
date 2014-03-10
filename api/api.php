<?php
namespace API;

if(EXEC != 1) {
	die('Invalid request');
}

// Include all model classes
require_once dirname(__FILE__) .'/../models/avatar.php';
require_once dirname(__FILE__) .'/../controllers/avatarController.php';
require_once dirname(__FILE__) .'/../models/document.php';
require_once dirname(__FILE__) .'/../controllers/documentController.php';
require_once dirname(__FILE__) .'/../models/grid.php';
require_once dirname(__FILE__) .'/../models/meeting.php';
require_once dirname(__FILE__) .'/../models/meetingRoom.php';
require_once dirname(__FILE__) .'/../models/presentation.php';
require_once dirname(__FILE__) .'/../controllers/presentationController.php';
require_once dirname(__FILE__) .'/../models/region.php';
require_once dirname(__FILE__) .'/../controllers/regionController.php';
require_once dirname(__FILE__) .'/../models/slide.php';
require_once dirname(__FILE__) .'/../controllers/slideController.php';
require_once dirname(__FILE__) .'/../models/user.php';
require_once dirname(__FILE__) .'/../models/userLoggedIn.php';
require_once dirname(__FILE__) .'/../controllers/userController.php';

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
     * @param boolean $auth - [Optional] Is authorization required for this function? (Default: FALSE)
     */
    public function addRoute($regex, $function, $module, $method = 'GET', $auth = FALSE) {
        $this->routes[$regex][$method]['module']    = $module;
        $this->routes[$regex][$method]['auth']      = $auth;
        $this->routes[$regex][$method]['function']  = $function;
    }

   /**
     * Checks if the given url can be matched to a function
     *
     * @param string $url - URL to check
     * @param boolean $authorized - [Optional] Is the user authorized
     * @return mixed - The result of the function if a match is found, FALSE when no match found
     * @throws \Exception
     */
    public function getRoute($url, $authorized = FALSE) {
        $result = FALSE;
        // Search for match
        foreach ($this->routes as $regex => $funcs) {
            // Method found for this URL?
            if (preg_match($regex, $url, $args)) {
                $method = $_SERVER['REQUEST_METHOD'];
                // Has access to this method?
                if (isset($funcs[$method]) && ($authorized >= $funcs[$method]['auth'])) {
                    $result = $funcs[$method]['module']->$funcs[$method]['function']($args);
                // Method found but no access?
                } elseif (isset($funcs[$method]) && !($authorized >= $funcs[$method]['auth'])) {
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
