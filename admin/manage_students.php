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

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

// Handle student actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_student') {
        $student_id = $_POST['student_id'];
        
        // Delete from both tables
        $stmt1 = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt1, "s", $student_id);
        
        $stmt2 = mysqli_prepare($conn, "DELETE FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt2, "s", $student_id);
        
        if (mysqli_stmt_execute($stmt1) && mysqli_stmt_execute($stmt2)) {
            $message = "✅ Student deleted successfully!";
        } else {
            $error = "❌ Error deleting student: " . mysqli_error($conn);
        }
    }
    
    if ($action === 'update_student') {
        $student_id = $_POST['student_id'];
        $full_name = trim($_POST['full_name']);
        $course = trim($_POST['course']);
        $section = trim($_POST['section']);
        
        // Update both tables
        $stmt1 = mysqli_prepare($conn, "UPDATE students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt1, "ssss", $full_name, $course, $section, $student_id);
        
        $stmt2 = mysqli_prepare($conn, "UPDATE official_students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt2, "ssss", $full_name, $course, $section, $student_id);
        
        if (mysqli_stmt_execute($stmt1) && mysqli_stmt_execute($stmt2)) {
            $message = "✅ Student updated successfully!";
        } else {
            $error = "❌ Error updating student: " . mysqli_error($conn);
        }
    }

    if ($action === 'add_student') {
        $student_id = trim($_POST['student_id']);
        $full_name = trim($_POST['full_name']);
        $course = trim($_POST['course']);
        $section = trim($_POST['section']);
        $create_login = isset($_POST['create_login']);
        $password = $create_login ? trim($_POST['password']) : '';

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
                $error = "❌ Student ID already exists.";
            } else {
                // Insert into official_students table
                $insert_stmt = mysqli_prepare($conn, "INSERT INTO official_students (student_id, full_name, course, section) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($insert_stmt, "ssss", $student_id, $full_name, $course, $section);

                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_msg = "✅ Student added successfully!";

                    // Create login account if requested
                    if ($create_login) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $login_stmt = mysqli_prepare($conn, "INSERT INTO students (student_id, full_name, course, section, password) VALUES (?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($login_stmt, "sssss", $student_id, $full_name, $course, $section, $hashed_password);

                        if (mysqli_stmt_execute($login_stmt)) {
                            $success_msg .= " Login account created.";
                        } else {
                            $success_msg .= " Warning: Failed to create login account.";
                        }
                    }

                    $message = $success_msg;
                } else {
                    $error = "❌ Failed to add student. Please try again.";
                }
            }
        }
    }
}

// Build student query with filters
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($filter_course)) {
    $where_conditions[] = "course = ?";
    $params[] = $filter_course;
    $param_types .= "s";
}

if (!empty($filter_section)) {
    $where_conditions[] = "section = ?";
    $params[] = $filter_section;
    $param_types .= "s";
}

if (!empty($filter_year)) {
    // Filter by calculated year level (from section or course)
    $where_conditions[] = "(
        os.section LIKE ? OR os.course LIKE ?
    )";
    $year_pattern = "%{$filter_year}%";
    $params[] = $year_pattern;
    $params[] = $year_pattern;
    $param_types .= "ss";
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "ss";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$student_query = "
    SELECT
        os.*,
        CASE
            -- Extract year level from section name - check highest numbers first
            WHEN os.section LIKE '%10%' THEN 10
            WHEN os.section LIKE '%9%' THEN 9
            WHEN os.section LIKE '%8%' THEN 8
            WHEN os.section LIKE '%7%' THEN 7
            WHEN os.section LIKE '%6%' THEN 6
            WHEN os.section LIKE '%5%' THEN 5
            WHEN os.section LIKE '%4%' THEN 4
            WHEN os.section LIKE '%3%' THEN 3
            WHEN os.section LIKE '%2%' THEN 2
            WHEN os.section LIKE '%1%' THEN 1
            -- Fallback to course name if section has no numbers
            WHEN os.course LIKE '%10%' THEN 10
            WHEN os.course LIKE '%9%' THEN 9
            WHEN os.course LIKE '%8%' THEN 8
            WHEN os.course LIKE '%7%' THEN 7
            WHEN os.course LIKE '%6%' THEN 6
            WHEN os.course LIKE '%5%' THEN 5
            WHEN os.course LIKE '%4%' THEN 4
            WHEN os.course LIKE '%3%' THEN 3
            WHEN os.course LIKE '%2%' THEN 2
            WHEN os.course LIKE '%1%' THEN 1
            -- Default to year 1
            ELSE 1
        END as calculated_year_level
    FROM official_students os
    $where_clause
    ORDER BY os.full_name ASC
";
$stmt = mysqli_prepare($conn, $student_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

mysqli_stmt_execute($stmt);
$students_result = mysqli_stmt_get_result($stmt);

// Get filter options
$courses_query = mysqli_query($conn, "SELECT DISTINCT course FROM official_students WHERE course IS NOT NULL AND course != '' ORDER BY course");
$sections_query = mysqli_query($conn, "SELECT DISTINCT section FROM official_students WHERE section IS NOT NULL AND section != '' ORDER BY section");

// Get year levels from actual student data (section-based)
$years_query = mysqli_query($conn, "
    SELECT DISTINCT
        CASE
            -- Extract year level from section name - check highest numbers first
            WHEN section LIKE '%10%' THEN 10
            WHEN section LIKE '%9%' THEN 9
            WHEN section LIKE '%8%' THEN 8
            WHEN section LIKE '%7%' THEN 7
            WHEN section LIKE '%6%' THEN 6
            WHEN section LIKE '%5%' THEN 5
            WHEN section LIKE '%4%' THEN 4
            WHEN section LIKE '%3%' THEN 3
            WHEN section LIKE '%2%' THEN 2
            WHEN section LIKE '%1%' THEN 1
            -- Fallback to course name if section has no numbers
            WHEN course LIKE '%10%' THEN 10
            WHEN course LIKE '%9%' THEN 9
            WHEN course LIKE '%8%' THEN 8
            WHEN course LIKE '%7%' THEN 7
            WHEN course LIKE '%6%' THEN 6
            WHEN course LIKE '%5%' THEN 5
            WHEN course LIKE '%4%' THEN 4
            WHEN course LIKE '%3%' THEN 3
            WHEN course LIKE '%2%' THEN 2
            WHEN course LIKE '%1%' THEN 1
            -- Default to year 1
            ELSE 1
        END as year_level
    FROM official_students
    ORDER BY year_level ASC
");

// Count total students
$total_students = mysqli_num_rows($students_result);
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
    <title>Manage Students - ADLOR Admin</title>
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
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .students-table th,
        .students-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .students-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            position: sticky;
            top: 0;
        }
        
        .students-table tr:hover {
            background: var(--gray-50);
        }
        
        .student-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .student-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #3b82f6; }
        .stat-card.courses { border-left-color: #10b981; }
        .stat-card.sections { border-left-color: #f59e0b; }
        .stat-card.years { border-left-color: #8b5cf6; }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .students-table {
                font-size: 0.875rem;
            }
            
            .students-table th,
            .students-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'students', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">👥 Manage All Students</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                View, filter, and manage student records
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem; max-width: 1400px;">
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem;">👥</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #3b82f6;"><?= $total_students ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Total Students</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card courses">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem;">📚</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= mysqli_num_rows($courses_query) ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Courses</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card sections">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem;">🏫</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?= mysqli_num_rows($sections_query) ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Sections</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card years">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem;">📊</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6;"><?= mysqli_num_rows($years_query) ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Year Levels</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; color: var(--gray-900);">🔍 Filter Students</h3>

                <form method="GET" class="filter-grid">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="course">Course</label>
                        <select id="course" name="course" class="form-select">
                            <option value="">All Courses</option>
                            <?php
                            mysqli_data_seek($courses_query, 0);
                            while ($course = mysqli_fetch_assoc($courses_query)):
                            ?>
                                <option value="<?= htmlspecialchars($course['course']) ?>" <?= $filter_course === $course['course'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="section">Section</label>
                        <select id="section" name="section" class="form-select">
                            <option value="">All Sections</option>
                            <?php
                            mysqli_data_seek($sections_query, 0);
                            while ($section = mysqli_fetch_assoc($sections_query)):
                            ?>
                                <option value="<?= htmlspecialchars($section['section']) ?>" <?= $filter_section === $section['section'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($section['section']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="year">Year Level</label>
                        <select id="year" name="year" class="form-select">
                            <option value="">All Year Levels</option>
                            <?php
                            mysqli_data_seek($years_query, 0);
                            while ($year = mysqli_fetch_assoc($years_query)):
                                if (!empty($year['year_level'])):
                            ?>
                                <option value="<?= htmlspecialchars($year['year_level']) ?>" <?= $filter_year == $year['year_level'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['year_level']) ?>
                                </option>
                            <?php
                                endif;
                            endwhile;
                            ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-input"
                               placeholder="Name or Student ID" value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="manage_students.php" class="btn btn-outline">🔄 Clear</a>
                        <a href="add_student.php" class="btn btn-success">➕ Add Student</a>
                        <a href="edit_student.php" class="btn btn-primary">✏️ Edit by ID</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students Table -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: var(--gray-900);">📋 Student Records</h3>
                    <div style="display: flex; gap: 1rem;">
                        <a href="data_management.php" class="btn btn-outline">📥 Import Students</a>
                        <a href="../database_admin.php" class="btn btn-outline">🗄️ Database Admin</a>
                    </div>
                </div>

                <?php if ($total_students > 0): ?>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $row_number = 1;
                                while ($student = mysqli_fetch_assoc($students_result)):
                                ?>
                                    <tr>
                                        <td>
                                            <span style="background: #f3f4f6; color: #6b7280; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                                                <?= $row_number++ ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($student['profile_picture']) && !empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                                <img src="<?= htmlspecialchars($student['profile_picture']) ?>"
                                                     alt="Student Photo" class="student-photo">
                                            <?php else: ?>
                                                <div class="student-photo-placeholder">
                                                    <?= strtoupper(substr($student['full_name'], 0, 2)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($student['student_id']) ?></strong></td>
                                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                                        <td>
                                            <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">
                                                <?= htmlspecialchars($student['course']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">
                                                <?= htmlspecialchars($student['section']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Use the calculated year level from the query
                                            $year_level = $student['calculated_year_level'] ?? 1;

                                            // Display year level with appropriate styling
                                            $year_colors = [
                                                1 => ['bg' => '#dbeafe', 'text' => '#1e40af'], // Blue for 1st year
                                                2 => ['bg' => '#dcfce7', 'text' => '#166534'], // Green for 2nd year
                                                3 => ['bg' => '#fef3c7', 'text' => '#92400e'], // Yellow for 3rd year
                                                4 => ['bg' => '#fce7f3', 'text' => '#be185d'], // Pink for 4th year
                                                5 => ['bg' => '#e0e7ff', 'text' => '#3730a3'], // Indigo for 5th year
                                                6 => ['bg' => '#f3e8ff', 'text' => '#7c2d12'], // Purple for 6th year
                                                7 => ['bg' => '#ecfdf5', 'text' => '#14532d'], // Emerald for 7th year
                                                8 => ['bg' => '#fef2f2', 'text' => '#991b1b'], // Red for 8th year
                                                9 => ['bg' => '#fffbeb', 'text' => '#92400e'], // Amber for 9th year
                                                10 => ['bg' => '#f0f9ff', 'text' => '#0c4a6e'] // Sky for 10th year
                                            ];

                                            $colors = $year_colors[$year_level] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];

                                            echo '<span style="background: ' . $colors['bg'] . '; color: ' . $colors['text'] . '; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">';
                                            echo htmlspecialchars($year_level);
                                            echo '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button onclick="editStudent('<?= htmlspecialchars($student['student_id']) ?>', '<?= htmlspecialchars($student['full_name']) ?>', '<?= htmlspecialchars($student['course']) ?>', '<?= htmlspecialchars($student['section']) ?>')"
                                                        class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                                                    ✏️ Edit
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this student?')">
                                                    <input type="hidden" name="action" value="delete_student">
                                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                                    <button type="submit" class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem; color: #dc2626; border-color: #dc2626;">
                                                        🗑️ Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray-600);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                        <h4 style="margin: 0 0 1rem 0;">No Students Found</h4>
                        <p style="margin: 0 0 2rem 0;">
                            <?php if (!empty($filter_course) || !empty($filter_section) || !empty($search)): ?>
                                No students match your current filters. Try adjusting your search criteria.
                            <?php else: ?>
                                No students have been added yet. Import students to get started.
                            <?php endif; ?>
                        </p>
                        <a href="data_management.php" class="btn btn-primary">📥 Import Students</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 2rem;">
            <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">🔗 Quick Actions</h4>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="data_management.php" class="btn btn-outline">📊 Data Management</a>
                <a href="manage_academics.php" class="btn btn-outline">🎓 Manage Academics</a>
                <a href="dashboard.php" class="btn btn-outline">📈 Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 1rem; width: 90%; max-width: 500px;">
            <h3 style="margin: 0 0 1.5rem 0;">✏️ Edit Student</h3>

            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_student">
                <input type="hidden" name="student_id" id="editStudentId">

                <div class="form-group">
                    <label class="form-label" for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="full_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editCourse">Course</label>
                    <input type="text" id="editCourse" name="course" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editSection">Section</label>
                    <input type="text" id="editSection" name="section" class="form-input" required>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Save Changes</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="flex: 1;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 1rem; width: 90%; max-width: 500px;">
            <h3 style="margin: 0 0 1.5rem 0;">➕ Add New Student</h3>

            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="add_student">

                <div class="form-group">
                    <label class="form-label" for="addStudentId">Student ID</label>
                    <input type="text" id="addStudentId" name="student_id" class="form-input" placeholder="e.g., 23-11797" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addFullName">Full Name</label>
                    <input type="text" id="addFullName" name="full_name" class="form-input" placeholder="e.g., Juan Dela Cruz" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addCourse">Course</label>
                    <input type="text" id="addCourse" name="course" class="form-input" placeholder="e.g., BSIT" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addSection">Section</label>
                    <input type="text" id="addSection" name="section" class="form-input" placeholder="e.g., IT-3A" required>
                </div>

                <div style="margin: 1rem 0;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="addCreateLogin" name="create_login">
                        <span>Create login account for this student</span>
                    </label>
                </div>

                <div class="form-group" id="addPasswordField" style="display: none;">
                    <label class="form-label" for="addPassword">Password</label>
                    <input type="password" id="addPassword" name="password" class="form-input" placeholder="Enter password">
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">➕ Add Student</button>
                    <button type="button" onclick="closeAddModal()" class="btn btn-outline" style="flex: 1;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store all sections for filtering
        <?php mysqli_data_seek($sections_query, 0); ?>
        const allSections = <?= json_encode(mysqli_fetch_all($sections_query, MYSQLI_ASSOC)) ?>;

        function filterSectionsByCourse() {
            const courseSelect = document.getElementById('course');
            const sectionSelect = document.getElementById('section');
            const selectedCourse = courseSelect.value;

            // Clear current section options except "All Sections"
            sectionSelect.innerHTML = '<option value="">All Sections</option>';

            if (selectedCourse) {
                // Get sections for the selected course from the server
                fetch(`get_sections_by_course.php?course=${encodeURIComponent(selectedCourse)}`)
                    .then(response => response.json())
                    .then(sections => {
                        sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section;
                            option.textContent = section.section;
                            sectionSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching sections:', error);
                        // Fallback: show all sections
                        allSections.forEach(section => {
                            if (section.section) {
                                const option = document.createElement('option');
                                option.value = section.section;
                                option.textContent = section.section;
                                sectionSelect.appendChild(option);
                            }
                        });
                    });
            } else {
                // Show all sections if no course selected
                allSections.forEach(section => {
                    if (section.section) {
                        const option = document.createElement('option');
                        option.value = section.section;
                        option.textContent = section.section;
                        sectionSelect.appendChild(option);
                    }
                });
            }
        }

        // Add event listener to course dropdown
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('course').addEventListener('change', filterSectionsByCourse);
        });

        function editStudent(studentId, fullName, course, section) {
            document.getElementById('editStudentId').value = studentId;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editCourse').value = course;
            document.getElementById('editCourse').readOnly = true; // Make course read-only
            document.getElementById('editSection').value = section;
            document.getElementById('editSection').readOnly = true; // Make section read-only
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            // Clear form
            document.getElementById('addForm').reset();
            document.getElementById('addPasswordField').style.display = 'none';
        }

        // Toggle password field for add modal
        document.getElementById('addCreateLogin').addEventListener('change', function() {
            const passwordField = document.getElementById('addPasswordField');
            const passwordInput = document.getElementById('addPassword');

            if (this.checked) {
                passwordField.style.display = 'block';
                passwordInput.required = true;
            } else {
                passwordField.style.display = 'none';
                passwordInput.required = false;
                passwordInput.value = '';
            }
        });

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
