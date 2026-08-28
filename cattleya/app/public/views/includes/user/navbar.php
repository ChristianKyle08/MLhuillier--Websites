<?php
ob_start();

require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    if (!headers_sent()) {
        header("Location: /cattleya/login");
    } else {
        echo "<script>window.location.href='/cattleya/login';</script>";
    }
    exit;
}

// Fetch user role and features JSON column from database
$userRole = $_SESSION['role'] ?? 'encoder';
$userFeatures = [];

try {
    $stmtUser = $pdo->prepare("SELECT role, features FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $uRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($uRow) {
        $userRole = $uRow['role'] ?? $userRole;
        $_SESSION['role'] = $userRole;
        if (!empty($uRow['features'])) {
            $rawFeatures = $uRow['features'];
            if (is_array($rawFeatures)) {
                $userFeatures = $rawFeatures;
            } else {
                $decoded = json_decode($rawFeatures, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $userFeatures = $decoded;
                } else {
                    $unserialized = @unserialize($rawFeatures);
                    if ($unserialized !== false && is_array($unserialized)) {
                        $userFeatures = $unserialized;
                    } else {
                        $userFeatures = array_filter(array_map('trim', explode(',', $rawFeatures)));
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

$rolePath  = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', trim($userRole))));
$baseRoute = "/cattleya/user/" . $rolePath;

/**
 * HELPER: Checks feature permissions dynamically for single or multiple feature keys.
 * Completely suppresses rendering if permission is absent.
 */
function checkUserFeatureAccess($featureKeys, $userFeatures, $userRole) {
    $roleClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', trim($userRole))));
    if ($roleClean === 'admin') {
        return true;
    }

    if (empty($userFeatures)) {
        return false;
    }

    $keysToCheck = is_array($featureKeys) ? $featureKeys : [$featureKeys];
    $expandedKeys = [];

    foreach ($keysToCheck as $key) {
        $expandedKeys[] = $key;
        $expandedKeys[] = "{$roleClean}_{$key}";

        switch ($key) {
            case 'dashboard':
                $expandedKeys[] = "{$roleClean}_dashboard";
                break;
            case 'payment':
            case 'payments':
                $expandedKeys[] = 'payment';
                $expandedKeys[] = 'payments';
                $expandedKeys[] = "{$roleClean}_payment";
                $expandedKeys[] = "{$roleClean}_payments";
                break;
            case 'waive_penalty':
            case 'waive_penalty_request':
                $expandedKeys[] = 'waive_penalty';
                $expandedKeys[] = 'waive_penalty_request';
                $expandedKeys[] = "{$roleClean}_waive_penalty";
                $expandedKeys[] = "{$roleClean}_waive_penalty_request";
                break;
            case 'commission':
            case 'commission_release':
                $expandedKeys[] = 'commission';
                $expandedKeys[] = 'commission_release';
                $expandedKeys[] = "{$roleClean}_commission";
                $expandedKeys[] = "{$roleClean}_commission_release";
                break;
            case 'rfp_approval_flow':
            case 'rfp_approval':
                $expandedKeys[] = 'rfp_approval_flow';
                $expandedKeys[] = 'rfp_approval';
                $expandedKeys[] = "{$roleClean}_rfp_approval_flow";
                $expandedKeys[] = "{$roleClean}_rfp_approval";
                break;
            case 'commission_config':
                $expandedKeys[] = 'commission_config';
                $expandedKeys[] = "{$roleClean}_commission_config";
                break;
            case 'services':
                $expandedKeys[] = 'avail_services';
                $expandedKeys[] = 'availed_services';
                $expandedKeys[] = "{$roleClean}_avail_services";
                $expandedKeys[] = "{$roleClean}_availed_services";
                break;
            case 'avail_services':
                $expandedKeys[] = "{$roleClean}_avail_services";
                break;
            case 'availed_services':
                $expandedKeys[] = "{$roleClean}_availed_services";
                break;
            case 'services_control':
                $expandedKeys[] = "{$roleClean}_services_control";
                break;
            case 'facilities_rental':
            case 'facilities_rental_services':
                $expandedKeys[] = 'facilities_rental';
                $expandedKeys[] = 'facilities_rental_services';
                $expandedKeys[] = "{$roleClean}_facilities_rental_services";
                $expandedKeys[] = "{$roleClean}_facilities_rental";
                break;
            case 'other_services':
                $expandedKeys[] = 'other_services';
                $expandedKeys[] = "{$roleClean}_other_services";
                break;
            case 'internment':
                $expandedKeys[] = 'internment';
                $expandedKeys[] = "{$roleClean}_internment";
                break;
            case 'gl_settings':
                $expandedKeys[] = 'gl_settings';
                $expandedKeys[] = "{$roleClean}_gl_settings";
                break;
            case 'inventory':
                $expandedKeys[] = 'inventory';
                $expandedKeys[] = "{$roleClean}_inventory";
                break;
            case 'product':
                $expandedKeys[] = 'product';
                $expandedKeys[] = "{$roleClean}_product";
                break;
            case 'registration':
                $expandedKeys[] = 'registration';
                $expandedKeys[] = "{$roleClean}_registration";
                break;
        }
    }

    foreach ($expandedKeys as $k) {
        if (in_array($k, $userFeatures, true) || (!empty($userFeatures[$k]) && $userFeatures[$k] !== false)) {
            return true;
        }
    }

    return false;
}

// -----------------------------------------------------------------------------
// PAGE ACCESS ENFORCEMENT & ROUTE VERIFICATION
// -----------------------------------------------------------------------------
$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base = '/cattleya';
if (strpos($rawUri, $base) === 0) {
    $currentUri = trim(substr($rawUri, strlen($base)), '/');
} else {
    $currentUri = trim($rawUri, '/');
}

$routeFeatureMap = [
    "user/{$rolePath}/dashboard"             => 'dashboard',
    "user/{$rolePath}/inventory"             => 'inventory',
    "user/{$rolePath}/payment"               => 'payment',
    "user/{$rolePath}/waive-penalty-request" => 'waive_penalty_request',
    "user/{$rolePath}/product"               => 'product',
    "user/{$rolePath}/commission-release"    => 'commission_release',
    "user/{$rolePath}/rfp-approval-flow"     => 'rfp_approval_flow',
    "user/{$rolePath}/commission-config"     => 'commission_config',
    "user/{$rolePath}/registration"          => 'registration',
    "user/{$rolePath}/gl-settings"           => 'gl_settings',
    "user/{$rolePath}/services-control"      => 'services_control',
    "user/{$rolePath}/avail-services"        => 'avail_services',
    "user/{$rolePath}/availed-services"      => 'availed_services'
];

$isAuthorized = true;
if (isset($routeFeatureMap[$currentUri]) && $rolePath !== 'admin') {
    $requiredFeature = $routeFeatureMap[$currentUri];
    if (!checkUserFeatureAccess($requiredFeature, $userFeatures, $userRole)) {
        $isAuthorized = false;
    }
}

if (!$isAuthorized) {
    $redirectUrl = (strpos($currentUri, 'dashboard') === false && checkUserFeatureAccess('dashboard', $userFeatures, $userRole)) 
        ? "{$baseRoute}/dashboard" 
        : "/cattleya/logout";

    if (!headers_sent()) {
        header("Location: {$redirectUrl}");
    } else {
        echo "<script>window.location.href='{$redirectUrl}';</script>";
    }
    exit;
}

// -----------------------------------------------------------------------------
// METRICS FOR BADGES & PENDING RFP LIST
// -----------------------------------------------------------------------------
$overdueCount = 0;
if (checkUserFeatureAccess(['payment', 'payments'], $userFeatures, $userRole)) {
    try {
        $stmtDue = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM payments 
            WHERE due_date < CURRENT_DATE() 
              AND status NOT IN ('fully paid', 'Paid')
        ");
        $stmtDue->execute();
        $overdueCount = $stmtDue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Payment Count Error: " . $e->getMessage());
    }
}

$requestedWaiveCount = 0;
if (checkUserFeatureAccess(['waive_penalty', 'waive_penalty_request'], $userFeatures, $userRole)) {
    try {
        $stmtWaiveCount = $pdo->prepare("SELECT COUNT(*) as total FROM payments WHERE request_waive = 'Requested'");
        $stmtWaiveCount->execute();
        $requestedWaiveCount = $stmtWaiveCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

$pendingCommissionRfps = [];
$pendingRfpCount = 0;
if (checkUserFeatureAccess(['rfp_approval_flow', 'rfp_approval', 'commission', 'commission_release'], $userFeatures, $userRole)) {
    try {
        $stmtRfp = $pdo->prepare("
            SELECT id, rfp_number, agent_name, amount, created_at, status 
            FROM rfp_approval_flow 
            WHERE status = 'Pending' AND (type = 'commission' OR category = 'commission' OR module = 'commission')
            ORDER BY id DESC
        ");
        $stmtRfp->execute();
        $pendingCommissionRfps = $stmtRfp->fetchAll(PDO::FETCH_ASSOC);
        $pendingRfpCount = count($pendingCommissionRfps);
    } catch (Exception $e) {
        error_log("RFP Approval Count Error: " . $e->getMessage());
    }
}

// Active State Detectors
$rfpActive = (strpos($currentUri, 'rfp-approval-flow') !== false);
$servicesActive = (strpos($currentUri, 'avail-services') !== false || strpos($currentUri, 'availed-services') !== false);

$maintenanceUrls = [
    "user/{$rolePath}/product",
    "user/{$rolePath}/registration",
    "user/{$rolePath}/commission-config",
    "user/{$rolePath}/gl-settings",
    "user/{$rolePath}/services-control"
];
$maintenanceActive = false;
foreach ($maintenanceUrls as $url) {
    if (strpos($currentUri, $url) !== false) {
        $maintenanceActive = true;
        break;
    }
}

$revenueServicesUrls = [
    "user/{$rolePath}/internment",
    "user/{$rolePath}/inventory",
    "user/{$rolePath}/other-services",
    "user/{$rolePath}/rental-services",
    "user/{$rolePath}/facilities-rental-services"
];
$revenueServicesActive = false;
foreach ($revenueServicesUrls as $url) {
    if (strpos($currentUri, $url) !== false) {
        $revenueServicesActive = true;
        break;
    }
}

$profileRoute = ($rolePath === 'admin') ? '/cattleya/views/includes/admin/profile' : '/cattleya/views/includes/user/profile';

// Group Access Aggregators to prevent empty headers
$hasMainMenuAccess = checkUserFeatureAccess([
    'dashboard', 'internment', 'inventory', 'other_services', 'facilities_rental', 'facilities_rental_services',
    'payment', 'waive_penalty', 'waive_penalty_request', 'commission', 'commission_release', 'rfp_approval_flow', 'rfp_approval'
], $userFeatures, $userRole);

$hasRevenueAccess = checkUserFeatureAccess(['internment', 'inventory', 'other_services', 'facilities_rental', 'facilities_rental_services'], $userFeatures, $userRole);
$hasServicesAccess = checkUserFeatureAccess(['avail_services', 'availed_services', 'services'], $userFeatures, $userRole);
$hasMaintenanceAccess = checkUserFeatureAccess(['product', 'registration', 'commission_config', 'gl_settings', 'services_control'], $userFeatures, $userRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cattleya - <?= ucfirst(htmlspecialchars($userRole)) ?> Suite</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
       :root {
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

        /* Logo Section Container */
        .logo-wrapper {
            padding: 1.5rem 1.25rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            border-bottom: 1px solid rgba(241, 245, 249, 0.8);
        }

        .logo-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        /* Ambient Backlight Glow & Hover Effects */
        .logo-icon-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .logo-icon-glow {
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg, #22c55e, #15803d);
            border-radius: 14px;
            filter: blur(8px);
            opacity: 0.4;
            transition: opacity 0.3s ease;
        }

        .logo-brand:hover .logo-icon-glow {
            opacity: 0.75;
        }

        .logo-icon {
            position: relative;
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.35rem;
            box-shadow: 0 8px 20px rgba(21, 128, 61, 0.25);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .logo-brand:hover .logo-icon {
            transform: rotate(18deg) scale(1.06);
        }

        /* Dual-Tone Typography */
        .logo-text-group {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .logo-text {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.035em;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-tagline {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #16a34a;
            margin-top: 3px;
        }

        /* Floating Responsive Toggle Button */
        .desktop-toggle-btn {
            position: absolute;
            right: -14px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            z-index: 10;
        }

        .desktop-toggle-btn:hover {
            color: #15803d;
            border-color: #86efac;
            background: #f0fdf4;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.22);
        }

        .desktop-toggle-btn i {
            font-size: 0.75rem;
            font-weight: 700;
        }
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
            font-weight: 600;
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

        .dropdown-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0, 1, 0, 1), opacity 0.3s ease;
            margin-left: 22px;
            padding-left: 12px;
            border-left: 2px dashed #e2e8f0;
            opacity: 0;
        }

        .dropdown-container.show {
            max-height: 500px;
            transition: max-height 0.4s ease-in-out, opacity 0.3s ease;
            opacity: 1;
            margin-top: 4px;
            margin-bottom: 10px;
        }

        .dropdown-container a {
            padding: 9px 14px;
            color: var(--sb-text-muted);
            font-size: 0.825rem;
            display: block;
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            transition: var(--transition-smooth);
            white-space: nowrap;
            margin-bottom: 3px;
            position: relative;
        }

        .dropdown-container a:hover, .dropdown-container a.sub-active {
            color: var(--sb-text-main);
            background: var(--sb-hover-bg);
            transform: translateX(2px);
        }

        .dropdown-container a.sub-active {
            color: var(--sb-accent);
            font-weight: 700;
            background: var(--sb-accent-light);
        }

        .arrow { 
            transition: transform 0.3s ease; 
            font-size: 0.7rem !important;
            width: auto !important;
        }
        .rotate-arrow { transform: rotate(180deg); }

        .badge-notification {
            font-size: 0.65rem;
            padding: 0.4em 0.65em;
            font-weight: 700;
            background-color: var(--sb-danger) !important;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }

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
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2);
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

        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .menu-label,
        .sidebar.collapsed .arrow,
        .sidebar.collapsed .user-details,
        .sidebar.collapsed .user-info-card .bi-chevron-expand,
        .sidebar.collapsed .dropdown-container,
        .sidebar.collapsed .badge-notification {
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
    </style>
</head>
<body>

<button class="mobile-toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">
    <i class="bi bi-list fs-4"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="mainSidebar">
        <div class="logo-wrapper">
        <a href="<?= $baseRoute ?>/dashboard" class="logo-brand">
            <!-- Icon Container with Ambient Glow Layer -->
            <div class="logo-icon-wrapper">
                <div class="logo-icon-glow"></div>
                <div class="logo-icon">
                    <i class="bi bi-flower3"></i>
                </div>
            </div>
            
            <!-- Brand Name & Micro Tagline -->
            <div class="logo-text-group sidebar-text">
                <span class="logo-text">Cattleya</span>
                <span class="logo-tagline">Management Suite</span>
            </div>
        </a>

        <!-- Floating Toggle Button -->
        <button class="desktop-toggle-btn d-none d-lg-flex" id="desktopSidebarToggle" aria-label="Collapse Sidebar">
            <i class="bi bi-chevron-left" id="desktopToggleIcon"></i>
        </button>
    </div>
    <div class="sidebar-menu">
        <!-- MAIN MENU SECTION -->
        <?php if ($hasMainMenuAccess): ?>
        <div class="menu-label">Main Menu</div>
        
        <!-- DASHBOARD -->
        <?php if (checkUserFeatureAccess('dashboard', $userFeatures, $userRole)): ?>
        <a href="<?= $baseRoute ?>/dashboard" class="nav-link-custom <?= (strpos($currentUri, 'dashboard') !== false) ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i>
            <span class="sidebar-text ms-3">Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- REVENUE SOURCES DROPDOWN -->
        <?php if ($hasRevenueAccess): ?>
        <button class="dropdown-btn" style="<?= $revenueServicesActive ? 'background: var(--sb-hover-bg); color: var(--sb-text-main);' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i> 
            <span class="sidebar-text ms-3">Revenue Streams</span>
            <i class="bi bi-chevron-down arrow ms-auto <?= $revenueServicesActive ? 'rotate-arrow' : '' ?>"></i>
        </button>

        <div class="dropdown-container <?= $revenueServicesActive ? 'show' : '' ?>" id="revenueServicesDropdown">
            <?php if (checkUserFeatureAccess('internment', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/internment" class="<?= (strpos($currentUri, 'internment') !== false) ? 'sub-active' : '' ?>">Internment Revenue</a>
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess('inventory', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/inventory" class="<?= (strpos($currentUri, 'inventory') !== false) ? 'sub-active' : '' ?>">Inventory Sales</a> 
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess('other_services', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/other-services" class="<?= (strpos($currentUri, 'other-services') !== false) ? 'sub-active' : '' ?>">Other Services & Park Rentals</a>
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess(['facilities_rental', 'facilities_rental_services'], $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/facilities-rental-services" class="<?= (strpos($currentUri, 'facilities-rental-services') !== false) ? 'sub-active' : '' ?>">Facilities Rental</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- PAYMENT -->
        <?php if (checkUserFeatureAccess(['payment', 'payments'], $userFeatures, $userRole)): ?>
        <a href="<?= $baseRoute ?>/payment" class="nav-link-custom <?= (strpos($currentUri, 'payment') !== false) ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i> 
            <span class="sidebar-text ms-3">Payments</span>
            <?php if ($overdueCount > 0): ?>
                <span class="badge rounded-pill badge-notification ms-auto text-white">
                    <?= $overdueCount ?>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- PENALTY WAIVE -->
        <?php if (checkUserFeatureAccess(['waive_penalty', 'waive_penalty_request'], $userFeatures, $userRole)): ?>
        <a href="<?= $baseRoute ?>/waive-penalty-request" class="nav-link-custom <?= (strpos($currentUri, 'waive-penalty-request') !== false) ? 'active' : '' ?>">
            <i class="bi bi-shield-exclamation"></i>
            <span class="sidebar-text ms-3">Penalty Waive</span>
            <?php if ($requestedWaiveCount > 0): ?>
                <span class="badge rounded-pill badge-notification ms-auto text-white">
                    <?= $requestedWaiveCount ?>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- COMMISSION RELEASE -->
        <?php if (checkUserFeatureAccess(['commission', 'commission_release'], $userFeatures, $userRole)): ?>
        <a href="<?= $baseRoute ?>/commission-release" class="nav-link-custom <?= (strpos($currentUri, 'commission-release') !== false) ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i> 
            <span class="sidebar-text ms-3">Commission Release</span>
        </a>
        <?php endif; ?>

        <?php endif; ?>

       <?php 
        // Evaluate permissions for individual service modules
        $canAccessAvail   = checkUserFeatureAccess(['avail_services', 'services'], $userFeatures, $userRole);
        $canAccessAvailed = checkUserFeatureAccess(['availed_services', 'services'], $userFeatures, $userRole);
        ?>
        <?php if ($canAccessAvail || $canAccessAvailed): ?>
            <div class="menu-label">Services</div>

            <button class="dropdown-btn" style="<?= $servicesActive ? 'background: var(--sb-hover-bg); color: var(--sb-text-main);' : '' ?>">
                <i class="bi bi-layers"></i> 
                <span class="sidebar-text ms-3">Service Hub</span>
                <i class="bi bi-chevron-down arrow ms-auto <?= $servicesActive ? 'rotate-arrow' : '' ?>"></i>
            </button>
            
            <div class="dropdown-container <?= $servicesActive ? 'show' : '' ?>" id="servicesDropdown">
                <?php if ($canAccessAvail): ?>
                    <a href="<?= $baseRoute ?>/avail-services" class="<?= (strpos($currentUri, 'avail-services') !== false) ? 'sub-active' : '' ?>">
                        Available Services
                    </a> 
                <?php endif; ?>

                <?php if ($canAccessAvailed): ?>
                    <a href="<?= $baseRoute ?>/availed-services" class="<?= (strpos($currentUri, 'availed-services') !== false) ? 'sub-active' : '' ?>">
                        Availed Services
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- OPERATIONS / MAINTENANCE DROPDOWN -->
        <?php if ($hasMaintenanceAccess): ?>
        <div class="menu-label">System Admin</div>

        <button class="dropdown-btn" style="<?= $maintenanceActive ? 'background: var(--sb-hover-bg); color: var(--sb-text-main);' : '' ?>">
            <i class="bi bi-gear"></i> 
            <span class="sidebar-text ms-3">Maintenance</span>
            <i class="bi bi-chevron-down arrow ms-auto <?= $maintenanceActive ? 'rotate-arrow' : '' ?>"></i>
        </button>
        <div class="dropdown-container <?= $maintenanceActive ? 'show' : '' ?>" id="maintenanceDropdown">
            <?php if (checkUserFeatureAccess('product', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/product" class="<?= (strpos($currentUri, 'product') !== false) ? 'sub-active' : '' ?>">Register Product</a>
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess('registration', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/registration" class="<?= (strpos($currentUri, 'registration') !== false) ? 'sub-active' : '' ?>">Register Personnel</a> 
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess('commission_config', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/commission-config" class="<?= (strpos($currentUri, 'commission-config') !== false) ? 'sub-active' : '' ?>">Commission Matrix</a>
            <?php endif; ?>

            <?php if (checkUserFeatureAccess('services_control', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/services-control" class="<?= (strpos($currentUri, 'services-control') !== false) ? 'sub-active' : '' ?>">Services Control</a> 
            <?php endif; ?>
            
            <?php if (checkUserFeatureAccess('gl_settings', $userFeatures, $userRole)): ?>
            <a href="<?= $baseRoute ?>/gl-settings" class="<?= (strpos($currentUri, 'gl-settings') !== false) ? 'sub-active' : '' ?>">GL Settings</a> 
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php 
        $user_name = $_SESSION['user_name'] ?? ucfirst(str_replace('_', ' ', $userRole)) . ' User';
        $words = explode(" ", $user_name);
        $user_initials = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
    ?>

   <!-- PROFILE AREA -->
<div class="sidebar-profile">
    <div class="profile-popover" id="profileMenu">
        <div class="p-3 bg-light border-bottom">
            <p class="user-name text-dark mb-0"><?= ucwords(htmlspecialchars($user_name)) ?></p>
            <small class="text-muted" style="font-size: 0.725rem;"><?= htmlspecialchars($_SESSION['user_email'] ?? $rolePath . '@cattleya.com') ?></small>
        </div>
        <a href="<?= $profileRoute ?>" class="popover-item">
            <i class="bi bi-person-gear"></i> Account Settings
        </a>
        <a href="#" class="popover-item text-danger" id="logoutBtn">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>

    <div class="user-info-card" id="profileToggle">
        <div class="user-avatar"><?= $user_initials ?></div>
        <div class="user-details flex-grow-1">
            <p class="user-name"><?= ucwords(htmlspecialchars($user_name)) ?></p>
            <p class="user-role mb-0"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? $userRole))) ?></p>
        </div>
        <i class="bi bi-chevron-expand text-muted"></i>
    </div>
</div>
</div>

<script>
    const sidebar = document.getElementById('mainSidebar');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const desktopToggleIcon = document.getElementById('desktopToggleIcon');

    if (desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('content-collapsed');
            
            if (sidebar.classList.contains('collapsed')) {
                desktopToggleIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
            } else {
                desktopToggleIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }
        });
    }

    document.querySelectorAll(".dropdown-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('content-collapsed');
                if (desktopToggleIcon) desktopToggleIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }

            const menu = this.nextElementSibling;
            const arrow = this.querySelector(".arrow");
            
            menu.classList.toggle("show");
            if (arrow) arrow.classList.toggle("rotate-arrow");

            if (menu.classList.contains("show")) {
                this.style.background = "var(--sb-hover-bg)";
                this.style.color = "var(--sb-text-main)";
            } else {
                this.style.background = "transparent";
                this.style.color = "var(--sb-text-muted)";
            }
        });
    });

    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');

    profileToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = profileMenu.style.display === 'block';
        profileMenu.style.display = isOpen ? 'none' : 'block';
    });

    document.addEventListener('click', () => { profileMenu.style.display = 'none'; });

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