<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Setting up SBO Authentication Table</h2>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Create SBO table
$sbo_table_sql = "CREATE TABLE IF NOT EXISTS sbo_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    position VARCHAR(100) DEFAULT 'SBO Member',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

echo "<p><strong>Creating SBO users table...</strong></p>";

if (mysqli_query($conn, $sbo_table_sql)) {
    echo "<p style='color: green;'>✅ SBO users table created successfully</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Error creating SBO table: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Insert default SBO users
$default_sbo_users = [
    [
        'email' => 'sbo.president@school.edu',
        'password' => password_hash('sbo123456', PASSWORD_DEFAULT),
        'full_name' => 'SBO President',
        'position' => 'President'
    ],
    [
        'email' => 'sbo.secretary@school.edu',
        'password' => password_hash('sbo123456', PASSWORD_DEFAULT),
        'full_name' => 'SBO Secretary',
        'position' => 'Secretary'
    ],
    [
        'email' => 'sbo.events@school.edu',
        'password' => password_hash('sbo123456', PASSWORD_DEFAULT),
        'full_name' => 'Events Coordinator',
        'position' => 'Events Coordinator'
    ]
];

echo "<p><strong>Adding default SBO users...</strong></p>";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO sbo_users (email, password, full_name, position) VALUES (?, ?, ?, ?)");

foreach ($default_sbo_users as $user) {
    mysqli_stmt_bind_param($stmt, "ssss", $user['email'], $user['password'], $user['full_name'], $user['position']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✅ Added SBO user: {$user['full_name']} ({$user['email']})</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add {$user['full_name']}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Setup Summary</h3>";
echo "<p><strong>Successful operations:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Show current SBO users
echo "<h3>Current SBO Users</h3>";
$sbo_result = mysqli_query($conn, "SELECT email, full_name, position, is_active, created_at FROM sbo_users ORDER BY created_at DESC");

if ($sbo_result && mysqli_num_rows($sbo_result) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<tr style='background: #f8f9fa; border-bottom: 2px solid #dee2e6;'>";
    echo "<th style='padding: 0.75rem; text-align: left; border: 1px solid #dee2e6;'>Email</th>";
    echo "<th style='padding: 0.75rem; text-align: left; border: 1px solid #dee2e6;'>Full Name</th>";
    echo "<th style='padding: 0.75rem; text-align: left; border: 1px solid #dee2e6;'>Position</th>";
    echo "<th style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>Status</th>";
    echo "<th style='padding: 0.75rem; text-align: left; border: 1px solid #dee2e6;'>Created</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($sbo_result)) {
        $status = $row['is_active'] ? '<span style="color: green;">✅ Active</span>' : '<span style="color: red;">❌ Inactive</span>';
        $created = date('M j, Y g:i A', strtotime($row['created_at']));
        
        echo "<tr style='border-bottom: 1px solid #dee2e6;'>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['position']) . "</td>";
        echo "<td style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>$status</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$created</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No SBO users found.</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 SBO Setup Complete!</h3>";
    echo "<p>SBO authentication system has been set up successfully.</p>";
    echo "<p><strong>Default Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>President:</strong> sbo.president@school.edu / sbo123456</li>";
    echo "<li><strong>Secretary:</strong> sbo.secretary@school.edu / sbo123456</li>";
    echo "<li><strong>Events Coordinator:</strong> sbo.events@school.edu / sbo123456</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>SBO members can now log in at <a href='sbo/login.php'>sbo/login.php</a></li>";
    echo "<li>Change default passwords after first login</li>";
    echo "<li>Create events and manage attendance</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Setup Issues</h3>";
    echo "<p>Some errors occurred during setup. Please review the errors above.</p>";
    echo "</div>";
}

// Close connection
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 5px;
    overflow: hidden;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
