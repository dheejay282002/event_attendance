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

$message = '';
$error = '';

// Handle event deletion
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    
    // Delete attendance records first (foreign key constraint)
    mysqli_query($conn, "DELETE FROM attendance WHERE event_id = $event_id");
    
    // Delete the event
    $delete_query = "DELETE FROM events WHERE id = $event_id";
    if (mysqli_query($conn, $delete_query)) {
        $message = "✅ Event deleted successfully!";
    } else {
        $error = "❌ Error deleting event: " . mysqli_error($conn);
    }
}

// Get system settings
$system_name = getSystemName($conn);
$system_logo = getSystemLogo($conn);

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    switch ($status_filter) {
        case 'upcoming':
            $where_conditions[] = "start_datetime > NOW()";
            break;
        case 'ongoing':
            $where_conditions[] = "start_datetime <= NOW() AND end_datetime >= NOW()";
            break;
        case 'completed':
            $where_conditions[] = "end_datetime < NOW()";
            break;
    }
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get events with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM events $where_clause";
$events_query = "SELECT e.*,
                    CASE
                        WHEN e.creator_type = 'admin' THEN 'Admin'
                        WHEN e.creator_type = 'sbo' THEN COALESCE(s.full_name, 'SBO User')
                        ELSE 'Unknown'
                    END as creator_name,
                    e.creator_type
                FROM events e
                LEFT JOIN sbo_users s ON e.created_by = s.id AND e.creator_type = 'sbo'
                $where_clause
                ORDER BY start_datetime DESC
                LIMIT $per_page OFFSET $offset";

// Execute count query
if (!empty($params)) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
}
$total_events = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_events / $per_page);

// Execute events query
if (!empty($params)) {
    $events_stmt = mysqli_prepare($conn, $events_query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($events_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($events_stmt);
    $events_result = mysqli_stmt_get_result($events_stmt);
} else {
    $events_result = mysqli_query($conn, $events_query);
}

// Get system settings
$system_name = getSystemName($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-panel-body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding-top: 80px; /* Space for fixed navigation */
        }

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

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .admin-panel-body {
                padding-top: 120px; /* More space for mobile navigation */
            }

            .fixed-container {
                padding-left: 1rem;
                padding-right: 1rem;
                overflow-x: hidden; /* Prevent horizontal overflow */
            }

            .filter-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            /* Table container with horizontal scroll */
            .admin-card {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .events-table {
                font-size: 0.75rem;
                min-width: 600px; /* Minimum width to maintain readability */
                table-layout: auto; /* Allow flexible column sizing on mobile */
            }

            .events-table th,
            .events-table td {
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }

            /* Adjust column widths for mobile */
            .events-table th:nth-child(1) { width: 50px; }   /* ID */
            .events-table th:nth-child(2) { width: 150px; }  /* Event Title */
            .events-table th:nth-child(3) { width: 180px; }  /* Date & Time */
            .events-table th:nth-child(4) { width: 80px; }   /* Status */
            .events-table th:nth-child(5) { width: 140px; }  /* Actions */

            /* Allow event title to wrap */
            .events-table td:nth-child(2) {
                white-space: normal;
                word-wrap: break-word;
                max-width: 150px;
            }

            /* Stack action buttons horizontally but smaller on mobile */
            .events-table td:nth-child(5) div {
                flex-direction: row;
                gap: 0.125rem;
                justify-content: center;
            }

            .events-table td:nth-child(5) .btn {
                min-width: auto;
                padding: 0.25rem 0.375rem;
                font-size: 0.625rem;
                border-radius: 0.25rem;
            }

            /* Smaller status badges */
            .status-badge {
                padding: 0.125rem 0.5rem;
                font-size: 0.625rem;
            }
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .admin-panel-body {
                padding-top: 120px; /* More space for mobile navigation */
            }

            .fixed-container {
                padding-left: 1rem;
                padding-right: 1rem;
                overflow-x: hidden; /* Prevent horizontal overflow */
            }

            .filter-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            /* Table container with horizontal scroll */
            .admin-card {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .events-table {
                font-size: 0.75rem;
                min-width: 600px; /* Minimum width to maintain readability */
                table-layout: auto; /* Allow flexible column sizing on mobile */
            }

            .events-table th,
            .events-table td {
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }

            /* Adjust column widths for mobile */
            .events-table th:nth-child(1) { width: 50px; }   /* ID */
            .events-table th:nth-child(2) { width: 150px; }  /* Event Title */
            .events-table th:nth-child(3) { width: 180px; }  /* Date & Time */
            .events-table th:nth-child(4) { width: 80px; }   /* Status */
            .events-table th:nth-child(5) { width: 140px; }  /* Actions */

            /* Allow event title to wrap */
            .events-table td:nth-child(2) {
                white-space: normal;
                word-wrap: break-word;
                max-width: 150px;
            }

            /* Stack action buttons horizontally but smaller on mobile */
            .events-table td:nth-child(5) div {
                flex-direction: row;
                gap: 0.125rem;
                justify-content: center;
            }

            .events-table td:nth-child(5) .btn {
                min-width: auto;
                padding: 0.25rem 0.375rem;
                font-size: 0.625rem;
                border-radius: 0.25rem;
            }

            /* Smaller status badges */
            .status-badge {
                padding: 0.125rem 0.5rem;
                font-size: 0.625rem;
            }
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            table-layout: fixed;
        }

        .events-table th,
        .events-table td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: top;
        }

        .events-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .events-table tr:hover {
            background: var(--gray-50);
        }

        /* Column widths for better desktop layout */
        .events-table th:nth-child(1) { width: 6%; }   /* ID */
        .events-table th:nth-child(2) { width: 30%; }  /* Event Title */
        .events-table th:nth-child(3) { width: 25%; }  /* Date & Time */
        .events-table th:nth-child(4) { width: 10%; }  /* Status */
        .events-table th:nth-child(5) { width: 14%; }  /* Creator */
        .events-table th:nth-child(6) { width: 15%; }  /* Actions */

        .events-table td:nth-child(2) {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .events-table td:nth-child(3) {
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .events-table td:nth-child(5) {
            font-size: 0.875rem;
            word-wrap: break-word;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.375rem;
            text-decoration: none;
            color: var(--gray-700);
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
</head>
<body class="admin-panel-body">
    <?php renderNavigation('admin', 'events', $_SESSION['admin_name']); ?>



    <div class="fixed-container" style="margin-bottom: 3rem;">
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

        <!-- Events Management -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📅</div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Event Records</h3>
                    </div>
                    <a href="create_event.php" class="btn btn-primary">
                        ➕ Create Event
                    </a>
                </div>

                <!-- Filters -->
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-input">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Events</option>
                            <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="ongoing" <?= $status_filter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Search Events</label>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Search by title or description..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-outline w-full">🔍 Filter</button>
                    </div>
                </form>

                <!-- Events Table -->
                <?php if (mysqli_num_rows($events_result) > 0): ?>
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Title</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Creator</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_number = ($page - 1) * $per_page + 1;
                            while ($event = mysqli_fetch_assoc($events_result)): 
                                // Determine event status
                                $now = time();
                                $start_time = strtotime($event['start_datetime']);
                                $end_time = strtotime($event['end_datetime']);
                                
                                if ($now < $start_time) {
                                    $status = 'Upcoming';
                                    $status_class = 'status-upcoming';
                                } elseif ($now >= $start_time && $now <= $end_time) {
                                    $status = 'Ongoing';
                                    $status_class = 'status-ongoing';
                                } else {
                                    $status = 'Completed';
                                    $status_class = 'status-completed';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <span style="color: var(--gray-600); font-weight: 600;">
                                            <?= $event['id'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--gray-900);"><?= htmlspecialchars($event['title']) ?></strong>
                                        <?php if (!empty($event['description'])): ?>
                                            <br><small style="color: var(--gray-600);"><?= htmlspecialchars(substr($event['description'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem; color: var(--gray-700);">
                                            <strong>Start:</strong> <?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?><br>
                                            <strong>End:</strong> <?= date('M j, Y g:i A', strtotime($event['end_datetime'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>"><?= $status ?></span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;">
                                            <span style="color: var(--gray-700);"><?= htmlspecialchars($event['creator_name']) ?></span>
                                            <br><small style="color: var(--gray-500);"><?= ucfirst($event['creator_type']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="edit_event.php?id=<?= $event['id'] ?>"
                                               class="btn btn-sm btn-outline" title="Edit Event"
                                               style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                ✏️
                                            </a>
                                            <a href="view_event.php?id=<?= $event['id'] ?>"
                                               class="btn btn-sm btn-outline" title="View Event Details"
                                               style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                👁️
                                            </a>
                                            <form method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Are you sure you want to delete this event?')">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <button type="submit" name="delete_event"
                                                        class="btn btn-sm btn-outline" title="Delete Event"
                                                        style="color: #dc2626; border-color: #dc2626; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">← Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--gray-700);">No Events Found</h3>
                        <p style="margin: 0;">Create your first event to get started with attendance tracking.</p>
                        <a href="create_event.php" class="btn btn-primary" style="margin-top: 1rem;">
                            ➕ Create First Event
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
