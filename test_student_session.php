<?php
session_start();
include 'db_connect.php';

echo "<h2>🔍 Student Session Debug</h2>";

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    echo "<h3>Testing Query for Student ID: $student_id</h3>";
    
    // Test the exact query from student_qr_codes.php
    $student_query = mysqli_prepare($conn, "
        SELECT
            s.student_id,
            COALESCE(os.full_name, s.full_name) as full_name,
            COALESCE(os.course, s.course) as course,
            COALESCE(os.section, s.section) as section,
            s.profile_picture
        FROM students s
        LEFT JOIN official_students os ON s.student_id = os.student_id
        WHERE s.student_id = ?
    ");
    
    if ($student_query) {
        mysqli_stmt_bind_param($student_query, "s", $student_id);
        mysqli_stmt_execute($student_query);
        $student_result = mysqli_stmt_get_result($student_query);
        $student = mysqli_fetch_assoc($student_result);
        
        if ($student) {
            echo "<p style='color: green;'>✅ Student found!</p>";
            echo "<pre>";
            print_r($student);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ No student found with ID: $student_id</p>";
            
            // Check if student exists in students table
            $check_query = mysqli_query($conn, "SELECT student_id, full_name FROM students WHERE student_id = '$student_id'");
            if (mysqli_num_rows($check_query) > 0) {
                echo "<p>Student exists in students table</p>";
            } else {
                echo "<p style='color: orange;'>Student does not exist in students table</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Query preparation failed: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<h3>No student session found</h3>";
    echo "<p>You need to log in as a student first.</p>";
    echo "<p><a href='student_login.php'>Go to Student Login</a></p>";
    
    echo "<h3>Available Harry Potter Students:</h3>";
    $hp_students = mysqli_query($conn, "SELECT student_id, full_name FROM students WHERE student_id LIKE 'HP-%' ORDER BY student_id LIMIT 10");
    
    if (mysqli_num_rows($hp_students) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Student ID</th><th>Name</th><th>Login Available</th></tr>";
        while ($row = mysqli_fetch_assoc($hp_students)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td style='color: green;'>✅ Yes</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No Harry Potter students found with login accounts.</p>";
    }
}

echo "<h3>Actions:</h3>";
echo "<p><a href='student_login.php'>Student Login</a></p>";
echo "<p><a href='logout.php'>Clear Session</a></p>";

mysqli_close($conn);
?>
