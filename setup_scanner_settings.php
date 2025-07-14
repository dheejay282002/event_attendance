<?php
include 'db_connect.php';

// Create scanner_settings table
$create_table_query = "
CREATE TABLE IF NOT EXISTS scanner_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    is_enabled BOOLEAN DEFAULT 1,
    start_time TIME NULL,
    end_time TIME NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    days_of_week VARCHAR(20) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_table_query)) {
    echo "✅ Scanner settings table created successfully.<br>";
} else {
    echo "❌ Error creating scanner settings table: " . mysqli_error($conn) . "<br>";
}

// Insert default scanner settings
$default_settings = [
    [
        'setting_name' => 'qr_scanner_enabled',
        'setting_value' => 'enabled',
        'is_enabled' => 1
    ],
    [
        'setting_name' => 'manual_id_entry_enabled',
        'setting_value' => 'enabled',
        'is_enabled' => 1
    ],
    [
        'setting_name' => 'scanner_schedule_enabled',
        'setting_value' => 'disabled',
        'is_enabled' => 0
    ],
    [
        'setting_name' => 'scanner_time_restriction',
        'setting_value' => 'none',
        'is_enabled' => 0,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00'
    ],
    [
        'setting_name' => 'scanner_date_restriction',
        'setting_value' => 'none',
        'is_enabled' => 0,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+30 days'))
    ],
    [
        'setting_name' => 'scanner_days_restriction',
        'setting_value' => 'none',
        'is_enabled' => 0,
        'days_of_week' => '1,2,3,4,5'
    ]
];

foreach ($default_settings as $setting) {
    $check_query = mysqli_prepare($conn, "SELECT id FROM scanner_settings WHERE setting_name = ?");
    mysqli_stmt_bind_param($check_query, "s", $setting['setting_name']);
    mysqli_stmt_execute($check_query);
    $check_result = mysqli_stmt_get_result($check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO scanner_settings (setting_name, setting_value, is_enabled, start_time, end_time, start_date, end_date, days_of_week) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);

        $start_time = $setting['start_time'] ?? null;
        $end_time = $setting['end_time'] ?? null;
        $start_date = $setting['start_date'] ?? null;
        $end_date = $setting['end_date'] ?? null;
        $days_of_week = $setting['days_of_week'] ?? null;

        mysqli_stmt_bind_param($stmt, "ssisssss",
            $setting['setting_name'],
            $setting['setting_value'],
            $setting['is_enabled'],
            $start_time,
            $end_time,
            $start_date,
            $end_date,
            $days_of_week
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Added setting: " . $setting['setting_name'] . "<br>";
        } else {
            echo "❌ Error adding setting " . $setting['setting_name'] . ": " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "ℹ️ Setting already exists: " . $setting['setting_name'] . "<br>";
    }
}

echo "<br><strong>🎉 Scanner settings setup complete!</strong><br>";
echo "<p>You can now manage scanner availability through the admin panel.</p>";
?>
