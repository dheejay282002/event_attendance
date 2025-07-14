<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// Handle event deletion - only allow deleting events created by this SBO user
if (isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];

    $delete_stmt = mysqli_prepare($conn, "DELETE FROM events WHERE id = ? AND created_by = ? AND creator_type = 'sbo'");
    mysqli_stmt_bind_param($delete_stmt, "ii", $event_id, $_SESSION['sbo_id']);

    if (mysqli_stmt_execute($delete_stmt)) {
        if (mysqli_affected_rows($conn) > 0) {
            $message = "✅ Event deleted successfully!";
        } else {
            $error = "❌ Event not found or you don't have permission to delete this event.";
        }
    } else {
        $error = "❌ Error deleting event: " . mysqli_error($conn);
    }
}

// Get events created by this SBO user only
$events_stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE created_by = ? AND creator_type = 'sbo' ORDER BY start_datetime DESC");
mysqli_stmt_bind_param($events_stmt, "i", $_SESSION['sbo_id']);
mysqli_stmt_execute($events_stmt);
$events_result = mysqli_stmt_get_result($events_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - <?= htmlspecialchars($system_name) ?> Event Attendance</title>
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

        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-lg);
            table-layout: fixed;
            max-width: 100%;
        }

        .events-table th,
        .events-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .events-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-800);
        }

        .events-table tr:hover {
            background: var(--gray-50);
        }

        .events-table th:nth-child(1) { width: 5%; }   /* ID */
        .events-table th:nth-child(2) { width: 25%; }  /* Event Title */
        .events-table th:nth-child(3) { width: 20%; }  /* Date & Time */
        .events-table th:nth-child(4) { width: 10%; }  /* Status */
        .events-table th:nth-child(5) { width: 20%; }  /* Assigned Sections */
        .events-table th:nth-child(6) { width: 20%; }  /* Actions */

        .fixed-container {
            max-width: 1200px;
            width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        @media (max-width: 1240px) {
            .fixed-container {
                width: 95%;
                max-width: 95%;
            }
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
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'events', $_SESSION['sbo_name']); ?>

    <div class="page-header">
        <div class="container">
            <div class="page-title">📅 Manage Events</div>
            <div class="page-subtitle">View and manage all events with attendance tracking</div>
        </div>
    </div>

    <div class="fixed-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Events Table -->
        <div class="card">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                    <h3 style="margin: 0;">📋 Event Records</h3>
                    <a href="create_event.php" class="btn btn-sm btn-outline" style="background: var(--success-color); color: white; border-color: var(--success-color); flex-shrink: 0; white-space: nowrap; min-width: auto; width: auto;">
                        ➕ Create Event
                    </a>
                </div>

                <?php if (mysqli_num_rows($events_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="events-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Title</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Assigned Sections</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $row_number = 1;
                                while ($event = mysqli_fetch_assoc($events_result)):
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
                                    <tr>
                                        <td>
                                            <span style="background: #f3f4f6; color: #6b7280; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                                                <?= $row_number++ ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                            <?php if (!empty($event['description'])): ?>
                                                <br><small style="color: var(--gray-600);"><?= htmlspecialchars(substr($event['description'], 0, 50)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;">
                                                <strong>Start:</strong> <?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?><br>
                                                <strong>End:</strong> <?= date('M j, Y g:i A', strtotime($event['end_datetime'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $status_class ?>"><?= $status ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($event['assigned_sections'])): ?>
                                                <small><?= htmlspecialchars($event['assigned_sections']) ?></small>
                                            <?php else: ?>
                                                <span style="color: var(--gray-500);">All Sections</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <a href="edit_event.php?id=<?= $event['id'] ?>"
                                                   class="btn btn-sm btn-outline" title="Edit Event">
                                                    ✏️ 
                                                </a>
                                                <a href="view_event.php?id=<?= $event['id'] ?>"
                                                   class="btn btn-sm btn-outline" title="View Event Details">
                                                    👁️                                                 </a>
                                                <form method="POST" style="display: inline;"
                                                      onsubmit="return confirm('Are you sure you want to delete this event?')">
                                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                    <button type="submit" name="delete_event"
                                                            class="btn btn-sm btn-outline" style="color: var(--danger-color);" title="Delete Event">
                                                        🗑️ 
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
                    <div class="text-center py-5">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                        <h4>No Events Created by You</h4>
                        <p style="color: var(--gray-600);">
                            You can only view and manage events that you have created.<br>
                            Start by creating your first event to track attendance.
                        </p>
                        <div class="mt-3">
                            <a href="create_event.php" class="btn btn-success">
                                ➕ Create Your First Event
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
