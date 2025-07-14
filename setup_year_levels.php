<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Setting up Default Year Levels</h2>";

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

// Create year_levels table first if it doesn't exist
$year_levels_table_sql = "CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_code VARCHAR(10) NOT NULL UNIQUE,
    year_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $year_levels_table_sql)) {
    echo "<p style='color: green;'>✅ Created/verified year_levels table</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating year_levels table: " . mysqli_error($conn) . "</p>";
}

// Default year levels
$default_year_levels = [
    [
        'year_code' => '1',
        'year_name' => 'First Year'
    ],
    [
        'year_code' => '2',
        'year_name' => 'Second Year'
    ],
    [
        'year_code' => '3',
        'year_name' => 'Third Year'
    ],
    [
        'year_code' => '4',
        'year_name' => 'Fourth Year'
    ]
];

echo "<p><strong>Adding default year levels...</strong></p>";

$stmt = mysqli_prepare($conn, "INSERT IGNORE INTO year_levels (year_code, year_name) VALUES (?, ?)");

foreach ($default_year_levels as $year) {
    mysqli_stmt_bind_param($stmt, "ss", $year['year_code'], $year['year_name']);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "<p style='color: green;'>✅ Added year level: {$year['year_name']} (Level {$year['year_code']})</p>";
            $success_count++;
        } else {
            echo "<p style='color: blue;'>ℹ️ Year level already exists: {$year['year_name']} (Level {$year['year_code']})</p>";
        }
    } else {
        $error = mysqli_error($conn);
        echo "<p style='color: red;'>❌ Failed to add {$year['year_name']}: " . htmlspecialchars($error) . "</p>";
        $errors[] = $error;
        $error_count++;
    }
}

mysqli_stmt_close($stmt);

echo "</div>";

echo "<h3>Setup Summary</h3>";
echo "<p><strong>New year levels added:</strong> $success_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Errors encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Show current year levels
echo "<h3>Current Year Levels</h3>";
$years_result = mysqli_query($conn, "SELECT year_code, year_name, is_active, created_at FROM year_levels ORDER BY year_code ASC");

if (mysqli_num_rows($years_result) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Year Level</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Year Name</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
    echo "<th style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: left;'>Created</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($years_result)) {
        $status = $row['is_active'] ? '<span style="color: green;">✅ Active</span>' : '<span style="color: orange;">⚠️ Inactive</span>';
        $created = date('M j, Y g:i A', strtotime($row['created_at']));
        
        echo "<tr style='border-bottom: 1px solid #dee2e6;'>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . htmlspecialchars($row['year_code']) . "</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['year_name']) . "</td>";
        echo "<td style='padding: 0.75rem; text-align: center; border: 1px solid #dee2e6;'>$status</td>";
        echo "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$created</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>No year levels found.</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li>Visit the <a href='admin/manage_academics.php'>Manage Academics</a> page to add courses and sections</li>";
echo "<li>Use these year levels when creating sections for different student levels</li>";
echo "<li>Students will be organized by their year level (1st year, 2nd year, etc.)</li>";
echo "</ul>";

mysqli_close($conn);
?>
