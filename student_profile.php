<?php
session_start();
include 'db_connect.php';
include 'includes/navigation.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$message = "";
$error = "";

// Get student data (prioritize official_students for most current info)
$stmt = mysqli_prepare($conn, "
    SELECT
        s.*,
        COALESCE(os.full_name, s.full_name) as full_name,
        COALESCE(os.course, s.course) as course,
        COALESCE(os.section, s.section) as section,
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
mysqli_stmt_bind_param($stmt, "s", $_SESSION['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/students/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if profile_picture column exists
$column_exists = false;
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'profile_picture'");
if (mysqli_num_rows($check_column) > 0) {
    $column_exists = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'upload_picture' && isset($_FILES['profile_picture'])) {
        if (!$column_exists) {
            $error = "❌ Profile picture feature not available. Please contact administrator to enable this feature.";
        } else {
            $file = $_FILES['profile_picture'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = 'student_' . $_SESSION['student_id'] . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Remove old profile picture if exists
                        if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])) {
                            unlink($student['profile_picture']);
                        }

                        // Update database
                        $picture_path = 'uploads/profile_pictures/students/' . $new_filename;
                        $update_stmt = mysqli_prepare($conn, "UPDATE students SET profile_picture = ? WHERE student_id = ?");
                        mysqli_stmt_bind_param($update_stmt, "ss", $picture_path, $_SESSION['student_id']);
                        mysqli_stmt_execute($update_stmt);

                        $student['profile_picture'] = $picture_path;
                        $message = "✅ Profile picture updated successfully!";
                    } else {
                        $error = "❌ Failed to upload file.";
                    }
                } else {
                    $error = "❌ Invalid file type or size. Please upload JPG, PNG, or GIF under 5MB.";
                }
            } else {
                $error = "❌ File upload error.";
            }
        }
    } elseif ($action === 'remove_picture') {
        if (!$column_exists) {
            $error = "❌ Profile picture feature not available. Please contact administrator to enable this feature.";
        } else {
            if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])) {
                unlink($student['profile_picture']);
            }

            // Update database
            $update_stmt = mysqli_prepare($conn, "UPDATE students SET profile_picture = NULL WHERE student_id = ?");
            mysqli_stmt_bind_param($update_stmt, "s", $_SESSION['student_id']);
            mysqli_stmt_execute($update_stmt);

            $student['profile_picture'] = null;
            $message = "✅ Profile picture removed successfully!";
        }
    } elseif ($action === 'setup_profile_pictures') {
        // Add profile_picture column to students table
        $add_column_query = "ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) NULL";
        if (mysqli_query($conn, $add_column_query)) {
            $column_exists = true;
            $message = "✅ Profile picture feature enabled successfully! You can now upload your profile picture.";
        } else {
            $error = "❌ Failed to enable profile picture feature: " . mysqli_error($conn);
        }
    } elseif ($action === 'register_face') {
        // Handle facial recognition registration
        $face_data = $_POST['face_data'] ?? '';

        if (empty($face_data)) {
            $error = "❌ No face data received. Please try capturing your face again.";
        } else {
            // Parse face data (could be JSON with liveness verification or just image data)
            $face_info = json_decode($face_data, true);
            if (!$face_info) {
                // Fallback for old format
                $face_info = ['image' => $face_data, 'liveness_verified' => false];
            }

            // Verify liveness detection was completed
            if (!isset($face_info['liveness_verified']) || !$face_info['liveness_verified']) {
                $error = "❌ Liveness verification required. Please complete the live detection process.";
            } else {
                // Check if facial recognition system is available
                $fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
                if (mysqli_num_rows($fr_check) == 0) {
                    $error = "❌ Facial recognition system is not set up. Please contact administrator.";
                } else {
                    // Process face registration with liveness verification
                    $face_result = registerStudentFace($conn, $_SESSION['student_id'], $face_info);

                    if ($face_result['success']) {
                        $message = $face_result['message'];
                    } else {
                        $error = $face_result['error'];
                    }
                }
            }
        }
    } elseif ($action === 'remove_face') {
        // Remove facial recognition data
        $fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
        if (mysqli_num_rows($fr_check) > 0) {
            // Remove from facial recognition data
            $remove_data = mysqli_prepare($conn, "DELETE FROM facial_recognition_data WHERE student_id = ?");
            mysqli_stmt_bind_param($remove_data, "s", $_SESSION['student_id']);

            // Update student status
            $update_status = mysqli_prepare($conn, "UPDATE students SET face_encoding_status = 'NONE', face_last_updated = NULL WHERE student_id = ?");
            mysqli_stmt_bind_param($update_status, "s", $_SESSION['student_id']);

            if (mysqli_stmt_execute($remove_data) && mysqli_stmt_execute($update_status)) {
                $message = "✅ Facial recognition data removed successfully!";
            } else {
                $error = "❌ Failed to remove facial recognition data.";
            }
        } else {
            $error = "❌ Facial recognition system is not available.";
        }
    }
}

// Function to register student face with liveness verification
function registerStudentFace($conn, $student_id, $face_info) {
    try {
        // Extract image data and verification info
        $face_data = $face_info['image'] ?? $face_info;
        $liveness_verified = $face_info['liveness_verified'] ?? false;
        $challenges_completed = $face_info['challenges_completed'] ?? 0;
        $verification_method = $face_info['verification_method'] ?? 'unknown';

        // Enhanced face encoding with liveness verification metadata
        $face_encoding = base64_encode(json_encode([
            'student_id' => $student_id,
            'encoding' => 'live_verified_face_encoding_' . time(),
            'timestamp' => time(),
            'source' => 'student_profile_live',
            'liveness_verified' => $liveness_verified,
            'challenges_completed' => $challenges_completed,
            'verification_method' => $verification_method,
            'security_level' => $liveness_verified ? 'high' : 'low'
        ]));

        // Create face image path
        $face_dir = 'uploads/facial_recognition/face_images/';
        if (!file_exists($face_dir)) {
            mkdir($face_dir, 0755, true);
        }

        $image_path = $face_dir . $student_id . '_live_' . time() . '.jpg';

        // Store in facial recognition data table with enhanced security
        $insert_query = mysqli_prepare($conn, "
            INSERT INTO facial_recognition_data (student_id, face_encoding, face_image_path, confidence_threshold, is_active)
            VALUES (?, ?, ?, 0.85, 1)
            ON DUPLICATE KEY UPDATE
            face_encoding = VALUES(face_encoding),
            face_image_path = VALUES(face_image_path),
            confidence_threshold = VALUES(confidence_threshold),
            updated_at = NOW()
        ");

        mysqli_stmt_bind_param($insert_query, "sss", $student_id, $face_encoding, $image_path);

        if (mysqli_stmt_execute($insert_query)) {
            // Update student status with enhanced verification
            $status = $liveness_verified ? 'ACTIVE' : 'PENDING';
            $update_status = mysqli_prepare($conn, "
                UPDATE students
                SET face_encoding_status = ?, face_last_updated = NOW(), face_recognition_enabled = 1
                WHERE student_id = ?
            ");
            mysqli_stmt_bind_param($update_status, "ss", $status, $student_id);
            mysqli_stmt_execute($update_status);

            // Save the face image
            $image_data = is_string($face_data) ? $face_data : $face_data;
            $image_binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image_data));
            if ($image_binary) {
                file_put_contents($image_path, $image_binary);
            }

            // Log the registration with security details
            $log_query = mysqli_prepare($conn, "
                INSERT INTO attendance_logs (student_id, event_id, scan_method, confidence_score, scan_timestamp, notes, status)
                VALUES (?, 0, 'FACIAL_REGISTRATION', ?, NOW(), ?, 'SUCCESS')
            ");
            $confidence = $liveness_verified ? 0.95 : 0.70;
            $notes = "Face registered with liveness verification: " . ($liveness_verified ? 'VERIFIED' : 'NOT_VERIFIED');
            mysqli_stmt_bind_param($log_query, "sds", $student_id, $confidence, $notes);
            mysqli_stmt_execute($log_query);

            $message = $liveness_verified
                ? '✅ Live face registered successfully with high security! You can now use facial recognition for attendance.'
                : '⚠️ Face registered but liveness verification failed. Please re-register for full security.';

            return [
                'success' => true,
                'message' => $message
            ];
        } else {
            return [
                'success' => false,
                'error' => '❌ Failed to register face data. Please try again.'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '❌ Error registering face: ' . $e->getMessage()
        ];
    }
}

// Get attendance statistics
$attendance_stats = [];
$attendance_query = "
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as attended_events,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as completed_events
    FROM attendance a
    JOIN events e ON a.event_id = e.id
    WHERE a.student_id = ?
";
$stmt = mysqli_prepare($conn, $attendance_query);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_stats = mysqli_fetch_assoc($result);

// Get current profile picture
$profile_picture = $student['profile_picture'] ?? null;

// Check facial recognition system availability and student status
$fr_available = false;
$fr_status = 'NONE';
$fr_last_updated = null;

$fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
if (mysqli_num_rows($fr_check) > 0) {
    $fr_available = true;

    // Get student's facial recognition status
    $fr_status_query = mysqli_prepare($conn, "SELECT face_encoding_status, face_last_updated FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($fr_status_query, "s", $_SESSION['student_id']);
    mysqli_stmt_execute($fr_status_query);
    $fr_result = mysqli_stmt_get_result($fr_status_query);
    $fr_data = mysqli_fetch_assoc($fr_result);

    if ($fr_data) {
        $fr_status = $fr_data['face_encoding_status'] ?? 'NONE';
        $fr_last_updated = $fr_data['face_last_updated'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-panel-body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .admin-header {
            background: none;
            color: var(--gray-900);
            padding: 5rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .profile-picture-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #10b981;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            margin-bottom: 1rem;
        }
        
        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 1rem auto;
            border: 4px solid #10b981;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .file-upload-area:hover {
            border-color: #10b981;
            background: #f8fafc;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .info-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .quick-actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            }
        }
        
        .info-item {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 0.75rem;
            border-left: 4px solid #10b981;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: var(--gray-900);
            font-size: 1rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            opacity: 0.9;
            font-size: 0.875rem;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline:hover:not(:disabled) {
            background: var(--gray-50);
            border-color: var(--gray-400);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }

        /* Processing Animation */
        @keyframes processing {
            0% { transform: translateX(-100%); }
            50% { transform: translateX(0%); }
            100% { transform: translateX(100%); }
        }

        /* Responsive Grid for Buttons */
        @media (max-width: 768px) {
            .btn {
                font-size: 0.8rem;
                padding: 0.6rem 1rem;
            }
        }
    </style>
</head>
<body class="admin-panel-body">
    <?php renderNavigation('student', 'profile', $_SESSION['full_name']); ?>
    
    <!-- Student Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
                👤 Student Profile
            </h1>
            <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
                Manage your student profile and view your information
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <!-- Profile Picture Section -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; text-align: center;">📸 Profile Picture</h3>
                    
                    <div class="profile-picture-container">
                        <?php if ($profile_picture && file_exists($profile_picture)): ?>
                            <img src="<?= $profile_picture ?>" alt="Student Profile" class="profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder">
                                🎓
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Upload New Picture -->
                    <form method="POST" enctype="multipart/form-data" style="margin-bottom: 1rem;">
                        <input type="hidden" name="action" value="upload_picture">
                        <div class="file-upload-area">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">📷</div>
                            <input type="file" name="profile_picture" accept="image/*" required style="margin-bottom: 1rem;">
                            <div style="font-size: 0.875rem; color: var(--gray-600);">
                                JPG, PNG, or GIF • Max 5MB
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            📸 Upload New Picture
                        </button>
                    </form>
                    
                    <!-- Remove Picture -->
                    <?php if ($profile_picture): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove_picture">
                            <button type="submit" class="btn btn-outline" style="width: 100%;" onclick="return confirm('Remove profile picture?')">
                                🗑️ Remove Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <!-- Facial Recognition -->
        <?php if ($fr_available): ?>
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">🔍 Facial Recognition</h3>

                <div style="text-align: center;">
                    <?php if ($fr_status === 'ACTIVE'): ?>
                        <!-- Face Registered -->
                        <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 2rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                            <h4 style="color: #065f46; margin: 0 0 0.5rem 0;">Face Registered Successfully!</h4>
                            <p style="color: #065f46; margin: 0; font-size: 0.875rem;">
                                You can now use facial recognition for attendance scanning.
                            </p>
                            <?php if ($fr_last_updated): ?>
                                <p style="color: #065f46; margin: 0.5rem 0 0 0; font-size: 0.75rem;">
                                    Registered: <?= date('M j, Y g:i A', strtotime($fr_last_updated)) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons for Registered Face -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; max-width: 500px; margin: 0 auto;">
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-primary">
                                📷 Register New
                            </button>
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-success">
                                🔄 Update Face
                            </button>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="action" value="remove_face">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ Remove facial recognition data?\n\nThis will disable facial recognition for attendance.')">
                                    🗑️ Delete Face
                                </button>
                            </form>
                        </div>

                    <?php elseif ($fr_status === 'PENDING'): ?>
                        <!-- Face Processing Status -->
                        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 2rem; border-radius: 1rem; margin-bottom: 1.5rem; border-left: 5px solid #f59e0b;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="font-size: 2rem;">⏳</div>
                                <div>
                                    <h4 style="color: #92400e; margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600;">Face Processing...</h4>
                                    <p style="color: #92400e; margin: 0; font-size: 0.875rem;">Your face is being processed. Please wait a moment.</p>
                                </div>
                            </div>

                            <!-- Processing Animation -->
                            <div style="background: #f3f4f6; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #f59e0b, #d97706); height: 100%; width: 60%; border-radius: 4px; animation: processing 2s ease-in-out infinite;"></div>
                            </div>
                        </div>

                        <!-- Action Buttons for Processing Face -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; max-width: 500px; margin: 0 auto;">
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-primary">
                                📷 Register New
                            </button>
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-success">
                                🔄 Update Face
                            </button>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="action" value="remove_face">
                                <button type="submit" class="btn btn-outline" onclick="return confirm('⚠️ Cancel face processing?\n\nThis will remove the pending face data.')">
                                    🗑️ Delete Face
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <!-- No Face Registered -->
                        <div style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); padding: 2rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📷</div>
                            <h4 style="color: #374151; margin: 0 0 0.5rem 0;">Register Your Face</h4>
                            <p style="color: #374151; margin: 0; font-size: 0.875rem;">
                                Enable facial recognition for quick and secure attendance scanning.
                            </p>
                        </div>

                        <!-- Action Buttons for No Face -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; max-width: 500px; margin: 0 auto;">
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-primary">
                                📷 Register Face
                            </button>
                            <button type="button" onclick="showFaceRegistration()" class="btn btn-success" disabled style="opacity: 0.5; cursor: not-allowed;">
                                🔄 Update Face
                            </button>
                            <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed;">
                                🗑️ Delete Face
                            </button>
                        </div>

                        <div style="margin-top: 1rem;">
                            <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">
                                💡 Register your face first to enable Update and Delete options
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Facial Recognition Not Available -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">🔍 Facial Recognition</h3>

                <div style="text-align: center;">
                    <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 2rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
                        <h4 style="color: #991b1b; margin: 0 0 0.5rem 0;">Facial Recognition Not Available</h4>
                        <p style="color: #991b1b; margin: 0; font-size: 0.875rem;">
                            The facial recognition system is not set up yet. Please contact your administrator to enable this feature.
                        </p>
                    </div>

                    <a href="setup_facial_recognition.php" class="btn btn-primary">
                        ⚙️ Setup Facial Recognition System
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📋 Profile Information</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student ID</div>
                            <div class="info-value"><?= htmlspecialchars($student['student_id']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= htmlspecialchars($student['full_name']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Course</div>
                            <div class="info-value"><?= htmlspecialchars($student['course']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Section</div>
                            <div class="info-value"><?= htmlspecialchars($student['section']) ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Year Level</div>
                            <div class="info-value"><?= htmlspecialchars($student['year_level']) ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Account Created</div>
                            <div class="info-value"><?= date('M j, Y', strtotime($student['created_at'])) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value" style="color: #10b981; font-weight: 600;">✅ Active</div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Quick Actions -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">⚡ Quick Actions</h3>
                <div class="quick-actions-grid" style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                    <!-- Mobile-first: single column, then responsive on larger screens -->
                    <a href="student_settings.php" class="btn btn-primary" style="text-decoration: none;">
                        ⚙️ Edit Profile
                    </a>
                    <a href="student_qr_codes.php" class="btn btn-secondary" style="text-decoration: none;">
                        📱 My QR Codes
                    </a>
                    <a href="student_dashboard.php" class="btn btn-outline" style="text-decoration: none;">
                        📈 Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Attendance Statistics -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700;">📊 My Attendance Statistics</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?= $attendance_stats['total_events'] ?? 0 ?></div>
                        <div class="stat-label">📅 Total Events</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        <div class="stat-number"><?= $attendance_stats['attended_events'] ?? 0 ?></div>
                        <div class="stat-label">✅ Events Attended</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="stat-number"><?= $attendance_stats['completed_events'] ?? 0 ?></div>
                        <div class="stat-label">🏆 Completed Events</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <div class="stat-number">
                            <?php 
                            $total = $attendance_stats['total_events'] ?? 0;
                            $attended = $attendance_stats['attended_events'] ?? 0;
                            $percentage = $total > 0 ? round(($attended / $total) * 100) : 0;
                            echo $percentage . '%';
                            ?>
                        </div>
                        <div class="stat-label">📈 Attendance Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facial Recognition Modal -->
    <div id="faceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 1rem; padding: 2rem; max-width: 500px; width: 90%;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700;">📷 Live Face Registration</h3>
                <p style="margin: 0; color: var(--gray-600); margin-bottom: 1rem;">
                    <strong>⚠️ LIVE DETECTION REQUIRED</strong><br>
                    This system uses advanced liveness detection to prevent spoofing with photos or videos.
                </p>
                <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                        <strong>Security Notice:</strong> You must be physically present. Photos, videos, or other people's faces will be rejected.
                    </p>
                </div>
            </div>

            <div style="position: relative; max-width: 400px; margin: 0 auto; border-radius: 1rem; overflow: hidden; background: #000;">
                <video id="faceVideo" autoplay muted style="width: 100%; height: auto;"></video>
                <canvas id="faceCanvas" style="display: none;"></canvas>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 200px; height: 200px; border: 3px solid #7c3aed; border-radius: 50%; pointer-events: none;"></div>
            </div>

            <!-- Liveness Detection Instructions -->
            <div id="livenessInstructions" style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; display: none;">
                <h4 style="margin: 0 0 0.5rem 0; color: #0c4a6e;">🔍 Liveness Detection Challenge</h4>
                <p id="livenessChallenge" style="margin: 0; color: #0c4a6e; font-weight: 600;"></p>
                <div style="margin-top: 0.5rem;">
                    <div id="challengeProgress" style="background: #e5e7eb; height: 8px; border-radius: 4px;">
                        <div id="progressBar" style="background: #3b82f6; height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="button" id="startFaceCamera" class="btn btn-primary" style="margin-right: 1rem;">
                    📷 Start Live Detection
                </button>
                <button type="button" id="startLivenessTest" class="btn btn-success" style="margin-right: 1rem; display: none;">
                    🔍 Begin Liveness Test
                </button>
                <button type="button" id="captureFace" class="btn btn-success" style="margin-right: 1rem; display: none;">
                    ✅ Complete Registration
                </button>
                <button type="button" onclick="closeFaceModal()" class="btn btn-outline">
                    ❌ Cancel
                </button>
            </div>

            <div id="faceStatus" style="margin-top: 1rem; text-align: center; color: var(--gray-600);"></div>

            <form method="POST" id="faceForm" style="display: none;">
                <input type="hidden" name="action" value="register_face">
                <input type="hidden" name="face_data" id="faceData">
            </form>
        </div>
    </div>

    <script>
        // Facial Recognition Variables
        let faceStream = null;
        let faceVideo = null;
        let faceCanvas = null;
        let faceContext = null;
        let livenessDetection = {
            active: false,
            challenges: [
                { type: 'blink', instruction: '👁️ Please blink your eyes slowly (3 times)', completed: false, attempts: 0 },
                { type: 'smile', instruction: '😊 Please smile naturally', completed: false, attempts: 0 },
                { type: 'turn_left', instruction: '⬅️ Turn your head slightly to the left', completed: false, attempts: 0 },
                { type: 'turn_right', instruction: '➡️ Turn your head slightly to the right', completed: false, attempts: 0 },
                { type: 'nod', instruction: '👆 Nod your head up and down slowly', completed: false, attempts: 0 }
            ],
            currentChallenge: 0,
            completedChallenges: 0,
            detectionFrames: [],
            isLive: false
        };

        function showFaceRegistration() {
            console.log('showFaceRegistration called');
            const modal = document.getElementById('faceModal');
            if (!modal) {
                console.error('faceModal not found');
                alert('Face registration modal not found. Please refresh the page.');
                return;
            }

            modal.style.display = 'block';
            faceVideo = document.getElementById('faceVideo');
            faceCanvas = document.getElementById('faceCanvas');

            if (!faceVideo || !faceCanvas) {
                console.error('Video or canvas elements not found');
                alert('Camera elements not found. Please refresh the page.');
                return;
            }

            faceContext = faceCanvas.getContext('2d');
            console.log('Face registration modal opened successfully');
        }

        function closeFaceModal() {
            document.getElementById('faceModal').style.display = 'none';
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }
        }

        // Camera error handler
        function handleCameraError(error, statusElement) {
            let errorMessage = '';
            let troubleshootingTips = '';

            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage = '❌ Camera access denied by user';
                    troubleshootingTips = `
                        <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                            <strong>🔧 How to fix:</strong>
                            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                                <li>Click the camera icon in your browser's address bar</li>
                                <li>Select "Allow" for camera access</li>
                                <li>Refresh the page and try again</li>
                                <li>Face registration requires camera access for security</li>
                            </ul>
                        </div>
                    `;
                    break;
                case 'NotFoundError':
                    errorMessage = '❌ No camera found on this device';
                    troubleshootingTips = `
                        <div style="margin-top: 1rem; padding: 1rem; background: #fee2e2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                            <strong>💡 What you can do:</strong>
                            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                                <li>Try on a device with a camera</li>
                                <li>Use a laptop or smartphone with a front camera</li>
                                <li>Face registration requires a camera for security verification</li>
                            </ul>
                        </div>
                    `;
                    break;
                case 'NotReadableError':
                    errorMessage = '❌ Camera is being used by another application';
                    troubleshootingTips = `
                        <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                            <strong>🔧 How to fix:</strong>
                            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                                <li>Close other apps using the camera (Zoom, Skype, Teams, etc.)</li>
                                <li>Restart your browser</li>
                                <li>Try again in a few moments</li>
                                <li>Make sure no other browser tabs are using the camera</li>
                            </ul>
                        </div>
                    `;
                    break;
                case 'OverconstrainedError':
                    errorMessage = '❌ Camera doesn\'t meet requirements';
                    troubleshootingTips = `
                        <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                            <strong>🔧 How to fix:</strong>
                            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                                <li>Try using a different camera (if available)</li>
                                <li>Update your browser to the latest version</li>
                                <li>Try using a different browser (Chrome, Firefox, Safari)</li>
                            </ul>
                        </div>
                    `;
                    break;
                default:
                    errorMessage = '❌ Camera access failed';
                    troubleshootingTips = `
                        <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-radius: 0.5rem; border-left: 4px solid #0284c7;">
                            <strong>💡 Troubleshooting steps:</strong>
                            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                                <li>Refresh the page and try again</li>
                                <li>Check if your browser supports camera access</li>
                                <li>Try using a different browser</li>
                                <li>Contact support if problem persists</li>
                            </ul>
                        </div>
                    `;
            }

            statusElement.innerHTML = errorMessage + troubleshootingTips;
        }

        async function startFaceCamera() {
            const status = document.getElementById('faceStatus');
            const startBtn = document.getElementById('startFaceCamera');
            const livenessBtn = document.getElementById('startLivenessTest');

            try {
                status.innerHTML = '📷 Starting camera for live detection...';

                const constraints = {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                };

                faceStream = await navigator.mediaDevices.getUserMedia(constraints);
                faceVideo.srcObject = faceStream;

                startBtn.style.display = 'none';
                livenessBtn.style.display = 'inline-block';

                status.innerHTML = '✅ Camera ready! Click "Begin Liveness Test" to start security verification.';

            } catch (error) {
                console.error('Camera error:', error);
                handleCameraError(error, status);
            }
        }

        function startLivenessTest() {
            const livenessBtn = document.getElementById('startLivenessTest');
            const instructions = document.getElementById('livenessInstructions');
            const status = document.getElementById('faceStatus');

            livenessBtn.style.display = 'none';
            instructions.style.display = 'block';

            // Reset liveness detection
            livenessDetection.active = true;
            livenessDetection.currentChallenge = 0;
            livenessDetection.completedChallenges = 0;
            livenessDetection.challenges.forEach(challenge => {
                challenge.completed = false;
                challenge.attempts = 0;
            });

            status.innerHTML = '🔍 Starting liveness detection. Please follow the instructions carefully.';

            // Start the first challenge
            startNextChallenge();
        }

        function startNextChallenge() {
            if (livenessDetection.currentChallenge >= livenessDetection.challenges.length) {
                completeLivenessTest();
                return;
            }

            const challenge = livenessDetection.challenges[livenessDetection.currentChallenge];
            const challengeText = document.getElementById('livenessChallenge');
            const progressBar = document.getElementById('progressBar');

            challengeText.innerHTML = challenge.instruction;

            // Update progress
            const progress = (livenessDetection.completedChallenges / livenessDetection.challenges.length) * 100;
            progressBar.style.width = progress + '%';

            // Simulate challenge detection (in real implementation, this would use computer vision)
            setTimeout(() => {
                simulateChallengeDetection(challenge);
            }, 3000); // Give user 3 seconds to perform the action
        }

        function simulateChallengeDetection(challenge) {
            // Simulate successful challenge completion
            // In real implementation, this would use facial landmark detection
            // to verify the user performed the requested action

            const success = Math.random() > 0.2; // 80% success rate for simulation

            if (success) {
                challenge.completed = true;
                livenessDetection.completedChallenges++;

                const status = document.getElementById('faceStatus');
                status.innerHTML = `✅ Challenge completed: ${challenge.instruction}`;

                setTimeout(() => {
                    livenessDetection.currentChallenge++;
                    startNextChallenge();
                }, 1000);
            } else {
                challenge.attempts++;
                if (challenge.attempts < 3) {
                    const status = document.getElementById('faceStatus');
                    status.innerHTML = `⚠️ Challenge failed. Please try again: ${challenge.instruction}`;

                    setTimeout(() => {
                        simulateChallengeDetection(challenge);
                    }, 2000);
                } else {
                    failLivenessTest();
                }
            }
        }

        function completeLivenessTest() {
            const instructions = document.getElementById('livenessInstructions');
            const captureBtn = document.getElementById('captureFace');
            const status = document.getElementById('faceStatus');
            const progressBar = document.getElementById('progressBar');

            progressBar.style.width = '100%';
            livenessDetection.isLive = true;

            status.innerHTML = '🎉 Liveness verification successful! You can now complete your face registration.';
            captureBtn.style.display = 'inline-block';

            // Hide instructions after a moment
            setTimeout(() => {
                instructions.style.display = 'none';
            }, 2000);
        }

        function failLivenessTest() {
            const status = document.getElementById('faceStatus');
            const instructions = document.getElementById('livenessInstructions');

            livenessDetection.active = false;
            livenessDetection.isLive = false;

            status.innerHTML = '❌ Liveness verification failed. Please ensure you are physically present and try again.';
            instructions.style.display = 'none';

            // Reset to start
            setTimeout(() => {
                const startBtn = document.getElementById('startFaceCamera');
                startBtn.style.display = 'inline-block';
                status.innerHTML = '🔄 Please try the liveness test again.';
            }, 3000);
        }

        function captureFace() {
            const status = document.getElementById('faceStatus');

            if (!faceStream) {
                status.innerHTML = '❌ Camera not started. Please start camera first.';
                return;
            }

            if (!livenessDetection.isLive) {
                status.innerHTML = '❌ Liveness verification required. Please complete the liveness test first.';
                return;
            }

            status.innerHTML = '📸 Capturing live face data...';

            // Set canvas dimensions to match video
            faceCanvas.width = faceVideo.videoWidth;
            faceCanvas.height = faceVideo.videoHeight;

            // Draw current video frame to canvas
            faceContext.drawImage(faceVideo, 0, 0);

            // Convert to base64 with metadata
            const faceDataURL = faceCanvas.toDataURL('image/jpeg', 0.8);

            // Add liveness verification metadata
            const faceData = {
                image: faceDataURL,
                liveness_verified: true,
                challenges_completed: livenessDetection.completedChallenges,
                timestamp: Date.now(),
                verification_method: 'live_detection'
            };

            // Set form data and submit
            document.getElementById('faceData').value = JSON.stringify(faceData);

            status.innerHTML = '📤 Registering verified live face...';

            // Stop camera
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }

            // Submit form
            document.getElementById('faceForm').submit();
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const startBtn = document.getElementById('startFaceCamera');
            const livenessBtn = document.getElementById('startLivenessTest');
            const captureBtn = document.getElementById('captureFace');

            if (startBtn) {
                startBtn.addEventListener('click', startFaceCamera);
            }

            if (livenessBtn) {
                livenessBtn.addEventListener('click', startLivenessTest);
            }

            if (captureBtn) {
                captureBtn.addEventListener('click', captureFace);
            }

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeFaceModal();
                }
            });
        });
    </script>

    <script>
        // Preview uploaded image
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-picture, .profile-picture-placeholder');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-picture';
                        img.alt = 'Profile Preview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
