<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

// Determine logged-in user role
$user_role  = strtolower($_SESSION['role'] ?? '');
$is_encoder = ($user_role === 'encoder');

// --- FETCH ALL UNRELEASED COMMISSIONS DIRECTLY ---
try {
    $stmt = $pdo->query("
        SELECT p.customer_id, s.customer_fullname, p.due_date, 
               s.agent_fullname, s.broker_fullname, s.um_fullname, 
               p.broker_commission_amount, p.um_commission_amount, p.agent_commission_amount,
               p.broker_commission_status, p.um_commission_status, p.agent_commission_status
        FROM payments p
        JOIN sales s ON p.sale_id = s.sale_id
        WHERE 
            (p.broker_commission_status != 'Released' AND p.broker_commission_amount > 0) OR 
            (p.um_commission_status != 'Released' AND p.um_commission_amount > 0) OR 
            (p.agent_commission_status != 'Released' AND p.agent_commission_amount > 0)
        ORDER BY p.due_date ASC, s.customer_fullname ASC
    ");
    $pending_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_transactions = [];
    $error_msg = "Failed to load pending commissions: " . $e->getMessage();
}

/**
 * Determine display state for a role's commission
 * Returns: 'selectable', 'pending_approval', 'released', or 'none'
 */
function getCommissionState($status, $amount) {
    $amt = (float)($amount ?? 0);
    if ($amt <= 0) {
        return 'none';
    }
    $st = strtolower(trim($status ?? ''));
    if ($st === 'released') {
        return 'released';
    }
    if ($st === 'pending approval' || $st === 'pending_approval') {
        return 'pending_approval';
    }
    return 'selectable';
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
        :root { 
            --c-primary: #1c5f66; 
            --c-primary-light: #2a838d;
            --c-accent: #a6ce39; 
            --c-bg: #f8fafc; 
            --c-card: #ffffff;
            --c-text-main: #0f172a;
            --c-text-sub: #64748b;
            --c-border: #e2e8f0;
            --c-success: #10b981;
            --c-warning: #d97706;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--c-bg); color: var(--c-text-main); padding-bottom: 120px; }
        
        .clr-layout-wrapper { display: flex; min-height: 100vh; }
        .clr-main-content { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        
        .clr-header-section { width: 100%; max-width: 1240px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .clr-header-section h1 { font-size: 28px; font-weight: 800; margin: 0 0 6px 0; color: var(--c-primary); display: flex; align-items: center; gap: 12px; }
        .clr-header-section p { color: var(--c-text-sub); margin: 0; font-size: 14px; }

        .clr-table-card { background: var(--c-card); width: 100%; max-width: 1240px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); border: 1px solid var(--c-border); overflow: hidden; }
        .clr-table { width: 100%; border-collapse: collapse; text-align: left; }
        .clr-table th { background: #f1f5f9; color: var(--c-text-sub); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 18px 24px; border-bottom: 1px solid var(--c-border); }
        .clr-table td { padding: 18px 24px; border-bottom: 1px solid var(--c-border); vertical-align: middle; }
        .clr-table tbody tr:last-child td { border-bottom: none; }
        .clr-table tbody tr:hover { background: #fafafa; }

        .customer-meta { display: flex; flex-direction: column; gap: 4px; }
        .customer-name { font-weight: 800; font-size: 15px; color: var(--c-text-main); }
        .due-date { font-size: 13px; color: var(--c-text-sub); display: flex; align-items: center; gap: 6px; }

        .role-selector { display: block; cursor: pointer; user-select: none; }
        .role-checkbox { display: none; }
        .role-card { border: 2px solid var(--c-border); border-radius: 12px; padding: 12px 14px; background: #fff; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
        .role-card.static { background: #f8fafc; border-style: dashed; }
        .role-card:hover:not(.static) { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        
        .role-checkbox:checked + .role-card { border-color: var(--c-primary); background: rgba(28, 95, 102, 0.03); box-shadow: 0 4px 12px rgba(28, 95, 102, 0.08); }
        .role-checkbox:checked + .role-card .check-icon { opacity: 1; transform: scale(1); }
        
        .check-icon { position: absolute; top: 12px; right: 12px; color: var(--c-primary); opacity: 0; transform: scale(0.5); transition: all 0.2s; }
        
        .role-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--c-text-sub); letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
        .role-name { font-size: 13px; font-weight: 700; color: var(--c-text-main); display: block; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px; }
        .role-amount { font-size: 14px; font-weight: 800; color: var(--c-primary); display: block; }

        /* Status Badges & Clickable RFP Form Buttons */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #f1f5f9; color: var(--c-text-sub); }
        .status-badge.released { background: #dcfce7; color: #166534; }
        .status-badge.pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        
        /* Clickable Pending Badge Button Style */
        .badge-btn {
            border: 1px solid #fcd34d;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .badge-btn:hover {
            background: #fde68a;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(217, 119, 6, 0.15);
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--c-text-sub); }

        .sticky-footer { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-top: 1px solid var(--c-border); padding: 16px 40px; box-shadow: 0 -10px 25px rgba(0,0,0,0.05); display: flex; justify-content: center; z-index: 100; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sticky-footer.visible { transform: translateY(0); }
        .footer-content { width: 100%; max-width: 1240px; display: flex; justify-content: space-between; align-items: center; }
        
        .summary-stats { display: flex; gap: 32px; align-items: center; }
        .stat-box { display: flex; flex-direction: column; }
        .stat-label { font-size: 11px; font-weight: 700; color: var(--c-text-sub); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 20px; font-weight: 800; color: var(--c-primary); }

        .btn-submit { background: var(--c-primary); color: #fff; border: none; padding: 14px 28px; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(28, 95, 102, 0.2); }
        .btn-submit:hover { background: var(--c-primary-light); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(28, 95, 102, 0.3); }
    </style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<form method="POST" action="/user/auditor/rfp-approval-flow" id="commissionForm">
    <input type="hidden" name="action" value="generate_flexible_rfp">

    <div class="clr-layout-wrapper">
        <main class="clr-main-content">
            
            <div class="clr-header-section">
                <div>
                    <h1><i data-lucide="layers"></i> Pending Commission Releases</h1>
                    <p>
                        <?php if ($is_encoder): ?>
                            Select available entity commissions to generate an RFP. Click any pending badge to view/review active RFPs.
                        <?php else: ?>
                            Click on any "Pending Approval" item to review or approve its Request for Payment.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="clr-table-card">
                <?php if (empty($pending_transactions)): ?>
                    <div class="empty-state">
                        <i data-lucide="check-circle" size="48" style="color: var(--c-success); margin-bottom: 12px;"></i>
                        <h3>All Commissions Processed</h3>
                        <p>There are currently no pending commissions awaiting release.</p>
                    </div>
                <?php else: ?>
                    <table class="clr-table">
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
                            ?>
                                <tr>
                                    <td>
                                        <div class="customer-meta">
                                            <span class="customer-name"><?= htmlspecialchars($row['customer_fullname'] ?? 'Unknown Client') ?></span>
                                            <span class="due-date"><i data-lucide="calendar" size="14"></i> <?= date('M d, Y', strtotime($row['due_date'])) ?></span>
                                        </div>
                                    </td>

                                    <!-- BROKER -->
                                    <td>
                                        <?php 
                                        $b_state = getCommissionState($row['broker_commission_status'], $row['broker_commission_amount']);
                                        if ($b_state === 'selectable'): ?>
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
                                            <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_broker" class="status-badge pending badge-btn" title="Click to view/approve RFP">
                                                <i data-lucide="clock" size="14"></i> Pending Approval <i data-lucide="arrow-right" size="12"></i>
                                            </button>
                                        <?php elseif ($b_state === 'released'): ?>
                                            <div class="status-badge released">
                                                <i data-lucide="check" size="14"></i> Released
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge">
                                                <i data-lucide="minus" size="14"></i> N/A
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- UNIT MANAGER -->
                                    <td>
                                        <?php 
                                        $um_state = getCommissionState($row['um_commission_status'], $row['um_commission_amount']);
                                        if ($um_state === 'selectable'): ?>
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
                                            <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_um" class="status-badge pending badge-btn" title="Click to view/approve RFP">
                                                <i data-lucide="clock" size="14"></i> Pending Approval <i data-lucide="arrow-right" size="12"></i>
                                            </button>
                                        <?php elseif ($um_state === 'released'): ?>
                                            <div class="status-badge released">
                                                <i data-lucide="check" size="14"></i> Released
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge">
                                                <i data-lucide="minus" size="14"></i> N/A
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- AGENT -->
                                    <td>
                                        <?php 
                                        $a_state = getCommissionState($row['agent_commission_status'], $row['agent_commission_amount']);
                                        if ($a_state === 'selectable'): ?>
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
                                            <button type="submit" form="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_agent" class="status-badge pending badge-btn" title="Click to view/approve RFP">
                                                <i data-lucide="clock" size="14"></i> Pending Approval <i data-lucide="arrow-right" size="12"></i>
                                            </button>
                                        <?php elseif ($a_state === 'released'): ?>
                                            <div class="status-badge released">
                                                <i data-lucide="check" size="14"></i> Released
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge">
                                                <i data-lucide="minus" size="14"></i> N/A
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
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
                <button type="submit" class="btn-submit">
                    <i data-lucide="file-text" size="18"></i> Generate Request for Payment
                </button>
            </div>
        </div>
    <?php endif; ?>
</form>

<!-- HIDDEN FORMS TO REDIRECT CLICKED PENDING BADGES TO RFP-APPROVAL-FLOW -->
<?php foreach ($pending_transactions as $row): 
    $cid = htmlspecialchars($row['customer_id']);
    $date = htmlspecialchars($row['due_date']);
    $roles_check = ['broker', 'um', 'agent'];
    foreach ($roles_check as $r):
        $st = getCommissionState($row[$r . '_commission_status'], $row[$r . '_commission_amount']);
        if ($st === 'pending_approval'):
?>
            <form id="rfp_load_<?= $cid ?>_<?= strtotime($date) ?>_<?= $r ?>" method="POST" action="/user/auditor/rfp-approval-flow">
                <input type="hidden" name="action" value="load_existing_rfp">
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

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateSelection);
        });
    });
</script>
</body>
</html>