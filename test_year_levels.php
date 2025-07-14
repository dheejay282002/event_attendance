<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connect.php';

echo "<h2>Testing Year Level Extraction from Sections</h2>";

// Test the year level extraction query
$test_query = "
    SELECT 
        os.student_id,
        os.full_name,
        os.course,
        os.section,
        CASE 
            WHEN os.section REGEXP '[0-9]' THEN 
                GREATEST(1, LEAST(4, 
                    CASE 
                        WHEN os.section LIKE '%4%' THEN 4
                        WHEN os.section LIKE '%3%' THEN 3
                        WHEN os.section LIKE '%2%' THEN 2
                        WHEN os.section LIKE '%1%' THEN 1
                        ELSE 1
                    END
                ))
            WHEN os.course REGEXP '[0-9]' THEN 
                GREATEST(1, LEAST(4,
                    CASE 
                        WHEN os.course LIKE '%4%' THEN 4
                        WHEN os.course LIKE '%3%' THEN 3
                        WHEN os.course LIKE '%2%' THEN 2
                        WHEN os.course LIKE '%1%' THEN 1
                        ELSE 1
                    END
                ))
            ELSE 1
        END as calculated_year_level
    FROM official_students os
    ORDER BY os.section, os.full_name
    LIMIT 20
";

$result = mysqli_query($conn, $test_query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>✅ Year Level Extraction Results</h3>";
    echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Student ID</th>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Full Name</th>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Course</th>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Section</th>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: center;'>Calculated Year</th>";
    echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Extraction Logic</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $year_level = $row['calculated_year_level'];
        $section = $row['section'];
        $course = $row['course'];
        
        // Determine extraction logic
        $logic = "Default (1)";
        if (preg_match('/[0-9]/', $section)) {
            if (strpos($section, '4') !== false) $logic = "From section: found '4'";
            elseif (strpos($section, '3') !== false) $logic = "From section: found '3'";
            elseif (strpos($section, '2') !== false) $logic = "From section: found '2'";
            elseif (strpos($section, '1') !== false) $logic = "From section: found '1'";
        } elseif (preg_match('/[0-9]/', $course)) {
            if (strpos($course, '4') !== false) $logic = "From course: found '4'";
            elseif (strpos($course, '3') !== false) $logic = "From course: found '3'";
            elseif (strpos($course, '2') !== false) $logic = "From course: found '2'";
            elseif (strpos($course, '1') !== false) $logic = "From course: found '1'";
        }
        
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
        echo "<td style='border: 1px solid #ddd; padding: 8px; font-size: 0.9em; color: #666;'>{$logic}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show year level distribution
    echo "<h3>📊 Year Level Distribution</h3>";
    $dist_query = "
        SELECT 
            CASE 
                WHEN os.section REGEXP '[0-9]' THEN 
                    GREATEST(1, LEAST(4, 
                        CASE 
                            WHEN os.section LIKE '%4%' THEN 4
                            WHEN os.section LIKE '%3%' THEN 3
                            WHEN os.section LIKE '%2%' THEN 2
                            WHEN os.section LIKE '%1%' THEN 1
                            ELSE 1
                        END
                    ))
                WHEN os.course REGEXP '[0-9]' THEN 
                    GREATEST(1, LEAST(4,
                        CASE 
                            WHEN os.course LIKE '%4%' THEN 4
                            WHEN os.course LIKE '%3%' THEN 3
                            WHEN os.course LIKE '%2%' THEN 2
                            WHEN os.course LIKE '%1%' THEN 1
                            ELSE 1
                        END
                    ))
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
        echo "</tr>";
        
        while ($dist_row = mysqli_fetch_assoc($dist_result)) {
            $year = $dist_row['year_level'];
            $count = $dist_row['student_count'];
            $bg_color = $year_colors[$year] ?? '#f3f4f6';
            
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; background: {$bg_color}; font-weight: bold;'>Year {$year}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>{$count} students</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No students found or query failed: " . mysqli_error($conn) . "</p>";
}

// Test some specific section patterns
echo "<h3>🧪 Section Pattern Tests</h3>";
$test_sections = [
    'NS-4B' => 4,
    'IT-3A' => 3, 
    'A2' => 2,
    'BSIT-1C' => 1,
    'CS-2D' => 2,
    'NoNumber' => 1,
    'BSCS-4A' => 4,
    'MATH-3B' => 3
];

echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Section Pattern</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Expected Year</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>SQL Result</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px;'>Status</th>";
echo "</tr>";

foreach ($test_sections as $section => $expected) {
    $test_sql = "
        SELECT 
            CASE 
                WHEN '$section' REGEXP '[0-9]' THEN 
                    GREATEST(1, LEAST(4, 
                        CASE 
                            WHEN '$section' LIKE '%4%' THEN 4
                            WHEN '$section' LIKE '%3%' THEN 3
                            WHEN '$section' LIKE '%2%' THEN 2
                            WHEN '$section' LIKE '%1%' THEN 1
                            ELSE 1
                        END
                    ))
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
echo "<h3>💡 How It Works</h3>";
echo "<p><strong>Year Level Extraction Logic:</strong></p>";
echo "<ol>";
echo "<li><strong>Primary:</strong> Look for numbers in the section name (e.g., 'NS-4B' → finds '4' → Year 4)</li>";
echo "<li><strong>Fallback:</strong> If no numbers in section, look in course name</li>";
echo "<li><strong>Default:</strong> If no numbers found anywhere, default to Year 1</li>";
echo "<li><strong>Validation:</strong> Ensure year level is between 1-4</li>";
echo "</ol>";
echo "<p><strong>Examples:</strong></p>";
echo "<ul>";
echo "<li>NS-4B → Year 4 (finds '4' in section)</li>";
echo "<li>IT-3A → Year 3 (finds '3' in section)</li>";
echo "<li>A2 → Year 2 (finds '2' in section)</li>";
echo "<li>BSIT-1C → Year 1 (finds '1' in section)</li>";
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
    font-size: 0.9rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th {
    font-weight: 600;
}
</style>
