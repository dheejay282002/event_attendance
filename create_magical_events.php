<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🪄 Creating Magical Events for Hogwarts</h2>";
echo "<p>Adding magical events that would have been part of your 2 AM session...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Magical events data
$magical_events = [
    [
        'Sorting Ceremony', 
        'Annual sorting of new students into their respective houses', 
        '2025-09-01 19:00:00', 
        '2025-09-01 21:00:00', 
        'Gryffindor-1,Hufflepuff-1,Ravenclaw-1,Slytherin-1'
    ],
    [
        'Defense Against the Dark Arts Practical Exam', 
        'Practical examination for all 5th year students in DADA', 
        '2025-06-15 09:00:00', 
        '2025-06-15 12:00:00', 
        'Gryffindor-5,Hufflepuff-5,Ravenclaw-5,Slytherin-5'
    ],
    [
        'Quidditch World Cup Viewing', 
        'Watch the Quidditch World Cup finals in the Great Hall', 
        '2025-07-20 14:00:00', 
        '2025-07-20 18:00:00', 
        'Gryffindor-4,Gryffindor-5,Gryffindor-6,Gryffindor-7,Hufflepuff-4,Hufflepuff-5,Hufflepuff-6,Ravenclaw-4,Ravenclaw-5,Ravenclaw-6,Ravenclaw-7,Slytherin-4,Slytherin-5,Slytherin-6,Slytherin-7'
    ],
    [
        'Potions Master Class', 
        'Advanced potions brewing session with Professor Snape', 
        '2025-03-10 10:00:00', 
        '2025-03-10 13:00:00', 
        'Slytherin-6,Slytherin-7,Ravenclaw-6,Ravenclaw-7'
    ],
    [
        'Herbology Field Trip', 
        'Visit to the Forbidden Forest for rare plant collection', 
        '2025-04-22 08:00:00', 
        '2025-04-22 16:00:00', 
        'Hufflepuff-3,Hufflepuff-4,Hufflepuff-5,Gryffindor-3,Gryffindor-4'
    ],
    [
        'Transfiguration Tournament', 
        'Inter-house transfiguration competition', 
        '2025-05-05 13:00:00', 
        '2025-05-05 17:00:00', 
        'Gryffindor-5,Hufflepuff-5,Ravenclaw-5,Slytherin-5,Gryffindor-6,Hufflepuff-6,Ravenclaw-6,Slytherin-6'
    ],
    [
        'Care of Magical Creatures Demonstration', 
        'Hippogriff handling and care demonstration', 
        '2025-02-14 11:00:00', 
        '2025-02-14 15:00:00', 
        'Gryffindor-3,Slytherin-3,Hufflepuff-3,Ravenclaw-2'
    ],
    [
        'Astronomy Tower Observation Night', 
        'Planetary alignment observation and star charting', 
        '2025-01-30 20:00:00', 
        '2025-01-31 02:00:00', 
        'Ravenclaw-4,Ravenclaw-5,Ravenclaw-6,Ravenclaw-7'
    ],
    [
        'Dumbledore\'s Army Meeting', 
        'Secret defense training session', 
        '2025-12-15 19:30:00', 
        '2025-12-15 22:00:00', 
        'Gryffindor-4,Gryffindor-5,Hufflepuff-5,Ravenclaw-4,Ravenclaw-5'
    ],
    [
        'Yule Ball', 
        'Annual winter formal dance for 4th year and above', 
        '2025-12-25 19:00:00', 
        '2025-12-26 01:00:00', 
        'Gryffindor-4,Gryffindor-5,Gryffindor-6,Gryffindor-7,Hufflepuff-4,Hufflepuff-5,Hufflepuff-6,Ravenclaw-4,Ravenclaw-5,Ravenclaw-6,Ravenclaw-7,Slytherin-4,Slytherin-5,Slytherin-6,Slytherin-7'
    ]
];

echo "<p><strong>Creating magical events...</strong></p>";

$event_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO events (title, description, start_datetime, end_datetime, assigned_sections) VALUES (?, ?, ?, ?, ?)");

foreach ($magical_events as $event) {
    mysqli_stmt_bind_param($event_stmt, "sssss", $event[0], $event[1], $event[2], $event[3], $event[4]);
    
    if (mysqli_stmt_execute($event_stmt)) {
        if (mysqli_stmt_affected_rows($event_stmt) > 0) {
            echo "<p style='color: green;'>✅ Created event: {$event[0]}</p>";
            $success_count++;
        } else {
            echo "<p style='color: blue;'>ℹ️ Event already exists: {$event[0]}</p>";
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to create event {$event[0]}: " . htmlspecialchars($error) . "</p>";
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Magical Events Summary</h3>";
echo "<p><strong>Events created:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

// Show current events
echo "<h3>📅 Current Magical Events</h3>";
$events_result = mysqli_query($conn, "SELECT title, description, start_datetime, assigned_sections FROM events ORDER BY start_datetime");

if (mysqli_num_rows($events_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Event</th>";
    echo "<th style='padding: 8px;'>Description</th>";
    echo "<th style='padding: 8px;'>Date & Time</th>";
    echo "<th style='padding: 8px;'>Houses/Years</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($events_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td style='padding: 8px;'>" . date('M j, Y g:i A', strtotime($row['start_datetime'])) . "</td>";
        echo "<td style='padding: 8px; font-size: 0.9em;'>" . htmlspecialchars($row['assigned_sections']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No events found.</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Magical Events Created Successfully!</h3>";
    echo "<p>Your Hogwarts events have been added to the system.</p>";
    echo "<p><strong>Students can now:</strong></p>";
    echo "<ul>";
    echo "<li>Register for magical events using their HP Student IDs</li>";
    echo "<li>Generate QR codes for event attendance</li>";
    echo "<li>Participate in house-specific and inter-house events</li>";
    echo "<li>Track attendance for Quidditch matches, classes, and special ceremonies</li>";
    echo "</ul>";
    echo "</div>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
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
</style>
