<?php
include 'db_connect.php';

$message = "";
$error = "";

// Handle setup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_profiles'])) {
    $success_count = 0;
    $error_count = 0;
    $messages = [];
    
    // Add profile_picture column to students table
    $students_query = "ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) NULL";
    if (mysqli_query($conn, $students_query)) {
        $messages[] = "✅ Added profile_picture column to students table";
        $success_count++;
    } else {
        $error_msg = mysqli_error($conn);
        if (strpos($error_msg, 'Duplicate column name') !== false) {
            $messages[] = "ℹ️ profile_picture column already exists in students table";
        } else {
            $messages[] = "❌ Error adding profile_picture to students table: $error_msg";
            $error_count++;
        }
    }
    
    // Add profile_picture column to sbo_users table
    $sbo_query = "ALTER TABLE sbo_users ADD COLUMN profile_picture VARCHAR(255) NULL";
    if (mysqli_query($conn, $sbo_query)) {
        $messages[] = "✅ Added profile_picture column to sbo_users table";
        $success_count++;
    } else {
        $error_msg = mysqli_error($conn);
        if (strpos($error_msg, 'Duplicate column name') !== false) {
            $messages[] = "ℹ️ profile_picture column already exists in sbo_users table";
        } else {
            $messages[] = "❌ Error adding profile_picture to sbo_users table: $error_msg";
            $error_count++;
        }
    }
    
    // Create upload directories
    $directories = [
        'uploads/profile_pictures/admin/',
        'uploads/profile_pictures/sbo/',
        'uploads/profile_pictures/students/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0777, true)) {
                $messages[] = "✅ Created directory: $dir";
                $success_count++;
            } else {
                $messages[] = "❌ Failed to create directory: $dir";
                $error_count++;
            }
        } else {
            $messages[] = "ℹ️ Directory already exists: $dir";
        }
    }
    
    // Create .htaccess file for security
    $htaccess_content = "# Prevent execution of PHP files in uploads directory
<Files *.php>
    Order Deny,Allow
    Deny from all
</Files>

# Allow only image files
<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>";

    $htaccess_path = 'uploads/.htaccess';
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        $messages[] = "✅ Created security .htaccess file";
        $success_count++;
    } else {
        $messages[] = "❌ Failed to create .htaccess file";
        $error_count++;
    }
    
    if ($error_count == 0) {
        $message = "🎉 Profile picture system setup completed successfully!";
    } else {
        $error = "⚠️ Setup completed with some errors. Check details below.";
    }
}

// Check current status
$students_has_column = false;
$sbo_has_column = false;

$check_students = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'profile_picture'");
if (mysqli_num_rows($check_students) > 0) {
    $students_has_column = true;
}

$check_sbo = mysqli_query($conn, "SHOW COLUMNS FROM sbo_users LIKE 'profile_picture'");
if (mysqli_num_rows($check_sbo) > 0) {
    $sbo_has_column = true;
}

$setup_needed = !$students_has_column || !$sbo_has_column;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('includes/system_config.php')) {
        include 'includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Profile Pictures - ADLOR</title>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .setup-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .setup-content {
            padding: 2rem;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .status-card {
            padding: 1.5rem;
            border-radius: 0.75rem;
            border-left: 4px solid;
        }
        
        .status-ready {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        
        .status-needed {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        
        .message-list {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .message-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: 800;">📸 Profile Picture Setup</h1>
            <p style="margin: 0; opacity: 0.9;">Enable profile picture functionality for ADLOR users</p>
        </div>
        
        <div class="setup-content">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <!-- Current Status -->
            <h3 style="margin: 0 0 1rem 0;">📊 Current Status</h3>
            <div class="status-grid">
                <div class="status-card <?= $students_has_column ? 'status-ready' : 'status-needed' ?>">
                    <h4 style="margin: 0 0 0.5rem 0;">👨‍🎓 Students</h4>
                    <p style="margin: 0; font-weight: 600;">
                        <?= $students_has_column ? '✅ Ready' : '⚠️ Setup Needed' ?>
                    </p>
                </div>
                
                <div class="status-card <?= $sbo_has_column ? 'status-ready' : 'status-needed' ?>">
                    <h4 style="margin: 0 0 0.5rem 0;">👥 SBO Users</h4>
                    <p style="margin: 0; font-weight: 600;">
                        <?= $sbo_has_column ? '✅ Ready' : '⚠️ Setup Needed' ?>
                    </p>
                </div>
            </div>
            
            <?php if ($setup_needed): ?>
                <!-- Setup Form -->
                <div style="background: #fef3c7; padding: 2rem; border-radius: 1rem; border: 2px solid #f59e0b; margin-bottom: 2rem;">
                    <h3 style="margin: 0 0 1rem 0; color: #92400e;">🔧 Setup Required</h3>
                    <p style="margin: 0 0 1.5rem 0; color: #92400e;">
                        Profile picture functionality needs to be set up. This will add the necessary database columns and create upload directories.
                    </p>
                    
                    <form method="POST">
                        <button type="submit" name="setup_profiles" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                            🚀 Setup Profile Pictures
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Already Setup -->
                <div style="background: #d1fae5; padding: 2rem; border-radius: 1rem; border: 2px solid #10b981; margin-bottom: 2rem;">
                    <h3 style="margin: 0 0 1rem 0; color: #065f46;">✅ Setup Complete</h3>
                    <p style="margin: 0; color: #065f46;">
                        Profile picture functionality is already set up and ready to use!
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Setup Messages -->
            <?php if (isset($messages) && !empty($messages)): ?>
                <h3 style="margin: 2rem 0 1rem 0;">📋 Setup Details</h3>
                <div class="message-list">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item"><?= $msg ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Quick Links -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
                <h3 style="margin: 0 0 1rem 0;">🔗 Quick Access</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="admin/login.php" class="btn btn-outline" style="text-decoration: none;">⚙️ Admin Login</a>
                    <a href="sbo/login.php" class="btn btn-outline" style="text-decoration: none;">👥 SBO Login</a>
                    <a href="student_login.php" class="btn btn-outline" style="text-decoration: none;">🎓 Student Login</a>
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">🏠 Home</a>
                </div>
            </div>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: #f1f5f9; border-radius: 1rem;">
                <h4 style="margin: 0 0 1rem 0; color: #475569;">📖 What This Setup Does</h4>
                <ul style="margin: 0; color: #475569; line-height: 1.6;">
                    <li>Adds <code>profile_picture</code> column to students table</li>
                    <li>Adds <code>profile_picture</code> column to sbo_users table</li>
                    <li>Creates secure upload directories for profile pictures</li>
                    <li>Sets up security configurations to prevent unauthorized access</li>
                    <li>Enables profile picture upload functionality for all user types</li>
                </ul>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
