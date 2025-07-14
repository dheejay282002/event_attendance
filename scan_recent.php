<?php
session_start();
date_default_timezone_set('Asia/Manila');

include 'db_connect.php';
include 'includes/navigation.php';

// Get recent attendance scans (last 50 records) from official_students table
$recent_query = "
    SELECT
        a.id,
        a.student_id,
        COALESCE(s.full_name, os.full_name) as full_name,
        COALESCE(s.course, os.course) as course,
        COALESCE(s.section, os.section) as section,
        e.title as event_name,
        a.time_in,
        a.time_out,
        a.created_at,
        CASE
            WHEN a.time_out IS NOT NULL THEN 'Complete'
            WHEN a.time_in IS NOT NULL THEN 'Time In Only'
            ELSE 'Unknown'
        END as status
    FROM attendance a
    LEFT JOIN students s ON a.student_id = s.student_id
    LEFT JOIN official_students os ON a.student_id = os.student_id
    LEFT JOIN events e ON a.event_id = e.id
    ORDER BY a.created_at DESC
    LIMIT 50
";

$recent_result = mysqli_query($conn, $recent_query);

// Get today's scan count
$today = date('Y-m-d');
$today_count_query = "SELECT COUNT(*) as count FROM attendance WHERE DATE(created_at) = '$today'";
$today_count_result = mysqli_query($conn, $today_count_query);
$today_count = mysqli_fetch_assoc($today_count_result)['count'];

// Get total scan count
$total_count_query = "SELECT COUNT(*) as count FROM attendance";
$total_count_result = mysqli_query($conn, $total_count_query);
$total_count = mysqli_fetch_assoc($total_count_result)['count'];
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
    <title>Recent Scans - ADLOR</title>
    <link rel="stylesheet" href="assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .recent-table {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 600;
        }

        .table-row {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr 2fr 1fr;
            gap: 1rem;
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: var(--gray-50);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }

        .status-complete {
            background: var(--success-light);
            color: var(--success-dark);
        }

        .status-partial {
            background: var(--warning-light);
            color: var(--warning-dark);
        }

        .refresh-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .table-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: left;
            }

            .table-row > div {
                padding: 0.25rem 0;
            }

            .table-row > div:first-child {
                font-weight: 600;
                color: var(--primary-color);
            }
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('scanner', 'recent', 'QR Scanner'); ?>
    
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <!-- Header -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">📋 Recent Scans</h1>
            <p style="color: var(--gray-600); margin: 0;">
                Latest attendance scanning activity across all events
            </p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $today_count ?></div>
                <div class="stat-label">Today's Scans</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_count ?></div>
                <div class="stat-label">Total Scans</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= mysqli_num_rows($recent_result) ?></div>
                <div class="stat-label">Recent Records</div>
            </div>
        </div>

        <!-- Recent Scans Table -->
        <div class="recent-table">
            <div class="table-header">
                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr 1fr 2fr 1fr; gap: 1rem;">
                    <div>Student ID</div>
                    <div>Student Name</div>
                    <div>Course</div>
                    <div>Section</div>
                    <div>Event</div>
                    <div>Status</div>
                </div>
            </div>

            <?php if (mysqli_num_rows($recent_result) > 0): ?>
                <?php while ($scan = mysqli_fetch_assoc($recent_result)): ?>
                    <div class="table-row">
                        <div>
                            <strong><?= htmlspecialchars($scan['student_id']) ?></strong>
                        </div>
                        <div>
                            <?= htmlspecialchars($scan['full_name'] ?? 'Unknown Student') ?>
                            <div style="font-size: 0.75rem; color: var(--gray-500);">
                                <?= date('M j, Y g:i A', strtotime($scan['created_at'])) ?>
                            </div>
                        </div>
                        <div><?= htmlspecialchars($scan['course'] ?? 'N/A') ?></div>
                        <div><?= htmlspecialchars($scan['section'] ?? 'N/A') ?></div>
                        <div>
                            <?= htmlspecialchars($scan['event_name'] ?? 'Unknown Event') ?>
                            <?php if ($scan['time_in']): ?>
                                <div style="font-size: 0.75rem; color: var(--gray-500);">
                                    In: <?= date('g:i A', strtotime($scan['time_in'])) ?>
                                    <?php if ($scan['time_out']): ?>
                                        | Out: <?= date('g:i A', strtotime($scan['time_out'])) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="status-badge <?= $scan['status'] === 'Complete' ? 'status-complete' : 'status-partial' ?>">
                                <?= $scan['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 3rem; text-align: center; color: var(--gray-500);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                    <h3>No Recent Scans</h3>
                    <p>No attendance records found. Start scanning QR codes to see activity here.</p>
                    <a href="scan_qr.php" class="btn btn-primary" style="margin-top: 1rem;">
                        📱 Go to QR Scanner
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Data">
        🔄
    </button>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);

        // Add fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
