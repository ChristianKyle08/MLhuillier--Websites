<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg   = '';

$logged_in_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in_role    = strtolower($_SESSION['role'] ?? 'encoder');

if (!isset($_SESSION['rfp_sigs'])) {
    $_SESSION['rfp_sigs'] = [];
}

function getUserDetailsById($pdo, $user_id) {
    if (!$user_id) return null;
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, signature, signature_type, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $fullname = strtoupper(trim($user['first_name'] . ' ' . $user['last_name']));
            $sig_data_uri = '';
            if (!empty($user['signature'])) {
                $sig = $user['signature'];
                if (strpos($sig, 'data:image') === 0) {
                    $sig_data_uri = $sig;
                } else {
                    $mime = !empty($user['signature_type']) ? $user['signature_type'] : 'image/png';
                    $sig_data_uri = 'data:' . $mime . ';base64,' . base64_encode($sig);
                }
            }
            return [
                'user_id' => $user['id'],
                'name'    => $fullname, 
                'sig_src' => $sig_data_uri,
                'role'    => strtoupper(str_replace('_', ' ', $user['role'])),
                'raw_role'=> strtolower($user['role'])
            ];
        }
    } catch (PDOException $e) {
        error_log("Signature fetch error: " . $e->getMessage());
    }
    return null;
}

function updatePaymentSignatureRecord($pdo, $rfp_data, $role_prefix, $user_id, $sig_src) {
    if (!$rfp_data || empty($rfp_data['selected_keys'])) return;

    $allowed_roles = ['encoder', 'reviewer', 'auditor', 'cfo'];
    if (!in_array($role_prefix, $allowed_roles, true)) return;

    $sql = "UPDATE payments SET {$role_prefix}_id = ?, {$role_prefix}_sig = ? WHERE customer_id = ? AND due_date = ?";
    $stmt = $pdo->prepare($sql);

    foreach ($rfp_data['selected_keys'] as $key) {
        $parts = explode('|', $key);
        if (count($parts) === 3) {
            list($cid, $date, $role) = $parts;
            $stmt->execute([$user_id, $sig_src, $cid, $date]);
        }
    }
}

function hasUserAlreadySigned($user_id, $sigs) {
    if (!$user_id) return false;
    foreach ($sigs as $stage => $sig_info) {
        if (isset($sig_info['user_id']) && (string)$sig_info['user_id'] === (string)$user_id) {
            return true;
        }
    }
    return false;
}

// --- 1. INITIALIZE FLEXIBLE RFP SELECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_flexible_rfp') {
    $selected_commissions = $_POST['selected_commissions'] ?? [];

    if (empty($selected_commissions)) {
        header('Location: commission_release.php');
        exit;
    }

    $total_amount = 0;
    $items        = [];
    $payees       = [];
    $item_no      = 1;

    foreach ($selected_commissions as $key) {
        $parts = explode('|', $key);
        if (count($parts) !== 3) continue;

        list($cid, $due_date, $role) = $parts;

        $amount_col = $role . '_commission_amount';
        $status_col = $role . '_commission_status';
        $name_col   = $role . '_fullname';

        try {
            $stmt = $pdo->prepare("
                SELECT p.customer_id, s.customer_fullname, p.due_date, 
                       s.{$name_col} as payee_person, p.{$amount_col} as comm_amount
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.customer_id = ? AND p.due_date = ? AND p.{$status_col} != 'Released'
                LIMIT 1
            ");
            $stmt->execute([$cid, $due_date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['comm_amount'] > 0) {
                $role_label = strtoupper($role === 'um' ? 'Unit Manager' : $role);
                $payee_individual = strtoupper($row['payee_person'] ?? 'N/A');
                $payees[$payee_individual] = true;

                $desc = "{$role_label} COMMISSION RELEASE - BENEFICIARY: {$payee_individual} (CLIENT: " . strtoupper($row['customer_fullname']) . " | DUE: " . date('M d, Y', strtotime($row['due_date'])) . ")";

                $amount = (float)$row['comm_amount'];
                $items[] = [
                    'item_no'      => $item_no++,
                    'qty'          => '1',
                    'unit'         => 'LOT',
                    'description'  => $desc,
                    'unit_price'   => number_format($amount, 2),
                    'total_amount' => number_format($amount, 2),
                    'raw_amount'   => $amount
                ];
                $total_amount += $amount;
            }
        } catch (PDOException $e) {
            $error_msg = "Database query error: " . $e->getMessage();
        }
    }

    $payee_count = count($payees);
    if ($payee_count === 1) {
        $payee_name = array_key_first($payees);
    } elseif ($payee_count > 1) {
        $payee_name = implode(', ', array_keys($payees));
    } else {
        $payee_name = 'MULTIPLE BENEFICIARIES';
    }

    $slip_no = 'REQ-COM-' . date('Ymd-His');

    $_SESSION['rfp_data'] = [
        'payee_name'     => $payee_name,
        'selected_keys'  => $selected_commissions,
        'amount'         => number_format($total_amount, 2),
        'raw_total'      => $total_amount,
        'date_requested' => date('d-M-y'),
        'slip_no'        => $slip_no,
        'description'    => "COMMISSION REQUISITION FOR " . $payee_name,
        'items'          => $items
    ];
    $_SESSION['rfp_status'] = 'draft';
    $_SESSION['rfp_sigs']   = [];
}

// --- 1.5 RECONSTRUCT SESSION FROM DATABASE FOR APPROVERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'load_existing_rfp') {
    $selected_key = $_POST['selected_key'] ?? '';
    $parts = explode('|', $selected_key);
    
    if (count($parts) === 3) {
        list($cid, $due_date, $role) = $parts;
        $amount_col = $role . '_commission_amount';
        $name_col   = $role . '_fullname';
        
        // Fetch the row and all signatures
        $stmt = $pdo->prepare("
            SELECT p.*, s.customer_fullname, s.{$name_col} as payee_person 
            FROM payments p JOIN sales s ON p.sale_id = s.sale_id 
            WHERE p.customer_id = ? AND p.due_date = ? LIMIT 1
        ");
        $stmt->execute([$cid, $due_date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $amount = (float)$row[$amount_col];
            $payee_name = strtoupper($row['payee_person']);
            
            // Rebuild Basic RFP Data
            $_SESSION['rfp_data'] = [
                'payee_name'     => $payee_name,
                'selected_keys'  => [$selected_key],
                'amount'         => number_format($amount, 2),
                'raw_total'      => $amount,
                'date_requested' => date('d-M-y'), // Note: Ideally fetch original creation date if stored
                'slip_no'        => 'REQ-COM-' . date('Ymd-His', strtotime($row['due_date'])),
                'description'    => "COMMISSION REQUISITION FOR " . $payee_name,
                'items'          => [[
                    'item_no' => 1, 'qty' => '1', 'unit' => 'LOT',
                    'description' => strtoupper($role) . " COMMISSION RELEASE - BENEFICIARY: {$payee_name}",
                    'unit_price' => number_format($amount, 2), 'total_amount' => number_format($amount, 2)
                ]]
            ];
            
            // Rebuild Signatures and State
            $_SESSION['rfp_sigs'] = [];
            $_SESSION['rfp_status'] = 'pending_review'; // Default starting state for approvers
            
            if ($row['encoder_id']) {
                $_SESSION['rfp_sigs']['encoder'] = getUserDetailsById($pdo, $row['encoder_id']);
                $_SESSION['rfp_sigs']['encoder']['sig_src'] = $row['encoder_sig'];
            }
            if ($row['reviewer_id']) {
                $_SESSION['rfp_sigs']['reviewer'] = getUserDetailsById($pdo, $row['reviewer_id']);
                $_SESSION['rfp_sigs']['reviewer']['sig_src'] = $row['reviewer_sig'];
                $_SESSION['rfp_status'] = 'pending_audit';
            }
            if ($row['auditor_id']) {
                $_SESSION['rfp_sigs']['auditor'] = getUserDetailsById($pdo, $row['auditor_id']);
                $_SESSION['rfp_sigs']['auditor']['sig_src'] = $row['auditor_sig'];
                $_SESSION['rfp_status'] = 'pending_cfo';
            }
            if ($row['cfo_id']) {
                $_SESSION['rfp_sigs']['cfo'] = getUserDetailsById($pdo, $row['cfo_id']);
                $_SESSION['rfp_sigs']['cfo']['sig_src'] = $row['cfo_sig'];
                $_SESSION['rfp_status'] = 'released';
            }
        }
    }
}

// --- 2. WORKFLOW SIGNING & APPROVAL ENGINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'generate_flexible_rfp') {
    $action = $_POST['action'] ?? '';
    $signer = getUserDetailsById($pdo, $logged_in_user_id);

    if (!$signer) {
        $error_msg = "User signature details could not be found for session ID: " . htmlspecialchars($logged_in_user_id);
    } else {
        $rfp_data = $_SESSION['rfp_data'] ?? null;
        $has_signed_already = hasUserAlreadySigned($signer['user_id'], $_SESSION['rfp_sigs']);

        if ($action === 'submit_encoder') {
            $_SESSION['rfp_status'] = 'pending_review';
            $_SESSION['rfp_sigs']['encoder'] = $signer;
            
            updatePaymentSignatureRecord($pdo, $rfp_data, 'encoder', $signer['user_id'], $signer['sig_src']);

            // LOCK SELECTED COMMISSIONS AS 'Pending Approval' IN THE DATABASE
            if (!empty($rfp_data['selected_keys'])) {
                foreach ($rfp_data['selected_keys'] as $key) {
                    $parts = explode('|', $key);
                    if (count($parts) === 3) {
                        list($cid, $date, $role) = $parts;
                        $status_col = $role . '_commission_status';
                        $stmt = $pdo->prepare("UPDATE payments SET {$status_col} = 'Pending Approval' WHERE customer_id = ? AND due_date = ?");
                        $stmt->execute([$cid, $date]);
                    }
                }
            }

            $success_msg = "RFP prepared and signed by Encoder. Selected items are now locked as 'Pending Approval'.";

        } elseif ($action === 'approve_reviewer') {
            if ($has_signed_already) {
                $error_msg = "Segregation of Duties Conflict: You encoded this RFP. The reviewer must be a different user.";
            } else {
                $_SESSION['rfp_status'] = 'pending_audit';
                $_SESSION['rfp_sigs']['reviewer'] = $signer;

                updatePaymentSignatureRecord($pdo, $rfp_data, 'reviewer', $signer['user_id'], $signer['sig_src']);
                $success_msg = "RFP reviewed and signature attached successfully.";
            }

        } elseif ($action === 'approve_auditor') {
            if ($has_signed_already) {
                $error_msg = "Segregation of Duties Conflict: You cannot audit an RFP you previously handled.";
            } else {
                $_SESSION['rfp_status'] = 'pending_cfo';
                $_SESSION['rfp_sigs']['auditor'] = $signer;

                updatePaymentSignatureRecord($pdo, $rfp_data, 'auditor', $signer['user_id'], $signer['sig_src']);
                $success_msg = "Audit signature attached successfully.";
            }

        } elseif ($action === 'approve_cfo') {
            if ($has_signed_already) {
                $error_msg = "Segregation of Duties Conflict: Final CFO approval must be performed by an independent approver.";
            } else {
                try {
                    // CFO FINAL APPROVAL: Update status from 'Pending Approval' to 'Released'
                    foreach ($rfp_data['selected_keys'] as $key) {
                        $parts = explode('|', $key);
                        if (count($parts) === 3) {
                            list($cid, $date, $role) = $parts;
                            $status_col = $role . '_commission_status';

                            $sql = "UPDATE payments SET {$status_col} = 'Released' WHERE customer_id = ? AND due_date = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$cid, $date]);
                        }
                    }

                    $_SESSION['rfp_status'] = 'released';
                    $_SESSION['rfp_sigs']['cfo'] = $signer;

                    updatePaymentSignatureRecord($pdo, $rfp_data, 'cfo', $signer['user_id'], $signer['sig_src']);
                    $success_msg = "RFP Finalized! CFO Signature saved and selected commission statuses updated to 'Released'.";
                } catch (PDOException $e) {
                    $error_msg = "Database Update Error: " . $e->getMessage();
                }
            }
        }
    }
}

$rfp    = $_SESSION['rfp_data'] ?? null;
$status = $_SESSION['rfp_status'] ?? 'draft';
$sigs   = $_SESSION['rfp_sigs'] ?? [];

if (!$rfp) {
    echo "<div style='padding:60px; font-family:sans-serif; text-align:center;'>
            <h2>No active RFP session found.</h2>
            <a href='commission_release.php' style='display:inline-block; margin-top:15px; padding:12px 24px; background:#1c5f66; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold;'>Go Back to Commissions</a>
          </div>";
    exit;
}

$status_levels = ['draft' => 1, 'pending_review' => 2, 'pending_audit' => 3, 'pending_cfo' => 4, 'released' => 5];
$current_level = $status_levels[$status] ?? 1;

$creator_id = $sigs['encoder']['user_id'] ?? null;
$is_creator = ($creator_id && (string)$creator_id === (string)$logged_in_user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request for Payment - <?= htmlspecialchars($rfp['slip_no']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --bg-canvas: #f8fafc;
            --brand-primary: #1c5f66;
            --brand-accent: #a6ce39;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
            --success: #10b981;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background-color: var(--bg-canvas); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); padding-bottom: 60px; }

        .rfp-control-wrapper { width: 100%; max-width: 920px; margin: 30px auto; padding: 0 16px; }
        .control-panel-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px 32px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 10px 15px -3px rgba(0,0,0,0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .cp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .cp-title { font-size: 18px; font-weight: 800; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }

        .stepper { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; }
        .stepper::before {
            content: ''; position: absolute; top: 14px; left: 0; right: 0; height: 2px;
            background: var(--border-light); z-index: 1;
        }
        .step {
            position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 8px;
            width: 25%; text-align: center;
        }
        .step-icon {
            width: 30px; height: 30px; border-radius: 50%; background: #ffffff;
            border: 2px solid var(--border-light); display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: var(--text-muted); transition: 0.3s;
        }
        .step-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        
        .step.completed .step-icon { background: var(--brand-primary); border-color: var(--brand-primary); color: #ffffff; }
        .step.completed .step-label { color: var(--text-main); }
        .step.active .step-icon { border-color: var(--brand-primary); color: var(--brand-primary); box-shadow: 0 0 0 3px rgba(28, 95, 102, 0.15); }
        .step.active .step-label { color: var(--brand-primary); font-weight: 800; }

        .cp-actions { display: flex; align-items: center; justify-content: space-between; padding-top: 20px; border-top: 1px solid var(--border-light); }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
            border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer;
            border: none; text-decoration: none; transition: 0.2s ease;
        }
        .btn-primary { background: var(--brand-primary); color: #ffffff; }
        .btn-primary:hover { background: #154c52; transform: translateY(-1px); }
        .btn-success { background: var(--success); color: #ffffff; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn-outline { background: #ffffff; border: 1.5px solid var(--border-light); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }

        .alert { padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .paper {
            width: 850px; min-height: 1050px; margin: 0 auto; background: #ffffff;
            padding: 45px 50px; box-shadow: 0 20px 40px rgba(0,0,0,0.06);
            border: 1px solid #d1d5db; position: relative; color: #000;
        }

        .co-header { text-align: center; font-size: 20px; font-weight: 800; font-family: 'Times New Roman', serif; text-transform: uppercase; letter-spacing: 0.05em; }
        .doc-title { text-align: center; font-size: 28px; font-weight: 800; font-family: 'Cinzel', serif; margin: 10px 0 25px; }

        .meta-grid { display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; margin-bottom: 12px; }
        .u-line { border-bottom: 1.5px solid #000; padding: 0 10px; display: inline-block; min-width: 150px; text-transform: uppercase; }

        .rfp-grid { width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 11px; }
        .rfp-grid th, .rfp-grid td { border: 1.5px solid #000; padding: 8px 10px; }
        .rfp-grid th { font-weight: 800; text-transform: uppercase; background: #fafafa; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .item-row { height: 34px; }
        
        .circle-total {
            display: inline-block; padding: 6px 18px; border: 2px solid #dc2626;
            border-radius: 50%; font-weight: 800; font-size: 14px; color: #000;
        }

        .sig-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 60px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .sig-box { position: relative; padding-top: 45px; text-align: center; }
        .sig-line { border-top: 1.5px solid #000; margin-bottom: 4px; }
        .role-text { font-size: 9px; font-weight: 600; color: #475569; }

        .db-sig-img {
            position: absolute; bottom: 25px; left: 50%; transform: translateX(-50%);
            max-height: 55px; max-width: 130px; object-fit: contain; z-index: 10;
        }
        .fallback-sig {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Dancing Script', cursive;
            font-size: 22px; color: #1e293b; text-transform: none; z-index: 10; white-space: nowrap;
        }

        .audit-stamp {
            position: absolute; top: -25px; left: 10px; border: 2px dashed #b91c1c; color: #b91c1c;
            padding: 3px 8px; font-size: 10px; font-weight: 800; transform: rotate(-5deg); background: rgba(255,255,255,0.9);
            text-align: center; z-index: 5;
        }
        .paid-stamp {
            position: absolute; top: 350px; left: 50%; transform: translateX(-50%) rotate(-15deg);
            border: 6px solid #16a34a; color: #16a34a; padding: 15px 40px; font-size: 56px;
            font-weight: 900; letter-spacing: 0.1em; opacity: 0.2; pointer-events: none; border-radius: 12px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .rfp-control-wrapper { display: none !important; }
            .paper { box-shadow: none; border: none; width: 100%; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="rfp-control-wrapper">
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i data-lucide="check-circle-2" size="18"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i data-lucide="alert-triangle" size="18"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="control-panel-card">
        <div class="cp-header">
            <h3 class="cp-title"><i data-lucide="layout-dashboard"></i> RFP Approval Flow</h3>
            <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                Logged in as: <strong><?= strtoupper(htmlspecialchars($logged_in_role)) ?></strong>
            </span>
        </div>

        <div class="stepper">
            <div class="step <?= $current_level > 1 ? 'completed' : ($current_level === 1 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 1 ? 'check' : 'pen-tool' ?>" size="16"></i></div>
                <div class="step-label">Encoded</div>
            </div>
            <div class="step <?= $current_level > 2 ? 'completed' : ($current_level === 2 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 2 ? 'check' : 'search' ?>" size="16"></i></div>
                <div class="step-label">Reviewed</div>
            </div>
            <div class="step <?= $current_level > 3 ? 'completed' : ($current_level === 3 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 3 ? 'check' : 'stamp' ?>" size="16"></i></div>
                <div class="step-label">Audited</div>
            </div>
            <div class="step <?= $current_level > 4 ? 'completed' : ($current_level === 4 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 4 ? 'check' : 'shield-check' ?>" size="16"></i></div>
                <div class="step-label">CFO Approved</div>
            </div>
        </div>

        <div class="cp-actions">
            <div style="display: flex; gap: 12px;">
                <a href="/user/finance/commission-release" class="btn btn-outline"><i data-lucide="arrow-left" size="16"></i> Back</a>
                <button type="button" onclick="window.print()" class="btn btn-outline"><i data-lucide="printer" size="16"></i> Print</button>
            </div>

            <form method="POST" style="margin: 0;">
                <?php if ($status === 'draft' && $logged_in_role === 'encoder'): ?>
                    <button type="submit" name="action" value="submit_encoder" class="btn btn-primary">
                        <i data-lucide="pen-tool" size="16"></i> Sign & Submit Draft
                    </button>
                <?php elseif ($status === 'pending_review' && $logged_in_role === 'encoder' && !$is_creator): ?>
                    <button type="submit" name="action" value="approve_reviewer" class="btn btn-primary">
                        <i data-lucide="check-square" size="16"></i> Review & Sign
                    </button>
                <?php elseif ($status === 'pending_audit' && $logged_in_role === 'auditor'): ?>
                    <button type="submit" name="action" value="approve_auditor" class="btn btn-primary">
                        <i data-lucide="stamp" size="16"></i> Audit & Stamp
                    </button>
                <?php elseif ($status === 'pending_cfo' && $logged_in_role === 'cfo'): ?>
                    <button type="submit" name="action" value="approve_cfo" class="btn btn-success" onclick="return confirm('Confirm release of funds? Selected commission statuses will be updated to Released.');">
                        <i data-lucide="badge-dollar-sign" size="16"></i> CFO Final Sign & Release
                    </button>
                <?php elseif ($status === 'released'): ?>
                    <span style="color: var(--success); font-weight: 800; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check-circle-2" size="20"></i> COMPLETED & RELEASED
                    </span>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 13px; font-weight: 500;">
                        <?php if ($status === 'pending_review' && $is_creator): ?>
                            <i data-lucide="lock" size="14" style="vertical-align: middle;"></i> Awaiting another Encoder to Review (You cannot review your own).
                        <?php else: ?>
                            <i data-lucide="lock" size="14" style="vertical-align: middle;"></i> Awaiting active step user approval...
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="paper">
    <?php if ($status === 'released'): ?>
        <div class="paid-stamp">PAID & RELEASED</div>
    <?php endif; ?>

    <div class="co-header">MLHUILLIER GROUP OF COMPANIES</div>
    <div class="doc-title">Requisition Form</div>

    <div class="meta-grid">
        <div>DATE REQUESTED <span class="u-line"><?= htmlspecialchars($rfp['date_requested']) ?></span></div>
        <div>REQUISITION SLIP # <span class="u-line"><?= htmlspecialchars($rfp['slip_no']) ?></span></div>
    </div>

    <table class="rfp-grid">
        <tr>
            <td colspan="4">Requester's Name &nbsp;&nbsp; <strong><?= htmlspecialchars(strtoupper($sigs['encoder']['name'] ?? 'PENDING')) ?></strong></td>
            <td colspan="2">DATE NEEDED &nbsp;&nbsp; <strong>ASAP</strong></td>
        </tr>
        <tr>
            <td colspan="6">ADDRESS &nbsp;&nbsp; <strong>1082 J. PANIS ST. KALUBIHAN TALAMBAN CEBU</strong></td>
        </tr>
        <tr>
            <td colspan="3">DEPT. &nbsp;&nbsp; <strong>OPERATIONS</strong></td>
            <td colspan="3">BUDGET SOURCE &nbsp;&nbsp; <strong>COMMISSION FUND</strong></td>
        </tr>
        <tr>
            <th colspan="2" style="width: 20%; background: #f1f5f9;">Purpose</th>
            <th colspan="4" class="text-center" style="font-size: 14px; background: #f8fafc;">REQUEST FOR PAYMENT</th>
        </tr>
        <tr>
            <th width="8%" class="text-center">ITEM #</th>
            <th width="8%" class="text-center">QTY</th>
            <th width="12%" class="text-center">UNIT/SIZE</th>
            <th width="44%">MATERIALS DESCRIPTION</th>
            <th width="14%" class="text-center">UNIT PRICE</th>
            <th width="14%" class="text-center">TOTAL AMOUNT</th>
        </tr>
        
        <?php 
        $displayItems = $rfp['items'] ?? [];
        for ($i = 0; $i < max(6, count($displayItems)); $i++): 
            $item = $displayItems[$i] ?? null;
        ?>
            <tr class="item-row">
                <td class="text-center"><?= $item ? htmlspecialchars($item['item_no']) : '' ?></td>
                <td class="text-center"><?= $item ? htmlspecialchars($item['qty']) : '' ?></td>
                <td class="text-center"><?= $item ? htmlspecialchars($item['unit']) : '' ?></td>
                <td><strong><?= $item ? htmlspecialchars($item['description']) : ($i === 0 ? htmlspecialchars($rfp['description']) : '') ?></strong></td>
                <td class="text-right"><?= $item ? htmlspecialchars($item['unit_price']) : '' ?></td>
                <td class="text-right" style="font-weight:800;"><?= $item ? htmlspecialchars($item['total_amount']) : ($i === 0 ? htmlspecialchars($rfp['amount']) : '') ?></td>
            </tr>
        <?php endfor; ?>

        <tr>
            <td colspan="5" style="padding: 16px;">
                <strong>NOTE:</strong> <br><br>
                <span style="margin-left: 80px; font-weight:800;">MJLFSI - FLEXIBLE COMMISSION RELEASE (PAYEE: <?= htmlspecialchars(strtoupper($rfp['payee_name'])) ?>)</span>
            </td>
            <td class="text-center" style="vertical-align: middle;">
                <span class="circle-total">₱<?= htmlspecialchars($rfp['amount']) ?></span>
            </td>
        </tr>
    </table>

    <div class="sig-container">
        <!-- ENCODER -->
        <div class="sig-box">
            <?php if (isset($sigs['encoder'])): ?>
                <?php if (!empty($sigs['encoder']['sig_src'])): ?>
                    <img src="<?= $sigs['encoder']['sig_src'] ?>" class="db-sig-img" alt="Encoder Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['encoder']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>PREPARED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['encoder']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['encoder']['role'] ?? 'ENCODER') ?></div>
        </div>

        <!-- REVIEWER -->
        <div class="sig-box">
            <?php if (isset($sigs['reviewer'])): ?>
                <?php if (!empty($sigs['reviewer']['sig_src'])): ?>
                    <img src="<?= $sigs['reviewer']['sig_src'] ?>" class="db-sig-img" alt="Reviewer Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['reviewer']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>REVIEWED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['reviewer']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['reviewer']['role'] ?? 'CHECKER / REVIEWER') ?></div>
        </div>

        <!-- AUDITOR -->
        <div class="sig-box">
            <?php if (isset($sigs['auditor'])): ?>
                <div class="audit-stamp">AUDIT DIVISION<br><span style="font-size:8px;">VERIFIED & STAMPED<br><?= date('M Y') ?></span></div>
                <?php if (!empty($sigs['auditor']['sig_src'])): ?>
                    <img src="<?= $sigs['auditor']['sig_src'] ?>" class="db-sig-img" alt="Auditor Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['auditor']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>AUDITED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['auditor']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['auditor']['role'] ?? 'AUDIT DIVISION') ?></div>
        </div>

        <!-- CFO -->
        <div class="sig-box">
            <?php if (isset($sigs['cfo'])): ?>
                <?php if (!empty($sigs['cfo']['sig_src'])): ?>
                    <img src="<?= $sigs['cfo']['sig_src'] ?>" class="db-sig-img" alt="CFO Signature">
                <?php else: ?>
                    <div class="fallback-sig" style="color:#0284c7; font-size:24px;"><?= htmlspecialchars($sigs['cfo']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>APPROVED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['cfo']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['cfo']['role'] ?? 'CHIEF FINANCIAL OFFICER') ?></div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>