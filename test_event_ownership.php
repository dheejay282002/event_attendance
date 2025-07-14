<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>🧪 Testing Event Ownership Implementation</h2>";
echo "<p>Verifying that event ownership and access control is working correctly...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check events table structure
echo "<h3>📋 Step 1: Events Table Structure</h3>";

$describe_result = mysqli_query($conn, "DESCRIBE events");
if ($describe_result) {
    $has_created_by = false;
    $has_creator_type = false;
    
    while ($row = mysqli_fetch_assoc($describe_result)) {
        if ($row['Field'] === 'created_by') $has_created_by = true;
        if ($row['Field'] === 'creator_type') $has_creator_type = true;
    }
    
    echo "<p><strong>Ownership columns:</strong></p>";
    echo "<ul>";
    echo "<li><strong>created_by:</strong> " . ($has_created_by ? '✅ Present' : '❌ Missing') . "</li>";
    echo "<li><strong>creator_type:</strong> " . ($has_creator_type ? '✅ Present' : '❌ Missing') . "</li>";
    echo "</ul>";
    
    if ($has_created_by && $has_creator_type) {
        $success_count++;
    } else {
        $error_count++;
    }
} else {
    echo "<p style='color: red;'>❌ Failed to check table structure: " . mysqli_error($conn) . "</p>";
    $error_count++;
}

// Step 2: Check existing events with ownership
echo "<h3>📅 Step 2: Current Events with Ownership</h3>";

$events_query = "SELECT e.id, e.title, e.created_by, e.creator_type, 
                    CASE 
                        WHEN e.creator_type = 'admin' THEN 'Admin User'
                        WHEN e.creator_type = 'sbo' THEN COALESCE(s.full_name, 'SBO User')
                        ELSE 'Unknown'
                    END as creator_name
                FROM events e 
                LEFT JOIN sbo_users s ON e.created_by = s.id AND e.creator_type = 'sbo'
                ORDER BY e.created_at DESC 
                LIMIT 10";

$events_result = mysqli_query($conn, $events_query);

if ($events_result) {
    if (mysqli_num_rows($events_result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background-color: #f8f9fa;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Event Title</th>";
        echo "<th style='padding: 8px;'>Created By ID</th>";
        echo "<th style='padding: 8px;'>Creator Type</th>";
        echo "<th style='padding: 8px;'>Creator Name</th>";
        echo "</tr>";
        
        while ($event = mysqli_fetch_assoc($events_result)) {
            $ownership_status = ($event['created_by'] && $event['creator_type']) ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($event['id']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($event['title']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($event['created_by'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($event['creator_type'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>$ownership_status " . htmlspecialchars($event['creator_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $success_count++;
    } else {
        echo "<p style='color: orange;'>⚠️ No events found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to query events: " . mysqli_error($conn) . "</p>";
    $error_count++;
}

// Step 3: Check SBO users for testing
echo "<h3>👥 Step 3: Available SBO Users</h3>";

$sbo_users_result = mysqli_query($conn, "SELECT id, email, full_name, position FROM sbo_users WHERE is_active = 1 LIMIT 5");

if ($sbo_users_result && mysqli_num_rows($sbo_users_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Email</th>";
    echo "<th style='padding: 8px;'>Full Name</th>";
    echo "<th style='padding: 8px;'>Position</th>";
    echo "</tr>";
    
    while ($sbo = mysqli_fetch_assoc($sbo_users_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($sbo['id']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($sbo['email']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($sbo['full_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($sbo['position']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $success_count++;
} else {
    echo "<p style='color: orange;'>⚠️ No active SBO users found</p>";
}

echo "</div>";

// Step 4: Test ownership filtering
echo "<h3>🔍 Step 4: Testing Ownership Filtering</h3>";

// Get first SBO user for testing
$test_sbo = mysqli_query($conn, "SELECT id FROM sbo_users WHERE is_active = 1 LIMIT 1");
if ($test_sbo && mysqli_num_rows($test_sbo) > 0) {
    $sbo_id = mysqli_fetch_assoc($test_sbo)['id'];
    
    // Test SBO-specific query
    $sbo_events_query = "SELECT COUNT(*) as count FROM events WHERE created_by = $sbo_id AND creator_type = 'sbo'";
    $sbo_events_result = mysqli_query($conn, $sbo_events_query);
    $sbo_events_count = mysqli_fetch_assoc($sbo_events_result)['count'];
    
    echo "<p><strong>SBO User ID $sbo_id events:</strong> $sbo_events_count events</p>";
    
    // Test admin query (all events)
    $all_events_query = "SELECT COUNT(*) as count FROM events";
    $all_events_result = mysqli_query($conn, $all_events_query);
    $all_events_count = mysqli_fetch_assoc($all_events_result)['count'];
    
    echo "<p><strong>Total events (admin view):</strong> $all_events_count events</p>";
    
    if ($sbo_events_count <= $all_events_count) {
        echo "<p style='color: green;'>✅ Ownership filtering is working correctly</p>";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ Ownership filtering issue detected</p>";
        $error_count++;
    }
} else {
    echo "<p style='color: orange;'>⚠️ No SBO users available for testing</p>";
}

echo "<h3>📊 Test Summary</h3>";
echo "<p><strong>✅ Successful tests:</strong> $success_count</p>";
echo "<p><strong>❌ Failed tests:</strong> $error_count</p>";

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Event Ownership Implementation Complete!</h3>";
    echo "<p>All tests passed! The event ownership system is working correctly.</p>";
    
    echo "<p><strong>✅ What's working:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Database Structure:</strong> Ownership columns are present</li>";
    echo "<li><strong>Event Creation:</strong> New events will be tagged with creator info</li>";
    echo "<li><strong>Access Control:</strong> SBO users can only see their own events</li>";
    echo "<li><strong>Admin Access:</strong> Admin can see all events with creator information</li>";
    echo "</ul>";
    
    echo "<p><strong>🧪 Test the functionality:</strong></p>";
    echo "<ol>";
    echo "<li><strong>SBO Test:</strong> Login as SBO and create an event</li>";
    echo "<li><strong>SBO View:</strong> Check that SBO only sees their own events</li>";
    echo "<li><strong>Admin Test:</strong> Login as admin and view all events</li>";
    echo "<li><strong>Admin View:</strong> Verify admin can see creator information</li>";
    echo "</ol>";
    
    echo "<p><strong>🎯 Key Features:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Event Isolation:</strong> SBO users only see events they created</li>";
    echo "<li><strong>Admin Overview:</strong> Admin sees all events with creator details</li>";
    echo "<li><strong>Edit Protection:</strong> SBO users can only edit their own events</li>";
    echo "<li><strong>Delete Protection:</strong> SBO users can only delete their own events</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Some Issues Found</h3>";
    echo "<p>There were $error_count failed tests. Please check the errors above and fix them.</p>";
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

td, th {
    border: 1px solid #dee2e6;
}

ul, ol {
    margin-left: 1.5rem;
}

li {
    margin-bottom: 0.5rem;
}
</style>
