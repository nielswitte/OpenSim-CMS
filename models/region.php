<?php
if(EXEC != 1) {
	die('Invalid request');
}
require_once dirname(__FILE__) .'/simpleModel.php';

/**
 * This class represents a region
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 17th, 2014
 */
class Region implements SimpleModel {
    private $name;
    private $uuid;
    private $online = FALSE;
    private $activeUsers = 0;
    private $totalUsers = 0;

    /**
     * Creates a new region with the given name and uuid
     *
     * @param string $uuid - Region UUID
     */
    public function __construct($uuid) {
        $this->uuid = $uuid;

        // @todo: implement a function to get the name when using grid mode
        $this->name = OS_DEFAULT_REGION;
    }

    /**
     * Returns the region name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the region UUID
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Gets information about the region
     */
    public function getInfoFromDatabase() {
        $raXML  = new OpenSimRPC();
        $result = $raXML->call('admin_region_query', array('region_id' => $this->getUuid()));
        $this->setOnlineStatus($result['success']);

        // Additional actions when MySQL database is accessable
        if(OS_DB_ENABLED && $this->getOnlineStatus()) {
            $osdb = Helper::getOSDB();
            // Get user's presentations
            $osdb->where("LastRegionID", $osdb->escape($this->getUuid()));
            $results = $osdb->get("GridUser");

            // Count active and total users
            foreach($results as $result) {
                if($result['Online'] == 'True') {
                    $this->activeUsers++;
                }
                $this->totalUsers++;
            }

        }
    }

    /**
     * Returns the server's online status
     *
     * @return boolean
     */
    public function getOnlineStatus() {
        return $this->online;
    }

    /**
     * Sets the server's status
     *
     * @param boolean $status
     */
    public function setOnlineStatus($status) {
        $this->online = $status;
    }

    /**
     * Returns the API url to this region
     *
     * @return string
     */
    public function getApiUrl() {
        return SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'.SERVER_PORT . SERVER_ROOT .'/api/region/'. $this->uuid .'/';
    }

    /**
     * Returns the number of currently active users
     *
     * @return integer
     */
    public function getActiveUsers() {
        return $this->activeUsers;
    }

    /**
     * Returns the total number of users
     *
     * @return integer
     */
    public function getTotalUsers() {
        return $this->totalUsers;
    }

    /**
     * Validates the parameters for a region request
     *
     * @param array $parameters
     * @return boolean
     * @throws Exception
     */
    public static function validateParameters($parameters) {
        $result = FALSE;
        if(!isset($parameters[1]) || !Helper::isValidUuid($parameters[1])) {
            throw new Exception('Expects parameter one to be UUID', 1);
        } elseif(isset($parameters[2]) && $parameters[2] != 'image') {
            throw new Exception('Expects parameter two to be image', 2);
        } else {
            $result = TRUE;
        }

        return $result;
    }

}

