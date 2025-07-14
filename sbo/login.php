<?php
session_start();
include '../db_connect.php';
include '../includes/system_config.php';

// Get system settings
$system_name = getSystemName($conn);
$system_logo = getSystemLogo($conn);

$error = "";
$redirect = $_GET['redirect'] ?? 'dashboard.php';

// Redirect if already logged in
if (isset($_SESSION['sbo_id'])) {
    if ($redirect && $redirect !== 'dashboard.php') {
        header("Location: ../" . $redirect);
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $query = "SELECT * FROM sbo_users WHERE email = ? AND is_active = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            // Login success: save session
            $_SESSION['sbo_id'] = $user['id'];
            $_SESSION['sbo_email'] = $user['email'];
            $_SESSION['sbo_name'] = $user['full_name'];
            $_SESSION['sbo_position'] = $user['position'];

            // Redirect to requested page or dashboard
            if ($redirect && $redirect !== 'dashboard.php') {
                header("Location: ../" . $redirect);
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found or account is inactive.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SBO Admin Login - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
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


        .admin-login-container {
            width: 100%;
            max-width: 450px;
            background: #ffffff !important;
        }

        .admin-login-card {
            background: #ffffff !important;
            background-color: #ffffff !important;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            position: relative;
            z-index: 12;
        }

        .admin-header {
            background: linear-gradient(135deg, #1e3a8a, #3730a3) !important;
            color: white !important;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }

        .admin-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: white !important;
        }

        .admin-title {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white !important;
        }

        .admin-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            color: white !important;
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

        .admin-login-card {
            animation: fadeInUp 0.8s ease-out;
        }

        .admin-header {
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
            transition: left 3s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Form input animations */
        .form-control:focus {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 58, 138, 0.3);
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
            animation: spin 3s linear infinite;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-header">
                <?php if ($system_logo && file_exists('../' . $system_logo)): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="../<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                    </div>
                <?php else: ?>
                    <div class="admin-logo">👥</div>
                <?php endif; ?>
                <div class="admin-title">SBO Admin Panel</div>
                <div class="admin-subtitle"><?= htmlspecialchars($system_name) ?> - Student Body Organization Portal</div>
            </div>

            <div style="padding: 2rem;">
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                        <strong>Authentication Failed:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="email" style="color: var(--gray-700); font-weight: 600;">
                            📧 Email Address
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-input"
                               placeholder="Enter your SBO admin email"
                               style="padding: 1rem; font-size: 1rem; border: 2px solid var(--gray-300);"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password" style="color: var(--gray-700); font-weight: 600;">
                            🔒 Password
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Enter your secure password"
                               style="padding: 1rem; font-size: 1rem; border: 2px solid var(--gray-300);"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary w-full" id="sboLoginBtn" style="padding: 1rem; font-size: 1.1rem; font-weight: 600; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                        <span id="sboLoginText">🚀 Access SBO Admin Panel</span>
                        <span id="sboLoginSpinner" class="loading-spinner" style="display: none;"></span>
                    </button>
                </form>


            </div>

            <div style="padding: 1.5rem; background: var(--gray-50); border-radius: 0 0 1.5rem 1.5rem; text-align: center; border-top: 1px solid var(--gray-200);">
                <p style="margin: 0;">
                    <a href="../index.php" style="color: var(--gray-600); text-decoration: none; font-weight: 500;">
                        ← Back to Home
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="sboLoadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner-large"></div>
            <p style="margin: 0; color: var(--gray-700);">Accessing SBO Panel...</p>
        </div>
    </div>

    <script>
        // Add fade-in animation to the login card
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.admin-login-card').classList.add('fade-in');
        });

        // Handle form submission with loading animation
        document.querySelector('form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('sboLoginBtn');
            const loginText = document.getElementById('sboLoginText');
            const loginSpinner = document.getElementById('sboLoginSpinner');
            const loadingOverlay = document.getElementById('sboLoadingOverlay');

            // Show button loading state
            loginBtn.disabled = true;
            loginText.style.display = 'none';
            loginSpinner.style.display = 'inline-block';

            // Show loading overlay after a short delay
            setTimeout(() => {
                loadingOverlay.classList.add('active');
            }, 100);
        });
    </script>

<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>
</html>
