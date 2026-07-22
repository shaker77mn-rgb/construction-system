<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Session Timeout */
$timeout_duration = 1800;

if (isset($_SESSION['LAST_ACTIVITY']) && 
    (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {

    session_unset();
    session_destroy();
}

$_SESSION['LAST_ACTIVITY'] = time();
