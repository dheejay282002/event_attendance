<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🔧 Fixing Profile Picture Column</h2>";
echo "<p>Adding missing profile_picture column to students table...</p>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check if profile_picture column exists in students table
echo "<p><strong>Step 1: Checking students table structure...</strong></p>";

$check_column = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'profile_picture'");
if (mysqli_num_rows($check_column) == 0) {
    echo "<p style='color: orange;'>⚠️ profile_picture column missing from students table</p>";
    
    // Add profile_picture column to students table
    $add_column_sql = "ALTER TABLE students ADD COLUMN profile_picture VARCHAR(500) DEFAULT NULL AFTER password";
    if (mysqli_query($conn, $add_column_sql)) {
        echo "<p style='color: green;'>✅ Added profile_picture column to students table</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add profile_picture column: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
} else {
    echo "<p style='color: green;'>✅ profile_picture column already exists in students table</p>";
}

// Step 2: Check if profile_picture column exists in sbo_users table
echo "<p><strong>Step 2: Checking sbo_users table structure...</strong></p>";

$check_sbo_column = mysqli_query($conn, "SHOW COLUMNS FROM sbo_users LIKE 'profile_picture'");
if (mysqli_num_rows($check_sbo_column) == 0) {
    echo "<p style='color: orange;'>⚠️ profile_picture column missing from sbo_users table</p>";
    
    // Add profile_picture column to sbo_users table
    $add_sbo_column_sql = "ALTER TABLE sbo_users ADD COLUMN profile_picture VARCHAR(500) DEFAULT NULL AFTER password";
    if (mysqli_query($conn, $add_sbo_column_sql)) {
        echo "<p style='color: green;'>✅ Added profile_picture column to sbo_users table</p>";
        $success_count++;
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add profile_picture column to sbo_users: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
} else {
    echo "<p style='color: green;'>✅ profile_picture column already exists in sbo_users table</p>";
}

// Step 3: Create profile picture directories
echo "<p><strong>Step 3: Creating profile picture directories...</strong></p>";

$directories = [
    'uploads/profile_pictures',
    'uploads/profile_pictures/students',
    'uploads/profile_pictures/sbo',
    'uploads/profile_pictures/admin'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color: green;'>✅ Created directory: $dir</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to create directory: $dir</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Directory already exists: $dir</p>";
    }
}

// Step 4: Create .htaccess for security
echo "<p><strong>Step 4: Setting up security...</strong></p>";

$htaccess_content = "# Prevent direct access to PHP files
<Files *.php>
    Order Deny,Allow
    Deny from all
</Files>

# Allow image files
<FilesMatch \"\.(jpg|jpeg|png|gif|webp)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Prevent directory browsing
Options -Indexes
";

$htaccess_path = 'uploads/profile_pictures/.htaccess';
if (file_put_contents($htaccess_path, $htaccess_content)) {
    echo "<p style='color: green;'>✅ Created security .htaccess file</p>";
    $success_count++;
} else {
    echo "<p style='color: red;'>❌ Failed to create .htaccess file</p>";
    $error_count++;
}

echo "</div>";

echo "<h3>Fix Summary</h3>";
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

// Step 5: Verify the fix
echo "<h3>Verification</h3>";
echo "<p><strong>Testing students table structure...</strong></p>";

$verify_students = mysqli_query($conn, "DESCRIBE students");
echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background-color: #f8f9fa;'><th style='padding: 8px;'>Column</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th></tr>";

while ($row = mysqli_fetch_assoc($verify_students)) {
    $highlight = ($row['Field'] == 'profile_picture') ? "style='background-color: #d4edda;'" : "";
    echo "<tr $highlight>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Profile Picture System Fixed!</h3>";
    echo "<p>The profile_picture column has been added and the system is now ready.</p>";
    echo "<p><strong>Students can now:</strong></p>";
    echo "<ul>";
    echo "<li>Upload profile pictures in their settings</li>";
    echo "<li>View profile pictures in QR codes and dashboards</li>";
    echo "<li>Update their magical photos</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test student login and profile picture upload</li>";
    echo "<li>Try accessing student_qr_codes.php again</li>";
    echo "<li>Upload some magical profile pictures for Harry Potter characters</li>";
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
    width: 100%;
}

th {
    background-color: #6c757d !important;
    color: white;
}

td, th {
    border: 1px solid #dee2e6;
}
</style>
