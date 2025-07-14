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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        $new_position = trim($_POST['position']);
        
        if (!empty($new_name) && !empty($new_email) && !empty($new_position)) {
            $update_stmt = mysqli_prepare($conn, "UPDATE sbo_users SET full_name = ?, email = ?, position = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "sssi", $new_name, $new_email, $new_position, $_SESSION['sbo_id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['sbo_name'] = $new_name;
                $_SESSION['sbo_position'] = $new_position;
                $message = "✅ Profile updated successfully!";
                
                // Refresh user data
                $stmt = mysqli_prepare($conn, "SELECT * FROM sbo_users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['sbo_id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $sbo_user = mysqli_fetch_assoc($result);
            } else {
                $error = "❌ Error updating profile: " . mysqli_error($conn);
            }
        } else {
            $error = "❌ Please fill in all fields.";
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($current_password) && !empty($new_password) && $new_password === $confirm_password) {
            // Verify current password
            if (password_verify($current_password, $sbo_user['password'])) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = mysqli_prepare($conn, "UPDATE sbo_users SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $_SESSION['sbo_id']);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "✅ Password changed successfully!";
                    } else {
                        $error = "❌ Error changing password: " . mysqli_error($conn);
                    }
                } else {
                    $error = "❌ Password must be at least 8 characters long.";
                }
            } else {
                $error = "❌ Current password is incorrect.";
            }
        } else {
            $error = "❌ Please fill in all fields and ensure new passwords match.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SBO Settings - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
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
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .section-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('sbo', 'settings', $_SESSION['sbo_name']); ?>
    
    <!-- SBO Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">⚙️ SBO Settings</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage your SBO account and preferences
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
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <!-- Profile Settings -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">👤</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Profile Information</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-input"
                                   value="<?= htmlspecialchars($sbo_user['full_name']) ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($sbo_user['email']) ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="position">SBO Position</label>
                            <select id="position" name="position" class="form-select" required>
                                <option value="President" <?= $sbo_user['position'] === 'President' ? 'selected' : '' ?>>President</option>
                                <option value="Vice President" <?= $sbo_user['position'] === 'Vice President' ? 'selected' : '' ?>>Vice President</option>
                                <option value="Secretary" <?= $sbo_user['position'] === 'Secretary' ? 'selected' : '' ?>>Secretary</option>
                                <option value="Treasurer" <?= $sbo_user['position'] === 'Treasurer' ? 'selected' : '' ?>>Treasurer</option>
                                <option value="Events Coordinator" <?= $sbo_user['position'] === 'Events Coordinator' ? 'selected' : '' ?>>Events Coordinator</option>
                                <option value="Public Relations Officer" <?= $sbo_user['position'] === 'Public Relations Officer' ? 'selected' : '' ?>>Public Relations Officer</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            💾 Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Password Settings -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">🔒</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Change Password</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-input" 
                                   placeholder="Enter current password"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-input" 
                                   placeholder="Enter new password"
                                   minlength="8"
                                   required>
                            <small style="color: var(--gray-600);">Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-input" 
                                   placeholder="Confirm new password"
                                   required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            🔐 Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Information -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📋</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Account Information</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Account Details</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">SBO ID:</span>
                                <span><?= htmlspecialchars($sbo_user['id']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Account Created:</span>
                                <span><?= date('M j, Y', strtotime($sbo_user['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Status:</span>
                                <span style="color: #10b981; font-weight: 600;">
                                    <?= $sbo_user['is_active'] ? '✅ Active' : '❌ Inactive' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Session Information</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Current Position:</span>
                                <span><?= htmlspecialchars($sbo_user['position']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Login Time:</span>
                                <span><?= date('M j, Y g:i A') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Session:</span>
                                <span style="color: #10b981; font-weight: 600;">✅ Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
