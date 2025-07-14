<?php
session_start();
include 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    http_response_code(403);
    exit('Access denied');
}

// Get parameters
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if ($type === 'student' && $id === $_SESSION['student_id']) {
    // Student QR code download
    $qr_file = "qr_codes/student_{$id}.png";
    
    if (file_exists($qr_file)) {
        // Set headers for download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="ADLOR_Student_QR_' . $id . '_' . date('Y-m-d') . '.png"');
        header('Content-Length: ' . filesize($qr_file));
        header('Cache-Control: no-cache, must-revalidate');
        
        // Output the file
        readfile($qr_file);
        exit;
    } else {
        http_response_code(404);
        exit('QR code file not found');
    }
} else {
    http_response_code(400);
    exit('Invalid request');
}
?>
