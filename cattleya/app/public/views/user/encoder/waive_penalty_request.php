<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';
$searchTerm = $_GET['search'] ?? '';
$payments = [];

try {
    // Revised JOIN condition to match ALL strict keys to completely eliminate duplicate entries
    $query = "SELECT payments.id, payments.customer_id, payments.block_number, payments.lot_number, 
                     payments.amount_due, payments.penalty_paid, payments.due_date, 
                     payments.waive_reason, payments.request_waive, sales.customer_fullname 
              FROM payments 
              LEFT JOIN sales ON payments.customer_id = sales.customer_id 
                             AND payments.block_number = sales.block_number 
                             AND payments.lot_number = sales.lot_number
              WHERE payments.request_waive = 'Requested'";

    // If there is a search term, add the condition (including full name searching)
    if (!empty($searchTerm)) {
        $query .= " AND (payments.customer_id LIKE :search 
                     OR sales.customer_fullname LIKE :search 
                     OR payments.block_number LIKE :search 
                     OR payments.lot_number LIKE :search)";
    }

    $stmt = $pdo->prepare($query);

    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', '%' . $searchTerm . '%');
    }

    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
}
// 1. Calculate Total Amount for 'Requested' Waives
$stmtSum = $pdo->prepare("SELECT SUM(penalty_paid) as total_penalty FROM payments WHERE request_waive = 'Requested'");
$stmtSum->execute();
$totalPenaltyRow = $stmtSum->fetch(PDO::FETCH_ASSOC);
$totalRequestedAmount = $totalPenaltyRow['total_penalty'] ?? 0;

// 2. Calculate Approval Rate
// (Approved Requests / (Approved + Rejected)) * 100
$stmtRate = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN request_waive = 'Approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN request_waive IN ('Approved', 'Rejected') THEN 1 END) as total_processed
    FROM payments
");
$stmtRate->execute();
$rateData = $stmtRate->fetch(PDO::FETCH_ASSOC);

$approvalRate = 0;
if ($rateData['total_processed'] > 0) {
    $approvalRate = ($rateData['approved_count'] / $rateData['total_processed']) * 100;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waive Requests Management</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
    :root {
        --c-primary: #1c5f66;
        --c-primary-dark: #114146;
        --c-primary-light: #e6f0f1;
        --c-accent: #a6ce39; /* Lime */
        --c-danger: #ef4444;
        --c-success: #10b981;
        --bg-main: #f4f7f9;
        --bg-card: #ffffff;
        --text-main: #334155;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --sidebar-width: 260px;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --transition: all 0.2s ease;
    }

    body {
        background-color: var(--bg-main);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
        margin: 0;
    }

    .main-content {
        margin-left: 0px;
        padding: 2rem;
        transition: var(--transition);
    }

    @media (max-width: 1024px) {
        .main-content { margin-left: 0; padding: 1rem; }
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Section */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--c-primary-dark);
        margin: 0;
    }

    .header-title p {
        color: var(--text-muted);
        margin: 0.25rem 0 0 0;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--bg-card);
        padding: 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .icon-warning { background: #fffbeb; color: #d97706; }
    .icon-primary { background: var(--c-primary-light); color: var(--c-primary); }
    .icon-success { background: #ecfdf5; color: var(--c-success); }

    .stat-info span { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
    .stat-info h3 { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--c-primary-dark); }

    /* Content Card & Toolbar */
    .content-card {
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .toolbar {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex-grow: 1;
        max-width: 400px;
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    .search-box input {
        width: 100%;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #f8fafc;
        outline: none;
        transition: var(--transition);
    }

    /* Revised to Primary Teal */
    .search-box input:focus { 
        border-color: var(--c-primary); 
        background: #fff; 
        box-shadow: 0 0 0 3px var(--c-primary-light); 
    }

    /* Table Styling */
    .table-responsive { width: 100%; overflow-x: auto; }
    
    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        min-width: 800px;
    }

    thead th {
        background: #f8fafc;
        padding: 1rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
    }

    tbody tr { border-bottom: 1px solid var(--border-color); transition: var(--transition); }
    tbody tr:hover { background: #fcfdfe; }

    td { padding: 1rem; vertical-align: middle; }

    .cell-main { display: block; font-weight: 600; color: var(--c-primary-dark); font-size: 0.9rem; }
    .cell-sub { display: block; font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }

    .remark-box {
        max-width: 250px;
        background: #f1f5f9;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.85rem;
        color: var(--text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Buttons & Actions */
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
        border: 1px solid transparent;
    }

    .btn-primary { background: var(--c-primary); color: #fff; }
    .btn-primary:hover { background: var(--c-primary-dark); transform: translateY(-1px); }
    .btn-outline { background: #fff; border-color: var(--border-color); color: var(--text-main); }
    .btn-outline:hover { background: #f8fafc; border-color: var(--text-muted); }

    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-action.approve { background: #ecfdf5; color: var(--c-success); }
    .btn-action.approve:hover { background: var(--c-success); color: #fff; }
    .btn-action.reject { background: #fef2f2; color: var(--c-danger); }
    .btn-action.reject:hover { background: var(--c-danger); color: #fff; }

    /* Remark Cell Styling */
    .remark-container {
        display: flex;
        align-items: center;
        gap: 8px;
        max-width: 300px;
    }

    .remark-text {
        font-size: 0.9rem;
        color: #4b5563;
    }

    /* Themed Badge */
    .badge-see-more {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9; 
        color: var(--c-primary); /* Use Primary Teal */
        border: none;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .badge-see-more i {
        font-size: 0.85rem;
        color: var(--c-accent); /* Use Lime for icon */
    }

    .badge-see-more:hover {
        background: var(--c-primary-light);
        color: var(--c-primary-dark);
        transform: translateY(-1px);
    }

    .badge-see-more:active {
        transform: translateY(0);
    }

    /* Modal Overlay - Teal Tinted */
    .waiveReasonModal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(17, 65, 70, 0.6); /* Primary Dark Teal tint */
        backdrop-filter: blur(8px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    /* Modal Box */
    .waiveReasonModal-content {
        background: #ffffff;
        width: 100%;
        max-width: 480px;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(17, 65, 70, 0.25);
        overflow: hidden;
        animation: modalAppear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalAppear {
        from { transform: scale(0.95) translateY(10px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }

    .waiveReasonModal-header {
        padding: 1.5rem 1.5rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .waiveReasonModal-header h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--c-primary); /* Primary Teal */
        margin: 0;
        display: flex;
        align-items: center;
    }

    .close-modal {
        background: var(--c-primary-light);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--c-primary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .close-modal:hover {
        background: var(--c-accent); /* Lime hover */
        color: var(--c-primary-dark);
    }

    .waiveReasonModal-body {
        padding: 0 1.5rem 1.5rem;
    }

    .customer-ref { 
        font-size: 0.8rem; 
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted); 
        margin-bottom: 0.75rem;
    }

    .reason-bubble {
        background: var(--bg-main);
        padding: 1.25rem;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        border-left: 4px solid var(--c-accent); /* Lime Accent Border */
        line-height: 1.6;
        color: var(--text-main);
        position: relative;
        font-size: 0.95rem;
    }

    .waiveReasonModal-footer {
        padding: 1.25rem 1.5rem;
        background: var(--bg-main);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
    }

    /* Themed Large Button */
    .btn-close-large {
        padding: 0.6rem 1.5rem;
        background: var(--c-primary); /* Primary Teal */
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-close-large:hover {
        background: var(--c-primary-dark);
        box-shadow: 0 10px 15px -3px rgba(17, 65, 70, 0.3);
    }

    /* Premium SweetAlert Global Overrides */
.premium-swal-popup {
    border-radius: 32px !important;
    padding: 2.5rem !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.premium-swal-title {
    font-size: 1.6rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    letter-spacing: -0.025em !important;
}

/* Custom UI Card for Modal Body */
.review-container {
    background: #f8fafc;
    border-radius: 20px;
    padding: 20px;
    margin-top: 1.5rem;
    border: 1px solid #e2e8f0;
    text-align: left;
}

.review-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
    display: block;
    margin-bottom: 2px;
}

.review-value {
    font-size: 1rem;
    font-weight: 500;
    color: #1e293b;
    margin-bottom: 12px;
    display: block;
}

.penalty-highlight {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    padding: 12px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}

.penalty-amount {
    color: #e11d48;
    font-weight: 800;
    font-size: 1.2rem;
}

.premium-btn {
    padding: 12px 32px !important;
    border-radius: 14px !important;
    font-weight: 600 !important;
    font-size: 0.9rem !important;
    transition: all 0.2s ease-in-out !important;
}

.premium-btn:hover {
    transform: translateY(-2px) !important;
    filter: brightness(1.1);
}
</style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        
        <header class="page-header">
            <div class="header-title">
                <h1>Waive Requests Management</h1>
                <p>Review and process penalty waiving requests from customers.</p>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-warning"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <span>Pending Requests</span>
                    <h3><?= count($payments) ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-primary"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span>Total Requested</span>
                    <h3>₱ <?= number_format($totalRequestedAmount, 2) ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-success"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stat-info">
                    <span>Approval Rate</span>
                    <h3><?= number_format($approvalRate, 1) ?>%</h3>
                </div>
            </div>
        </section>

        <div class="content-card">
            <div class="toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="waiveSearch" placeholder="Filter by customer, block, or lot...">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-outline" title="Refresh" onclick="window.location.reload();">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="waiveTable">
                    <thead>
                        <tr>
                            <th>Customer & Property</th>
                            <th>Schedule</th>
                            <th>Balance Info</th>
                            <th>Penalty to Waive</th>
                            <th>Reason for Request</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): 
                            foreach($payments as $payment): ?>
                        <tr>
                            <td>
                                <span class="cell-main">
                                    <i class="bi bi-person-circle text-muted"></i> <?= htmlspecialchars($payment['customer_fullname'] ?? 'N/A') ?>
                                </span>
                                <span class="cell-sub">
                                    Blk <?= htmlspecialchars($payment['block_number'] ?? '-') ?>, Lot <?= htmlspecialchars($payment['lot_number'] ?? '-') ?> (ID: <?= htmlspecialchars($payment['customer_id'] ?? '-') ?>)
                                </span>
                            </td>

                            <td>
                                <span class="cell-main"><?= date('M d, Y', strtotime($payment['due_date'])) ?></span>
                                <span class="cell-sub">Due Date</span>
                            </td>

                            <td>
                                <span class="cell-main" style="color: var(--c-primary-dark);">
                                    ₱ <?= number_format($payment['amount_due'] ?? 0, 2) ?>
                                </span>
                                <span class="cell-sub">Balance Due</span>
                            </td>

                            <td>
                                <span class="cell-main" style="color: #e11d48; font-weight: 800;">
                                    ₱ <?= number_format($payment['penalty_paid'] ?? 0, 2) ?>
                                </span>
                                <span class="cell-sub">Penalty Amount</span>
                                <span class="badge bg-warning text-dark mt-1" style="font-size: 0.75rem; padding: 0.25em 0.5em; border-radius: 4px;">
                                    <?= htmlspecialchars($payment['request_waive']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="remark-container">
                                    <?php 
                                    $reason = $payment['waive_reason'] ?? 'No reason provided.';
                                    $isLong = strlen($reason) > 50;
                                    $displayReason = $isLong ? substr($reason, 0, 50) . '...' : $reason;
                                    ?>
                                    <span class="remark-text">
                                        <i class="bi bi-chat-left-text text-muted me-1"></i>
                                        <?= htmlspecialchars($displayReason) ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <form class="waive-form" style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                        <input type="hidden" name="penalty_amount" value="<?= $payment['penalty_paid'] ?>">
                                        <input type="hidden" name="customer_name" value="<?= htmlspecialchars($payment['customer_fullname'] ?? '') ?>">
                                        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($payment['customer_id'] ?? '') ?>">
                                        <input type="hidden" name="block_number" value="<?= htmlspecialchars($payment['block_number'] ?? '') ?>">
                                        <input type="hidden" name="lot_number" value="<?= htmlspecialchars($payment['lot_number'] ?? '') ?>">
                                        <input type="hidden" name="due_date" value="<?= htmlspecialchars($payment['due_date'] ?? '') ?>">
                                        
                                        <button type="button" class="btn-action reject btn-reject-waiver" title="Reject">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <button type="button" class="btn-action approve btn-approve-waiver" title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;">No requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="waiveReasonModal" class="waiveReasonModal-overlay">
        <div class="waiveReasonModal-content">
            <div class="waiveReasonModal-header">
                <h3><i class="bi bi-chat-square-text-fill text-muted me-2"></i>Review Reason</h3>
                <button class="close-modal" onclick="closeReasonModal()">
                    <i class="bi bi-x-lg" style="font-size: 1rem;"></i>
                </button>
            </div>
            <div class="waiveReasonModal-body">
                <div class="customer-ref">Reference: <span id="modalCustomerId" class="fw-bold text-dark"></span></div>
                <div class="reason-bubble">
                    <p id="fullReasonText" style="margin: 0;"></p>
                </div>
            </div>
            <div class="waiveReasonModal-footer">
                <button class="btn-close-large" onclick="closeReasonModal()">Got it</button>
            </div>
        </div>
    </div>
</main>

<script>
    // Live Search filtering
    document.getElementById('waiveSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#waiveTable tbody tr');

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    function openReasonModal(reason, customerId) {
        const textElement = document.getElementById('fullReasonText');
        const idElement = document.getElementById('modalCustomerId');
        
        textElement.innerText = reason || "No reason provided by the customer.";
        idElement.innerText = customerId || "N/A";
        
        const modal = document.getElementById('waiveReasonModal');
        modal.style.display = 'flex';
        
        // Prevent background scrolling when modal is open
        document.body.style.overflow = 'hidden';
    }

    function closeReasonModal() {
        document.getElementById('waiveReasonModal').style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }

    // Global click listener to close modal
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('waiveReasonModal');
        if (event.target === modal) {
            closeReasonModal();
        }
    });
    document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-action');
    if (!btn) return;

    const form = btn.closest('.waive-form');
    const formData = new FormData(form);
    const isApprove = btn.classList.contains('approve');

    if (isApprove) {
        // --- APPROVAL REVIEW MODAL ---
        // Safely parse the penalty amount, defaulting to 0 if empty
        const rawAmount = formData.get('penalty_amount');
        const amountNum = rawAmount ? parseFloat(rawAmount) : 0;
        const amount = amountNum.toLocaleString(undefined, { minimumFractionDigits: 2 });
        
        const reviewHtml = `
            <div class="review-container" style="text-align: left;">
                <p><b>Customer:</b> ${formData.get('customer_name')}</p>
                <p><b>Property:</b> Blk ${formData.get('block_number')} Lot ${formData.get('lot_number')}</p>
                <p><b>Due Date:</b> ${formData.get('due_date')}</p>
                <hr>
                <p style="color: #be123c; font-size: 1.1rem;"><b>Waivable Penalty: ₱ ${amount}</b></p>
            </div>
        `;

        const result = await Swal.fire({
            title: 'Confirm Approval',
            html: reviewHtml,
            icon: 'info',
            iconColor: '#1c5f66',
            showCancelButton: true,
            confirmButtonText: 'Approve & Waive',
            cancelButtonText: 'Review Later',
            confirmButtonColor: '#1c5f66',
            cancelButtonColor: '#64748b',
            customClass: {
                popup: 'premium-swal-popup',
                title: 'premium-swal-title',
                confirmButton: 'premium-btn',
                cancelButton: 'premium-btn'
            }
        });

        if (result.isConfirmed) submitWaiveAction(form, 'Approved');

    } else {
        // --- REJECTION CONFIRMATION MODAL ---
        const result = await Swal.fire({
            title: 'Decline Request?',
            text: 'This action will reject the waiver and keep the penalty balance active.',
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonText: 'Yes, Decline',
            cancelButtonText: 'Keep Pending',
            confirmButtonColor: '#e11d48',
            customClass: {
                popup: 'premium-swal-popup',
                title: 'premium-swal-title',
                confirmButton: 'premium-btn',
                cancelButton: 'premium-btn'
            }
        });

        if (result.isConfirmed) submitWaiveAction(form, 'Rejected');
    }
});

/**
 * Handles the AJAX submission to PHP
 */
async function submitWaiveAction(formElement, status) {
    const formData = new FormData(formElement);
    formData.append('status', status);

    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        // Make sure this points to the exact path of your PHP file
        const response = await fetch('/cattleya/user/encoder/fetch/process-waive', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message,
                confirmButtonColor: '#1c5f66',
                borderRadius: '20px'
            });
            location.reload(); 
        } else {
            throw new Error(result.message || 'Error processing request');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Failed',
            text: error.message,
            borderRadius: '20px'
        });
    }
}
</script>

</body>
</html>