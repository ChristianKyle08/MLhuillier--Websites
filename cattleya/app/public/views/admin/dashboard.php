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
    header("Location: /login");
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
    <title>Admin Dashboard | Cattleya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
    :root {
        /* Cattleya Brand Palette Updated */
        --brand-color: #2a6279; /* Cattleya Navy */
        --brand-gradient: linear-gradient(135deg, #2a6279 0%, #1e4a5c 100%);
        --bg-color: #f8fafc;
        --sidebar-width: 280px;
        --card-border: rgba(42, 98, 121, 0.1);
        --text-main: #1e293b;
        --text-muted: #64748b;
        --accent-lime: #9dc44d; /* Cattleya Green */
    }

    body {
        font-family: 'Manrope', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-main);
        letter-spacing: -0.01em;
    }

    /* Layout Customization */
    .content {
        margin-left: var(--sidebar-width);
        padding: 2.5rem;
        transition: all 0.3s ease;
    }

    @media (max-width: 991.98px) { .content { margin-left: 0; padding: 1.5rem; } }

    /* Modern Card & Stat Refinement */
    .modern-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid var(--card-border);
        padding: 1.75rem;
        box-shadow: 0 10px 30px rgba(42, 98, 121, 0.05);
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 20px rgba(42, 98, 121, 0.03);
    }

    .stat-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .stat-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 2px; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--brand-color); }

    /* Table Design */
    .modern-table thead th {
        background: transparent;
        color: var(--brand-color);
        text-transform: uppercase;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        padding: 1rem;
        border-bottom: 1.5px solid var(--card-border);
    }

    .modern-table tbody td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--brand-gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }

    /* Buttons */
    .btn-action-icon {
        width: 36px; height: 36px; border-radius: 10px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .btn-modern-success { background: rgba(157, 196, 77, 0.15); color: #7ea336; }
    .btn-modern-success:hover { background: var(--accent-lime); color: #fff; }
    .btn-modern-danger { background: #feebeb; color: #ee5d50; }
    .btn-modern-danger:hover { background: #ee5d50; color: #fff; }

    /* Badges */
    .badge-soft-pending { background: #fff8e5; color: #ffb800; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: 0.75rem; }
    .badge-soft-role { background: rgba(42, 98, 121, 0.08); color: var(--brand-color); border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: 0.75rem; }

    /* Modal Design */
    .modal-content { border-radius: 24px; border: none; overflow: hidden; }
    .bg-gradient { background: var(--brand-gradient) !important; }
    .token-display-box { background: #f0f7f9; border: 2px dashed var(--brand-color); border-radius: 15px; }

    /* Link and Primary Color overrides */
    .text-primary { color: var(--brand-color) !important; }
    .btn-primary { background-color: var(--brand-color); border-color: var(--brand-color); }
    .btn-primary:hover { background-color: #1e4a5c; border-color: #1e4a5c; }
    /* Container for the select to allow for custom icon placement */
    .role-select-wrapper {
        position: relative;
        max-width: 200px;
    }

    .role-select-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9e9e9e;
        pointer-events: none;
        font-size: 0.9rem;
    }

    /* The actual select styling */
    .modern-role-select {
        padding-left: 35px !important; /* Space for the icon */
        border: 1px solid #e2e8f0;
        border-radius: 10px !important;
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .modern-role-select:hover {
        background-color: #ffffff;
        border-color: #cbd5e1;
    }

    .modern-role-select:focus {
        border-color: #a777e3;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(167, 119, 227, 0.1);
        color: #1e293b;
    }

    /* Style for the options inside */
    .modern-role-select option {
        font-weight: 400;
        color: #1e293b;
    }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../includes/admin/navbar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h2 class="fw-800 mb-1">Dashboard Overview</h2>
            <p class="text-muted fw-500">Welcome back, <span class="text-primary fw-700"><?= htmlspecialchars($user_name) ?></span></p>
        </div>
        <div class="d-none d-md-block">
            <button class="btn btn-white shadow-sm border-0 rounded-4 px-4 py-2 fw-700 text-primary" style="background: white;">
                <i class="bi bi-calendar3 me-2" style="color: var(--accent-lime);"></i> <?= date('M d, Y') ?>
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card animate__animated animate__fadeInUp">
                <div class="stat-icon-wrapper" style="background: rgba(42, 98, 121, 0.1); color: var(--brand-color);"><i class="bi bi-people-fill"></i></div>
                <div><span class="stat-label">Total Users</span><h3 class="stat-value mb-0"><?= $totalUsers ?></h3></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="stat-icon-wrapper" style="background: rgba(157, 196, 77, 0.15); color: var(--accent-lime);"><i class="bi bi-person-plus-fill"></i></div>
                <div><span class="stat-label">New Today</span><h3 class="stat-value mb-0"><?= $newUsers ?></h3></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="stat-icon-wrapper" style="background: #fff8e5; color: #ffb800;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><span class="stat-label">Active Resets</span><h3 class="stat-value mb-0"><?= $resetCount ?></h3></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="stat-icon-wrapper" style="background: #feebeb; color: #ee5d50;"><i class="bi bi-flag-fill"></i></div>
                <div><span class="stat-label">Reports</span><h3 class="stat-value mb-0">0</h3></div>
            </div>
        </div>
    </div>

    <div class="modern-card animate__animated animate__fadeIn">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-800 mb-0" style="color: var(--brand-color);">New User Requests</h5>
            <span class="badge-soft-pending">Action Required</span>
        </div>
        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead>
                    <tr>
                        <th>User Identity</th>
                        <th>Email Address</th>
                        <th style="width: 200px;">Assign Role</th> <th>Status</th>
                        <th class="text-end">Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role, status, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
                    $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!$pendingUsers) {
                        echo '<tr><td colspan="5" class="text-center py-5 text-muted">No pending registrations found.</td></tr>';
                    }

                    foreach ($pendingUsers as $u) {
                        $initials = strtoupper($u['first_name'][0] . $u['last_name'][0]);
                        $fullName = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
                        $userId = $u['id'];
                    ?>
                        <tr>
                            <td>
                                <div class='d-flex align-items-center gap-3'>
                                    <div class='avatar'><?= $initials ?></div>
                                    <span class='fw-700'><?= $fullName ?></span>
                                </div>
                            </td>
                            <td class='text-muted small'><?= htmlspecialchars($u['email']) ?></td>
                            <td class="py-3">
                                <div class="role-select-wrapper">
                                    <i class="bi bi-shield-lock"></i>
                                    
                                    <select class="form-select form-select-sm modern-role-select" id="role_<?= $userId ?>">
                                        <option value="" disabled selected>Assign Role</option>
                                        
                                        <optgroup label="Core Operations">
                                            <option value="encoder">Encoder</option>
                                            <option value="operation_manager">Operation Manager</option>
                                        </optgroup>
                                        
                                        <optgroup label="Financial & Audit">
                                            <option value="finance">Finance</option>
                                            <option value="cashier">Cashier</option>
                                            <option value="auditor">Auditor</option>
                                            <option value="cfo">CFO</option>
                                        </optgroup>
                                        
                                        <optgroup label="System Control">
                                            <option value="admin">Admin</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </td>
                            <td><span class='badge-soft-pending'>Waiting</span></td>
                            <td class='text-end'>
                                <div class='d-flex justify-content-end gap-2'>
                                    <button class='btn-action-icon btn-modern-success' 
                                            onclick="handleApprove(<?= $userId ?>)">
                                        <i class='bi bi-check-lg'></i>
                                    </button>
                                    
                                    <button class='btn-action-icon btn-modern-danger' 
                                            onclick="confirmAction('/cattleya/admin/delete-user?id=<?= $userId ?>', 'Decline this user?')">
                                        <i class='bi bi-trash'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modern-card animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-800 mb-0" style="color: var(--brand-color);">Password Reset Activity</h5>
    </div>
    <div class="table-responsive">
        <table class="table modern-table align-middle">
            <thead>
                <tr>
                    <th>User Identity</th>
                    <th>Email Address</th>
                    <th class="text-end">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT id, first_name, last_name, email, reset_token FROM users WHERE reset_token IS NOT NULL ORDER BY id DESC");
                $resets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!$resets) {
                    echo '<tr><td colspan="3" class="text-center py-5 text-muted">No active reset tokens found.</td></tr>';
                }

                foreach ($resets as $user):
                    $initials = strtoupper($user['first_name'][0] . $user['last_name'][0]);
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar"><?= $initials ?></div>
                            <div class="fw-700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        </div>
                    </td>
                    <td class="text-muted small">
                        <?= htmlspecialchars($user['email']) ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <button class="btn-action-icon border-0 shadow-sm" 
                                    style="background: #eef5f7; color: var(--brand-color); transition: all 0.2s ease;"
                                    onmouseover="this.style.background='var(--brand-color)'; this.style.color='white';"
                                    onmouseout="this.style.background='#eef5f7'; this.style.color='var(--brand-color)';"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#tokenModal<?= $user['id'] ?>"
                                    title="View Token">
                                <i class="bi bi-key-fill"></i>
                            </button>
                            
                            <button class="btn px-3 py-1-5 border-0 shadow-sm fw-800 text-white rounded-3 animate__animated animate__pulse animate__infinite animate__slower" 
                                    style="background: var(--accent-lime); font-size: 0.75rem; letter-spacing: 0.5px; transition: transform 0.2s;"
                                    onmouseover="this.style.transform='scale(1.05)';"
                                    onmouseout="this.style.transform='scale(1)';"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resetModal<?= $user['id'] ?>">
                                RESET
                            </button>
                            
                            <button class="btn-action-icon border-0 shadow-sm" 
                                    style="background: #feebeb; color: #ee5d50; transition: all 0.2s ease;"
                                    onmouseover="this.style.background='#ee5d50'; this.style.color='white';"
                                    onmouseout="this.style.background='#feebeb'; this.style.color='#ee5d50';"
                                    onclick="confirmAction('/cattleya/admin/cancel-reset?id=<?= $user['id'] ?>', 'Revoke this reset token?')"
                                    title="Revoke Token">
                                <i class="bi bi-x-circle-fill"></i>
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
<?php foreach ($resets as $user): ?>
    <div class="modal fade" id="tokenModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden animate__animated animate__zoomIn animate__faster" style="border-radius: 24px;">
                <div class="modal-header border-0 p-4" style="background: var(--brand-gradient);">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                            <i class="bi bi-shield-lock-fill text-white fs-4"></i>
                        </div>
                        <h5 class="modal-title fw-800 text-white mb-0">Verification Token</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="text-muted fw-500 mb-4">Provide this secure token to <span class="text-dark fw-700"><?= htmlspecialchars($user['first_name']) ?></span> for manual identity verification.</p>
                    
                    <div class="position-relative mb-4">
                        <div class="p-4 rounded-4" style="background: #f0f7f9; border: 2px dashed var(--brand-color);">
                            <code class="fs-2 fw-800 text-primary d-block" id="tokenText<?= $user['id'] ?>" style="letter-spacing: 2px;">
                                <?= htmlspecialchars($user['reset_token']) ?>
                            </code>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" 
                            onclick="copyToken('tokenText<?= $user['id'] ?>')">
                        <i class="bi bi-clipboard2-check-fill"></i> Copy to Clipboard
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resetModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden animate__animated animate__zoomIn animate__faster" style="border-radius: 24px;">
                <div class="modal-header border-0 p-4" style="background: var(--brand-gradient);">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                            <i class="bi bi-key-fill text-white fs-4"></i>
                        </div>
                        <h5 class="modal-title fw-800 text-white mb-0">System Override</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4 text-center">
                        <h6 class="text-uppercase fw-800 text-muted small mb-3" style="letter-spacing: 1px;">Generated Password</h6>
                        <div class="input-group">
                            <input type="text" id="passInput<?= $user['id'] ?>" 
                                   class="form-control form-control-lg border-0 bg-light fw-bold text-center py-3" 
                                   style="border-radius: 12px 0 0 12px; color: var(--brand-color);" 
                                   value="MLINC12345@" readonly>
                            <button class="btn btn-dark px-4" style="border-radius: 0 12px 12px 0;" 
                                    onclick="copyPassword('passInput<?= $user['id'] ?>')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="alert border-0 p-3 mb-4 d-flex align-items-center" 
                         style="background: rgba(157, 196, 77, 0.12); border-radius: 15px;">
                        <i class="bi bi-info-circle-fill text-success fs-4 me-3"></i>
                        <span class="small fw-600 text-dark">This action updates the database instantly. Please ensure the user is notified of their new credentials.</span>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-light w-100 py-3 rounded-4 fw-bold text-secondary" data-bs-dismiss="modal">Keep Current</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow" 
                                    style="background-color: var(--brand-color);"
                                    onclick="resetPassword(<?= $user['id'] ?>)">
                                Confirm Update
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

<style>
    /* Custom CSS to inject hover effects that JS can't handle alone */
    .swal2-styled.swal2-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(42, 98, 121, 0.3) !important;
    }
    .swal2-styled.swal2-cancel:hover {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
    }
    .cattleya-swal-popup {
        border: 1px solid rgba(42, 98, 121, 0.1) !important;
        backdrop-filter: blur(8px);
    }
</style>

<script>
const swalModern = {
    customClass: {
        popup: 'cattleya-swal-popup shadow-lg rounded-5 p-4',
        title: 'fw-800 text-dark fs-3 mb-2',
        htmlContainer: 'text-muted fs-6 fw-500',
        confirmButton: 'btn btn-primary btn-lg px-5 py-2-5 rounded-4 fw-bold mx-2 shadow-sm transition-all',
        cancelButton: 'btn btn-light btn-lg px-5 py-2-5 rounded-4 fw-bold mx-2 text-secondary transition-all',
        loader: 'text-primary'
    },
    buttonsStyling: false,
    showClass: { 
        popup: 'animate__animated animate__zoomIn animate__faster' 
    },
    hideClass: { 
        popup: 'animate__animated animate__zoomOut animate__faster' 
    }
};

function confirmAction(url, msg) {
    Swal.fire({
        ...swalModern,
        title: 'Are you sure?',
        text: msg,
        icon: 'warning',
        iconColor: '#2a6279',
        showCancelButton: true,
        confirmButtonText: 'Yes, Proceed',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true
    }).then((res) => {
        if (res.isConfirmed) {
            Swal.fire({ 
                ...swalModern,
                title: 'Updating...',
                html: '<div class="py-3">Please wait while we sync with the server.</div>',
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
                ...swalModern, 
                title: 'Reset Successful', 
                text: 'Credentials have been updated.', 
                icon: 'success',
                iconColor: '#9dc44d',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else { 
            throw new Error(data.error); 
        }
    } catch (err) {
        Swal.fire({ 
            ...swalModern, 
            title: 'System Error', 
            text: err.message, 
            icon: 'error',
            iconColor: '#ee5d50'
        });
    }
}

/**
 * Cattleya Toast Notification
 */
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: '#2a6279',
    color: '#fff',
    iconColor: '#9dc44d',
    customClass: {
        popup: 'rounded-4 shadow-lg animate__animated animate__fadeInRight animate__faster border-0'
    },
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function copyToken(id) {
    const text = document.getElementById(id).textContent;
    navigator.clipboard.writeText(text);
    Toast.fire({ 
        icon: 'success', 
        title: 'Token copied to clipboard',
        background: '#1e4a5c' // Slightly darker for contrast
    });
}

function copyPassword(id) {
    const input = document.getElementById(id);
    input.select();
    navigator.clipboard.writeText(input.value);
    Toast.fire({ 
        icon: 'success', 
        title: 'Password copied!',
        background: '#9dc44d',
        color: '#1e4a5c'
    });
}

function handleApprove(userId) {
    // Get the specific dropdown for this user
    const roleSelect = document.getElementById('role_' + userId);
    const selectedRole = roleSelect.value;

    // Validation: Check if role is empty
    if (!selectedRole) {
        // You can replace this with a prettier toast notification if you use them
        alert("Please select a System Role before approving the user.");
        roleSelect.classList.add('is-invalid'); // Optional: visual feedback
        roleSelect.focus();
        return;
    }

    // Role is selected: remove invalid class if it was there
    roleSelect.classList.remove('is-invalid');

    // Construct the URL with both ID and Role
    const approveUrl = `/cattleya/admin/approve-user?id=${userId}&role=${selectedRole}`;
    
    // Call your existing confirmAction logic
    confirmAction(approveUrl, `Approve this user as ${selectedRole.replace('_', ' ')}?`);
}
</script>
</body>
</html>