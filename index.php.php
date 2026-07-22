<?php
require_once 'config/session.php';

/* ============================
   Check Login Status
============================ */

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
