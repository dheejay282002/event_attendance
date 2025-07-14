<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle download request
if (isset($_POST['download']) && isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $filter_course = $_POST['filter_course'] ?? '';
    $filter_section = $_POST['filter_section'] ?? '';
    $filter_year = $_POST['filter_year'] ?? '';

    // Get event details
    $event_query = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
    mysqli_stmt_bind_param($event_query, "i", $event_id);
    mysqli_stmt_execute($event_query);
    $event_result = mysqli_stmt_get_result($event_query);
    $event = mysqli_fetch_assoc($event_result);

    if (!$event) {
        die("Event not found");
    }

    // Build dynamic query with filters
    $where_conditions = ["FIND_IN_SET(s.section, ?)"];
    $params = ["is", $event_id, $event['assigned_sections']];

    if (!empty($filter_course)) {
        $where_conditions[] = "s.course = ?";
        $params[0] .= "s";
        $params[] = $filter_course;
    }

    if (!empty($filter_section)) {
        $where_conditions[] = "s.section = ?";
        $params[0] .= "s";
        $params[] = $filter_section;
    }

    if (!empty($filter_year)) {
        // Filter by year level using improved extraction logic
        $where_conditions[] = "
            CASE
                -- Extract year level from section name - check for numbers 1-10
                WHEN s.section REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                WHEN s.section REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                WHEN s.section REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                WHEN s.section REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                WHEN s.section REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                WHEN s.section REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                WHEN s.section REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                WHEN s.section REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                WHEN s.section REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                WHEN s.section REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                -- Fallback to course name if section has no valid year numbers
                WHEN s.course REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                WHEN s.course REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                WHEN s.course REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                WHEN s.course REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                WHEN s.course REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                WHEN s.course REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                WHEN s.course REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                WHEN s.course REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                WHEN s.course REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                WHEN s.course REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                -- Default to year 1
                ELSE 1
            END = ?
        ";
        $params[0] .= "i";
        $params[] = $filter_year;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get attendance data with filters from official_students table
    $attendance_query = mysqli_prepare($conn, "
        SELECT
            s.student_id,
            s.full_name,
            s.course,
            s.section,
            a.time_in,
            a.time_out,
            CASE
                WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
                WHEN a.time_in IS NOT NULL THEN 'Time In Only'
                ELSE 'Absent'
            END as status
        FROM official_students s
        LEFT JOIN attendance a ON s.student_id = a.student_id AND a.event_id = ?
        WHERE $where_clause
        ORDER BY s.course, s.section, s.full_name
    ");

    mysqli_stmt_bind_param($attendance_query, ...$params);
    mysqli_stmt_execute($attendance_query);
    $attendance_result = mysqli_stmt_get_result($attendance_query);
    
    // Generate CSV content with filter info in filename
    $filename_parts = ["attendance", preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['title'])];

    if (!empty($filter_course)) {
        $filename_parts[] = $filter_course;
    }
    if (!empty($filter_section)) {
        $filename_parts[] = $filter_section;
    }
    if (!empty($filter_year)) {
        $filename_parts[] = "Year_" . $filter_year;
    }

    $filename_parts[] = date('Y-m-d');
    $filename = implode("_", $filename_parts) . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Event header
    fputcsv($output, ['Event: ' . $event['title']]);
    fputcsv($output, ['Date: ' . date('F j, Y', strtotime($event['start_datetime']))]);
    fputcsv($output, ['Time: ' . date('g:i A', strtotime($event['start_datetime'])) . ' - ' . date('g:i A', strtotime($event['end_datetime']))]);
    fputcsv($output, ['Sections: ' . $event['assigned_sections']]);

    // Add filter information
    if (!empty($filter_course) || !empty($filter_section) || !empty($filter_year)) {
        fputcsv($output, ['Filters Applied:']);
        if (!empty($filter_course)) {
            fputcsv($output, ['  Course: ' . $filter_course]);
        }
        if (!empty($filter_section)) {
            fputcsv($output, ['  Section: ' . $filter_section]);
        }
        if (!empty($filter_year)) {
            fputcsv($output, ['  Year: 20' . $filter_year]);
        }
    }

    fputcsv($output, ['Generated: ' . date('F j, Y g:i A')]);
    fputcsv($output, ['Generated by: ' . $_SESSION['admin_name'] . ' (Admin)']);
    fputcsv($output, []); // Empty row
    
    // CSV headers
    fputcsv($output, [
        'Student ID',
        'Full Name',
        'Course',
        'Section',
        'Time In',
        'Time Out',
        'Status'
    ]);
    
    // Data rows
    while ($row = mysqli_fetch_assoc($attendance_result)) {
        fputcsv($output, [
            $row['student_id'],
            $row['full_name'],
            $row['course'],
            $row['section'],
            $row['time_in'] ? date('Y-m-d H:i:s', strtotime($row['time_in'])) : '',
            $row['time_out'] ? date('Y-m-d H:i:s', strtotime($row['time_out'])) : '',
            $row['status']
        ]);
    }
    
    fclose($output);
    exit;
}

// Get all events for selection
$events_query = "SELECT * FROM events ORDER BY start_datetime DESC";
$events_result = mysqli_query($conn, $events_query);

// Get all available year levels from student data
$year_levels_query = "
    SELECT DISTINCT
        CASE
            -- Extract year level from section name - check for numbers 1-10
            WHEN section REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
            WHEN section REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
            WHEN section REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
            WHEN section REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
            WHEN section REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
            WHEN section REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
            WHEN section REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
            WHEN section REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
            WHEN section REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
            WHEN section REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
            -- Fallback to course name if section has no valid year numbers
            WHEN course REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
            WHEN course REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
            WHEN course REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
            WHEN course REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
            WHEN course REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
            WHEN course REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
            WHEN course REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
            WHEN course REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
            WHEN course REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
            WHEN course REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
            -- Default to year 1
            ELSE 1
        END as year_level
    FROM official_students
    ORDER BY year_level ASC
";
$year_levels_result = mysqli_query($conn, $year_levels_query);
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
    <title>Download Attendance - Admin</title>
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
            padding: 0;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'reports', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">📊 Download Attendance Reports</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0;">
                Export attendance data to Excel/CSV format (Admin Access)
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- Download Form -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                    <div style="background: #7c3aed; color: white; padding: 0.5rem; border-radius: 0.5rem; font-size: 1.25rem;">📥</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Select Event to Download</h3>
                </div>
                
                <?php if (mysqli_num_rows($events_result) > 0): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="event_id">Choose Event</label>
                            <select name="event_id" id="event_id" class="form-select" required>
                                <option value="">-- Select an Event --</option>
                                <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                    <option value="<?= $event['id'] ?>">
                                        <?= htmlspecialchars($event['title']) ?>
                                        (<?= date('M j, Y', strtotime($event['start_datetime'])) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filter Options -->
                        <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; margin: 1.5rem 0;">
                            <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">🔍 Optional Filters</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" for="filter_course">Filter by Course</label>
                                    <select name="filter_course" id="filter_course" class="form-select" disabled>
                                        <option value="">-- Select an event first --</option>
                                    </select>
                                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                        Step 2: Select a course from the event
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="filter_section">Filter by Section</label>
                                    <select name="filter_section" id="filter_section" class="form-select" disabled>
                                        <option value="">-- Select a year first --</option>
                                        <!-- Sections will be populated dynamically based on selected course and year -->
                                    </select>
                                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                        Step 4: Select a section (optional)
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="filter_year">Filter by Year</label>
                                    <select name="filter_year" id="filter_year" class="form-select" disabled>
                                        <option value="">-- Select a course first --</option>
                                        <!-- Year levels will be populated dynamically based on selected course -->
                                    </select>
                                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                        Step 3: Select a year level from the course
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button type="submit" name="download" class="btn btn-primary">
                                📊 Download as CSV/Excel
                            </button>
                            <a href="dashboard.php" class="btn btn-outline">
                                📈 Back to Dashboard
                            </a>
                        </div>
                    </form>

                    <!-- Instructions -->
                    <div style="background: #eff6ff; padding: 1.5rem; border-radius: 0.75rem; margin-top: 2rem; border: 1px solid #bfdbfe;">
                        <h4 style="margin: 0 0 1rem 0; color: #1e40af;">📋 How to Use</h4>
                        <div style="color: #1e40af; font-size: 0.875rem;">
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Step 1:</strong> Select an event from the dropdown</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Step 2:</strong> Choose a course from the event (optional)</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Step 3:</strong> Select a year level from the course (optional)</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Step 4:</strong> Pick a specific section (optional)</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Download:</strong> Click the button to export filtered attendance data</p>
                            <p style="margin: 0;">• <strong>File format:</strong> Compatible with Excel, Google Sheets, and other spreadsheet applications</p>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4 style="margin: 0 0 0.5rem 0;">No Events Available</h4>
                        <p style="margin: 0;">
                            No events have been created yet. Events are typically created by SBO users.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 2rem;">
            <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">🔗 Quick Actions</h4>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="dashboard.php" class="btn btn-outline">📊 Dashboard</a>
                <a href="manage_students.php" class="btn btn-outline">👥 Manage Students</a>
                <a href="../sbo/dashboard.php" class="btn btn-outline">🏛️ SBO Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Event data for dynamic filtering
        const eventData = {
            <?php
            // Reset the events query for JavaScript and get student data for each event
            mysqli_data_seek($events_result, 0);
            $event_data = [];
            while ($event = mysqli_fetch_assoc($events_result)) {
                $sections = array_map('trim', explode(',', $event['assigned_sections']));

                // Get courses and years for students in assigned sections
                $sections_list = "'" . implode("','", array_map('mysqli_real_escape_string', array_fill(0, count($sections), $conn), $sections)) . "'";
                $student_data_query = mysqli_query($conn, "
                    SELECT DISTINCT course
                    FROM official_students
                    WHERE section IN ($sections_list)
                    AND course IS NOT NULL
                    AND course != ''
                ");

                $courses = [];
                $years = [];
                while ($row = mysqli_fetch_assoc($student_data_query)) {
                    if ($row['course']) {
                        $courses[] = $row['course'];

                        // Extract year level from course code using PHP
                        $course = $row['course'];
                        if (preg_match('/-(\d+)/', $course, $matches)) {
                            // Pattern like "BSIT-2" or "BSCS-3A"
                            $year = intval($matches[1]);
                            if ($year >= 1 && $year <= 4) {
                                $years[] = $year;
                            }
                        } elseif (preg_match('/(\d+)/', $course, $matches)) {
                            // Pattern like "BSIT2" or "CS3"
                            $year = intval($matches[1]);
                            if ($year >= 1 && $year <= 4) {
                                $years[] = $year;
                            }
                        }
                    }
                }

                $event_data[] = $event['id'] . ': {
                    sections: ' . json_encode($sections) . ',
                    courses: ' . json_encode(array_unique($courses)) . ',
                    years: ' . json_encode(array_unique($years)) . '
                }';
            }
            echo implode(",\n            ", $event_data);
            ?>
        };

        function updateEventFilters() {
            const eventSelect = document.getElementById('event_id');
            const courseSelect = document.getElementById('filter_course');
            const yearSelect = document.getElementById('filter_year');
            const sectionSelect = document.getElementById('filter_section');

            const selectedEventId = eventSelect.value;

            // Reset all dependent filters
            courseSelect.innerHTML = '<option value="">-- All Courses --</option>';
            yearSelect.innerHTML = '<option value="">-- Select a course first --</option>';
            sectionSelect.innerHTML = '<option value="">-- Select a year first --</option>';

            courseSelect.disabled = true;
            yearSelect.disabled = true;
            sectionSelect.disabled = true;

            if (selectedEventId && eventData[selectedEventId]) {
                const event = eventData[selectedEventId];

                // Populate courses from the selected event
                event.courses.forEach(function(course) {
                    const option = document.createElement('option');
                    option.value = course;
                    option.textContent = course;
                    courseSelect.appendChild(option);
                });

                courseSelect.disabled = false;
            }
        }

        function updateCourseFilters() {
            const eventSelect = document.getElementById('event_id');
            const courseSelect = document.getElementById('filter_course');
            const yearSelect = document.getElementById('filter_year');
            const sectionSelect = document.getElementById('filter_section');

            const selectedEventId = eventSelect.value;
            const selectedCourse = courseSelect.value;

            // Reset dependent filters
            yearSelect.innerHTML = '<option value="">-- All Years --</option>';
            sectionSelect.innerHTML = '<option value="">-- Select a year first --</option>';
            yearSelect.disabled = true;
            sectionSelect.disabled = true;

            if (selectedEventId && eventData[selectedEventId] && selectedCourse) {
                // Get year levels for the specific course from the server
                fetch('get_course_years.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'event_id=' + encodeURIComponent(selectedEventId) + '&course=' + encodeURIComponent(selectedCourse)
                })
                .then(response => response.json())
                .then(years => {
                    years.forEach(function(year) {
                        const option = document.createElement('option');
                        option.value = year.level;
                        option.textContent = year.display;
                        yearSelect.appendChild(option);
                    });
                    yearSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching years:', error);
                    yearSelect.disabled = true;
                });
            }
        }

        function updateYearFilters() {
            const eventSelect = document.getElementById('event_id');
            const courseSelect = document.getElementById('filter_course');
            const yearSelect = document.getElementById('filter_year');
            const sectionSelect = document.getElementById('filter_section');

            const selectedEventId = eventSelect.value;
            const selectedCourse = courseSelect.value;
            const selectedYear = yearSelect.value;

            // Reset section filter
            sectionSelect.innerHTML = '<option value="">-- All Sections --</option>';
            sectionSelect.disabled = true;

            if (selectedEventId && eventData[selectedEventId] && selectedCourse && selectedYear) {
                // Get sections for the specific course and year from the server
                fetch('get_course_year_sections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'event_id=' + encodeURIComponent(selectedEventId) +
                          '&course=' + encodeURIComponent(selectedCourse) +
                          '&year=' + encodeURIComponent(selectedYear)
                })
                .then(response => response.json())
                .then(sections => {
                    sections.forEach(function(section) {
                        if (section.trim()) {
                            const option = document.createElement('option');
                            option.value = section.trim();
                            option.textContent = section.trim();
                            sectionSelect.appendChild(option);
                        }
                    });
                    sectionSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching sections:', error);
                    sectionSelect.disabled = true;
                });
            }
        }

        // Add event listeners
        document.getElementById('event_id').addEventListener('change', updateEventFilters);
        document.getElementById('filter_course').addEventListener('change', updateCourseFilters);
        document.getElementById('filter_year').addEventListener('change', updateYearFilters);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateEventFilters();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const eventId = document.getElementById('event_id').value;
            if (!eventId) {
                e.preventDefault();
                alert('Please select an event before downloading the report.');
                return false;
            }
        });
    </script>
</body>
</html>
