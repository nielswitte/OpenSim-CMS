<?php
require dirname(__FILE__) .'/../config.php';

session_start();
$sessionExpired = FALSE;

if(isset($_SESSION['AccessTokenExpires'])) {
    if($_SESSION['AccessTokenExpires'] < date('Y-m-d H:i:s')) {
        unset($_SESSION);
        session_destroy();
        // @todo nice implementation
        $sessionExpired = TRUE;
    } else {
        $_SESSION['AccessTokenExpires'] = date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES));
    }
}

require dirname(__FILE__) .'/templates/bootstrapped/index.php';

