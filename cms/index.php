<?php
require dirname(__FILE__) .'/../config.php';

session_start();

if(isset($_SESSION['AccessTokenExpires'])) {
    if($_SESSION['AccessTokenExpires'] < date('Y-m-d H:i:s')) {
        unset($_SESSION);
        session_destroy();
        // @todo nice implementation
        echo 'session expired';
    } else {
        $_SESSION['AccessTokenExpires'] = date('Y-m-d H:i:s', strtotime('+'. SERVER_API_TOKEN_EXPIRES));
    }
}

require dirname(__FILE__) .'/templates/bootstrapped/index.php';

