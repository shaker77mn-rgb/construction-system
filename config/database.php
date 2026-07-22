<?php

$host = "localhost";
$dbname = "construction_system";
$username = "root";
$password = "";

/* ============================
   Create Database Connection
============================ */

$conn = new mysqli($host, $username, $password, $dbname);

/* ============================
   Check Connection
============================ */

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

/* ============================
   Set Charset (Important for Arabic support)
============================ */
$conn->set_charset("utf8mb4");

?>
