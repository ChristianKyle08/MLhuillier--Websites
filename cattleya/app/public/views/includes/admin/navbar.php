<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cattleya Admin Suite</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            /* Modernized Professional Palette with Artistic Accents */
            --sb-bg-color: #ffffff;
            --sb-text-main: #0f172a;        
            --sb-text-muted: #64748b;       
            --sb-accent: #15803d;          
            --sb-accent-light: #f0fdf4;    
            --sb-hover-bg: #f8fafc;        
            --sb-border: #f1f5f9;          
            --sb-danger: #e11d48;          
            
            --sidebar-width: 278px;
            --sidebar-collapsed-width: 84px;
            --transition-smooth: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc; 
            margin: 0;
            padding-left: var(--sidebar-width);
            transition: var(--transition-smooth);
        }

        body.content-collapsed {
            padding-left: var(--sidebar-collapsed-width);
        }

        /* Artistic Glass-Clean Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: var(--sb-bg-color);
            display: flex;
            flex-direction: column;
            transition: var(--transition-smooth);
            border-right: 1px solid var(--sb-border);
            z-index: 1000;
            will-change: width, transform;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.02);
        }

        /* Artistic Branding Section */
        .logo-wrapper {
            padding: 1.75rem 1.5rem 1.25rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition-smooth);
        }
        
        .brand-badge {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .logo-icon-artistic {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(21, 128, 61, 0.22);
            flex-shrink: 0;
            position: relative;
            padding: 8px;
        }

        .logo-icon-artistic svg {
            width: 100%;
            height: 100%;
        }

        .logo-text-group {
            display: flex;
            flex-direction: column;
        }

        .logo-text {
            font-weight: 800;
            color: var(--sb-text-main);
            letter-spacing: -0.03em;
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .logo-eyebrow {
            font-size: 0.7rem;
            color: var(--sb-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        /* Floating Artistic Toggle Button */
        .desktop-toggle-btn {
            position: absolute;
            right: -15px;
            top: 2.2rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: var(--sb-text-muted);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            z-index: 10;
        }

        .desktop-toggle-btn:hover {
            color: var(--sb-accent);
            border-color: var(--sb-accent);
            background: var(--sb-accent-light);
            transform: scale(1.08);
        }

        /* Navigation Links Layout */
        .sidebar-menu {
            flex-grow: 1;
            padding: 0 1.15rem;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-label {
            color: #94a3b8;
            font-size: 0.68rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.1em;
            padding: 1.5rem 0.75rem 0.5rem;
            transition: opacity 0.2s ease;
        }

        .nav-link-custom, .dropdown-btn {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            color: var(--sb-text-muted);
            text-decoration: none;
            font-weight: 500;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: var(--transition-smooth);
            border: none;
            background: transparent;
            width: 100%;
            font-size: 0.875rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .nav-link-custom:hover, .dropdown-btn:hover {
            background: var(--sb-hover-bg);
            color: var(--sb-text-main);
            transform: translateX(3px);
        }

        .nav-link-custom.active {
            background: var(--sb-accent-light);
            color: var(--sb-accent);
            font-weight: 700;
            box-shadow: inset 3px 0 0 var(--sb-accent);
        }

        .nav-link-custom i, .dropdown-btn i {
            font-size: 1.15rem;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
            transition: var(--transition-smooth);
        }

        .nav-link-custom.active i {
            color: var(--sb-accent);
        }

        /* Refined Elegant Submenus */
        .dropdown-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0, 1, 0, 1), opacity 0.3s ease;
            margin-left: 24px;
            padding-left: 12px;
            border-left: 2px solid #f1f5f9;
            opacity: 0;
        }

        .dropdown-container.show {
            max-height: 500px;
            transition: max-height 0.4s ease-in-out, opacity 0.3s ease;
            opacity: 1;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .dropdown-container a {
            padding: 9px 12px 9px 14px;
            color: var(--sb-text-muted);
            font-size: 0.825rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            transition: var(--transition-smooth);
            white-space: nowrap;
            margin-bottom: 3px;
            position: relative;
        }

        .dropdown-container a:hover {
            color: var(--sb-text-main);
            background: var(--sb-hover-bg);
            padding-left: 17px;
        }

        /* Enhanced Active Submenu Item Design */
        .dropdown-container a.active, 
        .dropdown-container a.sub-active {
            background: var(--sb-accent-light) !important;
            color: var(--sb-accent) !important;
            font-weight: 700 !important;
            padding-left: 18px !important;
            box-shadow: 0 3px 10px rgba(21, 128, 61, 0.05);
        }

        /* Polished Glowing Radio/Dot Indicator Style */
        .dropdown-container a.active::before,
        .dropdown-container a.sub-active::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: var(--sb-accent);
            border-radius: 50%;
            margin-right: 10px;
            box-shadow: 0 0 0 4px rgba(21, 128, 61, 0.15);
            flex-shrink: 0;
            transition: var(--transition-smooth);
        }

        .arrow { 
            transition: transform 0.3s ease; 
            font-size: 0.7rem !important;
            width: auto !important;
        }
        .rotate-arrow { transform: rotate(180deg); }

        /* Artistic User Profile Card Area */
        .sidebar-profile {
            padding: 10px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            margin: 1.15rem;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.02);
            transition: var(--transition-smooth);
            position: relative;
        }

        .sidebar-profile:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
        }

        .user-info-card {
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 12px;
        }

        .avatar-container {
            position: relative;
            flex-shrink: 0;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: var(--sb-accent);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2);
        }

        .status-indicator {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 10px;
            height: 10px;
            background-color: #22c55e;
            border: 2px solid #ffffff;
            border-radius: 50%;
        }

        .user-name { color: var(--sb-text-main); font-size: 0.85rem; margin-bottom: 0; white-space: nowrap; font-weight: 700; }
        .user-role { color: var(--sb-text-muted); font-size: 0.72rem; white-space: nowrap; }

        .profile-popover {
            display: none;
            position: absolute;
            bottom: calc(100% + 12px); 
            left: 0; right: 0;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            z-index: 1100;
            border: 1px solid #e2e8f0;
            animation: fadeInPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .profile-popover.show {
            display: block;
        }

        @keyframes fadeInPop {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .popover-item {
            padding: 11px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--sb-text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        .popover-item:hover { background: var(--sb-hover-bg); color: var(--sb-accent); padding-left: 22px; }
        .popover-item.text-danger:hover { color: var(--sb-danger) !important; background: #fff1f2; }

        /* Collapsed Sidebar Modern Adaptation */
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .menu-label,
        .sidebar.collapsed .arrow,
        .sidebar.collapsed .user-details,
        .sidebar.collapsed .status-indicator,
        .sidebar.collapsed .user-info-card .bi-three-dots-vertical,
        .sidebar.collapsed .dropdown-container {
            display: none !important;
        }
        .sidebar.collapsed .logo-wrapper {
            padding: 1.75rem 0.5rem;
            justify-content: center;
        }
        .sidebar.collapsed .nav-link-custom,
        .sidebar.collapsed .dropdown-btn {
            justify-content: center;
            padding: 12px 0;
        }
        .sidebar.collapsed .nav-link-custom i,
        .sidebar.collapsed .dropdown-btn i {
            margin: 0 !important;
            font-size: 1.25rem;
        }
        .sidebar.collapsed .sidebar-profile {
            padding: 8px; margin: 10px 8px; border-radius: 12px;
        }
        .sidebar.collapsed .user-info-card { justify-content: center; }
        .sidebar.collapsed .profile-popover {
            left: 76px; bottom: 0; right: auto; width: 230px;
        }

        /* Mobile Controls */
        .mobile-toggle-btn {
            display: none;
            position: fixed;
            top: 15px; left: 15px;
            width: 44px; height: 44px;
            background: #ffffff;
            color: var(--sb-text-main);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            z-index: 999;
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .mobile-toggle-btn:hover { background: var(--sb-hover-bg); color: var(--sb-accent); }

        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            z-index: 995; opacity: 0; visibility: hidden;
            transition: var(--transition-smooth);
        }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }

        @media (max-width: 992px) {
            body { padding-left: 0 !important; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .mobile-toggle-btn { display: flex; align-items: center; justify-content: center; }
            .desktop-toggle-btn { display: none !important; }
        }

        /* Custom Modern Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .main-content { padding: 2.5rem; }
    </style>
</head>
<body>

    <!-- Inserted Navbar Content Markup -->
    <button class="mobile-toggle-btn" id="sidebarToggle" aria-label="Open menu">
        <i class="bi bi-list fs-5"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="mainSidebar">
        <div class="logo-wrapper">
            <a href="#" class="brand-badge">
                <div class="logo-icon-artistic">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 2C14.2 4.5 14.2 7.8 12 10C9.8 7.8 9.8 4.5 12 2Z" fill="white"/>
                        <path d="M12 22C9.8 19.5 9.8 16.2 12 14C14.2 16.2 14.2 19.5 12 22Z" fill="white"/>
                        <path d="M2 12C4.5 9.8 7.8 9.8 10 12C7.8 14.2 4.5 14.2 2 12Z" fill="white" fill-opacity="0.8"/>
                        <path d="M22 12C19.5 14.2 16.2 14.2 14 12C16.2 9.8 19.5 9.8 22 12Z" fill="white" fill-opacity="0.8"/>
                        <circle cx="12" cy="12" r="2.3" fill="white"/>
                    </svg>
                </div>
                <div class="logo-text-group sidebar-text">
                    <h2 class="mb-0"><span class="logo-text">Cattleya</span></h2>
                    <span class="logo-eyebrow">Admin Suite</span>
                </div>
            </a>
            <button class="desktop-toggle-btn d-none d-lg-flex" id="desktopSidebarToggle" aria-label="Collapse sidebar">
                <i class="bi bi-chevron-left" id="desktopToggleIcon"></i>
            </button>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-label">Main Menu</div>
            
            <a href="/admin/dashboard" class="nav-link-custom <?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'dashboard') !== false) ? 'active' : '' ?>">
               <i class="bi bi-house-door"></i>
            <span class="sidebar-text ms-3">Dashboard</span>
            </a>

            <div class="menu-label">Operations</div>

            <?php 
                $isMaintenanceActive = (strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'maintenance') !== false);
            ?>

            <button class="dropdown-btn" data-dropdown="maintenanceDropdown">
                 <i class="bi bi-gear"></i> 
            <span class="sidebar-text ms-3">Maintenance</span>
            <i class="bi bi-chevron-down arrow <?= $isMaintenanceActive ? 'rotate-arrow' : '' ?> ms-auto"></i>
            </button>
            <div class="dropdown-container <?= $isMaintenanceActive ? 'show' : '' ?>" id="maintenanceDropdown">
                <a href="/admin/users" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false) ? 'sub-active active' : '' ?>">Manage Users</a>
            </div>
        </div>

        <?php 
            $user_name = $_SESSION['user_name'] ?? 'Encoder User';
            $words = explode(" ", $user_name);
            $user_initials = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
        ?>

        <div class="sidebar-profile">
            <div class="profile-popover" id="profileMenu">
                <div class="p-3 bg-light border-bottom">
                    <p class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($user_name) ?></p>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['user_email'] ?? 'encoder@cattleya.com') ?></small>
                </div>
                <a href="/views/includes/admin/profile" class="popover-item">
                    <i class="bi bi-gear-wide-connected"></i> Settings
                </a>
                <a href="#" class="popover-item text-danger" id="logoutBtn">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </div>

            <div class="user-info-card" id="profileToggle">
                <div class="avatar-container">
                    <div class="user-avatar"><?= $user_initials ?></div>
                    <div class="status-indicator"></div>
                </div>
                <div class="user-details flex-grow-1 min-width-0">
                    <p class="user-name fw-bold text-truncate"><?= htmlspecialchars($user_name) ?></p>
                    <p class="user-role mb-0 text-truncate"><?= ucfirst($_SESSION['role'] ?? 'Encoder') ?></p>
                </div>
                <i class="bi bi-three-dots-vertical text-muted ms-auto"></i>
            </div>
        </div>
    </div>

    <!-- JavaScript Controller Logic -->
    <script>
        // Desktop Collapse Controls
        const mainSidebar = document.getElementById('mainSidebar');
        const body = document.body;
        const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
        const desktopToggleIcon = document.getElementById('desktopToggleIcon');

        desktopSidebarToggle.addEventListener('click', () => {
            mainSidebar.classList.toggle('collapsed');
            body.classList.toggle('content-collapsed');
            
            if (mainSidebar.classList.contains('collapsed')) {
                desktopToggleIcon.classList.remove('bi-chevron-left');
                desktopToggleIcon.classList.add('bi-chevron-right');
            } else {
                desktopToggleIcon.classList.remove('bi-chevron-right');
                desktopToggleIcon.classList.add('bi-chevron-left');
            }
        });

        // Mobile View Controllers
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            mainSidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            mainSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('active');
        });

        // Dropdown Accordion Toggle Logic
        const dropdownBtns = document.querySelectorAll('.dropdown-btn');
        dropdownBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-dropdown');
                const dropdownContent = document.getElementById(targetId);
                const arrowIcon = btn.querySelector('.arrow');

                dropdownContent.classList.toggle('show');
                arrowIcon.classList.toggle('rotate-arrow');
            });
        });

        // Profile Popover Card Controller
        const profileToggle = document.getElementById('profileToggle');
        const profileMenu = document.getElementById('profileMenu');

        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });

        window.addEventListener('click', () => {
            if (profileMenu.classList.contains('show')) {
                profileMenu.classList.remove('show');
            }
        });

        // Logout Modal
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();

        Swal.fire({
            html: `
                <div class="logout-modal-container text-center">
                    <div id="iconContainer" class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                         style="width: 60px; height: 60px; background: #fee2e2; color: #e11d48;">
                        <i class="bi bi-box-arrow-right" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-2">Sign Out</h4>
                        <p class="text-muted mb-0 mx-auto" style="max-width: 260px; font-size: 0.9rem;">
                            Are you sure you want to end your current session?
                        </p>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sign Out',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-4 border-0 shadow-lg p-4',
                confirmButton: 'btn btn-danger px-4 py-2 fw-semibold ms-2 rounded-3',
                cancelButton: 'btn btn-light px-4 py-2 text-dark fw-semibold rounded-3 border'
            },
            backdrop: `rgba(15, 23, 42, 0.4) blur(4px)`
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                setTimeout(() => {
                    window.location.href = '/cattleya/logout';
                }, 250);
            }
        });
    });
    </script>
</body>
</html>