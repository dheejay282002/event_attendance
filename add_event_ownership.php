<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>🔧 Adding Event Ownership Tracking</h2>";
echo "<p>Adding creator tracking to events table for proper access control...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check current events table structure
echo "<h3>📋 Step 1: Current Events Table Structure</h3>";

$describe_result = mysqli_query($conn, "DESCRIBE events");
if ($describe_result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Column</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "</tr>";
    
    $has_created_by = false;
    $has_creator_type = false;
    
    while ($row = mysqli_fetch_assoc($describe_result)) {
        if ($row['Field'] === 'created_by') $has_created_by = true;
        if ($row['Field'] === 'creator_type') $has_creator_type = true;
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Ownership columns status:</strong></p>";
    echo "<ul>";
    echo "<li><strong>created_by:</strong> " . ($has_created_by ? '✅ Exists' : '❌ Missing') . "</li>";
    echo "<li><strong>creator_type:</strong> " . ($has_creator_type ? '✅ Exists' : '❌ Missing') . "</li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>❌ Failed to describe events table: " . mysqli_error($conn) . "</p>";
    $error_count++;
}

// Step 2: Add ownership columns if they don't exist
echo "<h3>➕ Step 2: Adding Ownership Columns</h3>";

if (!$has_created_by) {
    $add_created_by = "ALTER TABLE events ADD COLUMN created_by INT DEFAULT NULL COMMENT 'ID of the user who created this event'";
    if (mysqli_query($conn, $add_created_by)) {
        echo "<p style='color: green;'>✅ Added 'created_by' column</p>";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ Failed to add 'created_by' column: " . mysqli_error($conn) . "</p>";
        $error_count++;
    }
} else {
    echo "<p style='color: blue;'>ℹ️ 'created_by' column already exists</p>";
}

if (!$has_creator_type) {
    $add_creator_type = "ALTER TABLE events ADD COLUMN creator_type ENUM('admin', 'sbo') DEFAULT NULL COMMENT 'Type of user who created this event'";
    if (mysqli_query($conn, $add_creator_type)) {
        echo "<p style='color: green;'>✅ Added 'creator_type' column</p>";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ Failed to add 'creator_type' column: " . mysqli_error($conn) . "</p>";
        $error_count++;
    }
} else {
    echo "<p style='color: blue;'>ℹ️ 'creator_type' column already exists</p>";
}

// Step 3: Update existing events to have admin ownership
echo "<h3>🔄 Step 3: Setting Ownership for Existing Events</h3>";

$existing_events = mysqli_query($conn, "SELECT COUNT(*) as count FROM events WHERE created_by IS NULL OR creator_type IS NULL");
$existing_count = mysqli_fetch_assoc($existing_events)['count'];

if ($existing_count > 0) {
    // Set existing events as created by admin (check if admin table exists)
    $tables_result = mysqli_query($conn, "SHOW TABLES LIKE 'admin%'");
    $admin_table = null;

    while ($table = mysqli_fetch_array($tables_result)) {
        $admin_table = $table[0];
        break;
    }

    if ($admin_table) {
        $admin_check = mysqli_query($conn, "SELECT id FROM $admin_table LIMIT 1");
    } else {
        $admin_check = false;
    }
    if ($admin_check && mysqli_num_rows($admin_check) > 0) {
        $admin_id = mysqli_fetch_assoc($admin_check)['id'];
        
        $update_existing = "UPDATE events SET created_by = $admin_id, creator_type = 'admin' WHERE created_by IS NULL OR creator_type IS NULL";
        if (mysqli_query($conn, $update_existing)) {
            echo "<p style='color: green;'>✅ Updated $existing_count existing events with admin ownership</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to update existing events: " . mysqli_error($conn) . "</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No admin users found. Existing events will remain without ownership.</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ All events already have ownership information</p>";
}

// Step 4: Add index for better performance
echo "<h3>⚡ Step 4: Adding Performance Index</h3>";

$add_index = "CREATE INDEX IF NOT EXISTS idx_events_creator ON events(creator_type, created_by)";
if (mysqli_query($conn, $add_index)) {
    echo "<p style='color: green;'>✅ Added performance index for creator queries</p>";
    $success_count++;
} else {
    echo "<p style='color: red;'>❌ Failed to add index: " . mysqli_error($conn) . "</p>";
    $error_count++;
}

echo "</div>";

// Step 5: Show updated table structure
echo "<h3>📋 Updated Events Table Structure</h3>";

$updated_describe = mysqli_query($conn, "DESCRIBE events");
if ($updated_describe) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Column</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "<th style='padding: 8px;'>Comment</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($updated_describe)) {
        $highlight = in_array($row['Field'], ['created_by', 'creator_type']) ? 'background-color: #d4edda;' : '';
        
        echo "<tr style='$highlight'>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Comment'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>📊 Summary</h3>";
echo "<p><strong>✅ Successful operations:</strong> $success_count</p>";
echo "<p><strong>❌ Failed operations:</strong> $error_count</p>";

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Event Ownership Tracking Added!</h3>";
    echo "<p>The events table now supports proper ownership tracking.</p>";
    echo "<p><strong>✅ What was added:</strong></p>";
    echo "<ul>";
    echo "<li><strong>created_by:</strong> Stores the ID of the user who created the event</li>";
    echo "<li><strong>creator_type:</strong> Stores whether the creator was 'admin' or 'sbo'</li>";
    echo "<li><strong>Performance index:</strong> Added for faster creator-based queries</li>";
    echo "<li><strong>Existing events:</strong> Updated with admin ownership</li>";
    echo "</ul>";
    
    echo "<p><strong>🎯 Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update event creation code to set ownership</li>";
    echo "<li>Update event management queries to filter by ownership</li>";
    echo "<li>Test the new ownership functionality</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Some Issues Occurred</h3>";
    echo "<p>There were $error_count failed operations. Please check the errors above and fix them.</p>";
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
