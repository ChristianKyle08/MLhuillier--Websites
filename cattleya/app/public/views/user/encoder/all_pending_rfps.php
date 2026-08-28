<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

$logged_in_role = strtolower($_SESSION['role'] ?? 'encoder');

$pending_commissions = [];
$roles = ['agent', 'um', 'sg', 'bms', 'smo'];

foreach ($roles as $r) {
    $status_col = $r . '_commission_status';
    $amount_col = $r . '_commission_amount';
    $name_col   = $r . '_fullname';

    try {
        $sql = "SELECT p.customer_id, p.due_date, s.customer_fullname, s.{$name_col} as payee, p.{$amount_col} as amount,
                p.encoder_id, p.reviewer_id, p.auditor_id, p.cfo_id, p.{$status_col} as db_status
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.{$status_col} = 'Pending Approval'";
        $stmt = $pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Determine exact workflow stage based on signatures present
            $stage = 'Pending Review';
            if ($row['reviewer_id']) $stage = 'Pending Audit';
            if ($row['auditor_id']) $stage = 'Pending CFO';
            
            $row['role_type'] = $r;
            $row['workflow_stage'] = $stage;
            $pending_commissions[] = $row;
        }
    } catch (PDOException $e) {
        error_log("Error fetching pending RFPs: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>All Pending RFPs</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #0f172a; padding: 40px; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f1f5f9; font-weight: 800; text-transform: uppercase; font-size: 12px; color: #64748b; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; }
        .bg-warning { background: #fef3c7; color: #d97706; }
        .btn { padding: 8px 16px; background: #1c5f66; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: #154c52; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

    <div class="card">
        <h2 style="margin-top:0; display:flex; align-items:center; gap:8px;"><i data-lucide="list-todo"></i> Pending Commission RFPs</h2>
        <table>
            <thead>
                <tr>
                    <th>Payee</th>
                    <th>Role</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Current Stage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($pending_commissions)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No pending RFPs found.</td></tr>
                <?php else: ?>
                    <?php foreach($pending_commissions as $req): ?>
                    <tr>
                        <td style="font-weight:800;"><?= htmlspecialchars(strtoupper($req['payee'])) ?></td>
                        <td><?= strtoupper($req['role_type']) ?></td>
                        <td><?= htmlspecialchars($req['customer_fullname']) ?></td>
                        <td style="font-weight:600; color:#10b981;">₱<?= number_format($req['amount'], 2) ?></td>
                        <td><span class="badge bg-warning"><?= $req['workflow_stage'] ?></span></td>
                        <td>
                            <form action="/user/encoder/rfp-approval-flow" method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="load_existing_rfp">
                                <input type="hidden" name="selected_key" value="<?= $req['customer_id'] . '|' . $req['due_date'] . '|' . $req['role_type'] ?>">
                                <button type="submit" class="btn"><i data-lucide="external-link" size="14"></i> Open RFP</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>