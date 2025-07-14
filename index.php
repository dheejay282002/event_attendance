<?php
// Include system configuration
include 'db_connect.php';
include 'includes/system_config.php';

// Set Manila timezone
date_default_timezone_set('Asia/Manila');

// Get system settings
$system_name = getSystemName($conn);
$system_logo = getSystemLogo($conn);
$system_description = getSystemDescription($conn);
$serverTime = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($system_name) ?> | <?= htmlspecialchars($system_description) ?></title>
  <?= generateFaviconTags($conn) ?>
  <link rel="stylesheet" href="assets/css/adlor-professional.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');

    :root {
      --primary-color: #7c3aed;
      --primary-dark: #5b21b6;
      --primary-hover: #8b5cf6;
      --gray-900: #111827;
      --gray-600: #4b5563;
    }

    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Inter', sans-serif;
      overflow: hidden;
    }

    @media (max-width: 768px) {
      .main {
        height: auto;
        min-height: 100vh;
      }

      .screen {
        height: auto;
        min-height: 100vh;
      }

      .home-screen {
        height: auto !important;
        min-height: 100vh;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
      }

      .clock-screen {
        height: 100vh;
        overflow: hidden;
      }

      /* When homepage is active, allow body scroll */
      body.homepage-active {
        overflow: auto !important;
      }

      body.homepage-active .main {
        overflow: visible !important;
      }
    }

    .main {
      width: 200%;
      height: 100vh;
      display: flex;
      transition: transform 0.6s ease;
    }

    .screen {
      width: 100vw;
      height: 100vh;
    }

    /* Clock View */
    .clock-screen {
      background-color: #000;
      color: #00ffcc;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      font-family: 'Courier New', monospace;
    }

    .clock {
      font-size: 64px;
      font-weight: 900;
      font-family: 'Orbitron', 'Courier New', monospace;
      color: #00ffcc;
      text-shadow:
        0 0 2px #00ffcc,
        0 0 4px #00ffcc,
        0 0 8px rgba(0, 255, 204, 0.5);
      letter-spacing: 3px;
      animation: glitch-flicker 4s infinite;
    }

    @keyframes glitch-flicker {
      0%, 97%, 100% {
        color: #00ffcc;
        text-shadow:
          0 0 2px #00ffcc,
          0 0 4px #00ffcc,
          0 0 8px rgba(0, 255, 204, 0.5);
      }
      98% {
        color: #ffffff;
        text-shadow:
          -1px 0 #ff0040,
          1px 0 #00ff40,
          0 0 2px #00ffcc,
          0 0 4px #00ffcc;
      }
      99% {
        color: #00ffcc;
        text-shadow:
          0 0 2px #00ffcc,
          0 0 4px #00ffcc,
          0 0 8px rgba(0, 255, 204, 0.5);
      }
    }

    .neon-logo {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      position: relative;
      border: 2px solid rgba(255,255,255,0.2);
      box-shadow: 0 0 10px rgba(0, 255, 204, 0.3);
    }

    .neon-logo::before {
      content: '';
      position: absolute;
      top: -15px;
      left: 55px;
      width: 15px;
      height: 15px;
      background: #00ffcc;
      border-radius: 50%;
      box-shadow:
        0 0 20px #00ffcc,
        0 0 40px #00ffcc,
        0 0 60px #00ffcc,
        inset 0 0 10px #ffffff;
      animation: snake-orbit 3s linear infinite;
      transform-origin: 7.5px 75px;
      z-index: 10;
    }

    .neon-logo::after {
      content: '';
      position: absolute;
      top: -10px;
      left: 57px;
      width: 10px;
      height: 10px;
      background: #ff0040;
      border-radius: 50%;
      box-shadow:
        0 0 15px #ff0040,
        0 0 30px #ff0040,
        0 0 45px #ff0040,
        inset 0 0 5px #ffffff;
      animation: snake-orbit-reverse 2s linear infinite;
      transform-origin: 5px 70px;
      z-index: 10;
    }

    @keyframes snake-orbit {
      0% {
        transform: rotate(0deg);
        box-shadow:
          0 0 10px #00ffcc,
          0 0 20px #00ffcc,
          0 0 30px #00ffcc;
      }
      50% {
        box-shadow:
          0 0 15px #00ffcc,
          0 0 30px #00ffcc,
          0 0 45px #00ffcc;
      }
      100% {
        transform: rotate(360deg);
        box-shadow:
          0 0 10px #00ffcc,
          0 0 20px #00ffcc,
          0 0 30px #00ffcc;
      }
    }

    @keyframes snake-orbit-reverse {
      0% {
        transform: rotate(360deg);
      }
      100% {
        transform: rotate(0deg);
      }
    }



    .date {
      font-size: 20px;
      color: #ccc;
      margin-top: 10px;
    }

    .location {
      font-size: 16px;
      color: #00ff88;
      margin-top: 8px;
      font-weight: 500;
    }

    .label {
      font-size: 14px;
      color: #777;
      margin-top: 5px;
    }

    /* Original Homepage Design */
    .home-screen {
      min-height: 100vh;
      background: linear-gradient(0deg, var(--primary-color) 0%, var(--primary-dark) 10%,rgb(2, 17, 46) 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }

    @media (max-width: 768px) {
      .home-screen {
        justify-content: flex-start;
        padding: 1rem;
        padding-top: 2rem;
      }
    }



    .hero-section {
      text-align: center;
      color: white;
      z-index: 1;
      max-width: 800px;
      margin-bottom: 3rem;
    }

    .hero-logo {
      font-size: 4rem;
      font-weight: 800;
      margin-bottom: 1rem;
      text-shadow: 0 4px 8px rgba(0,0,0,0.3);
      background: linear-gradient(45deg, #ffffff, #e0f2fe);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-tagline {
      font-size: 1.5rem;
      font-weight: 500;
      margin-bottom: 1rem;
      opacity: 0.95;
    }

    .hero-description {
      font-size: 1.125rem;
      opacity: 0.85;
      line-height: 1.6;
      max-width: 600px;
      margin: 0 auto 2rem;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2rem;
      max-width: 700px;
      width: 100%;
      z-index: 1;
      margin-bottom: 3rem;
    }

    .feature-card {
      background: rgba(214, 208, 208, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      transition: left 0.5s ease;
    }

    .feature-card:hover::before {
      left: 100%;
    }

    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 16px 48px rgba(0,0,0,0.15);
    }

    .feature-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      display: block;
    }

    .feature-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
    }

    .feature-description {
      color: var(--gray-600);
      font-size: 0.875rem;
      line-height: 1.5;
      margin-bottom: 1rem;
    }

    .feature-action {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--primary-color);
      font-weight: 500;
      font-size: 0.875rem;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .feature-action:hover {
      color: var(--primary-hover);
    }

    .footer-section {
      text-align: center;
      color: rgba(255, 255, 255, 0.9);
      z-index: 1;
      max-width: 800px;
      width: 100%;
    }

    .footer-links {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 2rem;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
      gap: 3rem;
    }

    .footer-link {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 1rem;
      padding: 1rem 1.5rem;
      color: white;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      min-height: 60px;
    }

    .footer-link:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.4);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .footer-link::before {
      font-size: 1.25rem;
    }

    .footer-link.scanner::before {
      content: '📱';
    }

    .footer-link.admin::before {
      content: '⚙️';
    }

    .help-link {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      color: white;
      text-decoration: none;
      font-size: 1.2rem;
      font-weight: bold;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      flex-shrink: 0;
    }

    .help-link:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.4);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      color: white;
      text-decoration: none;
    }

    .btn {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background: var(--primary-color);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      color: white;
      text-decoration: none;
    }

    @media (max-width: 768px) {
      .clock {
        font-size: 48px;
      }

      .date {
        font-size: 16px;
      }

      .hero-logo {
        font-size: 3rem;
      }

      .hero-tagline {
        font-size: 1.25rem;
      }

      .features-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .feature-card {
        padding: 1.5rem;
      }

      .footer-links {
        flex-direction: column;
        gap: 1rem;
        max-width: 300px;
      }

      .footer-link {
        padding: 0.875rem 1rem;
        min-height: 50px;
        font-size: 0.8rem;
      }

      .modal-content {
        width: 95%;
        margin: 5% auto;
      }

      .modal-header {
        padding: 1rem;
      }

      .modal-header h2 {
        font-size: 1.25rem;
      }

      .help-tabs {
        flex-wrap: wrap;
      }

      .tab-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }

      .tab-content {
        padding: 1rem;
      }

      .tab-pane h3 {
        font-size: 1.25rem;
      }
    }
  </style>
</head>
<body onclick="toggleView()">

<div class="main" id="mainScreen">
  <!-- Clock Screen -->
  <div class="screen clock-screen">
    <?php if ($system_logo && file_exists($system_logo)): ?>
        <div style="margin-bottom: 1rem;">
          <img src="<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" class="neon-logo">
        </div>
      <?php endif; ?>
    <div class="clock" id="clock">--:--:--</div>
    <div class="date" id="date">Loading...</div>
    <div class="location" id="location">📍 Detecting location...</div>
  </div>

  <!-- Homepage/Login Screen -->
  <div class="screen home-screen">
    <div class="hero-section">
      <?php if ($system_logo && file_exists($system_logo)): ?>
        <div style="margin-bottom: 1rem;">
          <img src="<?= htmlspecialchars($system_logo) ?>" alt="<?= htmlspecialchars($system_name) ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        </div>
      <?php endif; ?>
      <div class="hero-logo"><?= htmlspecialchars($system_name) ?></div>
      <div class="hero-tagline"><?= htmlspecialchars($system_description) ?></div>
      <div class="hero-description">
        Streamlined attendance management for educational institutions.
        Secure, efficient, and user-friendly digital attendance tracking system.
      </div>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <span class="feature-icon">👨‍🎓</span>
        <div class="feature-title">Student Portal</div>
        <div class="feature-description">
          Access your personal dashboard, view upcoming events, and generate QR codes for attendance.
        </div>
        <div style="margin-top: 1rem;">
          <a href="student_login.php" class="btn btn-primary" style="width: 100%; text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.875rem; display: block;">
            🔑 Login
          </a>
        </div>
      </div>

      <div class="feature-card" onclick="location.href='sbo/login.php'">
        <span class="feature-icon">👥</span>
        <div class="feature-title">SBO Panel</div>
        <div class="feature-description">
          Create and manage events, assign sections, and organize institutional activities.
        </div>
        <div class="feature-action">
          SBO Login →
        </div>
      </div>
    </div>

    <div class="footer-section">
      <div class="footer-links">
        <a href="scan_qr.php" class="footer-link scanner">
          Scanner
          <span style="font-size: 0.75rem; opacity: 0.8; display: block;"></span>
        </a>
        <a href="help.php" class="footer-link help-link">
          <span style="font-size: 1.5rem;">?</span>
        </a>
        <a href="admin/login.php" class="footer-link admin">
          Admin
          <span style="font-size: 0.75rem; opacity: 0.8; display: block;"></span>
        </a>
      </div>
      <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 1rem; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
        <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 500; color: white;">
          © 2025 <?= htmlspecialchars($system_name) ?> System
        </p>
        <p style="margin: 0; font-size: 0.75rem; color: white; opacity: 0.9;">
          Developed for Educational Excellence
        </p>
      </div>
    </div>
  </div>
</div>



<script>
  let serverTime = new Date("<?php echo $serverTime; ?>");
  let toggled = false;

  function updateClock() {
    serverTime.setSeconds(serverTime.getSeconds() + 1);

    const timeString = serverTime.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });

    const dateString = serverTime.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

    document.getElementById('clock').textContent = timeString;
    document.getElementById('date').textContent = dateString;
  }

  function getLocation() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function(position) {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;

          // Try to get location name using a free geocoding service
          fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=en`)
            .then(response => response.json())
            .then(data => {
              let locationText = '';
              if (data.city) {
                locationText = `${data.city}, ${data.countryName}`;
              } else if (data.locality) {
                locationText = `${data.locality}, ${data.countryName}`;
              } else if (data.countryName) {
                locationText = data.countryName;
              } else {
                locationText = `${lat.toFixed(2)}, ${lon.toFixed(2)}`;
              }
              // Remove "(the)" from location text
              locationText = locationText.replace(/\s*\(the\)\s*/gi, '');
              document.getElementById('location').textContent = locationText;
            })
            .catch(error => {
              // Fallback to coordinates if geocoding fails
              document.getElementById('location').textContent = `${lat.toFixed(2)}°, ${lon.toFixed(2)}°`;
            });
        },
        function(error) {
          // Handle location access denied or error
          switch(error.code) {
            case error.PERMISSION_DENIED:
              document.getElementById('location').textContent = 'Location access denied';
              break;
            case error.POSITION_UNAVAILABLE:
              document.getElementById('location').textContent = 'Location unavailable';
              break;
            case error.TIMEOUT:
              document.getElementById('location').textContent = 'Location timeout';
              break;
            default:
              document.getElementById('location').textContent = 'Manila, Philippines';
              break;
          }
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000 // 5 minutes
        }
      );
    } else {
      document.getElementById('location').textContent = 'Geolocation not supported';
    }
  }

  setInterval(updateClock, 1000);
  updateClock();
  getLocation(); // Get location on page load

  function toggleView() {
    const container = document.getElementById('mainScreen');
    if (!toggled) {
      container.style.transform = 'translateX(-50%)';
      // Enable scrolling on mobile when homepage is active
      if (window.innerWidth <= 768) {
        document.body.classList.add('homepage-active');
        document.body.style.overflow = 'auto';
      }
    } else {
      container.style.transform = 'translateX(0)';
      // Disable scrolling when clock is active
      document.body.classList.remove('homepage-active');
      document.body.style.overflow = 'hidden';
    }
    toggled = !toggled;
  }


</script>

<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
