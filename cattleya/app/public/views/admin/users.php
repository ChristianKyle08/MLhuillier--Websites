<?php
require __DIR__ . '/../../../config/database.php';
require __DIR__ . '/../includes/session_check.php';

$timezone = new DateTimeZone('Asia/Manila'); // PHT

/* ========================================
   MASTER FEATURE LIST (Categorized by Role)
======================================== */
$feature_categories = [
    'Encoder' => [
        'encoder_dashboard'                       => 'Encoder Dashboard',
        'encoder_inventory'                       => 'Encoder Inventory',
        'encoder_payment'                         => 'Encoder Payment',
        'encoder_waive_penalty_request'           => 'Encoder Waive Penalty Request',
        'encoder_product'                         => 'Encoder Product',
        'encoder_commission_config'               => 'Encoder Commission Config',
        'encoder_commission_release'              => 'Encoder Commission Release',
        'encoder_registration'                    => 'Encoder Registration',
        'encoder_gl_settings'                     => 'Encoder GL Settings',
        'encoder_services_control'                => 'Encoder Services Control',
        'encoder_avail_services'                  => 'Encoder Avail Services',
        'encoder_availed_services'                => 'Encoder Availed Services',
    ],
    'Cashier' => [
        'cashier_dashboard'                       => 'Cashier Dashboard',
        'cashier_inventory'                       => 'Cashier Inventory',
        'cashier_payment'                         => 'Cashier Payment',
        'cashier_waive_penalty_request'           => 'Cashier Waive Penalty Request',
        'cashier_product'                         => 'Cashier Product',
        'cashier_commission_config'               => 'Cashier Commission Config',
        'cashier_commission_release'              => 'Cashier Commission Release',
        'cashier_registration'                    => 'Cashier Registration',
        'cashier_gl_settings'                     => 'Cashier GL Settings',
        'cashier_services_control'                => 'Cashier Services Control',
        'cashier_avail_services'                  => 'Cashier Avail Services',
        'cashier_availed_services'                => 'Cashier Availed Services',
    ],
    'Auditor' => [
        'auditor_dashboard'                       => 'Auditor Dashboard',
        'auditor_inventory'                       => 'Auditor Inventory',
        'auditor_payment'                         => 'Auditor Payment',
        'auditor_waive_penalty_request'           => 'Auditor Waive Penalty Request',
        'auditor_product'                         => 'Auditor Product',
        'auditor_commission_config'               => 'Auditor Commission Config',
        'auditor_commission_release'              => 'Auditor Commission Release',
        'auditor_signature'                       => 'Auditor Signature',
        'auditor_gl_settings'                     => 'Auditor GL Settings',
        'auditor_services_control'                => 'Auditor Services Control',
        'auditor_avail_services'                  => 'Auditor Avail Services',
        'auditor_availed_services'                => 'Auditor Availed Services',
    ],
    'Finance' => [
        'finance_dashboard'                       => 'Finance Dashboard',
        'finance_inventory'                       => 'Finance Inventory',
        'finance_payment'                         => 'Finance Payment',
        'finance_waive_penalty_request'           => 'Finance Waive Penalty Request',
        'finance_product'                         => 'Finance Product',
        'finance_commission_config'               => 'Finance Commission Config',
        'finance_commission_release'              => 'Finance Commission Release',
        'finance_save_signature'                  => 'Finance Save Signature',
        'finance_gl_settings'                     => 'Finance GL Settings',
        'finance_services_control'                => 'Finance Services Control',
        'finance_avail_services'                  => 'Finance Avail Services',
        'finance_availed_services'                => 'Finance Availed Services',
    ],
    'CFO' => [
        'cfo_dashboard'                           => 'CFO Dashboard',
        'cfo_inventory'                           => 'CFO Inventory',
        'cfo_payment'                             => 'CFO Payment',
        'cfo_waive_penalty_request'               => 'CFO Waive Penalty Request',
        'cfo_product'                             => 'CFO Product',
        'cfo_commission_config'                   => 'CFO Commission Config',
        'cfo_commission_release'                  => 'CFO Commission Release',
        'cfo_save_signature'                      => 'CFO Save Signature',
        'cfo_gl_settings'                         => 'CFO GL Settings',
        'cfo_services_control'                    => 'CFO Services Control',
        'cfo_avail_services'                      => 'CFO Avail Services',
        'cfo_availed_services'                    => 'CFO Availed Services',
    ],
    'VPO (Vice President of Operations)' => [
        'vpo_dashboard'                           => 'VPO Dashboard',
        'vpo_inventory'                           => 'VPO Inventory',
        'vpo_payment'                             => 'VPO Payment',
        'vpo_waive_penalty_request'               => 'VPO Waive Penalty Request',
        'vpo_product'                             => 'VPO Product',
        'vpo_commission_config'                   => 'VPO Commission Config',
        'vpo_commission_release'                  => 'VPO Commission Release',
        'vpo_save_signature'                      => 'VPO Save Signature',
        'vpo_gl_settings'                         => 'VPO GL Settings',
        'vpo_services_control'                    => 'VPO Services Control',
        'vpo_avail_services'                      => 'VPO Avail Services',
        'vpo_availed_services'                    => 'VPO Availed Services',
    ],
    'Operation Manager' => [
        'operation_manager_dashboard'             => 'Operation Manager Dashboard',
        'operation_manager_inventory'             => 'Operation Manager Inventory',
        'operation_manager_payment'               => 'Operation Manager Payment',
        'operation_manager_waive_penalty_request' => 'Operation Manager Waive Penalty Request',
        'operation_manager_product'               => 'Operation Manager Product',
        'operation_manager_commission_config'     => 'Operation Manager Commission Config',
        'operation_manager_commission_release'    => 'Operation Manager Commission Release',
        'operation_manager_save_signature'        => 'Operation Manager Save Signature',
        'operation_manager_gl_settings'           => 'Operation Manager GL Settings',
        'operation_manager_services_control'      => 'Operation Manager Services Control',
        'operation_manager_avail_services'        => 'Operation Manager Avail Services',
        'operation_manager_availed_services'      => 'Operation Manager Availed Services',
    ]
];

// Flat array map for quick display lookups
$all_features = [];
foreach ($feature_categories as $cat => $feats) {
    $all_features = array_merge($all_features, $feats);
}

/* ========================================
   ROBUST FEATURE PARSER HELPER
======================================== */
function parseUserFeatures($raw) {
    if (empty($raw)) return [];
    if (is_array($raw)) return $raw;
    
    // Attempt 1: Standard JSON
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Attempt 2: PHP Serialized String
    $unserialized = @unserialize($raw);
    if ($unserialized !== false && is_array($unserialized)) {
        return $unserialized;
    }

    // Attempt 3: Comma-separated string
    return array_filter(array_map('trim', explode(',', $raw)));
}

/* ========================================
   REMEMBER TOKEN LOGIN & AUTH GUARD
======================================== */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role']       = $user['role'];
    } else {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /cattleya/login");
    exit;
}

/* ========================================
   FETCH USERS
======================================== */
$usersQuery = "
    SELECT id, first_name, last_name, username, email, role, status,
           created_at, last_active, last_logout, features
    FROM users
    WHERE role IN ('encoder', 'cashier', 'auditor', 'finance', 'cfo', 'vpo', 'operation_manager')
    ORDER BY created_at DESC
";
$users = $pdo->query($usersQuery)->fetchAll(PDO::FETCH_ASSOC);

// Metrics calculation
$total_users = count($users);
$active_users = 0;
$inactive_users = 0;
$total_assigned_features = 0;

foreach ($users as $u) {
    if ($u['status'] === 'active') {
        $active_users++;
    }
    if ($u['status'] === 'inactive') {
        $inactive_users++;
    }
    $total_assigned_features += count(parseUserFeatures($u['features']));
}

$user_name = $_SESSION['user_name'];
$email     = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Permissions & Access Control | Cattleya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #044e3b;
            --brand-primary-soft: rgba(4, 78, 59, 0.08);
            --brand-accent: #10b981;
            --brand-gradient: linear-gradient(135deg, #044e3b 0%, #059669 100%);
            
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-light: #f4f7f6;
            --surface-color: #ffffff;
            --sidebar-width: 280px; 
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            background-image: 
                radial-gradient(circle at 100% 0%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 0% 100%, rgba(4, 78, 59, 0.04) 0%, transparent 40%);
            background-attachment: fixed;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .content-wrapper {
            padding: 2.5rem;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        @media (max-width: 991.98px) {
            .content-wrapper { 
                margin-left: 0; 
                padding: 1.25rem; 
            }
        }

        /* === TYPOGRAPHY === */
        .fw-800 { font-weight: 800; }
        .fw-700 { font-weight: 700; }
        .fw-600 { font-weight: 600; }
        .fw-500 { font-weight: 500; }
        .fs-7   { font-size: 0.875rem; }

        /* === KPI CARDS === */
        .kpi-card {
            background: var(--surface-color);
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; 
            left: 0; 
            right: 0; 
            height: 4px;
            background: var(--brand-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .kpi-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 25px -5px rgba(0,0,0,0.06);
        }

        .kpi-card:hover::before { 
            opacity: 1; 
        }

        .kpi-icon {
            width: 56px; 
            height: 56px;
            border-radius: 16px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 1.5rem; 
            flex-shrink: 0;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);
        }

        .kpi-card-value {
            font-size: 1.75rem;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        /* === MAIN CONTAINER CARD === */
        .modern-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        /* === SEARCH & FILTERS === */
        .search-input-group {
            border-radius: 99px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .search-input-group .input-group-text {
            background: transparent; 
            border: none; 
            color: #94a3b8; 
            padding-left: 1.25rem;
        }

        .search-input-group .form-control {
            border: none; 
            background: transparent; 
            padding: 0.75rem 1.25rem 0.75rem 0.5rem; 
            font-weight: 500; 
            font-size: 0.95rem; 
            box-shadow: none !important;
        }

        .search-input-group:focus-within {
            background: #fff;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
        }

        .filter-select {
            border-radius: 99px;
            padding: 0.6rem 2.5rem 0.6rem 1.25rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
        }

        /* === AVATAR & INITIALS === */
        .user-avatar-initials {
            width: 48px; 
            height: 48px;
            background: var(--brand-gradient);
            color: #fff; 
            border-radius: 14px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-weight: 800; 
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(4, 78, 59, 0.25);
            position: relative;
        }

        .user-avatar-initials::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* === TABLE STYLING === */
        .table-premium {
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 0.5rem;
        }

        .table-premium thead th {
            background: transparent;
            color: #64748b;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 1.25rem 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-premium tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }

        .table-premium tbody tr {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .table-premium tbody tr:hover td { 
            background-color: #f8fafc; 
        }

        .table-premium tbody tr:hover td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table-premium tbody tr:hover td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        /* === BADGES & PULSE === */
        .badge-soft-role {
            background-color: var(--brand-primary-soft);
            color: var(--brand-primary);
            font-weight: 800;
            font-size: 0.7rem;
            padding: 0.4rem 0.85rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid rgba(4, 78, 59, 0.1);
        }

        .status-badge {
            font-weight: 700; 
            font-size: 0.8rem;
            padding: 0.4rem 0.85rem; 
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .status-active { 
            background-color: rgba(34, 197, 94, 0.1); 
            color: #15803d; 
            border: 1px solid rgba(34, 197, 94, 0.2); 
        }

        .status-inactive { 
            background-color: rgba(239, 68, 68, 0.1); 
            color: #b91c1c; 
            border: 1px solid rgba(239, 68, 68, 0.2); 
        }
        
        .pulse-dot {
            width: 6px; 
            height: 6px; 
            border-radius: 50%; 
            display: inline-block;
        }

        .pulse-green { 
            background: #22c55e; 
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); 
            animation: pulseGreen 2s infinite; 
        }

        .pulse-red { 
            background: #ef4444; 
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.5); 
        }

        @keyframes pulseGreen {
            0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70%  { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .feature-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.65rem;
            border-radius: 8px;
            font-weight: 600;
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .feature-badge:hover {
            border-color: #cbd5e1;
            background-color: #fff;
        }

        /* === ACTIONS === */
        .btn-edit-user {
            background: #ffffff; 
            color: var(--text-dark); 
            border: 1px solid #cbd5e1;
            border-radius: 12px; 
            padding: 0.5rem 1rem; 
            font-weight: 600; 
            font-size: 0.85rem; 
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .btn-edit-user:hover {
            background: var(--brand-primary); 
            color: #fff; 
            border-color: var(--brand-primary);
            box-shadow: 0 4px 12px rgba(4, 78, 59, 0.2);
            transform: translateY(-1px);
        }

        /* === CATEGORIZED MODAL === */
        .modal-content { 
            border-radius: 24px; 
            border: none; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }

        .modal-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #f1f5f9;
            padding: 1.5rem 2rem;
        }

        .modal-body { 
            padding: 2rem; 
            background: #fdfdfd; 
        }
        
        .feature-category-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.01);
        }

        .feature-category-title {
            font-size: 0.8rem; 
            font-weight: 800; 
            text-transform: uppercase;
            color: var(--brand-primary); 
            letter-spacing: 0.08em; 
            margin-bottom: 1rem;
            display: flex; 
            align-items: center; 
            gap: 0.5rem;
        }

        /* Custom Form Switch for Premium Feel */
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            margin-top: 0.15em;
            cursor: pointer;
            border-color: #cbd5e1;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%2394a3b8'/%3e%3c/svg%3e");
        }

        .form-switch .form-check-input:checked {
            background-color: var(--brand-accent);
            border-color: var(--brand-accent);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        .form-switch .form-check-label {
            cursor: pointer;
            user-select: none;
        }

        /* Modal Form Inputs */
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 0.65rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
        }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/../includes/admin/navbar.php'; ?>

    <div class="content-wrapper">
        
        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
            <div>
                <h3 class="fw-800 mb-2" style="color: var(--brand-primary); font-size: 2rem; letter-spacing: -0.02em;">
                    User Access & Permissions
                </h3>
                <p class="text-muted mb-0 fs-7 fw-500">
                    Manage system accounts, staff roles, and dynamic feature permissions
                </p>
            </div>
        </div>

        <!-- METRICS / KPI CARDS ROW -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-700 text-uppercase letter-spacing">Total Users</div>
                        <div class="fw-800 kpi-card-value text-dark"><?= $total_users ?></div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-700 text-uppercase letter-spacing">Active Accounts</div>
                        <div class="fw-800 kpi-card-value text-dark"><?= $active_users ?></div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-slash-circle-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-700 text-uppercase letter-spacing">Inactive</div>
                        <div class="fw-800 kpi-card-value text-dark"><?= $inactive_users ?></div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-700 text-uppercase letter-spacing">Active Grants</div>
                        <div class="fw-800 kpi-card-value text-dark"><?= $total_assigned_features ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN DATA TABLE CONTAINER -->
        <div class="modern-card">
            
            <!-- TOOLBAR: SEARCH & FILTERS -->
            <div class="row g-3 align-items-center mb-4">
                <div class="col-md-5">
                    <div class="input-group search-input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="userSearch" class="form-control" placeholder="Search by name, email, or username...">
                    </div>
                </div>
                <div class="col-md-7 d-flex gap-3 justify-content-md-end">
                    <select id="roleFilter" class="form-select w-auto fw-600 filter-select">
                        <option value="">All Roles</option>
                        <option value="encoder">Encoder</option>
                        <option value="cashier">Cashier</option>
                        <option value="auditor">Auditor</option>
                        <option value="finance">Finance</option>
                        <option value="cfo">CFO</option>
                        <option value="vpo">VPO</option>
                        <option value="operation_manager">Operation Manager</option>
                    </select>
                    <select id="statusFilter" class="form-select w-auto fw-600 filter-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-premium align-middle w-100" id="usersTable">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="d-none">ID</th>
                            <th>User Profile</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role & Assigned Features</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1; 
                        foreach ($users as $u): 
                            $parsedFeats = parseUserFeatures($u['features']);
                        ?>
                        <tr data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= htmlspecialchars($u['status']) ?>">
                            <td class="text-center text-muted fw-bold"><?= $count ?></td>
                            <td class="d-none"><?= htmlspecialchars($u['id']) ?></td>
                            
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php
                                        $words = explode(" ", $u['first_name'] . ' ' . $u['last_name']);
                                        $initials = "";
                                        foreach ($words as $w) { 
                                            if (isset($w[0])) $initials .= $w[0]; 
                                        }
                                        $initials = strtoupper(substr($initials, 0, 2));
                                    ?>
                                    <div class="user-avatar-initials"><?= $initials ?></div>
                                    <div>
                                        <div class="fw-700 text-dark fs-6">
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        </div>
                                        <div class="small text-muted fw-500">
                                            Joined <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="fw-700 text-dark">@<?= htmlspecialchars($u['username']) ?></td>
                            
                            <td class="text-muted fw-500"><?= htmlspecialchars($u['email']) ?></td>
                            
                            <!-- ROLE & FEATURE BADGES DISPLAY -->
                            <td>
                                <span class="badge-soft-role d-inline-block mb-2">
                                    <?= htmlspecialchars(str_replace('_', ' ', $u['role'])) ?>
                                </span>
                                <div class="d-flex flex-wrap gap-2" style="max-width: 320px;">
                                    <?php if (!empty($parsedFeats)): ?>
                                        <?php 
                                        $displayed = 0;
                                        $maxDisplay = 4;
                                        foreach ($parsedFeats as $fKey): 
                                            $fLabel = $all_features[$fKey] ?? ucwords(str_replace('_', ' ', $fKey));
                                            if ($displayed < $maxDisplay):
                                        ?>
                                            <span class="feature-badge">
                                                <i class="bi bi-check-circle-fill text-success" style="font-size:0.7rem;"></i>
                                                <?= htmlspecialchars($fLabel) ?>
                                            </span>
                                        <?php else: ?>
                                            <?php 
                                                $remaining = count($parsedFeats) - $maxDisplay;
                                                echo "<span class='feature-badge bg-secondary text-white border-secondary' title='More features assigned'>+{$remaining} more</span>";
                                                break;
                                            ?>
                                        <?php 
                                            endif; 
                                            $displayed++; 
                                        endforeach; 
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted fs-7 fst-italic">
                                            <i class="bi bi-exclamation-circle me-1"></i>No feature permissions
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- STATUS PULSE -->
                            <td>
                                <?php
                                    $statusClass = 'status-inactive'; // fallback
                                    $pulseClass = '';
                                    if ($u['status'] === 'active') {
                                        $statusClass = 'status-active';
                                        $pulseClass = 'pulse-green';
                                    } elseif ($u['status'] === 'inactive') {
                                        $statusClass = 'status-inactive';
                                        $pulseClass = 'pulse-red';
                                    } else if ($u['status'] === 'pending') {
                                        // Pending state config
                                        $statusClass = 'status-badge'; 
                                        $pulseClass = 'pulse-dot bg-warning';
                                    }
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <span class="pulse-dot <?= $pulseClass ?>"></span>
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            
                            <td class="fs-7 text-muted fw-600">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-activity text-success"></i>
                                    <?php
                                    if ($u['last_active']) {
                                        $dt = new DateTime($u['last_active'], new DateTimeZone('UTC'));
                                        $dt->setTimezone($timezone);
                                        echo $dt->format('M d, H:i');
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </div>
                            </td>
                            
                            <td class="text-center">
                                <button class="btn btn-edit-user editUserBtn"
                                        data-first="<?= htmlspecialchars($u['first_name'] ?? '', ENT_QUOTES) ?>"
                                        data-last="<?= htmlspecialchars($u['last_name'] ?? '', ENT_QUOTES) ?>"
                                        data-username="<?= htmlspecialchars($u['username'] ?? '', ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>"
                                        data-role="<?= htmlspecialchars($u['role'] ?? '', ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($u['status'] ?? '', ENT_QUOTES) ?>"
                                        data-features="<?= htmlspecialchars(json_encode($parsedFeats), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-sliders me-1"></i> Config
                                </button>
                            </td>
                        </tr>
                        <?php 
                            $count++; 
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CATEGORIZED FEATURE ACCESS EDIT MODAL -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-800 mb-1 text-dark">Edit Staff Account & Access</h5>
                        <p class="text-muted fs-7 fw-500 mb-0">Update user bio and fine-tune feature switch access</p>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal" style="box-shadow: none;"></button>
                </div>
                
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <!-- BASIC INFO -->
                        <div class="row g-4 mb-4 pb-2 border-bottom border-light">
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 border-secondary-subtle">@</span>
                                    <input type="text" class="form-control border-start-0 ps-0" name="username" id="edit_username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">Email Address</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">System Role</label>
                                <select class="form-select fw-600" name="role" id="edit_role">
                                    <option value="encoder">Encoder</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="auditor">Auditor</option>
                                    <option value="finance">Finance</option>
                                    <option value="cfo">CFO</option>
                                    <option value="vpo">VPO</option>
                                    <option value="operation_manager">Operation Manager</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 text-dark">Account Status</label>
                                <select class="form-select fw-600" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- CATEGORIZED PERMISSIONS -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-800 text-dark mb-0 fs-5">
                                <i class="bi bi-shield-lock-fill me-2" style="color: var(--brand-accent);"></i>Feature Access Controls
                            </h6>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-600 px-3 fs-7" id="selectAllFeatures">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill fw-600 px-3 fs-7" id="deselectAllFeatures">Deselect All</button>
                            </div>
                        </div>

                        <?php foreach ($feature_categories as $categoryName => $catFeatures): 
                            $catRoleKey = strtolower($categoryName);
                            if ($catRoleKey === 'operation manager') $catRoleKey = 'operation_manager';
                            if (strpos($catRoleKey, 'vpo') !== false) $catRoleKey = 'vpo'; 
                        ?>
                            <div class="feature-category-card" data-role-target="<?= $catRoleKey ?>" style="display: none;">
                                <div class="feature-category-title">
                                    <i class="bi bi-person-badge text-muted"></i> <?= htmlspecialchars($categoryName) ?>
                                </div>
                                <div class="row g-3">
                                    <?php foreach ($catFeatures as $fKey => $fLabel): ?>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                                <input class="form-check-input feature-checkbox m-0" type="checkbox" name="features[]" value="<?= $fKey ?>" id="feat_<?= $fKey ?>">
                                                <label class="form-check-label fw-600 fs-7 text-dark m-0 pt-1" for="feat_<?= $fKey ?>">
                                                    <?= htmlspecialchars($fLabel) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                    <div class="modal-footer border-top-0 p-4 pt-0 justify-content-end bg-transparent">
                        <button type="button" class="btn btn-light fw-700 rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-700 rounded-pill px-5 border-0" style="background: var(--brand-gradient); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /* Function to toggle feature categories based on role */
        function updateFeatureDisplay(role, resetHiddenCheckboxes = false) {
            document.querySelectorAll('.feature-category-card').forEach(card => {
                if (card.dataset.roleTarget === role) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                    if (resetHiddenCheckboxes) {
                        card.querySelectorAll('.feature-checkbox').forEach(chk => chk.checked = false);
                    }
                }
            });
        }

        /* Listen for role dropdown changes to update UI dynamically */
        document.getElementById("edit_role").addEventListener("change", function() {
            updateFeatureDisplay(this.value, true); 
        });

        /* Populating Modal on Edit Click */
        document.querySelectorAll(".editUserBtn").forEach(btn => {
            btn.addEventListener("click", function () {
                const row = this.closest("tr");
                const userId = row.querySelector("td.d-none").textContent.trim(); 
                const userRole = this.dataset.role.trim();

                document.getElementById("edit_id").value = userId;
                document.getElementById("edit_first").value = this.dataset.first;
                document.getElementById("edit_last").value = this.dataset.last;
                document.getElementById("edit_username").value = this.dataset.username;
                document.getElementById("edit_email").value = this.dataset.email;
                document.getElementById("edit_role").value = userRole;
                document.getElementById("edit_status").value = this.dataset.status.trim();

                // Clear existing switches
                document.querySelectorAll('.feature-checkbox').forEach(chk => chk.checked = false); 
                
                // Show only the category for the selected role
                updateFeatureDisplay(userRole, false);
                
                try {
                    const rawFeatures = this.dataset.features;
                    if (rawFeatures) {
                        const assigned = JSON.parse(rawFeatures);
                        if (Array.isArray(assigned)) {
                            assigned.forEach(feat => {
                                const chk = document.querySelector(`.feature-checkbox[value="${feat}"]`);
                                if (chk) {
                                    chk.checked = true;
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.warn("Feature parsing error:", e);
                }

                new bootstrap.Modal(document.getElementById("editUserModal")).show();
            });
        });

        /* Select All / Deselect All Controls (Affects only visible category) */
        const selectAllBtn = document.getElementById("selectAllFeatures");
        if (selectAllBtn) {
            selectAllBtn.addEventListener("click", () => {
                document.querySelectorAll('.feature-category-card').forEach(card => {
                    if (card.style.display !== 'none') {
                        card.querySelectorAll('.feature-checkbox').forEach(chk => chk.checked = true);
                    }
                });
            });
        }

        const deselectAllBtn = document.getElementById("deselectAllFeatures");
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener("click", () => {
                document.querySelectorAll('.feature-category-card').forEach(card => {
                    if (card.style.display !== 'none') {
                        card.querySelectorAll('.feature-checkbox').forEach(chk => chk.checked = false);
                    }
                });
            });
        }

        /* AJAX Form Submit */
        document.getElementById("editUserForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const res = await fetch("/cattleya/auth/update-users", { 
                    method: "POST", 
                    body: formData 
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "User Updated",
                        text: data.message || "Permissions updated successfully!",
                        timer: 1500,
                        showConfirmButton: false,
                        backdrop: `rgba(4, 78, 59, 0.4)`
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ 
                        icon: "error", 
                        title: "Update Failed", 
                        text: data.error || "An error occurred.", 
                        backdrop: `rgba(4, 78, 59, 0.4)` 
                    });
                }
            } catch (err) {
                Swal.fire({ 
                    icon: "error", 
                    title: "Error", 
                    text: err.message, 
                    backdrop: `rgba(4, 78, 59, 0.4)` 
                });
            }
        });

        /* Live Search & Filter Logic */
        const searchInput = document.getElementById('userSearch');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');

        function filterUsersTable() {
            const query = searchInput.value.toLowerCase();
            const selectedRole = roleFilter.value;
            const selectedStatus = statusFilter.value;

            document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                const role = row.getAttribute('data-role');
                const status = row.getAttribute('data-status');

                const matchesQuery = text.includes(query);
                const matchesRole = !selectedRole || role === selectedRole;
                const matchesStatus = !selectedStatus || status === selectedStatus;

                if (matchesQuery && matchesRole && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterUsersTable);
        if (roleFilter) roleFilter.addEventListener('change', filterUsersTable);
        if (statusFilter) statusFilter.addEventListener('change', filterUsersTable);
    </script>
</body>
</html>