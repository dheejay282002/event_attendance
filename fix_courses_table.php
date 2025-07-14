<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🔧 Fixing Courses Table for Import Data</h2>";
echo "<p>Creating missing courses table and fixing import functionality...</p>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Create courses table
echo "<p><strong>Step 1: Creating courses table...</strong></p>";

$courses_table_sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $courses_table_sql)) {
    echo "<p style='color: green;'>✅ Created courses table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to create courses table: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Step 2: Create sections table
echo "<p><strong>Step 2: Creating sections table...</strong></p>";

$sections_table_sql = "CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_code VARCHAR(50) NOT NULL UNIQUE,
    section_name VARCHAR(100) NOT NULL,
    course_id INT,
    year_level INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sections_table_sql)) {
    echo "<p style='color: green;'>✅ Created sections table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to create sections table: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Step 3: Insert default courses
echo "<p><strong>Step 3: Adding default courses...</strong></p>";

$default_courses = [
    ['BSIT', 'Bachelor of Science in Information Technology', 'Information Technology program'],
    ['BSCS', 'Bachelor of Science in Computer Science', 'Computer Science program'],
    ['BSIS', 'Bachelor of Science in Information Systems', 'Information Systems program'],
    ['BSBA', 'Bachelor of Science in Business Administration', 'Business Administration program'],
    ['BSED', 'Bachelor of Science in Education', 'Education program'],
    ['BSEE', 'Bachelor of Science in Electrical Engineering', 'Electrical Engineering program'],
    ['BSME', 'Bachelor of Science in Mechanical Engineering', 'Mechanical Engineering program'],
    ['BSCE', 'Bachelor of Science in Civil Engineering', 'Civil Engineering program'],
    ['MAGICAL', 'Magical Studies', 'Hogwarts magical education program'],
    ['GEN', 'General Studies', 'General education program']
];

$course_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO courses (course_code, course_name, description) VALUES (?, ?, ?)");

foreach ($default_courses as $course) {
    mysqli_stmt_bind_param($course_stmt, "sss", $course[0], $course[1], $course[2]);
    
    if (mysqli_stmt_execute($course_stmt)) {
        if (mysqli_stmt_affected_rows($course_stmt) > 0) {
            echo "<p style='color: green;'>✅ Added course: {$course[1]} ({$course[0]})</p>";
            $success_count++;
        } else {
            echo "<p style='color: blue;'>ℹ️ Course already exists: {$course[1]} ({$course[0]})</p>";
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add course {$course[1]}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

// Step 4: Add default sections
echo "<p><strong>Step 4: Adding default sections...</strong></p>";

$default_sections = [
    // BSIT sections
    ['BSIT-1A', 'BSIT 1A', 'BSIT', 1],
    ['BSIT-1B', 'BSIT 1B', 'BSIT', 1],
    ['BSIT-2A', 'BSIT 2A', 'BSIT', 2],
    ['BSIT-2B', 'BSIT 2B', 'BSIT', 2],
    ['BSIT-3A', 'BSIT 3A', 'BSIT', 3],
    ['BSIT-3B', 'BSIT 3B', 'BSIT', 3],
    ['BSIT-4A', 'BSIT 4A', 'BSIT', 4],
    ['BSIT-4B', 'BSIT 4B', 'BSIT', 4],
    
    // Magical Studies sections (Harry Potter themed)
    ['Gryffindor-1', 'Gryffindor 1st Year', 'MAGICAL', 1],
    ['Gryffindor-2', 'Gryffindor 2nd Year', 'MAGICAL', 2],
    ['Gryffindor-3', 'Gryffindor 3rd Year', 'MAGICAL', 3],
    ['Gryffindor-4', 'Gryffindor 4th Year', 'MAGICAL', 4],
    ['Gryffindor-5', 'Gryffindor 5th Year', 'MAGICAL', 5],
    ['Gryffindor-6', 'Gryffindor 6th Year', 'MAGICAL', 6],
    ['Gryffindor-7', 'Gryffindor 7th Year', 'MAGICAL', 7],
    
    ['Hufflepuff-1', 'Hufflepuff 1st Year', 'MAGICAL', 1],
    ['Hufflepuff-2', 'Hufflepuff 2nd Year', 'MAGICAL', 2],
    ['Hufflepuff-3', 'Hufflepuff 3rd Year', 'MAGICAL', 3],
    ['Hufflepuff-4', 'Hufflepuff 4th Year', 'MAGICAL', 4],
    ['Hufflepuff-5', 'Hufflepuff 5th Year', 'MAGICAL', 5],
    ['Hufflepuff-6', 'Hufflepuff 6th Year', 'MAGICAL', 6],
    ['Hufflepuff-7', 'Hufflepuff 7th Year', 'MAGICAL', 7],
    
    ['Ravenclaw-1', 'Ravenclaw 1st Year', 'MAGICAL', 1],
    ['Ravenclaw-2', 'Ravenclaw 2nd Year', 'MAGICAL', 2],
    ['Ravenclaw-3', 'Ravenclaw 3rd Year', 'MAGICAL', 3],
    ['Ravenclaw-4', 'Ravenclaw 4th Year', 'MAGICAL', 4],
    ['Ravenclaw-5', 'Ravenclaw 5th Year', 'MAGICAL', 5],
    ['Ravenclaw-6', 'Ravenclaw 6th Year', 'MAGICAL', 6],
    ['Ravenclaw-7', 'Ravenclaw 7th Year', 'MAGICAL', 7],
    
    ['Slytherin-1', 'Slytherin 1st Year', 'MAGICAL', 1],
    ['Slytherin-2', 'Slytherin 2nd Year', 'MAGICAL', 2],
    ['Slytherin-3', 'Slytherin 3rd Year', 'MAGICAL', 3],
    ['Slytherin-4', 'Slytherin 4th Year', 'MAGICAL', 4],
    ['Slytherin-5', 'Slytherin 5th Year', 'MAGICAL', 5],
    ['Slytherin-6', 'Slytherin 6th Year', 'MAGICAL', 6],
    ['Slytherin-7', 'Slytherin 7th Year', 'MAGICAL', 7]
];

// Get course IDs for foreign key references
$course_ids = [];
$course_result = mysqli_query($conn, "SELECT id, course_code FROM courses");
while ($row = mysqli_fetch_assoc($course_result)) {
    $course_ids[$row['course_code']] = $row['id'];
}

$section_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO sections (section_code, section_name, course_id, year_level) VALUES (?, ?, ?, ?)");

foreach ($default_sections as $section) {
    $course_id = $course_ids[$section[2]] ?? null;
    if ($course_id) {
        mysqli_stmt_bind_param($section_stmt, "ssii", $section[0], $section[1], $course_id, $section[3]);
        
        if (mysqli_stmt_execute($section_stmt)) {
            if (mysqli_stmt_affected_rows($section_stmt) > 0) {
                echo "<p style='color: green;'>✅ Added section: {$section[1]} ({$section[0]})</p>";
                $success_count++;
            } else {
                echo "<p style='color: blue;'>ℹ️ Section already exists: {$section[1]} ({$section[0]})</p>";
            }
        } else {
            $error = mysqli_error($conn);
            echo "<p style='color: red;'>❌ Failed to add section {$section[1]}: " . htmlspecialchars($error) . "</p>";
            $errors[] = $error;
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Course not found for section: {$section[1]} (Course: {$section[2]})</p>";
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Fix Summary</h3>";
echo "<p><strong>Successful operations:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Step 5: Verify the fix
echo "<h3>Verification</h3>";
echo "<p><strong>Current database tables:</strong></p>";

$tables_to_check = ['courses', 'sections', 'official_students', 'students', 'events'];

foreach ($tables_to_check as $table) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        echo "<p><strong>$table:</strong> " . $count_row['count'] . " records</p>";
    } else {
        echo "<p><strong>$table:</strong> Table does not exist</p>";
    }
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Import Data System Fixed!</h3>";
    echo "<p>The courses and sections tables have been created and populated.</p>";
    echo "<p><strong>Both Admin and SBO can now:</strong></p>";
    echo "<ul>";
    echo "<li>Import student data via CSV files</li>";
    echo "<li>Automatically create courses and sections from imported data</li>";
    echo "<li>Manage academic structure through the interface</li>";
    echo "<li>Import Harry Potter themed data without errors</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test CSV import in Admin panel</li>";
    echo "<li>Test CSV import in SBO panel</li>";
    echo "<li>Import your Harry Potter student data</li>";
    echo "</ul>";
    echo "</div>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
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
</style>
