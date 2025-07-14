<?php
/**
 * Simple PHP Development Server Starter
 * This bypasses Laravel's artisan serve command to avoid PHP 8.2 compatibility issues
 */

// Set error reporting to ignore deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Get the document root (public directory)
$documentRoot = __DIR__ . '/public';

// Check if public directory exists
if (!is_dir($documentRoot)) {
    echo "Error: Public directory not found at: $documentRoot\n";
    exit(1);
}

// Default host and port
$host = '127.0.0.1';
$port = 8000;

// Check if port is already in use
$socket = @fsockopen($host, $port, $errno, $errstr, 1);
if ($socket) {
    fclose($socket);
    echo "Port $port is already in use. Trying port " . ($port + 1) . "...\n";
    $port++;
}

// Start the PHP built-in server
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    escapeshellarg($documentRoot)
);

echo "Starting Laravel development server...\n";
echo "Server running at: http://$host:$port\n";
echo "Document root: $documentRoot\n";
echo "Press Ctrl+C to stop the server\n\n";

// Execute the command
passthru($command);
