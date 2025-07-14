<?php
// Test QR code generation
require_once 'simple_qr_generator.php';

echo "<h2>Testing QR Code Generation</h2>";

// Test data
$test_data = json_encode([
    'student_id' => 'TEST-12345',
    'full_name' => 'Test Student',
    'course' => 'BSIT',
    'section' => 'IT-3A',
    'timestamp' => time(),
    'hash' => md5('TEST-12345' . date('Y-m-d H:i:s'))
]);

echo "<p><strong>Test Data:</strong> " . htmlspecialchars($test_data) . "</p>";

// Check directory
$qr_dir = 'qr_codes/';
echo "<p><strong>QR Directory Exists:</strong> " . (file_exists($qr_dir) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>QR Directory Writable:</strong> " . (is_writable($qr_dir) ? 'Yes' : 'No') . "</p>";

// Create directory if it doesn't exist
if (!file_exists($qr_dir)) {
    mkdir($qr_dir, 0777, true);
    echo "<p><strong>Created QR Directory</strong></p>";
}

// Test filename
$test_filename = $qr_dir . 'test_generation.png';
echo "<p><strong>Test Filename:</strong> " . $test_filename . "</p>";

// Try to generate QR code
try {
    $result = SimpleQRGenerator::generateQRCode($test_data, $test_filename, 300);
    
    if ($result) {
        echo "<p style='color: green;'><strong>✅ QR Code Generated Successfully!</strong></p>";
        echo "<p><strong>File Path:</strong> " . $result . "</p>";
        echo "<p><strong>File Exists:</strong> " . (file_exists($result) ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>File Size:</strong> " . (file_exists($result) ? filesize($result) . ' bytes' : 'N/A') . "</p>";
        
        if (file_exists($result)) {
            echo "<div style='border: 2px solid #10b981; padding: 1rem; margin: 1rem 0; text-align: center;'>";
            echo "<h3>Generated QR Code:</h3>";
            echo "<img src='" . $result . "' alt='Test QR Code' style='max-width: 300px; border: 1px solid #ccc;'>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ QR Code Generation Failed</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test online services
echo "<h3>Testing Online QR Services</h3>";

$services = [
    'QR Server' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode('TEST'),
    'Google Charts' => 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode('TEST')
];

foreach ($services as $name => $url) {
    echo "<p><strong>Testing {$name}:</strong> ";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'ADLOR QR Test'
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    if ($content !== false) {
        echo "<span style='color: green;'>✅ Working</span></p>";
    } else {
        echo "<span style='color: red;'>❌ Failed</span></p>";
    }
}

echo "<hr>";
echo "<p><a href='student_qr_codes.php'>← Back to Student QR Codes</a></p>";
?>
