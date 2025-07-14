<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🔐 Creating Sample Harry Potter Login Accounts</h2>";
echo "<p>Creating login accounts for key Harry Potter characters...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Key Harry Potter characters for login accounts
$hp_logins = [
    ['HP-0000039', 'Potter, Harry', 'Gryffindor-5', 'Magical Studies', 'gryffindor123'],
    ['HP-0000040', 'Granger, Hermione', 'Gryffindor-5', 'Magical Studies', 'books123'],
    ['HP-0000041', 'Weasley, Ron', 'Gryffindor-5', 'Magical Studies', 'chess123'],
    ['HP-0000066', 'Malfoy, Draco', 'Slytherin-5', 'Magical Studies', 'slytherin123'],
    ['HP-0000059', 'Lovegood, Luna', 'Ravenclaw-4', 'Magical Studies', 'nargles123'],
    ['HP-0000052', 'Diggory, Cedric', 'Hufflepuff-6', 'Magical Studies', 'hufflepuff123'],
    ['HP-0000047', 'Weasley, Ginny', 'Gryffindor-4', 'Magical Studies', 'quidditch123'],
    ['HP-0000042', 'Longbottom, Neville', 'Gryffindor-5', 'Magical Studies', 'herbology123'],
    ['HP-0000058', 'Chang, Cho', 'Ravenclaw-6', 'Magical Studies', 'ravenclaw123'],
    ['HP-0000030', 'Potter, Albus Severus', 'Slytherin-1', 'Magical Studies', 'nextgen123']
];

echo "<p><strong>Creating login accounts for key characters...</strong></p>";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO students (student_id, full_name, section, course, password) VALUES (?, ?, ?, ?, ?)");

foreach ($hp_logins as $login) {
    $hashed_password = password_hash($login[4], PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "sssss", $login[0], $login[1], $login[2], $login[3], $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "<p style='color: green;'>✅ Created login: {$login[1]} ({$login[0]}) - Password: {$login[4]}</p>";
            $success_count++;
        } else {
            echo "<p style='color: blue;'>ℹ️ Login already exists: {$login[1]} ({$login[0]})</p>";
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to create login for {$login[1]}: " . htmlspecialchars($error) . "</p>";
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Login Creation Summary</h3>";
echo "<p><strong>Logins created:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

// Show current registered students
echo "<h3>🎓 Registered Harry Potter Students</h3>";
$students_result = mysqli_query($conn, "SELECT student_id, full_name, section FROM students WHERE student_id LIKE 'HP-%' ORDER BY student_id");

if (mysqli_num_rows($students_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Student ID</th>";
    echo "<th style='padding: 8px;'>Name</th>";
    echo "<th style='padding: 8px;'>House & Year</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($students_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px; font-family: monospace;'>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section']) . "</td>";
        echo "<td style='padding: 8px; color: green;'>✅ Can Login</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No registered students found.</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Harry Potter Login Accounts Created!</h3>";
    echo "<p>Key characters now have login accounts and can access the system.</p>";
    echo "<p><strong>Test Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Harry Potter:</strong> HP-0000039 / gryffindor123</li>";
    echo "<li><strong>Hermione Granger:</strong> HP-0000040 / books123</li>";
    echo "<li><strong>Ron Weasley:</strong> HP-0000041 / chess123</li>";
    echo "<li><strong>Draco Malfoy:</strong> HP-0000066 / slytherin123</li>";
    echo "<li><strong>Luna Lovegood:</strong> HP-0000059 / nargles123</li>";
    echo "</ul>";
    echo "<p><strong>These students can now:</strong></p>";
    echo "<ul>";
    echo "<li>Log into the student portal</li>";
    echo "<li>Generate QR codes for magical events</li>";
    echo "<li>View their attendance records</li>";
    echo "<li>Update their profiles with magical photos</li>";
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

table {
    font-size: 0.9rem;
}

th {
    background-color: #6c757d !important;
    color: white;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
