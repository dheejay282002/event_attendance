<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';
require_once '../includes/student_sync.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Handle file upload for import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, ['csv', 'xlsx'])) {
            $upload_dir = '../uploads/imports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'admin_import_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Process the file
                if ($file_extension === 'csv') {
                    $result = processCsvFile($filepath, $conn);
                } else {
                    $result = processExcelFile($filepath, $conn);
                }
                
                if ($result['success']) {
                    $message = "✅ Successfully imported {$result['count']} students!";
                    if ($result['courses_added'] > 0) {
                        $message .= " Also created {$result['courses_added']} new course(s).";
                    }
                    if ($result['sections_added'] > 0) {
                        $message .= " Also created {$result['sections_added']} new section(s).";
                    }
                    if ($result['duplicates'] > 0) {
                        $message .= " ⚠️ Found {$result['duplicates']} duplicate student(s) - these were skipped.";
                    }

                    // Clean up unused sections after import
                    $cleaned_sections = cleanupUnusedSections($conn);
                    if (!empty($cleaned_sections)) {
                        $message .= " Cleaned up " . count($cleaned_sections) . " unused sections.";
                    }
                } else {
                    $error = "❌ Import failed: " . $result['error'];
                }
                
                // Clean up uploaded file
                unlink($filepath);
            } else {
                $error = "❌ Failed to upload file.";
            }
        } else {
            $error = "❌ Please upload a CSV or Excel file.";
        }
    } else {
        $error = "❌ File upload error.";
    }
}

// Handle file upload for update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && isset($_FILES['update_csv_file'])) {
    $file = $_FILES['update_csv_file'];

    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($file_extension, ['csv', 'xlsx'])) {
            $upload_dir = '../uploads/imports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = 'admin_update_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Process the update file
                if ($file_extension === 'csv') {
                    $result = processUpdateCsvFile($filepath, $conn);
                } else {
                    $result = processUpdateExcelFile($filepath, $conn);
                }

                if ($result['success']) {
                    $message = "✅ Successfully processed {$result['total_processed']} students! ";
                    $message .= "Updated: {$result['updated']}, Added: {$result['added']}, Skipped: {$result['skipped']}";

                    // Clean up unused sections after update
                    $cleaned_sections = cleanupUnusedSections($conn);
                    if (!empty($cleaned_sections)) {
                        $message .= " Cleaned up " . count($cleaned_sections) . " unused sections.";
                    }
                } else {
                    $error = "❌ Update failed: " . $result['error'];
                }

                // Clean up uploaded file
                unlink($filepath);
            } else {
                $error = "❌ Failed to upload file.";
            }
        } else {
            $error = "❌ Please upload a CSV or Excel file.";
        }
    } else {
        $error = "❌ File upload error.";
    }
}

// Handle backup import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_backup' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];

    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_extension === 'csv') {
            $upload_dir = '../uploads/backups/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = 'backup_import_' . time() . '.csv';
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Process the backup file
                $result = processBackupFile($filepath, $conn);

                if ($result['success']) {
                    $message = "✅ System backup imported successfully! ";
                    $message .= "Imported {$result['students']} students, {$result['events']} events, ";
                    $message .= "{$result['attendance']} attendance records, and {$result['sbo_users']} SBO users.";
                } else {
                    $error = "❌ Backup import failed: " . $result['error'];
                }

                // Clean up uploaded file
                unlink($filepath);
            } else {
                $error = "❌ Failed to upload backup file.";
            }
        } else {
            $error = "❌ Please upload a CSV backup file.";
        }
    } else {
        $error = "❌ Backup file upload error.";
    }
}

function processCsvFile($filepath, $conn) {
    $count = 0;
    $duplicates = 0;
    $errors = [];
    $courses_added = [];
    $sections_added = [];

    if (($handle = fopen($filepath, "r")) !== FALSE) {
        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) >= 4) {
                $full_name = trim($data[0]);
                $student_id = trim($data[1]);
                $section = trim($data[2]);
                $course = trim($data[3]);

                if (!empty($full_name) && !empty($student_id) && !empty($section) && !empty($course)) {

                    // 1. Auto-create course if it doesn't exist
                    $course_id = ensureCourseExists($conn, $course, $courses_added);

                    // 2. Auto-create section if it doesn't exist
                    $section_id = ensureSectionExists($conn, $section, $course_id, $sections_added);

                    // 3. Use comprehensive sync system for new students
                    $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'add');
                    if ($sync_result['success']) {
                        $count++;
                    } else if (strpos($sync_result['error'], 'already exists') !== false || strpos($sync_result['error'], 'duplicate') !== false) {
                        // Handle duplicates gracefully
                        $duplicates++;
                    } else {
                        $errors[] = "Failed to add student {$student_id}: " . $sync_result['error'];
                    }
                } else {
                    $errors[] = "Missing required data for row: " . implode(', ', $data);
                }
            }
        }
        fclose($handle);
    }

    return [
        'success' => true,
        'count' => $count,
        'duplicates' => $duplicates,
        'courses_added' => count($courses_added),
        'sections_added' => count($sections_added),
        'errors' => $errors
    ];
}

function processExcelFile($filepath, $conn) {
    // For Excel files, we'll convert to CSV first or use a library
    // For now, return an error asking for CSV format
    return [
        'success' => false,
        'error' => 'Excel files not yet supported. Please convert to CSV format.',
        'count' => 0,
        'courses_added' => 0,
        'sections_added' => 0
    ];
}

function processUpdateCsvFile($filepath, $conn) {
    $total_processed = 0;
    $updated = 0;
    $added = 0;
    $skipped = 0;
    $errors = [];

    if (($handle = fopen($filepath, "r")) !== FALSE) {
        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) >= 4) {
                $full_name = trim($data[0]);
                $student_id = trim($data[1]);
                $section = trim($data[2]);
                $course = trim($data[3]);

                if (!empty($full_name) && !empty($student_id) && !empty($section) && !empty($course)) {
                    $total_processed++;

                    // Check if student exists in official_students
                    $check_stmt = mysqli_prepare($conn, "SELECT student_id FROM official_students WHERE student_id = ?");
                    mysqli_stmt_bind_param($check_stmt, "s", $student_id);
                    mysqli_stmt_execute($check_stmt);
                    $result = mysqli_stmt_get_result($check_stmt);

                    if (mysqli_num_rows($result) > 0) {
                        // Student exists, update their information
                        $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'update');
                        if ($sync_result['success']) {
                            $updated++;
                        } else {
                            $errors[] = "Failed to update student {$student_id}: " . $sync_result['error'];
                            $skipped++;
                        }
                    } else {
                        // Student doesn't exist, add them
                        $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'add');
                        if ($sync_result['success']) {
                            $added++;
                        } else {
                            $errors[] = "Failed to add student {$student_id}: " . $sync_result['error'];
                            $skipped++;
                        }
                    }
                }
            }
        }
        fclose($handle);
    }

    return [
        'success' => true,
        'total_processed' => $total_processed,
        'updated' => $updated,
        'added' => $added,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

function processUpdateExcelFile($filepath, $conn) {
    // For Excel files, we'll convert to CSV first or use a library
    // For now, return an error asking for CSV format
    return [
        'success' => false,
        'error' => 'Excel files not yet supported for updates. Please convert to CSV format.',
        'total_processed' => 0,
        'updated' => 0,
        'added' => 0,
        'skipped' => 0
    ];
}

function processBackupFile($filepath, $conn) {
    $students_imported = 0;
    $events_imported = 0;
    $attendance_imported = 0;
    $sbo_users_imported = 0;
    $errors = [];

    try {
        // Start transaction for data consistency
        mysqli_autocommit($conn, false);

        if (($handle = fopen($filepath, "r")) !== FALSE) {
            $current_section = '';
            $headers = [];

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                // Check for section headers
                if (isset($data[0]) && strpos($data[0], '===') !== false) {
                    $current_section = trim(str_replace('=', '', $data[0]));
                    $headers = [];
                    continue;
                }

                // Skip metadata rows
                if (isset($data[0]) && (strpos($data[0], 'Export Date:') !== false ||
                    strpos($data[0], 'Admin:') !== false ||
                    strpos($data[0], 'ADLOR SYSTEM') !== false)) {
                    continue;
                }

                // Process based on current section
                switch ($current_section) {
                    case 'STUDENTS':
                        if (empty($headers)) {
                            $headers = $data; // Store headers
                        } else {
                            // Process student data
                            if (count($data) >= 4) {
                                $student_id = trim($data[0]);
                                $full_name = trim($data[1]);
                                $course = trim($data[2]);
                                $section = trim($data[3]);

                                if (!empty($student_id) && !empty($full_name)) {
                                    // Use sync system to import student
                                    $sync_result = syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, 'add');
                                    if ($sync_result['success']) {
                                        $students_imported++;
                                    }
                                }
                            }
                        }
                        break;

                    case 'EVENTS':
                        if (empty($headers)) {
                            $headers = $data;
                        } else {
                            // Process event data
                            if (count($data) >= 8) {
                                $title = trim($data[1]);
                                $description = trim($data[2]);
                                $event_date = trim($data[3]);
                                $start_time = trim($data[4]);
                                $end_time = trim($data[5]);
                                $location = trim($data[6]);
                                $assigned_sections = trim($data[7]);

                                if (!empty($title) && !empty($event_date)) {
                                    $insert_event = mysqli_prepare($conn, "INSERT INTO events (title, description, event_date, start_time, end_time, location, assigned_sections, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                                    mysqli_stmt_bind_param($insert_event, "sssssss", $title, $description, $event_date, $start_time, $end_time, $location, $assigned_sections);
                                    if (mysqli_stmt_execute($insert_event)) {
                                        $events_imported++;
                                    }
                                }
                            }
                        }
                        break;

                    case 'ATTENDANCE':
                        if (empty($headers)) {
                            $headers = $data;
                        } else {
                            // Process attendance data
                            if (count($data) >= 4) {
                                $student_id = trim($data[1]);
                                $event_id = trim($data[3]);
                                $time_in = trim($data[5]);
                                $time_out = trim($data[6]);

                                if (!empty($student_id) && !empty($event_id)) {
                                    $insert_attendance = mysqli_prepare($conn, "INSERT INTO attendance (student_id, event_id, time_in, time_out) VALUES (?, ?, ?, ?)");
                                    $time_in_val = !empty($time_in) ? $time_in : null;
                                    $time_out_val = !empty($time_out) ? $time_out : null;
                                    mysqli_stmt_bind_param($insert_attendance, "siss", $student_id, $event_id, $time_in_val, $time_out_val);
                                    if (mysqli_stmt_execute($insert_attendance)) {
                                        $attendance_imported++;
                                    }
                                }
                            }
                        }
                        break;

                    case 'SBO USERS':
                        if (empty($headers)) {
                            $headers = $data;
                        } else {
                            // Process SBO user data
                            if (count($data) >= 4) {
                                $name = trim($data[1]);
                                $email = trim($data[2]);
                                $position = trim($data[3]);
                                $is_active = (trim($data[4]) === 'Active') ? 1 : 0;

                                if (!empty($name) && !empty($email)) {
                                    $insert_sbo = mysqli_prepare($conn, "INSERT INTO sbo_users (full_name, email, position, is_active, password) VALUES (?, ?, ?, ?, ?)");
                                    $default_password = password_hash('adlor2024', PASSWORD_DEFAULT);
                                    mysqli_stmt_bind_param($insert_sbo, "sssis", $name, $email, $position, $is_active, $default_password);
                                    if (mysqli_stmt_execute($insert_sbo)) {
                                        $sbo_users_imported++;
                                    }
                                }
                            }
                        }
                        break;
                }
            }
            fclose($handle);
        }

        // Commit transaction
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);

        return [
            'success' => true,
            'students' => $students_imported,
            'events' => $events_imported,
            'attendance' => $attendance_imported,
            'sbo_users' => $sbo_users_imported,
            'errors' => $errors
        ];

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);

        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'students' => 0,
            'events' => 0,
            'attendance' => 0,
            'sbo_users' => 0
        ];
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
    <title>Import Student Data - ADLOR Admin</title>
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
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .admin-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .section-header.clickable {
            cursor: pointer;
            transition: background-color 0.3s ease;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin: -0.75rem -0.75rem 1.5rem -0.75rem;
        }

        .section-header.clickable:hover {
            background-color: var(--gray-50);
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
        }

        .file-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            background: var(--gray-50);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: var(--primary-light);
            transform: scale(1.02);
        }
    </style>
</head>
<body class="admin-panel-body has-navbar">
    <?php renderNavigation('admin', 'data_management', $_SESSION['admin_name']); ?>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: white;">📥 Import Student Data</h1>
            <p style="font-size: 1.125rem; opacity: 0.9; margin: 0; color: white;">
                Upload CSV files to import student information into the system
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

        <!-- Import Form -->
        <div class="admin-card">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📄</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Upload Student Data</h3>
                </div>

                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="hidden" name="action" value="import">
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-800);">Choose CSV File</h4>
                        <p style="margin: 0 0 1rem 0; color: var(--gray-600);">
                            Click here or drag and drop your CSV file
                        </p>
                        <input type="file"
                               id="csv_file"
                               name="csv_file"
                               accept=".csv,.xlsx"
                               required
                               style="display: none;"
                               onchange="updateFileName()">
                        <div id="fileName" style="margin-top: 1rem; font-weight: 600; color: var(--primary-color);"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full" style="margin-top: 2rem;">
                        📥 Import Student Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Update Student Data Section -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">🔄</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Update Student Data</h3>
                </div>

                <div style="background: var(--warning-light); border: 1px solid var(--warning-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--warning-dark);">⚠️ Update Mode</h4>
                    <p style="margin: 0; color: var(--warning-dark); font-size: 0.875rem;">
                        This will update existing students' information and add new students automatically.
                        No duplicates will be created - existing students will be updated based on their Student ID.
                    </p>
                </div>

                <form method="POST" enctype="multipart/form-data" id="updateForm">
                    <input type="hidden" name="action" value="update">
                    <div class="file-upload-area" onclick="document.getElementById('update_csv_file').click()">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔄</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-800);">Choose CSV File for Update</h4>
                        <p style="margin: 0 0 1rem 0; color: var(--gray-600);">
                            Click here or drag and drop your CSV file
                        </p>
                        <input type="file"
                               id="update_csv_file"
                               name="update_csv_file"
                               accept=".csv,.xlsx"
                               required
                               style="display: none;"
                               onchange="updateUpdateFileName()">
                        <div id="updateFileName" style="margin-top: 1rem; font-weight: 600; color: var(--primary-color);"></div>
                    </div>

                    <button type="submit" class="btn btn-warning w-full" style="margin-top: 2rem;">
                        🔄 Update Student Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Manual Student Management Section -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Manual Student Management</h3>
                </div>

                <div style="background: var(--info-light); border: 1px solid var(--info-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--info-dark);">ℹ️ Individual Student Operations</h4>
                    <p style="margin: 0; color: var(--info-dark); font-size: 0.875rem;">
                        Add new students individually or edit existing student information by Student ID.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="add_student.php" class="btn btn-success" style="text-decoration: none; text-align: center; padding: 1.5rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">➕</div>
                        <div style="font-weight: 600;">Add New Student</div>
                        <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Create individual student record</div>
                    </a>

                    <a href="edit_student.php" class="btn btn-primary" style="text-decoration: none; text-align: center; padding: 1.5rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">✏️</div>
                        <div style="font-weight: 600;">Edit Student by ID</div>
                        <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Modify existing student data</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Download Reports & Backup Section -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📊</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Download Reports & System Backup</h3>
                </div>

                <div style="background: var(--info-light); border: 1px solid var(--info-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--info-dark);">📋 Export System Data</h4>
                    <p style="margin: 0; color: var(--info-dark); font-size: 0.875rem;">
                        Download comprehensive reports and create system backups in CSV format compatible with Excel and other spreadsheet applications.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                    <!-- Students Export -->
                    <a href="export_data.php?type=students" class="btn btn-primary" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Export Students</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Download complete student database</div>
                    </a>

                    <!-- Events Export -->
                    <a href="export_data.php?type=events" class="btn btn-success" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Export Events</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Download all events and details</div>
                    </a>

                    <!-- Attendance Export -->
                    <a href="download_attendance.php" class="btn btn-warning" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Download Attendance</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Export attendance reports by event</div>
                    </a>

                    <!-- SBO Users Export -->
                    <a href="export_data.php?type=sbo_users" class="btn btn-info" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏛️</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Export SBO Users</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Download SBO user accounts</div>
                    </a>

                    <!-- Full System Backup -->
                    <a href="export_data.php?type=full" class="btn btn-danger" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">💾</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Full System Backup</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Complete database export</div>
                    </a>

                    <!-- Attendance Records Export -->
                    <a href="export_data.php?type=attendance" class="btn btn-secondary" style="text-decoration: none; text-align: center; padding: 1.5rem; display: block;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Export All Attendance</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Download complete attendance records</div>
                    </a>
                </div>

                <div style="background: var(--warning-light); border: 1px solid var(--warning-color); border-radius: 0.5rem; padding: 1rem; margin-top: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--warning-dark);">💡 Export Information</h4>
                    <ul style="margin: 0.5rem 0 0 1.5rem; color: var(--warning-dark); font-size: 0.875rem;">
                        <li><strong>File Format:</strong> CSV files compatible with Excel, Google Sheets, and other spreadsheet applications</li>
                        <li><strong>Encoding:</strong> UTF-8 with BOM for proper character display in Excel</li>
                        <li><strong>Filename:</strong> Automatically timestamped (e.g., adlor_students_2024-07-08_14-30-25.csv)</li>
                        <li><strong>Data Integrity:</strong> All exports include complete data with proper relationships</li>
                        <li><strong>Security:</strong> Admin authentication required for all export operations</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Import System Backup Section -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header">
                    <div class="section-icon">📥</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Import System Backup</h3>
                </div>

                <div style="background: var(--danger-light); border: 1px solid var(--danger-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--danger-dark);">⚠️ Critical System Operation</h4>
                    <p style="margin: 0; color: var(--danger-dark); font-size: 0.875rem;">
                        <strong>WARNING:</strong> Importing a system backup will replace ALL existing data. This action cannot be undone.
                        Please ensure you have a current backup before proceeding.
                    </p>
                </div>

                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                    <input type="hidden" name="action" value="import_backup">

                    <div style="background: var(--gray-50); border: 2px dashed var(--gray-300); border-radius: 0.75rem; padding: 2rem; text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-700);">Select System Backup File</h4>
                        <p style="margin: 0 0 1rem 0; color: var(--gray-600); font-size: 0.875rem;">
                            Upload a CSV backup file exported from this system
                        </p>
                        <input type="file"
                               name="backup_file"
                               accept=".csv"
                               required
                               style="margin-bottom: 1rem; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; background: white;">
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ WARNING: This will replace ALL existing data with the backup file data. Are you absolutely sure you want to proceed?')">
                            📥 Import System Backup
                        </button>
                    </div>
                </form>

                <div style="background: var(--info-light); border: 1px solid var(--info-color); border-radius: 0.5rem; padding: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--info-dark);">📋 Backup Import Requirements</h4>
                    <ul style="margin: 0.5rem 0 0 1.5rem; color: var(--info-dark); font-size: 0.875rem;">
                        <li><strong>File Format:</strong> CSV file exported from this ADLOR system</li>
                        <li><strong>File Source:</strong> Must be a "Full System Backup" file from the export section</li>
                        <li><strong>Data Validation:</strong> System will validate backup file structure before import</li>
                        <li><strong>Backup Recommendation:</strong> Create a current backup before importing</li>
                        <li><strong>Admin Access:</strong> Only system administrators can perform backup imports</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div class="section-header clickable" onclick="toggleInstructions()">
                    <div class="section-icon">📋</div>
                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Import Instructions</h3>
                    <div style="margin-left: auto; font-size: 1.5rem; transition: transform 0.3s ease;" id="instructionsToggle">
                        ▼
                    </div>
                </div>

                <div id="instructionsContent" style="display: none; color: var(--gray-700); line-height: 1.6; margin-top: 1rem; border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                    <h4 style="color: var(--gray-800); margin-bottom: 1rem;">CSV File Format</h4>
                    <p>Your CSV file should have the following columns in this exact order:</p>
                    <ol style="margin-left: 1.5rem;">
                        <li><strong>Full Name</strong> - Student's complete name</li>
                        <li><strong>Student ID</strong> - Unique student identifier (e.g., 23-11797)</li>
                        <li><strong>Section</strong> - Student's section (e.g., NS-2A)</li>
                        <li><strong>Course</strong> - Student's course (e.g., BSIT)</li>
                    </ol>

                    <h4 style="color: var(--gray-800); margin: 1.5rem 0 1rem 0;">Example CSV Content</h4>
                    <div style="background: var(--gray-100); padding: 1rem; border-radius: 0.5rem; font-family: monospace; font-size: 0.875rem;">
                        Full Name,Student ID,Section,Course<br>
                        John Doe,23-11797,NS-2A,BSIT<br>
                        Jane Smith,23-11798,NS-2B,BSCS<br>
                        Mike Johnson,23-11799,NS-2A,BSIT
                    </div>

                    <h4 style="color: var(--gray-800); margin: 1.5rem 0 1rem 0;">Important Notes</h4>
                    <ul style="margin-left: 1.5rem;">
                        <li>The first row should contain column headers</li>
                        <li>Student IDs must be unique</li>
                        <li>Duplicate entries will be ignored</li>
                        <li>All fields are required</li>
                        <li>Maximum file size: 5MB</li>
                        <li><strong>Auto-Creation:</strong> Courses and sections will be automatically created if they don't exist</li>
                    </ul>

                    <h4 style="color: var(--gray-800); margin: 1.5rem 0 1rem 0;">Auto-Creation Features</h4>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Courses:</strong> New course codes (like BSIT, BSCS) will be automatically added to the system</li>
                        <li><strong>Sections:</strong> New section codes (like IT-3A, CS-2B) will be automatically created</li>
                        <li><strong>Smart Naming:</strong> System generates appropriate full names for courses and sections</li>
                        <li><strong>Year Levels:</strong> Sections are automatically assigned to appropriate year levels</li>
                    </ul>

                    <h4 style="color: var(--warning-dark); margin: 1.5rem 0 1rem 0;">🔄 Update Student Data Features</h4>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Smart Updates:</strong> Updates existing students based on Student ID</li>
                        <li><strong>No Duplicates:</strong> Prevents duplicate entries - updates existing records instead</li>
                        <li><strong>Auto-Add New Students:</strong> Automatically adds new Student IDs found in the CSV</li>
                        <li><strong>Year Level Updates:</strong> Perfect for updating students to new year levels</li>
                        <li><strong>Section Changes:</strong> Updates students who changed sections</li>
                        <li><strong>Password Preservation:</strong> Student login passwords remain unchanged during updates</li>
                        <li><strong>Dashboard Sync:</strong> Student dashboards automatically reflect updated information</li>
                        <li><strong>Bulk Processing:</strong> Handles large student lists efficiently</li>
                    </ul>

                    <div style="background: var(--info-light); border: 1px solid var(--info-color); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <h5 style="margin: 0 0 0.5rem 0; color: var(--info-dark);">💡 When to Use Update vs Import</h5>
                        <p style="margin: 0; color: var(--info-dark); font-size: 0.875rem;">
                            <strong>Use Import:</strong> For adding completely new students to the system<br>
                            <strong>Use Update:</strong> For updating existing students' year levels, sections, or adding new students to existing data
                        </p>
                    </div>
                </div> <!-- End instructionsContent -->
            </div>
        </div>
    </div>

    <script>
        function updateFileName() {
            const fileInput = document.getElementById('csv_file');
            const fileName = document.getElementById('fileName');

            if (fileInput.files.length > 0) {
                fileName.textContent = '📄 ' + fileInput.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }

        function updateUpdateFileName() {
            const fileInput = document.getElementById('update_csv_file');
            const fileName = document.getElementById('updateFileName');

            if (fileInput.files.length > 0) {
                fileName.textContent = '📄 ' + fileInput.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }

        function toggleInstructions() {
            const content = document.getElementById('instructionsContent');
            const toggle = document.getElementById('instructionsToggle');

            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                toggle.textContent = '▲';
                toggle.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                toggle.textContent = '▼';
                toggle.style.transform = 'rotate(0deg)';
            }
        }

        // Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('csv_file').files = files;
                updateFileName();
            }
        });
    </script>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
