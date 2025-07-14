<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$event_filter = $_GET['event_id'] ?? '';
$section_filter = $_GET['section'] ?? '';
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

// Get sections for filter - will be populated dynamically based on selected event
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - ADLOR SBO</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #581c87 100%);
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
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('sbo', 'attendance', $_SESSION['sbo_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">📋 View Attendance</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Monitor and review student attendance records
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- Filters -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 700;">📊 Filter Attendance</h3>
                
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div class="form-group">
                        <label class="form-label">Event</label>
                        <select name="event_id" id="event_filter" class="form-select">
                            <option value="">All Events</option>
                            <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                <option value="<?= $event['id'] ?>" <?= $event_filter == $event['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['title']) ?> (<?= date('M j, Y', strtotime($event['start_datetime'])) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Section</label>
                        <select name="section" id="section_filter" class="form-select" disabled>
                            <option value="">-- Select an event first --</option>
                            <!-- Sections will be populated dynamically based on selected event -->
                        </select>
                        <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                            Only sections assigned to the selected event will appear here
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="present" <?= $status_filter == 'present' ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= $status_filter == 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="complete" <?= $status_filter == 'complete' ? 'selected' : '' ?>>Complete</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="view_attendance.php" class="btn btn-outline">🔄 Reset</a>
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
                                        <td><?= $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-' ?></td>
                                        <td><?= $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-' ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($record['status']) {
                                                case 'Complete':
                                                    $status_class = 'status-complete';
                                                    break;
                                                case 'Time In Only':
                                                    $status_class = 'status-partial';
                                                    break;
                                                default:
                                                    $status_class = 'status-absent';
                                            }
                                            ?>
                                            <span class="status-badge <?= $status_class ?>">
                                                <?= $record['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                        No attendance records found for the selected filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Event data for dynamic section filtering
        const eventSections = {
            <?php
            // Reset the events query for JavaScript
            mysqli_data_seek($events_result, 0);
            $event_data = [];
            while ($event = mysqli_fetch_assoc($events_result)) {
                $sections = array_map('trim', explode(',', $event['assigned_sections']));
                $event_data[] = $event['id'] . ': ' . json_encode($sections);
            }
            echo implode(",\n            ", $event_data);
            ?>
        };

        // Function to update sections dropdown based on selected event
        function updateSectionsDropdown() {
            const eventSelect = document.getElementById('event_filter');
            const sectionSelect = document.getElementById('section_filter');
            const selectedEventId = eventSelect.value;

            // Clear current options
            sectionSelect.innerHTML = '<option value="">All Assigned Sections</option>';

            if (selectedEventId && eventSections[selectedEventId]) {
                const sections = eventSections[selectedEventId];

                // Add sections for the selected event
                sections.forEach(function(section) {
                    if (section.trim()) {
                        const option = document.createElement('option');
                        option.value = section.trim();
                        option.textContent = section.trim();

                        // Check if this section should be selected based on current filter
                        <?php if ($section_filter): ?>
                        if (section.trim() === '<?= htmlspecialchars($section_filter) ?>') {
                            option.selected = true;
                        }
                        <?php endif; ?>

                        sectionSelect.appendChild(option);
                    }
                });

                // Enable the section dropdown
                sectionSelect.disabled = false;
            } else {
                // Show all sections option when no specific event is selected
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            }
        }

        // Add event listener to event dropdown
        document.getElementById('event_filter').addEventListener('change', updateSectionsDropdown);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSectionsDropdown();
        });
    </script>
</body>
</html>
