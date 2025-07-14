<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';
require_once '../includes/student_sync.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    header("Location: ../sbo_login.php");
    exit();
}

$message = "";
$error = "";
$student = null;
$search_performed = false;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $search_id = trim($_POST['search_student_id']);
    
    if (!empty($search_id)) {
        // Search for student in official_students table
        $search_stmt = mysqli_prepare($conn, "SELECT * FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($search_stmt, "s", $search_id);
        mysqli_stmt_execute($search_stmt);
        $search_result = mysqli_stmt_get_result($search_stmt);
        
        if (mysqli_num_rows($search_result) > 0) {
            $student = mysqli_fetch_assoc($search_result);
            
            // Check if student has login account
            $login_check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($login_check, "s", $search_id);
            mysqli_stmt_execute($login_check);
            $login_result = mysqli_stmt_get_result($login_check);
            $student['has_login'] = mysqli_num_rows($login_result) > 0;
        } else {
            $error = "❌ Student ID not found.";
        }
        $search_performed = true;
    } else {
        $error = "❌ Please enter a Student ID to search.";
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $course = trim($_POST['course']);
    $section = trim($_POST['section']);

    // Validate required fields
    if (empty($student_id) || empty($full_name) || empty($course) || empty($section)) {
        $error = "❌ All fields are required.";
    } else {
        // Update official_students table
        $update_stmt = mysqli_prepare($conn, "UPDATE official_students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ssss", $full_name, $course, $section, $student_id);
        
        // Use comprehensive sync system
        $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'update');

        if ($sync_result['success']) {
            $message = "✅ Student updated successfully and synced across all systems!";
            if (!empty($sync_result['operations'])) {
                $message .= " Operations: " . implode(', ', $sync_result['operations']);
            }

            // Refresh student data
            $refresh_stmt = mysqli_prepare($conn, "SELECT * FROM official_students WHERE student_id = ?");
            mysqli_stmt_bind_param($refresh_stmt, "s", $student_id);
            mysqli_stmt_execute($refresh_stmt);
            $refresh_result = mysqli_stmt_get_result($refresh_stmt);
            $student = mysqli_fetch_assoc($refresh_result);

            // Check login status again
            $login_check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($login_check, "s", $student_id);
            mysqli_stmt_execute($login_check);
            $login_result = mysqli_stmt_get_result($login_check);
            $student['has_login'] = mysqli_num_rows($login_result) > 0;

        } else {
            $error = "❌ Failed to update student: " . implode(', ', $sync_result['errors']);
        }
    }
}

// Functions moved to includes/student_sync.php to avoid redeclaration

// Get courses and sections for dropdowns
$courses_query = mysqli_query($conn, "SELECT DISTINCT course FROM official_students ORDER BY course");
$sections_query = mysqli_query($conn, "SELECT DISTINCT section FROM official_students ORDER BY section");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - ADLOR Event Attendance</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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
            border-radius: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .section-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, #1e3a8a, #3730a3);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
            box-shadow: 0 8px 25px rgba(30, 58, 138, 0.3);
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

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: var(--warning-dark);
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

        .student-info {
            background: var(--info-light);
            border: 1px solid var(--info-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .edit-form {
            display: none;
        }

        .edit-form.show {
            display: block;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('sbo', 'import_data', $_SESSION['full_name']); ?>

    <div class="admin-header">
        <div class="container">
            <div class="text-center" style="position: relative; z-index: 1;">
                <div style="display: inline-block; background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 1rem; margin-bottom: 1rem;">
                    <div style="font-size: 3rem;">✏️</div>
                </div>
                <h1 style="margin: 0; font-size: 3rem; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Edit Student</h1>
                <p style="margin: 1rem 0 0 0; opacity: 0.9; font-size: 1.2rem; font-weight: 300;">
                    Search and modify student information by Student ID
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

        <!-- Search Form -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🔍</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Search Student</h3>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="search">
                    <div class="form-group">
                        <label class="form-label" for="search_student_id">Student ID</label>
                        <input type="text" 
                               id="search_student_id" 
                               name="search_student_id" 
                               class="form-control" 
                               placeholder="e.g., 23-11797"
                               value="<?= htmlspecialchars($_POST['search_student_id'] ?? '') ?>"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        🔍 Search Student
                    </button>
                </form>
            </div>
        </div>

        <?php if ($student): ?>
        <!-- Student Found - Edit Form -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">✏️</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Edit Student Information</h3>
                </div>

                <div class="student-info">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--info-dark);">📋 Current Information</h4>
                    <p style="margin: 0; color: var(--info-dark); font-size: 0.875rem;">
                        <strong>Login Account:</strong> <?= $student['has_login'] ? '✅ Yes' : '❌ No' ?>
                        <?php if ($student['has_login']): ?>
                            <br><small>QR code will be regenerated after update</small>
                        <?php endif; ?>
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               value="<?= htmlspecialchars($student['full_name']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="course">Course *</label>
                        <input type="text" 
                               id="course" 
                               name="course" 
                               class="form-control" 
                               value="<?= htmlspecialchars($student['course']) ?>"
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
                               value="<?= htmlspecialchars($student['section']) ?>"
                               list="sections_list"
                               required>
                        <datalist id="sections_list">
                            <?php while ($section_row = mysqli_fetch_assoc($sections_query)): ?>
                                <option value="<?= htmlspecialchars($section_row['section']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-warning">
                            💾 Update Student
                        </button>
                        <a href="import_data.php" class="btn btn-secondary">
                            ← Back to Import Data
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php elseif ($search_performed): ?>
        <!-- No Student Found -->
        <div class="admin-card">
            <div style="padding: 2rem; text-align: center;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
                <h3 style="color: var(--error-color); margin-bottom: 1rem;">Student Not Found</h3>
                <p style="color: var(--gray-600); margin-bottom: 2rem;">
                    The Student ID you searched for does not exist in the system.
                </p>
                <a href="add_student.php" class="btn btn-primary">
                    ➕ Add New Student Instead
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
