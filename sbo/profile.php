<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Get current SBO user data
$stmt = mysqli_prepare($conn, "SELECT * FROM sbo_users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['sbo_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sbo_user = mysqli_fetch_assoc($result);

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profile_pictures/sbo/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if profile_picture column exists
$column_exists = false;
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM sbo_users LIKE 'profile_picture'");
if (mysqli_num_rows($check_column) > 0) {
    $column_exists = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'upload_picture' && isset($_FILES['profile_picture'])) {
        if (!$column_exists) {
            $error = "❌ Profile picture feature not available. Please contact administrator to enable this feature.";
        } else {
        $file = $_FILES['profile_picture'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'sbo_' . $_SESSION['sbo_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Remove old profile picture if exists
                    if (!empty($sbo_user['profile_picture']) && file_exists('../' . $sbo_user['profile_picture'])) {
                        unlink('../' . $sbo_user['profile_picture']);
                    }
                    
                    // Update database
                    $picture_path = 'uploads/profile_pictures/sbo/' . $new_filename;
                    $update_stmt = mysqli_prepare($conn, "UPDATE sbo_users SET profile_picture = ? WHERE id = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $picture_path, $_SESSION['sbo_id']);
                    mysqli_stmt_execute($update_stmt);
                    
                    $sbo_user['profile_picture'] = $picture_path;
                    $message = "✅ Profile picture updated successfully!";
                } else {
                    $error = "❌ Failed to upload file.";
                }
            } else {
                $error = "❌ Invalid file type or size. Please upload JPG, PNG, or GIF under 5MB.";
            }
        } else {
            $error = "❌ File upload error.";
        }
        }
    } elseif ($action === 'remove_picture') {
        if (!$column_exists) {
            $error = "❌ Profile picture feature not available. Please contact administrator to enable this feature.";
        } else {
            if (!empty($sbo_user['profile_picture']) && file_exists('../' . $sbo_user['profile_picture'])) {
                unlink('../' . $sbo_user['profile_picture']);
            }

            // Update database
            $update_stmt = mysqli_prepare($conn, "UPDATE sbo_users SET profile_picture = NULL WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "i", $_SESSION['sbo_id']);
            mysqli_stmt_execute($update_stmt);

            $sbo_user['profile_picture'] = null;
            $message = "✅ Profile picture removed successfully!";
        }
    } elseif ($action === 'setup_profile_pictures') {
        // Add profile_picture column to sbo_users table
        $add_column_query = "ALTER TABLE sbo_users ADD COLUMN profile_picture VARCHAR(255) NULL";
        if (mysqli_query($conn, $add_column_query)) {
            $column_exists = true;
            $message = "✅ Profile picture feature enabled successfully! You can now upload your profile picture.";
        } else {
            $error = "❌ Failed to enable profile picture feature: " . mysqli_error($conn);
        }
    }
}

// Get current profile picture
$profile_picture = ($column_exists && isset($sbo_user['profile_picture'])) ? $sbo_user['profile_picture'] : null;
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
    <title>SBO Profile - ADLOR</title>
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
            background: none;
            color: var(--gray-900);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .profile-picture-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            margin-bottom: 1rem;
        }
        
        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 1rem auto;
            border: 4px solid var(--primary-color);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }
        
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 0.75rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: var(--gray-900);
            font-size: 1rem;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('sbo', 'profile', $_SESSION['sbo_name']); ?>
    
    <!-- SBO Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">👤 SBO Profile</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage your SBO profile and information
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
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
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Profile Picture Section -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; text-align: center;">📸 Profile Picture</h3>
                    
                    <div class="profile-picture-container">
                        <?php if ($profile_picture && file_exists('../' . $profile_picture)): ?>
                            <img src="../<?= $profile_picture ?>" alt="SBO Profile" class="profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder">
                                👥
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($column_exists): ?>
                        <!-- Upload New Picture -->
                        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 1rem;">
                            <input type="hidden" name="action" value="upload_picture">
                            <div class="file-upload-area">
                                <div style="font-size: 2rem; margin-bottom: 1rem;">📷</div>
                                <input type="file" name="profile_picture" accept="image/*" required style="margin-bottom: 1rem;">
                                <div style="font-size: 0.875rem; color: var(--gray-600);">
                                    JPG, PNG, or GIF • Max 5MB
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                📸 Upload New Picture
                            </button>
                        </form>

                        <!-- Remove Picture -->
                        <?php if ($profile_picture): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="remove_picture">
                                <button type="submit" class="btn btn-outline" style="width: 100%;" onclick="return confirm('Remove profile picture?')">
                                    🗑️ Remove Picture
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Setup Profile Pictures -->
                        <div style="text-align: center; padding: 2rem; background: #fef3c7; border-radius: 1rem; border: 2px solid #f59e0b;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">⚠️</div>
                            <h4 style="margin: 0 0 1rem 0; color: #92400e;">Profile Pictures Not Available</h4>
                            <p style="margin: 0 0 1.5rem 0; color: #92400e; font-size: 0.875rem;">
                                The profile picture feature needs to be set up. Click below to enable it.
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="setup_profile_pictures">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    🔧 Enable Profile Pictures
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📋 Profile Information</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">SBO ID</div>
                            <div class="info-value"><?= htmlspecialchars($sbo_user['id']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= htmlspecialchars($sbo_user['full_name']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($sbo_user['email']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Position</div>
                            <div class="info-value"><?= htmlspecialchars($sbo_user['position']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Account Created</div>
                            <div class="info-value"><?= date('M j, Y', strtotime($sbo_user['created_at'])) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value" style="color: #10b981; font-weight: 600;">
                                <?= $sbo_user['is_active'] ? '✅ Active' : '❌ Inactive' ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--gray-200);">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">⚡ Quick Actions</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="settings.php" class="btn btn-primary" style="text-decoration: none;">
                                ⚙️ Edit Profile
                            </a>
                            <a href="create_event.php" class="btn btn-secondary" style="text-decoration: none;">
                                📅 Create Event
                            </a>
                            <a href="dashboard.php" class="btn btn-outline" style="text-decoration: none;">
                                📈 Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SBO Statistics -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📊 My SBO Statistics</h3>
                
                <?php
                // Get SBO statistics (since events table doesn't track created_by, show general stats)
                $events_created = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM events"))['count'];
                $total_attendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance"))['count'];
                $total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students"))['count'];
                $recent_events = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM events WHERE start_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['count'];
                ?>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Total Events</div>
                        <div class="info-value" style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?= $events_created ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Total Attendance Records</div>
                        <div class="info-value" style="font-size: 1.5rem; font-weight: 700; color: #10b981;"><?= $total_attendance ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Registered Students</div>
                        <div class="info-value" style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;"><?= $total_students ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Recent Events (30 days)</div>
                        <div class="info-value" style="font-size: 1.5rem; font-weight: 700; color: #8b5cf6;"><?= $recent_events ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Current Session</div>
                        <div class="info-value"><?= date('M j, Y g:i A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Session Status</div>
                        <div class="info-value" style="color: #10b981; font-weight: 600;">✅ Active</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Preview uploaded image
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-picture, .profile-picture-placeholder');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-picture';
                        img.alt = 'Profile Preview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
