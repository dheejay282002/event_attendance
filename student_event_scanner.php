<?php
session_start();
include 'db_connect.php';
include 'includes/navigation.php';

date_default_timezone_set('Asia/Manila');

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$message = "";
$error = "";
$scan_result = "";

// Get student information (prioritize official_students for most current info)
$student_id = $_SESSION['student_id'];
$student_query = mysqli_prepare($conn, "
    SELECT
        s.student_id,
        COALESCE(os.full_name, s.full_name) as full_name,
        COALESCE(os.course, s.course) as course,
        COALESCE(os.section, s.section) as section,
        s.profile_picture
    FROM students s
    LEFT JOIN official_students os ON s.student_id = os.student_id
    WHERE s.student_id = ?
");
mysqli_stmt_bind_param($student_query, "s", $student_id);
mysqli_stmt_execute($student_query);
$student_result = mysqli_stmt_get_result($student_query);
$student = mysqli_fetch_assoc($student_result);

// Handle QR code scanning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    if (!empty($qr_data)) {
        // Try to decode JSON QR data
        $decoded_data = json_decode($qr_data, true);
        
        if ($decoded_data && isset($decoded_data['type']) && $decoded_data['type'] === 'event') {
            $event_id = $decoded_data['event_id'];
            
            // Verify event exists and student is assigned to it
            $event_query = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
            mysqli_stmt_bind_param($event_query, "i", $event_id);
            mysqli_stmt_execute($event_query);
            $event_result = mysqli_stmt_get_result($event_query);
            $event = mysqli_fetch_assoc($event_result);
            
            if ($event) {
                // Check if event has started (server-side validation)
                $current_timestamp = time();
                $event_start_timestamp = strtotime($event['start_datetime']);
                $event_end_timestamp = strtotime($event['end_datetime']);

                if ($current_timestamp < $event_start_timestamp) {
                    $error = "❌ Event has not started yet. Attendance will be available from " . date('M j, Y g:i A', $event_start_timestamp) . ".";
                } elseif ($current_timestamp > $event_end_timestamp) {
                    $error = "❌ Event has ended. Attendance is no longer accepted.";
                } else {
                    // Check if student's section is assigned to this event
                    $assigned_sections = array_map('trim', explode(',', $event['assigned_sections']));
                    $student_section = trim($student['section']);

                    if (in_array($student_section, $assigned_sections)) {
                    // Check if student already has attendance for this event
                    $attendance_check = mysqli_prepare($conn, "SELECT * FROM attendance WHERE student_id = ? AND event_id = ?");
                    mysqli_stmt_bind_param($attendance_check, "si", $student_id, $event_id);
                    mysqli_stmt_execute($attendance_check);
                    $attendance_result = mysqli_stmt_get_result($attendance_check);
                    
                    if (mysqli_num_rows($attendance_result) > 0) {
                        $attendance = mysqli_fetch_assoc($attendance_result);
                        if ($attendance['time_out'] === null) {
                            // Time out
                            $update_query = mysqli_prepare($conn, "UPDATE attendance SET time_out = NOW() WHERE student_id = ? AND event_id = ?");
                            mysqli_stmt_bind_param($update_query, "si", $student_id, $event_id);
                            mysqli_stmt_execute($update_query);
                            $scan_result = "time_out";
                            $message = "✅ Time out recorded successfully for: " . htmlspecialchars($event['title']);
                        } else {
                            $error = "⚠️ You have already completed attendance for this event.";
                        }
                    } else {
                        // Time in
                        $insert_query = mysqli_prepare($conn, "INSERT INTO attendance (student_id, event_id, time_in) VALUES (?, ?, NOW())");
                        mysqli_stmt_bind_param($insert_query, "si", $student_id, $event_id);
                        mysqli_stmt_execute($insert_query);
                        $scan_result = "time_in";
                        $message = "✅ Time in recorded successfully for: " . htmlspecialchars($event['title']);
                    }
                } else {
                    $error = "❌ You are not assigned to this event. Your section: " . htmlspecialchars($student_section);
                }
            }
            } else {
                $error = "❌ Event not found or invalid QR code.";
            }
        } else {
            $error = "❌ Invalid QR code format. Please scan a valid event QR code.";
        }
    } else {
        $error = "❌ Please scan or enter QR code data.";
    }
}

// Get recent attendance for this student
$recent_attendance_query = mysqli_prepare($conn, "
    SELECT a.*, e.title, e.start_datetime, e.end_datetime 
    FROM attendance a 
    JOIN events e ON a.event_id = e.id 
    WHERE a.student_id = ? 
    ORDER BY a.time_in DESC 
    LIMIT 5
");
mysqli_stmt_bind_param($recent_attendance_query, "s", $student_id);
mysqli_stmt_execute($recent_attendance_query);
$recent_attendance_result = mysqli_stmt_get_result($recent_attendance_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('includes/system_config.php')) {
        include 'includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event QR Scanner - ADLOR</title>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .scanner-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .scanner-area {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px dashed var(--primary-color);
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .qr-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.75rem;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .qr-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .scan-result {
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
        
        .scan-result.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .scan-result.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 2px solid #dc2626;
        }

        .student-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .student-photo-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 700;
            border: 4px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .scan-result-with-photo {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            text-align: left;
        }

        .scan-result-content {
            flex: 1;
        }
        
        .attendance-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .time-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .time-in {
            background: #d1fae5;
            color: #065f46;
        }
        
        .time-out {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Camera Scanner Styles */
        #qr-reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        #qr-reader video {
            width: 100%;
            height: auto;
            border-radius: 1rem;
        }

        .camera-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .scanner-tabs {
            display: flex;
            background: var(--gray-100);
            border-radius: 0.75rem;
            padding: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .scanner-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .scanner-tab.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .scanner-content {
            display: none;
        }

        .scanner-content.active {
            display: block;
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('student', 'scanner', $_SESSION['full_name']); ?>
    
    <!-- Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
                📱 Event QR Scanner
            </h1>
            <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
                Scan event QR codes to record your attendance
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="scan-result success">
                <div class="scan-result-with-photo">
                    <!-- Student Photo -->
                    <div>
                        <?php if (isset($student['profile_picture']) && !empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($student['profile_picture']) ?>"
                                 alt="Student Photo" class="student-photo">
                        <?php else: ?>
                            <div class="student-photo-placeholder">
                                <?= strtoupper(substr($student['full_name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Scan Result Content -->
                    <div class="scan-result-content">
                        <div style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <?= $message ?>
                        </div>
                        <div style="font-size: 1rem; opacity: 0.8;">
                            <strong><?= htmlspecialchars($student['full_name']) ?></strong> (<?= htmlspecialchars($student['student_id']) ?>)
                        </div>
                        <div style="font-size: 0.875rem; opacity: 0.7; margin-top: 0.25rem;">
                            <?= htmlspecialchars($student['course']) ?> - <?= htmlspecialchars($student['section']) ?>
                        </div>
                        <?php if ($scan_result): ?>
                            <div style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: rgba(255,255,255,0.3); border-radius: 0.5rem; display: inline-block;">
                                <span style="font-size: 0.875rem; font-weight: 600;">
                                    <?= $scan_result === 'time_in' ? '⏰ TIME IN' : '⏰ TIME OUT' ?>
                                </span>
                                <span style="font-size: 0.875rem; margin-left: 0.5rem;">
                                    <?= date('g:i A') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="scan-result error">
                <div class="scan-result-with-photo">
                    <!-- Student Photo -->
                    <div>
                        <?php if (isset($student['profile_picture']) && !empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($student['profile_picture']) ?>"
                                 alt="Student Photo" class="student-photo" style="border-color: #dc2626;">
                        <?php else: ?>
                            <div class="student-photo-placeholder" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border-color: #dc2626;">
                                <?= strtoupper(substr($student['full_name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Error Content -->
                    <div class="scan-result-content">
                        <div style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <?= $error ?>
                        </div>
                        <div style="font-size: 1rem; opacity: 0.8;">
                            <strong><?= htmlspecialchars($student['full_name']) ?></strong> (<?= htmlspecialchars($student['student_id']) ?>)
                        </div>
                        <div style="font-size: 0.875rem; opacity: 0.7; margin-top: 0.25rem;">
                            <?= htmlspecialchars($student['course']) ?> - <?= htmlspecialchars($student['section']) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- QR Scanner -->
        <div class="scanner-container">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; text-align: center;">📷 Scan Event QR Code</h3>
                
                <div class="scanner-area">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📱</div>
                    <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">Scan Event QR Code</h4>
                    <p style="margin: 0 0 2rem 0; color: var(--gray-600);">
                        Use your camera to scan the event QR code or manually enter the QR data
                    </p>

                    <!-- Scanner Tabs -->
                    <div class="scanner-tabs">
                        <button type="button" class="scanner-tab active" onclick="switchTab('camera')">
                            📷 Camera Scanner
                        </button>
                        <button type="button" class="scanner-tab" onclick="switchTab('manual')">
                            ⌨️ Manual Entry
                        </button>
                    </div>

                    <!-- Camera Scanner -->
                    <div id="camera-scanner" class="scanner-content active">
                        <div id="qr-reader"></div>
                        <div class="camera-controls">
                            <button type="button" id="start-camera" class="btn btn-primary">
                                📷 Start Camera
                            </button>
                            <button type="button" id="stop-camera" class="btn btn-outline" style="display: none;">
                                ⏹️ Stop Camera
                            </button>
                        </div>
                        <div id="camera-status" style="margin-top: 1rem; text-align: center; color: var(--gray-600);"></div>
                    </div>

                    <!-- Manual Entry -->
                    <div id="manual-scanner" class="scanner-content">
                        <form method="POST" id="manual-form">
                            <textarea name="qr_data"
                                      id="qr-data-input"
                                      class="qr-input"
                                      placeholder="Paste QR code data here or scan with camera above..."
                                      rows="4"
                                      required></textarea>
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                                🎯 Process Attendance
                            </button>
                        </form>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                        💡 <strong>Tip:</strong> Ask event organizers to show you the event QR code to scan
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Recent Attendance -->
        <div class="scanner-container" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📋 Recent Attendance</h3>
                
                <?php if (mysqli_num_rows($recent_attendance_result) > 0): ?>
                    <?php while ($attendance = mysqli_fetch_assoc($recent_attendance_result)): ?>
                        <div class="attendance-card">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.75rem;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900);">
                                        <?= htmlspecialchars($attendance['title']) ?>
                                    </h4>
                                    <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                                        📅 <?= date('M j, Y', strtotime($attendance['start_datetime'])) ?> • 
                                        🕐 <?= date('g:i A', strtotime($attendance['start_datetime'])) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div>
                                <span class="time-badge time-in">
                                    ⏰ In: <?= date('g:i A', strtotime($attendance['time_in'])) ?>
                                </span>
                                <?php if ($attendance['time_out']): ?>
                                    <span class="time-badge time-out">
                                        ⏰ Out: <?= date('g:i A', strtotime($attendance['time_out'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="time-badge" style="background: #fef3c7; color: #92400e;">
                                        ⏳ Not yet timed out
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">No attendance records yet</h4>
                        <p style="margin: 0; font-size: 1rem;">Start scanning event QR codes to build your attendance history!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let isScanning = false;

        // Tab switching functionality
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.scanner-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update content
            document.querySelectorAll('.scanner-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName + '-scanner').classList.add('active');

            // Stop camera if switching away from camera tab
            if (tabName !== 'camera' && isScanning) {
                stopCamera();
            }
        }

        // Camera functionality
        function startCamera() {
            const startBtn = document.getElementById('start-camera');
            const stopBtn = document.getElementById('stop-camera');
            const status = document.getElementById('camera-status');

            if (isScanning) return;

            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-block';
            status.innerHTML = '📷 Starting camera...';

            // Initialize QR code scanner
            html5QrcodeScanner = new Html5Qrcode("qr-reader");

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            html5QrcodeScanner.start(
                { facingMode: "environment" }, // Use back camera
                config,
                (decodedText, decodedResult) => {
                    // QR code successfully scanned
                    status.innerHTML = '✅ QR Code detected! Processing...';

                    // Fill the manual form and submit
                    document.getElementById('qr-data-input').value = decodedText;

                    // Stop camera
                    stopCamera();

                    // Auto-submit the form
                    setTimeout(() => {
                        document.getElementById('manual-form').submit();
                    }, 500);
                },
                (errorMessage) => {
                    // QR code scan error (this is normal when no QR code is in view)
                    // Don't show error messages for normal scanning
                }
            ).then(() => {
                isScanning = true;
                status.innerHTML = '📱 Point camera at QR code';
            }).catch((err) => {
                console.error('Camera start error:', err);
                status.innerHTML = '❌ Camera access denied or not available';
                startBtn.style.display = 'inline-block';
                stopBtn.style.display = 'none';
            });
        }

        function stopCamera() {
            if (!isScanning || !html5QrcodeScanner) return;

            const startBtn = document.getElementById('start-camera');
            const stopBtn = document.getElementById('stop-camera');
            const status = document.getElementById('camera-status');

            html5QrcodeScanner.stop().then(() => {
                isScanning = false;
                startBtn.style.display = 'inline-block';
                stopBtn.style.display = 'none';
                status.innerHTML = '📷 Camera stopped';
                html5QrcodeScanner = null;
            }).catch((err) => {
                console.error('Camera stop error:', err);
                status.innerHTML = '⚠️ Error stopping camera';
            });
        }

        // Event listeners
        document.getElementById('start-camera').addEventListener('click', startCamera);
        document.getElementById('stop-camera').addEventListener('click', stopCamera);

        // Auto-start camera when page loads (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Check if camera is supported
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                document.getElementById('camera-status').innerHTML = '📷 Camera ready - click "Start Camera" to begin scanning';
            } else {
                document.getElementById('camera-status').innerHTML = '❌ Camera not supported on this device';
                document.getElementById('start-camera').disabled = true;
                // Switch to manual tab
                switchTab('manual');
                document.querySelector('.scanner-tab[onclick="switchTab(\'manual\')"]').classList.add('active');
                document.querySelector('.scanner-tab[onclick="switchTab(\'camera\')"]').classList.remove('active');
            }
        });

        // Clean up camera when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (isScanning) {
                stopCamera();
            }
        });
    </script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
