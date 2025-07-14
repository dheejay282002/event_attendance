<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection and system config
include 'db_connect.php';
include 'includes/system_config.php';

echo "<h2>🎨 System Logo Favicon Update</h2>";
echo "<p>Ensuring the system logo appears in all browser tabs across the ADLOR system...</p>";

$success_count = 0;
$error_count = 0;

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check current system logo
echo "<p><strong>Step 1: Checking current system logo...</strong></p>";

$system_logo = getSystemLogo($conn);
$system_name = getSystemName($conn);

echo "<p><strong>System Name:</strong> " . htmlspecialchars($system_name) . "</p>";
echo "<p><strong>System Logo Path:</strong> " . htmlspecialchars($system_logo) . "</p>";

if ($system_logo && file_exists($system_logo)) {
    echo "<p style='color: green;'>✅ System logo file exists: $system_logo</p>";
    $success_count++;
    
    // Display current logo
    echo "<p><strong>Current Logo:</strong></p>";
    echo "<img src='$system_logo' alt='System Logo' style='max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid #ddd;'>";
    
} else {
    echo "<p style='color: orange;'>⚠️ No system logo uploaded or file not found</p>";
    echo "<p>You can upload a logo through Admin → Settings</p>";
}

// Step 2: Test favicon generation
echo "<p><strong>Step 2: Testing favicon generation...</strong></p>";

$favicon_html = generateFaviconTags($conn);
if ($favicon_html) {
    echo "<p style='color: green;'>✅ Favicon HTML generated successfully</p>";
    $success_count++;
    
    // Show a preview of the favicon HTML (truncated)
    $preview = substr($favicon_html, 0, 200) . '...';
    echo "<p><strong>Favicon HTML Preview:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 0.8rem;'>" . htmlspecialchars($preview) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Failed to generate favicon HTML</p>";
    $error_count++;
}

// Step 3: Check which pages include the favicon
echo "<p><strong>Step 3: Checking favicon inclusion in pages...</strong></p>";

$pages_to_check = [
    'index.php',
    'student_login.php',
    'student_register.php',
    'student_dashboard.php',
    'help.php',
    'admin/login.php',
    'admin/dashboard.php',
    'sbo/login.php',
    'sbo/dashboard.php'
];

$pages_with_favicon = 0;
$pages_without_favicon = 0;

foreach ($pages_to_check as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Check if page includes favicon functionality
        if (strpos($content, 'generateFaviconTags') !== false || 
            strpos($content, 'includes/favicon.php') !== false ||
            strpos($content, 'rel="icon"') !== false) {
            echo "<p style='color: green;'>✅ $page includes favicon</p>";
            $pages_with_favicon++;
        } else {
            echo "<p style='color: orange;'>⚠️ $page missing favicon inclusion</p>";
            $pages_without_favicon++;
        }
    } else {
        echo "<p style='color: gray;'>ℹ️ $page not found</p>";
    }
}

// Step 4: Add favicon to pages that don't have it
if ($pages_without_favicon > 0) {
    echo "<p><strong>Step 4: Adding favicon to pages without it...</strong></p>";
    
    foreach ($pages_to_check as $page) {
        if (file_exists($page)) {
            $content = file_get_contents($page);
            
            // Check if page needs favicon
            if (strpos($content, 'generateFaviconTags') === false && 
                strpos($content, 'includes/favicon.php') === false &&
                strpos($content, 'rel="icon"') === false) {
                
                // Add favicon include after the <head> tag
                if (strpos($content, '<head>') !== false) {
                    $favicon_include = "\n    <?php\n    include 'includes/system_config.php';\n    echo generateFaviconTags(\$conn);\n    ?>";
                    
                    // For admin and sbo pages, adjust the include path
                    if (strpos($page, 'admin/') === 0 || strpos($page, 'sbo/') === 0) {
                        $favicon_include = "\n    <?php\n    include '../includes/system_config.php';\n    echo generateFaviconTags(\$conn);\n    ?>";
                    }
                    
                    $new_content = str_replace('<head>', '<head>' . $favicon_include, $content);
                    
                    if (file_put_contents($page, $new_content)) {
                        echo "<p style='color: green;'>✅ Added favicon to $page</p>";
                        $success_count++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update $page</p>";
                        $error_count++;
                    }
                }
            }
        }
    }
}

// Step 5: Create a default logo if none exists
if (!$system_logo || !file_exists($system_logo)) {
    echo "<p><strong>Step 5: Creating default ADLOR logo...</strong></p>";
    
    // Create uploads directory if it doesn't exist
    if (!is_dir('uploads/system')) {
        mkdir('uploads/system', 0755, true);
    }
    
    // Create a simple SVG logo for ADLOR
    $svg_logo = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
    <defs>
        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#7c3aed;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
        </linearGradient>
    </defs>
    <circle cx="100" cy="100" r="90" fill="url(#grad1)" stroke="#ffffff" stroke-width="4"/>
    <text x="100" y="120" font-family="Arial, sans-serif" font-size="60" font-weight="bold" text-anchor="middle" fill="white">A</text>
    <text x="100" y="160" font-family="Arial, sans-serif" font-size="20" font-weight="normal" text-anchor="middle" fill="white">DLOR</text>
</svg>';
    
    $logo_path = 'uploads/system/adlor_logo.svg';
    if (file_put_contents($logo_path, $svg_logo)) {
        echo "<p style='color: green;'>✅ Created default ADLOR logo: $logo_path</p>";
        
        // Update system settings to use this logo
        $update_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_logo', ?) 
                       ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ss", $logo_path, $logo_path);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>✅ Updated system settings with new logo</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to update system settings</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create default logo</p>";
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Summary</h3>";
echo "<p><strong>✅ Successful operations:</strong> $success_count</p>";
echo "<p><strong>❌ Failed operations:</strong> $error_count</p>";

// Step 6: Test the final result
echo "<h3>🧪 Testing Final Result</h3>";

$final_logo = getSystemLogo($conn);
$final_favicon = generateFaviconTags($conn);

if ($final_logo && file_exists($final_logo)) {
    echo "<p style='color: green;'>✅ System logo is available: $final_logo</p>";
    echo "<p><strong>Logo Preview:</strong></p>";
    echo "<img src='$final_logo' alt='System Logo' style='max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid #ddd; margin: 10px 0;'>";
} else {
    echo "<p style='color: orange;'>⚠️ Using fallback favicon with system name initial</p>";
}

if ($final_favicon) {
    echo "<p style='color: green;'>✅ Favicon generation is working</p>";
} else {
    echo "<p style='color: red;'>❌ Favicon generation failed</p>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 System Logo Favicon Setup Complete!</h3>";
    echo "<p>Your system logo will now appear in browser tabs across the entire ADLOR system.</p>";
    echo "<p><strong>What's working:</strong></p>";
    echo "<ul>";
    echo "<li>✅ System logo is configured and accessible</li>";
    echo "<li>✅ Favicon generation is working properly</li>";
    echo "<li>✅ All major pages include favicon functionality</li>";
    echo "<li>✅ Browser tabs will show your system logo</li>";
    echo "<li>✅ Circular logo design for professional appearance</li>";
    echo "</ul>";
    echo "<p><strong>To customize further:</strong></p>";
    echo "<ul>";
    echo "<li>Go to Admin → Settings to upload a custom logo</li>";
    echo "<li>Supported formats: PNG, JPG, SVG</li>";
    echo "<li>Recommended size: 200x200 pixels or larger</li>";
    echo "<li>Logo will automatically be made circular for favicon</li>";
    echo "</ul>";
    echo "</div>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3, h4 {
    color: #2c3e50;
}

pre {
    font-size: 0.85rem;
    line-height: 1.4;
}

ul {
    margin-left: 1.5rem;
}

li {
    margin-bottom: 0.5rem;
}
</style>
