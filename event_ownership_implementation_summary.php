<?php
include 'db_connect.php';

echo "<h2>🎯 Event Ownership Implementation - Complete!</h2>";
echo "<p>Successfully implemented event ownership and access control system.</p>";

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

echo "<h3>✅ Implementation Summary</h3>";

echo "<h4>🗄️ Database Changes:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Added 'created_by' column:</strong> Stores the ID of the user who created the event</li>";
echo "<li>✅ <strong>Added 'creator_type' column:</strong> Stores whether creator was 'admin' or 'sbo'</li>";
echo "<li>✅ <strong>Added performance index:</strong> For faster creator-based queries</li>";
echo "<li>✅ <strong>Updated existing events:</strong> Set admin ownership for legacy events</li>";
echo "</ul>";

echo "<h4>📝 SBO Event Management Updates:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Create Event:</strong> Now tracks SBO user as creator</li>";
echo "<li>✅ <strong>Edit Event:</strong> Only allows editing events created by current SBO user</li>";
echo "<li>✅ <strong>Manage Events:</strong> Only shows events created by current SBO user</li>";
echo "<li>✅ <strong>Delete Event:</strong> Only allows deleting events created by current SBO user</li>";
echo "<li>✅ <strong>No Events Message:</strong> Updated to explain ownership filtering</li>";
echo "</ul>";

echo "<h4>👑 Admin Event Management Updates:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Create Event:</strong> Now tracks admin as creator</li>";
echo "<li>✅ <strong>Manage Events:</strong> Shows ALL events with creator information</li>";
echo "<li>✅ <strong>Creator Column:</strong> Displays who created each event</li>";
echo "<li>✅ <strong>Full Access:</strong> Admin can edit/delete any event</li>";
echo "</ul>";

echo "</div>";

echo "<h3>🎯 How Event Ownership Works</h3>";

echo "<div style='background: #e7f3ff; color: #0c5460; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔐 Access Control Rules:</h4>";

echo "<p><strong>For SBO Users:</strong></p>";
echo "<ul>";
echo "<li>🔒 <strong>View:</strong> Can only see events they created</li>";
echo "<li>✏️ <strong>Edit:</strong> Can only edit events they created</li>";
echo "<li>🗑️ <strong>Delete:</strong> Can only delete events they created</li>";
echo "<li>➕ <strong>Create:</strong> New events are automatically tagged with their ownership</li>";
echo "</ul>";

echo "<p><strong>For Admin Users:</strong></p>";
echo "<ul>";
echo "<li>👁️ <strong>View:</strong> Can see ALL events from all creators</li>";
echo "<li>✏️ <strong>Edit:</strong> Can edit ANY event regardless of creator</li>";
echo "<li>🗑️ <strong>Delete:</strong> Can delete ANY event regardless of creator</li>";
echo "<li>➕ <strong>Create:</strong> New events are tagged as admin-created</li>";
echo "<li>📊 <strong>Creator Info:</strong> Can see who created each event</li>";
echo "</ul>";
echo "</div>";

echo "<h3>📊 Current System Status</h3>";

// Show event ownership statistics
$stats_query = "SELECT 
    creator_type,
    COUNT(*) as event_count,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN creator_type = 'admin' THEN 'Admin'
            WHEN creator_type = 'sbo' THEN COALESCE(s.full_name, 'SBO User')
            ELSE 'Unknown'
        END
        SEPARATOR ', '
    ) as creators
FROM events e 
LEFT JOIN sbo_users s ON e.created_by = s.id AND e.creator_type = 'sbo'
WHERE e.creator_type IS NOT NULL
GROUP BY creator_type";

$stats_result = mysqli_query($conn, $stats_query);

if ($stats_result && mysqli_num_rows($stats_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Creator Type</th>";
    echo "<th style='padding: 8px;'>Event Count</th>";
    echo "<th style='padding: 8px;'>Creators</th>";
    echo "</tr>";
    
    while ($stat = mysqli_fetch_assoc($stats_result)) {
        $type_icon = $stat['creator_type'] === 'admin' ? '👑' : '👤';
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>$type_icon " . ucfirst($stat['creator_type']) . "</td>";
        echo "<td style='padding: 8px; text-align: center;'>" . $stat['event_count'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($stat['creators']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No events with ownership information found.</p>";
}

// Show recent events with ownership
echo "<h4>📅 Recent Events with Ownership:</h4>";

$recent_events = mysqli_query($conn, "SELECT e.id, e.title, e.creator_type,
    CASE 
        WHEN e.creator_type = 'admin' THEN 'Admin User'
        WHEN e.creator_type = 'sbo' THEN COALESCE(s.full_name, 'SBO User')
        ELSE 'Unknown'
    END as creator_name,
    e.created_at
FROM events e 
LEFT JOIN sbo_users s ON e.created_by = s.id AND e.creator_type = 'sbo'
ORDER BY e.created_at DESC 
LIMIT 5");

if ($recent_events && mysqli_num_rows($recent_events) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Event</th>";
    echo "<th style='padding: 8px;'>Creator</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Created</th>";
    echo "</tr>";
    
    while ($event = mysqli_fetch_assoc($recent_events)) {
        $type_icon = $event['creator_type'] === 'admin' ? '👑' : '👤';
        $type_color = $event['creator_type'] === 'admin' ? '#dc3545' : '#007bff';
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($event['title']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($event['creator_name']) . "</td>";
        echo "<td style='padding: 8px; color: $type_color;'>$type_icon " . ucfirst($event['creator_type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($event['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>🎯 Benefits Achieved</h3>";

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🎉 Event Ownership System Benefits:</h4>";

echo "<p><strong>🔐 Security & Privacy:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>Data Isolation:</strong> SBO users can only access their own events</li>";
echo "<li>✅ <strong>Edit Protection:</strong> Users cannot modify events they didn't create</li>";
echo "<li>✅ <strong>Delete Protection:</strong> Users cannot delete events they didn't create</li>";
echo "<li>✅ <strong>Access Control:</strong> Proper permission checking on all operations</li>";
echo "</ul>";

echo "<p><strong>👑 Administrative Control:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>Full Oversight:</strong> Admin can see and manage all events</li>";
echo "<li>✅ <strong>Creator Tracking:</strong> Admin knows who created each event</li>";
echo "<li>✅ <strong>Accountability:</strong> Clear audit trail of event creators</li>";
echo "<li>✅ <strong>System Management:</strong> Admin can intervene when needed</li>";
echo "</ul>";

echo "<p><strong>👤 User Experience:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>Clean Interface:</strong> SBO users only see relevant events</li>";
echo "<li>✅ <strong>Reduced Confusion:</strong> No access to events they can't manage</li>";
echo "<li>✅ <strong>Clear Ownership:</strong> Users know which events are theirs</li>";
echo "<li>✅ <strong>Intuitive Design:</strong> Natural workflow for event management</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🧪 Testing Your Implementation</h3>";

echo "<p><strong>Test the event ownership system:</strong></p>";
echo "<ol>";
echo "<li><strong>SBO Test:</strong></li>";
echo "<ul>";
echo "<li>Login as an SBO user (e.g., officer@edu.ph / adlor2024)</li>";
echo "<li>Go to <a href='sbo/manage_events.php' target='_blank'>SBO Manage Events</a></li>";
echo "<li>Verify you only see events you created</li>";
echo "<li>Create a new event and verify it appears in your list</li>";
echo "<li>Try to edit/delete only your own events</li>";
echo "</ul>";

echo "<li><strong>Admin Test:</strong></li>";
echo "<ul>";
echo "<li>Login as admin</li>";
echo "<li>Go to <a href='admin/manage_events.php' target='_blank'>Admin Manage Events</a></li>";
echo "<li>Verify you can see ALL events with creator information</li>";
echo "<li>Check the Creator column shows proper ownership</li>";
echo "<li>Verify you can edit/delete any event</li>";
echo "</ul>";

echo "<li><strong>Multi-User Test:</strong></li>";
echo "<ul>";
echo "<li>Create events with different SBO users</li>";
echo "<li>Verify each SBO user only sees their own events</li>";
echo "<li>Verify admin sees all events from all users</li>";
echo "</ul>";
echo "</ol>";

echo "<h3>🎯 Real-World Use Cases</h3>";

echo "<ul>";
echo "<li><strong>🏫 Department Events:</strong> Each SBO officer manages their department's events</li>";
echo "<li><strong>🎭 Club Activities:</strong> Different clubs create and manage their own events</li>";
echo "<li><strong>📚 Academic Events:</strong> Course-specific events managed by relevant officers</li>";
echo "<li><strong>🎉 Social Events:</strong> Social committee manages their events separately</li>";
echo "<li><strong>👑 Admin Oversight:</strong> Admin can monitor all activities across departments</li>";
echo "</ul>";

echo "<p style='margin-top: 2rem; font-style: italic; color: #666;'>Event ownership and access control successfully implemented! SBO users now have proper event isolation while admin maintains full oversight. 🎉</p>";

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

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
