<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';
require_once '../includes/system_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = intval($_GET['id'] ?? 0);

if ($event_id <= 0) {
    header("Location: manage_events.php");
    exit;
}

// Get event details
$event_query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $event_query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$event_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($event_result) === 0) {
    header("Location: manage_events.php");
    exit;
}

$event = mysqli_fetch_assoc($event_result);

// Get attendance statistics
$attendance_stats_query = "SELECT COUNT(*) as total_attendance FROM attendance WHERE event_id = ?";
$stats_stmt = mysqli_prepare($conn, $attendance_stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $event_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$attendance_stats = mysqli_fetch_assoc($stats_result);

// Get recent attendance records
$recent_attendance_query = "SELECT a.*, s.full_name, s.course, s.section 
                           FROM attendance a 
                           JOIN official_students s ON a.student_id = s.student_id 
                           WHERE a.event_id = ? 
                           ORDER BY a.attendance_time DESC 
                           LIMIT 10";
$recent_stmt = mysqli_prepare($conn, $recent_attendance_query);
mysqli_stmt_bind_param($recent_stmt, "i", $event_id);
mysqli_stmt_execute($recent_stmt);
$recent_attendance = mysqli_stmt_get_result($recent_stmt);

// Determine event status
$now = time();
$start_time = strtotime($event['start_datetime']);
$end_time = strtotime($event['end_datetime']);

if ($now < $start_time) {
    $status = 'Upcoming';
    $status_class = 'status-upcoming';
    $status_icon = '⏳';
} elseif ($now >= $start_time && $now <= $end_time) {
    $status = 'Ongoing';
    $status_class = 'status-ongoing';
    $status_icon = '🟢';
} else {
    $status = 'Completed';
    $status_class = 'status-completed';
    $status_icon = '✅';
}

// Parse assigned sections
$assigned_sections = !empty($event['assigned_sections']) ? explode(',', $event['assigned_sections']) : [];

// Get system settings
$system_name = getSystemName($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
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
            margin-bottom: 2rem;
        }

        .event-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .event-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-ongoing {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #f3f4f6;
            color: #374151;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 0.5rem;
            border: 1px solid var(--gray-200);
        }

        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .sections-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .section-tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .attendance-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .attendance-table tr:hover {
            background: var(--gray-50);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-panel-body">
    <?php renderNavigation('admin', 'events', $_SESSION['admin_name']); ?>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: white;">👁️ Event Details</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0; color: white;">
                View event information and attendance data
            </p>
        </div>
    </div>

    <div class="container" style="margin-bottom: 3rem;">
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="manage_events.php" class="btn btn-outline">← Back to Events</a>
            <a href="edit_event.php?id=<?= $event_id ?>" class="btn btn-primary">✏️ Edit Event</a>
            <a href="../scan_qr.php?event_id=<?= $event_id ?>" class="btn btn-outline">📱 QR Scanner</a>
        </div>

        <!-- Event Details -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="event-header">
                    <div class="event-icon">📅</div>
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: 700;">
                            <?= htmlspecialchars($event['title']) ?>
                        </h2>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                            <span class="status-badge <?= $status_class ?>">
                                <?= $status_icon ?> <?= $status ?>
                            </span>
                            <span style="color: var(--gray-600); font-size: 0.875rem;">
                                Event ID: #<?= $event_id ?>
                            </span>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <p style="margin: 0; color: var(--gray-600); font-size: 1rem;">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Information Grid -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Start Date & Time</div>
                        <div class="info-value">
                            <?= date('l, F j, Y', strtotime($event['start_datetime'])) ?><br>
                            <strong><?= date('g:i A', strtotime($event['start_datetime'])) ?></strong>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">End Date & Time</div>
                        <div class="info-value">
                            <?= date('l, F j, Y', strtotime($event['end_datetime'])) ?><br>
                            <strong><?= date('g:i A', strtotime($event['end_datetime'])) ?></strong>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Total Attendance</div>
                        <div class="info-value" style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                            <?= $attendance_stats['total_attendance'] ?> Students
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Created</div>
                        <div class="info-value">
                            <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Assigned Sections -->
                <div style="margin-bottom: 2rem;">
                    <div class="info-label" style="margin-bottom: 1rem;">Assigned Sections</div>
                    <?php if (!empty($assigned_sections)): ?>
                        <div class="sections-list">
                            <?php foreach ($assigned_sections as $section): ?>
                                <span class="section-tag"><?= htmlspecialchars(trim($section)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="color: var(--gray-600); font-style: italic;">
                            All sections are included in this event
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    📋 Recent Attendance
                </h3>

                <?php if (mysqli_num_rows($recent_attendance) > 0): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course & Section</th>
                                <th>Attendance Time</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($attendance = mysqli_fetch_assoc($recent_attendance)): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($attendance['full_name']) ?></strong><br>
                                        <small style="color: var(--gray-600);"><?= htmlspecialchars($attendance['student_id']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($attendance['course']) ?><br>
                                        <small style="color: var(--gray-600);"><?= htmlspecialchars($attendance['section']) ?></small>
                                    </td>
                                    <td>
                                        <?= date('M j, Y g:i A', strtotime($attendance['attendance_time'])) ?>
                                    </td>
                                    <td>
                                        <span style="background: #f3f4f6; color: #6b7280; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                            <?= htmlspecialchars($attendance['scan_method'] ?? 'QR Code') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="../admin/view_attendance.php?event_id=<?= $event_id ?>" class="btn btn-outline">
                            📊 View All Attendance Records
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-700);">No Attendance Records</h4>
                        <p style="margin: 0;">No students have attended this event yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
