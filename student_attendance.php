<?php
date_default_timezone_set('Asia/Manila');
session_start();
include 'db_connect.php';
include 'includes/navigation.php';
require_once 'includes/system_config.php';

// Get system settings
$system_name = getSystemName($conn);

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student information (prioritize official_students for most current info)
$student_query = mysqli_prepare($conn, "
    SELECT
        s.student_id,
        COALESCE(os.full_name, s.full_name) as full_name,
        COALESCE(os.course, s.course) as course,
        COALESCE(os.section, s.section) as section,
        s.profile_picture,
        CASE
            -- Extract year level from section name - check for numbers 1-10
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
            WHEN COALESCE(os.section, s.section) REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
            -- Fallback to course name if section has no valid year numbers
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
            WHEN COALESCE(os.course, s.course) REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
            -- Default to year 1
            ELSE 1
        END as year_level
    FROM students s
    LEFT JOIN official_students os ON s.student_id = os.student_id
    WHERE s.student_id = ?
");
mysqli_stmt_bind_param($student_query, "s", $student_id);
mysqli_stmt_execute($student_query);
$student_result = mysqli_stmt_get_result($student_query);
$student = mysqli_fetch_assoc($student_result);

// Get attendance history
$attendance_query = mysqli_prepare($conn, "
    SELECT a.*, e.title, e.start_datetime, e.end_datetime 
    FROM attendance a 
    JOIN events e ON a.event_id = e.id 
    WHERE a.student_id = ? 
    ORDER BY e.start_datetime DESC
");
mysqli_stmt_bind_param($attendance_query, "s", $student_id);
mysqli_stmt_execute($attendance_query);
$attendance_result = mysqli_stmt_get_result($attendance_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="has-navbar">
    <?php renderNavigation('student', 'attendance', $student['full_name']); ?>
    
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">📋 My Attendance History</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Track your attendance record for all events
            </p>
        </div>

        <!-- Student Info -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?>
                    </div>
                    <div>
                        <strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?>
                    </div>
                    <div>
                        <strong>Course:</strong> <?= htmlspecialchars($student['course']) ?>
                    </div>
                    <div>
                        <strong>Section:</strong> <?= htmlspecialchars($student['section']) ?>
                    </div>
                    <div>
                        <strong>Year Level:</strong> <?= htmlspecialchars($student['year_level']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <?php if (mysqli_num_rows($attendance_result) === 0): ?>
            <div class="alert alert-info">
                <h4 style="margin: 0 0 0.5rem 0;">No Attendance Records</h4>
                <p style="margin: 0;">You haven't attended any events yet. Your attendance will appear here once you scan your QR codes at events.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📊 Attendance Records</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
                                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Event</th>
                                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Date</th>
                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Time In</th>
                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Time Out</th>
                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = mysqli_fetch_assoc($attendance_result)): ?>
                                    <?php
                                    $event_date = date('M j, Y', strtotime($record['start_datetime']));
                                    $time_in = $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-';
                                    $time_out = $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-';
                                    
                                    // Determine status
                                    if ($record['time_in'] && $record['time_out']) {
                                        $status = 'Complete';
                                        $status_class = 'success';
                                        $status_icon = '✅';
                                    } elseif ($record['time_in']) {
                                        $status = 'Time In Only';
                                        $status_class = 'warning';
                                        $status_icon = '⏰';
                                    } else {
                                        $status = 'No Record';
                                        $status_class = 'error';
                                        $status_icon = '❌';
                                    }
                                    ?>
                                    <tr style="border-bottom: 1px solid var(--gray-200);">
                                        <td style="padding: 1rem;">
                                            <div style="font-weight: 500; color: var(--gray-900);">
                                                <?= htmlspecialchars($record['title']) ?>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; color: var(--gray-600);">
                                            <?= $event_date ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center; color: var(--gray-700);">
                                            <?= $time_in ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center; color: var(--gray-700);">
                                            <?= $time_out ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center;">
                                            <span style="
                                                display: inline-flex; 
                                                align-items: center; 
                                                gap: 0.25rem; 
                                                padding: 0.25rem 0.75rem; 
                                                border-radius: 1rem; 
                                                font-size: 0.75rem; 
                                                font-weight: 500;
                                                background: var(--<?= $status_class ?>-light);
                                                color: var(--<?= $status_class ?>-dark);
                                                border: 1px solid var(--<?= $status_class ?>-color);
                                            ">
                                                <?= $status_icon ?> <?= $status ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <?php
            // Reset result pointer and calculate stats
            mysqli_data_seek($attendance_result, 0);
            $total_events = 0;
            $complete_attendance = 0;
            $partial_attendance = 0;
            
            while ($record = mysqli_fetch_assoc($attendance_result)) {
                $total_events++;
                if ($record['time_in'] && $record['time_out']) {
                    $complete_attendance++;
                } elseif ($record['time_in']) {
                    $partial_attendance++;
                }
            }
            
            $attendance_rate = $total_events > 0 ? round(($complete_attendance / $total_events) * 100, 1) : 0;
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem;">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
                    <div class="stat-number"><?= $total_events ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, var(--success-color), var(--success-dark));">
                    <div class="stat-number"><?= $complete_attendance ?></div>
                    <div class="stat-label">Complete Attendance</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, var(--warning-color), var(--warning-dark));">
                    <div class="stat-number"><?= $partial_attendance ?></div>
                    <div class="stat-label">Partial Attendance</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <div class="stat-number"><?= $attendance_rate ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
