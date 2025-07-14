<?php
/**
 * System Configuration Helper
 * Provides functions to get system settings like name and logo
 */

/**
 * Get a system setting value
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
if (!function_exists('getSystemSetting')) {
function getSystemSetting($conn, $key, $default = '') {
    static $settings_cache = [];
    
    // Check cache first
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    try {
        $query = mysqli_prepare($conn, "SELECT setting_value FROM system_settings WHERE setting_key = ?");
        if ($query) {
            mysqli_stmt_bind_param($query, "s", $key);
            mysqli_stmt_execute($query);
            $result = mysqli_stmt_get_result($query);
            $row = mysqli_fetch_assoc($result);
            
            $value = $row ? $row['setting_value'] : $default;
            $settings_cache[$key] = $value;
            return $value;
        }
    } catch (Exception $e) {
        // Table might not exist yet, return default
    }
    
    return $default;
}
}

/**
 * Get system name
 * @param mysqli $conn Database connection
 * @return string System name
 */
if (!function_exists('getSystemName')) {
function getSystemName($conn) {
    return getSystemSetting($conn, 'system_name', 'ADLOR');
}
}

/**
 * Get system logo path
 * @param mysqli $conn Database connection
 * @return string Logo path or empty string
 */
if (!function_exists('getSystemLogo')) {
function getSystemLogo($conn) {
    return getSystemSetting($conn, 'system_logo', '');
}
}

/**
 * Get system description
 * @param mysqli $conn Database connection
 * @return string System description
 */
if (!function_exists('getSystemDescription')) {
function getSystemDescription($conn) {
    return getSystemSetting($conn, 'system_description', 'Event Attendance System');
}
}

/**
 * Display system logo HTML
 * @param mysqli $conn Database connection
 * @param string $class CSS class for the logo
 * @param string $style Additional CSS styles
 * @param bool $circular Whether to make the logo circular
 * @return string HTML for logo or system name
 */
if (!function_exists('displaySystemLogo')) {
function displaySystemLogo($conn, $class = '', $style = '', $circular = true) {
    $logo_path = getSystemLogo($conn);
    $system_name = getSystemName($conn);

    if ($logo_path && file_exists($logo_path)) {
        $circular_style = $circular ? 'border-radius: 50%; object-fit: cover;' : '';
        $combined_style = $circular_style . ' ' . $style;
        return '<img src="' . htmlspecialchars($logo_path) . '" alt="' . htmlspecialchars($system_name) . '" class="' . htmlspecialchars($class) . '" style="' . htmlspecialchars($combined_style) . '">';
    } else {
        return '<span class="' . htmlspecialchars($class) . '" style="' . htmlspecialchars($style) . '">' . htmlspecialchars($system_name) . '</span>';
    }
}
}

/**
 * Generate favicon HTML tags for system logo
 * @param mysqli $conn Database connection
 * @return string HTML favicon tags
 */
if (!function_exists('generateFaviconTags')) {
function generateFaviconTags($conn) {
    $logo_path = getSystemLogo($conn);
    $system_name = getSystemName($conn);

    if ($logo_path) {
        // Determine the correct file path based on current script location
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        $is_in_subdirectory = strpos($script_dir, '/admin') !== false || strpos($script_dir, '/sbo') !== false;

        // Construct the correct file path
        $file_path = $is_in_subdirectory ? '../' . $logo_path : $logo_path;

        if (file_exists($file_path)) {
            try {
                // Read the image file and convert to base64
                $image_data = file_get_contents($file_path);
                if ($image_data !== false) {
                    $image_type = mime_content_type($file_path);
                    $base64_image = 'data:' . $image_type . ';base64,' . base64_encode($image_data);

                    // Create a circular favicon using SVG that embeds the base64 image
                    $circular_favicon = 'data:image/svg+xml;base64,' . base64_encode('
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                            <defs>
                                <clipPath id="circle">
                                    <circle cx="50" cy="50" r="50"/>
                                </clipPath>
                            </defs>
                            <image href="' . $base64_image . '" x="0" y="0" width="100" height="100" clip-path="url(#circle)" preserveAspectRatio="xMidYMid slice"/>
                        </svg>
                    ');

                    return '
    <link rel="icon" type="image/svg+xml" href="' . $circular_favicon . '">
    <link rel="shortcut icon" type="image/svg+xml" href="' . $circular_favicon . '">
    <link rel="apple-touch-icon" sizes="180x180" href="' . $circular_favicon . '">
    <link rel="icon" type="image/png" sizes="32x32" href="' . $circular_favicon . '">
    <link rel="icon" type="image/png" sizes="16x16" href="' . $circular_favicon . '">
    <meta name="msapplication-TileImage" content="' . $circular_favicon . '">
    <meta name="msapplication-TileColor" content="transparent">';
                }
            } catch (Exception $e) {
                // Fall through to default favicon
            }
        }
    }

    // Use default circular favicon with system name initial (fallback)
    $default_favicon = 'data:image/svg+xml;base64,' . base64_encode('
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <text x="50" y="65" font-family="Arial, sans-serif" font-size="60" font-weight="bold" text-anchor="middle" fill="#374151">' . substr($system_name, 0, 1) . '</text>
        </svg>
    ');

    return '
    <link rel="icon" type="image/svg+xml" href="' . $default_favicon . '">
    <link rel="shortcut icon" type="image/svg+xml" href="' . $default_favicon . '">
    <link rel="apple-touch-icon" sizes="180x180" href="' . $default_favicon . '">
    <meta name="msapplication-TileColor" content="transparent">';
}
}

/**
 * Get all system settings as an array
 * @param mysqli $conn Database connection
 * @return array Associative array of settings
 */
if (!function_exists('getAllSystemSettings')) {
function getAllSystemSettings($conn) {
    static $all_settings_cache = null;
    
    if ($all_settings_cache !== null) {
        return $all_settings_cache;
    }
    
    $settings = [];
    
    try {
        $query = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }
    
    // Set defaults if not found
    if (!isset($settings['system_name'])) $settings['system_name'] = 'ADLOR';
    if (!isset($settings['system_logo'])) $settings['system_logo'] = '';
    if (!isset($settings['system_description'])) $settings['system_description'] = 'Event Attendance System';
    
    $all_settings_cache = $settings;
    return $settings;
}
}

/**
 * Update a system setting
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool Success status
 */
if (!function_exists('updateSystemSetting')) {
function updateSystemSetting($conn, $key, $value) {
    try {
        // Check if setting exists
        $check_query = mysqli_prepare($conn, "SELECT id FROM system_settings WHERE setting_key = ?");
        mysqli_stmt_bind_param($check_query, "s", $key);
        mysqli_stmt_execute($check_query);
        $result = mysqli_stmt_get_result($check_query);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing setting
            $update_query = mysqli_prepare($conn, "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            mysqli_stmt_bind_param($update_query, "ss", $value, $key);
            return mysqli_stmt_execute($update_query);
        } else {
            // Insert new setting
            $insert_query = mysqli_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            mysqli_stmt_bind_param($insert_query, "ss", $key, $value);
            return mysqli_stmt_execute($insert_query);
        }
    } catch (Exception $e) {
        return false;
    }
}
}
?>
