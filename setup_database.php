<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>ADLOR Database Setup</h2>";
echo "<p>Setting up database tables...</p>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Define SQL statements directly in PHP for better control
$sql_statements = [
    // Table 1: official_students
    "CREATE TABLE IF NOT EXISTS official_students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(255) NOT NULL,
        course VARCHAR(100) NOT NULL,
        section VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Table 2: students
    "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(255) NOT NULL,
        course VARCHAR(100) NOT NULL,
        section VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        photo VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Table 3: events
    "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        assigned_sections TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Table 4: attendance
    "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        event_id INT NOT NULL,
        time_in TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        time_out TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_event (student_id, event_id)
    )"
];

foreach ($sql_statements as $index => $statement) {
    $table_name = '';
    if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $statement, $matches)) {
        $table_name = $matches[1];
    }

    echo "<p><strong>Creating table:</strong> " . ($table_name ?: "Table " . ($index + 1)) . "</p>";

    if (mysqli_query($conn, $statement)) {
        echo "<p style='color: green;'>✅ Success</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }

    echo "<hr>";
}

echo "</div>";

echo "<h3>Setup Summary</h3>";
echo "<p><strong>Successful statements:</strong> $success_count</p>";
echo "<p><strong>Failed statements:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Test the setup by checking if tables exist
echo "<h3>Verifying Tables</h3>";
$tables_to_check = ['official_students', 'students', 'events', 'attendance'];

foreach ($tables_to_check as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        
        // Show row count
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
        $count_row = mysqli_fetch_assoc($count_result);
        echo "<p style='margin-left: 20px;'>Rows: " . $count_row['count'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' not found</p>";
    }
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Database Setup Complete!</h3>";
    echo "<p>All tables have been created successfully. Your ADLOR system is ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Upload student data using <a href='admin/upload_students.php'>admin/upload_students.php</a></li>";
    echo "<li>Students can register at <a href='student_register.php'>student_register.php</a></li>";
    echo "<li>Create events using <a href='sbo/create_event.php'>sbo/create_event.php</a></li>";
    echo "<li>Scan QR codes at <a href='scan_qr.php'>scan_qr.php</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Setup Incomplete</h3>";
    echo "<p>Some errors occurred during setup. Please review the errors above and fix them manually.</p>";
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
