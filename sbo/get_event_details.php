<?php
session_start();
include '../db_connect.php';

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID required']);
    exit;
}

$event_id = (int)$_GET['id'];

// Get event details
$stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

// Return event data as JSON
header('Content-Type: application/json');
echo json_encode($event);
?>
