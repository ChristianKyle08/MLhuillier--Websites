<?php
require __DIR__ . '/../../../config/database.php';
require __DIR__ . '/../includes/session_check.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role'];
    } else {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /cattleya/login");
    exit;
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$resetCount = $pdo->query("SELECT COUNT(*) FROM users WHERE reset_token IS NOT NULL AND reset_expires > NOW()")->fetchColumn();

$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Command Center | Cattleya Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
    :root {
        --brand-primary: #044e3b; 
        --brand-primary-dark: #022c22;
        --brand-accent: #10b981;
        --brand-glow: rgba(16, 185, 129, 0.15);
        --surface: #ffffff;
        --surface-glass: rgba(255, 255, 255, 0.92);
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --border-subtle: #e2e8f0;
        --shadow-subtle: 0 4px 20px -2px rgba(0, 0, 0, 0.03);
        --shadow-card: 0 12px 32px -5px rgba(4, 78, 59, 0.08);
        --brand-primary-soft: rgba(4, 78, 59, 0.07);
        --brand-accent-soft: rgba(16, 185, 129, 0.1);
        --radius-lg: 20px;
        --radius-xl: 24px;
        --shadow-elevated: 0 20px 45px -15px rgba(4, 78, 59, 0.16);
    }

    body {
        background-color: #f8fafc;
        background-image: 
            radial-gradient(circle at 100% 0%, rgba(16, 185, 129, 0.05) 0%, transparent 35%),
            radial-gradient(circle at 0% 100%, rgba(4, 78, 59, 0.04) 0%, transparent 35%);
        background-attachment: fixed;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: var(--text-primary);
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
        min-height: 100vh;
    }

    .dashboard-container { padding: 2rem 2.5rem; max-width: 1550px; margin: 0 auto; }
    @media (max-width: 991.98px) { .dashboard-container { padding: 1.25rem; } }

    /* Modern Executive Header */
    .exec-header {
        background: var(--surface-glass);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 1.5rem 2rem;
        box-shadow: var(--shadow-subtle);
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }
    .exec-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--brand-primary), var(--brand-accent), transparent);
    }

    /* Pulse dot animation */
    .pulse-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: var(--brand-accent);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Bento KPI Cards */
    .kpi-card {
        background: var(--surface);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-subtle);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 1.5rem; right: 1.5rem;
        height: 3px;
        border-radius: 0 0 3px 3px;
        background: linear-gradient(90deg, var(--brand-accent), transparent 85%);
        opacity: 0.7;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-card);
        border-color: #cbd5e1;
    }
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; margin-bottom: 1rem;
    }
    .kpi-blue { background: rgba(59, 130, 246, 0.08); color: #2563eb; }
    .kpi-emerald { background: rgba(16, 185, 129, 0.08); color: #059669; }
    .kpi-amber { background: rgba(245, 158, 11, 0.08); color: #d97706; }
    .kpi-rose { background: rgba(239, 68, 68, 0.08); color: #dc2626; }

    .kpi-label { font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; }
    .kpi-value { font-size: 2rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-top: 0.25rem; }

    /* Workspace Cards */
    .workspace-card {
        background: var(--surface);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-xl);
        padding: 1.75rem;
        box-shadow: var(--shadow-subtle);
        margin-top: 2rem;
        transition: box-shadow 0.3s ease;
    }
    .workspace-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    /* Modern Minimalist Tables */
    .table-custom { width: 100%; border-collapse: separate !important; border-spacing: 0 8px !important; }
    .table-custom thead th {
        border: none; padding: 0 1rem 0.5rem; color: var(--text-secondary);
        text-transform: uppercase; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em;
    }
    .table-custom tbody tr {
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .table-custom tbody tr:hover {
        background: #f1f5f9;
        transform: scale(1.001);
        box-shadow: 0 4px 14px -6px rgba(15, 23, 42, 0.08);
    }
    .table-custom tbody td { border: none; padding: 1rem; vertical-align: middle; }
    .table-custom tbody td:first-child { border-radius: 14px 0 0 14px; }
    .table-custom tbody td:last-child { border-radius: 0 14px 14px 0; }

    /* Empty states */
    .empty-state { padding: 2.5rem 1rem; text-align: center; }
    .empty-state-icon {
        width: 56px; height: 56px; margin: 0 auto 0.85rem;
        border-radius: 16px;
        background: var(--brand-accent-soft);
        color: var(--brand-primary);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }

    /* Avatars & Elements */
    .user-avatar {
        width: 40px; height: 40px; border-radius: 12px;
        background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
        color: #fff; font-weight: 700; font-size: 0.85rem;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(4, 78, 59, 0.15);
        flex-shrink: 0;
    }

    /* Controls & Buttons */
    .btn-action {
        width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-subtle);
        background: #fff; color: var(--text-secondary); display: inline-flex;
        align-items: center; justify-content: center; transition: all 0.2s ease;
    }
    .btn-action:hover { background: #f8fafc; color: var(--text-primary); border-color: #cbd5e1; transform: translateY(-1px); }
    .btn-action-approve:hover { background: #ecfdf5 !important; color: #059669 !important; border-color: #a7f3d0 !important; }
    .btn-action-deny:hover { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

    .btn-primary-glow {
        background: var(--brand-primary); color: #fff; border: none;
        border-radius: 10px; font-weight: 700; font-size: 0.78rem; padding: 0.5rem 1rem;
        box-shadow: 0 4px 12px rgba(4, 78, 59, 0.2); transition: all 0.2s ease;
    }
    .btn-primary-glow:hover { background: var(--brand-primary-dark); color: #fff; transform: translateY(-1px); }

    /* Select Inputs */
    .select-pill-wrapper { position: relative; }
    .select-pill-wrapper i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--brand-primary); font-size: 0.8rem; z-index: 2; }
    .select-pill {
        background-color: #fff; border: 1px solid var(--border-subtle);
        border-radius: 10px; padding: 0.45rem 0.75rem 0.45rem 2.1rem;
        color: var(--text-primary); font-size: 0.78rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s ease; width: 100%;
    }
    .select-pill:focus { border-color: var(--brand-accent); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12); outline: none; }

    /* Seamless Professional Modals */
    .modal-backdrop.show {
        background-color: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(8px);
    }
    .modal-content {
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.25);
        background: #ffffff;
        overflow: hidden;
    }
    .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
    }
    .modal-body {
        padding: 1.5rem;
    }
    .modal-content .btn-close {
        background-color: #f1f5f9;
        border-radius: 50%;
        padding: 0.6rem;
        opacity: 0.7;
        transition: all 0.2s ease;
    }
    .modal-content .btn-close:hover {
        opacity: 1;
        background-color: #e2e8f0;
        transform: rotate(90deg);
    }

    /* Team Composition */
    .role-bar-row { width: 100%; }
    .role-bar-label { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.4rem; }
    .role-bar-track { height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
    .role-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--brand-primary), var(--brand-accent));
        border-radius: 999px;
        transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @media (prefers-reduced-motion: reduce) {
        .animate__animated { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; }
        .kpi-card, .workspace-card, .table-custom tbody tr, .btn-action, .btn-primary-glow, .role-bar-fill {
            transition: none !important;
        }
    }

    /* Modern Glassmorphic SweetAlert Backdrop & Dialog */
.swal2-container {
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

.sa-modern-popup {
    border-radius: 28px !important;
    padding: 2.25rem 2rem !important;
    background: #ffffff !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.03) !important;
}

/* Typography Hierarchy */
.sa-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    font-size: 1.25rem !important;
    letter-spacing: -0.02em !important;
    margin-bottom: 0.5rem !important;
}

.sa-html-container {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 500 !important;
    color: #64748b !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
    padding: 0 0.5rem !important;
}

/* Custom Buttons & Hover States */
.sa-btn-confirm {
    background: linear-gradient(135deg, #044e3b 0%, #065f46 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    padding: 0.7rem 1.6rem !important;
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(4, 78, 59, 0.25) !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

.sa-btn-confirm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(4, 78, 59, 0.35) !important;
    background: linear-gradient(135deg, #022c22 0%, #044e3b 100%) !important;
}

.sa-btn-cancel {
    background: #f1f5f9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    padding: 0.7rem 1.6rem !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

.sa-btn-cancel:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
    transform: translateY(-1px) !important;
}

/* Customized Alert Icons */
.swal2-icon {
    border-width: 2.5px !important;
    margin: 0.75rem auto 1.25rem !important;
    transform: scale(0.92);
}

.swal2-icon.swal2-warning { border-color: #f59e0b !important; color: #f59e0b !important; }
.swal2-icon.swal2-success { border-color: #10b981 !important; color: #10b981 !important; }
.swal2-icon.swal2-error { border-color: #ef4444 !important; color: #ef4444 !important; }

/* Floating Capsule Toast */
.sa-toast-capsule {
    border-radius: 16px !important;
    background: rgba(15, 23, 42, 0.92) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 12px 32px -5px rgba(0, 0, 0, 0.3) !important;
    padding: 0.75rem 1.25rem !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 600 !important;
    font-size: 0.8125rem !important;
}

.swal2-timer-progress-bar {
    background: #10b981 !important;
    height: 3px !important;
}
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../includes/admin/navbar.php'; ?>

<div class="dashboard-container">
    
    <!-- Modern Executive Header -->
    <div class="exec-header animate__animated animate__fadeIn">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1 rounded-pill fw-700 d-inline-flex align-items-center gap-1.5" style="font-size: 0.7rem;">
                    <span class="pulse-dot"></span> Live System Normal
                </span>
                <span class="text-secondary fw-500 fs-7">• <?= date('l, F j, Y') ?></span>
            </div>
            <h1 class="fw-800 text-dark mb-0" style="font-size: 1.85rem;">Dashboard</h1>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="fw-700 text-dark fs-7"><?= htmlspecialchars($user_name) ?></div>
                <div class="text-secondary fw-500" style="font-size: 0.72rem;">Super Administrator</div>
            </div>
            <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
        </div>
    </div>

    <!-- Bento Grid Metrics -->
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card animate__animated animate__zoomIn" style="animation-delay: 0.05s;">
                <div class="kpi-icon kpi-blue"><i class="bi bi-people-fill"></i></div>
                <div class="kpi-label">Total Network Users</div>
                <div class="kpi-value"><?= $totalUsers ?></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card animate__animated animate__zoomIn" style="animation-delay: 0.1s;">
                <div class="kpi-icon kpi-emerald"><i class="bi bi-person-plus-fill"></i></div>
                <div class="kpi-label">Today's Onboarding</div>
                <div class="kpi-value"><?= $newUsers ?></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card animate__animated animate__zoomIn" style="animation-delay: 0.15s;">
                <div class="kpi-icon kpi-amber"><i class="bi bi-key-fill"></i></div>
                <div class="kpi-label">Active Resets</div>
                <div class="kpi-value"><?= $resetCount ?></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card animate__animated animate__zoomIn" style="animation-delay: 0.2s;">
                <div class="kpi-icon kpi-rose"><i class="bi bi-shield-exclamation"></i></div>
                <div class="kpi-label">System Flags</div>
                <div class="kpi-value">0</div>
            </div>
        </div>
    </div>

    <?php
    $roleBreakdown = $pdo->query("SELECT role, COUNT(*) as cnt FROM users WHERE status = 'active' GROUP BY role ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
    $totalActiveForBreakdown = array_sum(array_column($roleBreakdown, 'cnt'));
    ?>
    <!-- Team Composition -->
    <div class="workspace-card animate__animated animate__fadeInUp" style="animation-delay: 0.25s;">
        <div class="workspace-header">
            <div>
                <h3 class="fw-800 text-dark mb-1 fs-5">Team Composition</h3>
                <p class="text-secondary fw-500 fs-7 mb-0">Active accounts by assigned role.</p>
            </div>
            <span class="badge bg-light text-secondary px-3 py-2 rounded-pill fw-700 fs-7 border">
                <?= (int)$totalActiveForBreakdown ?> Active
            </span>
        </div>
        <?php if (!$roleBreakdown): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                <div class="fw-700 text-dark fs-7 mb-1">No active accounts yet</div>
                <div class="text-secondary fw-500 fs-8">Approved staff will show up here by role.</div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($roleBreakdown as $rb):
                    $roleName = ucwords(str_replace('_', ' ', $rb['role']));
                    $pct = $totalActiveForBreakdown > 0 ? round(($rb['cnt'] / $totalActiveForBreakdown) * 100) : 0;
                ?>
                <div class="role-bar-row">
                    <div class="role-bar-label">
                        <span class="fw-700 text-dark fs-7"><?= htmlspecialchars($roleName) ?></span>
                        <span class="text-secondary fw-600 fs-8"><?= (int)$rb['cnt'] ?> &middot; <?= $pct ?>%</span>
                    </div>
                    <div class="role-bar-track">
                        <div class="role-bar-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 1: Access Requests Workspace -->
    <div class="workspace-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
        <div class="workspace-header">
            <div>
                <h3 class="fw-800 text-dark mb-1 fs-5">Access Requests Pipeline</h3>
                <p class="text-secondary fw-500 fs-7 mb-0">Verify user identity and assign granular security clearances.</p>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-700 fs-7">
                Action Required
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Contact Email</th>
                        <th style="width: 260px;">Role Authorization</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role, status, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
                    $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!$pendingUsers) {
                        echo '<tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-check2-circle"></i></div><div class="fw-700 text-dark fs-7 mb-1">All clear!</div><div class="text-secondary fw-500 fs-8">No pending access requests right now.</div></div></td></tr>';
                    }

                    foreach ($pendingUsers as $u) {
                        $initials = strtoupper($u['first_name'][0] . $u['last_name'][0]);
                        $fullName = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
                        $userId = $u['id'];
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar"><?= $initials ?></div>
                                    <div>
                                        <div class="fw-700 text-dark fs-7"><?= $fullName ?></div>
                                        <div class="text-secondary fs-8 fw-500"><i class="bi bi-clock me-1"></i><?= date('M d, Y', strtotime($u['created_at'])) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary fw-500 fs-7"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <div class="select-pill-wrapper">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <select class="select-pill" id="role_<?= $userId ?>">
                                        <option value="" disabled selected>Assign Security Level</option>
                                        <optgroup label="Operations Core">
                                            <option value="encoder">Encoder</option>
                                            <option value="operation_manager">Operation Manager</option>
                                        </optgroup>
                                        <optgroup label="Financial Audit">
                                            <option value="finance">Finance</option>
                                            <option value="cashier">Cashier</option>
                                            <option value="auditor">Auditor</option>
                                            <option value="cfo">CFO</option>
                                        </optgroup>
                                        <optgroup label="System Admin">
                                            <option value="admin">Administrator</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill fw-700 fs-8">Pending</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-action btn-action-approve" onclick="handleApprove(<?= $userId ?>)" title="Approve">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                    <button class="btn-action btn-action-deny" onclick="confirmAction('/cattleya/admin/delete-user?id=<?= $userId ?>', 'Permanently decline and remove this request?')" title="Deny">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Security Resets Workspace -->
    <div class="workspace-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
        <div class="workspace-header">
            <div>
                <h3 class="fw-800 text-dark mb-1 fs-5">Active Security Resets</h3>
                <p class="text-secondary fw-500 fs-7 mb-0">Manage credential recovery tokens and execute system overrides.</p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Registered Email</th>
                        <th class="text-end">Recovery Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, first_name, last_name, email, reset_token FROM users WHERE reset_token IS NOT NULL ORDER BY id DESC");
                    $resets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!$resets) {
                        echo '<tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-shield-check"></i></div><div class="fw-700 text-dark fs-7 mb-1">No active recovery tokens</div><div class="text-secondary fw-500 fs-8">Password reset requests will appear here.</div></div></td></tr>';
                    }

                    foreach ($resets as $user):
                        $initials = strtoupper($user['first_name'][0] . $user['last_name'][0]);
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);"><?= $initials ?></div>
                                <div class="fw-700 text-dark fs-7"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                            </div>
                        </td>
                        <td class="text-secondary fw-500 fs-7">
                            <?= htmlspecialchars($user['email']) ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#tokenModal<?= $user['id'] ?>" title="Inspect Token">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn-primary-glow" data-bs-toggle="modal" data-bs-target="#resetModal<?= $user['id'] ?>">
                                    Override
                                </button>
                                <button type="button" class="btn-action btn-action-deny" onclick="confirmAction('/cattleya/admin/cancel-reset?id=<?= $user['id'] ?>', 'Revoke this reset token immediately?')" title="Revoke">
                                    <i class="bi bi-shield-x"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modernized Dynamic Modals Container -->
<?php foreach ($resets as $user): ?>
    <!-- Identity Token Inspection Modal -->
    <div class="modal fade" id="tokenModal<?= $user['id'] ?>" tabindex="-1" aria-labelledby="tokenModalLabel<?= $user['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-shield-lock fs-5"></i>
                        </div> 
                        <div>
                            <h5 class="modal-title fw-800 text-dark fs-6 mb-0" id="tokenModalLabel<?= $user['id'] ?>">Identity Token Verification</h5>
                            <span class="text-secondary fs-8 fw-500">Cryptographic recovery key</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3.5 rounded-3 mb-3 border bg-light position-relative">
                        <span class="text-uppercase text-success fw-800 fs-8 d-block mb-1" style="letter-spacing: 0.05em;">Active Recovery Token</span>
                        <code class="fs-6 fw-700 text-dark d-block text-break" id="tokenText<?= $user['id'] ?>" style="letter-spacing: 0.5px; font-family: monospace;">
                            <?= htmlspecialchars($user['reset_token']) ?>
                        </code>
                    </div>
                    <p class="text-secondary fw-500 fs-7 mb-4">Verification token generated for <span class="text-dark fw-700"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>.</p>
                    
                    <button type="button" class="btn-primary-glow w-100 py-2.5 fs-7 d-flex align-items-center justify-content-center gap-2 rounded-3" 
                            onclick="copyToClipboard('tokenText<?= $user['id'] ?>', 'Token copied securely')">
                        <i class="bi bi-clipboard fs-6"></i> Copy Token to Clipboard
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Override Modal -->
    <div class="modal fade" id="resetModal<?= $user['id'] ?>" tabindex="-1" aria-labelledby="resetModalLabel<?= $user['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-key-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-800 text-dark fs-6 mb-0" id="resetModalLabel<?= $user['id'] ?>">System Credential Override</h5>
                            <span class="text-secondary fs-8 fw-500">Forced password reset</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="text-uppercase fw-800 text-secondary fs-8 mb-2 d-block" style="letter-spacing: 0.05em;">Temporary Credential</label>
                        <div class="input-group rounded-3 border overflow-hidden bg-white p-1">
                            <input type="text" id="passInput<?= $user['id'] ?>" 
                                   class="form-control border-0 bg-transparent fw-800 text-center py-2 fs-6 text-dark" 
                                   style="font-family: monospace; letter-spacing: 1px;" 
                                   value="MLINC12345@" readonly>
                            <button type="button" class="btn btn-dark px-3 rounded-2 d-flex align-items-center justify-content-center fw-700 fs-7" 
                                    onclick="copyInputToClipboard('passInput<?= $user['id'] ?>', 'Credential copied securely')">
                                <i class="bi bi-copy me-1"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="p-3 mb-4 rounded-3 d-flex align-items-start" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2);">
                        <i class="bi bi-exclamation-triangle-fill fs-6 me-2.5 text-warning flex-shrink-0 mt-0.5"></i>
                        <span class="fs-8 fw-600 text-dark lh-sm">This action permanently overwrites current credentials for <span class="fw-800"><?= htmlspecialchars($user['first_name']) ?></span>.</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-light w-100 py-2.5 rounded-3 fw-700 text-secondary border fs-7" data-bs-dismiss="modal">Cancel</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-danger w-100 py-2.5 rounded-3 fw-700 fs-7 border-0 text-white"
                                    onclick="resetPassword(<?= $user['id'] ?>)" style="background: #dc2626;">
                                Execute Override
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const swalConfig = {
    customClass: {
        popup: 'sa-modern-popup',
        title: 'sa-title',
        htmlContainer: 'sa-html-container',
        confirmButton: 'sa-btn-confirm mx-1',
        cancelButton: 'sa-btn-cancel mx-1',
    },
    buttonsStyling: false,
    showClass: { popup: 'animate__animated animate__zoomIn animate__faster' },
    hideClass: { popup: 'animate__animated animate__zoomOut animate__faster' }
};

function confirmAction(url, msg) {
    Swal.fire({
        ...swalConfig,
        title: 'Authorization Required',
        text: msg,
        icon: 'warning',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: 'Confirm Execution',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((res) => {
        if (res.isConfirmed) {
            Swal.fire({ 
                ...swalConfig,
                title: 'Processing Request',
                html: '<div class="d-flex align-items-center justify-content-center gap-2 mt-2 text-secondary fs-7"><span class="spinner-border spinner-border-sm text-success" role="status"></span> Synchronizing securely with server database...</div>',
                allowOutsideClick: false, 
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            window.location.href = url;
        }
    });
}

async function resetPassword(id) {
    const formData = new FormData();
    formData.append('user_id', id);
    formData.append('password', 'MLINC12345@');

    try {
        const res = await fetch('/cattleya/admin/reset-password', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire({ 
                ...swalConfig, 
                title: 'Override Complete', 
                text: 'Credentials have been successfully updated in the database.', 
                icon: 'success',
                iconColor: '#10b981',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else { 
            throw new Error(data.error); 
        }
    } catch (err) {
        Swal.fire({ 
            ...swalConfig, 
            title: 'Execution Failed', 
            text: err.message, 
            icon: 'error',
            iconColor: '#ef4444'
        });
    }
}

const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    background: '#0f172a',
    color: '#ffffff',
    iconColor: '#10b981',
    customClass: {
        popup: 'sa-toast-capsule animate__animated animate__slideInUp animate__faster mb-3 me-3'
    }
});

function copyToClipboard(id, msg) {
    const text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text);
    Toast.fire({ icon: 'success', title: msg });
}

function copyInputToClipboard(id, msg) {
    const input = document.getElementById(id);
    input.select();
    navigator.clipboard.writeText(input.value);
    Toast.fire({ icon: 'success', title: msg });
}

function handleApprove(userId) {
    const roleSelect = document.getElementById('role_' + userId);
    const selectedRole = roleSelect.value;

    if (!selectedRole) {
        Toast.fire({
            icon: 'warning',
            title: 'Clearance required: Please assign a role.',
            iconColor: '#f59e0b'
        });
        roleSelect.style.borderColor = '#f59e0b';
        roleSelect.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.15)';
        roleSelect.focus();
        return;
    }

    roleSelect.style.borderColor = '';
    roleSelect.style.boxShadow = '';
    const formattedRole = selectedRole.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    const approveUrl = `/cattleya/admin/approve-user?id=${userId}&role=${selectedRole}`;
    
    confirmAction(approveUrl, `Grant this user "${formattedRole}" clearance?`);
}
</script>
</body>
</html>