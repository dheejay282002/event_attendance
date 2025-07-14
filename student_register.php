<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

$error = "";
$success = "";
$studentData = null;

// When Student ID is submitted
if (isset($_POST['check_id'])) {
    $student_id = trim($_POST['student_id']);

    $check_query = "SELECT * FROM official_students WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        // Check if already registered
        $check_registered = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
        mysqli_stmt_bind_param($check_registered, "s", $student_id);
        mysqli_stmt_execute($check_registered);
        mysqli_stmt_store_result($check_registered);

        if (mysqli_stmt_num_rows($check_registered) > 0) {
            $error = "❌ This Student ID is already registered. Please login instead.";
        } else {
            $studentData = mysqli_fetch_assoc($result);
        }
    } else {
        $error = "❌ Student ID not found in the system. Please contact your administrator to ensure you have been added to the official student list.";
    }
}

// When final form is submitted
if (isset($_POST['register'])) {
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $photo_name = $_FILES['photo']['name'];
    $photo_path = "uploads/" . $student_id . "_" . basename($photo_name);

    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).{8,}$/', $password)) {
        $error = "❌ Password must be at least 8 characters and include uppercase, lowercase, and a number.";
    } elseif (!move_uploaded_file($photo_tmp, $photo_path)) {
        $error = "❌ Failed to upload photo.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Get official student info again
        $lookup = mysqli_prepare($conn, "SELECT full_name, course, section FROM official_students WHERE student_id = ?");
        mysqli_stmt_bind_param($lookup, "s", $student_id);
        mysqli_stmt_execute($lookup);
        $info = mysqli_stmt_get_result($lookup);
        $official = mysqli_fetch_assoc($info);

        $insert = mysqli_prepare($conn, "INSERT INTO students (student_id, full_name, course, section, password, photo) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "ssssss", $student_id, $official['full_name'], $official['course'], $official['section'], $hashed_password, $photo_path);

        if (mysqli_stmt_execute($insert)) {
            $success = "✅ Registration complete! You may now log in.";
        } else {
            $error = "❌ Failed to save your information.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include 'includes/system_config.php';
    echo generateFaviconTags($conn);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - ADLOR</title>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Registration Page Animations */
        .login-container {
            animation: fadeInUp 0.8s ease-out;
        }

        .login-card {
            animation: slideInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .card-header h2 {
            animation: slideInDown 0.8s ease-out 0.2s both;
        }

        .card-header p {
            animation: fadeIn 1s ease-out 0.4s both;
        }

        .form-group {
            animation: slideInLeft 0.6s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }

        .btn {
            animation: bounceIn 0.8s ease-out 0.6s both;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .alert {
            animation: slideInDown 0.5s ease-out;
        }

        .card-footer {
            animation: fadeIn 1s ease-out 0.8s both;
        }

        /* Keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Input focus animations */
        .form-input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Success/Error message animations */
        .alert-success {
            animation: successPulse 0.6s ease-out;
        }

        .alert-error {
            animation: errorShake 0.6s ease-out;
        }

        @keyframes successPulse {
            0% {
                transform: scale(0.95);
                background-color: rgba(16, 185, 129, 0.1);
            }
            50% {
                transform: scale(1.02);
                background-color: rgba(16, 185, 129, 0.2);
            }
            100% {
                transform: scale(1);
                background-color: rgba(16, 185, 129, 0.15);
            }
        }

        @keyframes errorShake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(5px);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card card" style="max-width: 500px;">
            <div class="card-header text-center">
                <h2 style="margin-bottom: 0.5rem;">Student Registration</h2>
                <p style="color: var(--gray-600); margin: 0;">Join the system</p>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$studentData && !$success): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="student_id">Student ID</label>
                            <input type="text"
                                   id="student_id"
                                   name="student_id"
                                   class="form-input"
                                   placeholder="Enter your official student ID"
                                   required>
                            <small style="color: var(--gray-600); font-size: 0.875rem;">
                                Your student ID must be in the official student list.
                            </small>
                        </div>
                        <button type="submit" name="check_id" class="btn btn-primary w-full">
                            Verify Student ID
                        </button>
                    </form>

                <?php elseif ($studentData): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                            <h4 style="margin: 0 0 0.5rem 0;">Student Information Verified</h4>
                            <p style="margin: 0;">Please complete your registration below.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text"
                                   class="form-input"
                                   value="<?= htmlspecialchars($studentData['full_name']) ?>"
                                   readonly
                                   style="background: var(--gray-100);">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Course</label>
                                <input type="text"
                                       class="form-input"
                                       value="<?= htmlspecialchars($studentData['course']) ?>"
                                       readonly
                                       style="background: var(--gray-100);">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Section</label>
                                <input type="text"
                                       class="form-input"
                                       value="<?= htmlspecialchars($studentData['section']) ?>"
                                       readonly
                                       style="background: var(--gray-100);">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-input"
                                   placeholder="Create a strong password"
                                   required>
                            <small style="color: var(--gray-600); font-size: 0.875rem;">
                                Must be at least 8 characters with uppercase, lowercase, and number.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-input"
                                   placeholder="Confirm your password"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="photo">ID Photo</label>
                            <input type="file"
                                   id="photo"
                                   name="photo"
                                   class="form-input"
                                   accept="image/*"
                                   required>
                            <small style="color: var(--gray-600); font-size: 0.875rem;">
                                Upload a clear photo of your student ID or profile picture.
                            </small>
                        </div>

                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentData['student_id']) ?>">
                        <button type="submit" name="register" class="btn btn-success w-full">
                            Complete Registration
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card-footer text-center">
                <p style="margin: 0;">
                    Already have an account?
                    <a href="student_login.php" style="font-weight: 500;">Login here</a>
                </p>
                <p style="margin-top: 1rem; margin-bottom: 0;">
                    <a href="index.php" style="color: var(--gray-600);">← Back to Home</a>
                </p>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
