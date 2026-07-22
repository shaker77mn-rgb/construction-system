<?php
require_once 'config/session.php';

/* ============================
   Unset All Session Variables
============================ */
$_SESSION = [];

/* ============================
   Destroy Session
============================ */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

/* ============================
   Redirect To Login
============================ */
header("Location: login.php");
exit();
?>
