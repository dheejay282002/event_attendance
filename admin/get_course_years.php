<?php
session_start();
require_once '../db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['event_id']) || !isset($_POST['course'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$event_id = $_POST['event_id'];
$course = $_POST['course'];

try {
    // Get event details to check assigned sections
    $event_query = mysqli_prepare($conn, "SELECT assigned_sections FROM events WHERE id = ?");
    mysqli_stmt_bind_param($event_query, "i", $event_id);
    mysqli_stmt_execute($event_query);
    $event_result = mysqli_stmt_get_result($event_query);
    $event = mysqli_fetch_assoc($event_result);

    if (!$event) {
        echo json_encode(['error' => 'Event not found']);
        exit();
    }

    // Get year levels for students in the specified course and assigned sections
    $assigned_sections = $event['assigned_sections'];
    
    $year_levels_query = "
        SELECT DISTINCT
            CASE
                -- Extract year level from section name - check for numbers 1-10
                WHEN section REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                WHEN section REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                WHEN section REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                WHEN section REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                WHEN section REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                WHEN section REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                WHEN section REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                WHEN section REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                WHEN section REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                WHEN section REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                -- Fallback to course name if section has no valid year numbers
                WHEN course REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                WHEN course REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                WHEN course REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                WHEN course REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                WHEN course REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                WHEN course REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                WHEN course REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                WHEN course REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                WHEN course REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                WHEN course REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                -- Default to year 1
                ELSE 1
            END as year_level
        FROM official_students 
        WHERE course = ? 
        AND FIND_IN_SET(section, ?)
        ORDER BY year_level ASC
    ";

    $stmt = mysqli_prepare($conn, $year_levels_query);
    mysqli_stmt_bind_param($stmt, "ss", $course, $assigned_sections);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $years = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $year_level = $row['year_level'];
        
        // Generate display format
        $year_suffix = '';
        switch($year_level) {
            case 1: $year_suffix = 'st'; break;
            case 2: $year_suffix = 'nd'; break;
            case 3: $year_suffix = 'rd'; break;
            default: $year_suffix = 'th'; break;
        }
        
        $years[] = [
            'level' => $year_level,
            'display' => $year_level . $year_suffix . ' Year'
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($years);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
