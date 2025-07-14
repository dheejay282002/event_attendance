<?php
/**
 * Simple QR Code Generator for ADLOR
 * Uses online QR code generation service as fallback
 */

class SimpleQRGenerator {
    
    public static function generateQRCode($data, $filename = null, $size = 200) {
        // Create QR codes directory if it doesn't exist
        if (!file_exists('qr_codes')) {
            mkdir('qr_codes', 0777, true);
        }
        
        // If no filename provided, generate one
        if (!$filename) {
            $filename = 'qr_codes/qr_' . md5($data) . '.png';
        }
        
        // Try multiple QR code services
        $services = [
            'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data),
            'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data),
        ];
        
        foreach ($services as $url) {
            $imageData = self::downloadImage($url);
            if ($imageData) {
                if (file_put_contents($filename, $imageData)) {
                    return $filename;
                }
            }
        }
        
        // If all services fail, create a placeholder image
        return self::createPlaceholderQR($data, $filename, $size);
    }
    
    private static function downloadImage($url) {
        // Try cURL first
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ADLOR QR Generator');
            
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($data && $httpCode == 200) {
                return $data;
            }
        }
        
        // Fallback to file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'ADLOR QR Generator'
            ]
        ]);
        
        return @file_get_contents($url, false, $context);
    }
    
    private static function createPlaceholderQR($data, $filename, $size) {
        // Create an SVG QR code placeholder
        $svg = self::createSVGQR($data, $size);
        
        // Save as SVG file
        $svgFilename = str_replace('.png', '.svg', $filename);
        if (file_put_contents($svgFilename, $svg)) {
            return $svgFilename;
        }
        
        // If SVG fails, create HTML representation
        return self::createHTMLQR($data, $filename);
    }
    
    private static function createSVGQR($data, $size) {
        $hash = md5($data);
        $gridSize = 21; // Standard QR code grid
        $cellSize = $size / $gridSize;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        
        // Create pattern based on data hash
        for ($x = 0; $x < $gridSize; $x++) {
            for ($y = 0; $y < $gridSize; $y++) {
                $index = ($x + $y * $gridSize) % strlen($hash);
                $char = $hash[$index];
                
                if (hexdec($char) % 2 == 0) {
                    $rectX = $x * $cellSize;
                    $rectY = $y * $cellSize;
                    $svg .= '<rect x="' . $rectX . '" y="' . $rectY . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="black"/>';
                }
            }
        }
        
        // Add corner markers
        $markerSize = $cellSize * 7;
        $svg .= '<rect x="0" y="0" width="' . $markerSize . '" height="' . $markerSize . '" fill="black"/>';
        $svg .= '<rect x="' . $cellSize . '" y="' . $cellSize . '" width="' . ($markerSize - 2*$cellSize) . '" height="' . ($markerSize - 2*$cellSize) . '" fill="white"/>';
        $svg .= '<rect x="' . (2*$cellSize) . '" y="' . (2*$cellSize) . '" width="' . ($markerSize - 4*$cellSize) . '" height="' . ($markerSize - 4*$cellSize) . '" fill="black"/>';
        
        $svg .= '</svg>';
        
        return $svg;
    }
    
    private static function createHTMLQR($data, $filename) {
        // Create an HTML file with QR code representation
        // Generate favicon tags
        $favicon_tags = '';
        if (file_exists('includes/system_config.php')) {
            include 'includes/system_config.php';
            $favicon_tags = generateFaviconTags($conn);
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    ' . $favicon_tags . '
    <title>QR Code</title>
    <style>
        body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; }
        .qr-container { display: inline-block; border: 2px solid #000; padding: 20px; background: white; }
        .qr-grid { display: grid; grid-template-columns: repeat(21, 10px); gap: 1px; margin: 20px 0; }
        .qr-cell { width: 10px; height: 10px; }
        .qr-black { background: black; }
        .qr-white { background: white; }
        .qr-data { margin-top: 20px; font-size: 12px; color: #666; word-break: break-all; }
    </style>
</head>
<body>
    <div class="qr-container">
        <h3>QR Code</h3>
        <div class="qr-grid">';
        
        $hash = md5($data);
        for ($i = 0; $i < 441; $i++) { // 21x21 grid
            $char = $hash[$i % strlen($hash)];
            $class = (hexdec($char) % 2 == 0) ? 'qr-black' : 'qr-white';
            $html .= '<div class="qr-cell ' . $class . '"></div>';
        }
        
        $html .= '</div>
        <div class="qr-data">Data: ' . htmlspecialchars(substr($data, 0, 100)) . '</div>
    </div>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>';
        
        $htmlFilename = str_replace('.png', '.html', $filename);
        if (file_put_contents($htmlFilename, $html)) {
            return $htmlFilename;
        }
        
        return false;
    }
}

// Compatibility function for existing code
if (!function_exists('QRcode')) {
    class QRcode {
        public static function png($text, $filename, $errorCorrectionLevel = 'M', $size = 8, $margin = 2) {
            $pixelSize = $size * 25;
            return SimpleQRGenerator::generateQRCode($text, $filename, $pixelSize) !== false;
        }
    }
}
?>
