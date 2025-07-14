<?php
include 'db_connect.php';

// Set all years to not current
mysqli_query($conn, "UPDATE academic_calendar SET is_current = FALSE");

// Set 2024-2025 as current, or create it if it doesn't exist
$result = mysqli_query($conn, "UPDATE academic_calendar SET is_current = TRUE WHERE academic_year = '2024-2025'");

if (mysqli_affected_rows($conn) == 0) {
    // 2024-2025 doesn't exist, create it
    mysqli_query($conn, "INSERT INTO academic_calendar (academic_year, year_name, start_date, end_date, is_current) VALUES ('2024-2025', 'Academic Year 2024-2025', '2024-08-01', '2025-07-31', TRUE)");
    echo "Created and set 2024-2025 as current academic year\n";
} else {
    echo "Set 2024-2025 as current academic year\n";
}

// Show current academic year
$result = mysqli_query($conn, "SELECT academic_year FROM academic_calendar WHERE is_current = TRUE");
if ($row = mysqli_fetch_assoc($result)) {
    echo "Current academic year: " . $row['academic_year'] . "\n";
}

mysqli_close($conn);
?>
