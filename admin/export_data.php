<?php
session_start();
include '../db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$export_type = $_GET['type'] ?? 'students';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="adlor_' . $export_type . '_' . date('Y-m-d_H-i-s') . '.csv"');

// Add UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Create output stream
$output = fopen('php://output', 'w');

switch ($export_type) {
    case 'students':
        // Export students data
        fputcsv($output, ['Student ID', 'Full Name', 'Course', 'Section', 'Created At']);
        
        $query = "SELECT student_id, full_name, course, section, created_at FROM students ORDER BY student_id";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['student_id'],
                $row['full_name'],
                $row['course'],
                $row['section'],
                $row['created_at']
            ]);
        }
        break;
        
    case 'events':
        // Export events data
        fputcsv($output, ['Event ID', 'Title', 'Description', 'Event Date', 'Start Time', 'End Time', 'Location', 'Assigned Sections', 'Created By', 'Created At']);
        
        $query = "SELECT e.*, s.name as created_by_name FROM events e 
                  LEFT JOIN sbo_users s ON e.created_by = s.id 
                  ORDER BY e.event_date DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['description'],
                $row['event_date'],
                $row['start_time'],
                $row['end_time'],
                $row['location'],
                $row['assigned_sections'],
                $row['created_by_name'] ?? 'Unknown',
                $row['created_at']
            ]);
        }
        break;
        
    case 'attendance':
        // Export attendance data
        fputcsv($output, ['Attendance ID', 'Student ID', 'Student Name', 'Event ID', 'Event Title', 'Time In', 'Time Out', 'Status']);
        
        $query = "SELECT a.*,
                  COALESCE(os.full_name, s.full_name) as student_name,
                  e.title as event_title,
                  CASE
                      WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
                      WHEN a.time_in IS NOT NULL THEN 'Time In Only'
                      ELSE 'Absent'
                  END as status
                  FROM attendance a
                  LEFT JOIN official_students os ON a.student_id = os.student_id
                  LEFT JOIN students s ON a.student_id = s.student_id
                  LEFT JOIN events e ON a.event_id = e.id
                  ORDER BY a.time_in DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['student_id'],
                $row['student_name'] ?? 'Unknown Student',
                $row['event_id'],
                $row['event_title'] ?? 'Unknown Event',
                $row['time_in'],
                $row['time_out'],
                $row['status']
            ]);
        }
        break;
        
    case 'sbo_users':
        // Export SBO users data
        fputcsv($output, ['SBO ID', 'Name', 'Email', 'Position', 'Is Active', 'Created At']);
        
        $query = "SELECT id, name, email, position, is_active, created_at FROM sbo_users ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['position'],
                $row['is_active'] ? 'Active' : 'Inactive',
                $row['created_at']
            ]);
        }
        break;
        
    case 'full':
        // Export full system data (multiple sheets simulation)
        fputcsv($output, ['=== ADLOR SYSTEM FULL BACKUP ===']);
        fputcsv($output, ['Export Date: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Admin: ' . $_SESSION['admin_name']]);
        fputcsv($output, []);
        
        // Students section
        fputcsv($output, ['=== STUDENTS ===']);
        fputcsv($output, ['Student ID', 'Full Name', 'Course', 'Section', 'Created At']);
        
        $query = "SELECT student_id, full_name, course, section, created_at FROM students ORDER BY student_id";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['student_id'],
                $row['full_name'],
                $row['course'],
                $row['section'],
                $row['created_at']
            ]);
        }
        
        fputcsv($output, []);
        
        // Events section
        fputcsv($output, ['=== EVENTS ===']);
        fputcsv($output, ['Event ID', 'Title', 'Description', 'Event Date', 'Start Time', 'End Time', 'Location', 'Assigned Sections', 'Created At']);
        
        $query = "SELECT * FROM events ORDER BY event_date DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['description'],
                $row['event_date'],
                $row['start_time'],
                $row['end_time'],
                $row['location'],
                $row['assigned_sections'],
                $row['created_at']
            ]);
        }
        
        fputcsv($output, []);
        
        // Attendance section
        fputcsv($output, ['=== ATTENDANCE ===']);
        fputcsv($output, ['Attendance ID', 'Student ID', 'Event ID', 'Time In', 'Time Out']);
        
        $query = "SELECT * FROM attendance ORDER BY time_in DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['student_id'],
                $row['event_id'],
                $row['time_in'],
                $row['time_out']
            ]);
        }
        
        fputcsv($output, []);
        
        // SBO Users section
        fputcsv($output, ['=== SBO USERS ===']);
        fputcsv($output, ['SBO ID', 'Name', 'Email', 'Position', 'Is Active', 'Created At']);
        
        $query = "SELECT id, name, email, position, is_active, created_at FROM sbo_users ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['position'],
                $row['is_active'] ? 'Active' : 'Inactive',
                $row['created_at']
            ]);
        }
        break;
        
    default:
        fputcsv($output, ['Error: Invalid export type']);
        break;
}

fclose($output);
exit;
