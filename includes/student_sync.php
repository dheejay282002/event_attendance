<?php
/**
 * ADLOR Student Synchronization System
 * Ensures all student data is synchronized across the entire system
 */

function syncStudentAcrossSystem($conn, $student_id, $full_name, $course, $section, $action = 'add') {
    $sync_results = [
        'success' => true,
        'operations' => [],
        'errors' => []
    ];

    try {
        // Start transaction for data consistency
        mysqli_autocommit($conn, false);

        // 1. Update/Insert in official_students table (master record)
        if ($action === 'add') {
            $official_stmt = mysqli_prepare($conn, "
                INSERT INTO official_students (student_id, full_name, course, section, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                full_name = VALUES(full_name), 
                course = VALUES(course), 
                section = VALUES(section), 
                updated_at = NOW()
            ");
            mysqli_stmt_bind_param($official_stmt, "ssss", $student_id, $full_name, $course, $section);
        } else {
            $official_stmt = mysqli_prepare($conn, "
                UPDATE official_students 
                SET full_name = ?, course = ?, section = ?, updated_at = NOW() 
                WHERE student_id = ?
            ");
            mysqli_stmt_bind_param($official_stmt, "ssss", $full_name, $course, $section, $student_id);
        }
        
        if (mysqli_stmt_execute($official_stmt)) {
            $sync_results['operations'][] = "Updated official_students table";
        } else {
            throw new Exception("Failed to update official_students: " . mysqli_error($conn));
        }

        // 2. Update students table if login account exists (preserve password)
        $login_check = mysqli_prepare($conn, "SELECT student_id, password FROM students WHERE student_id = ?");
        mysqli_stmt_bind_param($login_check, "s", $student_id);
        mysqli_stmt_execute($login_check);
        $login_result = mysqli_stmt_get_result($login_check);
        
        if (mysqli_num_rows($login_result) > 0) {
            $update_login_stmt = mysqli_prepare($conn, "
                UPDATE students 
                SET full_name = ?, course = ?, section = ?, updated_at = NOW() 
                WHERE student_id = ?
            ");
            mysqli_stmt_bind_param($update_login_stmt, "ssss", $full_name, $course, $section, $student_id);
            
            if (mysqli_stmt_execute($update_login_stmt)) {
                $sync_results['operations'][] = "Updated students login table";
                
                // Regenerate QR code for students with login accounts
                if (regenerateStudentQRCode($student_id, $full_name)) {
                    $sync_results['operations'][] = "Regenerated student QR code";
                }
            } else {
                $sync_results['errors'][] = "Failed to update students table: " . mysqli_error($conn);
            }
        }

        // 3. Update year level based on section and course
        if (updateStudentYearLevel($conn, $student_id, $course, $section)) {
            $sync_results['operations'][] = "Updated year level information (extracted from section: $section)";
        } else {
            $sync_results['errors'][] = "Failed to update year level";
        }

        // 4. Attendance records are linked by student_id only (no need to update student info)
        // The attendance table only stores student_id, event_id, and timestamps
        // Student info is retrieved via JOIN with official_students or students table
        $sync_results['operations'][] = "Attendance records remain linked via student_id";

        // 5. Event registrations are handled through events.assigned_sections
        // No separate event_registrations table exists in current schema
        $sync_results['operations'][] = "Event participation managed via assigned_sections";

        // 6. Clean up unused sections
        $cleaned_sections = cleanupUnusedSections($conn);
        if (!empty($cleaned_sections)) {
            $sync_results['operations'][] = "Cleaned up " . count($cleaned_sections) . " unused sections";
        }

        // 7. Log the synchronization (optional - only if table exists)
        $log_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_sync_log'");
        if (mysqli_num_rows($log_table_check) > 0) {
            $log_stmt = mysqli_prepare($conn, "
                INSERT INTO student_sync_log (student_id, action, operations, timestamp)
                VALUES (?, ?, ?, NOW())
            ");
            $operations_json = json_encode($sync_results['operations']);
            mysqli_stmt_bind_param($log_stmt, "sss", $student_id, $action, $operations_json);
            mysqli_stmt_execute($log_stmt);
            $sync_results['operations'][] = "Logged synchronization activity";
        } else {
            $sync_results['operations'][] = "Synchronization completed (logging table not available)";
        }

        // Commit transaction
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);

        $sync_results['success'] = empty($sync_results['errors']);
        return $sync_results;

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        
        $sync_results['success'] = false;
        $sync_results['errors'][] = $e->getMessage();
        return $sync_results;
    }
}

function updateStudentYearLevel($conn, $student_id, $course, $section = '') {
    // Extract year level from section name using simplified logic
    $year_level = 1; // Default

    // First try to extract from section (prioritize highest number)
    if (!empty($section)) {
        if (strpos($section, '4') !== false) {
            $year_level = 4;
        } elseif (strpos($section, '3') !== false) {
            $year_level = 3;
        } elseif (strpos($section, '2') !== false) {
            $year_level = 2;
        } elseif (strpos($section, '1') !== false) {
            $year_level = 1;
        }
    }

    // Fallback to course if section has no numbers
    if ($year_level == 1 && !empty($course)) {
        if (strpos($course, '4') !== false) {
            $year_level = 4;
        } elseif (strpos($course, '3') !== false) {
            $year_level = 3;
        } elseif (strpos($course, '2') !== false) {
            $year_level = 2;
        } elseif (strpos($course, '1') !== false) {
            $year_level = 1;
        }
    }

    // Check if table exists before trying to update
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_year_levels'");
    if (mysqli_num_rows($table_check) == 0) {
        // Table doesn't exist, skip this operation
        return true;
    }

    // Update or insert year level
    $year_update = mysqli_prepare($conn, "
        INSERT INTO student_year_levels (student_id, year_level, course, section, updated_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        year_level = VALUES(year_level),
        course = VALUES(course),
        section = VALUES(section),
        updated_at = NOW()
    ");
    mysqli_stmt_bind_param($year_update, "siss", $student_id, $year_level, $course, $section);
    return mysqli_stmt_execute($year_update);
}

function regenerateStudentQRCode($student_id, $full_name) {
    try {
        // Generate QR code data
        $qr_data = json_encode([
            'student_id' => $student_id,
            'full_name' => $full_name,
            'timestamp' => time(),
            'hash' => md5($student_id . date('Y-m-d H:i:s'))
        ]);
        
        // Create QR code directory if it doesn't exist
        $qr_dir = '../uploads/qr_codes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        
        // Generate QR code filename
        $qr_filename = 'student_' . $student_id . '.png';
        $qr_filepath = $qr_dir . $qr_filename;
        
        // Use QR code API
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
        
        // Download and save QR code
        $qr_content = file_get_contents($qr_url);
        if ($qr_content !== false) {
            return file_put_contents($qr_filepath, $qr_content) !== false;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function cleanupUnusedSections($conn) {
    // Get all sections that exist in events but have no students
    $cleanup_query = "
        SELECT DISTINCT assigned_sections 
        FROM events 
        WHERE assigned_sections IS NOT NULL 
        AND assigned_sections != ''
    ";
    
    $result = mysqli_query($conn, $cleanup_query);
    $sections_to_check = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $sections = explode(',', $row['assigned_sections']);
        foreach ($sections as $section) {
            $section = trim($section);
            if (!empty($section)) {
                $sections_to_check[] = $section;
            }
        }
    }
    
    $cleaned_sections = [];
    
    foreach (array_unique($sections_to_check) as $section) {
        // Check if this section has any students
        $student_check = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM official_students WHERE section = ?");
        mysqli_stmt_bind_param($student_check, "s", $section);
        mysqli_stmt_execute($student_check);
        $count_result = mysqli_stmt_get_result($student_check);
        $count_row = mysqli_fetch_assoc($count_result);

        if ($count_row && isset($count_row['count']) && $count_row['count'] == 0) {
            // Remove this section from all events
            $update_events = "UPDATE events SET assigned_sections = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', assigned_sections, ','), CONCAT(',', ?, ','), ',')) WHERE FIND_IN_SET(?, assigned_sections)";
            $update_stmt = mysqli_prepare($conn, $update_events);
            mysqli_stmt_bind_param($update_stmt, "ss", $section, $section);
            mysqli_stmt_execute($update_stmt);
            
            $cleaned_sections[] = $section;
        }
    }
    
    // Clean up empty assigned_sections
    mysqli_query($conn, "UPDATE events SET assigned_sections = NULL WHERE assigned_sections = '' OR assigned_sections = ','");
    
    return $cleaned_sections;
}

function batchSyncStudents($conn, $students_data) {
    $batch_results = [
        'success' => true,
        'total_processed' => 0,
        'successful_syncs' => 0,
        'failed_syncs' => 0,
        'errors' => []
    ];

    foreach ($students_data as $student) {
        $batch_results['total_processed']++;
        
        $sync_result = syncStudentAcrossSystem(
            $conn,
            $student['student_id'] ?? '',
            $student['full_name'] ?? '',
            $student['course'] ?? '',
            $student['section'] ?? '',
            $student['action'] ?? 'update'
        );

        if ($sync_result['success']) {
            $batch_results['successful_syncs']++;
        } else {
            $batch_results['failed_syncs']++;
            $student_id = $student['student_id'] ?? 'Unknown';
            $errors = isset($sync_result['errors']) ? implode(', ', $sync_result['errors']) : 'Unknown error';
            $batch_results['errors'][] = "Student {$student_id}: {$errors}";
        }
    }

    $batch_results['success'] = $batch_results['failed_syncs'] == 0;
    return $batch_results;
}

function ensureCourseExists($conn, $course_code, &$courses_added) {
    // Check if course already exists
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE course_code = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $course_code);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($check_stmt);
        return isset($row['id']) ? $row['id'] : null;
    }

    // Course doesn't exist, create it
    $course_name = generateCourseName($course_code);
    $description = "Auto-generated course from student import";

    $insert_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO courses (course_code, course_name, description) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "sss", $course_code, $course_name, $description);

    if (mysqli_stmt_execute($insert_stmt)) {
        $course_id = mysqli_insert_id($conn);
        if ($course_id > 0) {
            $courses_added[] = $course_code;
        } else {
            // Course already exists, get its ID
            $existing_stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE course_code = ?");
            mysqli_stmt_bind_param($existing_stmt, "s", $course_code);
            mysqli_stmt_execute($existing_stmt);
            $existing_result = mysqli_stmt_get_result($existing_stmt);
            $existing_row = mysqli_fetch_assoc($existing_result);

            if ($existing_row && isset($existing_row['id'])) {
                $course_id = $existing_row['id'];
            } else {
                $course_id = null;
            }
            mysqli_stmt_close($existing_stmt);
        }
        mysqli_stmt_close($insert_stmt);
        mysqli_stmt_close($check_stmt);
        return $course_id;
    }

    mysqli_stmt_close($insert_stmt);
    mysqli_stmt_close($check_stmt);
    return null;
}

function ensureSectionExists($conn, $section_code, $course_id, &$sections_added) {
    // Check if section already exists for this course
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM sections WHERE section_code = ? AND course_id = ?");
    mysqli_stmt_bind_param($check_stmt, "si", $section_code, $course_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($check_stmt);
        return isset($row['id']) ? $row['id'] : null;
    }

    // Section doesn't exist, create it
    $section_name = generateSectionName($section_code);
    $default_year_id = getDefaultYearLevel($conn);
    $max_students = 50; // Default max students

    $insert_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO sections (section_code, course_id, year_id, section_name, max_students) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "siisi", $section_code, $course_id, $default_year_id, $section_name, $max_students);

    if (mysqli_stmt_execute($insert_stmt)) {
        $section_id = mysqli_insert_id($conn);
        if ($section_id > 0) {
            $sections_added[] = $section_code;
        } else {
            // Section already exists, get its ID
            $existing_stmt = mysqli_prepare($conn, "SELECT id FROM sections WHERE section_code = ?");
            mysqli_stmt_bind_param($existing_stmt, "s", $section_code);
            mysqli_stmt_execute($existing_stmt);
            $existing_result = mysqli_stmt_get_result($existing_stmt);
            $existing_row = mysqli_fetch_assoc($existing_result);
            $section_id = ($existing_row && isset($existing_row['id'])) ? $existing_row['id'] : null;
            mysqli_stmt_close($existing_stmt);
        }
        mysqli_stmt_close($insert_stmt);
        mysqli_stmt_close($check_stmt);
        return $section_id;
    }

    mysqli_stmt_close($insert_stmt);
    mysqli_stmt_close($check_stmt);
    return null;
}

function generateCourseName($course_code) {
    $course_names = [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSCS' => 'Bachelor of Science in Computer Science',
        'BSIS' => 'Bachelor of Science in Information Systems',
        'BSBA' => 'Bachelor of Science in Business Administration',
        'BSA' => 'Bachelor of Science in Accountancy',
        'BSED' => 'Bachelor of Science in Education',
        'BEED' => 'Bachelor of Elementary Education',
        'BSECE' => 'Bachelor of Science in Electronics and Communications Engineering',
        'BSCE' => 'Bachelor of Science in Civil Engineering',
        'BSME' => 'Bachelor of Science in Mechanical Engineering',
        'BSEE' => 'Bachelor of Science in Electrical Engineering',
        'BSN' => 'Bachelor of Science in Nursing',
        'BSHRM' => 'Bachelor of Science in Hotel and Restaurant Management',
        'BSTM' => 'Bachelor of Science in Tourism Management'
    ];

    return $course_names[$course_code] ?? $course_code . ' Program';
}

function generateSectionName($section_code) {
    // Generate a descriptive section name
    return "Section " . $section_code;
}

function getDefaultYearLevel($conn) {
    // Get the first year level ID, or create one if none exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM year_levels ORDER BY year_code ASC LIMIT 1");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return isset($row['id']) ? $row['id'] : null;
    }

    // No year levels exist, create a default one
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO year_levels (year_code, year_name) VALUES ('1', 'Year 1')");
    mysqli_stmt_execute($insert_stmt);
    $year_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);
    mysqli_stmt_close($stmt);

    return $year_id;
}
?>
