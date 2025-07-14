<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Setting Up System Settings</h2>";

// Create system_settings table
$system_settings_sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
)";

if (mysqli_query($conn, $system_settings_sql)) {
    echo "<p style='color: green;'>✅ System settings table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating system settings table: " . mysqli_error($conn) . "</p>";
}

// Insert default system settings
$default_settings = [
    ['system_name', 'ADLOR'],
    ['system_logo', ''],
    ['system_description', 'Event Attendance System'],
    ['system_version', '1.0.0']
];

foreach ($default_settings as $setting) {
    $key = $setting[0];
    $value = $setting[1];
    
    // Check if setting already exists
    $check_query = mysqli_prepare($conn, "SELECT id FROM system_settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($check_query, "s", $key);
    mysqli_stmt_execute($check_query);
    $result = mysqli_stmt_get_result($check_query);
    
    if (mysqli_num_rows($result) == 0) {
        // Insert new setting
        $insert_query = mysqli_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert_query, "ss", $key, $value);
        
        if (mysqli_stmt_execute($insert_query)) {
            echo "<p style='color: green;'>✅ Added default setting: {$key} = '{$value}'</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding setting {$key}: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Setting {$key} already exists</p>";
    }
}

// Create assets/images directory if it doesn't exist
$assets_dir = 'assets/images/';
if (!file_exists($assets_dir)) {
    if (mkdir($assets_dir, 0777, true)) {
        echo "<p style='color: green;'>✅ Created assets/images directory</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create assets/images directory</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Assets/images directory already exists</p>";
}

// Test system settings retrieval
echo "<h3>Testing System Settings</h3>";

$test_query = mysqli_query($conn, "SELECT * FROM system_settings ORDER BY setting_key");
if ($test_query && mysqli_num_rows($test_query) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Setting Key</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Setting Value</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Updated At</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($setting = mysqli_fetch_assoc($test_query)) {
        echo "<tr>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($setting['setting_key']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($setting['setting_value']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($setting['updated_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No system settings found</p>";
}

echo "<hr>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p>The system settings feature is now ready. You can now:</p>";
echo "<ul>";
echo "<li>Access system settings from the admin panel</li>";
echo "<li>Change the system name (currently: ADLOR)</li>";
echo "<li>Upload a custom system logo</li>";
echo "<li>Settings will sync across all pages and users</li>";
echo "</ul>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/system_settings.php'>Go to System Settings</a></li>";
echo "<li><a href='admin/dashboard.php'>Go to Admin Dashboard</a></li>";
echo "<li><a href='index.php'>View Homepage</a></li>";
echo "</ul>";
echo "</div>";

// Close database connection
mysqli_close($conn);
?>
