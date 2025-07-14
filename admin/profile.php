<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profile_pictures/admin/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'upload_picture' && isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'admin_' . $_SESSION['admin_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Remove old profile picture if exists
                    if (isset($_SESSION['admin_picture']) && file_exists('../' . $_SESSION['admin_picture'])) {
                        unlink('../' . $_SESSION['admin_picture']);
                    }
                    
                    $_SESSION['admin_picture'] = 'uploads/profile_pictures/admin/' . $new_filename;
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
    } elseif ($action === 'remove_picture') {
        if (isset($_SESSION['admin_picture']) && file_exists('../' . $_SESSION['admin_picture'])) {
            unlink('../' . $_SESSION['admin_picture']);
        }
        unset($_SESSION['admin_picture']);
        $message = "✅ Profile picture removed successfully!";
    }
}

// Get current profile picture
$profile_picture = $_SESSION['admin_picture'] ?? 'assets/images/default-admin-avatar.png';
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
    <title>Admin Profile - ADLOR</title>
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
            border: 4px solid #7c3aed;
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
            margin-bottom: 1rem;
        }
        
        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 1rem auto;
            border: 4px solid #7c3aed;
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
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
            border-color: #7c3aed;
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
            border-left: 4px solid #7c3aed;
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

        /* Responsive profile grid */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .admin-header h1 {
                font-size: 2rem !important;
            }

            .profile-picture,
            .profile-picture-placeholder {
                width: 120px !important;
                height: 120px !important;
            }
        }

        @media (max-width: 480px) {
            .profile-grid {
                gap: 0.5rem !important;
            }

            .admin-header {
                padding: 1rem 0 !important;
                margin-bottom: 1rem !important;
            }

            .admin-header h1 {
                font-size: 1.5rem !important;
            }

            .profile-picture,
            .profile-picture-placeholder {
                width: 100px !important;
                height: 100px !important;
            }
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'profile', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">👤 Admin Profile</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage your admin profile and information
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
        
        <div class="profile-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Profile Picture Section -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; text-align: center;">📸 Profile Picture</h3>
                    
                    <div class="profile-picture-container">
                        <?php if (file_exists('../' . $profile_picture)): ?>
                            <img src="../<?= $profile_picture ?>" alt="Admin Profile" class="profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder">
                                ⚙️
                            </div>
                        <?php endif; ?>
                    </div>
                    
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
                    <?php if (isset($_SESSION['admin_picture'])): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove_picture">
                            <button type="submit" class="btn btn-outline" style="width: 100%;" onclick="return confirm('Remove profile picture?')">
                                🗑️ Remove Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📋 Profile Information</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Admin ID</div>
                            <div class="info-value"><?= htmlspecialchars($_SESSION['admin_id']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($_SESSION['admin_email']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Role</div>
                            <div class="info-value"><?= htmlspecialchars($_SESSION['admin_role']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Login Time</div>
                            <div class="info-value"><?= date('M j, Y g:i A') ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Session Status</div>
                            <div class="info-value" style="color: #10b981; font-weight: 600;">✅ Active</div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--gray-200);">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">⚡ Quick Actions</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="settings.php" class="btn btn-primary" style="text-decoration: none;">
                                ⚙️ Edit Profile
                            </a>
                            <a href="data_management.php" class="btn btn-secondary" style="text-decoration: none;">
                                📊 Data Management
                            </a>
                            <a href="dashboard.php" class="btn btn-outline" style="text-decoration: none;">
                                📈 Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">💻 System Information</h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?= phpversion() ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Server Time</div>
                        <div class="info-value"><?= date('M j, Y g:i:s A') ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">System Status</div>
                        <div class="info-value" style="color: #10b981; font-weight: 600;">✅ Online</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Database Status</div>
                        <div class="info-value" style="color: #10b981; font-weight: 600;">✅ Connected</div>
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
