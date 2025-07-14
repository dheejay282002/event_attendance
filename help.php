<?php
require_once 'db_connect.php';
require_once 'includes/system_config.php';
require_once 'includes/favicon.php';

// Get system settings
$system_name = getSystemSetting($conn, 'system_name', 'ADLOR System');
$system_description = getSystemSetting($conn, 'system_description', 'Advanced Digital Learning Operations & Records');
$system_logo = getSystemSetting($conn, 'system_logo', '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help & About | <?= htmlspecialchars($system_name) ?></title>
  <?= generateFaviconTags($conn) ?>
  <link rel="stylesheet" href="assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #7c3aed;
      --primary-dark: #5b21b6;
      --primary-hover: #8b5cf6;
      --gray-900: #111827;
      --gray-600: #4b5563;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(0deg, var(--primary-color) 0%, var(--primary-dark) 10%, rgb(2, 17, 46) 100%);
      min-height: 100vh;
      color: white;
      animation: pageLoad 1.5s ease-out;
      overflow-x: hidden;
    }

    /* Animated background particles */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background:
        radial-gradient(circle at 20% 80%, rgba(120, 58, 237, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(0, 255, 204, 0.2) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.2) 0%, transparent 50%);
      animation: backgroundFloat 20s ease-in-out infinite;
      pointer-events: none;
      z-index: -1;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
      animation: fadeInUp 0.8s ease-out;
    }

    .header {
      text-align: center;
      margin-bottom: 3rem;
      padding: 2rem 0;
      animation: slideInDown 1s ease-out;
    }

    .header h1 {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 1rem;
      color: #ffffff;
      animation: glow 2s ease-in-out infinite alternate;
    }

    .header p {
      font-size: 1.25rem;
      color: #ffffff;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
      animation: fadeIn 1.2s ease-out 0.3s both;
    }

    .back-btn {
      position: fixed;
      top: 2rem;
      left: 2rem;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      width: 60px;
      height: 60px;
      color: white;
      text-decoration: none;
      font-size: 1.5rem;
      font-weight: bold;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      /* Removed animations as requested */
    }

    .back-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.4);
      /* Removed transform animations */
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      color: white;
      text-decoration: none;
      animation: backButtonSpin 0.6s ease-in-out;
    }

    .help-tabs {
      display: flex;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 1rem;
      margin-bottom: 2rem;
      overflow-x: auto;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .tab-btn {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      padding: 1rem 1.5rem;
      cursor: pointer;
      transition: color 0.3s ease, background 0.3s ease;
      font-weight: 500;
      white-space: nowrap;
      border-radius: 1rem;
      margin: 0.5rem;
      /* Removed bounceIn animation */
    }

    .tab-btn:hover {
      color: white;
      background: rgba(255, 255, 255, 0.1);
      /* Removed transform animation */
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .tab-btn.active {
      color: white;
      background: rgba(255, 255, 255, 0.2);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      /* Removed pulse animation */
    }

    /* Removed tab button animation delays */

    .tab-content {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 2rem;
      padding: 3rem;
      margin-bottom: 2rem;
      animation: contentSlideIn 0.8s ease-out;
      position: relative;
      overflow: hidden;
    }

    .tab-content::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(0, 255, 204, 0.1), transparent);
      animation: shimmer 3s linear infinite;
      pointer-events: none;
    }

    .tab-pane {
      display: none;
      animation: tabFadeIn 0.6s ease-out;
    }

    .tab-pane.active {
      display: block;
      animation: tabSlideIn 0.8s ease-out;
    }

    .tab-pane h2 {
      color: white;
      margin-bottom: 1.5rem;
      font-size: 2rem;
      font-weight: 700;
      animation: titleSlideIn 1s ease-out 0.2s both;
      position: relative;
    }

    .tab-pane h2::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 0;
      height: 3px;
      background: linear-gradient(90deg, #00ffcc, #7c3aed);
      animation: underlineExpand 1.5s ease-out 0.8s both;
    }

    .tab-pane h3 {
      color: #00ffcc;
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.5rem;
      font-weight: 600;
      /* Removed slideInLeft animation */
      position: relative;
    }

    .tab-pane h3::before {
      content: '▶';
      margin-right: 0.5rem;
      animation: arrowBounce 2s ease-in-out infinite;
    }

    .tab-pane h4 {
      color: white;
      margin-top: 1.5rem;
      margin-bottom: 0.75rem;
      font-size: 1.25rem;
      font-weight: 600;
      animation: fadeInUp 0.6s ease-out 0.6s both;
      transition: all 0.3s ease;
    }

    .tab-pane h4:hover {
      color: #00ffcc;
      transform: translateX(10px);
    }

    .tab-pane p {
      color: rgba(255, 255, 255, 0.9);
      line-height: 1.7;
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
      animation: textFadeIn 0.8s ease-out 0.8s both;
    }

    .tab-pane ul {
      color: rgba(255, 255, 255, 0.9);
      padding-left: 2rem;
      margin-bottom: 1.5rem;
      animation: listSlideIn 1s ease-out 1s both;
    }

    .tab-pane li {
      margin-bottom: 0.75rem;
      line-height: 1.6;
      font-size: 1.05rem;
    }

    .how-to-steps .step {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 1.5rem;
      padding: 2rem;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }

    .step:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-2px);
    }

    .step h4 {
      margin-top: 0;
      color: #00ffcc;
      font-size: 1.3rem;
    }

    .step p {
      margin-bottom: 0;
      font-size: 1.1rem;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 1.5rem;
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      animation: slideInUp 0.6s ease-out;
      animation-fill-mode: both;
      opacity: 0;
    }

    .feature-card:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .feature-card:nth-child(1) { animation-delay: 0.1s; }
    .feature-card:nth-child(2) { animation-delay: 0.2s; }
    .feature-card:nth-child(3) { animation-delay: 0.3s; }
    .feature-card:nth-child(4) { animation-delay: 0.4s; }
    .feature-card:nth-child(5) { animation-delay: 0.5s; }
    .feature-card:nth-child(6) { animation-delay: 0.6s; }

    .feature-card h4 {
      color: #00ffcc;
      margin-top: 0;
      margin-bottom: 1rem;
      font-size: 1.2rem;
    }

    .feature-card p {
      margin-bottom: 0;
      font-size: 1rem;
    }

    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }

      .header h1 {
        font-size: 2rem;
      }

      .header p {
        font-size: 1rem;
      }

      .back-btn {
        top: 1rem;
        left: 1rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
      }

      .help-tabs {
        flex-wrap: wrap;
      }

      .tab-btn {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
      }

      .tab-content {
        padding: 2rem 1.5rem;
      }

      .tab-pane h2 {
        font-size: 1.5rem;
      }

      .tab-pane h3 {
        font-size: 1.25rem;
      }

      .feature-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Animation Keyframes */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    @keyframes glow {
      from {
        text-shadow: 0 0 10px rgba(0, 255, 204, 0.5);
      }
      to {
        text-shadow: 0 0 20px rgba(0, 255, 204, 0.8), 0 0 30px rgba(0, 255, 204, 0.6);
      }
    }

    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    @keyframes bounceIn {
      0% {
        opacity: 0;
        transform: scale(0.3);
      }
      50% {
        opacity: 1;
        transform: scale(1.05);
      }
      70% {
        transform: scale(0.9);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Additional comprehensive animations */
    @keyframes pageLoad {
      0% {
        opacity: 0;
        transform: scale(0.95);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes backgroundFloat {
      0%, 100% {
        transform: translateX(0) translateY(0) rotate(0deg);
      }
      33% {
        transform: translateX(30px) translateY(-30px) rotate(120deg);
      }
      66% {
        transform: translateX(-20px) translateY(20px) rotate(240deg);
      }
    }

    @keyframes shimmer {
      0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
      }
      100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
      }
    }

    @keyframes contentSlideIn {
      0% {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @keyframes tabFadeIn {
      0% {
        opacity: 0;
      }
      100% {
        opacity: 1;
      }
    }

    @keyframes tabSlideIn {
      0% {
        opacity: 0;
        transform: translateX(-30px);
      }
      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes titleSlideIn {
      0% {
        opacity: 0;
        transform: translateY(-20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes underlineExpand {
      0% {
        width: 0;
      }
      100% {
        width: 100%;
      }
    }

    @keyframes slideInLeft {
      0% {
        opacity: 0;
        transform: translateX(-50px);
      }
      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes arrowBounce {
      0%, 100% {
        transform: translateX(0);
      }
      50% {
        transform: translateX(5px);
      }
    }

    @keyframes textFadeIn {
      0% {
        opacity: 0;
        transform: translateY(10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes listSlideIn {
      0% {
        opacity: 0;
        transform: translateX(-20px);
      }
      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes listItemFadeIn {
      0% {
        opacity: 0;
        transform: translateY(10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes sparkle {
      0%, 100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
      }
      50% {
        transform: scale(1.2) rotate(180deg);
        opacity: 0.7;
      }
    }

    @keyframes stepSlideIn {
      0% {
        opacity: 0;
        transform: translateX(-30px) scale(0.95);
      }
      100% {
        opacity: 1;
        transform: translateX(0) scale(1);
      }
    }

    @keyframes stepShimmer {
      0% {
        left: -100%;
      }
      100% {
        left: 100%;
      }
    }

    @keyframes sectionFadeIn {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes backButtonFloat {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-5px);
      }
    }

    @keyframes backButtonSpin {
      0% {
        transform: translateY(-2px) scale(1.1) rotate(-10deg);
      }
      50% {
        transform: translateY(-2px) scale(1.1) rotate(10deg);
      }
      100% {
        transform: translateY(-2px) scale(1.1) rotate(-10deg);
      }
    }
  </style>
</head>
<body>
  <a href="index.php" class="back-btn">←</a>
  
  <div class="container">
    <div class="header">
      <?php if ($system_logo && file_exists($system_logo)): ?>
        <div style="margin-bottom: 1rem;">
          <img src="<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        </div>
      <?php endif; ?>
      <h1>Help & About</h1>
      <p><?= htmlspecialchars($system_name) ?> - <?= htmlspecialchars($system_description) ?></p>
    </div>

    <div class="help-tabs">
      <button class="tab-btn active" onclick="showTab('about')">About System</button>
      <button class="tab-btn" onclick="showTab('features')">Features</button>
      <button class="tab-btn" onclick="showTab('howto')">How It Works</button>
      <button class="tab-btn" onclick="showTab('terms')">Terms & Services</button>
      <button class="tab-btn" onclick="showTab('scope')">System Scope</button>
      <button class="tab-btn" onclick="showTab('developer')">Developer</button>
    </div>
    
    <div class="tab-content">
      <div id="about" class="tab-pane active">
        <h2>About <?= htmlspecialchars($system_name) ?></h2>
        <p><?= htmlspecialchars($system_description) ?></p>
        <p>This is a comprehensive digital attendance management system designed for educational institutions. It provides secure, efficient, and user-friendly attendance tracking through multiple methods including QR codes, manual entry, and facial recognition.</p>
        
        <h3>System Information</h3>
        <p><strong>Version:</strong> 2.0</p>
        <p><strong>Last Updated:</strong> January 2025</p>
        <p><strong>Platform:</strong> Web-based Application</p>
        <p><strong>Compatibility:</strong> All modern browsers and devices</p>
      </div>

      <div id="features" class="tab-pane">
        <h2>System Features</h2>
        <div class="feature-grid">
          <div class="feature-card">
            <h4>🔍 QR Code Attendance</h4>
            <p>Quick and contactless attendance marking using unique QR codes for each student.</p>
          </div>
          <div class="feature-card">
            <h4>✏️ Manual Entry</h4>
            <p>Backup attendance method using Student ID for situations when QR scanning isn't available.</p>
          </div>
          <div class="feature-card">
            <h4>👤 Facial Recognition</h4>
            <p>Advanced biometric attendance system for enhanced security (Optional feature).</p>
          </div>
          <div class="feature-card">
            <h4>📅 Event Management</h4>
            <p>Create and manage institutional events with section and course assignments.</p>
          </div>
          <div class="feature-card">
            <h4>🎓 Student Portal</h4>
            <p>Personal dashboard for students to view events, generate QR codes, and track attendance.</p>
          </div>
          <div class="feature-card">
            <h4>👥 SBO Management</h4>
            <p>Student organization administration with event creation and student data management.</p>
          </div>
          <div class="feature-card">
            <h4>⚙️ Admin Controls</h4>
            <p>Complete system administration including user management and system configuration.</p>
          </div>
          <div class="feature-card">
            <h4>📊 Real-time Reports</h4>
            <p>Live attendance tracking and comprehensive analytics with visual dashboards.</p>
          </div>
          <div class="feature-card">
            <h4>📥 Data Export</h4>
            <p>Download attendance reports in Excel format for external analysis and record keeping.</p>
          </div>
          <div class="feature-card">
            <h4>📱 Mobile Responsive</h4>
            <p>Fully optimized interface that works seamlessly on all devices and screen sizes.</p>
          </div>
        </div>
      </div>

      <div id="howto" class="tab-pane">
        <h2>How the System Works</h2>
        <div class="how-to-steps">
          <div class="step">
            <h4>1. Student Registration</h4>
            <p>Students register with their institutional details including name, student ID, section, and course. Upon registration, they receive unique QR codes for attendance marking.</p>
          </div>
          <div class="step">
            <h4>2. Event Creation</h4>
            <p>SBO officers or administrators create events and assign them to specific sections, courses, or year levels. Events include details like date, time, location, and attendance requirements.</p>
          </div>
          <div class="step">
            <h4>3. Attendance Marking</h4>
            <p>Students mark their attendance using multiple methods: scanning their QR codes, entering their Student ID manually, or using facial recognition (if enabled).</p>
          </div>
          <div class="step">
            <h4>4. Real-time Tracking</h4>
            <p>Attendance is recorded instantly in the database and becomes available for real-time monitoring by authorized personnel through comprehensive dashboards.</p>
          </div>
          <div class="step">
            <h4>5. Reports & Analytics</h4>
            <p>Generate detailed attendance reports, export data in Excel format, and analyze attendance patterns through built-in analytics tools.</p>
          </div>
        </div>
      </div>

      <div id="terms" class="tab-pane">
        <h2>Terms of Service</h2>

        <h3>Data Privacy & Protection</h3>
        <p>All student data is handled in strict accordance with data protection regulations and institutional privacy policies. Personal information collected is used solely for legitimate attendance tracking purposes and is not shared with unauthorized third parties.</p>

        <h3>System Usage Guidelines</h3>
        <p>Users must use the system responsibly and only for legitimate educational and attendance purposes. Any misuse, attempt to manipulate attendance records, or unauthorized access to other users' data is strictly prohibited and may result in account suspension.</p>

        <h3>Account Security & Responsibility</h3>
        <p>Users are responsible for maintaining the security of their login credentials and must not share their accounts with others. Any suspicious activity or unauthorized access should be reported immediately to system administrators.</p>

        <h3>System Availability & Maintenance</h3>
        <p>While we strive for 99% system uptime, the platform may occasionally be unavailable for scheduled maintenance, updates, or unexpected technical issues. Users will be notified in advance of planned maintenance whenever possible.</p>

        <h3>Acceptable Use Policy</h3>
        <p>The system should be used in accordance with institutional policies and applicable laws. Users must not attempt to circumvent security measures, interfere with system operations, or use the platform for any illegal activities.</p>
      </div>

      <div id="scope" class="tab-pane">
        <h2>System Scope</h2>

        <h3>Target Users</h3>
        <div class="feature-grid">
          <div class="feature-card">
            <h4>🎓 Students</h4>
            <p>Mark attendance, view personal records, generate QR codes, and access event information through their personal dashboard.</p>
          </div>
          <div class="feature-card">
            <h4>👥 SBO Officers</h4>
            <p>Manage events, oversee student data, create attendance sessions, and generate comprehensive reports for their organization.</p>
          </div>
          <div class="feature-card">
            <h4>⚙️ Administrators</h4>
            <p>Complete system control including user management, system configuration, data import/export, and overall platform administration.</p>
          </div>
        </div>

        <h3>Supported Institutions</h3>
        <ul>
          <li><strong>Universities and Colleges:</strong> Higher education institutions with multiple departments and courses</li>
          <li><strong>High Schools and Secondary Schools:</strong> Secondary education with grade levels and sections</li>
          <li><strong>Training Centers and Academies:</strong> Professional development and skills training organizations</li>
          <li><strong>Corporate Training Programs:</strong> Business and corporate educational initiatives</li>
          <li><strong>Vocational Schools:</strong> Technical and vocational education institutions</li>
        </ul>

        <h3>Technical Requirements</h3>
        <ul>
          <li><strong>Modern Web Browser:</strong> Chrome 80+, Firefox 75+, Safari 13+, Edge 80+</li>
          <li><strong>Internet Connection:</strong> Stable connection for real-time features and data synchronization</li>
          <li><strong>Camera Access:</strong> Required for QR code scanning and facial recognition features</li>
          <li><strong>Location Services:</strong> Optional for enhanced security and location-based features</li>
          <li><strong>JavaScript Enabled:</strong> Required for interactive features and real-time updates</li>
        </ul>

        <h3>System Limitations</h3>
        <ul>
          <li>Requires internet connectivity for real-time features</li>
          <li>Facial recognition requires adequate lighting conditions</li>
          <li>QR code scanning requires camera access permissions</li>
          <li>Data export features may have file size limitations</li>
        </ul>
      </div>

      <div id="developer" class="tab-pane">
        <h2>About the Developer</h2>

        <div style="display: flex; align-items: center; margin-bottom: 2rem; gap: 2rem; flex-wrap: wrap;">
          <div style="flex-shrink: 0;">
            <img src="assets/images/developer.jpg" alt="Developer Photo"
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #00ffcc; box-shadow: 0 8px 32px rgba(0,255,204,0.3);"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(45deg, #00ffcc, #0099cc); display: none; align-items: center; justify-content: center; font-size: 3rem; color: white; font-weight: bold;">
              👨‍💻
            </div>
          </div>
          <div style="flex: 1; min-width: 300px;">
            <h3 style="margin: 0 0 0.5rem 0; color: #00ffcc; font-size: 1.8rem;">Dee Jay Cristobal</h3>
            <p style="margin: 0 0 1rem 0; font-size: 1.1rem; opacity: 0.9;">Full Stack Developer & System Architect</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
              <span style="background: rgba(0,255,204,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; border: 1px solid rgba(0,255,204,0.3);">PHP Expert</span>
              <span style="background: rgba(0,255,204,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; border: 1px solid rgba(0,255,204,0.3);">JavaScript</span>
              <span style="background: rgba(0,255,204,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; border: 1px solid rgba(0,255,204,0.3);">MySQL</span>
              <span style="background: rgba(0,255,204,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; border: 1px solid rgba(0,255,204,0.3);">UI/UX Design</span>
            </div>
          </div>
        </div>

        <h3>Developer Information</h3>
        <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
          <div class="feature-card">
            <h4>🎓 Education & Background</h4>
            <p><strong>Specialization:</strong> Web Development & Database Systems<br>
            <strong>Experience:</strong> Educational Technology Solutions<br>
            <strong>Focus:</strong> Modern Web Applications</p>
          </div>
          <div class="feature-card">
            <h4>💼 Professional Skills</h4>
            <p><strong>Backend:</strong> PHP, MySQL, API Development<br>
            <strong>Frontend:</strong> HTML5, CSS3, JavaScript ES6+<br>
            <strong>Design:</strong> Responsive UI/UX, Mobile-First</p>
          </div>
          <div class="feature-card">
            <h4>🚀 Project Highlights</h4>
            <p><strong>KUPAL System:</strong> Complete attendance management solution<br>
            <strong>Features:</strong> QR scanning, facial recognition, real-time analytics<br>
            <strong>Architecture:</strong> Scalable, secure, user-friendly</p>
          </div>
          <div class="feature-card">
            <h4>📧 Contact Information</h4>
            <p><strong>Email:</strong> deejay.cristobal@protonmai.com<br>
            <strong>GitHub:</strong> [your-github-username]<br>
            <strong>LinkedIn:</strong> [your-linkedin-profile]</p>
          </div>
        </div>

        <h3>Development Philosophy</h3>
        <p>This system was developed with a focus on educational excellence, user experience, and modern web technologies. The goal is to provide institutions with a reliable, secure, and user-friendly attendance management solution that adapts to the evolving needs of educational environments.</p>

        <h3>Technologies Used</h3>
        <div class="feature-grid">
          <div class="feature-card">
            <h4>🔧 Backend Technologies</h4>
            <p><strong>PHP 8+:</strong> Server-side development<br>
            <strong>MySQL:</strong> Database management<br>
            <strong>PDO:</strong> Secure database connections</p>
          </div>
          <div class="feature-card">
            <h4>🎨 Frontend Technologies</h4>
            <p><strong>HTML5:</strong> Modern markup<br>
            <strong>CSS3:</strong> Advanced styling<br>
            <strong>JavaScript ES6+:</strong> Interactive features</p>
          </div>
          <div class="feature-card">
            <h4>📚 Libraries & Frameworks</h4>
            <p><strong>Bootstrap:</strong> Responsive design<br>
            <strong>QR.js:</strong> QR code generation<br>
            <strong>Face-api.js:</strong> Facial recognition</p>
          </div>
          <div class="feature-card">
            <h4>🔒 Security Features</h4>
            <p><strong>Password Hashing:</strong> Secure authentication<br>
            <strong>SQL Injection Protection:</strong> Prepared statements<br>
            <strong>XSS Prevention:</strong> Input sanitization</p>
          </div>
        </div>

        <h3>Contact & Support</h3>
        <p>For technical support, feature requests, or system administration assistance, please contact your institutional system administrator or IT department.</p>

        <h3>Version History</h3>
        <ul>
          <li><strong>Version 2.0 (January 2025):</strong> Major UI overhaul, enhanced security, mobile optimization</li>
          <li><strong>Version 1.5:</strong> Added facial recognition and real-time analytics</li>
          <li><strong>Version 1.0:</strong> Initial release with QR code and manual attendance</li>
        </ul>

        <h3>Copyright & License</h3>
        <p>&copy; 2025 <?= htmlspecialchars($system_name) ?> System. Developed for Educational Excellence.</p>
        <p>This system is proprietary software designed specifically for educational institutions. All rights reserved.</p>
      </div>
    </div>
  </div>

<script>
  function showTab(tabName) {
    // Hide all tab panes
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabPanes.forEach(pane => pane.classList.remove('active'));
    
    // Remove active class from all tab buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab pane
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
  }
</script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
