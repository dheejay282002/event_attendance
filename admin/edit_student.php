<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";
$student = null;
$search_performed = false;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $search_id = trim($_POST['search_student_id'] ?? '');
    
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
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $section = trim($_POST['section'] ?? '');

    // Validate required fields
    if (empty($student_id) || empty($full_name) || empty($course) || empty($section)) {
        $error = "❌ All fields are required.";
    } else {
        // Update official_students table
        $update_stmt = mysqli_prepare($conn, "UPDATE official_students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ssss", $full_name, $course, $section, $student_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Also update students table if login account exists
            $login_check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($login_check, "s", $student_id);
            mysqli_stmt_execute($login_check);
            $login_result = mysqli_stmt_get_result($login_check);
            
            if (mysqli_num_rows($login_result) > 0) {
                // Update login account (preserve password)
                $update_login_stmt = mysqli_prepare($conn, "UPDATE students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
                mysqli_stmt_bind_param($update_login_stmt, "ssss", $full_name, $course, $section, $student_id);
                mysqli_stmt_execute($update_login_stmt);
                
                // Regenerate QR code
                regenerateStudentQRCode($student_id, $full_name);
                $message = "✅ Student updated successfully! QR code regenerated.";
            } else {
                $message = "✅ Student updated successfully!";
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
            $error = "❌ Failed to update student. Please try again.";
        }
    }
}

function regenerateStudentQRCode($student_id, $full_name) {
    // Generate QR code data
    $qr_data = json_encode([
        'student_id' => $student_id,
        'full_name' => $full_name,
        'timestamp' => time()
    ]);
    
    // Create QR code directory if it doesn't exist
    $qr_dir = '../uploads/qr_codes/';
    if (!is_dir($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    // Generate QR code filename
    $qr_filename = 'student_' . $student_id . '.png';
    $qr_filepath = $qr_dir . $qr_filename;
    
    // Use QR code API
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
    
    // Download and save QR code
    $qr_content = file_get_contents($qr_url);
    if ($qr_content !== false) {
        file_put_contents($qr_filepath, $qr_content);
    }
}

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
    <title>Edit Student - ADLOR Event Attendance</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <style>
        .admin-panel-body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .admin-header {
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
            margin-bottom: 2rem;
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
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .student-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-info h4 {
            color: #1e40af;
            margin: 0 0 0.75rem 0;
            font-size: 1.125rem;
            font-weight: 700;
        }

        .student-info p {
            color: #1e40af;
            margin: 0;
            font-size: 0.875rem;
        }

        .not-found-card {
            text-align: center;
            padding: 3rem 2rem;
        }

        .not-found-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .not-found-title {
            color: var(--error-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .not-found-text {
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'manage_students', $_SESSION['admin_name'] ?? 'Admin'); ?>

    <div class="admin-header">
        <div class="container">
            <h1 class="page-title">✏️ Edit Student</h1>
            <p class="page-subtitle">Search and modify student information by Student ID</p>
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

                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">
                            💾 Update Student
                        </button>
                        <a href="manage_students.php" class="btn btn-secondary">
                            ← Back to Manage Students
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php elseif ($search_performed): ?>
        <!-- No Student Found -->
        <div class="admin-card">
            <div class="not-found-card">
                <div class="not-found-icon">❌</div>
                <h3 class="not-found-title">Student Not Found</h3>
                <p class="not-found-text">
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
