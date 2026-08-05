<?php
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

// Fetch total count of available products
$availableProductCount = 0;
try {
    // Adjust 'product' and 'Available' to match your exact database table name and status value case
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM product WHERE status = 'Available'");
    $stmtCount->execute();
    $countRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $availableProductCount = $countRow['total'] ?? 0;
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Fetch total count of OVERDUE payments (less than current date)
$overdueCount = 0;
try {
    $stmtDue = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM payments 
        WHERE due_date < CURRENT_DATE() 
          AND status NOT IN ('fully paid', 'Paid')
    ");
    
    $stmtDue->execute();
    $dueRow = $stmtDue->fetch(PDO::FETCH_ASSOC);
    $overdueCount = $dueRow['total'] ?? 0;
} catch (Exception $e) {
    error_log("Payment Count Error: " . $e->getMessage());
}

// Fetch total count of active penalty waive requests
$requestedWaiveCount = 0;
try {
    $stmtWaiveCount = $pdo->prepare("SELECT COUNT(*) as total FROM payments WHERE request_waive = 'Requested'");
    $stmtWaiveCount->execute();
    $waiveCountRow = $stmtWaiveCount->fetch(PDO::FETCH_ASSOC);
    $requestedWaiveCount = $waiveCountRow['total'] ?? 0;
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Determine active state for parent dropdown containers based on URL pathing
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$servicesActive = (strpos($currentUri, 'avail-services') !== false);

$maintenanceUrls = ['/encoder/product', '/encoder/registration', '/encoder/commission-config', '/encoder/services-control', '/encoder/gl-settings'];
$maintenanceActive = false;
foreach ($maintenanceUrls as $url) {
    if (strpos($currentUri, $url) !== false) {
        $maintenanceActive = true;
        break;
    }
}
$revenueServicesUrls = ['/encoder/internment', '/encoder/inventory', '/encoder/other-services', '/encoder/rental-services', '/encoder/facilities-rental-services'];
$revenueServicesActive = false;
foreach ($revenueServicesUrls as $url) {
    if (strpos($currentUri, $url) !== false) {
        $revenueServicesActive = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cattleya - Encoder Suite</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            /* Cattleya Theme Colors */
            --brand-primary: #2a6279; /* Deep Teal */
            --brand-dark: #1e4a5c;    /* Darker Teal */
            --brand-accent: #9dc44d;  /* Lime Green */
            --brand-light: rgba(255, 255, 255, 0.1);
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 85px;
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7fe;
            margin: 0;
            padding-left: var(--sidebar-width);
            transition: var(--transition-smooth);
        }

        body.content-collapsed {
            padding-left: var(--sidebar-collapsed-width);
        }

        /* --- SIDEBAR CONTAINER --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: linear-gradient(180deg, var(--brand-primary) 0%, var(--brand-dark) 100%);
            display: flex;
            flex-direction: column;
            transition: var(--transition-smooth);
            box-shadow: 12px 0 50px rgba(42, 98, 121, 0.15);
            z-index: 1000;
            will-change: width;
        }

        /* Logo Section */
        .logo-wrapper {
            padding: 2.5rem 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition-smooth);
        }
        .logo-wrapper h2 {
            font-weight: 800;
            color: white;
            letter-spacing: -1.5px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-wrapper h2::before {
            content: '';
            width: 8px; height: 24px;
            background: var(--brand-accent); border-radius: 4px;
            display: inline-block;
            flex-shrink: 0;
        }

        /* Desktop Toggle Trigger Button */
        .desktop-toggle-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .desktop-toggle-btn:hover {
            background: var(--brand-accent);
            color: var(--brand-dark);
        }

        /* --- NAVIGATION --- */
        .sidebar-menu {
            flex-grow: 1;
            padding: 0 1.2rem;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-label {
            color: #9dc44d;
            font-size: 0.60rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1.2px;
            padding: 1.5rem 1.2rem 0.8rem;
            transition: opacity 0.2s ease;
        }

        .nav-link-custom, .dropdown-btn {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: var(--transition-smooth);
            border: none;
            background: transparent;
            width: 100%;
            font-size: 0.80rem;
            white-space: nowrap;
        }

        .nav-link-custom:hover, .dropdown-btn:hover {
            background: var(--brand-light);
            color: white;
        }

        .nav-link-custom.active {
            background: white;
            color: var(--brand-primary);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .nav-link-custom i, .dropdown-btn i {
            font-size: 1.1rem;
            flex-shrink: 0;
            width: 24px; /* Fixes icon width for uniform text alignment */
            text-align: center;
        }

        /* --- ENHANCED DROPDOWN --- */
        .dropdown-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0, 1, 0, 1);
            margin-left: 22px;
            border-left: 2px solid rgba(255, 255, 255, 0.1);
            opacity: 0;
        }

        .dropdown-container.show {
            max-height: 1000px;
            transition: max-height 0.5s ease-in-out;
            opacity: 1;
        }

        .dropdown-container a {
            padding: 8px 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            display: block;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            position: relative;
            white-space: nowrap;
        }

        .dropdown-container a::after {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            width: 12px; height: 2px;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-50%);
        }

        .dropdown-container a:hover, .dropdown-container a.sub-active {
            color: white;
            padding-left: 25px;
        }

        .arrow { 
            transition: transform 0.4s ease; 
            font-size: 0.7rem !important; /* Slightly smaller for the arrow */
            width: auto !important; /* Allow the arrow to size naturally */
        }
        .rotate-arrow { transform: rotate(180deg); }

        /* --- PROFILE CARD --- */
        .sidebar-profile {
            padding: 20px;
            background: rgba(0, 0, 0, 0.05);
            margin: 15px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition-smooth);
            position: relative;
        }

        .user-info-card {
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 12px;
        }

        .user-avatar {
            width: 42px; height: 42px;
            background: white;
            color: var(--brand-primary);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .user-name { color: white; font-size: 0.85rem; margin-bottom: 0; white-space: nowrap; }
        .user-role { color: rgba(255,255,255,0.5); font-size: 0.72rem; white-space: nowrap; }

        /* Popover UI */
        .profile-popover {
            display: none;
            position: absolute;
            bottom: 100px; left: 20px; right: 20px;
            background: white; border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            z-index: 1100;
            animation: cubic-bezier(0.68, -0.55, 0.27, 1.55) fadeInPop 0.4s forwards;
        }

        @keyframes fadeInPop {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .popover-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .popover-item:hover { background: #f8fafc; color: var(--brand-primary); }

        /* --- COLLAPSED STATES PERFORMANCE & DESIGN DESIGNATION --- */
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        /* Added .badge-notification here to hide when collapsed */
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .menu-label,
        .sidebar.collapsed .arrow,
        .sidebar.collapsed .user-details,
        .sidebar.collapsed .user-info-card .bi-three-dots-vertical,
        .sidebar.collapsed .dropdown-container,
        .sidebar.collapsed .badge-notification {
            display: none !important;
        }
        .sidebar.collapsed .logo-wrapper {
            padding: 2.5rem 0.5rem;
            justify-content: center;
        }
        .sidebar.collapsed .desktop-toggle-btn {
            position: absolute;
            right: -14px;
            top: 42px;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            background: var(--brand-primary);
            border: 2px solid white;
            z-index: 1050;
        }
        .sidebar.collapsed .nav-link-custom,
        .sidebar.collapsed .dropdown-btn {
            justify-content: center;
            padding: 12px 0;
        }
        .sidebar.collapsed .nav-link-custom i,
        .sidebar.collapsed .dropdown-btn i {
            margin: 0 !important; /* Force icons to be perfectly centered */
        }
        .sidebar.collapsed .sidebar-profile {
            padding: 10px;
            margin: 10px;
            border-radius: 16px;
        }
        .sidebar.collapsed .user-info-card {
            justify-content: center;
        }
        /* Transforms profile popover to float gracefully when collapsed */
        .sidebar.collapsed .profile-popover {
            left: 75px;
            bottom: 10px;
            right: auto;
            width: 230px;
        }

        /* --- MOBILE STYLING FIXES --- */
        .mobile-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 45px;
            height: 45px;
            background: var(--brand-primary);
            color: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(42, 98, 121, 0.2);
            z-index: 999;
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .mobile-toggle-btn:hover {
            background: var(--brand-dark);
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 995;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
        }
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 992px) {
            body { padding-left: 0 !important; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .mobile-toggle-btn { display: flex; align-items: center; justify-content: center; }
            .desktop-toggle-btn { display: none !important; }
        }
         /* Modern Premium Table Scrollbar */
         ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
<button class="mobile-toggle-btn" id="sidebarToggle">
    <i class="bi bi-grid-fill fs-5"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="mainSidebar">
    <div class="logo-wrapper">
        <h2><span class="sidebar-text">Cattleya</span></h2>
        <button class="desktop-toggle-btn d-none d-lg-flex" id="desktopSidebarToggle">
            <i class="bi bi-chevron-left" id="desktopToggleIcon"></i>
        </button>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Main Menu</div>
        
        <a href="/user/encoder/dashboard" class="nav-link-custom <?= (strpos($currentUri, 'dashboard') !== false) ? 'active' : '' ?>">
            <i class="bi bi-house-door-fill"></i>
            <span class="sidebar-text ms-3">Home</span>
        </a>

        <button class="dropdown-btn" style="<?= $revenueServicesActive ? 'background: var(--brand-light); color: white;' : '' ?>">
            <i class="bi bi-cash-coin"></i> 
            <span class="sidebar-text ms-3">Revenue Sources</span>
            <i class="bi bi-chevron-down arrow ms-auto <?= $revenueServicesActive ? 'rotate-arrow' : '' ?>"></i>
        </button>

        <div class="dropdown-container <?= $revenueServicesActive ? 'show' : '' ?>" id="revenueServicesDropdown">
            <a href="/user/encoder/internment" class="<?= (strpos($currentUri, '/encoder/internment') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Internment Service Revenue</a>
            <a href="/user/encoder/inventory" class="<?= (strpos($currentUri, '/encoder/inventory') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Inventory Revenue</a> 
            <a href="/user/encoder/other-services" class="<?= (strpos($currentUri, '/encoder/other-services') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Other services & Park Rentals</a>
            <a href="/user/encoder/facilities-rental-services" class="<?= (strpos($currentUri, '/encoder/faciities-rental-services') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Facilities Rental & Services</a>
        </div>

        <a href="/user/encoder/payment" class="nav-link-custom <?= (strpos($currentUri, 'payment') !== false) ? 'active' : '' ?>">
            <i class="bi bi-credit-card-fill"></i> 
            <span class="sidebar-text ms-3">Payment</span>
            <?php if ($overdueCount > 0): ?>
                <span class="badge bg-danger rounded-pill badge-notification ms-auto" style="font-size: 0.75rem; padding: 0.35em 0.65em;">
                    <?= $overdueCount ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="/user/encoder/waive-penalty-request" class="nav-link-custom <?= (strpos($currentUri, 'waive-penalty-request') !== false) ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-x"></i>
            <span class="sidebar-text ms-3">Penalty Waive</span>
            <?php if ($requestedWaiveCount > 0): ?>
                <span class="badge bg-danger rounded-pill badge-notification ms-auto" style="font-size: 0.75rem; padding: 0.35em 0.65em;">
                    <?= $requestedWaiveCount ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="/user/encoder/commission-release" class="nav-link-custom <?= (strpos($currentUri, 'commission-release') !== false) ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i> 
            <span class="sidebar-text ms-3">Commission Release</span>
        </a>

        <div class="menu-label">Services</div>

        <button class="dropdown-btn" style="<?= $servicesActive ? 'background: var(--brand-light); color: white;' : '' ?>">
            <i class="bi bi-card-checklist"></i> 
            <span class="sidebar-text ms-3">Services</span>
            <i class="bi bi-chevron-down arrow ms-auto <?= $servicesActive ? 'rotate-arrow' : '' ?>"></i>
        </button>
        <div class="dropdown-container <?= $servicesActive ? 'show' : '' ?>" id="servicesDropdown">
            <a href="/user/encoder/avail-services" class="<?= (strpos($currentUri, 'avail-services') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Avail Services</a> 
        </div>

        <div class="menu-label">Operations</div>

        <button class="dropdown-btn" style="<?= $maintenanceActive ? 'background: var(--brand-light); color: white;' : '' ?>">
            <i class="bi bi-gear-wide-connected"></i> 
            <span class="sidebar-text ms-3">Maintenance</span>
            <i class="bi bi-chevron-down arrow ms-auto <?= $maintenanceActive ? 'rotate-arrow' : '' ?>"></i>
        </button>
        <div class="dropdown-container <?= $maintenanceActive ? 'show' : '' ?>" id="maintenanceDropdown">
            <a href="/user/encoder/product" class="<?= (strpos($currentUri, '/encoder/product') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Register Product</a>
            <a href="/user/encoder/registration" class="<?= (strpos($currentUri, '/encoder/registration') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Register Personnel</a> 
            <a href="/user/encoder/commission-config" class="<?= (strpos($currentUri, '/encoder/commission-config') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Commission Control</a>
            <a href="/user/encoder/services-control" class="<?= (strpos($currentUri, '/encoder/services-control') !== false) ? 'sub-active text-white fw-bold' : '' ?>">Services Control</a>
            <a href="/user/encoder/gl-settings" class="<?= (strpos($currentUri, '/encoder/gl-settings') !== false) ? 'sub-active text-white fw-bold' : '' ?>">GL Settings</a> 
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
                <p class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?= $user_name ?></p>
                <small class="text-muted"><?= $_SESSION['user_email'] ?? 'encoder@cattleya.com' ?></small>
            </div>
            <a href="/views/includes/user/profile" class="popover-item">
                <i class="bi bi-person-gear"></i> Settings
            </a>
            <a href="#" class="popover-item text-danger" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>

        <div class="user-info-card" id="profileToggle">
            <div class="user-avatar"><?= $user_initials ?></div>
            <div class="user-details flex-grow-1">
                <p class="user-name fw-bold"><?= $user_name ?></p>
                <p class="user-role mb-0"><?= ucfirst($_SESSION['role'] ?? 'Encoder') ?></p>
            </div>
            <i class="bi bi-three-dots-vertical text-white-50"></i>
        </div>
    </div>
</div>

<script>
    const sidebar = document.getElementById('mainSidebar');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const desktopToggleIcon = document.getElementById('desktopToggleIcon');

    // --- DESKTOP SIDEBAR COLLAPSE TOGGLE ---
    if(desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('content-collapsed');
            
            if(sidebar.classList.contains('collapsed')) {
                desktopToggleIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
            } else {
                desktopToggleIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }
        });
    }

    // --- DROPDOWN ANIMATION ---
    document.querySelectorAll(".dropdown-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            // Intelligent UX Feature: Auto-expand sidebar if minimized and user clicks menu
            if(sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('content-collapsed');
                if(desktopToggleIcon) desktopToggleIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }

            const menu = this.nextElementSibling;
            const arrow = this.querySelector(".arrow");
            
            menu.classList.toggle("show");
            if(arrow) arrow.classList.toggle("rotate-arrow");

            if (menu.classList.contains("show")) {
                this.style.background = "rgba(255,255,255,0.1)";
                this.style.color = "white";
            } else {
                this.style.background = "transparent";
                this.style.color = "rgba(255, 255, 255, 0.7)";
            }
        });
    });

    // --- PROFILE POPOVER ---
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');

    profileToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = profileMenu.style.display === 'block';
        profileMenu.style.display = isOpen ? 'none' : 'block';
    });

    document.addEventListener('click', () => { profileMenu.style.display = 'none'; });

    // --- MOBILE SIDEBAR ---
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    toggle.addEventListener('click', () => {
        sidebar.classList.add('show');
        overlay.classList.add('active');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
    });

    document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();

    Swal.fire({
        html: `
            <div class="logout-modal-container" style="perspective: 1000px;">
                <div id="iconContainer" class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4" 
                     style="width: 70px; height: 70px; background: linear-gradient(135deg, #2a6279 0%, #448098 100%); 
                            color: white; shadow: 0 10px 20px rgba(42, 98, 121, 0.3); transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                    <i class="bi bi-door-open-fill" style="font-size: 2rem;"></i>
                </div>
                
                <div class="reveal-text">
                    <h3 class="fw-800 text-dark mb-2" style="letter-spacing: -0.03em; opacity: 0; transform: translateY(10px); transition: all 0.4s ease 0.1s;">
                        Confirm Sign Out
                    </h3>
                    <p class="text-muted mb-0 mx-auto" style="max-width: 260px; font-size: 0.95rem; opacity: 0; transform: translateY(10px); transition: all 0.4s ease 0.2s;">
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
            popup: 'rounded-5 border-0 shadow-2xl p-4 overflow-hidden',
            confirmButton: 'btn btn-lg px-5 py-3 fw-bold ms-3 rounded-pill transition-all',
            cancelButton: 'btn btn-lg px-4 py-3 text-muted fw-semibold rounded-pill transition-all'
        },

        didOpen: (modal) => {
            const confirmBtn = Swal.getConfirmButton();
            const cancelBtn = Swal.getCancelButton();
            const icon = document.getElementById('iconContainer');
            const texts = modal.querySelectorAll('.reveal-text > *');

            setTimeout(() => {
                texts.forEach(t => {
                    t.style.opacity = '1';
                    t.style.transform = 'translateY(0)';
                });
            }, 50);

            // Set confirming primary color to Cattleya Teal
            confirmBtn.style.backgroundColor = '#2a6279';
            confirmBtn.style.color = '#fff';
            confirmBtn.style.fontSize = '0.95rem';
            confirmBtn.style.border = 'none';
            
            cancelBtn.style.backgroundColor = '#f1f5f9';
            cancelBtn.style.fontSize = '0.9rem';
            cancelBtn.style.marginRight = '10px';

            confirmBtn.onmouseenter = () => {
                confirmBtn.style.transform = 'scale(1.05) translateY(-2px)';
                confirmBtn.style.backgroundColor = '#1e4a5c';
                confirmBtn.style.boxShadow = '0 10px 20px rgba(42, 98, 121, 0.3)';
                icon.style.transform = 'rotateY(180deg) scale(1.1)'; 
            };

            confirmBtn.onmouseleave = () => {
                confirmBtn.style.transform = 'scale(1) translateY(0)';
                confirmBtn.style.boxShadow = 'none';
                icon.style.transform = 'rotateY(0deg) scale(1)';
            };

            cancelBtn.onmouseenter = () => {
                cancelBtn.style.backgroundColor = '#e2e8f0';
                cancelBtn.style.color = '#1e293b';
            };
        },

        backdrop: `rgba(15, 23, 42, 0.8) blur(12px)`,
        showClass: {
            popup: 'animate__animated animate__zoomIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__zoomOut animate__faster'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const container = document.querySelector('.logout-modal-container');
            container.style.transition = 'all 0.5s ease';
            container.style.opacity = '0';
            container.style.transform = 'scale(0.9)';
            
            Swal.showLoading();
            setTimeout(() => {
                window.location.href = '/cattleya/logout';
            }, 300);
        }
    });
});
</script>

</body>
</html>