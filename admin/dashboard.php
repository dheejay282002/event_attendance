<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get system statistics
$stats = [];

// Total students
$students_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
$stats['total_students'] = mysqli_fetch_assoc($students_result)['count'];

// Total events
$events_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM events");
$stats['total_events'] = mysqli_fetch_assoc($events_result)['count'];

// Total attendance records
$attendance_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance");
$stats['total_attendance'] = mysqli_fetch_assoc($attendance_result)['count'];

// Total SBO users
$sbo_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM sbo_users WHERE is_active = 1");
$stats['total_sbo_users'] = mysqli_fetch_assoc($sbo_result)['count'];

// Current academic year
$current_academic_year_result = mysqli_query($conn, "SELECT academic_year FROM academic_calendar WHERE is_current = 1 LIMIT 1");
if ($current_academic_year_result && mysqli_num_rows($current_academic_year_result) > 0) {
    $stats['current_academic_year'] = mysqli_fetch_assoc($current_academic_year_result)['academic_year'];
} else {
    // Fallback to current year if no academic calendar is set
    $current_year = date('Y');
    $stats['current_academic_year'] = $current_year . '-' . ($current_year + 1);
}

// Recent activity
$recent_events_query = "SELECT * FROM events ORDER BY created_at DESC LIMIT 5";
$recent_events = mysqli_query($conn, $recent_events_query);

// System info
$db_size_query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' FROM information_schema.tables WHERE table_schema = DATABASE()";
$db_size_result = mysqli_query($conn, $db_size_query);
$db_size = mysqli_fetch_assoc($db_size_result)['db_size'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ADLOR</title>
    <?= generateFaviconTags($conn) ?>
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
        
        .stat-label-admin {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .action-button {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
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
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
            text-decoration: none;
            color: white;
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
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'dashboard', $_SESSION['admin_name']); ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
                ⚙️ Admin Dashboard
            </h1>
            <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
                System Administration & Management
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <!-- System Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <div class="stat-card-admin" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <div class="stat-number-admin"><?= $stats['total_students'] ?></div>
                <div class="stat-label-admin">👥 Total Students</div>
            </div>
            
            <div class="stat-card-admin" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="stat-number-admin"><?= $stats['total_events'] ?></div>
                <div class="stat-label-admin">📅 Total Events</div>
            </div>
            
            <div class="stat-card-admin" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="stat-number-admin"><?= $stats['total_attendance'] ?></div>
                <div class="stat-label-admin">📊 Attendance Records</div>
            </div>
            
            <div class="stat-card-admin" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <div class="stat-number-admin"><?= $stats['total_sbo_users'] ?></div>
                <div class="stat-label-admin">👤 SBO Users</div>
            </div>

            <div class="stat-card-admin" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                <div class="stat-number-admin" style="font-size: 1.8rem;">A.Y. <?= $stats['current_academic_year'] ?></div>
                <div class="stat-label-admin">📅 Academic Year</div>
            </div>
        </div>

        <!-- Admin Quick Actions -->
        <div class="admin-card" style="margin-bottom: 3rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🚀</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Admin Quick Actions</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                    <a href="manage_students.php" class="action-button">
                        👥 Manage Students
                    </a>
                    <a href="data_management.php" class="action-button" style="background: linear-gradient(135deg, #06b6d4, #0891b2); box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);">
                        📥 Import Student Data
                    </a>
                    <a href="manage_sbo.php" class="action-button" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                        👥 Manage SBO Users
                    </a>
                    <a href="scanner_settings.php" class="action-button" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                        📱 Scanner Settings
                    </a>
                    <a href="../database_admin.php" class="action-button" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                        🗄️ Database Admin
                    </a>
                    <a href="../sbo/dashboard.php" class="action-button" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);">
                        👥 SBO Dashboard
                    </a>
                    <a href="../scan_qr.php" class="action-button" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                        📱 QR Scanner
                    </a>
                </div>
            </div>
        </div>

        <!-- Facial Recognition Management -->
        <?php
        // Check if facial recognition system is available
        $fr_available = false;
        $fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
        if (mysqli_num_rows($fr_check) > 0) {
            $fr_available = true;

            // Get facial recognition statistics
            $fr_stats_query = "
                SELECT
                    (SELECT COUNT(*) FROM students WHERE face_encoding_status = 'ACTIVE') as enrolled_students,
                    (SELECT COUNT(*) FROM students WHERE face_encoding_status = 'PENDING') as pending_students,
                    (SELECT COUNT(*) FROM attendance_logs WHERE scan_method IN ('FACIAL_RECOGNITION', 'FACIAL_RECOGNITION_LIVE') AND DATE(scan_timestamp) = CURDATE()) as today_face_scans,
                    (SELECT COUNT(*) FROM attendance_logs WHERE scan_method IN ('FACIAL_RECOGNITION', 'FACIAL_RECOGNITION_LIVE')) as total_face_scans
            ";
            $fr_stats_result = mysqli_query($conn, $fr_stats_query);
            $fr_stats = mysqli_fetch_assoc($fr_stats_result);
        }
        ?>

        <?php if ($fr_available): ?>
        <div class="admin-card" style="margin-bottom: 3rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🔍</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Facial Recognition Management</h3>
                </div>

             
                <!-- FR Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #1e40af; margin-bottom: 0.25rem;"><?= $fr_stats['enrolled_students'] ?></div>
                        <div style="color: #1e40af; font-weight: 600; font-size: 0.75rem;">Enrolled Students</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #92400e; margin-bottom: 0.25rem;"><?= $fr_stats['pending_students'] ?></div>
                        <div style="color: #92400e; font-weight: 600; font-size: 0.75rem;">Pending</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #065f46; margin-bottom: 0.25rem;"><?= $fr_stats['today_face_scans'] ?></div>
                        <div style="color: #065f46; font-weight: 600; font-size: 0.75rem;">Today's Scans</div>
                    </div>

                    <div style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #3730a3; margin-bottom: 0.25rem;"><?= $fr_stats['total_face_scans'] ?></div>
                        <div style="color: #3730a3; font-weight: 600; font-size: 0.75rem;">Total Scans</div>
                    </div>
                </div>

                <!-- FR Management Buttons -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../facial_recognition_admin.php" class="action-button" style="background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);">
                        📷 Register Face
                    </a>
                    <a href="../facial_recognition_admin.php" class="action-button" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                        🔄 Update Face
                    </a>
                    <a href="../facial_recognition_admin.php" class="action-button" style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);">
                        🗑️ Delete Face
                    </a>
                    <a href="../scan_face_live.php" class="action-button" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                        📷 Face Scanner
                    </a>
                    <a href="view_attendance.php" class="action-button" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                        📋 View Attendance
                    </a>
                    <a href="../setup_facial_recognition.php" class="action-button" style="background: linear-gradient(135deg, #6b7280, #4b5563); box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);">
                        ⚙️ FR Settings
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Facial Recognition Not Available -->
        <div class="admin-card" style="margin-bottom: 3rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🔍</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Facial Recognition Management</h3>
                </div>

                <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; border-left: 5px solid #ef4444;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem;">⚙️</div>
                        <div>
                            <h4 style="margin: 0; color: #991b1b; font-size: 1.125rem; font-weight: 600;">System Not Available</h4>
                            <p style="margin: 0; color: #991b1b; font-size: 0.875rem;">Facial recognition system needs to be set up first.</p>
                        </div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="setup_facial_recognition.php" class="action-button" style="background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);">
                        ⚙️ Setup Facial Recognition
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <!-- Recent Events -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">📅</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Recent Events</h3>
                    </div>
                    
                    <?php if (mysqli_num_rows($recent_events) > 0): ?>
                        <?php while ($event = mysqli_fetch_assoc($recent_events)): ?>
                            <div style="padding: 1.5rem; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius: 1rem; margin-bottom: 1rem; border-left: 5px solid #0ea5e9;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #0c4a6e; font-size: 1.125rem; font-weight: 600;">
                                    <?= htmlspecialchars($event['title']) ?>
                                </h4>
                                <p style="margin: 0; color: #0c4a6e; font-size: 0.875rem;">
                                    <?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            <p style="margin: 0;">No events created yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="admin-card">
                <div style="padding: 2rem;">
                    <div class="section-header">
                        <div class="section-icon">💻</div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">System Information</h3>
                    </div>
                    
                    <div style="space-y: 1rem;">
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: var(--gray-50); border-radius: 0.5rem; margin-bottom: 1rem;">
                            <span style="font-weight: 600;">Database Size:</span>
                            <span><?= $db_size ?> MB</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: var(--gray-50); border-radius: 0.5rem; margin-bottom: 1rem;">
                            <span style="font-weight: 600;">PHP Version:</span>
                            <span><?= phpversion() ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: var(--gray-50); border-radius: 0.5rem; margin-bottom: 1rem;">
                            <span style="font-weight: 600;">Server Time:</span>
                            <span><?= date('M j, Y g:i A') ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: var(--gray-50); border-radius: 0.5rem;">
                            <span style="font-weight: 600;">System Status:</span>
                            <span style="color: #10b981; font-weight: 600;">✅ Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
