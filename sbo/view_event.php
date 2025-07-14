<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit();
}

$event = null;

// Get event ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_events.php");
    exit();
}

$event_id = $_GET['id'];

// Get event details
$event_stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
mysqli_stmt_bind_param($event_stmt, "i", $event_id);
mysqli_stmt_execute($event_stmt);
$event_result = mysqli_stmt_get_result($event_stmt);
$event = mysqli_fetch_assoc($event_result);

if (!$event) {
    header("Location: manage_events.php");
    exit();
}

// Get attendance statistics for this event
$attendance_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_attendees,
        COUNT(DISTINCT student_id) as unique_students
    FROM attendance 
    WHERE event_id = $event_id
");
$stats = mysqli_fetch_assoc($attendance_stats);

// Get recent attendance records
$recent_attendance = mysqli_query($conn, "
    SELECT a.*, os.full_name, os.course, os.section 
    FROM attendance a 
    LEFT JOIN official_students os ON a.student_id = os.student_id 
    WHERE a.event_id = $event_id 
    ORDER BY a.time_in DESC 
    LIMIT 10
");

// Determine event status
$current_time = date('Y-m-d H:i:s');
$status = '';
$status_class = '';

if ($current_time < $event['start_datetime']) {
    $status = 'Upcoming';
    $status_class = 'status-upcoming';
} elseif ($current_time >= $event['start_datetime'] && $current_time <= $event['end_datetime']) {
    $status = 'Ongoing';
    $status_class = 'status-ongoing';
} else {
    $status = 'Completed';
    $status_class = 'status-completed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - ADLOR Event Attendance</title>
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

        .info-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: var(--spacing-xl);
        }

        .info-card-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .info-card-body {
            padding: var(--spacing-xl);
        }

        .section-icon {
            background: linear-gradient(135deg, var(--info-color), var(--info-dark));
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-upcoming { background: #dbeafe; color: #1e40af; }
        .status-ongoing { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-lg);
        }

        .attendance-table th,
        .attendance-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .attendance-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-800);
        }

        .attendance-table tr:hover {
            background: var(--gray-50);
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'events', $_SESSION['sbo_name']); ?>

    <div class="page-header">
        <div class="container">
            <div class="page-title">👁️ View Event</div>
            <div class="page-subtitle">Event details and attendance information</div>
        </div>
    </div>

    <div class="container-lg">
        <!-- Event Information -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="section-icon">📅</div>
                <div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;"><?= htmlspecialchars($event['title']) ?></h3>
                    <p style="margin: 0.25rem 0 0 0; color: var(--gray-600);">Event Details and Information</p>
                </div>
            </div>
            <div class="info-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-lg);">
                    <div>
                        <h4 style="margin: 0 0 var(--spacing-md) 0; color: var(--gray-800);">Basic Information</h4>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Title:</strong> <?= htmlspecialchars($event['title']) ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Status:</strong> <span class="status-badge <?= $status_class ?>"><?= $status ?></span>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <div style="margin-bottom: var(--spacing-md);">
                                <strong>Description:</strong><br>
                                <span style="color: var(--gray-600);"><?= nl2br(htmlspecialchars($event['description'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 var(--spacing-md) 0; color: var(--gray-800);">Schedule</h4>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Start:</strong> <?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>End:</strong> <?= date('M j, Y g:i A', strtotime($event['end_datetime'])) ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Duration:</strong> 
                            <?php
                            $start = new DateTime($event['start_datetime']);
                            $end = new DateTime($event['end_datetime']);
                            $duration = $start->diff($end);
                            echo $duration->format('%h hours %i minutes');
                            ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Assigned Sections:</strong> 
                            <?php if (!empty($event['assigned_sections'])): ?>
                                <?= htmlspecialchars($event['assigned_sections']) ?>
                            <?php else: ?>
                                <span style="color: var(--gray-500);">All Sections</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="font-size: 2rem;">👥</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-color);"><?= $stats['unique_students'] ?></div>
                        <div style="color: var(--gray-600); font-weight: 500;">Unique Students</div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="font-size: 2rem;">📊</div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--success-color);"><?= $stats['total_attendees'] ?></div>
                        <div style="color: var(--gray-600); font-weight: 500;">Total Check-ins</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 style="margin: 0;">📋 Recent Attendance</h3>
                    <div class="d-flex gap-3">
                        <a href="view_attendance.php?event_id=<?= $event['id'] ?>" class="btn btn-outline">📊 Full Attendance</a>
                        <a href="download_attendance.php?event_id=<?= $event['id'] ?>" class="btn btn-outline">📥 Download</a>
                    </div>
                </div>

                <?php if (mysqli_num_rows($recent_attendance) > 0): ?>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Check-in Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($attendance = mysqli_fetch_assoc($recent_attendance)): ?>
                                    <tr>
                                        <td>
                                            <span style="font-family: monospace; font-weight: 600;">
                                                <?= htmlspecialchars($attendance['student_id']) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($attendance['full_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($attendance['course']) ?></td>
                                        <td><?= htmlspecialchars($attendance['section']) ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($attendance['time_in'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                        <h4>No Attendance Records</h4>
                        <p style="color: var(--gray-600);">No students have checked in for this event yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-200);">
            <div style="display: flex; gap: var(--spacing-md);">
                <a href="manage_events.php" class="btn btn-outline">← Back to Events</a>
                <a href="dashboard.php" class="btn btn-outline">🏠 Dashboard</a>
            </div>
            <div style="display: flex; gap: var(--spacing-md);">
                <a href="edit_event.php?id=<?= $event['id'] ?>" class="btn btn-primary">
                    ✏️ Edit Event
                </a>
            </div>
        </div>
    </div>
</body>
</html>
