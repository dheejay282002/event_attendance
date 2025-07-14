<?php
/**
 * QR Code Scanner - Clean ADLOR Design
 * ADLOR Attendance System
 */

session_start();
require_once 'db_connect.php';
require_once 'includes/scanner_functions.php';

// Check if user is authorized (admin, SBO, or scanner) - allow guest access
$user_type = $_SESSION['user_type'] ?? 'guest';
$is_authenticated = in_array($user_type, ['admin', 'sbo', 'scanner']);

$qr_data = null;
$event_id_selected = $_GET['event_id'] ?? $_POST['event_id'] ?? null;
$student = null;
$message = "";

// Check scanner settings and availability (with event-specific restrictions)
$scanner_status = getScannerAvailabilityStatus($conn, $event_id_selected);
$qr_scanner_enabled = $scanner_status['qr_scanner_enabled'];
$manual_entry_enabled = $scanner_status['manual_id_enabled'];
$scanner_time_available = $scanner_status['time_available'];
$scanner_date_available = $scanner_status['date_available'];
$scanner_overall_available = $scanner_status['overall_available'];

// Get event info for display (used by both QR scanner and manual entry sections)
$event_display_info = null;
if ($event_id_selected) {
    $event_query = "SELECT start_datetime, end_datetime FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $event_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id_selected);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event_display_info = mysqli_fetch_assoc($event_result);
}

// CRITICAL: Force real-time attendance time check
if ($event_id_selected) {
    $current_timestamp = time();
    $event_query = "SELECT start_datetime, end_datetime FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $event_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id_selected);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event_time_check = mysqli_fetch_assoc($event_result);

    if ($event_time_check) {
        $event_end_timestamp = strtotime($event_time_check['end_datetime']);

        // STRICT ENFORCEMENT: If current time is past event end time, FORCE DISABLE
        if ($current_timestamp > $event_end_timestamp) {
            $scanner_overall_available = false;
            $scanner_time_available = false;

            // Override the scanner status to show time expired
            $scanner_status['overall_available'] = false;
            $scanner_status['time_available'] = false;
            $scanner_status['messages'][] = "Event has ended. Attendance is no longer accepted.";

            // Debug info
            error_log("ATTENDANCE TIME EXPIRED - Current: " . date('Y-m-d H:i:s', $current_timestamp) .
                     ", Event End: " . date('Y-m-d H:i:s', $event_end_timestamp));
        }
    }
}



// Initialize variables
$scanner_disabled = false;
$scanner_time_restricted = false;

// If both QR scanner and manual entry are disabled, or time/date restrictions apply
if (!$qr_scanner_enabled && !$manual_entry_enabled) {
    $scanner_disabled = true;
} elseif (!$scanner_overall_available) {
    $scanner_time_restricted = true;
} else {
    $scanner_disabled = false;
    $scanner_time_restricted = false;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_input = trim($_POST['qr_data'] ?? '');
    $student_id_input = trim($_POST['student_id'] ?? '');
    $event_id_selected = $_POST['event_id'];

    $student_id = null;

    // Check if manual Student ID was entered
    if (!empty($student_id_input)) {
        $student_id = $student_id_input;
    } elseif (!empty($qr_input)) {
        // Try to decode the QR format
        $qr_data = json_decode($qr_input, true);

        if (!$qr_data || !isset($qr_data['student_id'])) {
            $message = "❌ Invalid or unreadable QR code format.";
        } else {
            $student_id = $qr_data['student_id'];
        }
    } else {
        $message = "❌ Please enter a Student ID or scan a QR code.";
    }

    if ($student_id) {
        // First, verify student exists in official_students (master list)
        $official_stmt = mysqli_prepare($conn, "SELECT * FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($official_stmt, "s", $student_id);
        mysqli_stmt_execute($official_stmt);
        $official_result = mysqli_stmt_get_result($official_stmt);
        $official_student = mysqli_fetch_assoc($official_result);

        if (!$official_student) {
            $message = "❌ Student ID '{$student_id}' not found in database.<br>📝 <strong>Please contact administrator</strong> to add you to the official student list.";
        } else {
            // Second, verify student has registered (has login account)
            $registered_stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($registered_stmt, "s", $student_id);
            mysqli_stmt_execute($registered_stmt);
            $registered_result = mysqli_stmt_get_result($registered_stmt);
            $student = mysqli_fetch_assoc($registered_result);

            if (!$student) {
                $message = "❌ Student ID '{$student_id}' has not registered yet.<br>📝 <strong>Please register first</strong> before your attendance can be recorded.<br>💡 Visit the homepage to create your account.";
            } else {
                // Use official_students data for current info, but student exists in both tables
                $student = $official_student;
            }
        }

        if ($student) {
            // Verify event exists and is active
            $event_stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
            mysqli_stmt_bind_param($event_stmt, "i", $event_id_selected);
            mysqli_stmt_execute($event_stmt);
            $event_result = mysqli_stmt_get_result($event_stmt);
            $event = mysqli_fetch_assoc($event_result);

            if (!$event) {
                $message = "❌ Selected event not found.";
            } else {
                // Check if student's section is assigned to this event
                $assigned_sections = array_map('trim', explode(',', $event['assigned_sections']));
                $student_section = trim($student['section']);

                if (!in_array($student_section, $assigned_sections)) {
                    $message = "❌ Student's section ({$student_section}) is not assigned to this event.";
                } else {
                    // Check existing attendance
                    $check_query = mysqli_prepare($conn, "SELECT * FROM attendance WHERE student_id = ? AND event_id = ?");
                    mysqli_stmt_bind_param($check_query, "si", $student['student_id'], $event_id_selected);
                    mysqli_stmt_execute($check_query);
                    $existing = mysqli_stmt_get_result($check_query);

                    if (mysqli_num_rows($existing) > 0) {
                        $existing_row = mysqli_fetch_assoc($existing);

                        if ($existing_row['time_out'] === null) {
                            // Time out
                            $update = mysqli_prepare($conn, "UPDATE attendance SET time_out = NOW() WHERE id = ?");
                            mysqli_stmt_bind_param($update, "i", $existing_row['id']);
                            mysqli_stmt_execute($update);
                            $message = "✅ Time out recorded successfully!";
                        } else {
                            $message = "⚠️ Student already completed attendance (timed in and out).";
                        }
                    } else {
                        // Time in
                        $insert = mysqli_prepare($conn, "INSERT INTO attendance (student_id, event_id, time_in) VALUES (?, ?, NOW())");
                        mysqli_stmt_bind_param($insert, "si", $student['student_id'], $event_id_selected);
                        mysqli_stmt_execute($insert);
                        $message = "✅ Time in recorded successfully!";
                    }
                }
            }
        }
    }
}

$event_query = mysqli_query($conn, "SELECT * FROM events ORDER BY start_datetime DESC");

// Get system configuration using the system_config functions
require_once 'includes/system_config.php';
$system_name = getSystemName($conn);
$system_logo = getSystemLogo($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR Scanner - <?= htmlspecialchars($system_name) ?></title>
  <?= generateFaviconTags($conn) ?>
  <link rel="stylesheet" href="assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <style>
    .admin-panel-body {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
    }

    /* Mirror the QR scanner camera view */
    #reader video {
      transform: scaleX(-1) !important;
      -webkit-transform: scaleX(-1) !important;
      -moz-transform: scaleX(-1) !important;
      -ms-transform: scaleX(-1) !important;
      -o-transform: scaleX(-1) !important;
    }

    /* Ensure the QR scanner container maintains proper styling */
    #reader {
      position: relative;
      overflow: hidden;
    }

    .admin-header {
      background: none;
      color: var(--gray-900);
      padding: 2rem 0;
      margin-bottom: 2rem;
    }

    .admin-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: var(--gray-900) !important;
    }

    .admin-card {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .admin-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .stat-card-admin {
      background: linear-gradient(135deg, #7c3aed, #6d28d9);
      color: white;
      padding: 2rem;
      border-radius: 1rem;
      text-align: center;
      box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
      transition: all 0.3s ease;
    }

    .stat-card-admin:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 35px rgba(124, 58, 237, 0.4);
    }

    .stat-number-admin {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Countdown Timer Animations */
    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0.3; }
    }

    @keyframes pulse {
      0% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.05); }
      100% { opacity: 1; transform: scale(1); }
    }

    .stat-label-admin {
      font-size: 1rem;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 0.05em;
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

    .info-card {
      background: var(--gray-50);
      padding: var(--spacing-md);
      border-radius: var(--radius-md);
      border: 1px solid var(--gray-200);
      text-align: center;
    }

    .info-label {
      font-weight: 600;
      color: var(--gray-600);
      margin-bottom: var(--spacing-xs);
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .info-value {
      color: var(--gray-900);
      font-size: 1rem;
      font-weight: 600;
    }

    /* Mobile responsive layout for student header */
    @media (max-width: 768px) {
      .student-header-section {
        flex-direction: column !important;
        text-align: center !important;
        align-items: center !important;
      }

      .student-avatar-container {
        order: -1;
        margin-bottom: 1rem;
      }

      .student-name {
        text-align: center !important;
      }
    }
  </style>
</head>
<body class="admin-panel-body has-navbar">
  <?php
  // Include navigation - show for all users (guests can access scanner)
  include 'includes/navigation.php';

  // Determine navigation type and current page
  $nav_user_type = $is_authenticated ? $user_type : 'scanner';
  $user_name = '';

  if ($is_authenticated) {
    $user_name = $_SESSION[$user_type . '_name'] ?? $_SESSION['username'] ?? '';
  }

  renderNavigation($nav_user_type, 'scan', $user_name);
  ?>

  <!-- Admin Header -->
  <div class="admin-header">
    <div class="container text-center">
      <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
        📱 QR Code Scanner
      </h1>
      <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
        Scan student QR codes or enter Student ID manually for attendance tracking
      </p>
    </div>
  </div>

  <div class="container" style="margin-bottom: 3rem;">




    <?php if (!$event_id_selected): ?>
    <!-- Event Selection -->
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon">📅</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Select Event</h3>
        </div>

        <div style="overflow-x: auto; margin-top: 1.5rem;">
          <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <thead>
              <tr style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white;">
                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid var(--primary-dark);">Event</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid var(--primary-dark);">Date & Time</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid var(--primary-dark);">Sections</th>
                <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid var(--primary-dark);">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $event_count = 0;
              while ($event = mysqli_fetch_assoc($event_query)):
                $event_count++;
              ?>
              <tr style="border-bottom: 1px solid var(--gray-200); transition: all 0.2s;" onmouseover="this.style.backgroundColor='var(--gray-50)'; this.style.transform='scale(1.01)'" onmouseout="this.style.backgroundColor='white'; this.style.transform='scale(1)'">
                <td style="padding: 1rem;">
                  <div style="font-weight: 700; font-size: 1.1rem; color: var(--gray-800); margin-bottom: 0.25rem;">
                    <?= htmlspecialchars($event['title']) ?>
                  </div>
                  <div style="color: var(--gray-600); font-size: 0.9rem;">
                    <?= htmlspecialchars($event['description']) ?>
                  </div>
                </td>
                <td style="padding: 1rem;">
                  <div style="font-weight: 600; color: var(--gray-800); margin-bottom: 0.25rem;">
                    📅 <?= date('M j, Y', strtotime($event['start_datetime'])) ?>
                  </div>
                  <div style="color: var(--gray-600); font-size: 0.9rem;">
                    🕐 <?= date('g:i A', strtotime($event['start_datetime'])) ?> - <?= date('g:i A', strtotime($event['end_datetime'])) ?>
                  </div>
                </td>
                <td style="padding: 1rem;">
                  <div style="color: var(--gray-600); font-size: 0.9rem;">
                    <?= htmlspecialchars($event['assigned_sections']) ?>
                  </div>
                </td>
                <td style="padding: 1rem; text-align: center;">
                  <a href="?event_id=<?= $event['id'] ?>" class="btn" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                    📱 Select Event
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>

              <?php if ($event_count === 0): ?>
              <tr>
                <td colspan="4" style="padding: 3rem; text-align: center; color: var(--gray-600);">
                  <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">📅</div>
                  <div style="font-weight: 600; margin-bottom: 0.5rem;">No Events Available</div>
                  <div style="font-size: 0.9rem;">There are currently no events available for scanning.</div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>

    <!-- Scanner Disabled Message (shown after event selection) -->
    <?php if ($scanner_disabled): ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">🚫</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Scanner Currently Disabled</h3>
        </div>

        <div style="background: var(--error-light); padding: 1.5rem; border-radius: var(--radius-lg); border-left: 4px solid var(--error-color);">
          <?php if (!empty($scanner_status['messages'])): ?>
            <?php foreach ($scanner_status['messages'] as $message): ?>
              <p style="margin: 0.5rem 0; color: var(--error-dark);">• <?= htmlspecialchars($message) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="margin: 0; color: var(--error-dark);">The QR scanner and manual entry have been disabled by the administrator.</p>
          <?php endif; ?>
        </div>

        <p style="margin: 1rem 0 0 0; color: var(--gray-600); text-align: center;">
          Please contact your administrator or SBO officers for assistance.
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- QR Scanner Section -->
    <?php if ($qr_scanner_enabled && $scanner_overall_available): ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon">📱</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">QR Code Scanner</h3>
        </div>

        <?php if ($event_id_selected && $event_display_info): ?>
            <!-- Display Start & End Time Info -->
            <div style="background: var(--success-light); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid var(--success-color);">
                <p style="margin: 0; color: var(--success-dark); font-size: 0.9rem;">
                    <strong>📅 Attendance Time:</strong>
                    <?= date('g:i A', strtotime($event_display_info['start_datetime'])) ?> -
                    <?= date('g:i A', strtotime($event_display_info['end_datetime'])) ?>
                    <br>
                    <small>Attendance is only accepted during attendance time period.</small>
                </p>
                <div style="margin-top: 0.5rem; font-weight: 600; color: var(--success-dark);">
                    <small>⏰ <span id="countdownTimer">Calculating...</span></small>
                </div>
            </div>

            <!-- Countdown Script for QR Scanner -->
            <script>
            function startQRCountdown(startTimeStr, endTimeStr) {
                const startTime = new Date(startTimeStr).getTime();
                const endTime = new Date(endTimeStr).getTime();

                function updateQRTimer() {
                    const now = new Date().getTime();
                    const countdownText = document.getElementById("countdownTimer");
                    const scannerArea = document.getElementById("qrSection");

                    // Before event starts - HIDE SCANNER
                    if (now < startTime) {
                        const remaining = startTime - now;
                        const hours = Math.floor(remaining / (1000 * 60 * 60));
                        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

                        countdownText.innerText = "Attendance starts in: " + hours + "h " + minutes + "m " + seconds + "s";

                        // Hide QR scanner section
                        if (scannerArea) {
                            scannerArea.style.display = "none";
                        }
                        return;
                    }

                    // After event ends - DISABLE SCANNER
                    const remaining = endTime - now;
                    if (remaining <= 0) {
                        countdownText.innerText = "Event has ended";

                        // Replace QR scanner with disabled message
                        if (scannerArea) {
                            scannerArea.innerHTML = `
                                <div style="padding: 2rem; text-align: center; background: var(--error-light); border-radius: var(--radius-md); border: 2px solid var(--error-color);">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏰</div>
                                    <h3 style="color: var(--error-color); margin-bottom: 1rem;">Attendance Time Expired</h3>
                                    <p style="color: var(--error-dark);">The attendance period for this event has ended. QR code scanning is no longer available.</p>
                                </div>
                            `;
                        }
                        return;
                    }

                    // Event is active - SHOW TIME REMAINING
                    const hours = Math.floor(remaining / (1000 * 60 * 60));
                    const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

                    countdownText.innerText = "Time remaining: " + hours + "h " + minutes + "m " + seconds + "s";

                    // Show QR scanner section
                    if (scannerArea) {
                        scannerArea.style.display = "block";
                    }
                }

                updateQRTimer();
                setInterval(updateQRTimer, 1000);
            }

            // Start the countdown using event start and end times
            <?php if ($event_display_info): ?>
                startQRCountdown("<?= date('Y-m-d\TH:i:s', strtotime($event_display_info['start_datetime'])) ?>", "<?= date('Y-m-d\TH:i:s', strtotime($event_display_info['end_datetime'])) ?>");
            <?php endif; ?>
            </script>
        <?php endif; ?>

        <div id="qrSection" style="text-align: center;">
          <p style="color: var(--gray-600); margin-bottom: 1.5rem;">Position QR code within the scanner area</p>
          <div id="reader" style="margin: 1rem auto; max-width: 400px; border-radius: var(--radius-lg); overflow: hidden; border: 3px solid var(--primary-color);"></div>

          <form method="POST" id="scanForm" style="display: none;">
            <input type="hidden" name="event_id" value="<?= $event_id_selected ?>">
            <input type="hidden" name="qr_data" id="qr_data">
          </form>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">⚠️</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">QR Scanner Unavailable</h3>
        </div>

        <?php if (!$qr_scanner_enabled): ?>
          <p style="margin: 0; color: var(--gray-600);">QR code scanning has been disabled by the administrator.</p>
        <?php elseif (!$scanner_overall_available): ?>
          <?php if (!empty($scanner_status['messages'])): ?>
            <?php foreach ($scanner_status['messages'] as $status_message): ?>
              <p style="margin: 0 0 1rem 0; color: var(--error-color); font-weight: 600;"><?= htmlspecialchars($status_message) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="margin: 0 0 1rem 0; color: var(--gray-600);">QR code scanning is currently outside available hours.</p>
          <?php endif; ?>

          <!-- Show when it will be available -->
          <?php if (isset($scanner_status['restrictions']['time']) && $scanner_status['restrictions']['time']['enabled']): ?>
          <div style="background: var(--info-light); padding: 1rem; border-radius: var(--radius-md);">
            <p style="margin: 0; color: var(--info-dark); font-size: 0.9rem;">
              <strong>📅 Available Hours:</strong>
              <?= date('g:i A', strtotime($scanner_status['restrictions']['time']['start_time'])) ?> -
              <?= date('g:i A', strtotime($scanner_status['restrictions']['time']['end_time'])) ?>
            </p>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Manual Entry Section -->
    <?php if ($manual_entry_enabled && $scanner_overall_available): ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon" style="background: linear-gradient(135deg, #10b981, #059669);">📝</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Student ID</h3>
        </div>

        <?php if ($event_id_selected && $event_display_info): ?>
            <!-- Display Start & End Time Info -->
            <div style="background: var(--success-light); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid var(--success-color);">
                <p style="margin: 0; color: var(--success-dark); font-size: 0.9rem;">
                    <strong>📅 Attendance Time:</strong>
                    <?= date('g:i A', strtotime($event_display_info['start_datetime'])) ?> -
                    <?= date('g:i A', strtotime($event_display_info['end_datetime'])) ?>
                    <br>
                    <small>Attendance is only accepted during attendance time period.</small>
                </p>
                <div style="margin-top: 0.5rem; font-weight: 600; color: var(--success-dark);">
                    <small>⏰<span id="countdownTimer2">Calculating...</span></small>
                </div>
            </div>

            <!-- Countdown Script -->
            <script>
            function startCountdown(startTimeStr, endTimeStr) {
                const startTime = new Date(startTimeStr).getTime();
                const endTime = new Date(endTimeStr).getTime();

                function updateTimer() {
                    const now = new Date().getTime();
                    const countdownText = document.getElementById("countdownTimer2");
                    const manualArea = document.getElementById("manualSection");

                    // Before event starts - HIDE MANUAL ENTRY
                    if (now < startTime) {
                        const remaining = startTime - now;
                        const hours = Math.floor(remaining / (1000 * 60 * 60));
                        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

                        countdownText.innerText = "Attendance starts in: " + hours + "h " + minutes + "m " + seconds + "s";

                        // Hide manual entry section
                        if (manualArea) {
                            manualArea.style.display = "none";
                        }
                        return;
                    }

                    // After event ends - DISABLE MANUAL ENTRY
                    const remaining = endTime - now;
                    if (remaining <= 0) {
                        countdownText.innerText = "Event has ended";

                        // Replace manual entry with disabled message
                        if (manualArea) {
                            manualArea.innerHTML = `
                                <div style="padding: 2rem; text-align: center; background: var(--error-light); border-radius: var(--radius-md); border: 2px solid var(--error-color);">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏰</div>
                                    <h3 style="color: var(--error-color); margin-bottom: 1rem;">Attendance Time Expired</h3>
                                    <p style="color: var(--error-dark);">The attendance period has ended. Student ID entry is no longer available.</p>
                                </div>
                            `;
                        }
                        return;
                    }

                    // Event is active - SHOW TIME REMAINING
                    const hours = Math.floor(remaining / (1000 * 60 * 60));
                    const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

                    countdownText.innerText = "Time remaining: " + hours + "h " + minutes + "m " + seconds + "s";

                    // Show manual entry section
                    if (manualArea) {
                        manualArea.style.display = "block";
                    }
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            }

            // Start the countdown using event start and end times
            <?php if ($event_display_info): ?>
                startCountdown("<?= date('Y-m-d\TH:i:s', strtotime($event_display_info['start_datetime'])) ?>", "<?= date('Y-m-d\TH:i:s', strtotime($event_display_info['end_datetime'])) ?>");
            <?php endif; ?>
            </script>
        <?php endif; ?>

        <div id="manualSection">
          <p style="color: var(--gray-600); margin-bottom: 1.5rem;">Enter Student ID when QR code scanning is not available</p>

          <form method="POST">
            <input type="hidden" name="event_id" value="<?= $event_id_selected ?>">
            <div class="form-group">
              <input type="text"
                     name="student_id"
                     placeholder="Enter Student ID (e.g., 23-11797)"
                     class="form-input"
                     pattern="[0-9]{2}-[0-9]{5}"
                     title="Format: XX-XXXXX (e.g., 23-11797)"
                     required>
            </div>
            <button type="submit" class="btn btn-success w-full">
              ✅ Record Attendance
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">⚠️</div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Manual Entry Unavailable</h3>
        </div>

        <?php if (!$manual_entry_enabled): ?>
          <p style="margin: 0; color: var(--gray-600);">Manual Student ID entry has been disabled by the administrator.</p>
        <?php elseif (!$scanner_overall_available): ?>
          <?php if (!empty($scanner_status['messages'])): ?>
            <?php foreach ($scanner_status['messages'] as $status_message): ?>
              <p style="margin: 0 0 1rem 0; color: var(--error-color); font-weight: 600;"><?= htmlspecialchars($status_message) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="margin: 0 0 1rem 0; color: var(--gray-600);">Manual entry is currently outside available hours.</p>
          <?php endif; ?>

          <!-- Show when it will be available -->
          <?php if (isset($scanner_status['restrictions']['time']) && $scanner_status['restrictions']['time']['enabled']): ?>
          <div style="background: var(--info-light); padding: 1rem; border-radius: var(--radius-md);">
            <p style="margin: 0; color: var(--info-dark); font-size: 0.9rem;">
              <strong>📅 Available Hours:</strong>
              <?= date('g:i A', strtotime($scanner_status['restrictions']['time']['start_time'])) ?> -
              <?= date('g:i A', strtotime($scanner_status['restrictions']['time']['end_time'])) ?>
            </p>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Results Section -->
    <?php if ($message): ?>
    <div class="admin-card" style="margin-bottom: 3rem;">
      <div style="padding: 2rem;">
        <div class="section-header">
          <div class="section-icon" style="background: linear-gradient(135deg, <?= strpos($message, '✅') !== false ? '#10b981, #059669' : (strpos($message, '⚠️') !== false ? '#f59e0b, #d97706' : '#ef4444, #dc2626') ?>);">
            <?= strpos($message, '✅') !== false ? '✅' : (strpos($message, '⚠️') !== false ? '⚠️' : '❌') ?>
          </div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Scan Result</h3>
        </div>

        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1.5rem; text-align: center;">
          <?= $message ?>
        </div>

        <?php if ($student): ?>
        <!-- Student Details -->
        <div class="admin-card" style="margin-top: 1.5rem;">
          <div style="padding: 2rem;">
            <div class="student-header-section" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); flex-wrap: wrap;">
              <?php
              // Enhanced profile picture detection
              $profile_pic_path = '';
              $profile_pic_found = false;

              // Check multiple possible locations for profile picture
              $possible_paths = [
                  "uploads/profile_pictures/students/student_{$student['student_id']}_*.jpg",
                  "uploads/profile_pictures/students/student_{$student['student_id']}_*.png",
                  "uploads/{$student['student_id']}_*.jpg",
                  "uploads/{$student['student_id']}_*.png",
                  "uploads/{$student['student_id']}.jpg",
                  "uploads/{$student['student_id']}.png"
              ];

              foreach ($possible_paths as $pattern) {
                  $files = glob($pattern);
                  if (!empty($files)) {
                      $profile_pic_path = end($files);
                      if (file_exists($profile_pic_path)) {
                          $profile_pic_found = true;
                          break;
                      }
                  }
              }
              ?>

              <!-- Profile Photo -->
              <div class="student-avatar-container" style="flex-shrink: 0;">
                <?php if ($profile_pic_found && $profile_pic_path): ?>
                  <img src="<?= htmlspecialchars($profile_pic_path) ?>"
                       alt="<?= htmlspecialchars($student['full_name']) ?>"
                       class="student-avatar"
                       style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--success-color);">
                <?php else: ?>
                <div class="student-avatar" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                  👤
                </div>
                <?php endif; ?>
              </div>
              <h3 class="student-name" style="margin: 0; font-size: 1.5rem; font-weight: 700; flex: 1;"><?= htmlspecialchars($student['full_name']) ?></h3>
            </div>

            <!-- Student Information -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
              <div class="info-card">
                <div class="info-label">🆔 Student ID</div>
                <div class="info-value"><?= htmlspecialchars($student['student_id']) ?></div>
              </div>
              <div class="info-card">
                <div class="info-label">📚 Course</div>
                <div class="info-value"><?= htmlspecialchars($student['course']) ?></div>
              </div>
              <div class="info-card">
                <div class="info-label">👥 Section</div>
                <div class="info-value"><?= htmlspecialchars($student['section']) ?></div>
              </div>
              <div class="info-card">
                <div class="info-label">⏰ Scan Time</div>
                <div class="info-value"><?= date('M j, Y g:i:s A') ?></div>
              </div>
            </div>
        </div>
      </div>
      <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem;">
              <a href="scan_qr.php?event_id=<?= $event_id_selected ?>" class="btn btn-primary">
                📱 Scan Another
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Navigation Buttons -->
    <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: center;">
      <a href="scan_qr.php" class="btn btn-secondary" style="width: auto; white-space: nowrap;">
        ← Back to Event Selection
      </a>
      <?php if (!$is_authenticated): ?>
      <a href="index.php" class="btn btn-secondary" style="width: auto; white-space: nowrap;">
        🏠 Back to Home
      </a>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const qrField = document.getElementById("qr_data");
    const form = document.getElementById("scanForm");

    function onScanSuccess(decodedText, decodedResult) {
      // Set the QR data and submit
      qrField.value = decodedText;
      form.submit();
    }

    function onScanFailure(error) {
      // Handle scan failure silently
      console.log("Scan failed:", error);
    }

    // Real-time attendance time checking
    <?php if ($event_id_selected): ?>
    <?php
    // Get event information directly from database
    $event_query = "SELECT title, start_datetime, end_datetime FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $event_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id_selected);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event_info = mysqli_fetch_assoc($event_result);
    ?>

    // Set global event data for navigation countdown
    <?php if ($event_info): ?>
    window.eventEndTimestamp = <?= strtotime($event_info['end_datetime']) ?>;
    window.eventStartTimestamp = <?= strtotime($event_info['start_datetime']) ?>;
    window.eventTitle = '<?= addslashes($event_info['title']) ?>';
    <?php endif; ?>



    function checkAttendanceTime() {
        const now = new Date();
        const currentTimestamp = Math.floor(now.getTime() / 1000);

        // Event time information from PHP (directly from database)
        const eventStartTimestamp = <?= $event_info ? strtotime($event_info['start_datetime']) : 'null' ?>;
        const eventEndTimestamp = <?= $event_info ? strtotime($event_info['end_datetime']) : 'null' ?>;

        if (eventEndTimestamp && currentTimestamp > eventEndTimestamp) {
            // Event has ended - disable scanner immediately
            disableScanner('Event has ended. Attendance is no longer accepted.');
            return false;
        }

        return true;
    }



    function disableScanner(message) {
        // Disable QR scanner
        const qrSection = document.querySelector('.admin-card:has(#reader)');
        const manualSection = document.querySelector('.admin-card:has(input[name="student_id"])');

        if (qrSection) {
            qrSection.innerHTML = `
                <div style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🚫</div>
                    <h3 style="color: var(--error-color); margin-bottom: 1rem;">Scanner Disabled</h3>
                    <p style="color: var(--error-dark);">${message}</p>
                    <p style="margin-top: 1rem; color: var(--gray-600);">
                        <small>Page will refresh automatically...</small>
                    </p>
                </div>
            `;
        }

        if (manualSection) {
            manualSection.innerHTML = `
                <div style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🚫</div>
                    <h3 style="color: var(--error-color); margin-bottom: 1rem;">Student ID Entry Disabled</h3>
                    <p style="color: var(--error-dark);">${message}</p>
                </div>
            `;
        }

        // Also disable any forms to prevent submission
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, button');
            inputs.forEach(input => {
                input.disabled = true;
            });
        });

        // Refresh page after 3 seconds to show server-side disabled state
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    // Initialize countdown timer immediately when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initial check
        checkAttendanceTime();

        // Check attendance time every second
        setInterval(checkAttendanceTime, 1000);
    });

    // Also run initial check immediately (in case DOM is already loaded)
    if (document.readyState === 'loading') {
        // DOM is still loading, wait for DOMContentLoaded
    } else {
        // DOM is already loaded, run immediately
        checkAttendanceTime();
        setInterval(checkAttendanceTime, 1000);
    }
    <?php endif; ?>

    // Initialize QR scanner only if enabled
    <?php if ($event_id_selected && !$message && $qr_scanner_enabled): ?>
    const html5QrCode = new Html5Qrcode("reader");

    Html5Qrcode.getCameras().then(devices => {
      if (devices && devices.length) {
        // Use back camera if available
        const cameraId = devices.length > 1 ? devices[1].id : devices[0].id;

        html5QrCode.start(
          cameraId,
          {
            fps: 10,
            qrbox: { width: 300, height: 300 },
            aspectRatio: 1.0
          },
          onScanSuccess,
          onScanFailure
        ).catch(err => {
          console.error("Failed to start camera:", err);
          document.getElementById("reader").innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--error-color);">❌ Camera access failed. Please use manual entry below.</div>';
        });
      } else {
        console.error("No cameras found");
        document.getElementById("reader").innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--error-color);">❌ No cameras found. Please use manual entry below.</div>';
      }
    }).catch(err => {
      console.error("Error getting cameras:", err);
      document.getElementById("reader").innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--error-color);">❌ Unable to access camera. Please use manual entry below.</div>';
    });
    <?php endif; ?>
  </script>




<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
