<?php
/**
 * Scanner Settings Helper Functions
 * Functions to check scanner availability and restrictions
 */

/**
 * Get all scanner settings from database
 */
function getScannerSettings($conn) {
    $settings_query = "SELECT * FROM scanner_settings";
    $settings_result = mysqli_query($conn, $settings_query);
    $settings = [];
    
    if ($settings_result) {
        while ($row = mysqli_fetch_assoc($settings_result)) {
            $settings[$row['setting_name']] = $row;
        }
    }
    
    return $settings;
}

/**
 * Check if QR scanner is enabled
 */
function isQRScannerEnabled($conn) {
    $settings = getScannerSettings($conn);
    return isset($settings['qr_scanner_enabled']) && $settings['qr_scanner_enabled']['is_enabled'] == 1;
}

/**
 * Check if manual ID entry is enabled
 */
function isManualIDEntryEnabled($conn) {
    $settings = getScannerSettings($conn);
    return isset($settings['manual_id_entry_enabled']) && $settings['manual_id_entry_enabled']['is_enabled'] == 1;
}



/**
 * Check if scanner is available based on time restrictions
 */
function isScannerAvailableByTime($conn, $event_id = null) {
    // If event ID is provided, check event's actual start/end times first
    if ($event_id) {
        $event_query = "SELECT start_datetime, end_datetime FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $event_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event_result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($event_result);

        if (!$event) {
            return false; // Event not found
        }

        // Check if current time is within event's start and end time
        $current_timestamp = time();
        $event_start_timestamp = strtotime($event['start_datetime']);
        $event_end_timestamp = strtotime($event['end_datetime']);

        // STRICT ENFORCEMENT: NO ATTENDANCE AFTER END TIME
        if ($current_timestamp > $event_end_timestamp) {
            return false; // Event has ended - NO MORE ATTENDANCE ACCEPTED
        }

        // Accept attendance until event end time
        return true;
    }

    // If no event ID provided, use global time restrictions
    $settings = getScannerSettings($conn);

    // If time restrictions are not enabled, scanner is always available
    if (!isset($settings['scanner_schedule_enabled']) || $settings['scanner_schedule_enabled']['is_enabled'] != 1) {
        return true;
    }

    // Check global time restrictions
    if (isset($settings['scanner_time_restriction'])) {
        $time_setting = $settings['scanner_time_restriction'];
        return checkTimeRange($time_setting['start_time'], $time_setting['end_time']);
    }

    return true;
}

/**
 * Helper function to check if current time is within a time range
 */
function checkTimeRange($start_time, $end_time) {
    $current_time = date('H:i:s');

    // Ensure times are in 24-hour format
    $start_time = date('H:i:s', strtotime($start_time));
    $end_time = date('H:i:s', strtotime($end_time));

    if ($start_time && $end_time) {
        // Convert times to timestamps for more reliable comparison
        $current_timestamp = strtotime($current_time);
        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);

        // Handle overnight time ranges (e.g., 22:00:00 to 06:00:00)
        if ($start_timestamp > $end_timestamp) {
            // Overnight range: current time should be >= start_time OR <= end_time
            return $current_timestamp >= $start_timestamp || $current_timestamp <= $end_timestamp;
        } else {
            // Normal range: current time should be between start and end
            return $current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp;
        }
    }

    return true;
}

/**
 * Check if scanner is available based on date restrictions
 */
function isScannerAvailableByDate($conn, $event_id = null) {
    // If event ID is provided, check event's actual dates first
    if ($event_id) {
        $event_query = "SELECT start_datetime, end_datetime FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $event_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event_result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($event_result);

        if (!$event) {
            return false; // Event not found
        }

        // Check if current date is within event's date range
        $current_timestamp = time();
        $event_end_timestamp = strtotime($event['end_datetime']);

        // STRICT ENFORCEMENT: NO ATTENDANCE AFTER END DATE
        if ($current_timestamp > $event_end_timestamp) {
            return false; // Event has ended - NO MORE ATTENDANCE ACCEPTED
        }

        // Accept attendance until event end date/time
        return true;
    }

    // Use global date restrictions
    $settings = getScannerSettings($conn);

    // If date restrictions are not enabled, scanner is always available
    if (!isset($settings['scanner_date_restriction']) || $settings['scanner_date_restriction']['is_enabled'] != 1) {
        return true;
    }

    // Check date restrictions
    if (isset($settings['scanner_date_restriction'])) {
        $date_setting = $settings['scanner_date_restriction'];
        $current_date = date('Y-m-d');
        $start_date = $date_setting['start_date'];
        $end_date = $date_setting['end_date'];

        if ($start_date && $end_date) {
            return $current_date >= $start_date && $current_date <= $end_date;
        }
    }

    return true;
}

/**
 * Check overall scanner availability
 */
function isScannerAvailable($conn, $event_id = null) {
    return isScannerAvailableByTime($conn, $event_id) && isScannerAvailableByDate($conn, $event_id);
}

/**
 * Check if QR scanner is enabled for a specific event
 */
function isQRScannerEnabledForEvent($conn, $event_id = null) {
    // Check global QR scanner setting first
    if (!isQRScannerEnabled($conn)) {
        return false;
    }

    // If event_id is provided, check event-specific settings
    if ($event_id) {
        $event_query = "SELECT allow_qr_scanner FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $event_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event_result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($event_result);

        if (!$event) {
            return false; // Event doesn't exist
        }

        // Check if QR scanner is enabled for this specific event
        return (bool)$event['allow_qr_scanner'];
    }

    return true; // No specific event, use global setting
}

/**
 * Check if manual entry is enabled for a specific event
 */
function isManualEntryEnabledForEvent($conn, $event_id = null) {
    // Check global manual entry setting first
    if (!isManualIDEntryEnabled($conn)) {
        return false;
    }

    // If event_id is provided, check event-specific settings
    if ($event_id) {
        $event_query = "SELECT allow_manual_entry FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $event_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event_result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($event_result);

        if (!$event) {
            return false; // Event doesn't exist
        }

        // Check if manual entry is enabled for this specific event
        return (bool)$event['allow_manual_entry'];
    }

    return true; // No specific event, use global setting
}

/**
 * Get scanner availability status with detailed information
 */
function getScannerAvailabilityStatus($conn, $event_id = null) {
    $status = [
        'qr_scanner_enabled' => isQRScannerEnabledForEvent($conn, $event_id),
        'manual_id_enabled' => isManualEntryEnabledForEvent($conn, $event_id),
        'time_available' => isScannerAvailableByTime($conn, $event_id),
        'date_available' => isScannerAvailableByDate($conn, $event_id),
        'overall_available' => false,
        'restrictions' => [],
        'messages' => [],
        'event_specific' => false
    ];

    // Check if event exists and get event time information
    if ($event_id) {
        $event_query = "SELECT id, title, start_datetime, end_datetime FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $event_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event_result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($event_result);

        if (!$event) {
            $status['qr_scanner_enabled'] = false;
            $status['manual_id_enabled'] = false;
            $status['overall_available'] = false;
            $status['messages'][] = "Event not found";
            return $status;
        }

        // Add event time information to restrictions
        $status['restrictions']['event_time'] = [
            'enabled' => true,
            'start_time' => date('H:i:s', strtotime($event['start_datetime'])),
            'end_time' => date('H:i:s', strtotime($event['end_datetime'])),
            'start_datetime' => $event['start_datetime'],
            'end_datetime' => $event['end_datetime'],
            'source' => 'event'
        ];
        $status['event_specific'] = true;
    }

    // Check overall availability - TIME RESTRICTIONS TAKE ABSOLUTE PRIORITY
    // If time or date restrictions fail, ALWAYS disable regardless of global settings
    if (!$status['time_available'] || !$status['date_available']) {
        $status['overall_available'] = false;
    } else if (!$status['qr_scanner_enabled'] && !$status['manual_id_enabled']) {
        $status['overall_available'] = false;
    } else {
        $status['overall_available'] = true;
    }

    // Add messages for disabled features - prioritize global settings
    if (!$status['qr_scanner_enabled'] && !$status['manual_id_enabled']) {
        $status['messages'][] = "Both QR Code scanning and Manual Student ID entry have been disabled by administrators";
    } else {
        if (!$status['qr_scanner_enabled']) {
            $status['messages'][] = "QR Code scanning has been disabled by administrators";
        }

        if (!$status['manual_id_enabled']) {
            $status['messages'][] = "Manual Student ID entry has been disabled by administrators";
        }
    }

    // Only show time/date restriction messages if at least one feature is enabled
    if ($status['qr_scanner_enabled'] || $status['manual_id_enabled']) {
        if (!$status['time_available']) {
            if ($event_id && isset($status['restrictions']['event_time'])) {
                $event_time = $status['restrictions']['event_time'];
                $start_time = date('g:i A', strtotime($event_time['start_datetime']));
                $end_time = date('g:i A', strtotime($event_time['end_datetime']));
                $current_datetime = date('Y-m-d H:i:s');

                if ($current_datetime > $event_time['end_datetime']) {
                    $status['messages'][] = "Event has ended. Attendance is no longer accepted (Event time was: {$start_time} - {$end_time})";
                } else {
                    // This should rarely happen since we're more permissive now
                    $status['messages'][] = "Attendance is accepted until event ends at {$end_time}";
                }
            } else {
                $status['messages'][] = "Scanner is not available at this time due to time restrictions";
            }
        }

        if (!$status['date_available']) {
            if ($event_id && isset($status['restrictions']['event_time'])) {
                $event_start_date = date('F j, Y', strtotime($status['restrictions']['event_time']['start_datetime']));
                $event_end_date = date('F j, Y', strtotime($status['restrictions']['event_time']['end_datetime']));
                $current_date = date('Y-m-d');
                $event_start_date_check = date('Y-m-d', strtotime($status['restrictions']['event_time']['start_datetime']));
                $event_end_date_check = date('Y-m-d', strtotime($status['restrictions']['event_time']['end_datetime']));

                if ($current_date > $event_end_date_check) {
                    if ($event_start_date === $event_end_date) {
                        $status['messages'][] = "Event has ended. Attendance is no longer accepted (Event date was: {$event_start_date})";
                    } else {
                        $status['messages'][] = "Event has ended. Attendance is no longer accepted (Event dates were: {$event_start_date} to {$event_end_date})";
                    }
                } else {
                    // This should rarely happen since we're more permissive now
                    if ($event_start_date === $event_end_date) {
                        $status['messages'][] = "Attendance is accepted until event date ends: {$event_end_date}";
                    } else {
                        $status['messages'][] = "Attendance is accepted until event dates end: {$event_end_date}";
                    }
                }
            } else {
                $status['messages'][] = "Scanner is not available on this date due to date restrictions";
            }
        }
    }

    return $status;
}

/**
 * Render scanner unavailable message
 */
function renderScannerUnavailableMessage($status) {
    $html = '<div style="text-align: center; padding: 3rem; background: white; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
    $html .= '<div style="font-size: 4rem; margin-bottom: 1rem;">🚫</div>';
    $html .= '<h2 style="color: var(--error-color); margin-bottom: 1rem;">Scanner Currently Unavailable</h2>';
    
    if (!empty($status['messages'])) {
        $html .= '<div style="background: var(--error-light); padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; border-left: 4px solid var(--error-color);">';
        foreach ($status['messages'] as $message) {
            $html .= '<p style="margin: 0.5rem 0; color: var(--error-dark);">• ' . htmlspecialchars($message) . '</p>';
        }
        $html .= '</div>';
    }
    
    // Show when scanner will be available
    if (isset($status['restrictions']['time']) && $status['restrictions']['time']['enabled']) {
        $html .= '<div style="background: var(--info-light); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">';
        $html .= '<p style="margin: 0; color: var(--info-dark);"><strong>Available Hours:</strong> ';
        $html .= date('g:i A', strtotime($status['restrictions']['time']['start_time'])) . ' - ';
        $html .= date('g:i A', strtotime($status['restrictions']['time']['end_time']));
        $html .= '</p></div>';
    }
    
    if (isset($status['restrictions']['date']) && $status['restrictions']['date']['enabled']) {
        $html .= '<div style="background: var(--info-light); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">';
        $html .= '<p style="margin: 0; color: var(--info-dark);"><strong>Available Dates:</strong> ';
        $html .= date('M j, Y', strtotime($status['restrictions']['date']['start_date'])) . ' - ';
        $html .= date('M j, Y', strtotime($status['restrictions']['date']['end_date']));
        $html .= '</p></div>';
    }
    
    $html .= '<p style="color: var(--gray-600); margin: 0;">Please contact your administrator or SBO officers for assistance.</p>';
    $html .= '</div>';

    return $html;
}
