<?php
require_once __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

$user_role  = strtolower($_SESSION['role'] ?? '');
$is_encoder = ($user_role === 'encoder');

$error_msg = $_GET['error'] ?? '';
$success_msg = $_GET['success'] ?? '';

/**
 * Determine the UI state of a commission item.
 */
function getCommissionState($status, $amount, $rfp_id = null) {
    $amt = (float)($amount ?? 0);
    if ($amt <= 0) return 'none';
    if (strtolower(trim((string)$status)) === 'released') return 'released';
    if (!empty($rfp_id)) return 'pending_approval';
    
    $st = strtolower(trim((string)$status));
    if ($st === 'pending approval' || $st === 'pending_approval') return 'pending_approval';
    
    return 'selectable';
}

// 1. FETCH PENDING TRANSACTIONS (Unprocessed Lines)
try {
    $stmt = $pdo->query("
        SELECT
            p.id AS payment_id, p.customer_id, s.customer_fullname, p.due_date,
            s.agent_fullname, s.broker_fullname, s.um_fullname,
            p.broker_commission_amount, p.um_commission_amount, p.agent_commission_amount,
            p.broker_commission_status, p.um_commission_status, p.agent_commission_status,
            (SELECT ri.rfp_id FROM rfp_request_items ri JOIN rfp_requests rr ON rr.id = ri.rfp_id WHERE ri.payment_id = p.id AND ri.commission_role = 'broker' AND rr.status NOT IN ('rejected') ORDER BY ri.id DESC LIMIT 1) AS broker_rfp_id,
            (SELECT ri.rfp_id FROM rfp_request_items ri JOIN rfp_requests rr ON rr.id = ri.rfp_id WHERE ri.payment_id = p.id AND ri.commission_role = 'um' AND rr.status NOT IN ('rejected') ORDER BY ri.id DESC LIMIT 1) AS um_rfp_id,
            (SELECT ri.rfp_id FROM rfp_request_items ri JOIN rfp_requests rr ON rr.id = ri.rfp_id WHERE ri.payment_id = p.id AND ri.commission_role = 'agent' AND rr.status NOT IN ('rejected') ORDER BY ri.id DESC LIMIT 1) AS agent_rfp_id
        FROM payments p
        JOIN sales s ON p.sale_id = s.sale_id
        WHERE (p.broker_commission_status != 'Released' AND p.broker_commission_amount > 0)
           OR (p.um_commission_status != 'Released' AND p.um_commission_amount > 0)
           OR (p.agent_commission_status != 'Released' AND p.agent_commission_amount > 0)
        ORDER BY p.due_date ASC, s.customer_fullname ASC
    ");
    $pending_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_transactions = [];
    $error_msg = 'Failed to load pending commissions: ' . $e->getMessage();
}

// 2. FETCH GROUPED RFP REQUESTS (Pending & Released Approval)
try {
    $stmt_grouped_rfp = $pdo->query("
        SELECT 
            rr.id AS rfp_id,
            rr.status AS rfp_status,
            rr.master_requisition_slip,
            CONCAT('REQ-COM-', LPAD(rr.id, 6, '0')) AS requisition_slip_no,
            COUNT(ri.id) AS total_items,
            SUM(
                CASE 
                    WHEN ri.commission_role = 'broker' THEN p.broker_commission_amount
                    WHEN ri.commission_role = 'um' THEN p.um_commission_amount
                    WHEN ri.commission_role = 'agent' THEN p.agent_commission_amount
                    ELSE 0
                END
            ) AS rfp_total_amount,
            GROUP_CONCAT(DISTINCT s.customer_fullname SEPARATOR ', ') AS client_names,
            GROUP_CONCAT(DISTINCT 
                CASE 
                    WHEN ri.commission_role = 'broker' THEN s.broker_fullname
                    WHEN ri.commission_role = 'um' THEN s.um_fullname
                    WHEN ri.commission_role = 'agent' THEN s.agent_fullname
                END SEPARATOR ', '
            ) AS payee_names
        FROM rfp_requests rr
        JOIN rfp_request_items ri ON ri.rfp_id = rr.id
        JOIN payments p ON p.id = ri.payment_id
        JOIN sales s ON p.sale_id = s.sale_id
        WHERE LOWER(TRIM(rr.status)) NOT IN ('rejected')
        GROUP BY rr.id, rr.status, rr.master_requisition_slip
        ORDER BY rr.id DESC
    ");
    $raw_grouped_rfps = $stmt_grouped_rfp->fetchAll(PDO::FETCH_ASSOC);

    // Group items sharing the same Requisition Number together
    $consolidated_rfps = [];
    foreach ($raw_grouped_rfps as $grfp) {
        $req_no = !empty($grfp['master_requisition_slip']) ? $grfp['master_requisition_slip'] : $grfp['requisition_slip_no'];
        if (!isset($consolidated_rfps[$req_no])) {
            $consolidated_rfps[$req_no] = $grfp;
            $consolidated_rfps[$req_no]['rfp_ids_array'] = [$grfp['rfp_id']];
        } else {
            $consolidated_rfps[$req_no]['rfp_ids_array'][] = $grfp['rfp_id'];
            $consolidated_rfps[$req_no]['total_items'] += (int)$grfp['total_items'];
            $consolidated_rfps[$req_no]['rfp_total_amount'] += (float)$grfp['rfp_total_amount'];

            // Unique payee list
            $payees = array_filter(array_unique(array_map('trim', explode(',', $consolidated_rfps[$req_no]['payee_names'] . ',' . $grfp['payee_names']))));
            $consolidated_rfps[$req_no]['payee_names'] = implode(', ', $payees);

            // Unique client list
            $clients = array_filter(array_unique(array_map('trim', explode(',', $consolidated_rfps[$req_no]['client_names'] . ',' . $grfp['client_names']))));
            $consolidated_rfps[$req_no]['client_names'] = implode(', ', $clients);
        }
    }
    $grouped_rfps = array_values($consolidated_rfps);
} catch (PDOException $e) {
    $grouped_rfps = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Commission Releases | ML Rental System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root { --c-primary: #1c5f66; --c-primary-light: #2a838d; --c-accent: #a6ce39; --c-bg: #f8fafc; --c-card: #ffffff; --c-text-main: #0f172a; --c-text-sub: #64748b; --c-border: #e2e8f0; --c-success: #10b981; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--c-bg); color: var(--c-text-main); padding-bottom: 120px; }
        
        .clr-layout-wrapper { display: flex; min-height: 100vh; }
        .clr-main-content { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        
        .clr-header-section { width: 100%; max-width: 1240px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
        .clr-header-section h1 { font-size: 24px; font-weight: 800; margin: 0 0 6px 0; color: var(--c-primary); display: flex; align-items: center; gap: 12px; }
        .clr-header-section p { color: var(--c-text-sub); margin: 0; font-size: 14px; }

        /* SEARCH & FILTER INTERFACE (SEI) STYLES */
        .sei-filter-card { background: var(--c-card); width: 100%; max-width: 1240px; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.03); border: 1px solid var(--c-border); margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
        .sei-search-group { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 280px; position: relative; }
        .sei-search-icon { position: absolute; left: 14px; color: var(--c-text-sub); }
        .sei-input { width: 100%; padding: 12px 14px 12px 42px; border-radius: 10px; border: 1px solid var(--c-border); font-family: inherit; font-size: 14px; outline: none; transition: border-color 0.2s; background: #fff; }
        .sei-input:focus { border-color: var(--c-primary); }
        
        .sei-controls { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .sei-select { padding: 12px 16px; border-radius: 10px; border: 1px solid var(--c-border); font-family: inherit; font-size: 13px; font-weight: 600; color: var(--c-text-main); outline: none; background: #fff; cursor: pointer; transition: border-color 0.2s; }
        .sei-select:focus { border-color: var(--c-primary); }
        .sei-btn-reset { padding: 12px 16px; background: #f1f5f9; color: var(--c-text-sub); border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s; }
        .sei-btn-reset:hover { background: #e2e8f0; color: var(--c-text-main); }

        .clr-table-card { background: var(--c-card); width: 100%; max-width: 1240px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); border: 1px solid var(--c-border); overflow: hidden; margin-bottom: 40px; }
        .clr-table { width: 100%; border-collapse: collapse; text-align: left; }
        .clr-table th { background: #f1f5f9; color: var(--c-text-sub); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid var(--c-border); }
        .clr-table td { padding: 16px 20px; border-bottom: 1px solid var(--c-border); vertical-align: middle; }
        .clr-table tbody tr:hover { background: #fafafa; }

        .customer-meta { display: flex; flex-direction: column; gap: 4px; }
        .customer-name { font-weight: 800; font-size: 14px; color: var(--c-text-main); }
        .due-date { font-size: 12px; color: var(--c-text-sub); display: flex; align-items: center; gap: 4px; }

        .role-selector { display: block; cursor: pointer; user-select: none; }
        .role-checkbox { display: none; }
        .role-card { border: 2px solid var(--c-border); border-radius: 12px; padding: 12px; background: #fff; position: relative; transition: all 0.2s; }
        .role-card.static { background: #f8fafc; border-style: dashed; }
        .role-checkbox:checked + .role-card { border-color: var(--c-primary); background: rgba(28, 95, 102, 0.03); }
        .role-checkbox:checked + .role-card .check-icon { opacity: 1; transform: scale(1); }
        
        .check-icon { position: absolute; top: 10px; right: 10px; color: var(--c-primary); opacity: 0; transform: scale(0.5); transition: all 0.2s; }
        .role-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--c-text-sub); display: block; margin-bottom: 2px; }
        .role-name { font-size: 13px; font-weight: 700; color: var(--c-text-main); display: block; margin-bottom: 4px; }
        .role-amount { font-size: 14px; font-weight: 800; color: var(--c-primary); display: block; }

        /* REQUISITION SLIP LINK BUTTON */
        .req-link-btn { background: none; border: none; padding: 0; color: var(--c-primary); font-size: 14px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: underline; font-family: inherit; transition: color 0.2s; }
        .req-link-btn:hover { color: var(--c-primary-light); }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #f1f5f9; color: var(--c-text-sub); }
        .status-badge.released { background: #dcfce7; color: #166534; }
        .status-badge.pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-btn { cursor: pointer; font-family: inherit; transition: all 0.2s ease; border: none; outline: none; }
        .badge-btn:hover { background: #fde68a; transform: translateY(-1px); }
        .status-badge.released.badge-btn:hover { background: #bbf7d0; transform: translateY(-1px); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--c-text-sub); }

        .btn-submit { background: var(--c-primary); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-submit:hover { background: var(--c-primary-light); transform: translateY(-1px); }
        .btn-accent { background: var(--c-accent); color: var(--c-primary); }
        .btn-accent:hover { background: #96bb33; }

        .sticky-footer { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-top: 1px solid var(--c-border); padding: 16px 40px; display: flex; justify-content: center; z-index: 100; transform: translateY(100%); transition: transform 0.3s; }
        .sticky-footer.visible { transform: translateY(0); }
        .footer-content { width: 100%; max-width: 1240px; display: flex; justify-content: space-between; align-items: center; }
        
        .summary-stats { display: flex; gap: 32px; align-items: center; }
        .stat-box { display: flex; flex-direction: column; }
        .stat-label { font-size: 11px; font-weight: 700; color: var(--c-text-sub); text-transform: uppercase; }
        .stat-value { font-size: 20px; font-weight: 800; color: var(--c-primary); }

        .alert-success { background: #dcfce7; color: #166534; padding: 12px 20px; border-radius: 8px; width: 100%; max-width: 1240px; margin-bottom: 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: 12px 20px; border-radius: 8px; width: 100%; max-width: 1240px; margin-bottom: 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="clr-layout-wrapper">
    <main class="clr-main-content">
        
        <?php if ($success_msg): ?>
            <div class="alert-success">
                <i data-lucide="check-circle" size="18"></i> <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert-error">
                <i data-lucide="alert-triangle" size="18"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- ========================================================= -->
        <!-- SEI TOOLBAR: SEARCH & FILTER INTERFACE                    -->
        <!-- ========================================================= -->
        <div class="sei-filter-card">
            <div class="sei-search-group">
                <i data-lucide="search" class="sei-search-icon" size="18"></i>
                <input type="text" id="seiSearchInput" class="sei-input" placeholder="Search client, payee, requisition slip #, agent, broker...">
            </div>
            <div class="sei-controls">
                <select id="seiStatusFilter" class="sei-select">
                    <option value="">All Statuses</option>
                    <option value="pending approval">Pending Approval</option>
                    <option value="selectable">Available / Selectable</option>
                    <option value="released">Released</option>
                </select>
                <button type="button" id="seiResetBtn" class="sei-btn-reset">
                    <i data-lucide="rotate-ccw" size="14"></i> Clear Filters
                </button>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 1. BATCH RFP SECTION (Group & Process)                    -->
        <!-- ========================================================= -->
        <?php if (!empty($grouped_rfps)): ?>
            <div class="clr-header-section">
                <div>
                    <h1><i data-lucide="file-check"></i> Pending RFP Requests</h1>
                    <p>Select multiple requests to group them under a single Requisition Slip, or process them together.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" form="multiRfpForm" name="action" value="load_multiple_rfps" class="btn-submit">
                        <i data-lucide="layers" size="16"></i> Process Selected RFPs
                    </button>
                </div>
            </div>

            <!-- ADDED: Division by Signature Status Condition -->
            <form id="multiRfpForm" method="POST" action="/user/operatoin_manager/rfp-approval-flow">
                <?php 
                $stage_groups = [
                    'encoder'  => [],
                    'auditor'  => [],
                    'cfo'      => [],
                    'released' => []
                ];
                foreach ($grouped_rfps as $grfp) {
                    $stat = strtolower(trim($grfp['rfp_status']));
                    if ($stat === 'pending_audit') {
                        $stage_groups['auditor'][] = $grfp;
                    } elseif ($stat === 'pending_cfo') {
                        $stage_groups['cfo'][] = $grfp;
                    } elseif ($stat === 'released') {
                        $stage_groups['released'][] = $grfp;
                    } else {
                        $stage_groups['encoder'][] = $grfp;
                    }
                }
                ?>
                
                <?php foreach (['encoder' => 'Encoder Stage', 'auditor' => 'Auditor Stage', 'cfo' => 'CFO Stage', 'released' => 'Released Stage'] as $stage_id => $stage_name): ?>
                    <?php if (!empty($stage_groups[$stage_id])): ?>
                        <div class="clr-header-section" style="margin-top: 15px; margin-bottom: 10px;">
                            <h3 style="font-size: 15px; margin: 0; color: var(--c-primary); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="folder-open" size="18"></i> <?= $stage_name ?>
                            </h3>
                        </div>
                        <div class="clr-table-card" style="margin-bottom: 25px;">
                            <table class="clr-table" id="groupedRfpTable_<?= $stage_id ?>">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">Select</th>
                                        <th width="20%">Requisition Slip #</th>
                                        <th width="25%">Payee(s)</th>
                                        <th width="20%">Client / Items</th>
                                        <th width="15%">Total Amount</th>
                                        <th width="15%">Current Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stage_groups[$stage_id] as $grfp): 
                                        $display_status = strtolower(trim($grfp['rfp_status'])) === 'released' ? 'released' : 'pending approval';
                                    ?>
                                        <tr data-status="<?= htmlspecialchars($display_status) ?>">
                                            <td style="text-align: center;">
                                                <?php if ($stage_id === 'released'): ?>
                                                    <i data-lucide="check-circle" size="18" style="color: var(--c-success);"></i>
                                                <?php else: ?>
                                                    <?php foreach ($grfp['rfp_ids_array'] as $idx => $sub_id): ?>
                                                        <input type="checkbox" 
                                                               name="rfp_ids[]" 
                                                               value="<?= (int)$sub_id ?>" 
                                                               class="grp-cb-<?= (int)$grfp['rfp_id'] ?>" 
                                                               style="<?= $idx === 0 ? 'width: 18px; height: 18px; cursor: pointer; accent-color: var(--c-primary);' : 'display: none;' ?>" 
                                                               <?= $idx > 0 ? '' : 'onchange="document.querySelectorAll(\'.grp-cb-' . (int)$grfp['rfp_id'] . '\').forEach(c => c.checked = this.checked)"' ?>>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="submit" form="rfp_load_group_<?= (int)$grfp['rfp_id'] ?>" class="req-link-btn" title="View Requisition Details">
                                                    <i data-lucide="external-link" size="14"></i> <?= htmlspecialchars($grfp['master_requisition_slip'] ?? $grfp['requisition_slip_no']) ?>
                                                </button>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; font-size: 13px;">
                                                    <?= htmlspecialchars($grfp['payee_names'] ?: 'N/A') ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="customer-meta">
                                                    <span class="customer-name"><?= htmlspecialchars($grfp['client_names'] ?: 'Multiple Clients') ?></span>
                                                    <span class="due-date"><?= (int)$grfp['total_items'] ?> Item(s)</span>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="color: var(--c-primary); font-size: 15px;">₱<?= number_format($grfp['rfp_total_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <button type="submit" form="rfp_load_group_<?= (int)$grfp['rfp_id'] ?>" class="status-badge <?= strtolower(trim($grfp['rfp_status'])) === 'released' ? 'released' : 'pending' ?> badge-btn" title="View Details">
                                                    <i data-lucide="<?= strtolower(trim($grfp['rfp_status'])) === 'released' ? 'check' : 'clock' ?>" size="14"></i> 
                                                    <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $grfp['rfp_status']))) ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </form>

            <!-- HIDDEN FORMS FOR GROUPED RFP DETAILS -->
            <?php foreach ($grouped_rfps as $grfp): ?>
                <form id="rfp_load_group_<?= (int)$grfp['rfp_id'] ?>" method="POST" action="/user/operation_manager/rfp-approval-flow" style="display:none;">
                    <input type="hidden" name="action" value="load_multiple_rfps">
                    <input type="hidden" name="rfp_id" value="<?= (int)$grfp['rfp_id'] ?>">
                    <?php foreach (($grfp['rfp_ids_array'] ?? [$grfp['rfp_id']]) as $sub_rfp_id): ?>
                        <input type="hidden" name="rfp_ids[]" value="<?= (int)$sub_rfp_id ?>">
                    <?php endforeach; ?>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ========================================================= -->
        <!-- 2. UNPROCESSED COMMISSIONS SECTION                        -->
        <!-- ========================================================= -->
        <?php if ($is_encoder): ?>
        <div class="clr-header-section">
            <div>
                <h1><i data-lucide="layers"></i> Unprocessed Commission Lines</h1>
                <p>
                    <?php if ($is_encoder): ?>
                        Select available entity commissions to generate an RFP.
                    <?php else: ?>
                        Pending commissions are queued for approval in the Pending RFP Requests section above.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="clr-table-card">
            <form method="POST" action="/user/operation_manager/rfp-approval-flow" id="commissionForm">
                <input type="hidden" name="action" value="generate_flexible_rfp">

                <?php if (empty($pending_transactions)): ?>
                    <div class="empty-state">
                        <i data-lucide="check-circle" size="48" style="color: var(--c-success); margin-bottom: 12px;"></i>
                        <h3>All Commissions Processed</h3>
                        <p>There are currently no pending commissions awaiting release.</p>
                    </div>
                <?php else: ?>
                    <table class="clr-table" id="unprocessedCommissionsTable">
                        <thead>
                            <tr>
                                <th width="28%">Transaction / Client</th>
                                <th width="24%">Broker Commission</th>
                                <th width="24%">Unit Manager Commission</th>
                                <th width="24%">Agent Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_transactions as $row): 
                                $cid = htmlspecialchars($row['customer_id']);
                                $date = htmlspecialchars($row['due_date']);
                                $b_state = getCommissionState($row['broker_commission_status'], $row['broker_commission_amount'], $row['broker_rfp_id'] ?? null);
                                $um_state = getCommissionState($row['um_commission_status'], $row['um_commission_amount'], $row['um_rfp_id'] ?? null);
                                $a_state = getCommissionState($row['agent_commission_status'], $row['agent_commission_amount'], $row['agent_rfp_id'] ?? null);
                                $row_statuses = implode(' ', array_filter([$b_state, $um_state, $a_state]));
                            ?>
                                <tr data-status="<?= htmlspecialchars($row_statuses) ?>">
                                    <td>
                                        <div class="customer-meta">
                                            <span class="customer-name"><?= htmlspecialchars($row['customer_fullname'] ?? 'Unknown Client') ?></span>
                                            <span class="due-date"><i data-lucide="calendar" size="14"></i> <?= date('M d, Y', strtotime($row['due_date'])) ?></span>
                                        </div>
                                    </td>

                                    <!-- BROKER -->
                                    <td>
                                        <?php if ($b_state === 'selectable'): ?>
                                            <?php if ($is_encoder): ?>
                                                <label class="role-selector">
                                                    <input type="checkbox" name="selected_commissions[]" value="<?= $cid ?>|<?= $date ?>|broker" class="role-checkbox" data-amount="<?= $row['broker_commission_amount'] ?>">
                                                    <div class="role-card">
                                                        <i data-lucide="check-circle-2" class="check-icon" size="18"></i>
                                                        <span class="role-title">Broker</span>
                                                        <span class="role-name"><?= htmlspecialchars($row['broker_fullname'] ?? 'N/A') ?></span>
                                                        <span class="role-amount">₱<?= number_format($row['broker_commission_amount'], 2) ?></span>
                                                    </div>
                                                </label>
                                            <?php else: ?>
                                                <div class="role-card static">
                                                    <span class="role-title">Broker</span>
                                                    <span class="role-name"><?= htmlspecialchars($row['broker_fullname'] ?? 'N/A') ?></span>
                                                    <span class="role-amount">₱<?= number_format($row['broker_commission_amount'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($b_state === 'pending_approval'): ?>
                                            <?php if (!empty($row['broker_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_broker" class="status-badge pending badge-btn" title="View Details">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge pending">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($b_state === 'released'): ?>
                                            <?php if (!empty($row['broker_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_broker" class="status-badge released badge-btn" title="View Details">
                                                    <i data-lucide="check" size="14"></i> Released
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge released"><i data-lucide="check" size="14"></i> Released</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="status-badge"><i data-lucide="minus" size="14"></i> N/A</div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- UNIT MANAGER -->
                                    <td>
                                        <?php if ($um_state === 'selectable'): ?>
                                            <?php if ($is_encoder): ?>
                                                <label class="role-selector">
                                                    <input type="checkbox" name="selected_commissions[]" value="<?= $cid ?>|<?= $date ?>|um" class="role-checkbox" data-amount="<?= $row['um_commission_amount'] ?>">
                                                    <div class="role-card">
                                                        <i data-lucide="check-circle-2" class="check-icon" size="18"></i>
                                                        <span class="role-title">Unit Manager</span>
                                                        <span class="role-name"><?= htmlspecialchars($row['um_fullname'] ?? 'N/A') ?></span>
                                                        <span class="role-amount">₱<?= number_format($row['um_commission_amount'], 2) ?></span>
                                                    </div>
                                                </label>
                                            <?php else: ?>
                                                <div class="role-card static">
                                                    <span class="role-title">Unit Manager</span>
                                                    <span class="role-name"><?= htmlspecialchars($row['um_fullname'] ?? 'N/A') ?></span>
                                                    <span class="role-amount">₱<?= number_format($row['um_commission_amount'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($um_state === 'pending_approval'): ?>
                                            <?php if (!empty($row['um_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_um" class="status-badge pending badge-btn" title="View Details">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge pending">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($um_state === 'released'): ?>
                                            <?php if (!empty($row['um_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_um" class="status-badge released badge-btn" title="View Details">
                                                    <i data-lucide="check" size="14"></i> Released
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge released"><i data-lucide="check" size="14"></i> Released</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="status-badge"><i data-lucide="minus" size="14"></i> N/A</div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- AGENT -->
                                    <td>
                                        <?php if ($a_state === 'selectable'): ?>
                                            <?php if ($is_encoder): ?>
                                                <label class="role-selector">
                                                    <input type="checkbox" name="selected_commissions[]" value="<?= $cid ?>|<?= $date ?>|agent" class="role-checkbox" data-amount="<?= $row['agent_commission_amount'] ?>">
                                                    <div class="role-card">
                                                        <i data-lucide="check-circle-2" class="check-icon" size="18"></i>
                                                        <span class="role-title">Agent</span>
                                                        <span class="role-name"><?= htmlspecialchars($row['agent_fullname'] ?? 'N/A') ?></span>
                                                        <span class="role-amount">₱<?= number_format($row['agent_commission_amount'], 2) ?></span>
                                                    </div>
                                                </label>
                                            <?php else: ?>
                                                <div class="role-card static">
                                                    <span class="role-title">Agent</span>
                                                    <span class="role-name"><?= htmlspecialchars($row['agent_fullname'] ?? 'N/A') ?></span>
                                                    <span class="role-amount">₱<?= number_format($row['agent_commission_amount'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($a_state === 'pending_approval'): ?>
                                            <?php if (!empty($row['agent_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_agent" class="status-badge pending badge-btn" title="View Details">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge pending">
                                                    <i data-lucide="clock" size="14"></i> Pending Approval
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($a_state === 'released'): ?>
                                            <?php if (!empty($row['agent_rfp_id'])): ?>
                                                <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_agent" class="status-badge released badge-btn" title="View Details">
                                                    <i data-lucide="check" size="14"></i> Released
                                                </button>
                                            <?php else: ?>
                                                <div class="status-badge released"><i data-lucide="check" size="14"></i> Released</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="status-badge"><i data-lucide="minus" size="14"></i> N/A</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- STICKY SELECTION SUMMARY FOOTER (Encoder Only) -->
<?php if ($is_encoder): ?>
    <div class="sticky-footer" id="stickyFooter">
        <div class="footer-content">
            <div class="summary-stats">
                <div class="stat-box">
                    <span class="stat-label">Commissions Selected</span>
                    <span class="stat-value" id="selectedCount" style="color: var(--c-text-main);">0</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Total RFP Amount</span>
                    <span class="stat-value" id="totalAmount">₱0.00</span>
                </div>
            </div>
            <button type="submit" form="commissionForm" class="btn-submit">
                <i data-lucide="file-text" size="18"></i> Generate Request for Payment
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- HIDDEN FORMS FOR REDIRECTING BADGE CLICKS -->
<?php foreach ($pending_transactions as $row): 
    $cid = htmlspecialchars($row['customer_id']);
    $date = htmlspecialchars($row['due_date']);
    foreach (['broker', 'um', 'agent'] as $r):
        $st = getCommissionState($row[$r . '_commission_status'], $row[$r . '_commission_amount'], $row[$r . '_rfp_id'] ?? null);
        if (($st === 'pending_approval' || $st === 'released') && !empty($row[$r . '_rfp_id'])):
?>
            <form id="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_<?= $r ?>" method="POST" action="/user/operation_manager/rfp-approval-flow">
                <input type="hidden" name="action" value="load_existing_rfp">
                <input type="hidden" name="rfp_id" value="<?= (int)($row[$r . '_rfp_id'] ?? 0) ?>">
                <input type="hidden" name="selected_key" value="<?= $cid ?>|<?= $date ?>|<?= $r ?>">
            </form>
<?php 
        endif;
    endforeach;
endforeach; 
?>

<script>
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = document.querySelectorAll('.role-checkbox');
        const stickyFooter = document.getElementById('stickyFooter');
        const countDisplay = document.getElementById('selectedCount');
        const totalDisplay = document.getElementById('totalAmount');

        // SEI Filter Elements
        const searchInput = document.getElementById('seiSearchInput');
        const statusFilter = document.getElementById('seiStatusFilter');
        const resetBtn = document.getElementById('seiResetBtn');

        function updateSelection() {
            if (!stickyFooter) return;
            let count = 0;
            let total = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    count++;
                    total += parseFloat(cb.getAttribute('data-amount') || 0);
                }
            });

            if (countDisplay) countDisplay.textContent = count;
            if (totalDisplay) totalDisplay.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (count > 0) {
                stickyFooter.classList.add('visible');
            } else {
                stickyFooter.classList.remove('visible');
            }
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));

        // ==========================================
        // SEI Real-Time Search & Filter Functionality
        // ==========================================
        function filterTables() {
            const query = (searchInput.value || '').toLowerCase().trim();
            const selectedStatus = (statusFilter.value || '').toLowerCase().trim();

            const tables = document.querySelectorAll('.clr-table tbody');

            tables.forEach(tbody => {
                const rows = tbody.querySelectorAll('tr');

                rows.forEach(row => {
                    const rowText = row.innerText.toLowerCase();
                    const rowStatusAttr = (row.getAttribute('data-status') || '').toLowerCase();

                    const matchesSearch = !query || rowText.includes(query);
                    const matchesStatus = !selectedStatus || rowStatusAttr.includes(selectedStatus);

                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTables);
        if (statusFilter) statusFilter.addEventListener('change', filterTables);

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                if (statusFilter) statusFilter.value = '';
                filterTables();
            });
        }
    });
</script>
</body>
</html>