<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/* ============================
   Validate ID
============================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$client_id = intval($_GET['id']);

/* ============================
   Check If Client Exists
============================ */
$stmt = $conn->prepare("SELECT id FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: list.php");
    exit();
}
$stmt->close();

/* ============================
   Check If Client Has Projects
============================ */
$check = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM projects 
    WHERE client_id = ?
");
$check->bind_param("i", $client_id);
$check->execute();
$count_result = $check->get_result();
$project_count = $count_result->fetch_assoc()['total'];
$check->close();

if ($project_count > 0) {
    header("Location: list.php?error=has_projects");
    exit();
}

/* ============================
   Delete Client
============================ */
$delete = $conn->prepare("DELETE FROM clients WHERE id = ?");
$delete->bind_param("i", $client_id);
$delete->execute();
$delete->close();

header("Location: list.php?success=deleted");
exit();
?>
