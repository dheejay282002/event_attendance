<?php
session_start();
include 'db_connect.php';
include 'includes/system_config.php';

// Get system settings
$system_name = getSystemName($conn);
$system_logo = getSystemLogo($conn);

$error = "";

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST["student_id"]);
    $password = $_POST["password"];

    $query = "SELECT * FROM students WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            // Login success: save session
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: student_dashboard.php");
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Student ID not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
        }

        body {
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
            background-attachment: fixed !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: cover !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-family: 'Inter', sans-serif;
        }

        /* Remove any pseudo-elements that might create patterns */
        *::before, *::after,
        html::before, html::after,
        body::before, body::after {
            display: none !important;
            content: none !important;
            background: none !important;
        }

        /* Ensure no background patterns from any source */
        .login-container,
        .login-card,
        .card-body {
            background-image: none !important;
        }


        /* Override any potential background patterns */
        body {
            background: #ffffff !important;
            background-image: none !important;
        }

        /* Ensure page container is white */
        .login-container {
            background: #ffffff !important;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: white !important;
            position: relative;
            z-index: 5;
        }

        /* Ensure no background patterns or elements */
        .login-container::before,
        .login-container::after,
        .login-card::before,
        .login-card::after {
            display: none !important;
            content: none !important;
        }

        .login-card {
            background: white !important;
            background-color: white !important;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            position: relative;
            z-index: 10;
        }

        .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: white !important;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }

        .card-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.875rem;
            font-weight: 700;
        }

        .card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color:rgb(5, 5, 5);
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #374151;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .footer-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 2rem;
            padding-bottom:2rem;
            border-top: 10px solid #e5e7eb;
        }

        .footer-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .footer-link a:hover {
            color: #3b82f6;
        }

        /* Additional background overrides */
        html {
            background: #ffffff !important;
        }

        body {
            background: #ffffff !important;
        }

        /* Override any inherited backgrounds */
        div, section, main, article {
            background-image: none !important;
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
                background: #ffffff !important;
            }

            .card-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }
        }

        /* Login Animations */
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

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: 200px 0;
            }
        }

        .login-card {
            animation: fadeInUp 0.8s ease-out;
        }

        .card-header {
            animation: slideInFromLeft 1s ease-out 0.2s both;
        }

        .form-group {
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.6s;
        }

        .btn {
            animation: fadeInUp 0.6s ease-out 0.8s both;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            animation: pulse 0.6s ease-in-out;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -200px;
            width: 200px;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Form input animations */
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        /* Loading spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <?php if ($system_logo && file_exists($system_logo)): ?>
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <img src="<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color);">
                    </div>
                <?php endif; ?>
                <h2>🎓 Student Portal</h2>
                <p>Access your <?= htmlspecialchars($system_name) ?> student dashboard</p>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="student_id">
                            <span style="margin-right: 0.5rem;">🆔</span>Student ID
                        </label>
                        <input type="text"
                               id="student_id"
                               name="student_id"
                               class="form-input"
                               placeholder="e.g., 23-11797"
                               pattern="[0-9]{2}-[0-9]{5}"
                               title="Format: XX-XXXXX (e.g., 23-11797)"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <span style="margin-right: 0.5rem;">🔒</span>Password
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Enter your password"
                               required>
                    </div>

                    <button type="submit" class="btn">
                        🎓 Access Student Portal
                    </button>
                </form>
            </div>
            
                <div class="footer-link">
                    <a href="student_register.php">Don't have an account? Register here</a>
                    <br><br>
                    <a href="index.php">← Back to  <?= htmlspecialchars($system_name) ?> Home</a>
                </div>
            </div>
        </div>
    </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
