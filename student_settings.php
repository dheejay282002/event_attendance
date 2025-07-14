<?php
session_start();
include 'db_connect.php';
include 'includes/navigation.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$message = "";
$error = "";

// Get current student data
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "s", $_SESSION['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $new_name = trim($_POST['full_name']);

        if (!empty($new_name)) {
            $update_stmt = mysqli_prepare($conn, "UPDATE students SET full_name = ? WHERE student_id = ?");
            mysqli_stmt_bind_param($update_stmt, "ss", $new_name, $_SESSION['student_id']);

            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['full_name'] = $new_name;
                $message = "✅ Profile updated successfully!";

                // Refresh student data
                $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
                mysqli_stmt_bind_param($stmt, "s", $_SESSION['student_id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $student = mysqli_fetch_assoc($result);
            } else {
                $error = "❌ Error updating profile: " . mysqli_error($conn);
            }
        } else {
            $error = "❌ Please fill in your name.";
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($current_password) && !empty($new_password) && $new_password === $confirm_password) {
            // Verify current password
            if (password_verify($current_password, $student['password'])) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = mysqli_prepare($conn, "UPDATE students SET password = ? WHERE student_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $_SESSION['student_id']);
                    
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
    <title>Student Settings - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
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
            padding: 5rem 0;
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
            background: linear-gradient(135deg, #10b981, #059669);
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
    <?php renderNavigation('student', 'settings', $_SESSION['full_name']); ?>
    
    <!-- Student Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">⚙️ Student Settings</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage your student account and preferences
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
                            <label class="form-label" for="student_id">Student ID</label>
                            <input type="text" 
                                   id="student_id" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($student['student_id']) ?>"
                                   readonly
                                   style="background: var(--gray-100);">
                            <small style="color: var(--gray-600);">Student ID cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input type="text" 
                                   id="full_name" 
                                   name="full_name" 
                                   class="form-input" 
                                   value="<?= htmlspecialchars($student['full_name']) ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="course">Course</label>
                            <input type="text"
                                   id="course"
                                   name="course"
                                   class="form-input"
                                   value="<?= htmlspecialchars($student['course']) ?>"
                                   readonly
                                   style="background: var(--gray-100);">
                            <small style="color: var(--gray-600);">Course cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="section">Section</label>
                            <input type="text"
                                   id="section"
                                   name="section"
                                   class="form-input"
                                   value="<?= htmlspecialchars($student['section']) ?>"
                                   readonly
                                   style="background: var(--gray-100);">
                            <small style="color: var(--gray-600);">Section cannot be changed</small>
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
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Academic Details</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Student ID:</span>
                                <span><?= htmlspecialchars($student['student_id']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Course:</span>
                                <span><?= htmlspecialchars($student['course']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Section:</span>
                                <span><?= htmlspecialchars($student['section']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                        <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Account Status</h4>
                        <div style="space-y: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Account Created:</span>
                                <span><?= date('M j, Y', strtotime($student['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600;">Login Time:</span>
                                <span><?= date('M j, Y g:i A') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Status:</span>
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
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
