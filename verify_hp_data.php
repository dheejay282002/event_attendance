<?php
include 'db_connect.php';

echo "<h2>🏰 Verifying Harry Potter Database Restoration</h2>";

// Check HP students
$hp_result = mysqli_query($conn, "SELECT student_id, full_name, section FROM official_students WHERE student_id LIKE 'HP-%' ORDER BY student_id LIMIT 20");

echo "<h3>📚 Harry Potter Students in Database:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Student ID</th><th>Name</th><th>House & Year</th></tr>";

while ($row = mysqli_fetch_assoc($hp_result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['section']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Count by house
$houses = ['Gryffindor', 'Hufflepuff', 'Ravenclaw', 'Slytherin'];
echo "<h3>🏠 Students by House:</h3>";
foreach ($houses as $house) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM official_students WHERE section LIKE '$house%'");
    $count = mysqli_fetch_assoc($count_result)['count'];
    echo "<p><strong>$house:</strong> $count students</p>";
}

// Total HP students
$total_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM official_students WHERE student_id LIKE 'HP-%'");
$total = mysqli_fetch_assoc($total_result)['count'];
echo "<h3>📊 Total Harry Potter Students: $total</h3>";

mysqli_close($conn);
?>
