<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Setting up Student Year Levels Table</h2>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Create student_year_levels table
$year_levels_table_sql = "CREATE TABLE IF NOT EXISTS student_year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    year_level INT NOT NULL DEFAULT 1,
    course VARCHAR(100) NOT NULL,
    section VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student (student_id),
    INDEX idx_year_level (year_level),
    INDEX idx_course (course),
    INDEX idx_section (section)
)";

// Create student_sync_log table
$sync_log_table_sql = "CREATE TABLE IF NOT EXISTS student_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    operations JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp)
)";

// Create qr_generation_log table
$qr_log_table_sql = "CREATE TABLE IF NOT EXISTS qr_generation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_timestamp (timestamp)
)";

echo "<p><strong>Creating table:</strong> student_year_levels</p>";

if (mysqli_query($conn, $year_levels_table_sql)) {
    echo "<p style='color: green;'>✅ Success: student_year_levels table created</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

echo "<p><strong>Creating table:</strong> student_sync_log</p>";

if (mysqli_query($conn, $sync_log_table_sql)) {
    echo "<p style='color: green;'>✅ Success: student_sync_log table created</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

echo "<p><strong>Creating table:</strong> qr_generation_log</p>";

if (mysqli_query($conn, $qr_log_table_sql)) {
    echo "<p style='color: green;'>✅ Success: qr_generation_log table created</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

echo "<hr>";

// Populate year levels from existing students
echo "<p><strong>Populating year levels from existing students...</strong></p>";

$populate_query = "
    INSERT IGNORE INTO student_year_levels (student_id, year_level, course, section)
    SELECT
        student_id,
        CASE
            WHEN section REGEXP '[0-9]+' THEN
                GREATEST(1, LEAST(4, CAST(REGEXP_SUBSTR(section, '[0-9]+') AS UNSIGNED)))
            WHEN course REGEXP '[0-9]+' THEN
                GREATEST(1, LEAST(4, CAST(REGEXP_SUBSTR(course, '[0-9]+') AS UNSIGNED)))
            ELSE 1
        END as year_level,
        course,
        section
    FROM official_students
";

if (mysqli_query($conn, $populate_query)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✅ Success: Populated {$affected_rows} student year levels</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error populating year levels: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

echo "<hr>";

// Test the setup by checking if table exists and has data
echo "<h3>Verifying Setup</h3>";

$result = mysqli_query($conn, "SHOW TABLES LIKE 'student_year_levels'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✅ Table 'student_year_levels' exists</p>";
    
    // Show row count
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM student_year_levels");
    $count_row = mysqli_fetch_assoc($count_result);
    echo "<p style='margin-left: 20px;'>Records: " . $count_row['count'] . "</p>";
    
    // Show sample data
    $sample_result = mysqli_query($conn, "SELECT * FROM student_year_levels LIMIT 5");
    if (mysqli_num_rows($sample_result) > 0) {
        echo "<p style='margin-left: 20px;'><strong>Sample data:</strong></p>";
        echo "<table style='margin-left: 20px; border-collapse: collapse;'>";
        echo "<tr style='background: #ddd;'><th style='border: 1px solid #999; padding: 5px;'>Student ID</th><th style='border: 1px solid #999; padding: 5px;'>Year Level</th><th style='border: 1px solid #999; padding: 5px;'>Course</th></tr>";
        while ($row = mysqli_fetch_assoc($sample_result)) {
            echo "<tr>";
            echo "<td style='border: 1px solid #999; padding: 5px;'>" . htmlspecialchars($row['student_id']) . "</td>";
            echo "<td style='border: 1px solid #999; padding: 5px;'>" . $row['year_level'] . "</td>";
            echo "<td style='border: 1px solid #999; padding: 5px;'>" . htmlspecialchars($row['course']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Table 'student_year_levels' not found</p>";
}

echo "</div>";

// Summary
echo "<h3>Setup Summary</h3>";
echo "<p><strong>Successful operations:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>Student year levels table has been successfully created and populated.</p>";
    echo "<p><strong>Features enabled:</strong></p>";
    echo "<ul>";
    echo "<li>Automatic year level tracking</li>";
    echo "<li>Course-based year level extraction</li>";
    echo "<li>System-wide student synchronization</li>";
    echo "<li>Database administration integration</li>";
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
