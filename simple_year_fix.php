<?php
/**
 * Simple Year Level Fix - Direct SQL Update
 */

require_once 'db_connect.php';

echo "<h2>Simple Year Level Fix</h2>";
echo "<hr>";

// Step 1: Ensure year_level column exists
echo "<h3>Step 1: Adding year_level column if needed</h3>";
$add_column_sql = "ALTER TABLE official_students ADD COLUMN year_level INT DEFAULT NULL";
$result = mysqli_query($conn, $add_column_sql);

if ($result) {
    echo "<p style='color: green;'>✅ year_level column added successfully</p>";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') !== false || strpos($error, 'duplicate column') !== false) {
        echo "<p style='color: blue;'>ℹ️ year_level column already exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: $error</p>";
    }
}

echo "<hr>";

// Step 2: Update year levels using direct SQL
echo "<h3>Step 2: Updating year levels with SQL</h3>";

// Update based on section patterns
$updates = [
    // For "Witch 4" pattern
    "UPDATE official_students SET year_level = 4 WHERE section LIKE '%4%' OR section LIKE '%Witch 4%'",
    // For "NS-3A" pattern  
    "UPDATE official_students SET year_level = 3 WHERE section LIKE '%3%' OR section LIKE '%NS-3A%'",
    // For other patterns
    "UPDATE official_students SET year_level = 2 WHERE section LIKE '%2%' AND year_level IS NULL",
    "UPDATE official_students SET year_level = 1 WHERE section LIKE '%1%' AND year_level IS NULL",
    // Default fallback
    "UPDATE official_students SET year_level = 1 WHERE year_level IS NULL"
];

foreach ($updates as $index => $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        echo "<p style='color: green;'>✅ Update " . ($index + 1) . ": {$affected} rows affected</p>";
        echo "<p style='color: gray; font-size: 12px;'>SQL: " . htmlspecialchars($sql) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Update " . ($index + 1) . " failed: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr>";

// Step 3: Show results
echo "<h3>Step 3: Verification</h3>";

$check_query = "SELECT student_id, full_name, section, course, year_level FROM official_students ORDER BY year_level DESC, section LIMIT 20";
$result = mysqli_query($conn, $check_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Student ID</th>";
echo "<th style='padding: 8px;'>Name</th>";
echo "<th style='padding: 8px;'>Section</th>";
echo "<th style='padding: 8px;'>Course</th>";
echo "<th style='padding: 8px; background-color: #d4edda;'>Year Level</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($result)) {
    $year_color = $row['year_level'] ? 'green' : 'red';
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['student_id']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['course']) . "</td>";
    echo "<td style='padding: 8px; color: {$year_color}; font-weight: bold;'>" . ($row['year_level'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 4: Summary
echo "<hr>";
echo "<h3>Step 4: Summary</h3>";

$summary_query = "SELECT year_level, COUNT(*) as count FROM official_students GROUP BY year_level ORDER BY year_level";
$summary_result = mysqli_query($conn, $summary_query);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Year Level</th>";
echo "<th style='padding: 8px;'>Student Count</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($summary_result)) {
    $year_display = $row['year_level'] ?: 'NULL';
    $color = $row['year_level'] ? 'black' : 'red';
    echo "<tr>";
    echo "<td style='padding: 8px; color: {$color};'>{$year_display}</td>";
    echo "<td style='padding: 8px;'>{$row['count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ Year level update completed!</p>";
echo "<p><a href='database_admin.php?table=official_students' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Check Database</a></p>";

mysqli_close($conn);
?>
