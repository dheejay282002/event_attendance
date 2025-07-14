<?php
session_start();
include '../db_connect.php';

date_default_timezone_set('Asia/Manila');

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['event_id'])) {
    header("Location: event_qr_codes.php");
    exit;
}

$event_id = $_GET['event_id'];

// Get event details
$event_query = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
mysqli_stmt_bind_param($event_query, "i", $event_id);
mysqli_stmt_execute($event_query);
$event_result = mysqli_stmt_get_result($event_query);
$event = mysqli_fetch_assoc($event_result);

if (!$event) {
    header("Location: event_qr_codes.php");
    exit;
}

// Regenerate Event QR Code
require_once '../simple_qr_generator.php';

// Create event QR code data
$event_qr_data = json_encode([
    'type' => 'event',
    'event_id' => $event['id'],
    'title' => $event['title'],
    'start_datetime' => $event['start_datetime'],
    'end_datetime' => $event['end_datetime'],
    'assigned_sections' => $event['assigned_sections'],
    'timestamp' => time(),
    'hash' => md5($event['id'] . $event['title'] . $event['start_datetime'] . time())
]);

// Create QR codes directory if it doesn't exist
$qr_dir = '../qr_codes/events';
if (!file_exists($qr_dir)) {
    mkdir($qr_dir, 0777, true);
}

// Generate event QR code
$event_qr_filename = $qr_dir . "/event_{$event['id']}.png";
SimpleQRGenerator::generateQRCode($event_qr_data, $event_qr_filename);

// Redirect back with success message
header("Location: event_qr_codes.php?regenerated=" . $event['id']);
exit;
?>
