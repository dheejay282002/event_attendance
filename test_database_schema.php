<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>Database Schema Test</h2>";
echo "<p>Testing database queries used by the application...</p>";

$tests_passed = 0;
$tests_failed = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Test 1: Check if all tables exist
echo "<h3>Test 1: Table Existence</h3>";
$tables = ['official_students', 'students', 'events', 'attendance'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        $tests_passed++;
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        $tests_failed++;
        $errors[] = "Table $table is missing";
    }
}

// Test 2: Test student dashboard query
echo "<h3>Test 2: Student Dashboard Query</h3>";
$section = 'IT-3A';
$now = date('Y-m-d H:i:s');
$events_query = mysqli_prepare($conn, "
    SELECT * FROM events 
    WHERE (assigned_sections LIKE ? OR assigned_sections LIKE ? OR assigned_sections LIKE ? OR assigned_sections = ?) 
    AND start_datetime >= ?
    ORDER BY start_datetime ASC
");

if ($events_query) {
    $section_start = $section . ',%';
    $section_middle = '%,' . $section . ',%';
    $section_end = '%,' . $section;
    $section_only = $section;
    
    mysqli_stmt_bind_param($events_query, "sssss", $section_start, $section_middle, $section_end, $section_only, $now);
    
    if (mysqli_stmt_execute($events_query)) {
        $result = mysqli_stmt_get_result($events_query);
        $count = mysqli_num_rows($result);
        echo "<p style='color: green;'>✅ Student dashboard query works (found $count events for $section)</p>";
        $tests_passed++;
    } else {
        echo "<p style='color: red;'>❌ Student dashboard query failed: " . mysqli_error($conn) . "</p>";
        $tests_failed++;
        $errors[] = "Student dashboard query failed";
    }
    mysqli_stmt_close($events_query);
} else {
    echo "<p style='color: red;'>❌ Failed to prepare student dashboard query: " . mysqli_error($conn) . "</p>";
    $tests_failed++;
    $errors[] = "Failed to prepare student dashboard query";
}

// Test 3: Test scan_qr query
echo "<h3>Test 3: Scan QR Query</h3>";
$scan_query = mysqli_query($conn, "SELECT * FROM events ORDER BY start_datetime DESC");
if ($scan_query) {
    $count = mysqli_num_rows($scan_query);
    echo "<p style='color: green;'>✅ Scan QR query works (found $count events)</p>";
    $tests_passed++;
} else {
    echo "<p style='color: red;'>❌ Scan QR query failed: " . mysqli_error($conn) . "</p>";
    $tests_failed++;
    $errors[] = "Scan QR query failed";
}

// Test 4: Test attendance insertion
echo "<h3>Test 4: Attendance Operations</h3>";
// First check if we have sample data
$student_check = mysqli_query($conn, "SELECT student_id FROM students LIMIT 1");
$event_check = mysqli_query($conn, "SELECT id FROM events LIMIT 1");

if (mysqli_num_rows($student_check) > 0 && mysqli_num_rows($event_check) > 0) {
    $student_row = mysqli_fetch_assoc($student_check);
    $event_row = mysqli_fetch_assoc($event_check);
    
    // Test attendance check query
    $check_query = mysqli_prepare($conn, "SELECT * FROM attendance WHERE student_id = ? AND event_id = ?");
    if ($check_query) {
        mysqli_stmt_bind_param($check_query, "si", $student_row['student_id'], $event_row['id']);
        if (mysqli_stmt_execute($check_query)) {
            echo "<p style='color: green;'>✅ Attendance check query works</p>";
            $tests_passed++;
        } else {
            echo "<p style='color: red;'>❌ Attendance check query failed: " . mysqli_error($conn) . "</p>";
            $tests_failed++;
            $errors[] = "Attendance check query failed";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to prepare attendance check query: " . mysqli_error($conn) . "</p>";
        $tests_failed++;
        $errors[] = "Failed to prepare attendance check query";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No sample data available for attendance test</p>";
}

// Test 5: Test column existence
echo "<h3>Test 5: Required Columns</h3>";
$column_tests = [
    'events' => ['id', 'title', 'description', 'start_datetime', 'end_datetime', 'assigned_sections'],
    'students' => ['id', 'student_id', 'full_name', 'course', 'section', 'password'],
    'attendance' => ['id', 'student_id', 'event_id', 'time_in', 'time_out'],
    'official_students' => ['id', 'student_id', 'full_name', 'course', 'section']
];

foreach ($column_tests as $table => $columns) {
    $desc_result = mysqli_query($conn, "DESCRIBE $table");
    if ($desc_result) {
        $existing_columns = [];
        while ($row = mysqli_fetch_assoc($desc_result)) {
            $existing_columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($columns, $existing_columns);
        if (empty($missing_columns)) {
            echo "<p style='color: green;'>✅ All required columns exist in '$table'</p>";
            $tests_passed++;
        } else {
            echo "<p style='color: red;'>❌ Missing columns in '$table': " . implode(', ', $missing_columns) . "</p>";
            $tests_failed++;
            $errors[] = "Missing columns in $table: " . implode(', ', $missing_columns);
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to describe table '$table': " . mysqli_error($conn) . "</p>";
        $tests_failed++;
        $errors[] = "Failed to describe table $table";
    }
}

echo "</div>";

// Summary
echo "<h3>Test Summary</h3>";
echo "<p><strong>Tests Passed:</strong> $tests_passed</p>";
echo "<p><strong>Tests Failed:</strong> $tests_failed</p>";

if ($tests_failed > 0) {
    echo "<h4>Errors:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

if ($tests_failed == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 All Tests Passed!</h3>";
    echo "<p>Your database schema is correctly set up and compatible with the ADLOR application.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Some Tests Failed</h3>";
    echo "<p>Please review the errors above and fix the database schema issues.</p>";
    echo "</div>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3, h4 {
    color: #2c3e50;
}
</style>
