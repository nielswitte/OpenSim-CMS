<?php
defined('EXEC') or die('Config not loaded');

/**
 * This is the slightly modified RPC-class of the BSD-licensed WiXTD webportal
 * Retrieved from http://opensimulator.org/wiki/RemoteAdmin:RemoteAdmin_Examples
 * Extended and customized for this project
 *
 * @author OpenSim (original)
 * @author Niels Witte (modified by)
 * @version 0.2
 * @date May 7, 2014
 * @since February 13, 2014
 */
class OpenSimRPC {
    private $serverUri;
    private $serverPort;
    private $password;
    private $timeout;

    /**
     * Creates a new OpenSim Remote PC instance
     * Default values are from config.php
     *
     * @param string $uri - http://opensim.server.address
     * @param integer $port - Remote Admin port
     * @param string $password - Remote Admin password
     * @param integer $timeout - [Optional] Time time in seconds before the request times out
     */
    public function __construct($uri, $port, $password, $timeout = 5) {
        $this->serverUri    = $uri;
        $this->serverPort   = $port;
        $this->password     = $password;
        $this->timeout      = $timeout;
    }

    /**
     * Makes remote call with given command and paramters
     *
     * @param string $command - The name of the function to execute
     * @param array $parameters - Array with parameters for the function
     * @return XML or boolean FALSE when failed to connect
     */
    public function call($command, $parameters) {
        $parameters['password'] = $this->password;
        $request                = xmlrpc_encode_request($command, $parameters);
        $ch                     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUri);
        curl_setopt($ch, CURLOPT_PORT, $this->serverPort);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);
        return $error == '' ? xmlrpc_decode($result) : FALSE;
    }

}