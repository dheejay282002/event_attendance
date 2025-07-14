<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$event_filter = $_GET['event_id'] ?? '';
$section_filter = $_GET['section'] ?? '';
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($event_filter) {
    $where_conditions[] = "e.id = ?";
    $params[] = $event_filter;
    $param_types .= 'i';
}

if ($section_filter) {
    $where_conditions[] = "s.section = ?";
    $params[] = $section_filter;
    $param_types .= 's';
}

if ($course_filter) {
    $where_conditions[] = "s.course = ?";
    $params[] = $course_filter;
    $param_types .= 's';
}

if ($status_filter) {
    if ($status_filter === 'present') {
        $where_conditions[] = "a.time_in IS NOT NULL";
    } elseif ($status_filter === 'absent') {
        $where_conditions[] = "a.time_in IS NULL";
    } elseif ($status_filter === 'complete') {
        $where_conditions[] = "a.time_in IS NOT NULL AND a.time_out IS NOT NULL";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get attendance data from official_students table - only show students assigned to events
$attendance_query = "
    SELECT
        s.student_id,
        s.full_name,
        s.course,
        s.section,
        e.title as event_title,
        e.start_datetime,
        a.time_in,
        a.time_out,
        CASE
            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
            WHEN a.time_in IS NOT NULL THEN 'Time In Only'
            ELSE 'Absent'
        END as status
    FROM official_students s
    INNER JOIN events e ON FIND_IN_SET(s.section, e.assigned_sections) > 0
    LEFT JOIN attendance a ON s.student_id = a.student_id AND e.id = a.event_id
    $where_clause
    ORDER BY e.start_datetime DESC, s.section, s.full_name
";

$stmt = mysqli_prepare($conn, $attendance_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

// Get events for filter
$events_query = "SELECT * FROM events ORDER BY start_datetime DESC";
$events_result = mysqli_query($conn, $events_query);

// Get courses for filter
$courses_query = "SELECT DISTINCT course FROM official_students ORDER BY course";
$courses_result = mysqli_query($conn, $courses_query);

// Get sections for filter
$sections_query = "SELECT DISTINCT section FROM official_students ORDER BY section";
$sections_result = mysqli_query($conn, $sections_query);
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
    <title>View Attendance - ADLOR Admin</title>
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
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .attendance-table th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .attendance-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-complete {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-partial {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-absent {
            background: #fecaca;
            color: #991b1b;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .form-control {
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.875rem;
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

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
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

        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'attendance', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <h1 class="page-title">📋 View Attendance</h1>
            <p class="page-subtitle">Monitor and review student attendance records (Admin Access)</p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- Filter Form -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                    <div style="background: #7c3aed; color: white; padding: 0.5rem; border-radius: 0.5rem; font-size: 1.25rem;">🔍</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Filter Attendance Records</h3>
                </div>
                
                <form method="GET" action="">
                    <div class="filter-form">
                        <div class="form-group">
                            <label class="form-label">Event</label>
                            <select name="event_id" class="form-control">
                                <option value="">All Events</option>
                                <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                    <option value="<?= $event['id'] ?>" <?= $event_filter == $event['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['title']) ?> - <?= date('M j, Y', strtotime($event['start_datetime'])) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Course</label>
                            <select name="course" class="form-control">
                                <option value="">All Courses</option>
                                <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                                    <option value="<?= htmlspecialchars($course['course']) ?>" <?= $course_filter == $course['course'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Section</label>
                            <select name="section" class="form-control">
                                <option value="">All Sections</option>
                                <?php while ($section = mysqli_fetch_assoc($sections_result)): ?>
                                    <option value="<?= htmlspecialchars($section['section']) ?>" <?= $section_filter == $section['section'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($section['section']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="present" <?= $status_filter == 'present' ? 'selected' : '' ?>>Present</option>
                                <option value="absent" <?= $status_filter == 'absent' ? 'selected' : '' ?>>Absent</option>
                                <option value="complete" <?= $status_filter == 'complete' ? 'selected' : '' ?>>Complete</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                            <a href="view_attendance.php" class="btn btn-secondary">🔄 Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">📋 Attendance Records</h3>
                    <a href="download_attendance.php" class="btn btn-success">📊 Download Report</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($attendance_result) > 0): ?>
                                <?php while ($record = mysqli_fetch_assoc($attendance_result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['student_id']) ?></td>
                                        <td><?= htmlspecialchars($record['full_name']) ?></td>
                                        <td><?= htmlspecialchars($record['course']) ?></td>
                                        <td><?= htmlspecialchars($record['section']) ?></td>
                                        <td><?= htmlspecialchars($record['event_title']) ?></td>
                                        <td><?= date('M j, Y', strtotime($record['start_datetime'])) ?></td>
                                        <td>
                                            <?php if ($record['time_in']): ?>
                                                <?= date('g:i A', strtotime($record['time_in'])) ?>
                                            <?php else: ?>
                                                <span style="color: var(--gray-500);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['time_out']): ?>
                                                <?= date('g:i A', strtotime($record['time_out'])) ?>
                                            <?php else: ?>
                                                <span style="color: var(--gray-500);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php
                                                echo match($record['status']) {
                                                    'Complete' => 'status-complete',
                                                    'Time In Only' => 'status-partial',
                                                    'Absent' => 'status-absent',
                                                    default => 'status-absent'
                                                };
                                            ?>">
                                                <?= $record['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                                        <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">No Attendance Records Found</div>
                                        <div>Try adjusting your filters or check if events have been created with assigned sections.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 700;">📊 Summary Statistics</h3>

                <?php
                // Calculate statistics
                mysqli_data_seek($attendance_result, 0); // Reset result pointer
                $total_records = 0;
                $present_count = 0;
                $complete_count = 0;
                $absent_count = 0;

                while ($record = mysqli_fetch_assoc($attendance_result)) {
                    $total_records++;
                    if ($record['status'] === 'Complete') {
                        $complete_count++;
                        $present_count++;
                    } elseif ($record['status'] === 'Time In Only') {
                        $present_count++;
                    } else {
                        $absent_count++;
                    }
                }

                $attendance_rate = $total_records > 0 ? round(($present_count / $total_records) * 100, 1) : 0;
                $completion_rate = $total_records > 0 ? round(($complete_count / $total_records) * 100, 1) : 0;
                ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); padding: 1.5rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: #1e40af; margin-bottom: 0.5rem;"><?= $total_records ?></div>
                        <div style="color: #1e40af; font-weight: 600;">Total Records</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 1.5rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: #065f46; margin-bottom: 0.5rem;"><?= $present_count ?></div>
                        <div style="color: #065f46; font-weight: 600;">Present</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 1.5rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: #92400e; margin-bottom: 0.5rem;"><?= $complete_count ?></div>
                        <div style="color: #92400e; font-weight: 600;">Complete</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 1.5rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: #991b1b; margin-bottom: 0.5rem;"><?= $absent_count ?></div>
                        <div style="color: #991b1b; font-weight: 600;">Absent</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); padding: 1.5rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: #3730a3; margin-bottom: 0.5rem;"><?= $attendance_rate ?>%</div>
                        <div style="color: #3730a3; font-weight: 600;">Attendance Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
