<?php
if(EXEC != 1) {
	die('Invalid request');
}

$postData = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW);

// No access Token present, but post information available?
if(!isset($_SESSION['AccessToken']) && isset($postData['username']) && isset($postData['password'])) {
    $url = SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'. SERVER_PORT . SERVER_ROOT .'/api/auth/username/';
    $data = array(
        'username' => $postData['username'],
        'password' => $postData['password'],
        'ip'       => $_SERVER['REMOTE_ADDR']
    );

    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'                => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'                => 'POST',
            'content'               => http_build_query($data),
        ),
    );
    $context    = stream_context_create($options);
    $result     = json_decode(file_get_contents($url, false, $context));

    // Success?
    if(isset($result->token)) {
        $isAuthorized                       = TRUE;
        $_SESSION['AccessToken']            = $result->token;
        $_SESSION['AccessTokenExpires']     = $result->expires;
        $_SESSION['UserId']                 = $result->userId;
    }
}
