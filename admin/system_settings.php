<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Create system_settings table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
)";
mysqli_query($conn, $create_table_sql);

$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_system_name') {
        $new_system_name = trim($_POST['system_name']);
        
        if (!empty($new_system_name)) {
            // Update or insert system name
            $check_query = mysqli_query($conn, "SELECT * FROM system_settings WHERE setting_key = 'system_name'");
            
            if (mysqli_num_rows($check_query) > 0) {
                $update_stmt = mysqli_prepare($conn, "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'system_name'");
                mysqli_stmt_bind_param($update_stmt, "s", $new_system_name);
            } else {
                $update_stmt = mysqli_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_name', ?)");
                mysqli_stmt_bind_param($update_stmt, "s", $new_system_name);
            }
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = "✅ System name updated successfully!";
            } else {
                $error = "❌ Error updating system name: " . mysqli_error($conn);
            }
        } else {
            $error = "❌ Please enter a system name.";
        }
    } elseif ($action === 'update_system_logo') {
        if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['system_logo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $upload_dir = '../assets/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'system_logo.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $logo_path = 'assets/images/' . $new_filename;
                    
                    // Update or insert system logo
                    $check_query = mysqli_query($conn, "SELECT * FROM system_settings WHERE setting_key = 'system_logo'");
                    
                    if (mysqli_num_rows($check_query) > 0) {
                        $update_stmt = mysqli_prepare($conn, "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'system_logo'");
                        mysqli_stmt_bind_param($update_stmt, "s", $logo_path);
                    } else {
                        $update_stmt = mysqli_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_logo', ?)");
                        mysqli_stmt_bind_param($update_stmt, "s", $logo_path);
                    }
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "✅ System logo updated successfully!";
                    } else {
                        $error = "❌ Error updating system logo: " . mysqli_error($conn);
                    }
                } else {
                    $error = "❌ Failed to upload logo file.";
                }
            } else {
                $error = "❌ Invalid file type or size. Please upload JPG, PNG, GIF, or SVG under 5MB.";
            }
        } else {
            $error = "❌ Please select a logo file.";
        }
    }
}

// Include system configuration functions
require_once '../includes/system_config.php';

$current_system_name = getSystemSetting($conn, 'system_name', 'ADLOR');
$current_system_logo = getSystemSetting($conn, 'system_logo', '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('../includes/system_config.php')) {
        include '../includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?= htmlspecialchars($current_system_name) ?></title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-panel-body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card {
            background: white;
            padding: 0;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .section-icon {
            font-size: 1.5rem;
        }
        
        .logo-preview {
            width: 120px;
            height: 120px;
            border: 3px solid var(--gray-200);
            border-radius: 50%;
            padding: 0;
            background: white;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        .logo-preview img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .current-settings {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'system', $_SESSION['admin_name']); ?>

    <div class="admin-header">
        <div class="container">
            <h1 style="margin: 0; font-size: 2.5rem; font-weight: 800;">⚙️ System Settings</h1>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 1.1rem;">Configure system name and branding</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Current Settings Display -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📋</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Current System Settings</h3>
                </div>

                <div class="current-settings">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: center;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-700);">System Name</h4>
                            <p style="margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--primary-color);">
                                <?= htmlspecialchars($current_system_name) ?>
                            </p>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-700);">System Logo</h4>
                            <?php if ($current_system_logo && file_exists('../' . $current_system_logo)): ?>
                                <div class="logo-preview">
                                    <img src="../<?= htmlspecialchars($current_system_logo) ?>" alt="Current System Logo">
                                </div>
                            <?php else: ?>
                                <div class="logo-preview">
                                    No logo uploaded
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Name Settings -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🏷️</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">System Name</h3>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_system_name">
                    
                    <div class="form-group">
                        <label class="form-label" for="system_name">System Name</label>
                        <input type="text" 
                               id="system_name" 
                               name="system_name" 
                               class="form-input" 
                               value="<?= htmlspecialchars($current_system_name) ?>"
                               placeholder="e.g., ADLOR, MySchool System"
                               required>
                        <small style="color: var(--gray-600);">This name will appear in navigation, titles, and throughout the system</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        💾 Update System Name
                    </button>
                </form>
            </div>
        </div>

        <!-- System Logo Settings -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🖼️</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">System Logo</h3>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_system_logo">
                    
                    <div class="form-group">
                        <label class="form-label" for="system_logo">Upload New Logo</label>
                        <input type="file" 
                               id="system_logo" 
                               name="system_logo" 
                               class="form-input" 
                               accept="image/*"
                               required>
                        <small style="color: var(--gray-600);">Supported formats: JPG, PNG, GIF, SVG • Max size: 5MB • Recommended: 200x100px</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        📸 Update System Logo
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 2rem;">
            <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">🔗 Quick Actions</h4>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="dashboard.php" class="btn btn-outline">📊 Dashboard</a>
                <a href="manage_students.php" class="btn btn-outline">👥 Manage Students</a>
                <a href="../" class="btn btn-outline">🏠 View Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
