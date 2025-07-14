<?php
include 'simple_qr_generator.php';

echo "<h2>Testing Simple QR Generator</h2>";

$testData = json_encode([
    'student_id' => '202300001',
    'event_id' => 1,
    'timestamp' => time(),
    'hash' => md5('202300001' . '1' . date('Y-m-d'))
]);

echo "<p>Generating QR code for: " . htmlspecialchars($testData) . "</p>";

$result = SimpleQRGenerator::generateQRCode($testData, 'qr_codes/test_qr.png');

if ($result) {
    echo "<p style='color: green;'>✅ QR Code generated successfully: " . htmlspecialchars($result) . "</p>";
    
    // Check if it's an image file
    if (file_exists($result)) {
        $fileSize = filesize($result);
        echo "<p>File size: " . $fileSize . " bytes</p>";
        
        // Display the QR code
        if (strpos($result, '.png') !== false) {
            echo "<img src='" . htmlspecialchars($result) . "' alt='Generated QR Code' style='border: 2px solid #000; max-width: 300px;'>";
        } elseif (strpos($result, '.svg') !== false) {
            echo "<iframe src='" . htmlspecialchars($result) . "' width='300' height='300' style='border: 2px solid #000;'></iframe>";
        } elseif (strpos($result, '.html') !== false) {
            echo "<iframe src='" . htmlspecialchars($result) . "' width='400' height='400' style='border: 2px solid #000;'></iframe>";
        }
    } else {
        echo "<p style='color: red;'>❌ File was not created</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to generate QR code</p>";
}

echo "<p><a href='index.php'>← Back to Home</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}
</style>
