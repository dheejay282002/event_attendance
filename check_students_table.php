<?php
include 'db_connect.php';

echo "<h2>Students Table Structure</h2>";

$result = mysqli_query($conn, 'DESCRIBE students');
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the exact query from student_qr_codes.php
echo "<h3>Testing Query</h3>";
$test_query = "
    SELECT
        s.student_id,
        COALESCE(os.full_name, s.full_name) as full_name,
        COALESCE(os.course, s.course) as course,
        COALESCE(os.section, s.section) as section,
        s.profile_picture
    FROM students s
    LEFT JOIN official_students os ON s.student_id = os.student_id
    LIMIT 1
";

$test_result = mysqli_query($conn, $test_query);
if ($test_result) {
    echo "<p style='color: green;'>✅ Query works!</p>";
    if ($row = mysqli_fetch_assoc($test_result)) {
        echo "<p>Sample result: " . print_r($row, true) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Query failed: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>
