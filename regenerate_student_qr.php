<?php
session_start();
include 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if this is a POST request with the correct action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'regenerate_qr') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$student_id = $_SESSION['student_id'];

// Verify the student_id matches the session (security check)
if (isset($_POST['student_id']) && $_POST['student_id'] !== $student_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    // Get updated student information (prioritize official_students for most current info)
    $student_query = mysqli_prepare($conn, "
        SELECT
            s.student_id,
            COALESCE(os.full_name, s.full_name) as full_name,
            COALESCE(os.course, s.course) as course,
            COALESCE(os.section, s.section) as section
        FROM students s
        LEFT JOIN official_students os ON s.student_id = os.student_id
        WHERE s.student_id = ?
    ");
    mysqli_stmt_bind_param($student_query, "s", $student_id);
    mysqli_stmt_execute($student_query);
    $student_result = mysqli_stmt_get_result($student_query);
    $student = mysqli_fetch_assoc($student_result);

    if (!$student) {
        throw new Exception('Student not found');
    }

    // Generate new QR code data with current timestamp
    $qr_data = json_encode([
        'student_id' => $student['student_id'],
        'full_name' => $student['full_name'],
        'course' => $student['course'],
        'section' => $student['section'],
        'timestamp' => time(),
        'hash' => md5($student['student_id'] . date('Y-m-d H:i:s'))
    ]);

    // Create QR code directory if it doesn't exist
    $qr_dir = 'qr_codes/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }

    // Generate QR code filename
    $qr_filename = $qr_dir . "student_{$student['student_id']}.png";

    // Use SimpleQRGenerator for reliable QR generation
    require_once 'simple_qr_generator.php';

    // Generate QR code using the simple generator
    $result = SimpleQRGenerator::generateQRCode($qr_data, $qr_filename, 300);
    if (!$result) {
        throw new Exception('Failed to generate QR code');
    }

    // Try to update the QR code generation timestamp (optional - column may not exist)
    try {
        $update_query = mysqli_prepare($conn, "
            UPDATE students
            SET qr_generated_at = NOW()
            WHERE student_id = ?
        ");
        if ($update_query) {
            mysqli_stmt_bind_param($update_query, "s", $student_id);
            mysqli_stmt_execute($update_query);
        }
    } catch (Exception $e) {
        // Column doesn't exist, ignore
    }

    // Try to log the QR code regeneration (optional - table may not exist)
    try {
        $log_query = mysqli_prepare($conn, "
            INSERT INTO qr_generation_log (student_id, action, timestamp)
            VALUES (?, 'regenerate', NOW())
        ");
        if ($log_query) {
            mysqli_stmt_bind_param($log_query, "s", $student_id);
            mysqli_stmt_execute($log_query);
        }
    } catch (Exception $e) {
        // Table doesn't exist, ignore
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'QR code regenerated successfully',
        'filename' => $qr_filename,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'student_id' => $student_id,
            'qr_dir_exists' => file_exists('qr_codes/'),
            'qr_dir_writable' => is_writable('qr_codes/'),
            'file_path' => isset($qr_filename) ? $qr_filename : 'not_set'
        ]
    ]);
}

// Close database connection
mysqli_close($conn);
?>
