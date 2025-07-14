<?php
/**
 * Favicon Helper for ADLOR System
 * This file provides a simple function to include favicon tags in all pages
 */

// Include system configuration if not already included
if (!function_exists('getSystemLogo')) {
    require_once __DIR__ . '/system_config.php';
}

/**
 * Output favicon HTML tags for the current page
 * This function should be called in the <head> section of HTML pages
 */
function outputFavicon() {
    global $conn;
    
    // Ensure we have a database connection
    if (!isset($conn)) {
        include __DIR__ . '/../db_connect.php';
    }
    
    echo generateFaviconTags($conn);
}

/**
 * Get favicon tags as string (for pages that need to store it in a variable)
 */
function getFaviconTags() {
    global $conn;
    
    // Ensure we have a database connection
    if (!isset($conn)) {
        include __DIR__ . '/../db_connect.php';
    }
    
    return generateFaviconTags($conn);
}
?>
