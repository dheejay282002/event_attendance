<?php
/**
 * Direct Year Level Fix Script
 * This script will add the year_level column and populate it correctly
 */

require_once 'db_connect.php';

echo "<h2>Direct Year Level Fix</h2>";
echo "<hr>";

// Step 1: Check if year_level column exists
echo "<h3>Step 1: Checking year_level column</h3>";
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM official_students LIKE 'year_level'");
$column_exists = mysqli_num_rows($check_column) > 0;

if ($column_exists) {
    echo "<p style='color: blue;'>✓ year_level column already exists</p>";
} else {
    echo "<p style='color: orange;'>⚠ year_level column does not exist, adding it...</p>";
    $add_column = mysqli_query($conn, "ALTER TABLE official_students ADD COLUMN year_level INT DEFAULT NULL");
    if ($add_column) {
        echo "<p style='color: green;'>✅ Successfully added year_level column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding column: " . mysqli_error($conn) . "</p>";
        exit;
    }
}

echo "<hr>";

// Step 2: Show current data sample
echo "<h3>Step 2: Current Data Sample</h3>";
$sample_query = mysqli_query($conn, "SELECT student_id, full_name, section, course, year_level FROM official_students LIMIT 10");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Student ID</th>";
echo "<th style='padding: 8px;'>Name</th>";
echo "<th style='padding: 8px;'>Section</th>";
echo "<th style='padding: 8px;'>Course</th>";
echo "<th style='padding: 8px;'>Current Year Level</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($sample_query)) {
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['student_id']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['course']) . "</td>";
    echo "<td style='padding: 8px;'>" . ($row['year_level'] ? $row['year_level'] : 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// Step 3: Extract year levels using PHP logic
echo "<h3>Step 3: Extracting and Updating Year Levels</h3>";

function extractYearFromSection($section) {
    // Remove spaces and convert to uppercase for consistent processing
    $section = strtoupper(trim($section));
    
    // Pattern 1: Look for numbers at the end (like "WITCH 4", "NS-3A")
    if (preg_match('/(\d+)[A-Z]*$/', $section, $matches)) {
        $year = intval($matches[1]);
        if ($year >= 1 && $year <= 10) {
            return $year;
        }
    }
    
    // Pattern 2: Look for numbers after dash (like "NS-3A", "IT-2B")
    if (preg_match('/-(\d+)/', $section, $matches)) {
        $year = intval($matches[1]);
        if ($year >= 1 && $year <= 10) {
            return $year;
        }
    }
    
    // Pattern 3: Look for any number in the section
    if (preg_match('/(\d+)/', $section, $matches)) {
        $year = intval($matches[1]);
        if ($year >= 1 && $year <= 10) {
            return $year;
        }
    }
    
    return 1; // Default to year 1
}

// Get all students and update their year levels
$students_query = mysqli_query($conn, "SELECT student_id, section, course FROM official_students");
$updated_count = 0;
$total_count = 0;

echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";

while ($student = mysqli_fetch_assoc($students_query)) {
    $total_count++;
    $year_level = extractYearFromSection($student['section']);
    
    // Update the student's year level
    $update_stmt = mysqli_prepare($conn, "UPDATE official_students SET year_level = ? WHERE student_id = ?");
    mysqli_stmt_bind_param($update_stmt, "is", $year_level, $student['student_id']);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo "<p style='color: green; margin: 2px 0;'>✅ {$student['student_id']}: '{$student['section']}' → Year {$year_level}</p>";
        $updated_count++;
    } else {
        echo "<p style='color: red; margin: 2px 0;'>❌ Failed to update {$student['student_id']}</p>";
    }
    
    mysqli_stmt_close($update_stmt);
}

echo "</div>";

echo "<hr>";

// Step 4: Show results
echo "<h3>Step 4: Update Results</h3>";
echo "<p><strong>Total students processed:</strong> {$total_count}</p>";
echo "<p><strong>Successfully updated:</strong> {$updated_count}</p>";

// Step 5: Show updated data sample
echo "<h3>Step 5: Updated Data Sample</h3>";
$updated_sample = mysqli_query($conn, "SELECT student_id, full_name, section, course, year_level FROM official_students LIMIT 10");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Student ID</th>";
echo "<th style='padding: 8px;'>Name</th>";
echo "<th style='padding: 8px;'>Section</th>";
echo "<th style='padding: 8px;'>Course</th>";
echo "<th style='padding: 8px;'>Updated Year Level</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($updated_sample)) {
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['student_id']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['course']) . "</td>";
    echo "<td style='padding: 8px; font-weight: bold; color: green;'>" . $row['year_level'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p style='color: green; font-weight: bold;'>✅ Year level fix completed!</p>";
echo "<p><a href='database_admin.php?table=official_students' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Updated Database</a></p>";

mysqli_close($conn);
?>
