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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_sbo':
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $full_name = trim($_POST['full_name']);
                $position = trim($_POST['position']);
                
                // Validate inputs
                if (empty($email) || empty($password) || empty($full_name) || empty($position)) {
                    $error = "All fields are required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                } else {
                    // Check if email already exists
                    $check_query = mysqli_prepare($conn, "SELECT id FROM sbo_users WHERE email = ?");
                    mysqli_stmt_bind_param($check_query, "s", $email);
                    mysqli_stmt_execute($check_query);
                    $check_result = mysqli_stmt_get_result($check_query);
                    
                    if (mysqli_num_rows($check_result) > 0) {
                        $error = "Email already exists.";
                    } else {
                        // Hash password and insert new SBO user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $insert_query = mysqli_prepare($conn, "INSERT INTO sbo_users (email, password, full_name, position, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                        mysqli_stmt_bind_param($insert_query, "ssss", $email, $hashed_password, $full_name, $position);
                        
                        if (mysqli_stmt_execute($insert_query)) {
                            $message = "SBO user added successfully!";
                        } else {
                            $error = "Failed to add SBO user.";
                        }
                    }
                }
                break;
                
            case 'delete_sbo':
                $sbo_id = (int)$_POST['sbo_id'];
                
                // Soft delete (set is_active to 0)
                $delete_query = mysqli_prepare($conn, "UPDATE sbo_users SET is_active = 0 WHERE id = ?");
                mysqli_stmt_bind_param($delete_query, "i", $sbo_id);
                
                if (mysqli_stmt_execute($delete_query)) {
                    $message = "SBO user deleted successfully!";
                } else {
                    $error = "Failed to delete SBO user.";
                }
                break;
                
            case 'activate_sbo':
                $sbo_id = (int)$_POST['sbo_id'];
                
                // Reactivate user
                $activate_query = mysqli_prepare($conn, "UPDATE sbo_users SET is_active = 1 WHERE id = ?");
                mysqli_stmt_bind_param($activate_query, "i", $sbo_id);
                
                if (mysqli_stmt_execute($activate_query)) {
                    $message = "SBO user reactivated successfully!";
                } else {
                    $error = "Failed to reactivate SBO user.";
                }
                break;
        }
    }
}

// Get all SBO users (active and inactive)
$sbo_query = "SELECT * FROM sbo_users ORDER BY is_active DESC, created_at DESC";
$sbo_result = mysqli_query($conn, $sbo_query);

// Get SBO statistics
$active_sbo_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM sbo_users WHERE is_active = 1");
$active_count = mysqli_fetch_assoc($active_sbo_count)['count'];

$total_sbo_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM sbo_users");
$total_count = mysqli_fetch_assoc($total_sbo_count)['count'];
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
    <title>Manage SBO Users - ADLOR Admin</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .add-form {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .sbo-table {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 600;
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr 1fr 1.5fr;
            gap: 1rem;
        }

        .table-row {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr 1fr 1.5fr;
            gap: 1rem;
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: var(--gray-50);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }

        .status-active {
            background: var(--success-light);
            color: var(--success-dark);
        }

        .status-inactive {
            background: var(--error-light);
            color: var(--error-dark);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 0.5rem;
        }

        @media (max-width: 768px) {
            .table-header, .table-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('admin', 'sbo', $_SESSION['admin_name']); ?>
    
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">👥 Manage SBO Users</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Add, remove, and manage Student Body Organization user accounts
            </p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $active_count ?></div>
                <div class="stat-label">Active SBO Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_count ?></div>
                <div class="stat-label">Total SBO Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_count - $active_count ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>

        <!-- Add New SBO User Form -->
        <div class="add-form">
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 1.5rem; border-radius: 1rem 1rem 0 0; margin: -2rem -2rem 2rem -2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="font-size: 2rem;">➕</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Add New SBO Officer</h3>
                        <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Create a new Student Body Organization user account</p>
                    </div>
                </div>
            </div>

            <form method="POST" style="background: var(--gray-50); padding: 2rem; border-radius: 1rem; margin-bottom: 1rem;">
                <input type="hidden" name="action" value="add_sbo">

                <!-- Personal Information Section -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 1rem 0; color: var(--gray-700); font-size: 1.125rem; display: flex; align-items: center; gap: 0.5rem;">
                        👤 Personal Information
                    </h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name" style="font-weight: 600; color: var(--gray-700);">
                                <span style="margin-right: 0.5rem;">👤</span>Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required
                                   placeholder="Enter officer's full name"
                                   style="padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: 0.75rem; transition: all 0.3s ease;">
                        </div>
                        <div class="form-group">
                            <label for="position" style="font-weight: 600; color: var(--gray-700);">
                                <span style="margin-right: 0.5rem;">🏆</span>Position
                            </label>
                            <select id="position" name="position" class="form-control" required
                                    style="padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: 0.75rem; transition: all 0.3s ease;">
                                <option value="">Select Officer Position</option>
                                <option value="President">🏆 President</option>
                                <option value="Vice President">🥈 Vice President</option>
                                <option value="Secretary">📝 Secretary</option>
                                <option value="Treasurer">💰 Treasurer</option>
                                <option value="Events Coordinator">🎉 Events Coordinator</option>
                                <option value="Public Relations Officer">📢 Public Relations Officer</option>
                                <option value="Academic Affairs Officer">📚 Academic Affairs Officer</option>
                                <option value="Sports Coordinator">⚽ Sports Coordinator</option>
                                <option value="Cultural Affairs Officer">🎭 Cultural Affairs Officer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 1rem 0; color: var(--gray-700); font-size: 1.125rem; display: flex; align-items: center; gap: 0.5rem;">
                        🔐 Account Information
                    </h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" style="font-weight: 600; color: var(--gray-700);">
                                <span style="margin-right: 0.5rem;">📧</span>Email Address
                            </label>
                            <input type="email" id="email" name="email" class="form-control" required
                                   placeholder="officer.position@school.edu"
                                   style="padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: 0.75rem; transition: all 0.3s ease;">
                            <small style="color: var(--gray-600); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                💡 Recommended format: sbo.position@school.edu
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="password" style="font-weight: 600; color: var(--gray-700);">
                                <span style="margin-right: 0.5rem;">🔒</span>Password
                            </label>
                            <div style="position: relative;">
                                <input type="password" id="password" name="password" class="form-control" required
                                       placeholder="Enter secure password" minlength="8"
                                       style="padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: 0.75rem; transition: all 0.3s ease; padding-right: 3rem;">
                                <button type="button" onclick="togglePassword()"
                                        style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-500); cursor: pointer; font-size: 1.125rem;">
                                    👁️
                                </button>
                            </div>
                            <small style="color: var(--gray-600); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                🔒 Minimum 8 characters required
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div style="text-align: center; padding-top: 1rem; border-top: 1px solid var(--gray-300);">
                    <button type="submit" class="btn btn-primary"
                            style="padding: 1rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 0.75rem; background: linear-gradient(135deg, var(--success-color), #059669); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;">
                        ➕ Create SBO Officer Account
                    </button>
                </div>
            </form>
        </div>

        <!-- SBO Users Table -->
        <div class="sbo-table">
            <div class="table-header">
                <div>Email</div>
                <div>Full Name</div>
                <div>Position</div>
                <div>Status</div>
                <div>Created</div>
                <div>Actions</div>
            </div>

            <?php if (mysqli_num_rows($sbo_result) > 0): ?>
                <?php while ($sbo = mysqli_fetch_assoc($sbo_result)): ?>
                    <div class="table-row">
                        <div>
                            <strong><?= htmlspecialchars($sbo['email']) ?></strong>
                        </div>
                        <div><?= htmlspecialchars($sbo['full_name']) ?></div>
                        <div><?= htmlspecialchars($sbo['position']) ?></div>
                        <div>
                            <span class="status-badge <?= $sbo['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $sbo['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div><?= date('M j, Y', strtotime($sbo['created_at'])) ?></div>
                        <div class="action-buttons">
                            <?php if ($sbo['is_active']): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this SBO user?')">
                                    <input type="hidden" name="action" value="delete_sbo">
                                    <input type="hidden" name="sbo_id" value="<?= $sbo['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">
                                        🗑️ Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="activate_sbo">
                                    <input type="hidden" name="sbo_id" value="<?= $sbo['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-small">
                                        ✅ Reactivate
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 3rem; text-align: center; color: var(--gray-500);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h3>No SBO Users Found</h3>
                    <p>Add your first SBO user using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleBtn = event.target;

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Form input focus effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary-color)';
                    this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                });

                input.addEventListener('blur', function() {
                    this.style.borderColor = 'var(--gray-300)';
                    this.style.boxShadow = 'none';
                });
            });

            // Add fade-in animation to table rows
            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Form validation feedback
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = 'var(--error-color)';
                            field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                            isValid = false;
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            }
        });
    </script>
</body>
</html>
