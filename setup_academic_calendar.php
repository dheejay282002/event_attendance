<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Setting up Academic Calendar System</h2>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Create academic_calendar table for actual academic years
$academic_calendar_sql = "CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL UNIQUE,
    year_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $academic_calendar_sql)) {
    echo "<p style='color: green;'>✅ Created academic_calendar table successfully</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'already exists') !== false) {
        echo "<p style='color: blue;'>ℹ️ academic_calendar table already exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating academic_calendar table: $error</p>";
        $errors[] = $error;
        $error_count++;
    }
}

// Create year_levels table if it doesn't exist
echo "<p><strong>Creating year_levels table...</strong></p>";
$year_levels_sql = "CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_code VARCHAR(10) NOT NULL UNIQUE,
    year_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $year_levels_sql)) {
    echo "<p style='color: green;'>✅ Created/verified year_levels table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error creating year_levels table: $error</p>";
    $errors[] = $error;
    $error_count++;
}

// Add current academic year
$current_year = date('Y');
$next_year = $current_year + 1;
$academic_year = "$current_year-$next_year";
$year_name = "Academic Year $academic_year";
$start_date = "$current_year-08-01"; // August 1st
$end_date = "$next_year-07-31";      // July 31st next year

echo "<p><strong>Adding current academic year...</strong></p>";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO academic_calendar (academic_year, year_name, start_date, end_date, is_current) VALUES (?, ?, ?, ?, TRUE)");
mysqli_stmt_bind_param($stmt, "ssss", $academic_year, $year_name, $start_date, $end_date);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<p style='color: green;'>✅ Added current academic year: $year_name</p>";
        $success_count++;
    } else {
        echo "<p style='color: blue;'>ℹ️ Current academic year already exists: $year_name</p>";
    }
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to add current academic year: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

mysqli_stmt_close($stmt);

// Add next academic year
$next_academic_year = "$next_year-" . ($next_year + 1);
$next_year_name = "Academic Year $next_academic_year";
$next_start_date = "$next_year-08-01";
$next_end_date = ($next_year + 1) . "-07-31";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO academic_calendar (academic_year, year_name, start_date, end_date, is_current) VALUES (?, ?, ?, ?, FALSE)");
mysqli_stmt_bind_param($stmt, "ssss", $next_academic_year, $next_year_name, $next_start_date, $next_end_date);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<p style='color: green;'>✅ Added next academic year: $next_year_name</p>";
        $success_count++;
    } else {
        echo "<p style='color: blue;'>ℹ️ Next academic year already exists: $next_year_name</p>";
    }
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to add next academic year: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

mysqli_stmt_close($stmt);

echo "</div>";

echo "<h3>Setup Summary</h3>";
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

// Show current academic calendar
echo "<h3>Current Academic Calendar</h3>";
$calendar_result = mysqli_query($conn, "SELECT academic_year, year_name, start_date, end_date, is_current, is_active FROM academic_calendar ORDER BY start_date DESC");

if (mysqli_num_rows($calendar_result) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Academic Year</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Year Name</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Duration</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center;'>Current</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($calendar_result)) {
        $current = $row['is_current'] ? '<span style="color: green; font-weight: bold;">🎯 CURRENT</span>' : '<span style="color: gray;">-</span>';
        $status = $row['is_active'] ? '<span style="color: green;">✅ Active</span>' : '<span style="color: orange;">⚠️ Inactive</span>';
        $duration = date('M j, Y', strtotime($row['start_date'])) . ' - ' . date('M j, Y', strtotime($row['end_date']));
        
        echo "<tr style='border-bottom: 1px solid #dee2e6;'>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6; font-weight: bold;'>" . htmlspecialchars($row['academic_year']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['year_name']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$duration</td>";
        echo "<td style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>$current</td>";
        echo "<td style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>$status</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>No academic years found.</p>";
}

// Show year levels
echo "<h3>Current Year Levels</h3>";
$year_levels_result = mysqli_query($conn, "SELECT year_code, year_name, is_active FROM year_levels ORDER BY year_code ASC");

if ($year_levels_result && mysqli_num_rows($year_levels_result) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center;'>Level</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Year Name</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($year_levels_result)) {
        $status = $row['is_active'] ? '<span style="color: green;">✅ Active</span>' : '<span style="color: orange;">⚠️ Inactive</span>';
        
        echo "<tr style='border-bottom: 1px solid #dee2e6;'>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . htmlspecialchars($row['year_code']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['year_name']) . "</td>";
        echo "<td style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>$status</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>No year levels found. Run <a href='setup_year_levels.php'>setup_year_levels.php</a> to create them.</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><strong>Academic Calendar:</strong> Represents school years (2024-2025, 2025-2026) that change annually</li>";
echo "<li><strong>Year Levels:</strong> Represents student progression (1st, 2nd, 3rd, 4th year) that remain constant</li>";
echo "<li>Update dashboards to show current academic year from academic_calendar table</li>";
echo "<li>Use year_levels for student classification and section organization</li>";
echo "</ul>";

mysqli_close($conn);
?>
