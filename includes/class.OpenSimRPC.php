<?php

/**
 * This is the slightly modified RPC-class of the BSD-licensed WiXTD webportal
 * Retrieved from http://opensimulator.org/wiki/RemoteAdmin:RemoteAdmin_Examples
 * Extended and customized for this project
 *
 * @author OpenSim (original)
 * @author Niels Witte (modified by)
 * @version 0.1
 * @date February 13th, 2014
 */
class OpenSimRPC {
    private $serverUri;
    private $serverPort;
    private $password;

    /**
     * Creates a new OpenSim Remote PC instance
     * Default values are from config.php
     *
     * @param String $uri - http://opensim.server.address [default: OS_REMOTE_ADMIN_URI]
     * @param type $port - Remote Admin port [default: OS_REMOTE_ADMIN_PORT]
     * @param type $password - Remote Admin password [default: OS_REMOTE_ADMIN_PASSWORD]
     */
    public function __construct($uri = OS_REMOTE_ADMIN_URI, $port = OS_REMOTE_ADMIN_PORT, $password = OS_REMOTE_ADMIN_PASSWORD) {
        $this->serverUri = $uri;
        $this->serverPort = $port;
        $this->password = $password;
    }

    /**
     * Makes remote call with given command and paramters
     *
     * @param String $command - The name of the function to execute
     * @param Array $parameters - Array with parameters for the function
     * @return XML
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);

        return xmlrpc_decode($result);
    }

}