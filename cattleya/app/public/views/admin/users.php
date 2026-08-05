<?php
require __DIR__ . '/../../../config/database.php';
require __DIR__ . '/../includes/session_check.php';
$timezone = new DateTimeZone('Asia/Manila'); // PHT
/* ========================================
   REMEMBER TOKEN LOGIN (Logic Unchanged)
======================================== */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE remember_token = ?
        AND status = 'active'
    ");
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

/* ========================================
   AUTH GUARD (Logic Unchanged)
======================================== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login");
    exit;
}

/* ========================================
   FETCH USERS (Logic Unchanged)
======================================== */
$users = $pdo->query("
    SELECT
        id, first_name, last_name, username, email, role, status,
        created_at, last_active, last_logout
    FROM users
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$user_name = $_SESSION['user_name'];
$email     = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>System Users | Cattleya</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="/assets/css/admin/users.css">

<style>
    :root {
        /* NEW COLOR THEME: Emerald & Gold */
        --brand-primary: #065f46; 
        --brand-primary-soft: rgba(6, 95, 70, 0.1);
        --brand-accent: #b45309;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --bg-light: #f8fafc;
        --sidebar-width: 280px; 
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
        overflow-x: hidden;
    }

    /* === PREVENT SIDEBAR OVERLAP === */
    .content-wrapper {
        margin-left: var(--sidebar-width);
        padding: 2.5rem;
        transition: all 0.3s ease;
        min-height: 100vh;
    }

    @media (max-width: 991.98px) {
        .content-wrapper {
            margin-left: 0;
            padding: 1.5rem;
        }
    }

    /* === PREMIUM CARD STYLING === */
    .modern-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.03);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04), 0 4px 6px -2px rgba(0,0,0,0.02);
        padding: 2rem;
    }

    /* === MODERN SEARCH BAR === */
    .search-input-group {
        max-width: 400px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        border-radius: 12px;
    }
    .search-input-group .input-group-text {
        background: #fff;
        border-right: none;
        border-color: #e2e8f0;
        color: #adb5bd;
        padding-left: 1rem;
    }
    .search-input-group .form-control {
        border-left: none;
        border-color: #e2e8f0;
        padding: 0.75rem 1rem 0.75rem 0;
        font-weight: 500;
    }
    .search-input-group .form-control:focus {
        border-color: #e2e8f0;
        box-shadow: none;
    }
    .search-input-group:focus-within {
        border: 1px solid var(--brand-primary);
        box-shadow: 0 0 0 4px var(--brand-primary-soft);
    }
    .search-input-group:focus-within .input-group-text,
    .search-input-group:focus-within .form-control {
        border-color: transparent;
    }

    /* === AVATAR & NAME === */
    .user-avatar-initials {
        width: 45px; height: 45px;
        background: linear-gradient(135deg, var(--brand-primary), #10b981);
        color: #fff; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1rem;
        box-shadow: 0 4px 6px rgba(6, 95, 70, 0.2);
    }

    /* === MODERN TABLE === */
    .table-premium thead th {
        background: transparent;
        color: var(--text-muted);
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .table-premium tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-premium tbody tr {
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .table-premium tbody tr:hover {
        background-color: #f0fdf4; /* Very light emerald tint */
        transform: scale(1.005);
    }

    /* === SOFT BADGES === */
    [class^="badge-soft-"] {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    
    .badge-soft-role {
        background-color: var(--brand-primary-soft);
        color: var(--brand-primary);
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .badge-soft-success { background-color: #dcfce7; color: #166534; }
    .badge-soft-danger { background-color: #fee2e2; color: #991b1b; }
    .badge-soft-warning { background-color: #fef3c7; color: #92400e; }

    /* === BUTTONS === */
    .btn-edit-user {
        background: transparent;
        color: var(--text-muted);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem 0.8rem;
        transition: all 0.2s ease;
    }
    .btn-edit-user:hover {
        background: var(--brand-primary);
        color: #fff;
        border-color: var(--brand-primary);
        transform: translateY(-2px);
    }

    .btn-save-modern {
        background: var(--brand-primary);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        transition: all 0.2s ease;
    }
    .btn-save-modern:hover {
        background: #044e3a;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(6, 95, 70, 0.3);
    }

    /* === MODAL === */
    .modal-content {
        border: none;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
    }
    .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem 2rem;
    }
    .modal-body {
        padding: 2rem;
    }
    .modal-title { font-weight: 800; color: var(--text-dark); }
    .form-label { font-weight: 600; color: #475569; font-size: 0.9rem; }
    .form-control, .form-select {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border-color: #e2e8f0;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 4px var(--brand-primary-soft);
    }

    /* === SWAL OVERRIDE === */
    .rounded-swal-popup {
        border-radius: 20px !important;
    }
</style>
</head>

<body>

<?php require_once __DIR__ . '/../includes/admin/navbar.php'; ?>

<div class="content-wrapper">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-800 mb-1" style="color: var(--text-dark);">System Users</h2>
            <p class="text-muted mb-0">Overview of all registered system administrators and staff</p>
        </div>
    </div>

   <div class="modern-card">
        
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-5">
            <div>
                <span class="badge bg-light text-dark border px-3 py-2 fs-6 rounded-pill fw-600">
                    <i class="bi bi-people-fill me-2" style="color: var(--brand-primary);"></i>
                    <?= count($users) ?> Registered Users
                </span>
            </div>

            <div class="input-group search-input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="userSearch" class="form-control" placeholder="Search users by name, email, or username...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-premium align-middle w-100">
                <thead>
                    <tr>
                        <th class="text-center">#</th> <th class="d-none">ID</th> <th>Full Name</th>
                        <th>Username</th>
                        <th>Email Address</th>
                        <th>Access Role</th>
                        <th>Account Status</th>
                        <th>Activity Logs</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $count ?></td>
                        <td class="d-none"><?= htmlspecialchars($u['id']) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php
                                    $words = explode(" ", $u['first_name'] . ' ' . $u['last_name']);
                                    $initials = "";
                                    foreach ($words as $w) {
                                        if(isset($w[0])) $initials .= $w[0];
                                    }
                                    $initials = strtoupper(substr($initials, 0, 2));
                                ?>
                                <div class="user-avatar-initials"><?= $initials ?></div>
                                <div>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                    <div class="small text-muted">Created: <?= date('M d, Y', strtotime($u['created_at'])) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="fw-600 text-dark">@<?= htmlspecialchars($u['username']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                        
                        <td><span class="badge-soft-role"><?= htmlspecialchars($u['role']) ?></span></td>
                        
                        <td>
                            <?php
                                $statusClass = 'badge-soft-pending';
                                $icon = 'bi-clock';
                                if ($u['status'] == 'active') {
                                    $statusClass = 'badge-soft-success';
                                    $icon = 'bi-check-circle';
                                } elseif ($u['status'] == 'inactive') {
                                    $statusClass = 'badge-soft-danger';
                                    $icon = 'bi-x-circle';
                                }
                            ?>
                            <span class="<?= $statusClass ?>">
                                <i class="bi <?= $icon ?> me-1"></i>
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        
                        <td class="small text-muted">
                            <div class="text-nowrap mb-1">
                                <i class="bi bi-activity text-success me-1"></i>Active: 
                                <?php
                                if ($u['last_active']) {
                                    $dt = new DateTime($u['last_active'], new DateTimeZone('UTC'));
                                    $dt->setTimezone($timezone);
                                    echo $dt->format('M d, Y H:i');
                                } else echo '-';
                                ?>
                            </div>
                            <div class="text-nowrap">
                                <i class="bi bi-door-closed text-danger me-1"></i>Logout: 
                                <?php
                                if ($u['last_logout']) {
                                    $dt = new DateTime($u['last_logout'], new DateTimeZone('UTC'));
                                    $dt->setTimezone($timezone);
                                    echo $dt->format('M d, Y H:i');
                                } else echo '-';
                                ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-edit-user editUserBtn"
                                    data-first="<?= htmlspecialchars($u['first_name']) ?>"
                                    data-last="<?= htmlspecialchars($u['last_name']) ?>"
                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                    data-email="<?= htmlspecialchars($u['email']) ?>"
                                    data-role="<?= $u['role'] ?>"
                                    data-status="<?= $u['status'] ?>"
                                    title="Edit details for <?= htmlspecialchars($u['first_name']) ?>">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php $count++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800">Edit User Details</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">System Role</label>
                            <select class="form-select" name="role" id="edit_role">
                                <option value="encoder">Encoder</option>
                                <option value="admin">Admin</option>
                                <option value="auditor">Auditor</option>
                                <option value="finance">Finance</option>
                                <option value="cfo">CFO</option>
                                <option value="cashier">Cashier</option>
                                <option value="operation_manager">Operation Manager</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 justify-content-end">
                    <button type="button" class="btn btn-link text-muted fw-600 text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-save-modern px-5"><i class="bi bi-check-circle me-1"></i> Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* JS Logic remains exactly as provided */
document.querySelectorAll(".editUserBtn").forEach(btn => {
    btn.addEventListener("click", function () {
        const row = this.closest("tr");
        const userId = row.querySelector("td.d-none").textContent.trim(); 

        document.getElementById("edit_id").value = userId;
        document.getElementById("edit_first").value = this.dataset.first;
        document.getElementById("edit_last").value = this.dataset.last;
        document.getElementById("edit_username").value = this.dataset.username;
        document.getElementById("edit_email").value = this.dataset.email;
        document.getElementById("edit_role").value = this.dataset.role.trim();
        document.getElementById("edit_status").value = this.dataset.status.trim();

        new bootstrap.Modal(document.getElementById("editUserModal")).show();
    });
});

document.getElementById("editUserForm").addEventListener("submit", async function(e){
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const res = await fetch("/auth/update-users", { method: "POST", body: formData });
        const text = await res.text();
        console.log("Raw response:", text);
        const data = JSON.parse(text);

        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "User Updated",
                text: data.message || "User details were successfully updated",
                timer: 1500,
                showConfirmButton: false,
                confirmButtonColor: '#065f46',
                customClass: { popup: 'rounded-swal-popup' }
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.error || "Update failed",
                confirmButtonColor: '#065f46'
            });
        }

    } catch (err) {
        console.error("JS error:", err);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong: " + err.message,
            confirmButtonColor: '#065f46'
        });
    }
});

/* ================= REAL-TIME USER SEARCH ================= */
document.getElementById('userSearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.table-premium tbody tr').forEach(row => {
        const name = row.cells[2].textContent.toLowerCase();
        const username = row.cells[3].textContent.toLowerCase();
        const email = row.cells[4].textContent.toLowerCase();
        row.style.display = (name.includes(filter) || username.includes(filter) || email.includes(filter)) ? '' : 'none';
    });
});
</script>

</body>
</html>