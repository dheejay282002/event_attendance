<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🔧 Fixing Sections Table Structure</h2>";
echo "<p>Adding missing columns and fixing import functionality...</p>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Step 1: Check current sections table structure
echo "<p><strong>Step 1: Checking current sections table structure...</strong></p>";

$describe_result = mysqli_query($conn, "DESCRIBE sections");
$existing_columns = [];
while ($row = mysqli_fetch_assoc($describe_result)) {
    $existing_columns[] = $row['Field'];
    echo "<p style='color: blue;'>ℹ️ Found column: {$row['Field']} ({$row['Type']})</p>";
}

// Step 2: Add missing columns
echo "<p><strong>Step 2: Adding missing columns...</strong></p>";

$columns_to_add = [
    'year_id' => "ADD COLUMN year_id INT DEFAULT NULL",
    'section_name' => "ADD COLUMN section_name VARCHAR(100) DEFAULT NULL",
    'max_students' => "ADD COLUMN max_students INT DEFAULT 50"
];

foreach ($columns_to_add as $column => $sql) {
    if (!in_array($column, $existing_columns)) {
        $alter_sql = "ALTER TABLE sections $sql";
        if (mysqli_query($conn, $alter_sql)) {
            echo "<p style='color: green;'>✅ Added column: $column</p>";
            $success_count++;
        } else {
            $error = mysqli_error($conn);
            echo "<p style='color: red;'>❌ Failed to add column $column: " . htmlspecialchars($error) . "</p>";
            $errors[] = $error;
            $error_count++;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Column already exists: $column</p>";
    }
}

// Step 3: Update existing sections with proper data
echo "<p><strong>Step 3: Updating existing sections with proper data...</strong></p>";

// Update section_name where it's NULL
$update_name_sql = "UPDATE sections SET section_name = section_code WHERE section_name IS NULL OR section_name = ''";
if (mysqli_query($conn, $update_name_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✅ Updated section names for $affected sections</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to update section names: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Update year_id based on year_level
$update_year_sql = "UPDATE sections SET year_id = year_level WHERE year_id IS NULL AND year_level IS NOT NULL";
if (mysqli_query($conn, $update_year_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✅ Updated year_id for $affected sections</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to update year_id: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Step 4: Create year_levels table if it doesn't exist
echo "<p><strong>Step 4: Ensuring year_levels table exists...</strong></p>";

$year_levels_sql = "CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_code VARCHAR(10) NOT NULL UNIQUE,
    year_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $year_levels_sql)) {
    echo "<p style='color: green;'>✅ Created/verified year_levels table</p>";
    $success_count++;
} else {
    $error = mysqli_error($conn);
    echo "<p style='color: red;'>❌ Failed to create year_levels table: " . htmlspecialchars($error) . "</p>";
    $errors[] = $error;
    $error_count++;
}

// Step 5: Insert default year levels
echo "<p><strong>Step 5: Adding default year levels...</strong></p>";

$default_year_levels = [
    ['1', '1st Year'],
    ['2', '2nd Year'],
    ['3', '3rd Year'],
    ['4', '4th Year'],
    ['5', '5th Year'],
    ['6', '6th Year'],
    ['7', '7th Year']
];

$year_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO year_levels (year_code, year_name) VALUES (?, ?)");

foreach ($default_year_levels as $year) {
    mysqli_stmt_bind_param($year_stmt, "ss", $year[0], $year[1]);
    
    if (mysqli_stmt_execute($year_stmt)) {
        if (mysqli_stmt_affected_rows($year_stmt) > 0) {
            echo "<p style='color: green;'>✅ Added year level: {$year[1]} (Code: {$year[0]})</p>";
            $success_count++;
        } else {
            echo "<p style='color: blue;'>ℹ️ Year level already exists: {$year[1]} (Code: {$year[0]})</p>";
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add year level {$year[1]}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

// Step 6: Update year_id references to use year_levels table IDs
echo "<p><strong>Step 6: Updating year_id references...</strong></p>";

$year_mapping = [];
$year_result = mysqli_query($conn, "SELECT id, year_code FROM year_levels");
while ($row = mysqli_fetch_assoc($year_result)) {
    $year_mapping[$row['year_code']] = $row['id'];
}

foreach ($year_mapping as $year_code => $year_id) {
    $update_sql = "UPDATE sections SET year_id = ? WHERE year_level = ? AND year_id != ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "iii", $year_id, $year_code, $year_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            echo "<p style='color: green;'>✅ Updated $affected sections to use year_id $year_id for year level $year_code</p>";
            $success_count++;
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to update year_id for year level $year_code: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

echo "</div>";

echo "<h3>Fix Summary</h3>";
echo "<p><strong>Successful operations:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Step 7: Verify the fix
echo "<h3>Verification</h3>";
echo "<p><strong>Updated sections table structure:</strong></p>";

$verify_sections = mysqli_query($conn, "DESCRIBE sections");
echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background-color: #f8f9fa;'><th style='padding: 8px;'>Column</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th></tr>";

while ($row = mysqli_fetch_assoc($verify_sections)) {
    $highlight = in_array($row['Field'], ['year_id', 'section_name', 'max_students']) ? "style='background-color: #d4edda;'" : "";
    echo "<tr $highlight>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample sections data
echo "<p><strong>Sample sections data:</strong></p>";
$sample_sections = mysqli_query($conn, "SELECT section_code, section_name, course_id, year_level, year_id, max_students FROM sections LIMIT 10");

if (mysqli_num_rows($sample_sections) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Section Code</th>";
    echo "<th style='padding: 8px;'>Section Name</th>";
    echo "<th style='padding: 8px;'>Course ID</th>";
    echo "<th style='padding: 8px;'>Year Level</th>";
    echo "<th style='padding: 8px;'>Year ID</th>";
    echo "<th style='padding: 8px;'>Max Students</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($sample_sections)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section_code']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['section_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['course_id']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['year_level']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['year_id']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['max_students']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

if ($error_count == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 Sections Table Structure Fixed!</h3>";
    echo "<p>The sections table now has all required columns for import functionality.</p>";
    echo "<p><strong>Fixed columns:</strong></p>";
    echo "<ul>";
    echo "<li>✅ year_id - References year_levels table</li>";
    echo "<li>✅ section_name - Human-readable section names</li>";
    echo "<li>✅ max_students - Maximum students per section (default: 50)</li>";
    echo "</ul>";
    echo "<p><strong>Import functionality should now work properly!</strong></p>";
    echo "<ul>";
    echo "<li>Try importing CSV data through Admin panel</li>";
    echo "<li>Try importing CSV data through SBO panel</li>";
    echo "<li>Import your Harry Potter student data</li>";
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

table {
    font-size: 0.9rem;
    width: 100%;
}

th {
    background-color: #6c757d !important;
    color: white;
}

td, th {
    border: 1px solid #dee2e6;
}
</style>
