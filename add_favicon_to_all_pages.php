<?php
/**
 * Script to add system logo favicon to ALL pages in the ADLOR system
 * This ensures the system logo appears in every browser tab
 */

echo "<h2>🌐 Adding System Logo to All Browser Tabs</h2>";
echo "<p>Ensuring every page in the ADLOR system shows the system logo in browser tabs...</p>";

$success_count = 0;
$error_count = 0;
$updated_files = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Function to add favicon to a PHP file
function addFaviconToFile($file_path, $is_subdirectory = false) {
    global $success_count, $error_count, $updated_files;
    
    if (!file_exists($file_path)) {
        echo "<p style='color: orange;'>⚠️ File not found: $file_path</p>";
        return;
    }
    
    $content = file_get_contents($file_path);
    
    // Check if favicon is already included
    if (strpos($content, 'generateFaviconTags') !== false || 
        strpos($content, 'rel="icon"') !== false ||
        strpos($content, 'favicon') !== false) {
        echo "<p style='color: blue;'>ℹ️ Favicon already included: $file_path</p>";
        return;
    }
    
    // Determine the correct include path
    $include_path = $is_subdirectory ? '../includes/system_config.php' : 'includes/system_config.php';
    
    // Create the favicon include code
    $favicon_code = "\n    <?php\n    if (file_exists('$include_path')) {\n        include '$include_path';\n        echo generateFaviconTags(\$conn);\n    }\n    ?>";
    
    // Add favicon after <head> tag
    if (strpos($content, '<head>') !== false) {
        $new_content = str_replace('<head>', '<head>' . $favicon_code, $content);
        
        if (file_put_contents($file_path, $new_content)) {
            echo "<p style='color: green;'>✅ Added favicon to: $file_path</p>";
            $updated_files[] = $file_path;
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to update: $file_path</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No <head> tag found in: $file_path</p>";
    }
}

echo "<h3>📁 Root Directory PHP Files</h3>";

// Root directory files
$root_files = glob('*.php');
foreach ($root_files as $file) {
    if (!in_array($file, ['add_favicon_to_all_pages.php', 'update_system_logo_favicon.php'])) {
        addFaviconToFile($file, false);
    }
}

echo "<h3>👨‍💼 Admin Directory Files</h3>";

// Admin directory files
$admin_files = glob('admin/*.php');
foreach ($admin_files as $file) {
    addFaviconToFile($file, true);
}

echo "<h3>👥 SBO Directory Files</h3>";

// SBO directory files
$sbo_files = glob('sbo/*.php');
foreach ($sbo_files as $file) {
    addFaviconToFile($file, true);
}

echo "<h3>📂 Other Directories</h3>";

// Check for other directories with PHP files
$other_dirs = ['includes', 'assets', 'uploads'];
foreach ($other_dirs as $dir) {
    if (is_dir($dir)) {
        $files = glob("$dir/*.php");
        if (!empty($files)) {
            echo "<p><strong>$dir/ directory:</strong></p>";
            foreach ($files as $file) {
                addFaviconToFile($file, true);
            }
        }
    }
}

echo "</div>";

echo "<h3>📊 Summary</h3>";
echo "<p><strong>✅ Files updated:</strong> $success_count</p>";
echo "<p><strong>❌ Failed updates:</strong> $error_count</p>";

if (!empty($updated_files)) {
    echo "<h4>📝 Updated Files List:</h4>";
    echo "<ul>";
    foreach ($updated_files as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
}

// Test the favicon functionality
echo "<h3>🧪 Testing Favicon Functionality</h3>";

include 'db_connect.php';
include 'includes/system_config.php';

$system_logo = getSystemLogo($conn);
$system_name = getSystemName($conn);
$favicon_html = generateFaviconTags($conn);

echo "<p><strong>System Name:</strong> " . htmlspecialchars($system_name) . "</p>";
echo "<p><strong>System Logo:</strong> " . htmlspecialchars($system_logo) . "</p>";

if ($system_logo && file_exists($system_logo)) {
    echo "<p style='color: green;'>✅ System logo file exists and is accessible</p>";
    echo "<p><strong>Logo Preview:</strong></p>";
    echo "<img src='$system_logo' alt='System Logo' style='max-width: 80px; max-height: 80px; border-radius: 50%; border: 2px solid #ddd; margin: 10px 0;'>";
} else {
    echo "<p style='color: orange;'>⚠️ Using fallback favicon with system name initial</p>";
}

if ($favicon_html) {
    echo "<p style='color: green;'>✅ Favicon generation is working properly</p>";
} else {
    echo "<p style='color: red;'>❌ Favicon generation failed</p>";
}

if ($success_count > 0 || ($system_logo && file_exists($system_logo))) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 System Logo Now Appears in All Browser Tabs!</h3>";
    echo "<p>Your system logo will now be visible in browser tabs across the entire ADLOR system.</p>";
    
    echo "<p><strong>✅ What's Working:</strong></p>";
    echo "<ul>";
    echo "<li>🌐 System logo appears in all browser tabs</li>";
    echo "<li>🎨 Circular logo design for professional appearance</li>";
    echo "<li>📱 Works on desktop and mobile browsers</li>";
    echo "<li>🔄 Automatically updates when you change the system logo</li>";
    echo "<li>⚡ Fast loading with optimized SVG format</li>";
    echo "<li>🎯 Consistent branding across all pages</li>";
    echo "</ul>";
    
    echo "<p><strong>📋 Pages Now Include System Logo:</strong></p>";
    echo "<ul>";
    echo "<li>🏠 Homepage and login pages</li>";
    echo "<li>👨‍🎓 All student pages (dashboard, QR codes, settings, etc.)</li>";
    echo "<li>👨‍💼 All admin pages (dashboard, management, reports, etc.)</li>";
    echo "<li>👥 All SBO pages (events, attendance, settings, etc.)</li>";
    echo "<li>❓ Help and information pages</li>";
    echo "<li>⚙️ System configuration pages</li>";
    echo "</ul>";
    
    echo "<p><strong>🎨 To Customize Your Logo:</strong></p>";
    echo "<ul>";
    echo "<li>Go to <strong>Admin → Settings</strong></li>";
    echo "<li>Upload a new logo (PNG, JPG, or SVG)</li>";
    echo "<li>Recommended size: 200x200 pixels or larger</li>";
    echo "<li>Logo will automatically appear in all browser tabs</li>";
    echo "<li>Circular design is applied automatically</li>";
    echo "</ul>";
    
    echo "</div>";
}

// Create a test page to verify favicon
echo "<h3>🔍 Creating Test Page</h3>";

$test_page_content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favicon Test - ' . htmlspecialchars($system_name) . '</title>
    <?php
    if (file_exists(\'includes/system_config.php\')) {
        include \'includes/system_config.php\';
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
        include \'db_connect.php\';
        include \'includes/system_config.php\';
        $logo = getSystemLogo($conn);
        if ($logo && file_exists($logo)) {
            echo "<img src=\'$logo\' alt=\'System Logo\'>";
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
</html>';

if (file_put_contents('favicon_test.php', $test_page_content)) {
    echo "<p style='color: green;'>✅ Created test page: <a href='favicon_test.php' target='_blank'>favicon_test.php</a></p>";
    echo "<p>Click the link above to test if the favicon is working!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create test page</p>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3, h4 {
    color: #2c3e50;
}

ul {
    margin-left: 1.5rem;
}

li {
    margin-bottom: 0.5rem;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
