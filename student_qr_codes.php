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

// Get student information (prioritize official_students for most current info)
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

if (!$student) {
    header("Location: student_login.php");
    exit;
}

// Get current and upcoming events for this student's section
$now = date('Y-m-d H:i:s');
$section = $student['section'];

$events_query = mysqli_prepare($conn, "
    SELECT * FROM events
    WHERE FIND_IN_SET(?, assigned_sections) > 0
    AND end_datetime >= ?
    ORDER BY start_datetime ASC
");

mysqli_stmt_bind_param($events_query, "ss", $section, $now);
mysqli_stmt_execute($events_query);
$events_result = mysqli_stmt_get_result($events_query);

// Generate student QR code (not event-specific)
require_once 'simple_qr_generator.php';

// Generate a general student QR code for attendance
$qr_data = json_encode([
    'student_id' => $student['student_id'],
    'full_name' => $student['full_name'],
    'course' => $student['course'],
    'section' => $student['section'],
    'timestamp' => time(),
    'hash' => md5($student['student_id'] . date('Y-m-d'))
]);

// Create QR code filename
$qr_filename = "qr_codes/student_{$student['student_id']}.png";

// Ensure directory exists
if (!file_exists('qr_codes')) {
    mkdir('qr_codes', 0777, true);
}

// Generate QR code if it doesn't exist or is older than 1 day
if (!file_exists($qr_filename) || (time() - filemtime($qr_filename)) > 86400) {
    SimpleQRGenerator::generateQRCode($qr_data, $qr_filename);
}

// Get events for display (but QR is not event-specific)
$events = [];
while ($event = mysqli_fetch_assoc($events_result)) {
    $events[] = $event;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My QR Codes - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="has-navbar">
    <?php renderNavigation('student', 'qr_codes', $student['full_name']); ?>
    
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">📱 My QR Codes</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Your attendance QR codes are automatically generated and ready to use
            </p>
        </div>

        <!-- Student Info Card -->
        <div class="card slide-in-left" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 style="margin: 0;">👤 Student Information</h3>
            </div>
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
                </div>
            </div>
        </div>

        <!-- Student QR Code -->
        <div class="card slide-in-right" style="margin-bottom: 2rem;">
            <div class="card-header text-center">
                <h3 style="margin: 0; color: var(--primary-color);">📱 Your Attendance QR Code</h3>
                <p style="margin: 0.5rem 0 0 0; color: var(--gray-600);">
                    Use this QR code for all event attendance
                </p>
            </div>

            <div class="card-body text-center">
                <?php if (file_exists($qr_filename)): ?>
                    <!-- Student QR Code Display -->
                    <div class="pulse-animation" style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 1.5rem; display: inline-block;">
                        <img src="<?= $qr_filename ?>"
                             alt="Student QR Code for <?= htmlspecialchars($student['full_name']) ?>"
                             style="width: 280px; height: 280px; border: 4px solid var(--primary-color); border-radius: 1rem;">
                    </div>

                    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 0.5rem 0;">✅ QR Code Ready!</h4>
                        <p style="margin: 0; font-size: 0.875rem;">
                            Show this QR code to the scanner at any event for attendance recording.
                        </p>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <button onclick="generateNewQR()" class="btn btn-success">
                            🔄 Generate New QR Code
                        </button>
                        <button onclick="downloadQR('<?= $qr_filename ?>', '<?= $student['student_id'] ?>')"
                                class="btn btn-primary">
                            💾 Download QR Code
                        </button>
                        <button onclick="printQR('<?= $qr_filename ?>')"
                                class="btn btn-outline">
                            🖨️ Print QR Code
                        </button>
                        <button onclick="shareQR()" class="btn btn-secondary">
                            📤 Share QR Code
                        </button>
                    </div>

                <?php else: ?>
                    <!-- QR Code Generation Failed -->
                    <div style="background: var(--error-light); padding: 2rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--error-dark);">QR Code Generation Failed</h4>
                        <p style="margin: 0 0 1rem 0; color: var(--error-dark);">
                            Unable to generate your QR code. Please try refreshing the page.
                        </p>
                        <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                            Debug: Expected file at <?= htmlspecialchars($qr_filename) ?>
                        </p>
                    </div>

                    <button onclick="location.reload()" class="btn btn-primary">
                        🔄 Refresh Page
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <?php if (!empty($events)): ?>
            <div class="card">
                <div class="card-header">
                    <h4 style="margin: 0;">📅 Your Upcoming Events</h4>
                    <p style="margin: 0.5rem 0 0 0; color: var(--gray-600);">
                        Use your QR code above for attendance at these events
                    </p>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($events as $event): ?>
                            <?php
                            // Check if required event fields exist
                            if (!isset($event['start_datetime']) || !isset($event['end_datetime'])) {
                                continue; // Skip events with missing datetime data
                            }

                            $event_datetime = strtotime($event['start_datetime']);
                            $formatted_datetime = date('F j, Y \a\t g:i A', $event_datetime);
                            $time_until = $event_datetime - time();

                            $status_class = '';
                            $status_text = '';
                            $status_icon = '';

                            if ($time_until > 3600) {
                                $status_class = 'alert-info';
                                $status_text = 'Upcoming';
                                $status_icon = '⏰';
                            } elseif ($time_until > 0) {
                                $status_class = 'alert-warning';
                                $status_text = 'Starting Soon';
                                $status_icon = '🔔';
                            } else {
                                $end_time = strtotime($event['end_datetime']);
                                if (time() < $end_time) {
                                    $status_class = 'alert-success';
                                    $status_text = 'Event Started!';
                                    $status_icon = '🎯';
                                } else {
                                    continue; // Skip past events
                                }
                            }

                            // Get event name with fallback
                            $event_name = $event['title'] ?? $event['event_name'] ?? $event['name'] ?? 'Unnamed Event';
                            ?>

                            <div class="alert <?= $status_class ?>" style="margin: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                    <div>
                                        <h5 style="margin: 0 0 0.25rem 0;"><?= $status_icon ?> <?= htmlspecialchars($event_name) ?></h5>
                                        <p style="margin: 0; font-size: 0.875rem; opacity: 0.8;">
                                            📅 <?= $formatted_datetime ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge" style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                            <?= $status_text ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h4 style="margin: 0;">📋 How to Use Your QR Codes</h4>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <h5 style="color: var(--primary-color); margin-bottom: 0.5rem;">1. Wait for Availability</h5>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">
                            QR codes become available 1 hour before each event starts.
                        </p>
                    </div>
                    <div>
                        <h5 style="color: var(--primary-color); margin-bottom: 0.5rem;">2. Show to Scanner</h5>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">
                            Present your QR code to the attendance scanner at the event.
                        </p>
                    </div>
                    <div>
                        <h5 style="color: var(--primary-color); margin-bottom: 0.5rem;">3. Time In/Out</h5>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">
                            Use the same QR code for both time-in and time-out.
                        </p>
                    </div>
                    <div>
                        <h5 style="color: var(--primary-color); margin-bottom: 0.5rem;">4. Keep Secure</h5>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">
                            Don't share your QR codes with others to maintain attendance accuracy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentData = {
            fullName: '<?= htmlspecialchars($student['full_name']) ?>',
            studentId: '<?= htmlspecialchars($student['student_id']) ?>',
            course: '<?= htmlspecialchars($student['course']) ?>',
            section: '<?= htmlspecialchars($student['section']) ?>'
        };

        function downloadQR(filename, studentId) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Downloading...';
            btn.disabled = true;

            try {
                const downloadUrl = 'download_qr.php?type=student&id=' + encodeURIComponent(studentId);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                setTimeout(() => {
                    btn.innerHTML = '✅ Downloaded!';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                }, 500);
            } catch (error) {
                btn.innerHTML = '❌ Download Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }
        }

        function printQR(filename) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Preparing...';
            btn.disabled = true;

            try {
                const img = new Image();
                img.onload = function() {
                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        throw new Error('Popup blocked');
                    }

                    const printContent = '<!DOCTYPE html><html><head><title>ADLOR Student QR Code - Print</title><style>@page { margin: 1cm; size: A4; }body { font-family: Arial, sans-serif; text-align: center; margin: 0; padding: 20px; background: white; color: #333; }.header { margin-bottom: 30px; border-bottom: 3px solid #7c3aed; padding-bottom: 20px; }.logo { font-size: 2rem; font-weight: bold; color: #7c3aed; margin-bottom: 10px; }h2 { color: #333; margin: 0 0 15px 0; font-size: 1.5rem; }.student-details { background: #f8fafc; padding: 15px; border-radius: 10px; margin: 20px 0; border: 2px solid #e2e8f0; }.student-details p { margin: 8px 0; font-weight: 600; color: #4a5568; }.qr-container { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: inline-block; margin: 20px 0; border: 3px solid #7c3aed; }img { width: 250px; height: 250px; border-radius: 10px; }.instructions { background: #e6fffa; padding: 15px; border-radius: 10px; margin-top: 20px; border-left: 4px solid #38b2ac; }.instructions h3 { margin: 0 0 10px 0; color: #2d3748; }.instructions p { margin: 5px 0; font-size: 14px; color: #4a5568; }.footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0; font-size: 12px; color: #718096; }</style></head><body><div class="header"><div class="logo">ADLOR</div><h2>Student Attendance QR Code</h2></div><div class="student-details"><p><strong>Student Name:</strong> ' + studentData.fullName + '</p><p><strong>Student ID:</strong> ' + studentData.studentId + '</p><p><strong>Course:</strong> ' + studentData.course + '</p><p><strong>Section:</strong> ' + studentData.section + '</p></div><div class="qr-container"><img src="' + filename + '" alt="Student QR Code" onload="window.print(); setTimeout(() => window.close(), 1000);"></div><div class="instructions"><h3>📱 How to Use This QR Code</h3><p>• Present this QR code to event organizers for attendance</p><p>• Keep this code accessible on your phone or printed copy</p><p>• This QR code is unique to your student account</p><p>• Valid for all ADLOR system events</p></div><div class="footer"><p>Generated: ' + new Date().toLocaleString() + '</p><p>ADLOR - Attendance Data Logging and Organizing Records</p></div>
<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body></html>';

                    printWindow.document.write(printContent);
                    printWindow.document.close();

                    setTimeout(() => {
                        btn.innerHTML = '✅ Print Ready!';
                        btn.style.background = '#10b981';
                        btn.style.color = 'white';
                        btn.disabled = false;
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.style.background = '';
                            btn.style.color = '';
                        }, 2000);
                    }, 1000);
                };

                img.onerror = function() {
                    btn.innerHTML = '❌ QR Not Found';
                    btn.style.background = '#dc2626';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                };

                img.src = filename;
            } catch (error) {
                btn.innerHTML = '❌ Print Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }
        }

        function shareQR() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Sharing...';
            btn.disabled = true;

            if (navigator.share) {
                navigator.share({
                    title: 'My ADLOR QR Code',
                    text: 'Student QR Code for ADLOR Attendance System',
                    url: window.location.href
                }).then(() => {
                    btn.innerHTML = '✅ Shared!';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                }).catch(() => {
                    copyToClipboard(btn, originalText);
                });
            } else {
                copyToClipboard(btn, originalText);
            }
        }

        function copyToClipboard(btn, originalText) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                btn.innerHTML = '✅ Link Copied!';
                btn.style.background = '#10b981';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(() => {
                btn.innerHTML = '❌ Share Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            });
        }

        function generateNewQR() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Generating...';
            btn.disabled = true;

            // Create form data for QR regeneration
            const formData = new FormData();
            formData.append('action', 'regenerate_qr');
            formData.append('student_id', '<?= $student['student_id'] ?>');

            fetch('regenerate_student_qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '✅ Generated!';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';

                    // Reload page after 1 second to show new QR code
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    btn.innerHTML = '❌ Failed';
                    btn.style.background = '#dc2626';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                }
            })
            .catch(error => {
                btn.innerHTML = '❌ Error';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            });
        }

        setTimeout(() => {
            location.reload();
        }, 86400000);
    </script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
