<?php
/**
 * Script to add ADLOR Animation System to all PHP pages
 * This will inject the animation script before the closing </body> tag
 */

echo "<h2>🎬 Adding ADLOR Animation System to All Pages</h2>";

$animation_script = '
<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>';

$admin_animation_script = '
<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>';

$sbo_animation_script = '
<!-- ADLOR Animation System -->
<script src="../assets/js/adlor-animations.js"></script>

</body>';

$success_count = 0;
$error_count = 0;

// Function to add animation script to a file
function addAnimationScript($file_path, $script_to_add) {
    global $success_count, $error_count;
    
    if (!file_exists($file_path)) {
        echo "<p style='color: orange;'>⚠️ File not found: $file_path</p>";
        $error_count++;
        return;
    }
    
    $content = file_get_contents($file_path);
    
    // Check if animation script is already added
    if (strpos($content, 'adlor-animations.js') !== false) {
        echo "<p style='color: blue;'>ℹ️ Animation already added: $file_path</p>";
        return;
    }
    
    // Replace </body> with animation script + </body>
    if (strpos($content, '</body>') !== false) {
        $new_content = str_replace('</body>', $script_to_add, $content);
        
        if (file_put_contents($file_path, $new_content)) {
            echo "<p style='color: green;'>✅ Added animations to: $file_path</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Failed to write: $file_path</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No </body> tag found in: $file_path</p>";
        $error_count++;
    }
}

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Root directory PHP files
$root_files = [
    'student_register.php',
    'student_dashboard.php',
    'student_qr_codes.php',
    'student_settings.php',
    'scan_qr.php',
    'logout.php'
];

echo "<h3>📁 Root Directory Files</h3>";
foreach ($root_files as $file) {
    if (file_exists($file)) {
        addAnimationScript($file, $animation_script);
    }
}

// Admin directory files
$admin_files = [
    'admin/login.php',
    'admin/dashboard.php',
    'admin/manage_students.php',
    'admin/manage_events.php',
    'admin/manage_academics.php',
    'admin/data_management.php',
    'admin/settings.php',
    'admin/attendance_reports.php'
];

echo "<h3>👨‍💼 Admin Directory Files</h3>";
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        addAnimationScript($file, $admin_animation_script);
    }
}

// SBO directory files
$sbo_files = [
    'sbo/login.php',
    'sbo/dashboard.php',
    'sbo/manage_events.php',
    'sbo/attendance_reports.php',
    'sbo/settings.php'
];

echo "<h3>👥 SBO Directory Files</h3>";
foreach ($sbo_files as $file) {
    if (file_exists($file)) {
        addAnimationScript($file, $sbo_animation_script);
    }
}

// Scan for additional PHP files
echo "<h3>🔍 Scanning for Additional PHP Files</h3>";

$additional_files = glob('*.php');
foreach ($additional_files as $file) {
    if (!in_array($file, ['index.php', 'student_login.php', 'help.php']) && 
        !in_array($file, $root_files) && 
        !strpos($file, 'add_animations_to_all_pages.php')) {
        addAnimationScript($file, $animation_script);
    }
}

echo "</div>";

echo "<h3>📊 Summary</h3>";
echo "<p><strong>✅ Successfully added animations:</strong> $success_count files</p>";
echo "<p><strong>❌ Failed operations:</strong> $error_count files</p>";

if ($success_count > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 ADLOR Animation System Deployed!</h3>";
    echo "<p>The comprehensive animation system has been added to $success_count pages across your ADLOR system.</p>";
    echo "<p><strong>Features now active system-wide:</strong></p>";
    echo "<ul>";
    echo "<li>🌟 Animated background with subtle gradients</li>";
    echo "<li>✨ Floating particles on all pages</li>";
    echo "<li>🎭 Entrance animations for all elements</li>";
    echo "<li>🎯 Hover effects on buttons and cards</li>";
    echo "<li>📱 Interactive ripple effects</li>";
    echo "<li>🔄 Loading animations for forms</li>";
    echo "<li>🎨 Smooth transitions throughout</li>";
    echo "<li>💫 Page-specific animations</li>";
    echo "</ul>";
    echo "<p><strong>Your entire ADLOR system now has beautiful, professional animations!</strong></p>";
    echo "</div>";
}

echo "<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3 {
    color: #2c3e50;
}

p {
    margin: 0.5rem 0;
}
</style>";
?>
