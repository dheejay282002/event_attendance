<?php
session_start();
include '../db_connect.php';
include '../includes/navigation.php';

date_default_timezone_set('Asia/Manila');

// Check if SBO is logged in
if (!isset($_SESSION['sbo_id'])) {
    header("Location: login.php");
    exit;
}

// Get all events with their QR codes
$events_query = mysqli_query($conn, "SELECT * FROM events ORDER BY start_datetime DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('../includes/system_config.php')) {
        include '../includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event QR Codes - ADLOR SBO</title>
    <link rel="stylesheet" href="../assets/css/adlor-professional.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .event-qr-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .qr-display {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .event-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-ongoing {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #f3f4f6;
            color: #374151;
        }
    </style>
</head>
<body class="has-navbar">
    <?php renderNavigation('sbo', 'events', $_SESSION['sbo_name']); ?>
    
    <!-- Header -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
                📱 Event QR Codes
            </h1>
            <p style="font-size: 1.125rem; color: var(--gray-600); margin: 0;">
                Manage and display QR codes for student attendance
            </p>
        </div>
    </div>
    
    <div class="container" style="margin-bottom: 3rem;">
        <?php if (mysqli_num_rows($events_query) > 0): ?>
            <?php while ($event = mysqli_fetch_assoc($events_query)): ?>
                <?php
                $now = time();
                $start_time = strtotime($event['start_datetime']);
                $end_time = strtotime($event['end_datetime']);
                
                if ($now < $start_time) {
                    $status = 'upcoming';
                    $status_text = '🔜 Upcoming';
                    $status_class = 'status-upcoming';
                } elseif ($now >= $start_time && $now <= $end_time) {
                    $status = 'ongoing';
                    $status_text = '🟢 Ongoing';
                    $status_class = 'status-ongoing';
                } else {
                    $status = 'completed';
                    $status_text = '✅ Completed';
                    $status_class = 'status-completed';
                }
                
                $qr_file_path = "../qr_codes/events/event_{$event['id']}.png";
                $qr_file = "../qr_codes/events/event_{$event['id']}.png"; // For display in HTML
                $qr_file_download = "../qr_codes/events/event_{$event['id']}.png"; // For download
                ?>
                
                <div class="event-qr-card">
                    <div style="padding: 2rem;">
                        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1.5rem;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--gray-900);">
                                    <?= htmlspecialchars($event['title']) ?>
                                </h3>
                                <p style="margin: 0 0 1rem 0; color: var(--gray-600);">
                                    <?= htmlspecialchars($event['description']) ?>
                                </p>
                                <div class="event-status <?= $status_class ?>">
                                    <?= $status_text ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: center;">
                            <!-- Event Details -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--gray-700);">📋 Event Details</h4>
                                <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem;">
                                    <div style="margin-bottom: 1rem;">
                                        <strong style="color: var(--gray-700);">📅 Date:</strong><br>
                                        <span style="color: var(--gray-900);"><?= date('M j, Y', strtotime($event['start_datetime'])) ?></span>
                                    </div>
                                    <div style="margin-bottom: 1rem;">
                                        <strong style="color: var(--gray-700);">🕐 Time:</strong><br>
                                        <span style="color: var(--gray-900);">
                                            <?= date('g:i A', strtotime($event['start_datetime'])) ?> - 
                                            <?= date('g:i A', strtotime($event['end_datetime'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <strong style="color: var(--gray-700);">🏫 Sections:</strong><br>
                                        <span style="color: var(--gray-900);"><?= htmlspecialchars($event['assigned_sections']) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--gray-700); text-align: center;">📱 Event QR Code</h4>
                                <?php if (file_exists($qr_file_path)): ?>
                                    <div class="qr-display">
                                        <img src="<?= $qr_file ?>" 
                                             alt="QR Code for <?= htmlspecialchars($event['title']) ?>"
                                             style="width: 200px; height: 200px; border: 3px solid var(--primary-color); border-radius: 0.75rem;">
                                        <p style="margin: 1rem 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
                                            Students scan this code for attendance
                                        </p>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.75rem; justify-content: center; margin-top: 1rem;">
                                        <button onclick="downloadEventQR('<?= $qr_file ?>', '<?= $event['id'] ?>', '<?= addslashes($event['title']) ?>')"
                                                class="btn btn-primary btn-sm">
                                            💾 Download
                                        </button>
                                        <button onclick="printEventQR('<?= $qr_file ?>', '<?= addslashes($event['title']) ?>')"
                                                class="btn btn-outline btn-sm">
                                            🖨️ Print
                                        </button>
                                        <button onclick="shareEventQR('<?= $event['id'] ?>', '<?= addslashes($event['title']) ?>')"
                                                class="btn btn-secondary btn-sm">
                                            📤 Share
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 2rem; background: var(--gray-50); border-radius: 0.75rem;">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                                        <p style="margin: 0; color: var(--gray-600);">QR Code not found</p>
                                        <p style="margin: 0.5rem 0; color: var(--gray-500); font-size: 0.875rem;">
                                            File: <?= htmlspecialchars($qr_file_path) ?>
                                        </p>
                                        <button onclick="regenerateQR(<?= $event['id'] ?>)" class="btn btn-primary btn-sm" style="margin-top: 1rem;">
                                            🔄 Generate QR Code
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem; color: var(--gray-500);">
                <div style="font-size: 5rem; margin-bottom: 2rem;">📅</div>
                <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem;">No events created yet</h3>
                <p style="margin: 0 0 2rem 0; font-size: 1rem;">Create your first event to generate QR codes for student attendance.</p>
                <a href="create_event.php" class="btn btn-primary">
                    📅 Create First Event
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function downloadEventQR(filename, eventId, eventTitle) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Downloading...';
            btn.disabled = true;

            try {
                // Create download link with proper error handling
                const link = document.createElement('a');
                link.href = filename;
                link.download = `Event_QR_${eventId}_${eventTitle.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
                link.style.display = 'none';
                document.body.appendChild(link);

                // Test if file exists by creating an image
                const img = new Image();
                img.onload = function() {
                    // File exists, proceed with download
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        btn.innerHTML = '✅ Downloaded!';
                        btn.style.background = '#10b981';
                        btn.style.color = 'white';

                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.style.background = '';
                            btn.style.color = '';
                        }, 2000);
                    }, 500);
                };

                img.onerror = function() {
                    // File doesn't exist or can't be loaded
                    document.body.removeChild(link);
                    btn.innerHTML = '❌ File Not Found';
                    btn.style.background = '#dc2626';
                    btn.style.color = 'white';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                };

                img.src = filename;

            } catch (error) {
                console.error('Download error:', error);
                btn.innerHTML = '❌ Download Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }
        }
        
        function printEventQR(filename, eventTitle) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Preparing...';
            btn.disabled = true;

            try {
                // Test if image exists first
                const img = new Image();
                img.onload = function() {
                    // Image loaded successfully, proceed with print
                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        throw new Error('Popup blocked');
                    }

                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                            <head>
    <?php
    if (file_exists('../includes/system_config.php')) {
        include '../includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
                                <title>Event QR Code - ${eventTitle}</title>
                                <style>
                                    @page { margin: 1cm; size: A4; }
                                    body {
                                        font-family: Arial, sans-serif;
                                        text-align: center;
                                        margin: 0;
                                        padding: 20px;
                                        background: white;
                                    }
                                    .header { margin-bottom: 30px; }
                                    .qr-container {
                                        background: white;
                                        padding: 20px;
                                        border-radius: 15px;
                                        display: inline-block;
                                        margin: 20px 0;
                                        border: 3px solid #3b82f6;
                                    }
                                    img {
                                        width: 300px;
                                        height: 300px;
                                        border: none;
                                    }
                                    .instructions {
                                        margin-top: 20px;
                                        font-size: 14px;
                                        color: #333;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="header">
                                    <h1>ADLOR Event QR Code</h1>
                                    <h2>${eventTitle}</h2>
                                </div>
                                <div class="qr-container">
                                    <img src="${filename}" alt="Event QR Code" onload="window.print(); setTimeout(() => window.close(), 1000);">
                                </div>
                                <div class="instructions">
                                    <p><strong>Instructions:</strong> Students should scan this QR code to record their attendance</p>
                                    <p><strong>Event:</strong> ${eventTitle}</p>
                                </div>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();

                    setTimeout(() => {
                        btn.innerHTML = '✅ Print Ready!';
                        btn.style.background = '#10b981';
                        btn.style.color = 'white';

                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.style.background = '';
                            btn.style.color = '';
                        }, 2000);
                    }, 1000);
                };

                img.onerror = function() {
                    // Image failed to load
                    btn.innerHTML = '❌ QR Not Found';
                    btn.style.background = '#dc2626';
                    btn.style.color = 'white';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                };

                img.src = filename;

            } catch (error) {
                console.error('Print error:', error);
                btn.innerHTML = '❌ Print Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }
        }
        
        function shareEventQR(eventId, eventTitle) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Sharing...';
            btn.disabled = true;
            
            if (navigator.share) {
                navigator.share({
                    title: `Event QR Code - ${eventTitle}`,
                    text: `QR Code for event: ${eventTitle}`,
                    url: window.location.href
                }).then(() => {
                    btn.innerHTML = '✅ Shared!';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                }).catch(() => {
                    copyToClipboard(btn, originalText, window.location.href);
                });
            } else {
                copyToClipboard(btn, originalText, window.location.href);
            }
        }
        
        function copyToClipboard(btn, originalText, text) {
            navigator.clipboard.writeText(text).then(() => {
                btn.innerHTML = '✅ Link Copied!';
                btn.style.background = '#10b981';
                btn.style.color = 'white';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(() => {
                btn.innerHTML = '❌ Share Failed';
                btn.style.background = '#dc2626';
                btn.style.color = 'white';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            });
        }
        
        function regenerateQR(eventId) {
            if (confirm('Regenerate QR code for this event?')) {
                window.location.href = `regenerate_event_qr.php?event_id=${eventId}`;
            }
        }
    </script>
</body>
</html>
