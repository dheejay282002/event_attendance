<?php
session_start();
date_default_timezone_set('Asia/Manila');

include 'db_connect.php';
include 'simple_qr_generator.php'; // Include QR library

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

// No event ID needed - generate general student QR code
$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo "❌ Student not found.";
    exit;
}

// Generate student QR code data (not event-specific)
$payload = json_encode([
    'student_id' => $student['student_id'],
    'full_name' => $student['full_name'],
    'course' => $student['course'],
    'section' => $student['section'],
    'timestamp' => time(),
    'hash' => md5($student['student_id'] . date('Y-m-d'))
]);

// Generate QR code using simple generator
$qr_dir = 'qr_codes';
if (!is_dir($qr_dir)) mkdir($qr_dir, 0777, true);
$qr_filename = "$qr_dir/student_" . $student_id . ".png";

// Use SimpleQRGenerator for reliable QR generation
SimpleQRGenerator::generateQRCode($payload, $qr_filename);
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
  <title>QR Code Generated - ADLOR</title>
  <link rel="stylesheet" href="assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <div style="min-height: 100vh; background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%); padding: 2rem;">
    <div class="container-sm">
      <!-- Header -->
      <div class="text-center" style="margin-bottom: 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 0.5rem; font-size: 2rem;">
          📱 Your Student QR Code
        </h1>
        <p style="color: var(--gray-600); margin: 0;">
          Use this QR code for attendance at all events
        </p>
      </div>

      <!-- Main QR Card -->
      <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body text-center">
          <!-- QR Code Display -->
          <div style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 2rem; display: inline-block;">
            <img src="<?= $qr_filename ?>"
                 alt="Student QR Code for <?= htmlspecialchars($student['full_name']) ?>"
                 style="width: 280px; height: 280px; border: 4px solid var(--primary-color); border-radius: 1rem; background: white;">
          </div>

          <!-- Student Info -->
          <div style="background: var(--success-light); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem;">
            <h3 style="color: var(--success-dark); margin-bottom: 1rem; font-size: 1.125rem;">
              👤 Student Information
            </h3>
            <div style="text-align: left; color: var(--success-dark);">
              <p style="margin-bottom: 0.5rem;"><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
              <p style="margin-bottom: 0.5rem;"><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
              <p style="margin-bottom: 0.5rem;"><strong>Course:</strong> <?= htmlspecialchars($student['course']) ?></p>
              <p style="margin: 0;"><strong>Section:</strong> <?= htmlspecialchars($student['section']) ?></p>
            </div>
          </div>

          <!-- Instructions -->
          <div style="background: var(--primary-light); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem;">
            <h3 style="color: var(--primary-dark); margin-bottom: 1rem; font-size: 1.125rem;">
              📋 How to Use This QR Code
            </h3>
            <div style="text-align: left; color: var(--primary-dark);">
              <p style="margin-bottom: 0.5rem;">• Use this QR code for attendance at any event</p>
              <p style="margin-bottom: 0.5rem;">• Show this QR code to the attendance scanner</p>
              <p style="margin-bottom: 0.5rem;">• Keep your phone screen bright and steady</p>
              <p style="margin-bottom: 0.5rem;">• Wait for the confirmation message</p>
              <p style="margin: 0;">• QR code works for both time-in and time-out</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Student Information Card -->
      <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
          <h3 style="margin: 0; color: var(--gray-800);">👤 Student Information</h3>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem;">
              <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem;">Full Name</div>
              <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($student['full_name']) ?></div>
            </div>
            <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem;">
              <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem;">Student ID</div>
              <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($student['student_id']) ?></div>
            </div>
            <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem;">
              <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem;">Course</div>
              <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($student['course']) ?></div>
            </div>
            <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem;">
              <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem;">Section</div>
              <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($student['section']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="student_dashboard.php" class="btn btn-primary" style="min-width: 160px;">
          ← Back to Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-outline" style="min-width: 160px;">
          🖨️ Print QR Code
        </button>
        <button onclick="downloadQR()" class="btn btn-secondary" style="min-width: 160px;">
          💾 Save Image
        </button>
      </div>

      <!-- Security Notice -->
      <div class="alert alert-warning">
        <h4 style="margin: 0 0 0.5rem 0; color: var(--warning-dark);">🔒 Security Notice</h4>
        <p style="margin: 0; font-size: 0.875rem; color: var(--warning-dark);">
          This QR code is unique to you and valid only for the specific event.
          Do not share this code with others as it may compromise attendance accuracy.
        </p>
      </div>
    </div>
  </div>

  <script>
    function downloadQR() {
      const link = document.createElement('a');
      link.href = '<?= $qr_filename ?>';
      link.download = 'qr_code_<?= $student['student_id'] ?>_event_<?= $event_id ?>.png';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>

  <style>
    @media print {
      body {
        background: white !important;
        padding: 1rem !important;
      }

      .container-sm {
        max-width: none !important;
        padding: 0 !important;
      }

      /* Hide non-essential elements */
      .btn, .alert, script {
        display: none !important;
      }

      /* Show only QR code and student info */
      .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        margin-bottom: 1rem !important;
        page-break-inside: avoid;
      }

      /* Optimize QR code for printing */
      img {
        max-width: 300px !important;
        height: auto !important;
        border: 2px solid #000 !important;
      }

      /* Ensure text is readable */
      h1, h2, h3, h4, h5, h6, p, div {
        color: #000 !important;
      }

      /* Page break control */
      .card:first-child {
        page-break-after: avoid;
      }
    }
  </style>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
