<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Adding Sample Data to ADLOR Database</h2>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Sample official students data
$sample_students = [
    ['202300001', 'John Doe', 'BSIT', 'IT-3A'],
    ['202300002', 'Jane Smith', 'BSCS', 'CS-2B'],
    ['202300003', 'Mike Johnson', 'BSIT', 'IT-3A'],
    ['202300004', 'Sarah Wilson', 'BSCS', 'CS-2B'],
    ['202300005', 'Alex Brown', 'BSIT', 'IT-3B'],
    ['202300006', 'Emily Davis', 'BSCS', 'CS-3A'],
    ['202300007', 'Chris Lee', 'BSIT', 'IT-2A'],
    ['202300008', 'Maria Garcia', 'BSCS', 'CS-2A']
];

echo "<p><strong>Adding sample official students...</strong></p>";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO official_students (student_id, full_name, course, section) VALUES (?, ?, ?, ?)");

foreach ($sample_students as $student) {
    mysqli_stmt_bind_param($stmt, "ssss", $student[0], $student[1], $student[2], $student[3]);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✅ Added: {$student[1]} ({$student[0]})</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add {$student[1]}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

echo "<hr>";

// Sample events data
$sample_events = [
    [
        'Orientation Program', 
        'Welcome orientation for new students', 
        '2025-01-15 09:00:00', 
        '2025-01-15 12:00:00', 
        'IT-3A,CS-2B,IT-3B'
    ],
    [
        'Tech Seminar', 
        'Latest trends in technology and programming', 
        '2025-01-20 14:00:00', 
        '2025-01-20 17:00:00', 
        'IT-3A,CS-2B,CS-3A'
    ],
    [
        'Career Fair', 
        'Meet with potential employers and learn about career opportunities', 
        '2025-01-25 10:00:00', 
        '2025-01-25 16:00:00', 
        'IT-3A,CS-2B,IT-3B,CS-3A,IT-2A,CS-2A'
    ],
    [
        'Programming Contest', 
        'Annual programming competition for IT and CS students', 
        '2025-02-01 13:00:00', 
        '2025-02-01 18:00:00', 
        'IT-3A,CS-2B,CS-3A,IT-2A'
    ]
];

echo "<p><strong>Adding sample events...</strong></p>";

$event_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO events (title, description, start_datetime, end_datetime, assigned_sections) VALUES (?, ?, ?, ?, ?)");

foreach ($sample_events as $event) {
    mysqli_stmt_bind_param($event_stmt, "sssss", $event[0], $event[1], $event[2], $event[3], $event[4]);
    
    if (mysqli_stmt_execute($event_stmt)) {
        echo "<p style='color: green;'>✅ Added event: {$event[0]}</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add event {$event[0]}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Sample Data Summary</h3>";
echo "<p><strong>Successful insertions:</strong> $success_count</p>";
echo "<p><strong>Failed insertions:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Show current data counts
echo "<h3>Current Database Status</h3>";
$tables_to_check = ['official_students', 'students', 'events', 'attendance'];

foreach ($tables_to_check as $table) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    $count_row = mysqli_fetch_assoc($count_result);
    echo "<p><strong>$table:</strong> " . $count_row['count'] . " records</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Sample Data Added Successfully!</h3>";
    echo "<p>Your ADLOR system now has sample data to work with.</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Register students using their Student IDs (202300001 to 202300008)</li>";
    echo "<li>Create additional events through the SBO panel</li>";
    echo "<li>Test the QR code generation and scanning features</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Some Issues Occurred</h3>";
    echo "<p>Some sample data may not have been added. Please review the errors above.</p>";
    echo "</div>";
}

// Close connection
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

hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 10px 0;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
