<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

echo "<h2>🔍 Debugging SBO Login Issue</h2>";
echo "<p>Investigating why SBO accounts created by admin can't login...</p>";

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check SBO table structure
echo "<h3>📋 Step 1: SBO Table Structure</h3>";

$describe_result = mysqli_query($conn, "DESCRIBE sbo_users");
if ($describe_result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Column</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($describe_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Failed to describe sbo_users table: " . mysqli_error($conn) . "</p>";
}

// Step 2: Check existing SBO users
echo "<h3>👥 Step 2: Current SBO Users</h3>";

$users_result = mysqli_query($conn, "SELECT id, email, full_name, name, position, is_active, created_at FROM sbo_users ORDER BY created_at DESC");
if ($users_result) {
    if (mysqli_num_rows($users_result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr style='background-color: #f8f9fa;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Email</th>";
        echo "<th style='padding: 8px;'>Full Name</th>";
        echo "<th style='padding: 8px;'>Name</th>";
        echo "<th style='padding: 8px;'>Position</th>";
        echo "<th style='padding: 8px;'>Active</th>";
        echo "<th style='padding: 8px;'>Created</th>";
        echo "</tr>";
        
        while ($user = mysqli_fetch_assoc($users_result)) {
            $active_status = $user['is_active'] ? '✅ Yes' : '❌ No';
            $active_color = $user['is_active'] ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['full_name'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['name'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['position']) . "</td>";
            echo "<td style='padding: 8px; color: $active_color;'>$active_status</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No SBO users found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to query sbo_users: " . mysqli_error($conn) . "</p>";
}

// Step 3: Test login process
echo "<h3>🔐 Step 3: Testing Login Process</h3>";

// Get the first active SBO user for testing
$test_user_result = mysqli_query($conn, "SELECT * FROM sbo_users WHERE is_active = 1 LIMIT 1");
if ($test_user_result && mysqli_num_rows($test_user_result) > 0) {
    $test_user = mysqli_fetch_assoc($test_user_result);
    
    echo "<p><strong>Testing with user:</strong> " . htmlspecialchars($test_user['email']) . "</p>";
    
    // Check what fields are available
    echo "<p><strong>Available fields for this user:</strong></p>";
    echo "<ul>";
    foreach ($test_user as $field => $value) {
        if ($field !== 'password') {
            echo "<li><strong>$field:</strong> " . htmlspecialchars($value ?? 'NULL') . "</li>";
        } else {
            echo "<li><strong>$field:</strong> [HASHED PASSWORD]</li>";
        }
    }
    echo "</ul>";
    
    // Check if password verification would work
    echo "<p><strong>Password hash check:</strong></p>";
    if (!empty($test_user['password'])) {
        echo "<p style='color: green;'>✅ Password hash exists</p>";
        
        // Test with common default passwords
        $test_passwords = ['adlor2024', 'sbo123456', 'password', '123456'];
        foreach ($test_passwords as $test_pass) {
            if (password_verify($test_pass, $test_user['password'])) {
                echo "<p style='color: green;'>✅ Password '$test_pass' would work for this user</p>";
                break;
            }
        }
    } else {
        echo "<p style='color: red;'>❌ No password hash found</p>";
    }
    
} else {
    echo "<p style='color: orange;'>⚠️ No active SBO users found for testing</p>";
}

// Step 4: Check for column name inconsistencies
echo "<h3>🔍 Step 4: Checking for Column Name Issues</h3>";

$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM sbo_users LIKE '%name%'");
if ($columns_check) {
    echo "<p><strong>Name-related columns:</strong></p>";
    echo "<ul>";
    while ($col = mysqli_fetch_assoc($columns_check)) {
        echo "<li>" . htmlspecialchars($col['Field']) . " (" . htmlspecialchars($col['Type']) . ")</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Step 5: Identify the issues
echo "<h3>🎯 Potential Issues Identified</h3>";

echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>⚠️ Possible Problems:</h4>";
echo "<ol>";
echo "<li><strong>Column Name Mismatch:</strong> Admin creates users with 'full_name' but login might expect 'name'</li>";
echo "<li><strong>Password Issues:</strong> Password might not be properly hashed during creation</li>";
echo "<li><strong>Active Status:</strong> Users might be created as inactive</li>";
echo "<li><strong>Session Variable Mismatch:</strong> Login tries to access wrong field names</li>";
echo "</ol>";

echo "<h4>🔧 Recommended Fixes:</h4>";
echo "<ol>";
echo "<li>Standardize column names (use either 'full_name' or 'name' consistently)</li>";
echo "<li>Ensure password hashing is working correctly</li>";
echo "<li>Verify is_active is set to 1 during creation</li>";
echo "<li>Update login code to use correct field names</li>";
echo "<li>Add better error logging to identify exact failure point</li>";
echo "</ol>";
echo "</div>";

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
