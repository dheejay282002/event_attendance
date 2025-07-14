<?php
include 'db_connect.php';

echo "<h2>Setting up Test Students</h2>";

// Create students table if it doesn't exist
$create_students_table = "
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    course VARCHAR(50) NOT NULL,
    section VARCHAR(20) NOT NULL,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_students_table)) {
    echo "<p>✅ Students table created/verified</p>";
} else {
    echo "<p>❌ Error creating students table: " . mysqli_error($conn) . "</p>";
}

// Create official_students table if it doesn't exist
$create_official_students_table = "
CREATE TABLE IF NOT EXISTS official_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    course VARCHAR(50) NOT NULL,
    section VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_official_students_table)) {
    echo "<p>✅ Official students table created/verified</p>";
} else {
    echo "<p>❌ Error creating official_students table: " . mysqli_error($conn) . "</p>";
}

// Sample test students
$test_students = [
    ['23-11797', 'John Doe', 'BSIT', 'IT-3A'],
    ['23-11798', 'Jane Smith', 'BSCS', 'CS-2B'],
    ['23-11799', 'Mike Johnson', 'BSIT', 'IT-3A'],
    ['23-11800', 'Sarah Wilson', 'BSCS', 'CS-2B'],
    ['23-11801', 'David Brown', 'BSIT', 'IT-3B'],
    ['24-12345', 'Emma Davis', 'BSCS', 'CS-1A'],
    ['24-12346', 'Alex Garcia', 'BSIT', 'IT-2A'],
    ['24-12347', 'Lisa Martinez', 'BSCS', 'CS-1B']
];

echo "<h3>Adding Test Students</h3>";

foreach ($test_students as $student) {
    $student_id = $student[0];
    $full_name = $student[1];
    $course = $student[2];
    $section = $student[3];
    $password = password_hash('student123', PASSWORD_DEFAULT); // Default password
    
    // Insert into students table
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO students (student_id, full_name, course, section, password) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $student_id, $full_name, $course, $section, $password);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "<p>✅ Added to students: {$student_id} - {$full_name}</p>";
        } else {
            echo "<p>ℹ️ Already exists in students: {$student_id} - {$full_name}</p>";
        }
    } else {
        echo "<p>❌ Error adding to students: {$student_id} - " . mysqli_error($conn) . "</p>";
    }
    
    // Insert into official_students table
    $stmt2 = mysqli_prepare($conn, "INSERT IGNORE INTO official_students (student_id, full_name, course, section) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt2, "ssss", $student_id, $full_name, $course, $section);
    
    if (mysqli_stmt_execute($stmt2)) {
        if (mysqli_stmt_affected_rows($stmt2) > 0) {
            echo "<p>✅ Added to official_students: {$student_id} - {$full_name}</p>";
        } else {
            echo "<p>ℹ️ Already exists in official_students: {$student_id} - {$full_name}</p>";
        }
    } else {
        echo "<p>❌ Error adding to official_students: {$student_id} - " . mysqli_error($conn) . "</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Test Student IDs you can use:</strong></p>";
echo "<ul>";
foreach ($test_students as $student) {
    echo "<li>{$student[0]} - {$student[1]} ({$student[2]}, {$student[3]})</li>";
}
echo "</ul>";

echo "<p><strong>Default password for all test students:</strong> student123</p>";

echo "<h3>Quick Links:</h3>";
echo "<p><a href='debug_students.php'>🔍 Debug Students Database</a></p>";
echo "<p><a href='scan_qr.php'>📱 Test QR Scanner</a></p>";
echo "<p><a href='student_login.php'>👨‍🎓 Student Login</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
p { margin: 5px 0; }
hr { margin: 10px 0; }
ul { margin: 10px 0; }
</style>
