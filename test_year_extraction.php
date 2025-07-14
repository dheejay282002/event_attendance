<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>🧪 Testing Year Level Extraction</h2>";

// Test the new simplified year level extraction
$test_query = "
    SELECT 
        os.student_id,
        os.full_name,
        os.course,
        os.section,
        CASE 
            -- Extract year level from section name (prioritize highest number found)
            WHEN os.section LIKE '%4%' THEN 4
            WHEN os.section LIKE '%3%' THEN 3  
            WHEN os.section LIKE '%2%' THEN 2
            WHEN os.section LIKE '%1%' THEN 1
            -- Fallback to course name if section has no numbers
            WHEN os.course LIKE '%4%' THEN 4
            WHEN os.course LIKE '%3%' THEN 3
            WHEN os.course LIKE '%2%' THEN 2
            WHEN os.course LIKE '%1%' THEN 1
            -- Default to year 1
            ELSE 1
        END as calculated_year_level
    FROM official_students os
    ORDER BY calculated_year_level DESC, os.section, os.full_name
    LIMIT 20
";

$result = mysqli_query($conn, $test_query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>✅ Year Level Extraction Results (Top 20 Students)</h3>";
    echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 0.9rem;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Student ID</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Full Name</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Course</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Section</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: center;'>Year Level</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Logic</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $year_level = $row['calculated_year_level'];
        $section = $row['section'];
        $course = $row['course'];
        
        // Determine extraction logic
        $logic = "Default (1)";
        if (strpos($section, '4') !== false) $logic = "Section contains '4'";
        elseif (strpos($section, '3') !== false) $logic = "Section contains '3'";
        elseif (strpos($section, '2') !== false) $logic = "Section contains '2'";
        elseif (strpos($section, '1') !== false) $logic = "Section contains '1'";
        elseif (strpos($course, '4') !== false) $logic = "Course contains '4'";
        elseif (strpos($course, '3') !== false) $logic = "Course contains '3'";
        elseif (strpos($course, '2') !== false) $logic = "Course contains '2'";
        elseif (strpos($course, '1') !== false) $logic = "Course contains '1'";
        
        // Color code the year level
        $year_colors = [
            1 => '#dbeafe',
            2 => '#dcfce7', 
            3 => '#fef3c7',
            4 => '#fce7f3'
        ];
        $bg_color = $year_colors[$year_level] ?? '#f3f4f6';
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; font-family: monospace;'>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['course']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>" . htmlspecialchars($row['section']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; background: {$bg_color}; font-weight: bold;'>Year {$year_level}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; font-size: 0.8em; color: #666;'>{$logic}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show year level distribution
    echo "<h3>📊 Year Level Distribution</h3>";
    $dist_query = "
        SELECT 
            CASE 
                WHEN os.section LIKE '%4%' THEN 4
                WHEN os.section LIKE '%3%' THEN 3  
                WHEN os.section LIKE '%2%' THEN 2
                WHEN os.section LIKE '%1%' THEN 1
                WHEN os.course LIKE '%4%' THEN 4
                WHEN os.course LIKE '%3%' THEN 3
                WHEN os.course LIKE '%2%' THEN 2
                WHEN os.course LIKE '%1%' THEN 1
                ELSE 1
            END as year_level,
            COUNT(*) as student_count
        FROM official_students os
        GROUP BY year_level
        ORDER BY year_level
    ";
    
    $dist_result = mysqli_query($conn, $dist_query);
    if ($dist_result) {
        echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th style='border: 1px solid #ddd; padding: 12px;'>Year Level</th>";
        echo "<th style='border: 1px solid #ddd; padding: 12px;'>Student Count</th>";
        echo "<th style='border: 1px solid #ddd; padding: 12px;'>Percentage</th>";
        echo "</tr>";
        
        $total_students = 0;
        $year_data = [];
        while ($dist_row = mysqli_fetch_assoc($dist_result)) {
            $year_data[] = $dist_row;
            $total_students += $dist_row['student_count'];
        }
        
        foreach ($year_data as $dist_row) {
            $year = $dist_row['year_level'];
            $count = $dist_row['student_count'];
            $percentage = round(($count / $total_students) * 100, 1);
            $bg_color = $year_colors[$year] ?? '#f3f4f6';
            
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; background: {$bg_color}; font-weight: bold;'>Year {$year}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>{$count} students</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>{$percentage}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No students found or query failed: " . mysqli_error($conn) . "</p>";
}

// Test specific examples from the screenshot
echo "<h3>🔍 Testing Specific Examples from Your Data</h3>";
$specific_examples = [
    'A2' => 2,
    'BST-3B' => 3,
    'BST-3B BPO' => 3,
    'BSARCH' => 1
];

echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Section Pattern</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Expected Year</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>SQL Result</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Status</th>";
echo "</tr>";

foreach ($specific_examples as $section => $expected) {
    $test_sql = "
        SELECT 
            CASE 
                WHEN '$section' LIKE '%4%' THEN 4
                WHEN '$section' LIKE '%3%' THEN 3  
                WHEN '$section' LIKE '%2%' THEN 2
                WHEN '$section' LIKE '%1%' THEN 1
                ELSE 1
            END as result
    ";
    
    $test_result = mysqli_query($conn, $test_sql);
    $test_row = mysqli_fetch_assoc($test_result);
    $actual = $test_row['result'];
    
    $status = ($actual == $expected) ? "✅ Pass" : "❌ Fail";
    $status_color = ($actual == $expected) ? "#d4edda" : "#f8d7da";
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; font-family: monospace; font-weight: bold;'>{$section}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Year {$expected}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Year {$actual}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; background: {$status_color};'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 How the New Logic Works</h3>";
echo "<p><strong>Simplified Year Level Extraction:</strong></p>";
echo "<ol>";
echo "<li><strong>Section Priority:</strong> Look for numbers 4, 3, 2, 1 in section name (in that order)</li>";
echo "<li><strong>Course Fallback:</strong> If no numbers in section, look in course name</li>";
echo "<li><strong>Default:</strong> If no numbers found, default to Year 1</li>";
echo "</ol>";
echo "<p><strong>Examples from your data:</strong></p>";
echo "<ul>";
echo "<li><strong>A2</strong> → Contains '2' → Year 2 ✅</li>";
echo "<li><strong>BST-3B</strong> → Contains '3' → Year 3 ✅</li>";
echo "<li><strong>BST-3B BPO</strong> → Contains '3' → Year 3 ✅</li>";
echo "<li><strong>BSARCH</strong> → No numbers → Year 1 ✅</li>";
echo "</ul>";
echo "</div>";

// Close connection
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

h2, h3 {
    color: #2c3e50;
}

table {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    background: white;
}

th {
    font-weight: 600;
}
</style>
