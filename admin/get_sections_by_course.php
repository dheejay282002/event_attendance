<?php
session_start();
include '../db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get course parameter
$course = $_GET['course'] ?? '';

if (empty($course)) {
    echo json_encode([]);
    exit;
}

// Get sections that belong to the selected course
$query = "SELECT DISTINCT section 
          FROM official_students 
          WHERE course = ? 
          AND section IS NOT NULL 
          AND section != '' 
          ORDER BY section";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $course);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = ['section' => $row['section']];
}

header('Content-Type: application/json');
echo json_encode($sections);
?>
