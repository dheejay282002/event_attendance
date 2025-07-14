<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_qr_scanner':
                $enabled = isset($_POST['qr_scanner_enabled']) ? 1 : 0;
                $update_query = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, updated_at = NOW() WHERE setting_name = 'qr_scanner_enabled'");
                mysqli_stmt_bind_param($update_query, "i", $enabled);
                if (mysqli_stmt_execute($update_query)) {
                    $message = "QR Scanner " . ($enabled ? "enabled" : "disabled") . " successfully!";
                } else {
                    $error = "Failed to update QR Scanner setting.";
                }
                break;
                
            case 'toggle_manual_entry':
                $enabled = isset($_POST['manual_id_enabled']) ? 1 : 0;
                $update_query = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, updated_at = NOW() WHERE setting_name = 'manual_id_entry_enabled'");
                mysqli_stmt_bind_param($update_query, "i", $enabled);
                if (mysqli_stmt_execute($update_query)) {
                    $message = "Manual ID Entry " . ($enabled ? "enabled" : "disabled") . " successfully!";
                } else {
                    $error = "Failed to update Manual ID Entry setting.";
                }
                break;

            case 'toggle_facial_recognition':
                // Check if facial recognition system is available
                $fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
                if (mysqli_num_rows($fr_check) == 0) {
                    $error = "Facial recognition system is not set up. Please set it up first.";
                } else {
                    $enabled = isset($_POST['facial_recognition_enabled']) ? 1 : 0;

                    // Check if setting exists, if not create it
                    $check_setting = mysqli_query($conn, "SELECT id FROM scanner_settings WHERE setting_name = 'facial_recognition_enabled'");
                    if (mysqli_num_rows($check_setting) == 0) {
                        $insert_query = mysqli_prepare($conn, "INSERT INTO scanner_settings (setting_name, is_enabled, created_at, updated_at) VALUES ('facial_recognition_enabled', ?, NOW(), NOW())");
                        mysqli_stmt_bind_param($insert_query, "i", $enabled);
                        mysqli_stmt_execute($insert_query);
                    } else {
                        $update_query = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, updated_at = NOW() WHERE setting_name = 'facial_recognition_enabled'");
                        mysqli_stmt_bind_param($update_query, "i", $enabled);
                        mysqli_stmt_execute($update_query);
                    }

                    $message = "Facial Recognition " . ($enabled ? "enabled" : "disabled") . " successfully!";
                }
                break;

            case 'update_time_schedule':
                $schedule_enabled = isset($_POST['schedule_enabled']) ? 1 : 0;
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                
                // Update schedule enabled setting
                $update_schedule = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, updated_at = NOW() WHERE setting_name = 'scanner_schedule_enabled'");
                mysqli_stmt_bind_param($update_schedule, "i", $schedule_enabled);
                mysqli_stmt_execute($update_schedule);
                
                // Update time restriction
                $update_time = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, start_time = ?, end_time = ?, updated_at = NOW() WHERE setting_name = 'scanner_time_restriction'");
                mysqli_stmt_bind_param($update_time, "iss", $schedule_enabled, $start_time, $end_time);
                
                if (mysqli_stmt_execute($update_time)) {
                    $message = "Time schedule updated successfully!";
                } else {
                    $error = "Failed to update time schedule.";
                }
                break;
                
            case 'update_date_schedule':
                $date_enabled = isset($_POST['date_enabled']) ? 1 : 0;
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $update_date = mysqli_prepare($conn, "UPDATE scanner_settings SET is_enabled = ?, start_date = ?, end_date = ?, updated_at = NOW() WHERE setting_name = 'scanner_date_restriction'");
                mysqli_stmt_bind_param($update_date, "iss", $date_enabled, $start_date, $end_date);
                
                if (mysqli_stmt_execute($update_date)) {
                    $message = "Date schedule updated successfully!";
                } else {
                    $error = "Failed to update date schedule.";
                }
                break;
        }
    }
}

// Get current settings
$settings_query = "SELECT * FROM scanner_settings";
$settings_result = mysqli_query($conn, $settings_query);
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_name']] = $row;
}

// Check if facial recognition system is available
$fr_available = false;
$fr_check = mysqli_query($conn, "SHOW TABLES LIKE 'facial_recognition_data'");
if (mysqli_num_rows($fr_check) > 0) {
    $fr_available = true;

    // Ensure facial recognition setting exists
    if (!isset($settings['facial_recognition_enabled'])) {
        $insert_fr_setting = mysqli_query($conn, "INSERT INTO scanner_settings (setting_name, is_enabled, created_at, updated_at) VALUES ('facial_recognition_enabled', 0, NOW(), NOW())");
        if ($insert_fr_setting) {
            $settings['facial_recognition_enabled'] = ['setting_name' => 'facial_recognition_enabled', 'is_enabled' => 0];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('../includes/system_config.php')) {
        include '../includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Settings - ADLOR Admin</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .settings-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .settings-content {
            padding: 2rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--gray-800);
            font-size: 1.125rem;
        }

        .setting-info p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .schedule-form {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 1rem;
            margin-top: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-enabled {
            background: var(--success-light);
            color: var(--success-dark);
        }

        .status-disabled {
            background: var(--error-light);
            color: var(--error-dark);
        }

        .status-scheduled {
            background: var(--warning-light);
            color: var(--warning-dark);
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('admin', 'scanner', $_SESSION['admin_name']); ?>
    
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">📱 Scanner Settings</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Control QR scanner and manual ID entry availability
            </p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Quick Controls -->
        <div class="settings-card">
            <div class="settings-header">
                <span style="font-size: 1.5rem;">🎛️</span>
                <h3 style="margin: 0;">Quick Controls</h3>
            </div>
            <div class="settings-content">
                <!-- QR Scanner Toggle -->
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>📱 QR Code Scanner</h4>
                        <p>Enable or disable camera-based QR code scanning</p>
                        <div class="status-indicator <?= $settings['qr_scanner_enabled']['is_enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                            <?= $settings['qr_scanner_enabled']['is_enabled'] ? '✅ Enabled' : '❌ Disabled' ?>
                        </div>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_qr_scanner">
                        <label class="toggle-switch">
                            <input type="checkbox" name="qr_scanner_enabled" 
                                   <?= $settings['qr_scanner_enabled']['is_enabled'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                    </form>
                </div>

                <!-- Manual ID Entry Toggle -->
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>⌨️ Manual ID Entry</h4>
                        <p>Enable or disable manual student ID input</p>
                        <div class="status-indicator <?= $settings['manual_id_entry_enabled']['is_enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                            <?= $settings['manual_id_entry_enabled']['is_enabled'] ? '✅ Enabled' : '❌ Disabled' ?>
                        </div>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_manual_entry">
                        <label class="toggle-switch">
                            <input type="checkbox" name="manual_id_enabled" 
                                   <?= $settings['manual_id_entry_enabled']['is_enabled'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                    </form>
                </div>

                <!-- Facial Recognition Toggle -->
                <?php if ($fr_available): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>🔍 Facial Recognition</h4>
                        <p>Enable or disable facial recognition scanning for attendance</p>
                        <div class="status-indicator <?= $settings['facial_recognition_enabled']['is_enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                            <?= $settings['facial_recognition_enabled']['is_enabled'] ? '✅ Enabled' : '❌ Disabled' ?>
                        </div>
                        <?php if ($settings['facial_recognition_enabled']['is_enabled']): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-600);">
                                <strong>Note:</strong> Students must register their faces in their profile settings first.
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_facial_recognition">
                        <label class="toggle-switch">
                            <input type="checkbox" name="facial_recognition_enabled"
                                   <?= $settings['facial_recognition_enabled']['is_enabled'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                    </form>
                </div>
                <?php else: ?>
                <div class="setting-item" style="opacity: 0.6;">
                    <div class="setting-info">
                        <h4>🔍 Facial Recognition</h4>
                        <p>Facial recognition system is not set up. Set it up to enable this feature.</p>
                        <div class="status-indicator status-disabled">
                            ❌ Not Available
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <a href="../setup_facial_recognition.php" class="btn btn-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            ⚙️ Setup System
                        </a>
                        <a href="../facial_recognition_admin.php" class="btn btn-outline" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            🔧 Manage FR
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Time Schedule -->
        <div class="settings-card">
            <div class="settings-header">
                <span style="font-size: 1.5rem;">⏰</span>
                <h3 style="margin: 0;">Time Schedule</h3>
            </div>
            <div class="settings-content">
                <div class="setting-info" style="margin-bottom: 1rem;">
                    <h4>Daily Time Restrictions</h4>
                    <p>Set specific hours when the scanner should be available</p>
                    <div class="status-indicator <?= $settings['scanner_schedule_enabled']['is_enabled'] ? 'status-scheduled' : 'status-disabled' ?>">
                        <?= $settings['scanner_schedule_enabled']['is_enabled'] ? '⏰ Scheduled' : '🕐 Always Available' ?>
                    </div>
                </div>

                <form method="POST" class="schedule-form">
                    <input type="hidden" name="action" value="update_time_schedule">
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="schedule_enabled" 
                                   <?= $settings['scanner_schedule_enabled']['is_enabled'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <span style="margin-left: 1rem; font-weight: 500;">Enable Time Restrictions</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" 
                                   value="<?= $settings['scanner_time_restriction']['start_time'] ?? '08:00' ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" class="form-control" 
                                   value="<?= $settings['scanner_time_restriction']['end_time'] ?? '17:00' ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        ⏰ Update Time Schedule
                    </button>
                </form>
            </div>
        </div>

        <!-- Date Range -->
        <div class="settings-card">
            <div class="settings-header">
                <span style="font-size: 1.5rem;">📅</span>
                <h3 style="margin: 0;">Date Range</h3>
            </div>
            <div class="settings-content">
                <div class="setting-info" style="margin-bottom: 1rem;">
                    <h4>Date Range Restrictions</h4>
                    <p>Set specific date range when the scanner should be available</p>
                    <div class="status-indicator <?= $settings['scanner_date_restriction']['is_enabled'] ? 'status-scheduled' : 'status-disabled' ?>">
                        <?= $settings['scanner_date_restriction']['is_enabled'] ? '📅 Date Restricted' : '📅 Always Available' ?>
                    </div>
                </div>

                <form method="POST" class="schedule-form">
                    <input type="hidden" name="action" value="update_date_schedule">
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="date_enabled" 
                                   <?= $settings['scanner_date_restriction']['is_enabled'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <span style="margin-left: 1rem; font-weight: 500;">Enable Date Restrictions</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" 
                                   value="<?= $settings['scanner_date_restriction']['start_date'] ?? date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" 
                                   value="<?= $settings['scanner_date_restriction']['end_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        📅 Update Date Range
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add confirmation for disabling critical features
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked && (this.name === 'qr_scanner_enabled' || this.name === 'manual_id_enabled')) {
                    if (!confirm('Are you sure you want to disable this feature? This will prevent users from using this scanning method.')) {
                        this.checked = true;
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>
