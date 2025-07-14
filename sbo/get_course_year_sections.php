<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['event_id']) || !isset($_POST['course']) || !isset($_POST['year'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$event_id = $_POST['event_id'];
$course = $_POST['course'];
$year = $_POST['year'];

try {
    // Get the event and its assigned sections
    $event_query = mysqli_prepare($conn, "SELECT assigned_sections FROM events WHERE id = ?");
    mysqli_stmt_bind_param($event_query, "i", $event_id);
    mysqli_stmt_execute($event_query);
    $event_result = mysqli_stmt_get_result($event_query);
    
    if (mysqli_num_rows($event_result) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit();
    }
    
    $event = mysqli_fetch_assoc($event_result);
    $assigned_sections = array_map('trim', explode(',', $event['assigned_sections']));
    
    // Get sections for the specific course and year from students in assigned sections
    $sections_list = "'" . implode("','", array_map('mysqli_real_escape_string', array_fill(0, count($assigned_sections), $conn), $assigned_sections)) . "'";
    
    // Build the query to filter by course and year level
    $sections_query = mysqli_query($conn, "
        SELECT DISTINCT section 
        FROM official_students 
        WHERE course = '" . mysqli_real_escape_string($conn, $course) . "'
        AND section IN ($sections_list)
        AND section IS NOT NULL 
        AND section != ''
        AND (
            (course REGEXP '-[0-9]+' AND SUBSTRING_INDEX(SUBSTRING_INDEX(course, '-', -1), ' ', 1) = '" . mysqli_real_escape_string($conn, $year) . "')
            OR 
            (course REGEXP '[0-9]+' AND REGEXP_SUBSTR(course, '[0-9]+') = '" . mysqli_real_escape_string($conn, $year) . "')
        )
        ORDER BY section
    ");
    
    $sections = [];
    while ($row = mysqli_fetch_assoc($sections_query)) {
        $sections[] = $row['section'];
    }
    
    // If no sections found with year filtering, fall back to course-only filtering
    if (empty($sections)) {
        $sections_query = mysqli_query($conn, "
            SELECT DISTINCT section 
            FROM official_students 
            WHERE course = '" . mysqli_real_escape_string($conn, $course) . "'
            AND section IN ($sections_list)
            AND section IS NOT NULL 
            AND section != ''
            ORDER BY section
        ");
        
        while ($row = mysqli_fetch_assoc($sections_query)) {
            $sections[] = $row['section'];
        }
    }
    
    // Return sections as JSON
    header('Content-Type: application/json');
    echo json_encode($sections);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
