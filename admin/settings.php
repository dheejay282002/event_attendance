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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $new_name = trim($_POST['admin_name']);
        $new_email = trim($_POST['admin_email']);
        
        if (!empty($new_name) && !empty($new_email)) {
            $_SESSION['admin_name'] = $new_name;
            $_SESSION['admin_email'] = $new_email;
            $message = "✅ Profile updated successfully!";
        } else {
            $error = "❌ Please fill in all fields.";
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // For demo purposes, we'll just show a message
        // In production, you'd validate against stored passwords
        if (!empty($current_password) && !empty($new_password) && $new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $message = "✅ Password changed successfully! (Demo mode - changes not persisted)";
            } else {
                $error = "❌ Password must be at least 8 characters long.";
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
    <title>Admin Settings - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-panel-body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding-top: 120px; /* Add more space for navigation bar */
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
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .section-icon {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
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
<body class="admin-panel-body">
    <?php renderNavigation('admin', 'settings', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">⚙️ Admin Settings</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage your admin account and system preferences
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
                            <label class="form-label" for="admin_name">Full Name</label>
                            <input type="text" 
                                   id="admin_name" 
                                   name="admin_name" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($_SESSION['admin_name']) ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="admin_email">Email Address</label>
                            <input type="email" 
                                   id="admin_email" 
                                   name="admin_email" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($_SESSION['admin_email']) ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($_SESSION['admin_role']) ?>"
                                   readonly
                                   style="background: var(--gray-100);">
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
        
        <!-- System Information -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">💻</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">System Information</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Session Information</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Admin ID:</span>
                                <span><?= htmlspecialchars($_SESSION['admin_id']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Login Time:</span>
                                <span><?= date('M j, Y g:i A') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Session Status:</span>
                                <span style="color: #10b981; font-weight: 600;">✅ Active</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">System Status</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">PHP Version:</span>
                                <span><?= phpversion() ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Server Time:</span>
                                <span><?= date('M j, Y g:i A') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">System:</span>
                                <span style="color: #10b981; font-weight: 600;">✅ Online</span>
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
