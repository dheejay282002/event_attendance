<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';
require_once '../includes/system_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';
$event_id = intval($_GET['id'] ?? 0);

if ($event_id <= 0) {
    header("Location: manage_events.php");
    exit;
}

// Get event details
$event_query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $event_query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$event_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($event_result) === 0) {
    header("Location: manage_events.php");
    exit;
}

$event = mysqli_fetch_assoc($event_result);

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
    
    // Validation
    if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
        $error = "❌ Please fill in all required fields.";
    } elseif (strtotime($start_datetime) >= strtotime($end_datetime)) {
        $error = "❌ End time must be after start time.";
    } elseif (!$allow_qr_scanner && !$allow_manual_entry) {
        $error = "❌ At least one attendance method (QR Scanner or Manual Entry) must be enabled.";
    } else {
        // Update event
        $update_query = "UPDATE events SET title = ?, description = ?, start_datetime = ?, end_datetime = ?, assigned_sections = ?, allow_qr_scanner = ?, allow_manual_entry = ?, attendance_method_note = ?, updated_at = NOW() WHERE id = ?";

        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssiisi", $title, $description, $start_datetime, $end_datetime, $assigned_sections, $allow_qr_scanner, $allow_manual_entry, $attendance_method_note, $event_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "✅ Event updated successfully!";
            
            // Refresh event data
            $event['title'] = $title;
            $event['description'] = $description;
            $event['start_datetime'] = $start_datetime;
            $event['end_datetime'] = $end_datetime;
            $event['assigned_sections'] = $assigned_sections;
            $event['allow_qr_scanner'] = $allow_qr_scanner;
            $event['allow_manual_entry'] = $allow_manual_entry;
            $event['attendance_method_note'] = $attendance_method_note;
        } else {
            $error = "❌ Error updating event: " . mysqli_error($conn);
        }
    }
}

// Get available sections for assignment
$sections_query = "SELECT DISTINCT section, course FROM official_students ORDER BY course, section";
$sections_result = mysqli_query($conn, $sections_query);

// Group sections by course
$sections_by_course = [];
while ($section = mysqli_fetch_assoc($sections_result)) {
    $sections_by_course[$section['course']][] = $section['section'];
}

// Parse assigned sections
$assigned_sections_array = !empty($event['assigned_sections']) ? explode(',', $event['assigned_sections']) : [];

// Get system settings
$system_name = getSystemName($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?= htmlspecialchars($system_name) ?></title>
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



        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            background: var(--gray-50);
        }

        .course-group {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            border: 1px solid var(--gray-200);
        }

        .course-title {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .section-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .section-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .section-checkbox label {
            margin: 0;
            font-size: 0.875rem;
            color: var(--gray-600);
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
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
            .form-actions {
                flex-direction: column;
            }
            
            .sections-grid {
                grid-template-columns: 1fr;
            }

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
<body class="admin-panel-body">
    <?php renderNavigation('admin', 'events', $_SESSION['admin_name']); ?>



    <div class="container" style="margin-bottom: 3rem;">
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

        <!-- Edit Event Form -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">✏️</div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Edit Event Details</h3>
                        <p style="margin: 0.25rem 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
                            Event ID: #<?= $event_id ?> | Created: <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?>
                        </p>
                    </div>
                    <a href="manage_events.php" class="btn btn-outline">
                        ← Back to Events
                    </a>
                </div>

                <form method="POST">
                    <div class="form-grid">
                        <!-- Event Title -->
                        <div class="form-group">
                            <label class="form-label required">Event Title</label>
                            <input type="text" name="title" class="form-input" 
                                   placeholder="Enter event title..." 
                                   value="<?= htmlspecialchars($event['title']) ?>" required>
                        </div>

                        <!-- Start Date & Time -->
                        <div class="form-group">
                            <label class="form-label required">Start Date & Time</label>
                            <input type="datetime-local" name="start_datetime" class="form-input" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($event['start_datetime'])) ?>" required>
                        </div>

                        <!-- End Date & Time -->
                        <div class="form-group">
                            <label class="form-label required">End Date & Time</label>
                            <input type="datetime-local" name="end_datetime" class="form-input" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($event['end_datetime'])) ?>" required>
                        </div>

                        <!-- Event Description -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Event Description</label>
                            <textarea name="description" class="form-input" rows="4" 
                                      placeholder="Enter event description (optional)..."><?= htmlspecialchars($event['description']) ?></textarea>
                        </div>

                        <!-- Assigned Sections -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Assigned Sections</label>
                            <p style="margin: 0.5rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                Select specific sections for this event, or leave empty to include all sections.
                            </p>
                            
                            <?php if (!empty($sections_by_course)): ?>
                                <div class="sections-grid">
                                    <?php foreach ($sections_by_course as $course => $sections): ?>
                                        <div class="course-group">
                                            <div class="course-title" title="Click to select/deselect all sections in this course">
                                                <?= htmlspecialchars($course) ?>
                                            </div>
                                            <?php foreach ($sections as $section): ?>
                                                <div class="section-checkbox">
                                                    <input type="checkbox" 
                                                           name="assigned_sections[]" 
                                                           value="<?= htmlspecialchars($section) ?>"
                                                           id="section_<?= htmlspecialchars($section) ?>"
                                                           <?= in_array($section, $assigned_sections_array) ? 'checked' : '' ?>>
                                                    <label for="section_<?= htmlspecialchars($section) ?>">
                                                        <?= htmlspecialchars($section) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 2rem; color: var(--gray-500); background: var(--gray-50); border-radius: 0.5rem;">
                                    <p style="margin: 0;">No sections available. Import students first to see sections.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attendance Method Settings -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">🎯</span>
                            <h3>Attendance Method Settings</h3>
                        </div>
                        <div class="section-content">
                            <p class="section-description">
                                Configure which attendance methods are allowed for this event.
                            </p>

                            <div class="attendance-methods">
                                <div class="method-toggle">
                                    <div class="toggle-container">
                                        <input type="checkbox"
                                               name="allow_qr_scanner"
                                               id="allow_qr_scanner"
                                               class="toggle-input"
                                               <?= ($event['allow_qr_scanner'] ?? 1) ? 'checked' : '' ?>>
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
                                               <?= ($event['allow_manual_entry'] ?? 1) ? 'checked' : '' ?>>
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
                                          placeholder="Add any special instructions about attendance methods for this event..."><?= htmlspecialchars($event['attendance_method_note'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="manage_events.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            ✅ Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update end time minimum when start time changes
        const startInput = document.querySelector('input[name="start_datetime"]');
        const endInput = document.querySelector('input[name="end_datetime"]');
        
        if (startInput && endInput) {
            startInput.addEventListener('change', function() {
                endInput.min = this.value;
                if (endInput.value && endInput.value <= this.value) {
                    // Add 1 hour to start time for end time
                    const startTime = new Date(this.value);
                    startTime.setHours(startTime.getHours() + 1);
                    endInput.value = startTime.toISOString().slice(0, 16);
                }
            });
        }

        // Select all sections in a course
        document.querySelectorAll('.course-title').forEach(title => {
            title.addEventListener('click', function() {
                const courseGroup = this.parentElement;
                const checkboxes = courseGroup.querySelectorAll('input[type="checkbox"]');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                });
            });
        });
    </script>
</body>
</html>
