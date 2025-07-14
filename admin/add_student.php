<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';
require_once '../includes/student_sync.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $create_login = isset($_POST['create_login']);
    $password = $create_login ? trim($_POST['password'] ?? '') : '';

    // Validate required fields
    if (empty($student_id) || empty($full_name) || empty($course) || empty($section)) {
        $error = "❌ All fields are required.";
    } elseif ($create_login && empty($password)) {
        $error = "❌ Password is required when creating login account.";
    } else {
        // Check if student ID already exists
        $check_stmt = mysqli_prepare($conn, "SELECT student_id FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $student_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "❌ Student ID already exists. Use Edit Student to modify existing records.";
        } else {
            // Use comprehensive sync system
            $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'add');

            if ($sync_result['success']) {
                $success_msg = "✅ Student added successfully and synced across all systems!";

                // Create login account if requested
                if ($create_login) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $login_stmt = mysqli_prepare($conn, "INSERT INTO students (student_id, full_name, course, section, password) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($login_stmt, "sssss", $student_id, $full_name, $course, $section, $hashed_password);

                    if (mysqli_stmt_execute($login_stmt)) {
                        // Generate QR code for new student with login
                        regenerateStudentQRCode($student_id, $full_name);
                        $success_msg .= " Login account created and QR code generated.";
                    } else {
                        $success_msg .= " Warning: Failed to create login account.";
                    }
                }

                $message = $success_msg;

                // Clear form
                $student_id = $full_name = $course = $section = $password = "";
                $create_login = false;
            } else {
                $error = "❌ Failed to add student: " . implode(', ', $sync_result['errors']);
            }
        }
    }
}

// Function moved to includes/student_sync.php to avoid redeclaration

// Get courses and sections for dropdowns
$courses_query = mysqli_query($conn, "SELECT DISTINCT course FROM official_students ORDER BY course");
$sections_query = mysqli_query($conn, "SELECT DISTINCT section FROM official_students ORDER BY section");
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
    <title>Add New Student - ADLOR Event Attendance</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
            color: white;
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-2xl);
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .section-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray-500);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--gray-600);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: var(--success-light);
            color: var(--success-dark);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background: var(--error-light);
            color: var(--error-dark);
            border: 1px solid var(--error-color);
        }

        .password-field {
            display: none;
        }

        .password-field.show {
            display: block;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'manage_students', $_SESSION['admin_name'] ?? 'Admin'); ?>

    <div class="admin-header">
        <div class="container">
            <div class="text-center">
                <h1 style="margin: 0; font-size: 2.5rem; font-weight: 800;">➕ Add New Student</h1>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 1.1rem;">
                    Create individual student record
                </p>
            </div>
        </div>
    </div>

    <div class="container" style="max-width: 800px;">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div style="padding: 2rem;">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="student_id">Student ID *</label>
                        <input type="text" 
                               id="student_id" 
                               name="student_id" 
                               class="form-control" 
                               placeholder="e.g., 23-11797"
                               value="<?= htmlspecialchars($student_id ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="e.g., Juan Dela Cruz"
                               value="<?= htmlspecialchars($full_name ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="course">Course *</label>
                        <input type="text" 
                               id="course" 
                               name="course" 
                               class="form-control" 
                               placeholder="e.g., BSIT"
                               value="<?= htmlspecialchars($course ?? '') ?>"
                               list="courses_list"
                               required>
                        <datalist id="courses_list">
                            <?php while ($course_row = mysqli_fetch_assoc($courses_query)): ?>
                                <option value="<?= htmlspecialchars($course_row['course']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="section">Section *</label>
                        <input type="text" 
                               id="section" 
                               name="section" 
                               class="form-control" 
                               placeholder="e.g., IT-3A"
                               value="<?= htmlspecialchars($section ?? '') ?>"
                               list="sections_list"
                               required>
                        <datalist id="sections_list">
                            <?php while ($section_row = mysqli_fetch_assoc($sections_query)): ?>
                                <option value="<?= htmlspecialchars($section_row['section']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" 
                               id="create_login" 
                               name="create_login" 
                               onchange="togglePasswordField()"
                               <?= ($create_login ?? false) ? 'checked' : '' ?>>
                        <label for="create_login">Create login account for this student</label>
                    </div>

                    <div class="form-group password-field" id="password_field">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter password for student login">
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Student
                        </button>
                        <a href="manage_students.php" class="btn btn-secondary">
                            ← Back to Manage Students
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordField() {
            const checkbox = document.getElementById('create_login');
            const passwordField = document.getElementById('password_field');
            const passwordInput = document.getElementById('password');
            
            if (checkbox.checked) {
                passwordField.classList.add('show');
                passwordInput.required = true;
            } else {
                passwordField.classList.remove('show');
                passwordInput.required = false;
                passwordInput.value = '';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePasswordField();
        });
    </script>
</body>
</html>
