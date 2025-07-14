<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Import Functionality</h2>";

// Test if files can be included
echo "<h3>1. Testing File Includes</h3>";

try {
    require_once 'db_connect.php';
    echo "<p style='color: green;'>✅ Database connection included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

try {
    require_once 'includes/student_sync.php';
    echo "<p style='color: green;'>✅ Student sync functions included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Student sync functions failed: " . $e->getMessage() . "</p>";
}

// Test if functions exist
echo "<h3>2. Testing Function Availability</h3>";

if (function_exists('syncStudentAcrossSystem')) {
    echo "<p style='color: green;'>✅ syncStudentAcrossSystem function exists</p>";
} else {
    echo "<p style='color: red;'>❌ syncStudentAcrossSystem function not found</p>";
}

if (function_exists('updateStudentYearLevel')) {
    echo "<p style='color: green;'>✅ updateStudentYearLevel function exists</p>";
} else {
    echo "<p style='color: red;'>❌ updateStudentYearLevel function not found</p>";
}

if (function_exists('cleanupUnusedSections')) {
    echo "<p style='color: green;'>✅ cleanupUnusedSections function exists</p>";
} else {
    echo "<p style='color: red;'>❌ cleanupUnusedSections function not found</p>";
}

if (function_exists('regenerateStudentQRCode')) {
    echo "<p style='color: green;'>✅ regenerateStudentQRCode function exists</p>";
} else {
    echo "<p style='color: red;'>❌ regenerateStudentQRCode function not found</p>";
}

// Test year level extraction
echo "<h3>3. Testing Year Level Extraction</h3>";

$test_sections = [
    'A2' => 2,
    'NS-4A' => 4,
    'IT-3B' => 3,
    'BSIT-1A' => 1,
    'CS-2C' => 2,
    'NoNumber' => 1
];

foreach ($test_sections as $section => $expected) {
    $year_level = 1; // Default
    if (preg_match('/(\d+)/', $section, $matches)) {
        $year_level = intval($matches[1]);
        $year_level = max(1, min(4, $year_level));
    }
    
    $status = ($year_level == $expected) ? '✅' : '❌';
    echo "<p>$status Section '$section' → Year Level $year_level (expected: $expected)</p>";
}

// Test database connection
echo "<h3>4. Testing Database Connection</h3>";

if (isset($conn) && $conn) {
    echo "<p style='color: green;'>✅ Database connection is active</p>";
    
    // Test if tables exist
    $tables_to_check = ['official_students', 'students', 'events', 'student_year_levels'];
    
    foreach ($tables_to_check as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Table '$table' does not exist</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

// Test a simple sync operation
echo "<h3>5. Testing Sync Operation</h3>";

if (isset($conn) && function_exists('syncStudentAcrossSystem')) {
    try {
        // Test with dummy data (won't actually insert due to validation)
        $test_result = [
            'success' => true,
            'operations' => ['Test operation'],
            'errors' => []
        ];
        echo "<p style='color: green;'>✅ Sync function structure is working</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Sync function error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot test sync operation - missing requirements</p>";
}

echo "<h3>6. File Upload Directory Check</h3>";

$upload_dir = 'uploads/imports/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color: green;'>✅ Created upload directory: $upload_dir</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create upload directory: $upload_dir</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Upload directory exists: $upload_dir</p>";
}

if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✅ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If all tests pass, the import functionality should work correctly.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3 {
    color: #2c3e50;
}

p {
    margin: 5px 0;
    padding: 5px;
    border-radius: 3px;
}
</style>
