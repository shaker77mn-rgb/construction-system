<?php
require_once __DIR__ . '/../config/session.php';

/* ============================
   Check Login
============================ */
if (!isset($_SESSION['user_id'])) {
    header("Location: /construction_system/login.php");
    exit();
}

/* ============================
   Role-Based Access Control
============================ */
function checkRole($allowed_roles = []) {

    if (!isset($_SESSION['role'])) {
        header("Location: /construction_system/login.php");
        exit();
    }

    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: /construction_system/dashboard.php");
        exit();
    }
}
?>
