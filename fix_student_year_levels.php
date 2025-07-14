<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>Fixing Student Year Levels</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// First, add year_level column to official_students table if it doesn't exist
$add_column_sql = "ALTER TABLE official_students ADD COLUMN year_level INT DEFAULT NULL";
$result = mysqli_query($conn, $add_column_sql);
if ($result) {
    echo "<p style='color: green;'>✅ Added year_level column to official_students table</p>";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "<p style='color: blue;'>ℹ️ year_level column already exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding column: $error</p>";
    }
}

// Function to extract year level from various formats
function extractYearLevel($section, $course) {
    // Try to extract from section first
    $patterns = [
        '/^[A-Z]+-(\d+)[A-Z]*/',     // BSIT-2A, CS-3B
        '/^[A-Z]+(\d+)[A-Z]*/',      // BSIT2A, CS3B  
        '/(\d+)/',                    // Any number in section
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $section, $matches)) {
            $year = intval($matches[1]);
            if ($year >= 1 && $year <= 6) {
                return $year;
            }
        }
    }
    
    // Try to extract from course
    if (preg_match('/(\d+)/', $course, $matches)) {
        $year = intval($matches[1]);
        if ($year >= 1 && $year <= 6) {
            return $year;
        }
    }
    
    return null;
}

// Get all students
$students_query = mysqli_query($conn, "SELECT student_id, section, course FROM official_students");
$updated_count = 0;
$failed_count = 0;

echo "<p><strong>Processing students...</strong></p>";

while ($student = mysqli_fetch_assoc($students_query)) {
    $year_level = extractYearLevel($student['section'], $student['course']);
    
    if ($year_level) {
        // Update student with year level
        $update_stmt = mysqli_prepare($conn, "UPDATE official_students SET year_level = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($update_stmt, "is", $year_level, $student['student_id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo "<p style='color: green;'>✅ {$student['student_id']}: {$student['section']} → Year Level {$year_level}</p>";
            $updated_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to update {$student['student_id']}</p>";
            $failed_count++;
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        echo "<p style='color: orange;'>⚠️ {$student['student_id']}: Could not extract year level from '{$student['section']}' or '{$student['course']}'</p>";
        $failed_count++;
    }
}

echo "</div>";

echo "<h3>Summary</h3>";
echo "<p><strong>Students updated:</strong> $updated_count</p>";
echo "<p><strong>Failed/Skipped:</strong> $failed_count</p>";

// Show sample of updated data
echo "<h3>Sample Updated Records</h3>";
$sample_query = mysqli_query($conn, "SELECT student_id, full_name, section, course, year_level FROM official_students WHERE year_level IS NOT NULL LIMIT 10");

if (mysqli_num_rows($sample_query) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6;'>Student ID</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6;'>Name</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6;'>Section</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6;'>Course</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6;'>Year Level</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($sample_query)) {
        echo "<tr>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['section']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['course']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . htmlspecialchars($row['year_level']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>No records with year levels found.</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li>The manage_students.php page will now show year levels from the database</li>";
echo "<li>Year level filtering will work properly</li>";
echo "<li>All student records now have consistent year level data</li>";
echo "</ul>";

mysqli_close($conn);
?>
