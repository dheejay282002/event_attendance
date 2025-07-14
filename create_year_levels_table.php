<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Creating Student Year Levels Table</h2>";

// Create student_year_levels table
$year_levels_table_sql = "CREATE TABLE IF NOT EXISTS student_year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    year_level INT NOT NULL DEFAULT 1,
    course VARCHAR(100) NOT NULL DEFAULT '',
    section VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student (student_id),
    INDEX idx_year_level (year_level),
    INDEX idx_course (course),
    INDEX idx_section (section)
)";

echo "<p><strong>Creating table:</strong> student_year_levels</p>";

if (mysqli_query($conn, $year_levels_table_sql)) {
    echo "<p style='color: green;'>✅ Success: student_year_levels table created</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Create student_sync_log table
$sync_log_table_sql = "CREATE TABLE IF NOT EXISTS student_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    operations TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp)
)";

echo "<p><strong>Creating table:</strong> student_sync_log</p>";

if (mysqli_query($conn, $sync_log_table_sql)) {
    echo "<p style='color: green;'>✅ Success: student_sync_log table created</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Create qr_generation_log table
$qr_log_table_sql = "CREATE TABLE IF NOT EXISTS qr_generation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_timestamp (timestamp)
)";

echo "<p><strong>Creating table:</strong> qr_generation_log</p>";

if (mysqli_query($conn, $qr_log_table_sql)) {
    echo "<p style='color: green;'>✅ Success: qr_generation_log table created</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Populate year levels from existing students
echo "<hr>";
echo "<p><strong>Populating year levels from existing students...</strong></p>";

// Check if we have students to populate
$student_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM official_students");
$student_count = mysqli_fetch_assoc($student_count_result)['count'];

if ($student_count > 0) {
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
    } else {
        echo "<p style='color: red;'>❌ Error populating year levels: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No students found to populate year levels</p>";
}

// Test the setup by checking if table exists and has data
echo "<hr>";
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
        echo "<table style='margin-left: 20px; border-collapse: collapse; border: 1px solid #ddd;'>";
        echo "<tr style='background: #f5f5f5;'><th style='border: 1px solid #ddd; padding: 8px;'>Student ID</th><th style='border: 1px solid #ddd; padding: 8px;'>Year Level</th><th style='border: 1px solid #ddd; padding: 8px;'>Course</th><th style='border: 1px solid #ddd; padding: 8px;'>Section</th></tr>";
        while ($row = mysqli_fetch_assoc($sample_result)) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['student_id']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;'>" . $row['year_level'] . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['course']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['section']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Table 'student_year_levels' not found</p>";
}

echo "<hr>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p>The student year levels system is now ready. You can now:</p>";
echo "<ul>";
echo "<li>View students with section-based year levels in Admin and SBO manage students pages</li>";
echo "<li>Import students with automatic year level extraction</li>";
echo "<li>Add/edit students with synchronized year level updates</li>";
echo "<li>Filter students by year level based on their section names</li>";
echo "</ul>";
echo "<p><strong>Year Level Examples:</strong></p>";
echo "<ul>";
echo "<li>NS-4B → Year 4 (finds '4' in section name)</li>";
echo "<li>IT-3A → Year 3 (finds '3' in section name)</li>";
echo "<li>A2 → Year 2 (finds '2' in section name)</li>";
echo "<li>BSIT-1C → Year 1 (finds '1' in section name)</li>";
echo "</ul>";
echo "</div>";

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

h2, h3 {
    color: #2c3e50;
}

hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 20px 0;
}

table {
    font-size: 0.9rem;
}
</style>
