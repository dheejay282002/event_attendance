<?php
// facial_recognition.php
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
    <title>Facial Recognition - Maintenance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0e0e0e;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .alert-box {
            background-color: #1a1a1a;
            border: 2px solid #ffcc00;
            padding: 40px;
            border-radius: 15px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 0 30px rgba(255, 204, 0, 0.3);
        }

        .alert-title {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #ffcc00;
        }

        .alert-message {
            font-size: 16px;
            color: #dddddd;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="alert-box">
        <div class="alert-title">System Maintenance</div>
        <div class="alert-message">
            The facial recognition feature is curr
            ently under maintenance.<br><br>
            Please check back later or contact the system administrator for assistance.
        </div>
    </div>
</body>
</html>
