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

// Create tables if they don't exist
$create_tables = [
    "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(10) NOT NULL UNIQUE,
        course_name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS year_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year_code VARCHAR(10) NOT NULL UNIQUE,
        year_name VARCHAR(50) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_code VARCHAR(20) NOT NULL UNIQUE,
        course_id INT,
        year_id INT,
        section_name VARCHAR(50) NOT NULL,
        max_students INT DEFAULT 50,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (year_id) REFERENCES year_levels(id) ON DELETE CASCADE
    )"
];

foreach ($create_tables as $sql) {
    mysqli_query($conn, $sql);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add_course':
            $course_code = strtoupper(trim($_POST['course_code']));
            $course_name = trim($_POST['course_name']);
            $description = trim($_POST['description']);
            
            // Check if course already exists
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE course_code = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $course_code);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "❌ Course code '$course_code' already exists! Please use a different course code.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO courses (course_code, course_name, description) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $course_code, $course_name, $description);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "✅ Course '$course_code - $course_name' added successfully!";
                } else {
                    $error = "❌ Error adding course: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
            break;
            
        case 'add_year':
            $year_code = trim($_POST['year_code']);

            // Auto-generate year name based on year code
            $year_names = [
                '1' => '1st Year',
                '2' => '2nd Year',
                '3' => '3rd Year',
                '4' => '4th Year',
                '5' => '5th Year',
                '6' => '6th Year'
            ];
            $year_name = $year_names[$year_code] ?? "Year $year_code";

            // Validate year code is a number between 1-6
            if (!is_numeric($year_code) || $year_code < 1 || $year_code > 6) {
                $error = "❌ Year code must be a number between 1 and 6 (representing 1st year to 6th year).";
            } else {
                // Check if year level already exists
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM year_levels WHERE year_code = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $year_code);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($check_result) > 0) {
                    $error = "❌ Year level '$year_code' already exists! Please use a different year level.";
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO year_levels (year_code, year_name) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt, "ss", $year_code, $year_name);

                    if (mysqli_stmt_execute($stmt)) {
                        $message = "✅ Year level '$year_name' added successfully!";
                    } else {
                        $error = "❌ Error adding year level: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($check_stmt);
            }
            break;
            
        case 'add_section':
            $section_code = strtoupper(trim($_POST['section_code']));
            $course_id = $_POST['course_id'];
            $year_id = $_POST['year_id'];
            $section_name = trim($_POST['section_name']);
            $max_students = $_POST['max_students'];
            
            // Check if section already exists for this course and year
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM sections WHERE section_code = ? AND course_id = ? AND year_id = ?");
            mysqli_stmt_bind_param($check_stmt, "sii", $section_code, $course_id, $year_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "❌ Section '$section_code' already exists for this course and year! Please use a different section code.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO sections (section_code, course_id, year_id, section_name, max_students) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "siisi", $section_code, $course_id, $year_id, $section_name, $max_students);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "✅ Section '$section_code - $section_name' added successfully!";
                } else {
                    $error = "❌ Error adding section: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
            break;
            
        case 'delete_course':
            $course_id = $_POST['course_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $course_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "✅ Course deleted successfully!";
            } else {
                $error = "❌ Error deleting course: " . mysqli_error($conn);
            }
            break;
            
        case 'delete_year':
            $year_id = $_POST['year_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM year_levels WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $year_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "✅ Year level deleted successfully!";
            } else {
                $error = "❌ Error deleting year level: " . mysqli_error($conn);
            }
            break;
            
        case 'delete_section':
            $section_id = $_POST['section_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM sections WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $section_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "✅ Section deleted successfully!";
            } else {
                $error = "❌ Error deleting section: " . mysqli_error($conn);
            }
            break;
    }
}

// Get existing data
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_code");
$years = mysqli_query($conn, "SELECT * FROM year_levels ORDER BY year_code");
$sections = mysqli_query($conn, "SELECT s.*, c.course_code, c.course_name, y.year_name
                                FROM sections s
                                LEFT JOIN courses c ON s.course_id = c.id
                                LEFT JOIN year_levels y ON s.year_id = y.id
                                ORDER BY s.section_code");
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
    <title>Manage Academics - ADLOR Admin</title>
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
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .data-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .data-table tr:hover {
            background: var(--gray-50);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'academics', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">🎓 Manage Academics</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Manage courses, year levels, and sections
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
        
        <!-- Add Forms -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <!-- Add Course -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">📚</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Add Course</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_course">
                        
                        <div class="form-group">
                            <label class="form-label" for="course_code">Course Code</label>
                            <input type="text" id="course_code" name="course_code" class="form-input" 
                                   placeholder="e.g., BSIT, BSCS" maxlength="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="course_name">Course Name</label>
                            <input type="text" id="course_name" name="course_name" class="form-input" 
                                   placeholder="e.g., Bachelor of Science in Information Technology" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-input" rows="3" 
                                      placeholder="Course description (optional)"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Course
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Add Academic Year -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">📊</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Add Year Level</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_year">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="year_code">Year Level</label>
                                <select id="year_code" name="year_code" class="form-select" required>
                                    <option value="">-- Select Year Level --</option>
                                    <?php
                                    // Get available year levels from database
                                    $available_years = mysqli_query($conn, "SELECT year_code, year_name FROM year_levels WHERE is_active = 1 ORDER BY year_code ASC");
                                    $used_levels = [];
                                    while ($level = mysqli_fetch_assoc($available_years)) {
                                        $used_levels[] = $level['year_code'];
                                    }

                                    // Show options for levels 1-6, but mark existing ones
                                    for ($i = 1; $i <= 6; $i++) {
                                        if (!in_array($i, $used_levels)) {
                                            echo "<option value='$i'>$i</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                                <strong>Note:</strong> Year levels represent student academic progression (1st year, 2nd year, etc.),
                                not calendar years. This helps organize students by their academic level.
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Year Level
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Add Section -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">🏫</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Add Section</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_section">
                        
                        <div class="form-group">
                            <label class="form-label" for="section_code">Section Code</label>
                            <input type="text" id="section_code" name="section_code" class="form-input" 
                                   placeholder="e.g., IT-3A, CS-2B" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="course_id">Course</label>
                                <select id="course_id" name="course_id" class="form-select" required>
                                    <option value="">Select Course</option>
                                    <?php while ($course = mysqli_fetch_assoc($courses)): ?>
                                        <option value="<?= $course['id'] ?>"><?= $course['course_code'] ?> - <?= $course['course_name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="year_id">Year Level</label>
                                <select id="year_id" name="year_id" class="form-select" required>
                                    <option value="">-- Select Year Level --</option>
                                    <?php while ($year = mysqli_fetch_assoc($years)): ?>
                                        <option value="<?= $year['id'] ?>">
                                            <?= htmlspecialchars($year['year_code']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="section_name">Section Name</label>
                                <input type="text" id="section_name" name="section_name" class="form-input" 
                                       placeholder="e.g., Information Technology 3A" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="max_students">Max Students</label>
                                <input type="number" id="max_students" name="max_students" class="form-input" 
                                       value="50" min="1" max="100">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Section
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Data Tables -->
        <div style="display: grid; gap: 2rem;">
            <!-- Courses Table -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">📚</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Existing Courses</h3>
                    </div>

                    <?php
                    mysqli_data_seek($courses, 0); // Reset pointer
                    if (mysqli_num_rows($courses) > 0):
                    ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($course = mysqli_fetch_assoc($courses)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                                        <td><?= htmlspecialchars($course['description'] ?: 'No description') ?></td>
                                        <td><?= date('M j, Y', strtotime($course['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this course? This will also delete all associated sections.')">
                                                <input type="hidden" name="action" value="delete_course">
                                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-600);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                            <p>No courses added yet. Add your first course above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Academic Years Table -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">📊</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Year Levels</h3>
                    </div>

                    <?php
                    mysqli_data_seek($years, 0); // Reset pointer
                    if (mysqli_num_rows($years) > 0):
                    ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Year Level</th>
                                    <th>Year Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($year = mysqli_fetch_assoc($years)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($year['year_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($year['year_name']) ?></td>
                                        <td>
                                            <?php
                                            $level_descriptions = [
                                                '1' => '1st Year Students',
                                                '2' => '2nd Year Students',
                                                '3' => '3rd Year Students',
                                                '4' => '4th Year Students',
                                                '5' => '5th Year Students',
                                                '6' => '6th Year Students'
                                            ];
                                            echo $level_descriptions[$year['year_code']] ?? 'Year ' . $year['year_code'] . ' Students';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $year['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                                <?= $year['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this academic year? This will also delete all associated sections.')">
                                                <input type="hidden" name="action" value="delete_year">
                                                <input type="hidden" name="year_id" value="<?= $year['id'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-600);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                            <p>No year levels added yet. Add your first year level above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sections Table -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">🏫</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Sections</h3>
                    </div>

                    <?php if (mysqli_num_rows($sections) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Section Code</th>
                                    <th>Section Name</th>
                                    <th>Course</th>
                                    <th>Academic Year</th>
                                    <th>Max Students</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($section = mysqli_fetch_assoc($sections)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($section['section_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($section['section_name']) ?></td>
                                        <td><?= htmlspecialchars($section['course_code'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($section['year_name'] ?: 'N/A') ?></td>
                                        <td><?= $section['max_students'] ?></td>
                                        <td>
                                            <span class="badge <?= $section['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                                <?= $section['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this section?')">
                                                <input type="hidden" name="action" value="delete_section">
                                                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-600);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🏫</div>
                            <p>No sections added yet. Add your first section above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="margin-top: 2rem; text-align: center;">
            <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">🔗 Quick Actions</h4>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="data_management.php" class="btn btn-outline">📊 Data Management</a>
                <a href="upload_students.php" class="btn btn-outline">👥 Upload Students</a>
                <a href="dashboard.php" class="btn btn-outline">📈 Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Show popup alerts for messages and errors
        <?php if (!empty($message)): ?>
            alert('<?= addslashes($message) ?>');
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            alert('<?= addslashes($error) ?>');
        <?php endif; ?>

        // Add confirmation for delete actions
        function confirmDelete(type, name) {
            return confirm(`Are you sure you want to delete this ${type}: "${name}"?\n\nThis action cannot be undone and may affect related data.`);
        }

        // Add form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required]');

            for (let input of inputs) {
                if (!input.value.trim()) {
                    alert(`Please fill in the ${input.previousElementSibling.textContent} field.`);
                    input.focus();
                    return false;
                }
            }
            return true;
        }

        // Add event listeners to forms
        document.addEventListener('DOMContentLoaded', function() {
            // Add course form
            const addCourseForm = document.querySelector('form[action*="add_course"]');
            if (addCourseForm) {
                addCourseForm.addEventListener('submit', function(e) {
                    if (!validateForm(this.id)) {
                        e.preventDefault();
                    }
                });
            }

            // Add year form
            const addYearForm = document.querySelector('form[action*="add_year"]');
            if (addYearForm) {
                addYearForm.addEventListener('submit', function(e) {
                    if (!validateForm(this.id)) {
                        e.preventDefault();
                    }
                });
            }

            // Add section form
            const addSectionForm = document.querySelector('form[action*="add_section"]');
            if (addSectionForm) {
                addSectionForm.addEventListener('submit', function(e) {
                    if (!validateForm(this.id)) {
                        e.preventDefault();
                    }
                });
            }

            // Add delete confirmations
            const deleteButtons = document.querySelectorAll('button[name*="delete"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const form = this.closest('form');
                    const type = this.name.includes('course') ? 'course' :
                                this.name.includes('year') ? 'academic year' : 'section';
                    const name = this.closest('tr').querySelector('td:first-child').textContent;

                    if (!confirmDelete(type, name)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
