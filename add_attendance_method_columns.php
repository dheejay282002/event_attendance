<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>🔧 Adding Attendance Method Columns to Events Table</h2>";
echo "<p>Adding columns to control QR scanner and manual student ID entry settings...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check current table structure
echo "<p><strong>Step 1: Checking current events table structure...</strong></p>";

$describe_result = mysqli_query($conn, "DESCRIBE events");
$existing_columns = [];
while ($row = mysqli_fetch_assoc($describe_result)) {
    $existing_columns[] = $row['Field'];
    echo "<p style='color: blue;'>ℹ️ Found column: {$row['Field']} ({$row['Type']})</p>";
}

// Step 2: Add new columns for attendance method control
echo "<p><strong>Step 2: Adding attendance method control columns...</strong></p>";

$columns_to_add = [
    'allow_qr_scanner' => "ADD COLUMN allow_qr_scanner BOOLEAN DEFAULT TRUE COMMENT 'Allow QR scanner attendance'",
    'allow_manual_entry' => "ADD COLUMN allow_manual_entry BOOLEAN DEFAULT TRUE COMMENT 'Allow manual student ID entry'",
    'attendance_method_note' => "ADD COLUMN attendance_method_note TEXT DEFAULT NULL COMMENT 'Optional note about attendance method restrictions'"
];

foreach ($columns_to_add as $column => $sql) {
    if (!in_array($column, $existing_columns)) {
        $alter_sql = "ALTER TABLE events $sql";
        if (mysqli_query($conn, $alter_sql)) {
            echo "<p style='color: green;'>✅ Added column: $column</p>";
            $success_count++;
        } else {
            $error = mysqli_error($conn);
            echo "<p style='color: red;'>❌ Failed to add column $column: " . htmlspecialchars($error) . "</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Column already exists: $column</p>";
    }
}

// Step 3: Update existing events with default values
echo "<p><strong>Step 3: Setting default values for existing events...</strong></p>";

$update_sql = "UPDATE events SET 
                allow_qr_scanner = TRUE, 
                allow_manual_entry = TRUE 
                WHERE allow_qr_scanner IS NULL OR allow_manual_entry IS NULL";

if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✅ Updated $affected existing events with default attendance method settings</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to update existing events: " . htmlspecialchars($error) . "</p>";
    $error_count++;
}

echo "</div>";

echo "<h3>📊 Summary</h3>";
echo "<p><strong>✅ Successful operations:</strong> $success_count</p>";
echo "<p><strong>❌ Failed operations:</strong> $error_count</p>";

// Step 4: Verify the changes
echo "<h3>🔍 Verification - Updated Events Table Structure</h3>";

$verify_result = mysqli_query($conn, "DESCRIBE events");
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Column</th>";
echo "<th style='padding: 8px;'>Type</th>";
echo "<th style='padding: 8px;'>Null</th>";
echo "<th style='padding: 8px;'>Default</th>";
echo "<th style='padding: 8px;'>Comment</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($verify_result)) {
    $highlight = in_array($row['Field'], ['allow_qr_scanner', 'allow_manual_entry', 'attendance_method_note']) ? "style='background-color: #d4edda;'" : "";
    echo "<tr $highlight>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Comment'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 5: Show sample data
echo "<h3>📋 Sample Events Data</h3>";
$sample_events = mysqli_query($conn, "SELECT id, title, allow_qr_scanner, allow_manual_entry, attendance_method_note FROM events LIMIT 5");

if (mysqli_num_rows($sample_events) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Event ID</th>";
    echo "<th style='padding: 8px;'>Title</th>";
    echo "<th style='padding: 8px;'>QR Scanner</th>";
    echo "<th style='padding: 8px;'>Manual Entry</th>";
    echo "<th style='padding: 8px;'>Note</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($sample_events)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding: 8px;'>" . ($row['allow_qr_scanner'] ? '✅ Enabled' : '❌ Disabled') . "</td>";
        echo "<td style='padding: 8px;'>" . ($row['allow_manual_entry'] ? '✅ Enabled' : '❌ Disabled') . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['attendance_method_note'] ?? 'None') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No events found in the database.</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Attendance Method Control Added Successfully!</h3>";
    echo "<p>The events table now supports attendance method restrictions.</p>";
    echo "<p><strong>New Features Available:</strong></p>";
    echo "<ul>";
    echo "<li>✅ <strong>QR Scanner Control:</strong> Enable/disable QR scanner for each event</li>";
    echo "<li>✅ <strong>Manual Entry Control:</strong> Enable/disable manual student ID entry for each event</li>";
    echo "<li>✅ <strong>Flexible Options:</strong> Allow both, one, or neither method per event</li>";
    echo "<li>✅ <strong>Optional Notes:</strong> Add explanatory notes about attendance restrictions</li>";
    echo "<li>✅ <strong>Backward Compatibility:</strong> Existing events default to allowing both methods</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Update create_event.php to include attendance method toggles</li>";
    echo "<li>Update edit_event.php to include attendance method toggles</li>";
    echo "<li>Update QR scanner to check event settings</li>";
    echo "<li>Update manual entry system to check event settings</li>";
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

td, th {
    border: 1px solid #dee2e6;
}
</style>
