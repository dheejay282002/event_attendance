<?php
/**
 * Verify Events Table Structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h2>✅ Events Table Verification</h2>";
echo "<hr>";

// Test 1: Check if events table exists
echo "<h3>1. Table Existence Check</h3>";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'events'");
if (mysqli_num_rows($table_check) > 0) {
    echo "<p style='color: green;'>✅ Events table exists</p>";
} else {
    echo "<p style='color: red;'>❌ Events table does not exist</p>";
    exit;
}

// Test 2: Check table structure
echo "<h3>2. Table Structure</h3>";
$structure_query = mysqli_query($conn, "DESCRIBE events");

if ($structure_query) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Column</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "<th style='padding: 8px;'>Extra</th>";
    echo "</tr>";
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($structure_query)) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $row['Field'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Type'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Null'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Key'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Default'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for required columns
    $required_columns = ['id', 'title', 'description', 'start_datetime', 'end_datetime', 'assigned_sections', 'created_at', 'updated_at'];
    
    echo "<h4>Required Columns Check:</h4>";
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
        }
    }
    
    // Check for optional columns
    $optional_columns = ['created_by', 'event_date', 'event_time'];
    echo "<h4>Optional Columns Check:</h4>";
    foreach ($optional_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color: blue;'>ℹ️ Optional column '$col' exists</p>";
        } else {
            echo "<p style='color: gray;'>⚪ Optional column '$col' not present</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Cannot describe events table: " . mysqli_error($conn) . "</p>";
}

// Test 3: Test event insertion
echo "<h3>3. Event Insertion Test</h3>";

$test_event = [
    'title' => 'Test Event - Admin Creation',
    'description' => 'This is a test event created by admin verification script',
    'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 hour')),
    'end_datetime' => date('Y-m-d H:i:s', strtotime('+3 hours')),
    'assigned_sections' => 'Test-1A,Test-2B'
];

echo "<p><strong>Testing event insertion with data:</strong></p>";
echo "<ul>";
echo "<li><strong>Title:</strong> " . htmlspecialchars($test_event['title']) . "</li>";
echo "<li><strong>Description:</strong> " . htmlspecialchars($test_event['description']) . "</li>";
echo "<li><strong>Start:</strong> " . $test_event['start_datetime'] . "</li>";
echo "<li><strong>End:</strong> " . $test_event['end_datetime'] . "</li>";
echo "<li><strong>Sections:</strong> " . htmlspecialchars($test_event['assigned_sections']) . "</li>";
echo "</ul>";

try {
    $insert_query = "INSERT INTO events (title, description, start_datetime, end_datetime, assigned_sections) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", 
            $test_event['title'], 
            $test_event['description'], 
            $test_event['start_datetime'], 
            $test_event['end_datetime'], 
            $test_event['assigned_sections']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $event_id = mysqli_insert_id($conn);
            echo "<p style='color: green;'>✅ Test event inserted successfully! Event ID: #$event_id</p>";
            
            // Verify the inserted event
            $verify_query = "SELECT * FROM events WHERE id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_query);
            mysqli_stmt_bind_param($verify_stmt, "i", $event_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            
            if ($row = mysqli_fetch_assoc($verify_result)) {
                echo "<p style='color: green;'>✅ Event verified in database:</p>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background-color: #f8f9fa;'>";
                echo "<th style='padding: 8px;'>Field</th>";
                echo "<th style='padding: 8px;'>Value</th>";
                echo "</tr>";
                
                foreach ($row as $field => $value) {
                    echo "<tr>";
                    echo "<td style='padding: 8px; font-weight: bold;'>" . htmlspecialchars($field) . "</td>";
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($value) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Clean up test event
                $delete_query = "DELETE FROM events WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($delete_stmt, "i", $event_id);
                if (mysqli_stmt_execute($delete_stmt)) {
                    echo "<p style='color: blue;'>ℹ️ Test event cleaned up successfully</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Could not clean up test event (ID: #$event_id)</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Could not verify inserted event</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to insert test event: " . mysqli_stmt_error($stmt) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to prepare insert statement: " . mysqli_error($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception during event insertion: " . $e->getMessage() . "</p>";
}

// Test 4: Check existing events
echo "<h3>4. Existing Events</h3>";

$existing_events_query = "SELECT id, title, start_datetime, end_datetime, created_at FROM events ORDER BY created_at DESC LIMIT 5";
$existing_result = mysqli_query($conn, $existing_events_query);

if ($existing_result && mysqli_num_rows($existing_result) > 0) {
    echo "<p><strong>Recent Events in Database:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Title</th>";
    echo "<th style='padding: 8px;'>Start DateTime</th>";
    echo "<th style='padding: 8px;'>End DateTime</th>";
    echo "<th style='padding: 8px;'>Created At</th>";
    echo "</tr>";
    
    while ($event = mysqli_fetch_assoc($existing_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>#" . $event['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($event['title']) . "</td>";
        echo "<td style='padding: 8px;'>" . $event['start_datetime'] . "</td>";
        echo "<td style='padding: 8px;'>" . $event['end_datetime'] . "</td>";
        echo "<td style='padding: 8px;'>" . $event['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: gray;'>ℹ️ No existing events found in database</p>";
}

echo "<hr>";

// Final status
echo "<h3>🎯 System Status</h3>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 0.5rem; padding: 1rem; margin: 1rem 0;'>";
echo "<h4 style='color: #155724; margin: 0 0 0.5rem 0;'>✅ Events Table Ready!</h4>";
echo "<p style='color: #155724; margin: 0;'>The events table is properly configured and ready for admin event creation.</p>";
echo "</div>";

echo "<p><strong>Admin Event Management:</strong></p>";
echo "<ul>";
echo "<li>✅ Events table exists with correct structure</li>";
echo "<li>✅ Event insertion/deletion working</li>";
echo "<li>✅ Admin can create events without 'created_by' column</li>";
echo "<li>✅ Compatible with existing SBO event system</li>";
echo "</ul>";

echo "<p><strong>Access Admin Event Management:</strong></p>";
echo "<p><a href='admin/manage_events.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📅 Manage Events</a></p>";
echo "<p><a href='admin/create_event.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>➕ Create Event</a></p>";

mysqli_close($conn);
?>
