<?php
include 'db_connect.php';

echo "<h2>Debug: Students in Database</h2>";

// Check both possible student tables
echo "<h3>Checking 'students' table:</h3>";
$query = "SELECT student_id, full_name, course, section FROM students ORDER BY student_id";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "<p style='color: red;'>Error querying 'students' table: " . mysqli_error($conn) . "</p>";

    // Try official_students table
    echo "<h3>Checking 'official_students' table:</h3>";
    $query = "SELECT student_id, full_name, course, section FROM official_students ORDER BY student_id";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "<p style='color: red;'>Error querying 'official_students' table: " . mysqli_error($conn) . "</p>";
        exit;
    }
}

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Student ID</th><th>Full Name</th><th>Course</th><th>Section</th></tr>";
    
    while ($student = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['course']) . "</td>";
        echo "<td>" . htmlspecialchars($student['section']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Total Students: " . mysqli_num_rows($result) . "</h3>";
} else {
    echo "<p style='color: red;'>No students found in database!</p>";
}

// Check table structure
echo "<h3>Students Table Structure:</h3>";
$structure_query = "DESCRIBE students";
$structure_result = mysqli_query($conn, $structure_query);

if ($structure_result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($field = mysqli_fetch_assoc($structure_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Test a specific student ID lookup
if (isset($_GET['test_id'])) {
    $test_id = $_GET['test_id'];
    echo "<h3>Testing Student ID: " . htmlspecialchars($test_id) . "</h3>";
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $test_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);
    
    if ($student) {
        echo "<p style='color: green;'>✅ Student found!</p>";
        echo "<pre>" . print_r($student, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Student not found!</p>";
    }
}

echo "<hr>";
echo "<form method='GET'>";
echo "<label>Test Student ID: <input type='text' name='test_id' placeholder='e.g., 23-11797'></label>";
echo "<button type='submit'>Test Lookup</button>";
echo "</form>";
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
