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
if (isset($_SESSION['admin_id'])) {
    if ($redirect && $redirect !== 'dashboard.php') {
        header("Location: ../" . $redirect);
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// Default admin credentials (you can change these)
$admin_credentials = [
    'admin@adlor.edu' => 'admin123456',
    'superadmin@adlor.edu' => 'superadmin123',
    'system@adlor.edu' => 'adl0rsecure2025'
];

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    if (isset($admin_credentials[$email]) && $admin_credentials[$email] === $password) {
        // Login success: save session
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = 'System Administrator';
        $_SESSION['admin_role'] = 'Super Admin';

        // Redirect to requested page or dashboard
        if ($redirect && $redirect !== 'dashboard.php') {
            header("Location: ../" . $redirect);
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= htmlspecialchars($system_name) ?></title>
    <?= generateFaviconTags($conn) ?>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-family: 'Inter', sans-serif;
        }

        .admin-login-container {
            width: 100%;
            max-width: 450px;
        }

        .admin-login-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .admin-header {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }

        .admin-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .admin-title {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .admin-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color:rgb(255, 255, 255);
            font-size: 0.875rem;
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
        .admin-header {
            background: linear-gradient(135deg,rgb(88, 34, 160),rgb(155, 72, 211)) !important;
            color: white !important;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #374151;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
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
            background: linear-gradient(135deg, #5b21b6, #4c1d95);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
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

        .card-body {
            padding: 2rem;
        }

        .footer-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .footer-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .footer-link a:hover {
            color: #7c3aed;
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .admin-header {
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
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Form input animations */
        .form-control:focus {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
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
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-header">
                <?php if ($system_logo && file_exists('../' . $system_logo)): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="../<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color);">
                    </div>
                <?php else: ?>
                    <div class="admin-logo">⚙️</div>
                <?php endif; ?>
                <div class="admin-title">System Admin Panel</div>
                <div class="admin-subtitle"><?= htmlspecialchars($system_name) ?> Administration Portal</div>
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
                            📧 Admin Email
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               placeholder="Enter your admin email"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password" style="color: var(--gray-700); font-weight: 600;">
                            🔒 Password
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Enter your admin password"
                               required>
                    </div>

                    <button type="submit" class="btn">
                        ⚙️ Access Admin Panel
                    </button>
                </form>
                

            </div>
            
            <div style="padding: 1.5rem; background: var(--gray-50); border-radius: 0 0 1.5rem 1.5rem; text-align: center; border-top: 1px solid var(--gray-200);">
                <p style="margin: 0;">
                    <a href="../index.php" style="color: var(--gray-600); text-decoration: none; font-weight: 500;">
                        ← Back to <?= htmlspecialchars($system_name) ?> Home
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="adminLoadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner-large"></div>
            <p style="margin: 0; color: var(--gray-700);">Accessing Admin Panel...</p>
        </div>
    </div>

    <script>
        // Add fade-in animation to the login card
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.admin-login-card').classList.add('fade-in');
        });

        // Handle form submission with loading animation
        document.querySelector('form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('adminLoginBtn');
            const loginText = document.getElementById('adminLoginText');
            const loginSpinner = document.getElementById('adminLoginSpinner');
            const loadingOverlay = document.getElementById('adminLoadingOverlay');

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
