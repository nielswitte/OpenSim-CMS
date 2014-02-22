<?php
if (EXEC != 1) {
    die('Invalid request');
}
unset($_SESSION);
session_destroy();

