<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/navigation.php';

// Check if user is logged in as SBO
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $assigned_sections = isset($_POST['assigned_sections']) ? implode(',', $_POST['assigned_sections']) : '';

    // Attendance method settings
    $allow_qr_scanner = isset($_POST['allow_qr_scanner']) ? 1 : 0;
    $allow_manual_entry = isset($_POST['allow_manual_entry']) ? 1 : 0;
    $attendance_method_note = trim($_POST['attendance_method_note'] ?? '');

    // Validate required fields
    if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
        $error = "❌ Title, start date/time, and end date/time are required.";
    } elseif (strtotime($start_datetime) >= strtotime($end_datetime)) {
        $error = "❌ End date/time must be after start date/time.";
    } elseif (!$allow_qr_scanner && !$allow_manual_entry) {
        $error = "❌ At least one attendance method (QR Scanner or Manual Entry) must be enabled.";
    } else {
        // Insert event into database with ownership tracking
        $stmt = mysqli_prepare($conn, "INSERT INTO events (title, description, start_datetime, end_datetime, assigned_sections, allow_qr_scanner, allow_manual_entry, attendance_method_note, created_by, creator_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $created_by = $_SESSION['sbo_id'];
        $creator_type = 'sbo';
        mysqli_stmt_bind_param($stmt, "sssssiisis", $title, $description, $start_datetime, $end_datetime, $assigned_sections, $allow_qr_scanner, $allow_manual_entry, $attendance_method_note, $created_by, $creator_type);
        
        if (mysqli_stmt_execute($stmt)) {
            $event_id = mysqli_insert_id($conn);
            
            // Generate QR code for the event
            require_once '../simple_qr_generator.php';
            
            $event_qr_data = json_encode([
                'type' => 'event',
                'event_id' => $event_id,
                'title' => $title,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'assigned_sections' => $assigned_sections,
                'timestamp' => time(),
                'hash' => md5($event_id . $title . $start_datetime . time())
            ]);
            
            // Create QR codes directory if it doesn't exist
            $qr_dir = '../qr_codes/events';
            if (!file_exists($qr_dir)) {
                mkdir($qr_dir, 0777, true);
            }
            
            // Generate event QR code
            $event_qr_filename = $qr_dir . "/event_{$event_id}.png";
            SimpleQRGenerator::generateQRCode($event_qr_data, $event_qr_filename);
            
            $message = "✅ Event created successfully with QR code generated!";
            
            // Clear form data
            $title = $description = $start_datetime = $end_datetime = '';
            $assigned_sections = [];
        } else {
            $error = "❌ Error creating event: " . mysqli_error($conn);
        }
    }
}

// Get all sections grouped by course for the form
$sections_query = mysqli_query($conn, "SELECT DISTINCT course, section FROM official_students ORDER BY course, section");
$sections_by_course = [];
while ($row = mysqli_fetch_assoc($sections_query)) {
    $sections_by_course[$row['course']][] = $row['section'];
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
    <title>Create Event - ADLOR Event Attendance</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-2xl);
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .form-section {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .form-section-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .form-section-body {
            padding: var(--spacing-xl);
        }

        .section-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        /* Attendance Method Toggle Styles */
        .attendance-methods {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .method-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 0.75rem;
            border: 2px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .method-toggle:hover {
            border-color: var(--primary-color);
            background: var(--primary-50);
        }

        .toggle-container {
            position: relative;
            flex-shrink: 0;
        }

        .toggle-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-label {
            display: block;
            width: 60px;
            height: 34px;
            background-color: #ccc;
            border-radius: 34px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .toggle-slider {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 26px;
            height: 26px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-input:checked + .toggle-label {
            background-color: var(--primary-color);
        }

        .toggle-input:checked + .toggle-label .toggle-slider {
            transform: translateX(26px);
        }

        .method-info {
            flex: 1;
        }

        .method-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .method-info p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .method-toggle {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .toggle-container {
                align-self: center;
            }
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'events', $_SESSION['sbo_name']); ?>

    <div class="page-header">
        <div class="container">
            <div class="page-title">➕ Create New Event</div>
            <div class="page-subtitle">Create and configure new events for student attendance tracking</div>
        </div>
    </div>

    <div class="container-md">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">📅</div>
                <div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Create New Event</h3>
                    <p style="margin: 0.25rem 0 0 0; color: var(--gray-600);">Fill in the details to create a new event</p>
                </div>
            </div>
            <div class="form-section-body">

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="title">Event Title *</label>
                        <input type="text" id="title" name="title" class="form-input"
                               placeholder="e.g., Weekly Assembly" value="<?= htmlspecialchars($title ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3"
                                  placeholder="Optional event description"><?= htmlspecialchars($description ?? '') ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                        <div class="form-group">
                            <label class="form-label" for="start_datetime">Start Date & Time *</label>
                            <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-input"
                                   value="<?= htmlspecialchars($start_datetime ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="end_datetime">End Date & Time *</label>
                            <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-input"
                                   value="<?= htmlspecialchars($end_datetime ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assigned Sections (Optional)</label>
                        <p style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: var(--spacing-md);">
                            Leave empty to allow all sections, or select specific sections for this event.
                        </p>
                        <div style="border: 1px solid var(--gray-300); border-radius: var(--radius-md); padding: var(--spacing-md); max-height: 300px; overflow-y: auto;">
                            <?php foreach ($sections_by_course as $course => $sections): ?>
                                <div style="font-weight: 600; color: var(--primary-color); margin-bottom: var(--spacing-sm);"><?= htmlspecialchars($course) ?></div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-sm); margin-bottom: var(--spacing-lg);">
                                    <?php foreach ($sections as $section): ?>
                                        <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                                            <input type="checkbox" name="assigned_sections[]" value="<?= htmlspecialchars($section) ?>">
                                            <span><?= htmlspecialchars($section) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Attendance Method Settings -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon">🎯</div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">Attendance Method Settings</h3>
                                <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">Configure which attendance methods are allowed for this event</p>
                            </div>
                        </div>
                        <div class="form-section-content">
                            <div class="attendance-methods">
                                <div class="method-toggle">
                                    <div class="toggle-container">
                                        <input type="checkbox"
                                               name="allow_qr_scanner"
                                               id="allow_qr_scanner"
                                               class="toggle-input"
                                               <?= (isset($_POST['allow_qr_scanner']) || !isset($_POST['submit'])) ? 'checked' : '' ?>>
                                        <label for="allow_qr_scanner" class="toggle-label">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="method-info">
                                        <h4>📱 QR Code Scanner</h4>
                                        <p>Allow students to mark attendance by scanning QR codes</p>
                                    </div>
                                </div>

                                <div class="method-toggle">
                                    <div class="toggle-container">
                                        <input type="checkbox"
                                               name="allow_manual_entry"
                                               id="allow_manual_entry"
                                               class="toggle-input"
                                               <?= (isset($_POST['allow_manual_entry']) || !isset($_POST['submit'])) ? 'checked' : '' ?>>
                                        <label for="allow_manual_entry" class="toggle-label">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="method-info">
                                        <h4>⌨️ Manual Student ID Entry</h4>
                                        <p>Allow manual entry of student IDs for attendance</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="attendance_method_note">📝 Additional Notes (Optional)</label>
                                <textarea name="attendance_method_note"
                                          id="attendance_method_note"
                                          rows="3"
                                          placeholder="Add any special instructions about attendance methods for this event..."><?= htmlspecialchars($_POST['attendance_method_note'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-200);">
                        <div style="display: flex; gap: var(--spacing-md);">
                            <a href="manage_events.php" class="btn btn-outline">← Cancel</a>
                            <a href="dashboard.php" class="btn btn-outline">🏠 Dashboard</a>
                        </div>
                        <div style="display: flex; gap: var(--spacing-md);">
                            <button type="reset" class="btn btn-outline" onclick="return confirm('Are you sure you want to clear all fields?')">
                                🔄 Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                ➕ Create Event
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
