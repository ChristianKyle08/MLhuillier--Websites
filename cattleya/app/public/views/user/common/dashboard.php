<?php
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

// -----------------------------------------------------------------------------
// CURRENT USER (drives the personalized header + role-specific banner below)
// -----------------------------------------------------------------------------
// This same dashboard.php is shared by auditor, cashier, and cfo - it can't
// assume which one is actually logged in, so it looks the user up instead
// of hardcoding a role/name in the markup.
$currentUser = ['first_name' => '', 'last_name' => '', 'role' => '', 'signature' => null];
try {
    $stmtCurrentUser = $pdo->prepare("SELECT first_name, last_name, role, signature FROM users WHERE id = ?");
    $stmtCurrentUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtCurrentUser->fetch(PDO::FETCH_ASSOC) ?: $currentUser;
} catch (Exception $e) {
    error_log("Current User Fetch Error: " . $e->getMessage());
}

$roleLabels = [
    'admin'             => 'Administrator',
    'encoder'           => 'Encoder',
    'cashier'           => 'Cashier',
    'auditor'           => 'Auditor',
    'finance'           => 'Finance',
    'cfo'               => 'CFO',
    'operation_manager' => 'Operations Manager',
];
$currentRole      = $currentUser['role'] ?? '';
$currentRoleLabel = $roleLabels[$currentRole] ?? ucfirst(str_replace('_', ' ', $currentRole ?: 'Staff'));
$currentUserName  = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
if ($currentUserName === '') {
    $currentUserName = $_SESSION['username'] ?? 'there';
}

// Auditor and CFO are 2 of the 3 RFP approval-chain roles (encoder being the
// third, on the other dashboard variant) - both need a signature on file
// before they can sign off on anything in rfp_approval_flow.php.
$needsSignatureReminder = in_array($currentRole, ['auditor', 'cfo'], true) && empty($currentUser['signature']);

// -----------------------------------------------------------------------------
// DASHBOARD METRICS & READ-ONLY DATA FETCHING
// -----------------------------------------------------------------------------

// 1. Today's Collections
$todayCollections = 0.00;
try {
    $stmtToday = $pdo->prepare("
        SELECT SUM(amount_paid) as total 
        FROM payments 
        WHERE DATE(payment_date) = CURRENT_DATE()
    ");
    $stmtToday->execute();
    $todayCollections = (float)($stmtToday->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    error_log("Today Collections Error: " . $e->getMessage());
}

// 2. Monthly Collections
$monthlyCollections = 0.00;
try {
    $stmtMonth = $pdo->prepare("
        SELECT SUM(amount_paid) as total 
        FROM payments 
        WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
          AND YEAR(payment_date) = YEAR(CURRENT_DATE())
    ");
    $stmtMonth->execute();
    $monthlyCollections = (float)($stmtMonth->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    error_log("Monthly Collections Error: " . $e->getMessage());
}

// 3. Total Overdue Accounts Count
$overdueCount = 0;
try {
    $stmtDue = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM payments 
        WHERE due_date < CURRENT_DATE() 
          AND status NOT IN ('fully paid', 'Paid')
    ");
    $stmtDue->execute();
    $overdueCount = (int)($stmtDue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    error_log("Overdue Count Error: " . $e->getMessage());
}

// 4. Pending Availed Services Count
$pendingServicesCount = 0;
try {
    $stmtPending = $pdo->prepare("SELECT COUNT(*) as total FROM avail_services WHERE status = 'Pending'");
    $stmtPending->execute();
    $pendingServicesCount = (int)($stmtPending->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    error_log("Pending Services Error: " . $e->getMessage());
}

// 5. Recent Payment Logs (Read-only feed)
$recentPayments = [];
try {
    $stmtPayments = $pdo->prepare("
        SELECT p.*, s.customer_fullname 
        FROM payments p
        LEFT JOIN sales s ON p.sale_id = s.sale_id
        ORDER BY p.created_at DESC 
        LIMIT 6
    ");
    $stmtPayments->execute();
    $recentPayments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Recent Payments Query Error: " . $e->getMessage());
}

// 6. Overdue Receivables Feed
$overdueList = [];
try {
    $stmtOverdueList = $pdo->prepare("
        SELECT p.*, s.customer_fullname, s.mobile_number 
        FROM payments p
        LEFT JOIN sales s ON p.sale_id = s.sale_id
        WHERE p.due_date < CURRENT_DATE() 
          AND p.status NOT IN ('fully paid', 'Paid')
        ORDER BY p.due_date ASC 
        LIMIT 6
    ");
    $stmtOverdueList->execute();
    $overdueList = $stmtOverdueList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Overdue List Error: " . $e->getMessage());
}

// 7. Recent Availed Services Information
$recentAvailedServices = [];
try {
    $stmtServices = $pdo->prepare("
        SELECT * FROM avail_services 
        ORDER BY date_avail DESC 
        LIMIT 6
    ");
    $stmtServices->execute();
    $recentAvailedServices = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Availed Services Error: " . $e->getMessage());
}

// 8. Available Inventory Summary
$productSummary = [];
try {
    $stmtProducts = $pdo->prepare("
        SELECT product_name, block_number, lot_number, niche_type, tcp, status 
        FROM product 
        ORDER BY product_id DESC 
        LIMIT 6
    ");
    $stmtProducts->execute();
    $productSummary = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Product Summary Error: " . $e->getMessage());
}
?>
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header Banner -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h3 class="fw-800 text-dark mb-1" style="letter-spacing: -0.02em;">
                Welcome back, <?= htmlspecialchars($currentUserName) ?>
            </h3>
            <p class="text-muted mb-0 small">
                <span class="badge bg-primary-soft text-primary rounded-pill px-2 py-1 me-2"><?= htmlspecialchars($currentRoleLabel) ?></span>
                Real-time system transaction logs, payment metrics, and service records.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-white text-secondary shadow-sm px-3 py-2 border rounded-pill font-monospace">
                <i class="bi bi-clock-history text-primary me-1"></i>
                <?= date('F j, Y | h:i A') ?>
            </span>
        </div>
    </div>

    <?php if ($needsSignatureReminder): ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between shadow-sm rounded-4 mb-4" role="alert">
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Action needed:</strong> You haven't uploaded a digital signature yet. As <?= htmlspecialchars($currentRoleLabel) ?>, you'll need one on file to sign off on RFP approvals.
        </div>
        <a href="/cattleya/user/signature" class="btn btn-sm btn-warning text-dark fw-600 flex-shrink-0 ms-3">Upload Signature</a>
    </div>
    <?php endif; ?>


    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Today's Collections -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative metric-card">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-uppercase fw-700 text-muted small tracking-wide">Today's Total</span>
                        <div class="icon-shape bg-primary-soft text-primary rounded-3">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-800 text-dark mb-1">₱<?= number_format($todayCollections, 2) ?></h3>
                    <span class="text-muted small fs-7"><i class="bi bi-calendar-check me-1"></i>Collections logged today</span>
                </div>
            </div>
        </div>

        <!-- Metric 2: Monthly Collections -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative metric-card">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-uppercase fw-700 text-muted small tracking-wide">Monthly Revenue</span>
                        <div class="icon-shape bg-success-soft text-success rounded-3">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-800 text-dark mb-1">₱<?= number_format($monthlyCollections, 2) ?></h3>
                    <span class="text-muted small fs-7"><i class="bi bi-calendar-month me-1"></i>Current month totals</span>
                </div>
            </div>
        </div>

        <!-- Metric 3: Overdue Accounts -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative metric-card">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-uppercase fw-700 text-muted small tracking-wide">Overdue Accounts</span>
                        <div class="icon-shape bg-danger-soft text-danger rounded-3">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-800 text-dark mb-1"><?= number_format($overdueCount) ?></h3>
                    <span class="text-muted small fs-7"><i class="bi bi-clock me-1"></i>Past payment due date</span>
                </div>
            </div>
        </div>

        <!-- Metric 4: Pending Services -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative metric-card">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-uppercase fw-700 text-muted small tracking-wide">Pending Services</span>
                        <div class="icon-shape bg-warning-soft text-warning rounded-3">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-800 text-dark mb-1"><?= number_format($pendingServicesCount) ?></h3>
                    <span class="text-muted small fs-7"><i class="bi bi-tools me-1"></i>Awaiting processing</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Overview Grid Section 1 -->
    <div class="row g-4 mb-4">
        <!-- Recent Payments Feed -->
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-700 text-dark mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Recent Collections</h5>
                        <p class="text-muted small mb-0">Latest payment transactions processed in system</p>
                    </div>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2 font-monospace">Read-Only</span>
                </div>
                <div class="card-body px-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Receipt / Ref</th>
                                    <th>Customer</th>
                                    <th>Amount Paid</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPayments)): ?>
                                    <?php foreach ($recentPayments as $pay): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-700 text-dark">
                                                    <?= htmlspecialchars($pay['or_number'] ?: ($pay['ar_number'] ?: '#' . $pay['id'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-600 text-secondary">
                                                    <?= htmlspecialchars($pay['customer_fullname'] ?: ($pay['customer_id'] ?: 'N/A')) ?>
                                                </span>
                                            </td>
                                            <td class="fw-700 text-emerald">
                                                ₱<?= number_format((float)$pay['amount_paid'], 2) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= htmlspecialchars($pay['payment_method'] ?? 'Cash') ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= $pay['payment_date'] ? date('M d, Y', strtotime($pay['payment_date'])) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <?php
                                                $pStatus = strtolower($pay['status'] ?? '');
                                                $badgeClass = 'bg-secondary';
                                                if (in_array($pStatus, ['paid', 'fully paid'])) {
                                                    $badgeClass = 'bg-success-soft text-success';
                                                } elseif ($pStatus === 'partial') {
                                                    $badgeClass = 'bg-info-soft text-info';
                                                } elseif ($pStatus === 'pending') {
                                                    $badgeClass = 'bg-warning-soft text-warning';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?> px-2 py-1">
                                                    <?= ucfirst(htmlspecialchars($pay['status'] ?? 'N/A')) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No recent payment logs available.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Receivables Alert -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-700 text-dark mb-1"><i class="bi bi-bell me-2 text-danger"></i>Overdue Receivables</h5>
                        <p class="text-muted small mb-0">Accounts with pending overdue balances</p>
                    </div>
                    <span class="badge bg-danger-soft text-danger rounded-pill px-3 py-1">Notice</span>
                </div>
                <div class="card-body px-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Block / Lot</th>
                                    <th>Amount Due</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($overdueList)): ?>
                                    <?php foreach ($overdueList as $due): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-700 text-dark">
                                                    <?= htmlspecialchars($due['customer_fullname'] ?: 'Customer #' . $due['customer_id']) ?>
                                                </div>
                                                <small class="text-muted fs-8"><?= htmlspecialchars($due['mobile_number'] ?? 'No contact') ?></small>
                                            </td>
                                            <td class="small font-monospace">
                                                B<?= htmlspecialchars($due['block_number'] ?? '-') ?> / L<?= htmlspecialchars($due['lot_number'] ?? '-') ?>
                                            </td>
                                            <td class="fw-700 text-danger">
                                                ₱<?= number_format((float)$due['amount_due'], 2) ?>
                                            </td>
                                            <td class="small text-danger fw-600">
                                                <?= date('M d, Y', strtotime($due['due_date'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No overdue payment records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Overview Grid Section 2 -->
    <div class="row g-4">
        <!-- Availed Services Log -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-700 text-dark mb-1"><i class="bi bi-card-checklist me-2 text-info"></i>Availed Services Log</h5>
                    <p class="text-muted small mb-0">List of requested services and reservation details</p>
                </div>
                <div class="card-body px-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Customer</th>
                                    <th>Fee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentAvailedServices)): ?>
                                    <?php foreach ($recentAvailedServices as $srv): ?>
                                        <tr>
                                            <td class="fw-700 text-dark">
                                                <?= htmlspecialchars($srv['services_name']) ?>
                                            </td>
                                            <td class="small text-secondary">
                                                <?= htmlspecialchars($srv['customer_fullname']) ?>
                                            </td>
                                            <td class="fw-700 text-dark">
                                                ₱<?= number_format((float)$srv['fee'], 2) ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= date('M d, Y', strtotime($srv['date_avail'])) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $sStat = strtolower($srv['status']);
                                                $sBadge = 'bg-secondary';
                                                if ($sStat === 'completed' || $sStat === 'approved') {
                                                    $sBadge = 'bg-success-soft text-success';
                                                } elseif ($sStat === 'pending') {
                                                    $sBadge = 'bg-warning-soft text-warning';
                                                } elseif ($sStat === 'cancelled') {
                                                    $sBadge = 'bg-danger-soft text-danger';
                                                }
                                                ?>
                                                <span class="badge <?= $sBadge ?> px-2 py-1">
                                                    <?= ucfirst(htmlspecialchars($srv['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No availed services recorded.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Inventory Summary -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-700 text-dark mb-1"><i class="bi bi-box-seam me-2 text-warning"></i>Product Catalog Overview</h5>
                    <p class="text-muted small mb-0">Overview of lot inventory and product pricing</p>
                </div>
                <div class="card-body px-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Block / Lot</th>
                                    <th>Niche Type</th>
                                    <th>TCP</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($productSummary)): ?>
                                    <?php foreach ($productSummary as $prod): ?>
                                        <tr>
                                            <td class="fw-700 text-dark">
                                                <?= htmlspecialchars($prod['product_name']) ?>
                                            </td>
                                            <td class="small font-monospace">
                                                B<?= htmlspecialchars($prod['block_number'] ?? '-') ?> / L<?= htmlspecialchars($prod['lot_number'] ?? '-') ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= htmlspecialchars($prod['niche_type'] ?? 'N/A') ?>
                                            </td>
                                            <td class="fw-700 text-dark">
                                                ₱<?= number_format((float)$prod['tcp'], 2) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $prodStat = strtolower($prod['status']);
                                                $prodBadge = 'bg-success-soft text-success';
                                                if ($prodStat === 'sold') {
                                                    $prodBadge = 'bg-danger-soft text-danger';
                                                } elseif ($prodStat === 'reserved') {
                                                    $prodBadge = 'bg-warning-soft text-warning';
                                                }
                                                ?>
                                                <span class="badge <?= $prodBadge ?> px-2 py-1">
                                                    <?= ucfirst(htmlspecialchars($prod['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No products recorded in database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling & Custom Themes for Cashier Dashboard */
    .fw-800 { font-weight: 800; }
    .fw-700 { font-weight: 700; }
    .fw-600 { font-weight: 600; }
    .fs-7 { font-size: 0.8rem; }
    .fs-8 { font-size: 0.725rem; }
    .tracking-wide { letter-spacing: 0.05em; }

    .text-emerald { color: #10b981; }

    /* Soft Accent Background Colors */
    .bg-primary-soft { background-color: rgba(42, 98, 121, 0.12); }
    .bg-success-soft { background-color: rgba(16, 185, 129, 0.12); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.12); }
    .bg-warning-soft { background-color: rgba(245, 158, 11, 0.12); }
    .bg-info-soft { background-color: rgba(14, 165, 233, 0.12); }

    .icon-shape {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .metric-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.07) !important;
    }

    /* Custom Table Styling */
    .custom-table thead th {
        font-size: 0.725rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #64748b;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 14px;
    }
    .custom-table tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
    }
    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }
</style>