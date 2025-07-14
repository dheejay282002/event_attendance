<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favicon Test - HOGWARTS</title>
    <?php
    if (file_exists('includes/system_config.php')) {
        include 'includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .logo-preview { text-align: center; margin: 20px 0; }
        .logo-preview img { max-width: 150px; border-radius: 50%; border: 3px solid #ddd; }
    </style>
</head>
<body>
    <h1>🎨 Favicon Test Page</h1>
    <p>This page tests if the system logo appears in the browser tab.</p>
    
    <div class="logo-preview">
        <h3>Current System Logo:</h3>
        <?php
        include 'db_connect.php';
        include 'includes/system_config.php';
        $logo = getSystemLogo($conn);
        if ($logo && file_exists($logo)) {
            echo "<img src='$logo' alt='System Logo'>";
            echo "<p>✅ Logo should appear in browser tab</p>";
        } else {
            echo "<p>⚠️ Using fallback favicon with system name initial</p>";
        }
        ?>
    </div>
    
    <p><strong>Instructions:</strong></p>
    <ul>
        <li>Look at the browser tab for this page</li>
        <li>You should see your system logo as the favicon</li>
        <li>If you see a letter instead, upload a logo in Admin → Settings</li>
    </ul>
    
    <p><a href="index.php">← Back to Homepage</a></p>
</body>
</html>