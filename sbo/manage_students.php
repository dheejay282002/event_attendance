<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';
require_once '../includes/student_sync.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause for filtering
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($filter_course)) {
    $where_conditions[] = "os.course = ?";
    $params[] = $filter_course;
    $param_types .= "s";
}

if (!empty($filter_section)) {
    $where_conditions[] = "os.section = ?";
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
    $where_conditions[] = "(os.full_name LIKE ? OR os.student_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
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

// Get statistics
$total_courses = mysqli_num_rows($courses_query);
$total_sections = mysqli_num_rows($sections_query);
$total_years = mysqli_num_rows($years_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - ADLOR Event Attendance</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
        }

        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
        }

        .filter-section {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-xl);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-lg);
        }

        .students-table th,
        .students-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .students-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-800);
        }

        .students-table tr:hover {
            background: var(--gray-50);
        }

        .year-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .year-1 { background: #dbeafe; color: #1e40af; }
        .year-2 { background: #dcfce7; color: #166534; }
        .year-3 { background: #fef3c7; color: #92400e; }
        .year-4 { background: #fce7f3; color: #be185d; }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'manage_students', $_SESSION['sbo_name']); ?>

    <div class="page-header">
        <div class="container">
            <div class="page-title">👥 Manage Students</div>
            <div class="page-subtitle">View and manage student records with section-based year levels</div>
        </div>
    </div>

    <div class="container-lg">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 2rem;">👥</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-color);"><?= $total_students ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Total Students</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 2rem;">📚</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $total_courses ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Courses</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 2rem;">🏫</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?= $total_sections ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Sections</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 2rem;">📊</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6;"><?= $total_years ?></div>
                        <div style="color: var(--gray-600); font-weight: 600;">Year Levels</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h3 style="margin: 0 0 var(--spacing-lg) 0;">🔍 Filter Students</h3>
            
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="course">Course</label>
                    <select id="course" name="course" class="form-select">
                        <option value="">All Courses</option>
                        <?php while ($course = mysqli_fetch_assoc($courses_query)): ?>
                            <option value="<?= htmlspecialchars($course['course']) ?>" <?= $filter_course === $course['course'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="section">Section</label>
                    <select id="section" name="section" class="form-select">
                        <option value="">All Sections</option>
                        <?php while ($section = mysqli_fetch_assoc($sections_query)): ?>
                            <option value="<?= htmlspecialchars($section['section']) ?>" <?= $filter_section === $section['section'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['section']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
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

                <div class="col-md-3">
                    <label class="form-label" for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-input" placeholder="Name or Student ID" value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-12">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="manage_students.php" class="btn btn-outline">🔄 Clear</a>
                        <a href="add_student.php" class="btn btn-success">➕ Add Student</a>
                        <a href="edit_student.php" class="btn btn-primary">✏️ Edit by ID</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0;">📋 Student Records</h3>
                    <div style="display: flex; gap: 1rem;">
                        <a href="import_data.php" class="btn btn-outline" style="background: var(--primary-color); color: white; border-color: var(--primary-color); flex-shrink: 0; white-space: nowrap;">📥 Import Students</a>
                    </div>
                </div>

                <?php if ($total_students > 0): ?>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Year Level</th>
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
                                            <span style="font-family: monospace; font-weight: 600;">
                                                <?= htmlspecialchars($student['student_id']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($student['full_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span style="background: #e0f2fe; color: #0277bd; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">
                                                <?= htmlspecialchars($student['course']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: #fff3e0; color: #ef6c00; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">
                                                <?= htmlspecialchars($student['section']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Use the calculated year level from the query
                                            $year_level = $student['calculated_year_level'] ?? 1;
                                            
                                            echo '<span class="year-badge year-' . $year_level . '">';
                                            echo htmlspecialchars($year_level);
                                            echo '</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                        <h4>No Students Found</h4>
                        <p class="text-muted">
                            <?php if (!empty($filter_course) || !empty($filter_section) || !empty($search)): ?>
                                Try adjusting your filters or search terms.
                            <?php else: ?>
                                Start by importing students or adding them individually.
                            <?php endif; ?>
                        </p>
                        <div class="mt-3">
                            <a href="import_data.php" class="btn btn-primary">📥 Import Students</a>
                            <a href="add_student.php" class="btn btn-success">➕ Add Student</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
            const courseSelect = document.getElementById('course');
            if (courseSelect) {
                courseSelect.addEventListener('change', filterSectionsByCourse);
            }
        });
    </script>
</body>
</html>
