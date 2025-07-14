<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🔄 Restoring Database to 2 AM State</h2>";
echo "<p>Restoring your Harry Potter themed student data from the 2 AM session...</p>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Clear existing sample data
echo "<p><strong>Step 1: Clearing existing sample data...</strong></p>";

$clear_queries = [
    "DELETE FROM attendance WHERE student_id LIKE '202300%'",
    "DELETE FROM students WHERE student_id LIKE '202300%'", 
    "DELETE FROM official_students WHERE student_id LIKE '202300%'",
    "DELETE FROM events WHERE title IN ('Orientation Program', 'Tech Seminar', 'Career Fair', 'Programming Contest')"
];

foreach ($clear_queries as $query) {
    if (mysqli_query($conn, $query)) {
        $affected = mysqli_affected_rows($conn);
        echo "<p style='color: orange;'>🗑️ Cleared $affected records</p>";
    }
}

// Step 2: Import Harry Potter student data
echo "<p><strong>Step 2: Importing Harry Potter student data...</strong></p>";

$hp_students = [
    ['HP-0000001', 'McLaggen, Cormac', 'Gryffindor-6', 'Magical Studies'],
    ['HP-0000002', 'Vane, Romilda', 'Gryffindor-4', 'Magical Studies'],
    ['HP-0000003', 'Midgen, Eloise', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000004', 'Bell, Katie', 'Gryffindor-7', 'Magical Studies'],
    ['HP-0000005', 'Robins, Demelza', 'Gryffindor-4', 'Magical Studies'],
    ['HP-0000006', 'Bobbin, Melinda', 'Hufflepuff-6', 'Magical Studies'],
    ['HP-0000007', 'Jones, Megan', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000008', 'Madley, Laura', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000009', 'Perks, Sally-Anne', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000010', 'Quirke, Orla', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000011', 'MacDougal, Morag', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000012', 'Entwhistle, Kevin', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000013', 'Li, Su', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000014', 'Cornfoot, Stephen', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000015', 'Smith, Sally', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000016', 'Carmichael, Eddie', 'Ravenclaw-7', 'Magical Studies'],
    ['HP-0000017', 'Pritchard, Graham', 'Slytherin-4', 'Magical Studies'],
    ['HP-0000018', 'Baddock, Malcolm', 'Slytherin-4', 'Magical Studies'],
    ['HP-0000019', 'Harper', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000020', 'Dingle, Harold', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000021', 'Montague', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000022', 'Warrington', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000023', 'Warrington, Cassius', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000024', 'Bletchley, Miles', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000025', 'Urquhart', 'Slytherin-6', 'Magical Studies'],
    ['HP-0000026', 'Vaisey', 'Slytherin-6', 'Magical Studies'],
    ['HP-0000027', 'Derek', 'Unknown-4', 'Magical Studies'],
    ['HP-0000028', 'Creevey, Dennis', 'Gryffindor-3', 'Magical Studies'],
    ['HP-0000029', 'Creevey, Colin', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000030', 'Potter, Albus Severus', 'Slytherin-1', 'Magical Studies'],
    ['HP-0000031', 'Malfoy, Scorpius', 'Slytherin-1', 'Magical Studies'],
    ['HP-0000032', 'Granger-Weasley, Rose', 'Gryffindor-1', 'Magical Studies'],
    ['HP-0000033', 'Fredericks, Yann', 'Hufflepuff-1', 'Magical Studies'],
    ['HP-0000034', 'Chapman, Polly', 'Slytherin-2', 'Magical Studies'],
    ['HP-0000035', 'Bowker Jr., Craig', 'Hufflepuff-3', 'Magical Studies'],
    ['HP-0000036', 'Jenkins, Karl', 'Gryffindor-2', 'Magical Studies'],
    ['HP-0000037', 'Lexie', 'Ravenclaw-2', 'Magical Studies'],
    ['HP-0000038', 'Balthazar', 'Slytherin-3', 'Magical Studies'],
    ['HP-0000039', 'Potter, Harry', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000040', 'Granger, Hermione', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000041', 'Weasley, Ron', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000042', 'Longbottom, Neville', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000043', 'Thomas, Dean', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000044', 'Finnigan, Seamus', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000045', 'Brown, Lavender', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000046', 'Patil, Parvati', 'Gryffindor-5', 'Magical Studies'],
    ['HP-0000047', 'Weasley, Ginny', 'Gryffindor-4', 'Magical Studies'],
    ['HP-0000048', 'Johnson, Angelina', 'Gryffindor-7', 'Magical Studies'],
    ['HP-0000049', 'Spinnet, Alicia', 'Gryffindor-7', 'Magical Studies'],
    ['HP-0000050', 'Jordan, Lee', 'Gryffindor-7', 'Magical Studies'],
    ['HP-0000051', 'Wood, Oliver', 'Gryffindor-7', 'Magical Studies'],
    ['HP-0000052', 'Diggory, Cedric', 'Hufflepuff-6', 'Magical Studies'],
    ['HP-0000053', 'Abbott, Hannah', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000054', 'Macmillan, Ernie', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000055', 'Bones, Susan', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000056', 'Finch-Fletchley, Justin', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000057', 'Smith, Zacharias', 'Hufflepuff-5', 'Magical Studies'],
    ['HP-0000058', 'Chang, Cho', 'Ravenclaw-6', 'Magical Studies'],
    ['HP-0000059', 'Lovegood, Luna', 'Ravenclaw-4', 'Magical Studies'],
    ['HP-0000060', 'Patil, Padma', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000061', 'Corner, Michael', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000062', 'Boot, Terry', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000063', 'Goldstein, Anthony', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000064', 'Turpin, Lisa', 'Ravenclaw-5', 'Magical Studies'],
    ['HP-0000065', 'Edgecombe, Marietta', 'Ravenclaw-6', 'Magical Studies'],
    ['HP-0000066', 'Malfoy, Draco', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000067', 'Parkinson, Pansy', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000068', 'Crabbe, Vincent', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000069', 'Goyle, Gregory', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000070', 'Nott, Theodore', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000071', 'Zabini, Blaise', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000072', 'Bulstrode, Millicent', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000073', 'Greengrass, Daphne', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000074', 'Davis, Tracey', 'Slytherin-5', 'Magical Studies'],
    ['HP-0000075', 'Greengrass, Astoria', 'Slytherin-6', 'Magical Studies'],
    ['HP-0000076', 'Flint, Marcus', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000077', 'Pucey, Adrian', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000078', 'Higgs, Terence', 'Slytherin-7', 'Magical Studies'],
    ['HP-0000079', 'Davies, Roger', 'Ravenclaw-7', 'Magical Studies']
];

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO official_students (student_id, full_name, section, course) VALUES (?, ?, ?, ?)");

foreach ($hp_students as $student) {
    mysqli_stmt_bind_param($stmt, "ssss", $student[0], $student[1], $student[2], $student[3]);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "<p style='color: green;'>✅ Added: {$student[1]} ({$student[0]})</p>";
            $success_count++;
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add {$student[1]}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Restoration Summary</h3>";
echo "<p><strong>Students restored:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

// Show current data counts
echo "<h3>Current Database Status</h3>";
$tables_to_check = ['official_students', 'students', 'events', 'attendance'];

foreach ($tables_to_check as $table) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    $count_row = mysqli_fetch_assoc($count_result);
    echo "<p><strong>$table:</strong> " . $count_row['count'] . " records</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Database Restored to 2 AM State!</h3>";
    echo "<p>Your Harry Potter themed student data has been successfully restored.</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Register students using their HP Student IDs (HP-0000001 to HP-0000079)</li>";
    echo "<li>Students can use IDs like: HP-0000039 (Harry Potter), HP-0000040 (Hermione Granger), HP-0000066 (Draco Malfoy), etc.</li>";
    echo "<li>Create magical events through the SBO panel</li>";
    echo "<li>Test the QR code generation with Hogwarts students</li>";
    echo "</ul>";
    echo "</div>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
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
