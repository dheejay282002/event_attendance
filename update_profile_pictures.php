<?php
include 'db_connect.php';

echo "<h2>Adding Profile Picture Support to ADLOR Database</h2>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Add profile_picture column to students table
echo "<h3>Updating Students Table...</h3>";
$students_query = "ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) NULL";
if (mysqli_query($conn, $students_query)) {
    echo "<p style='color: green;'>✅ Added profile_picture column to students table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>ℹ️ profile_picture column already exists in students table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding profile_picture to students table: $error</p>";
        $error_count++;
    }
}

// Add profile_picture column to sbo_users table
echo "<h3>Updating SBO Users Table...</h3>";
$sbo_query = "ALTER TABLE sbo_users ADD COLUMN profile_picture VARCHAR(255) NULL";
if (mysqli_query($conn, $sbo_query)) {
    echo "<p style='color: green;'>✅ Added profile_picture column to sbo_users table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>ℹ️ profile_picture column already exists in sbo_users table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding profile_picture to sbo_users table: $error</p>";
        $error_count++;
    }
}

// Create uploads directories
echo "<h3>Creating Upload Directories...</h3>";

$directories = [
    'uploads/profile_pictures/admin/',
    'uploads/profile_pictures/sbo/',
    'uploads/profile_pictures/students/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
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

// Create .htaccess file for uploads security
echo "<h3>Setting Up Upload Security...</h3>";
$htaccess_content = "# Prevent execution of PHP files in uploads directory
<Files *.php>
    Order Deny,Allow
    Deny from all
</Files>

# Allow only image files
<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Deny access to other file types
<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>";

$htaccess_path = 'uploads/.htaccess';
if (file_put_contents($htaccess_path, $htaccess_content)) {
    echo "<p style='color: green;'>✅ Created security .htaccess file</p>";
    $success_count++;
} else {
    echo "<p style='color: red;'>❌ Failed to create .htaccess file</p>";
    $error_count++;
}

// Create default avatar images directory
echo "<h3>Setting Up Default Avatars...</h3>";
$assets_dir = 'assets/images/';
if (!file_exists($assets_dir)) {
    if (mkdir($assets_dir, 0777, true)) {
        echo "<p style='color: green;'>✅ Created assets/images directory</p>";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ Failed to create assets/images directory</p>";
        $error_count++;
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Assets directory already exists</p>";
}

echo "</div>";

// Summary
echo "<div style='margin-top: 20px; padding: 20px; border-radius: 5px; " . 
     ($error_count == 0 ? "background: #d4edda; color: #155724;" : "background: #f8d7da; color: #721c24;") . "'>";

if ($error_count == 0) {
    echo "<h3>🎉 Profile Picture System Setup Complete!</h3>";
    echo "<p>All database tables and directories have been set up successfully.</p>";
    echo "<p><strong>Features Added:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Profile picture support for Students</li>";
    echo "<li>✅ Profile picture support for SBO Users</li>";
    echo "<li>✅ Secure upload directories created</li>";
    echo "<li>✅ Upload security configured</li>";
    echo "</ul>";
    echo "<p><strong>Profile Pages Available:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin/profile.php'>Admin Profile</a></li>";
    echo "<li><a href='sbo/profile.php'>SBO Profile</a></li>";
    echo "<li><a href='student_profile.php'>Student Profile</a></li>";
    echo "</ul>";
} else {
    echo "<h3>⚠️ Setup Completed with Errors</h3>";
    echo "<p>Some operations failed. Please check the errors above and try again.</p>";
    echo "<p>Successful operations: $success_count</p>";
    echo "<p>Failed operations: $error_count</p>";
}

echo "</div>";

// Navigation links
echo "<div style='margin-top: 20px; text-align: center;'>";
echo "<h3>🔗 Quick Access</h3>";
echo "<div style='display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;'>";
echo "<a href='admin/login.php' style='padding: 10px 20px; background: #7c3aed; color: white; text-decoration: none; border-radius: 5px;'>Admin Login</a>";
echo "<a href='sbo/login.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>SBO Login</a>";
echo "<a href='student_login.php' style='padding: 10px 20px; background: #059669; color: white; text-decoration: none; border-radius: 5px;'>Student Login</a>";
echo "<a href='index.php' style='padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 5px;'>Home</a>";
echo "</div>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
a { color: #2563eb; }
</style>
