<?php
date_default_timezone_set('Asia/Manila');
session_start();
include 'db_connect.php';
include 'includes/navigation.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student's course and section from DB (prioritize official_students for most current info)
$student_query = mysqli_prepare($conn, "
    SELECT
        COALESCE(os.course, s.course) as course,
        COALESCE(os.section, s.section) as section,
        COALESCE(os.full_name, s.full_name) as full_name
    FROM students s
    LEFT JOIN official_students os ON s.student_id = os.student_id
    WHERE s.student_id = ?
");
mysqli_stmt_bind_param($student_query, "s", $student_id);
mysqli_stmt_execute($student_query);
$student_result = mysqli_stmt_get_result($student_query);
$student_info = mysqli_fetch_assoc($student_result);

$course = $student_info['course'];
$section = $student_info['section'];
$full_name = $student_info['full_name'];

// Fetch upcoming and current events assigned to this section
$now = date('Y-m-d H:i:s');
$events_query = mysqli_prepare($conn, "
    SELECT * FROM events
    WHERE FIND_IN_SET(?, assigned_sections) > 0
    AND end_datetime >= ?
    ORDER BY start_datetime ASC
");

mysqli_stmt_bind_param($events_query, "ss", $section, $now);
mysqli_stmt_execute($events_query);
$events_result = mysqli_stmt_get_result($events_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Admin Panel - ADLOR</title>
  <?= generateFaviconTags($conn) ?>
  <link rel="stylesheet" href="assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    .student-admin-body {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
    }

    @keyframes pulse {
      0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
      }
      50% {
        box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
      }
    }

    .student-admin-header {
      background: none;
      color: var(--gray-900);
      padding: 1rem 0;
      margin-bottom: 2rem;
    }

    .student-admin-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: var(--gray-900) !important;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
    }

    .student-admin-subtitle {
      font-size: 1.125rem;
      opacity: 0.9;
      color: var(--gray-700) !important;
    }

    .student-admin-card {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .student-admin-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .stat-card {
      padding: 2rem;
      border-radius: 1rem;
      text-align: center;
      color: white;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .stat-card.blue {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .stat-card.green {
      background: linear-gradient(135deg, #10b981, #059669);
    }

    .stat-card.orange {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .stat-card.purple {
      background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .stat-card.teal {
      background: linear-gradient(135deg, #14b8a6, #0d9488);
    }

    .stat-number {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      display: block;
    }

    .stat-label {
      font-size: 0.875rem;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
    }

    .student-action-button {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      padding: 1.5rem;
      border-radius: 1rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }

    .student-action-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
      text-decoration: none;
      color: white;
    }

    .student-section-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid var(--gray-200);
    }

    .student-section-icon {
      background: linear-gradient(135deg, #7c3aed, #6d28d9);
      color: white;
      width: 3rem;
      height: 3rem;
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
  </style>
</head>
<body class="student-admin-body has-navbar">
  <?php renderNavigation('student', 'dashboard', $full_name); ?>

  <!-- Student Admin Header -->
  <div class="student-admin-header fade-in">
    <div class="container text-center">
      <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
        ⚙️ Student Dashboard
      </h1>
      <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
        Academic Progress & Event Management
      </p>
    </div>
  </div>

  <div class="container" style="margin-bottom: 3rem;">
    <!-- Statistics Grid -->
    <div class="stats-grid">
      <?php
      // Get student's attendance count
      $attendance_query = mysqli_prepare($conn, "SELECT COUNT(*) as attendance_count FROM attendance WHERE student_id = ?");
      mysqli_stmt_bind_param($attendance_query, "s", $student_id);
      mysqli_stmt_execute($attendance_query);
      $attendance_result = mysqli_stmt_get_result($attendance_query);
      $attendance_data = mysqli_fetch_assoc($attendance_result);
      $attendance_count = $attendance_data['attendance_count'];

      // Get total events for this section
      $total_events_query = mysqli_prepare($conn, "
          SELECT COUNT(*) as total_events FROM events
          WHERE FIND_IN_SET(?, assigned_sections) > 0
      ");
      mysqli_stmt_bind_param($total_events_query, "s", $section);
      mysqli_stmt_execute($total_events_query);
      $total_events_result = mysqli_stmt_get_result($total_events_query);
      $total_events_data = mysqli_fetch_assoc($total_events_result);
      $total_events = $total_events_data['total_events'];

      // Get upcoming and current events count
      $upcoming_events_query = mysqli_prepare($conn, "
          SELECT COUNT(*) as upcoming_events FROM events
          WHERE FIND_IN_SET(?, assigned_sections) > 0
          AND end_datetime >= ?
      ");
      mysqli_stmt_bind_param($upcoming_events_query, "ss", $section, $now);
      mysqli_stmt_execute($upcoming_events_query);
      $upcoming_events_result = mysqli_stmt_get_result($upcoming_events_query);
      $upcoming_events_data = mysqli_fetch_assoc($upcoming_events_result);
      $upcoming_events = $upcoming_events_data['upcoming_events'];
      ?>

      <div class="stat-card blue">
        <span class="stat-number"><?= $attendance_count ?></span>
        <div class="stat-label">
          📊 ATTENDANCE RECORDS
        </div>
      </div>

      <div class="stat-card green">
        <span class="stat-number"><?= $total_events ?></span>
        <div class="stat-label">
          📅 TOTAL EVENTS
        </div>
      </div>

      <div class="stat-card orange">
        <span class="stat-number"><?= $upcoming_events ?></span>
        <div class="stat-label">
          🔔 UPCOMING EVENTS
        </div>
      </div>

      <div class="stat-card purple">
        <span class="stat-number"><?= htmlspecialchars($section) ?></span>
        <div class="stat-label">
          🏫 MY SECTION
        </div>
      </div>

      <div class="stat-card teal">
        <span class="stat-number">A.Y. 2024-2025</span>
        <div class="stat-label">
          📚 ACADEMIC YEAR
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="student-admin-card" style="margin-bottom: 2rem;">
      <div style="padding: 2rem;">
        <div class="student-section-header">
          <div class="student-section-icon">🚀</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Quick Actions</h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
          <a href="student_qr_codes.php" class="student-action-button">
            📱 View My QR Codes
          </a>
          <a href="student_attendance.php" class="student-action-button" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
            📋 My Attendance History
          </a>
        </div>
      </div>
    </div>

    <!-- Events Section -->
    <div class="student-admin-card">
      <div style="padding: 2rem;">
        <div class="student-section-header">
          <div class="student-section-icon">📅</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Your Events</h3>
        </div>

        <?php if (mysqli_num_rows($events_result) === 0): ?>
          <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
            <h4 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">No events available</h4>
            <p style="margin: 0; font-size: 1rem;">There are no current or upcoming events for your section at this time.</p>
          </div>
        <?php else: ?>
          <div style="display: grid; gap: 1.5rem;">
            <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
              <?php
                $event_start = strtotime($event['start_datetime']);
                $event_end = strtotime($event['end_datetime']);
                $now_timestamp = time();

                // Determine event status
                $is_ongoing = $now_timestamp >= $event_start && $now_timestamp <= $event_end;
                $is_upcoming = $now_timestamp < $event_start;
                $is_past = $now_timestamp > $event_end;

                // Format the datetime for display
                $formatted_start = date('F j, Y \a\t g:i A', $event_start);
                $formatted_end = date('g:i A', $event_end);

                // Calculate time information
                if ($is_ongoing) {
                  $time_remaining = $event_end - $now_timestamp;
                  $hours_remaining = floor($time_remaining / 3600);
                  $minutes_remaining = floor(($time_remaining % 3600) / 60);
                  $time_text = "🔴 HAPPENING NOW - Ends in " . ($hours_remaining > 0 ? "{$hours_remaining}h {$minutes_remaining}m" : "{$minutes_remaining}m");
                  $status_color = '#dc2626'; // Red for ongoing
                  $bg_gradient = 'linear-gradient(135deg, #fef2f2, #fee2e2)';
                  $border_style = '5px solid #dc2626';
                  $pulse_animation = 'animation: pulse 2s infinite;';
                } elseif ($is_upcoming) {
                  $time_until = $event_start - $now_timestamp;
                  $days = floor($time_until / 86400);
                  $hours = floor(($time_until % 86400) / 3600);
                  $time_text = "⏰ Starts in " . ($days > 0 ? "{$days} days, {$hours} hours" : "{$hours} hours");
                  $status_color = '#3b82f6'; // Blue for upcoming
                  $bg_gradient = 'linear-gradient(135deg, #eff6ff, #dbeafe)';
                  $border_style = '5px solid #3b82f6';
                  $pulse_animation = '';
                } else {
                  $time_text = "✅ Event completed";
                  $status_color = '#6b7280'; // Gray for past
                  $bg_gradient = 'linear-gradient(135deg, #f9fafb, #f3f4f6)';
                  $border_style = '5px solid #6b7280';
                  $pulse_animation = '';
                }

                // QR code availability (1 hour before to 1 hour after start)
                $qr_start = $event_start - 3600; // 1 hour before
                $qr_end = $event_start + 3600; // 1 hour after start
                $can_generate_qr = $now_timestamp >= $qr_start && $now_timestamp <= $qr_end;
              ?>
              <div style="padding: 2rem; background: <?= $bg_gradient ?>; border-radius: 1rem; <?= $border_style ?>; <?= $pulse_animation ?>">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                  <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">
                      <?= htmlspecialchars($event['title']) ?>
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                      <span style="background: <?= $status_color ?>; color: white; padding: 0.5rem 1rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">
                        📅 <?= date('M j, Y', strtotime($event['start_datetime'])) ?>
                      </span>
                      <span style="background: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">
                        🕐 <?= date('g:i A', strtotime($event['start_datetime'])) ?> - <?= date('g:i A', strtotime($event['end_datetime'])) ?>
                      </span>
                      <span style="background: #8b5cf6; color: white; padding: 0.5rem 1rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">
                        ⏰ <?= $time_text ?>
                      </span>
                    </div>
                  </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                  <p style="margin: 0; color: #374151; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($event['description'])) ?>
                  </p>
                </div>

                <?php if ($can_generate_qr): ?>
                  <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 1px solid #10b981;">
                    <p style="margin: 0; font-size: 0.875rem; color: #065f46; font-weight: 600;">
                      ✅ <strong>QR Code Available!</strong> Your attendance QR code is ready to use.
                    </p>
                  </div>
                  <a href="student_qr_codes.php" class="student-action-button" style="display: inline-flex; padding: 1rem 2rem;">
                    📱 View QR Code
                  </a>
                <?php else: ?>
                  <div style="background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 1px solid #f59e0b;">
                    <p style="margin: 0; font-size: 0.875rem; color: #92400e; font-weight: 600;">
                      ⏰ QR code will be available 1 hour before the event starts.
                    </p>
                  </div>
                  <button style="background: #9ca3af; color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; font-weight: 600; cursor: not-allowed;" disabled>
                    🔒 QR Not Available Yet
                  </button>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
