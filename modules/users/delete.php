<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin']);

/* ============================
   Validate ID
============================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$user_id = intval($_GET['id']);

/* ============================
   Prevent Self Delete
============================ */
if ($user_id == $_SESSION['user_id']) {
    header("Location: list.php");
    exit();
}

/* ============================
   Check If User Exists
============================ */
$stmt = $conn->prepare("
    SELECT users.id, roles.role_name
    FROM users
    JOIN roles ON users.role_id = roles.id
    WHERE users.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: list.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

/* ============================
   Prevent Deleting Last Admin
============================ */
if ($user['role_name'] == 'Admin') {

    $admin_count = $conn->query("
        SELECT COUNT(*) AS total 
        FROM users
        JOIN roles ON users.role_id = roles.id
        WHERE roles.role_name = 'Admin'
    ")->fetch_assoc()['total'];

    if ($admin_count <= 1) {
        header("Location: list.php");
        exit();
    }
}

/* ============================
   Delete User
============================ */
$delete = $conn->prepare("DELETE FROM users WHERE id = ?");
$delete->bind_param("i", $user_id);
$delete->execute();
$delete->close();

header("Location: list.php");
exit();
?>
