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

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

// Handle student actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_student') {
        $student_id = $_POST['student_id'];

        // Delete from both tables
        $stmt1 = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt1, "s", $student_id);

        $stmt2 = mysqli_prepare($conn, "DELETE FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt2, "s", $student_id);

        if (mysqli_stmt_execute($stmt1) && mysqli_stmt_execute($stmt2)) {
            $message = "✅ Student deleted successfully!";
        } else {
            $error = "❌ Error deleting student: " . mysqli_error($conn);
        }
    }

    if ($action === 'update_student') {
        $student_id = $_POST['student_id'];
        $full_name = trim($_POST['full_name']);
        $course = trim($_POST['course']);
        $section = trim($_POST['section']);

        // Update both tables
        $stmt1 = mysqli_prepare($conn, "UPDATE students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt1, "ssss", $full_name, $course, $section, $student_id);

        $stmt2 = mysqli_prepare($conn, "UPDATE official_students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt2, "ssss", $full_name, $course, $section, $student_id);

        if (mysqli_stmt_execute($stmt1) && mysqli_stmt_execute($stmt2)) {
            $message = "✅ Student updated successfully!";
        } else {
            $error = "❌ Error updating student: " . mysqli_error($conn);
        }
    }
}

// Build student query with filters
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($filter_course)) {
    $where_conditions[] = "course = ?";
    $params[] = $filter_course;
    $param_types .= "s";
}

if (!empty($filter_section)) {
    $where_conditions[] = "section = ?";
    $params[] = $filter_section;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "ss";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$student_query = "SELECT * FROM official_students $where_clause ORDER BY full_name ASC";
$stmt = mysqli_prepare($conn, $student_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

mysqli_stmt_execute($stmt);
$students_result = mysqli_stmt_get_result($stmt);

// Get filter options
$courses_query = mysqli_query($conn, "SELECT DISTINCT course FROM official_students WHERE course IS NOT NULL AND course != '' ORDER BY course");
$sections_query = mysqli_query($conn, "SELECT DISTINCT section FROM official_students WHERE section IS NOT NULL AND section != '' ORDER BY section");

// Get academic years from new table if available
$years_query = mysqli_query($conn, "SELECT year_code, year_name FROM year_levels WHERE is_active = 1 ORDER BY year_code DESC");
if (!$years_query || mysqli_num_rows($years_query) == 0) {
    // Fallback: extract years from student IDs
    $years_query = mysqli_query($conn, "SELECT DISTINCT CONCAT('20', LEFT(student_id, 2)) as year_code FROM official_students WHERE student_id REGEXP '^[0-9]{2}-[0-9]{5}$' ORDER BY year_code DESC");
}

if (isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"]["tmp_name"];
    $handle = fopen($file, "r");

    if ($handle !== FALSE) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row == 0) { $row++; continue; }

            // Handle your CSV format: Full Name, Student ID, Section, Course
            $full_name = trim($data[0]);
            $student_id = trim($data[1]);
            $section = trim($data[2]);
            $course = trim($data[3]);

            // Validate student ID format (should be like 23-10413)
            if (!preg_match('/^\d{2}-\d{5}$/', $student_id)) {
                continue; // Skip invalid student IDs
            }

            $check = mysqli_prepare($conn, "SELECT student_id, full_name, course, section FROM official_students WHERE student_id = ?");
            mysqli_stmt_bind_param($check, "s", $student_id);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($result) > 0) {
                $existing = mysqli_fetch_assoc($result);
                if (
                    $existing['full_name'] !== $full_name ||
                    $existing['course'] !== $course ||
                    $existing['section'] !== $section
                ) {
                    $update = mysqli_prepare($conn, "UPDATE official_students SET full_name = ?, course = ?, section = ? WHERE student_id = ?");
                    mysqli_stmt_bind_param($update, "ssss", $full_name, $course, $section, $student_id);
                    mysqli_stmt_execute($update);
                    $updated++;
                }
            } else {
                $insert = mysqli_prepare($conn, "INSERT INTO official_students (student_id, full_name, course, section) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($insert, "ssss", $student_id, $full_name, $course, $section);
                mysqli_stmt_execute($insert);
                $inserted++;
            }
            $row++;
        }
        fclose($handle);
        $success = true;
    } else {
        $error = "❌ Failed to open the CSV file.";
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
  <title>Upload Students - ADLOR Admin</title>
  <link rel="stylesheet" href="../assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <?php renderNavigation('admin', 'students', $_SESSION['admin_name']); ?>

    <div class="container-md" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0;">👥 Manage Students</h2>
                <p style="color: var(--gray-600); margin: 0.5rem 0 0 0;">Upload and manage student data</p>
            </div>

            <div class="card-body">
        <?php if ($success): ?>
          <div class="alert alert-success">
            <h4 style="margin: 0 0 0.5rem 0;">✅ Upload Complete!</h4>
            <p style="margin: 0;">
              <strong>Inserted:</strong> <?= $inserted ?> students<br>
              <strong>Updated:</strong> <?= $updated ?> students
            </p>
          </div>
        <?php elseif ($error): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Admin is already authenticated, show upload form directly -->
          <form method="POST" enctype="multipart/form-data">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
              <h4 style="margin: 0 0 0.5rem 0;">📋 CSV Upload Instructions</h4>
              <p style="margin: 0; font-size: 0.875rem;">
                Upload a CSV file with columns: <strong>Full Name, Student ID, Section, Course</strong><br>
                Example: <code>"Vrenelli M. Agustin, 23-10413, NS-2A, BSIT"</code><br>
                The first row should contain headers and will be skipped.
              </p>
            </div>

            <div class="form-group">
              <label class="form-label" for="csv_file">Select CSV File</label>
              <input type="file"
                     id="csv_file"
                     name="csv_file"
                     class="form-input"
                     accept=".csv"
                     required>
              <small style="color: var(--gray-600); font-size: 0.875rem;">
                Only CSV files are accepted. Maximum file size: 10MB.
              </small>
            </div>

            <button type="submit" class="btn btn-success w-full">
              📤 Upload Students
            </button>
          </form>
            </div>

            <div class="card-footer text-center">
                <p style="margin: 0;">
                    <a href="../database_admin.php" style="font-weight: 500;">📊 View Database</a>
                </p>
                <p style="margin-top: 1rem; margin-bottom: 0;">
                    <a href="dashboard.php" style="color: var(--gray-600);">← Back to Dashboard</a>
                </p>
            </div>
        </div>
    </div>
    </div>
  </div>
</body>
</html>
