<?php
// Navigation component for dynamic system
// Usage: include 'includes/navigation.php'; then call renderNavigation($user_type, $current_page);

// Include system configuration
require_once __DIR__ . '/system_config.php';

function renderNavigation($user_type = 'guest', $current_page = '', $user_name = '') {
    global $conn;

    // Get system name dynamically
    $system_name = getSystemName($conn);
    $nav_items = [];
    
    switch ($user_type) {
        case 'student':
            $nav_items = [
                'dashboard' => ['url' => 'student_dashboard.php', 'label' => '📊 Dashboard', 'icon' => '📊'],
                'qr_codes' => ['url' => 'student_qr_codes.php', 'label' => '📱 My QR Codes', 'icon' => '📱'],
                'scanner' => ['url' => 'student_event_scanner.php', 'label' => '📷 Event Scanner', 'icon' => '📷'],
                'attendance' => ['url' => 'student_attendance.php', 'label' => '📋 My Attendance', 'icon' => '📋'],
                'profile' => ['url' => 'student_profile.php', 'label' => '👤 Profile', 'icon' => '👤'],
                'settings' => ['url' => 'student_settings.php', 'label' => '⚙️ Settings', 'icon' => '⚙️']
            ];
            break;
            
        case 'sbo':
            // Determine if we're already in the SBO directory
            $current_path = $_SERVER['REQUEST_URI'];
            $sbo_prefix = (strpos($current_path, '/sbo/') !== false) ? '' : 'sbo/';
            $root_prefix = (strpos($current_path, '/sbo/') !== false) ? '../' : '';

            $nav_items = [
                'dashboard' => ['url' => $sbo_prefix . 'dashboard.php', 'label' => '📊 Dashboard', 'icon' => '📊'],
                'events' => ['url' => $sbo_prefix . 'manage_events.php', 'label' => '📅 Manage Events', 'icon' => '📅'],
                'students' => ['url' => $sbo_prefix . 'manage_students.php', 'label' => '👥 Manage Students', 'icon' => '👥'],
                'qr_codes' => ['url' => $sbo_prefix . 'event_qr_codes.php', 'label' => '📱 Event QR Codes', 'icon' => '📱'],
                'attendance' => ['url' => $sbo_prefix . 'view_attendance.php', 'label' => '📋 View Attendance', 'icon' => '📋'],
                'reports' => ['url' => $sbo_prefix . 'download_attendance.php', 'label' => '📈 Reports', 'icon' => '📈'],
                'scanner' => ['url' => $root_prefix . 'scan_qr.php', 'label' => '📱 QR Scanner', 'icon' => '📱'],
                'scanner_settings' => ['url' => $sbo_prefix . 'scanner_settings.php', 'label' => '⚙️ Scanner Settings', 'icon' => '⚙️'],
                'profile' => ['url' => $sbo_prefix . 'profile.php', 'label' => '👤 Profile', 'icon' => '👤'],
                'settings' => ['url' => $sbo_prefix . 'settings.php', 'label' => '⚙️ Settings', 'icon' => '⚙️']
            ];
            break;
            
        case 'admin':
            // Determine if we're already in the admin directory
            $current_path = $_SERVER['REQUEST_URI'];
            $admin_prefix = (strpos($current_path, '/admin/') !== false) ? '' : 'admin/';
            $root_prefix = (strpos($current_path, '/admin/') !== false) ? '../' : '';

            $nav_items = [
                'dashboard' => ['url' => $admin_prefix . 'dashboard.php', 'label' => '📊 Dashboard', 'icon' => '📊'],
                'academics' => ['url' => $admin_prefix . 'manage_academics.php', 'label' => '🎓 Manage Academics', 'icon' => '🎓'],
                'events' => ['url' => $admin_prefix . 'manage_events.php', 'label' => '📅 Manage Events', 'icon' => '📅'],
                'data' => ['url' => $admin_prefix . 'data_management.php', 'label' => '📊 Data Management', 'icon' => '📊'],
                'students' => ['url' => $admin_prefix . 'manage_students.php', 'label' => '👥 Manage Students', 'icon' => '👥'],
                'sbo' => ['url' => $admin_prefix . 'manage_sbo.php', 'label' => '👥 Manage SBO', 'icon' => '👥'],
                'attendance' => ['url' => $admin_prefix . 'view_attendance.php', 'label' => '📋 View Attendance', 'icon' => '📋'],
                'reports' => ['url' => $admin_prefix . 'download_attendance.php', 'label' => '📈 Download Reports', 'icon' => '📈'],
                'scanner' => ['url' => $root_prefix . 'scan_qr.php', 'label' => '📱 QR Scanner', 'icon' => '📱'],
                'scanner_settings' => ['url' => $admin_prefix . 'scanner_settings.php', 'label' => '⚙️ Scanner Settings', 'icon' => '⚙️'],
                'database' => ['url' => $root_prefix . 'database_admin.php', 'label' => '🗄️ Database', 'icon' => '🗄️'],
                'system' => ['url' => $admin_prefix . 'system_settings.php', 'label' => '⚙️ System Settings', 'icon' => '⚙️'],
                'profile' => ['url' => $admin_prefix . 'profile.php', 'label' => '👤 Profile', 'icon' => '👤'],
                'settings' => ['url' => $admin_prefix . 'settings.php', 'label' => '🔧 Admin Settings', 'icon' => '🔧']
            ];
            break;
            
        case 'scanner':
            $nav_items = [
                'scan' => ['url' => 'scan_qr.php', 'label' => '📱 QR Scanner', 'icon' => '📱'],
                'recent' => ['url' => 'scan_recent.php', 'label' => '📋 Recent Scans', 'icon' => '📋']
            ];
            break;
    }
    
    ?>
    <nav class="navbar">
        <div class="container d-flex justify-between items-center">
            <!-- Brand -->
            <div class="d-flex items-center gap-3">
                <?php
                // Determine dashboard URL based on user type
                $dashboard_url = 'index.php'; // Default for guests and scanners

                if ($user_type === 'sbo') {
                    $current_path = $_SERVER['REQUEST_URI'];
                    $sbo_prefix = (strpos($current_path, '/sbo/') !== false) ? '' : 'sbo/';
                    $dashboard_url = $sbo_prefix . 'dashboard.php';
                } elseif ($user_type === 'student') {
                    $dashboard_url = 'student_dashboard.php';
                } elseif ($user_type === 'admin') {
                    $current_path = $_SERVER['REQUEST_URI'];
                    $admin_prefix = (strpos($current_path, '/admin/') !== false) ? '' : 'admin/';
                    $dashboard_url = $admin_prefix . 'dashboard.php';
                }
                ?>
                <a href="<?= $dashboard_url ?>" class="navbar-brand"><?= htmlspecialchars($system_name) ?></a>
                <?php if ($user_name): ?>
                    <span style="color: var(--gray-600); font-size: 0.875rem;">
                        Welcome, <?= htmlspecialchars($user_name) ?>
                    </span>
                <?php endif; ?>
            </div>
            


            <!-- Navigation Items -->
            <div class="navbar-nav d-none d-md-flex">
                <?php if ($user_type !== 'guest'): ?>
                    <!-- Main Menu Dropdown -->
                    <div class="dropdown">
                        <button class="dropdown-toggle nav-link" onclick="toggleDropdown(event)">
                            <?= $user_type === 'scanner' ? '📱 Scanner' : '📋 Menu' ?> <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="dropdown-menu">
                            <?php foreach ($nav_items as $key => $item): ?>
                                <a href="<?= $item['url'] ?>"
                                   class="dropdown-item <?= $current_page === $key ? 'active' : '' ?>">
                                    <?= $item['label'] ?>
                                </a>
                            <?php endforeach; ?>

                            <?php if ($user_type === 'scanner'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="index.php" class="dropdown-item">
                                    🏠 Home
                                </a>
                            <?php endif; ?>

                            <?php if ($user_type !== 'scanner'): ?>
                                <div class="dropdown-divider"></div>

                                <!-- Logout -->
                                <?php
                                // Determine correct logout path based on current location and user type
                                if ($user_type === 'sbo') {
                                    $current_path = $_SERVER['REQUEST_URI'];
                                    $logout_url = (strpos($current_path, '/sbo/') !== false) ? 'logout.php' : 'sbo/logout.php';
                                } elseif ($user_type === 'admin') {
                                    $current_path = $_SERVER['REQUEST_URI'];
                                    $logout_url = (strpos($current_path, '/admin/') !== false) ? 'logout.php' : 'admin/logout.php';
                                } else {
                                    $logout_url = 'logout.php';
                                }
                                ?>
                                <a href="<?= $logout_url ?>" class="dropdown-item logout-item">
                                    🚪 Logout
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn d-md-none" onclick="toggleMobileMenu()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                ☰
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu d-md-none" style="display: none;">
            <div class="container">
                <?php foreach ($nav_items as $key => $item): ?>
                    <a href="<?= $item['url'] ?>"
                       class="mobile-nav-link <?= $current_page === $key ? 'active' : '' ?>">
                        <?= $item['label'] ?>
                    </a>
                <?php endforeach; ?>
                
                <?php if ($user_type !== 'guest' && $user_type !== 'scanner'): ?>
                    <?php
                    // Use the same logout URL logic as desktop menu
                    if ($user_type === 'sbo') {
                        $current_path = $_SERVER['REQUEST_URI'];
                        $logout_url = (strpos($current_path, '/sbo/') !== false) ? 'logout.php' : 'sbo/logout.php';
                    } elseif ($user_type === 'admin') {
                        $current_path = $_SERVER['REQUEST_URI'];
                        $logout_url = (strpos($current_path, '/admin/') !== false) ? 'logout.php' : 'admin/logout.php';
                    } else {
                        $logout_url = 'logout.php';
                    }
                    ?>
                    <a href="<?= $logout_url ?>" class="mobile-nav-link" style="color: var(--error-color);">
                        🚪 Logout
                    </a>
                <?php endif; ?>
                
                <?php if ($user_type === 'scanner'): ?>
                    <a href="index.php" class="mobile-nav-link">
                        🏠 Home
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <style>
        /* Remove all animations from navigation elements */
        .navbar, .navbar *, .dropdown, .dropdown *, .mobile-menu, .mobile-menu * {
            animation: none !important;
            transform: none !important;
            transition: color 0.2s ease, background 0.2s ease, opacity 0.2s ease !important;
        }

        .navbar {
            animation: none !important;
        }

        .navbar-brand {
            animation: none !important;
            transform: none !important;
        }

        .nav-link {
            animation: none !important;
            transform: none !important;
        }

        .dropdown-toggle {
            animation: none !important;
            transform: none !important;
        }

        .dropdown-toggle:hover {
            transform: none !important;
        }

        /* Prevent any floating, bouncing, or movement animations */
        .navbar-nav .nav-item,
        .navbar-nav .nav-link,
        .dropdown-item,
        .navbar-brand,
        .navbar-toggler {
            animation: none !important;
            transform: none !important;
        }

        /* Remove hover animations */
        .nav-link:hover,
        .dropdown-item:hover,
        .navbar-brand:hover {
            transform: none !important;
            animation: none !important;
        }

        .mobile-menu {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 1rem 0;
            animation: none !important;
        }
        
        .mobile-nav-link {
            display: block;
            padding: 0.75rem 0;
            color: var(--gray-600);
            text-decoration: none;
            border-bottom: 1px solid var(--gray-100);
            transition: color 0.2s ease;
        }
        
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            color: var(--primary-color);
            background: var(--primary-light);
            padding-left: 1rem;
        }
        
        .mobile-nav-link:last-child {
            border-bottom: none;
        }


        
        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            background: none;
            border: none;
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-toggle:hover {
            background: var(--gray-100);
            color: var(--primary-color);
        }

        .dropdown-arrow {
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }

        .dropdown.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-width: 220px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            padding: 0.5rem 0;
        }

        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: var(--gray-50);
            color: var(--primary-color);
        }

        .dropdown-item.active {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-200);
            margin: 0.5rem 0;
        }

        .dropdown-submenu-header {
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            color: var(--gray-800);
            background: var(--gray-50);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .submenu-item {
            padding-left: 2rem !important;
            font-size: 0.9rem;
        }

        .submenu-item:before {
            content: "→";
            margin-right: 0.5rem;
            color: var(--gray-400);
        }

        .logout-item {
            color: var(--error-color) !important;
        }

        .logout-item:hover {
            background: #fef2f2;
            color: #dc2626 !important;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                display: none !important;
            }

            .dropdown-menu {
                position: fixed;
                top: 60px;
                left: 1rem;
                right: 1rem;
                width: auto;
            }
        }
    </style>
    
    <script>


        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        function toggleDropdown(event) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = event.target.closest('.dropdown');
            const isActive = dropdown.classList.contains('active');

            // Close all other dropdowns
            document.querySelectorAll('.dropdown.active').forEach(d => {
                d.classList.remove('active');
            });

            // Toggle current dropdown
            if (!isActive) {
                dropdown.classList.add('active');
            }
        }
        
        // Close mobile menu and dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // Close mobile menu
            const menu = document.getElementById('mobile-menu');
            const button = document.querySelector('.mobile-menu-btn');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.style.display = 'none';
            }

            // Close dropdowns
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    </script>
    <?php
}
?>
