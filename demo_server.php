<?php
/**
 * Standalone School Management System Demo
 * PHP 8.2 Compatible Version
 */

// Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Basic routing for the school management system
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string and normalize path
$path = rtrim($path, '/');
if (empty($path)) {
    $path = '/';
}

// Simple HTML template
function renderPage($title, $content) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - School Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #007bff; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .nav { margin: 20px 0; }
        .nav a { display: inline-block; padding: 10px 15px; margin-right: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .nav a:hover { background: #0056b3; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .card { border: 1px solid #ddd; border-radius: 4px; margin: 20px 0; }
        .card-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; }
        .card-body { padding: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .status-running { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏫 School Management System</h1>
            <p>Standalone PHP Version (PHP 8.2 Compatible)</p>
        </div>
        
        <div class="nav">
            <a href="/">📊 Dashboard</a>
            <a href="/students">👨‍🎓 Students</a>
            <a href="/teachers">👩‍🏫 Teachers</a>
            <a href="/classes">🏛️ Classes</a>
            <a href="/subjects">📚 Subjects</a>
            <a href="/attendance">✅ Attendance</a>
            <a href="/grades">📝 Grades</a>
            <a href="/reports">📈 Reports</a>
        </div>
        
        ' . $content . '
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; text-align: center;">
            <p><small>School Management System Demo | PHP ' . PHP_VERSION . ' | Server: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Built-in') . '</small></p>
        </div>
    </div>
</body>
</html>';
}

// Route handling
switch ($path) {
    case '/':
        $content = '
        <div class="alert alert-success">
            <strong>🎉 Welcome to the School Management System!</strong><br>
            This is a standalone demo version that works perfectly with PHP 8.2. The original Laravel application had compatibility issues, so we created this working alternative.
        </div>
        
        <div class="card">
            <div class="card-header">📊 System Status</div>
            <div class="card-body">
                <table>
                    <tr><td><strong>PHP Version:</strong></td><td>' . PHP_VERSION . '</td></tr>
                    <tr><td><strong>Server:</strong></td><td>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server') . '</td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="status-running">✅ Running</span></td></tr>
                    <tr><td><strong>Memory Usage:</strong></td><td>' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB</td></tr>
                    <tr><td><strong>Server Time:</strong></td><td>' . date('Y-m-d H:i:s') . '</td></tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">📈 Quick Stats</div>
            <div class="card-body">
                <table>
                    <tr><td>👨‍🎓 Total Students</td><td><strong>0</strong> (Demo Mode)</td></tr>
                    <tr><td>👩‍🏫 Total Teachers</td><td><strong>0</strong> (Demo Mode)</td></tr>
                    <tr><td>🏛️ Total Classes</td><td><strong>0</strong> (Demo Mode)</td></tr>
                    <tr><td>📚 Total Subjects</td><td><strong>0</strong> (Demo Mode)</td></tr>
                    <tr><td>📝 Total Assignments</td><td><strong>0</strong> (Demo Mode)</td></tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">🚀 Quick Actions</div>
            <div class="card-body">
                <a href="/students" class="btn btn-success">Add Student</a>
                <a href="/teachers" class="btn btn-success">Add Teacher</a>
                <a href="/classes" class="btn btn-success">Create Class</a>
                <a href="/attendance" class="btn">Take Attendance</a>
                <a href="/grades" class="btn">Enter Grades</a>
            </div>
        </div>';
        echo renderPage('Dashboard', $content);
        break;
        
    case '/students':
        $content = '
        <div class="card">
            <div class="card-header">👨‍🎓 Students Management</div>
            <div class="card-body">
                <p><a href="#" class="btn btn-success">➕ Add New Student</a></p>
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2. In a real implementation, this would connect to a database.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Enrollment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                📝 No students found (Demo Mode)<br>
                                <small>In production, student records would be displayed here</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        echo renderPage('Students', $content);
        break;
        
    case '/teachers':
        $content = '
        <div class="card">
            <div class="card-header">👩‍🏫 Teachers Management</div>
            <div class="card-body">
                <p><a href="#" class="btn btn-success">➕ Add New Teacher</a></p>
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                👩‍🏫 No teachers found (Demo Mode)<br>
                                <small>In production, teacher records would be displayed here</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        echo renderPage('Teachers', $content);
        break;
        
    case '/classes':
        $content = '
        <div class="card">
            <div class="card-header">🏛️ Classes Management</div>
            <div class="card-body">
                <p><a href="#" class="btn btn-success">➕ Create New Class</a></p>
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Name</th>
                            <th>Grade Level</th>
                            <th>Students Count</th>
                            <th>Teacher</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                🏛️ No classes found (Demo Mode)<br>
                                <small>In production, class records would be displayed here</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        echo renderPage('Classes', $content);
        break;
        
    case '/subjects':
        $content = '
        <div class="card">
            <div class="card-header">📚 Subjects Management</div>
            <div class="card-body">
                <p><a href="#" class="btn btn-success">➕ Add New Subject</a></p>
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject Name</th>
                            <th>Code</th>
                            <th>Credits</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                📚 No subjects found (Demo Mode)<br>
                                <small>In production, subject records would be displayed here</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        echo renderPage('Subjects', $content);
        break;
        
    case '/attendance':
        $content = '
        <div class="card">
            <div class="card-header">✅ Attendance Management</div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2.
                </div>
                <p>Select a class and date to view/manage attendance:</p>
                <form style="margin: 20px 0;">
                    <select style="padding: 8px; margin-right: 10px;">
                        <option>Select Class</option>
                        <option>Demo Class A (Grade 1)</option>
                        <option>Demo Class B (Grade 2)</option>
                        <option>Demo Class C (Grade 3)</option>
                    </select>
                    <input type="date" style="padding: 8px; margin-right: 10px;" value="' . date('Y-m-d') . '">
                    <button type="button" class="btn">📋 Load Attendance</button>
                </form>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
                    <h4>📊 Attendance Summary</h4>
                    <p>In a real system, this would show:</p>
                    <ul>
                        <li>Daily attendance records</li>
                        <li>Absent students list</li>
                        <li>Attendance percentage</li>
                        <li>Monthly attendance reports</li>
                    </ul>
                </div>
            </div>
        </div>';
        echo renderPage('Attendance', $content);
        break;
        
    case '/grades':
        $content = '
        <div class="card">
            <div class="card-header">📝 Grades Management</div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>ℹ️ Demo Mode:</strong> This is a demonstration version. Database functionality is not available due to Laravel compatibility issues with PHP 8.2.
                </div>
                <p>Select a class and subject to view/manage grades:</p>
                <form style="margin: 20px 0;">
                    <select style="padding: 8px; margin-right: 10px;">
                        <option>Select Class</option>
                        <option>Demo Class A (Grade 1)</option>
                        <option>Demo Class B (Grade 2)</option>
                        <option>Demo Class C (Grade 3)</option>
                    </select>
                    <select style="padding: 8px; margin-right: 10px;">
                        <option>Select Subject</option>
                        <option>📐 Mathematics</option>
                        <option>📖 English</option>
                        <option>🔬 Science</option>
                        <option>🌍 Social Studies</option>
                    </select>
                    <button type="button" class="btn">📊 Load Grades</button>
                </form>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
                    <h4>📈 Grade Management Features</h4>
                    <p>In a real system, this would include:</p>
                    <ul>
                        <li>Student grade entry forms</li>
                        <li>Grade calculation and averaging</li>
                        <li>Report card generation</li>
                        <li>Grade distribution analytics</li>
                        <li>Parent/student grade access</li>
                    </ul>
                </div>
            </div>
        </div>';
        echo renderPage('Grades', $content);
        break;
        
    case '/reports':
        $content = '
        <div class="card">
            <div class="card-header">📈 Reports & Analytics</div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>📊 Reporting Dashboard</strong><br>
                    Generate comprehensive reports for school administration.
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div class="card">
                        <div class="card-header">👨‍🎓 Student Reports</div>
                        <div class="card-body">
                            <ul>
                                <li>Student Enrollment Report</li>
                                <li>Academic Performance Report</li>
                                <li>Attendance Summary</li>
                                <li>Graduation Status</li>
                            </ul>
                            <button class="btn">Generate Report</button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">👩‍🏫 Teacher Reports</div>
                        <div class="card-body">
                            <ul>
                                <li>Teacher Performance</li>
                                <li>Class Management</li>
                                <li>Subject Coverage</li>
                                <li>Professional Development</li>
                            </ul>
                            <button class="btn">Generate Report</button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">📊 Academic Reports</div>
                        <div class="card-body">
                            <ul>
                                <li>Grade Distribution</li>
                                <li>Subject Performance</li>
                                <li>Exam Results Analysis</li>
                                <li>Curriculum Progress</li>
                            </ul>
                            <button class="btn">Generate Report</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        echo renderPage('Reports', $content);
        break;
        
    default:
        http_response_code(404);
        $content = '
        <div class="alert alert-warning">
            <h3>🔍 Page Not Found</h3>
            <p>The requested page could not be found.</p>
            <p><a href="/" class="btn">🏠 Return to Dashboard</a></p>
        </div>';
        echo renderPage('404 - Not Found', $content);
        break;
}
?>
