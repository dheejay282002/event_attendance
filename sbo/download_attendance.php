<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit;
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
        // Filter by year level extracted from course code
        $where_conditions[] = "(
            (s.course REGEXP '-[0-9]+' AND SUBSTRING_INDEX(SUBSTRING_INDEX(s.course, '-', -1), ' ', 1) = ?) OR
            (s.course REGEXP '[0-9]+' AND REGEXP_SUBSTR(s.course, '[0-9]+') = ?)
        )";
        $params[0] .= "ss";
        $params[] = $filter_year;
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
    fputcsv($output, ['Generated by: ' . $_SESSION['sbo_name']]);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Attendance - ADLOR SBO</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'reports', $_SESSION['sbo_name']); ?>
    
    <div class="container-md" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">📊 Download Attendance Reports</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Export attendance data to Excel/CSV format
            </p>
        </div>

        <!-- Download Form -->
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">📥 Select Event to Download</h3>
            </div>
            
            <div class="card-body">
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
                                        - Sections: <?= htmlspecialchars($event['assigned_sections']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filter Options -->
                        <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; margin: 1.5rem 0;">
                            <h4 style="margin: 0 0 1rem 0; color: var(--gray-800);">🔍 Filter Options (Optional)</h4>
                            <p style="margin: 0 0 1rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                Filter the attendance data by specific criteria to create targeted reports
                            </p>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" for="filter_course">Filter by Course</label>
                                    <select name="filter_course" id="filter_course" class="form-select" disabled>
                                        <option value="">-- Select an event first --</option>
                                        <!-- Courses will be populated dynamically based on selected event -->
                                    </select>
                                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                        Step 1: Select a course to enable year filter
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="filter_section">Filter by Section</label>
                                    <select name="filter_section" id="filter_section" class="form-select" disabled>
                                        <option value="">-- Select a course first --</option>
                                        <!-- Sections will be populated dynamically based on selected course -->
                                    </select>
                                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                        Step 2: Sections will appear after selecting a course
                                    </small>
                                </div>


                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button type="submit" name="download" class="btn btn-success">
                                📊 Download as CSV/Excel
                            </button>
                            <a href="view_attendance.php" class="btn btn-outline">
                                👁️ View Online First
                            </a>
                        </div>
                    </form>
                    
                    <!-- Instructions -->
                    <div style="margin-top: 2rem; padding: 1.5rem; background: var(--primary-light); border-radius: 0.75rem; border: 1px solid var(--primary-color);">
                        <h4 style="margin: 0 0 1rem 0; color: var(--primary-dark);">📋 Download Instructions</h4>
                        <div style="color: var(--primary-dark); font-size: 0.875rem; line-height: 1.6;">
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Select an event</strong> from the dropdown menu above</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Section filter</strong> will automatically show only sections assigned to the selected event</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Apply additional filters</strong> (optional) to narrow down by Course or Year</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Click "Download as CSV/Excel"</strong> to export the filtered attendance data</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Only students from assigned sections</strong> will be included in the report</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>Students who didn't attend</strong> will be marked as "Absent"</p>
                            <p style="margin: 0 0 0.5rem 0;">• <strong>File format</strong> is compatible with Excel, Google Sheets, and other spreadsheet applications</p>
                            <p style="margin: 0;">• <strong>Filename includes</strong> event name, filters applied, and generation date for easy organization</p>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4 style="margin: 0 0 0.5rem 0;">No Events Available</h4>
                        <p style="margin: 0;">
                            No events have been created yet. 
                            <a href="create_event.php" style="font-weight: 500;">Create an event</a> 
                            first to generate attendance reports.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-footer text-center">
                <p style="margin: 0;">
                    <a href="dashboard.php" style="color: var(--gray-600);">← Back to SBO Dashboard</a>
                </p>
            </div>
        </div>

        <!-- Sample Data Preview -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h4 style="margin: 0;">📄 Sample Export Format</h4>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Student ID</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Full Name</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Course</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Section</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Time In</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Time Out</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid var(--gray-200);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--gray-200);">
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">202300001</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">John Doe</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">BSIT</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">IT-3A</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">2025-01-15 09:15:30</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">2025-01-15 12:05:45</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200); color: var(--success-color); font-weight: 500;">Complete</td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--gray-200);">
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">202300002</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">Jane Smith</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">BSCS</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">CS-2B</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">2025-01-15 09:20:15</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);"></td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200); color: var(--warning-color); font-weight: 500;">Time In Only</td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--gray-200);">
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">202300003</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">Mike Johnson</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">BSIT</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);">IT-3A</td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);"></td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200);"></td>
                                <td style="padding: 0.75rem; border: 1px solid var(--gray-200); color: var(--error-color); font-weight: 500;">Absent</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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

        // Function to update filter dropdowns based on selected event
        function updateEventFilters() {
            const eventSelect = document.getElementById('event_id');
            const sectionSelect = document.getElementById('filter_section');
            const courseSelect = document.getElementById('filter_course');

            const selectedEventId = eventSelect.value;

            // Clear current options
            courseSelect.innerHTML = '<option value="">-- All Courses --</option>';
            sectionSelect.innerHTML = '<option value="">-- Select a course first --</option>';

            if (selectedEventId && eventData[selectedEventId]) {
                const data = eventData[selectedEventId];

                // Populate courses (always available when event is selected)
                data.courses.forEach(function(course) {
                    if (course.trim()) {
                        const option = document.createElement('option');
                        option.value = course.trim();
                        option.textContent = course.trim();
                        courseSelect.appendChild(option);
                    }
                });

                // Enable only course dropdown initially
                courseSelect.disabled = false;

                // Keep section disabled until course is selected
                sectionSelect.disabled = true;
            } else {
                // Disable all dropdowns if no event is selected
                sectionSelect.disabled = true;
                courseSelect.disabled = true;

                // Update placeholder text
                sectionSelect.innerHTML = '<option value="">-- Select an event first --</option>';
                courseSelect.innerHTML = '<option value="">-- Select an event first --</option>';
            }
        }

        // Function to update sections based on selected course
        function updateCourseFilters() {
            const eventSelect = document.getElementById('event_id');
            const sectionSelect = document.getElementById('filter_section');
            const courseSelect = document.getElementById('filter_course');
            const selectedEventId = eventSelect.value;
            const selectedCourse = courseSelect.value;

            // Clear sections
            sectionSelect.innerHTML = '<option value="">-- All Sections --</option>';

            if (selectedEventId && eventData[selectedEventId] && selectedCourse) {
                // Get sections for the specific course from the server
                fetch('get_course_sections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'event_id=' + encodeURIComponent(selectedEventId) + '&course=' + encodeURIComponent(selectedCourse)
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
                    // Fallback: show all sections for the event
                    const data = eventData[selectedEventId];
                    data.sections.forEach(function(section) {
                        if (section.trim()) {
                            const option = document.createElement('option');
                            option.value = section.trim();
                            option.textContent = section.trim();
                            sectionSelect.appendChild(option);
                        }
                    });
                    sectionSelect.disabled = false;
                });
            } else if (!selectedCourse) {
                // If no course selected, disable sections
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">-- Select a course first --</option>';
            } else {
                // If no event selected
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">-- Select an event first --</option>';
            }
        }



        // Add event listeners
        document.getElementById('event_id').addEventListener('change', updateEventFilters);
        document.getElementById('filter_course').addEventListener('change', updateCourseFilters);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Event Data:', eventData); // Debug: Check what data is available
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
