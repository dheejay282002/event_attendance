<?php
session_start();
include 'db_connect.php';
include 'includes/navigation.php';
require_once 'includes/system_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin/login.php");
    exit;
}

// Get system settings
$system_name = getSystemName($conn);

$action = $_GET['action'] ?? 'view';
$table = $_GET['table'] ?? 'official_students';

// Handle actions
if ($_POST) {
    if ($_POST['action'] === 'delete_all' && isset($_POST['confirm'])) {
        $table_to_clear = $_POST['table'];
        if (in_array($table_to_clear, ['official_students', 'students', 'events', 'attendance'])) {
            mysqli_query($conn, "DELETE FROM $table_to_clear");
            $message = "✅ All records deleted from $table_to_clear";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('includes/system_config.php')) {
        include 'includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Admin - <?= htmlspecialchars($system_name) ?></title>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 5rem auto 2rem auto;
            background: white;
            padding: 0.8rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .nav a {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .nav a.active {
            background: #0056b3;
        }
        
        .nav a:hover {
            background: #0056b3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .danger-zone {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }

            .nav {
                flex-direction: column;
                gap: 5px;
            }

            .nav a {
                text-align: center;
                padding: 10px;
                font-size: 0.875rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 8px 4px;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 0.5rem;
                padding: 0.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .nav a {
                padding: 8px;
                font-size: 0.75rem;
            }

            table {
                font-size: 0.75rem;
            }

            th, td {
                padding: 6px 2px;
            }
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('admin', 'database', $_SESSION['admin_name']); ?>

<div class="container">
    <h1>🗄️ <?= htmlspecialchars($system_name) ?> Database Administration</h1>
    
    <?php if (isset($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats">
        <?php
        $tables = ['official_students', 'students', 'events', 'attendance'];
        foreach ($tables as $tbl) {
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $tbl");
            $count = mysqli_fetch_assoc($result)['count'];
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$count</div>";
            echo "<div>" . ucwords(str_replace('_', ' ', $tbl)) . "</div>";
            echo "</div>";
        }
        ?>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <a href="?table=official_students" class="<?= $table === 'official_students' ? 'active' : '' ?>">Official Students</a>
        <a href="?table=students" class="<?= $table === 'students' ? 'active' : '' ?>">Registered Students</a>
        <a href="?table=events" class="<?= $table === 'events' ? 'active' : '' ?>">Events</a>
        <a href="?table=attendance" class="<?= $table === 'attendance' ? 'active' : '' ?>">Attendance</a>
        <a href="setup_database.php">Setup Database</a>
        <a href="add_sample_data.php">Add Sample Data</a>
    </div>
    
    <!-- Table Data -->
    <h2><?= ucwords(str_replace('_', ' ', $table)) ?></h2>
    
    <?php
    // Special query for official_students to include calculated year level
    if ($table === 'official_students') {
        $query = "
            SELECT
                id,
                student_id,
                full_name,
                course,
                section,
                created_at,
                updated_at,
                CASE
                    -- Extract year level from section name - check for numbers 1-10
                    WHEN section REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                    WHEN section REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                    WHEN section REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                    WHEN section REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                    WHEN section REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                    WHEN section REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                    WHEN section REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                    WHEN section REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                    WHEN section REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                    WHEN section REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                    -- Fallback to course name if section has no valid year numbers
                    WHEN course REGEXP '[^0-9]10[^0-9]*$|^10[^0-9]|-10[^0-9]' THEN 10
                    WHEN course REGEXP '[^0-9]9[^0-9]*$|^9[^0-9]|-9[^0-9]' THEN 9
                    WHEN course REGEXP '[^0-9]8[^0-9]*$|^8[^0-9]|-8[^0-9]' THEN 8
                    WHEN course REGEXP '[^0-9]7[^0-9]*$|^7[^0-9]|-7[^0-9]' THEN 7
                    WHEN course REGEXP '[^0-9]6[^0-9]*$|^6[^0-9]|-6[^0-9]' THEN 6
                    WHEN course REGEXP '[^0-9]5[^0-9]*$|^5[^0-9]|-5[^0-9]' THEN 5
                    WHEN course REGEXP '[^0-9]4[^0-9]*$|^4[^0-9]|-4[^0-9]' THEN 4
                    WHEN course REGEXP '[^0-9]3[^0-9]*$|^3[^0-9]|-3[^0-9]' THEN 3
                    WHEN course REGEXP '[^0-9]2[^0-9]*$|^2[^0-9]|-2[^0-9]' THEN 2
                    WHEN course REGEXP '[^0-9]1[^0-9]*$|^1[^0-9]|-1[^0-9]' THEN 1
                    -- Default to year 1
                    ELSE 1
                END as year_level
            FROM $table
            ORDER BY full_name ASC
        ";
    } else {
        $query = "SELECT * FROM $table ORDER BY id DESC";
    }

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo "<div class='table-responsive'>";
        echo "<table>";
        
        // Table headers
        $first_row = mysqli_fetch_assoc($result);
        mysqli_data_seek($result, 0); // Reset pointer
        
        echo "<tr>";
        foreach (array_keys($first_row) as $column) {
            echo "<th>" . ucwords(str_replace('_', ' ', $column)) . "</th>";
        }
        echo "</tr>";
        
        // Table data with sequential numbering
        $sequential_id = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $column => $value) {
                // Replace database ID with sequential number for official_students
                if ($column === 'id' && $table === 'official_students') {
                    $value = $sequential_id;
                }
                // Handle null values and ensure we have a string
                $display_value = $value ?? '';
                $display_value = (string)$display_value; // Ensure it's a string

                if (strlen($display_value) > 50) {
                    $display_value = substr($display_value, 0, 50) . '...';
                }

                // Special styling for year_level column
                if ($column === 'year_level' && $table === 'official_students') {
                    $year_colors = [
                        1 => ['bg' => '#dbeafe', 'text' => '#1e40af'], // Blue for 1st year
                        2 => ['bg' => '#dcfce7', 'text' => '#166534'], // Green for 2nd year
                        3 => ['bg' => '#fef3c7', 'text' => '#92400e'], // Yellow for 3rd year
                        4 => ['bg' => '#fce7f3', 'text' => '#be185d']  // Pink for 4th year
                    ];
                    $colors = $year_colors[$display_value] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
                    echo "<td><span style='background: {$colors['bg']}; color: {$colors['text']}; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 0.875rem;'>" . htmlspecialchars($display_value) . "</span></td>";
                } else {
                    echo "<td>" . htmlspecialchars($display_value) . "</td>";
                }
            }
            echo "</tr>";
            $sequential_id++; // Increment for next student
        }
        
        echo "</table>";
        echo "</div>"; // Close table-responsive
    } else {
        echo "<p>No records found in this table.</p>";
    }
    ?>
    
    <!-- Danger Zone -->
    <div class="danger-zone">
        <h3>⚠️ Danger Zone</h3>
        <p>Clear all data from <?= ucwords(str_replace('_', ' ', $table)) ?> table:</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete ALL records from <?= $table ?>? This cannot be undone!')">
            <input type="hidden" name="action" value="delete_all">
            <input type="hidden" name="table" value="<?= $table ?>">
            <label>
                <input type="checkbox" name="confirm" required> I understand this will delete all data
            </label><br><br>
            <button type="submit" class="btn-danger">Delete All Records</button>
        </form>
    </div>
</div>


<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
