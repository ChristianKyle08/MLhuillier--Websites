<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

// --- HELPER FUNCTION FOR STATUS BADGES ---
function renderStatusBadge($status) {
    if (empty($status)) return '';
    $statusStr = htmlspecialchars($status);
    $class = (strcasecmp($statusStr, 'Released') === 0) ? 'released' : 'pending';
    return '<div style="margin-top:4px;"><span class="clr-badge '.$class.'">'.$statusStr.'</span></div>';
}

// --- HANDLE FORM SUBMISSION & ACTIONS ---
$modal_results = null;
$filter_used = '';
$id = '';
$date_from = '';
$date_to = '';
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'search';
    $filter_type = $_POST['filter_type'] ?? 'customer';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';

    if ($action === 'release') {
        $t_id = $_POST['target_id'] ?? '';
        
        try {
            if ($filter_type === 'customer') {
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET broker_commission_status = CASE WHEN broker_commission_status IS NOT NULL AND broker_commission_status != '' AND broker_commission_status != 'Released' THEN 'Released' ELSE broker_commission_status END,
                        um_commission_status = CASE WHEN um_commission_status IS NOT NULL AND um_commission_status != '' AND um_commission_status != 'Released' THEN 'Released' ELSE um_commission_status END,
                        agent_commission_status = CASE WHEN agent_commission_status IS NOT NULL AND agent_commission_status != '' AND agent_commission_status != 'Released' THEN 'Released' ELSE agent_commission_status END
                    WHERE customer_id = ? AND due_date >= ? AND due_date <= ?
                ");
                $stmt->execute([$t_id, $date_from, $date_to]);
            } elseif ($filter_type === 'agent') {
                $stmt = $pdo->prepare("UPDATE payments SET agent_commission_status = 'Released' WHERE agent_id = ? AND due_date >= ? AND due_date <= ? AND agent_commission_status IS NOT NULL AND agent_commission_status != '' AND agent_commission_status != 'Released'");
                $stmt->execute([$t_id, $date_from, $date_to]);
            } elseif ($filter_type === 'broker') {
                $stmt = $pdo->prepare("UPDATE payments SET broker_commission_status = 'Released' WHERE broker_id = ? AND due_date >= ? AND due_date <= ? AND broker_commission_status IS NOT NULL AND broker_commission_status != '' AND broker_commission_status != 'Released'");
                $stmt->execute([$t_id, $date_from, $date_to]);
            } elseif ($filter_type === 'manager') {
                $stmt = $pdo->prepare("UPDATE payments SET um_commission_status = 'Released' WHERE um_id = ? AND due_date >= ? AND due_date <= ? AND um_commission_status IS NOT NULL AND um_commission_status != '' AND um_commission_status != 'Released'");
                $stmt->execute([$t_id, $date_from, $date_to]);
            }
            
            $success_msg = "Commissions for the selected criteria have been successfully updated to Released.";
            $filter_used = $filter_type; // keep the tab active

        } catch (PDOException $e) {
            $error_msg = "Error updating commissions: " . $e->getMessage();
        }

    } elseif ($action === 'search') {
        
        // --- FETCH DATA FOR MODAL ---
        if ($filter_type === 'customer') {
            $id = $_POST['customer_id'] ?? '';
            $stmt = $pdo->prepare("
                SELECT s.customer_fullname, p.due_date, s.agent_fullname, s.broker_fullname, s.um_fullname, 
                       p.broker_commission_amount, p.um_commission_amount, p.agent_commission_amount,
                       p.broker_commission_status, p.um_commission_status, p.agent_commission_status
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.customer_id = ? AND p.due_date >= ? AND p.due_date <= ?
                  AND (
                      (p.broker_commission_status IS NOT NULL AND p.broker_commission_status != '') OR 
                      (p.um_commission_status IS NOT NULL AND p.um_commission_status != '') OR 
                      (p.agent_commission_status IS NOT NULL AND p.agent_commission_status != '')
                  )
                ORDER BY p.due_date ASC
            ");
            $stmt->execute([$id, $date_from, $date_to]);
            $modal_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filter_used = 'customer';

        } elseif ($filter_type === 'agent') {
            $id = $_POST['agent_id'] ?? '';
            $stmt = $pdo->prepare("
                SELECT s.customer_fullname, s.agent_fullname, p.due_date, p.agent_commission_amount, p.agent_commission_status
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.agent_id = ? AND p.due_date >= ? AND p.due_date <= ?
                  AND p.agent_commission_status IS NOT NULL AND p.agent_commission_status != ''
                ORDER BY p.due_date ASC
            ");
            $stmt->execute([$id, $date_from, $date_to]);
            $modal_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filter_used = 'agent';

        } elseif ($filter_type === 'broker') {
            $id = $_POST['broker_id'] ?? '';
            $stmt = $pdo->prepare("
                SELECT s.customer_fullname, s.broker_fullname, p.due_date, p.broker_commission_amount, p.broker_commission_status
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.broker_id = ? AND p.due_date >= ? AND p.due_date <= ?
                  AND p.broker_commission_status IS NOT NULL AND p.broker_commission_status != ''
                ORDER BY p.due_date ASC
            ");
            $stmt->execute([$id, $date_from, $date_to]);
            $modal_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filter_used = 'broker';

        } elseif ($filter_type === 'manager') {
            $id = $_POST['manager_id'] ?? '';
            $stmt = $pdo->prepare("
                SELECT s.customer_fullname, s.um_fullname, p.due_date, p.um_commission_amount, p.um_commission_status
                FROM payments p
                JOIN sales s ON p.sale_id = s.sale_id
                WHERE p.um_id = ? AND p.due_date >= ? AND p.due_date <= ?
                  AND p.um_commission_status IS NOT NULL AND p.um_commission_status != ''
                ORDER BY p.due_date ASC
            ");
            $stmt->execute([$id, $date_from, $date_to]);
            $modal_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filter_used = 'manager';
        }
    }
}

// --- FETCH INITIAL DROPDOWN LISTS ---
try {
    $customer_stmt = $pdo->query("SELECT DISTINCT p.customer_id, s.customer_fullname FROM payments p JOIN sales s ON p.sale_id = s.sale_id WHERE p.customer_id IS NOT NULL AND p.customer_id != '' ORDER BY s.customer_fullname ASC");
    $customer_list = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

    $brokers = $pdo->query("SELECT DISTINCT p.broker_id, s.broker_fullname FROM payments p JOIN sales s ON p.sale_id = s.sale_id WHERE p.broker_id IS NOT NULL AND p.broker_id != '' ORDER BY s.broker_fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
    $managers = $pdo->query("SELECT DISTINCT p.um_id, s.um_fullname FROM payments p JOIN sales s ON p.sale_id = s.sale_id WHERE p.um_id IS NOT NULL AND p.um_id != '' ORDER BY s.um_fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
    $agents = $pdo->query("SELECT DISTINCT p.agent_id, s.agent_fullname FROM payments p JOIN sales s ON p.sale_id = s.sale_id WHERE p.agent_id IS NOT NULL AND p.agent_id != '' ORDER BY s.agent_fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customer_list = $brokers = $managers = $agents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Release Commission | ML Rental System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root { 
            --c-primary: #1c5f66; 
            --c-primary-light: #2a838d;
            --c-accent: #a6ce39; 
            --c-bg: #f4f7f6; 
            --c-card: #ffffff;
            --c-text-main: #1e293b;
            --c-text-sub: #64748b;
            --c-border: #e2e8f0;
            --sidebar-width: 280px;
        }

        * { box-sizing: border-box; }

        body { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--c-bg); color: var(--c-text-main); }

        .clr-layout-wrapper { display: flex; min-height: 100vh; }

        .clr-main-content {
            flex: 1; margin-left: 0px; padding: 40px; display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
        }

        /* --- ALERTS --- */
        .clr-alert-wrapper { width: 100%; max-width: 680px; margin-bottom: 20px; }
        .clr-alert { padding: 16px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; animation: slideDown 0.4s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .clr-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .clr-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- FORM CARD --- */
        .clr-form-card { background: var(--c-card); width: 100%; max-width: 680px; border-radius: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.7); overflow: hidden; position: relative; z-index: 10; }
        .clr-brand-accent { height: 8px; background: linear-gradient(90deg, var(--c-primary), var(--c-accent)); }
        .clr-card-header { padding: 40px 40px 30px; text-align: center; }
        .clr-icon-circle { width: 64px; height: 64px; background: rgba(28, 95, 102, 0.1); color: var(--c-primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .clr-card-header h1 { font-size: 26px; font-weight: 800; margin: 0; letter-spacing: -0.02em; }
        .clr-card-header p { color: var(--c-text-sub); font-size: 15px; margin-top: 8px; }

        /* --- STYLED TABS --- */
        .clr-tabs-container { padding: 0 40px; margin-bottom: 30px; }
        .clr-tabs-track { background: #f1f5f9; padding: 6px; border-radius: 16px; display: flex; gap: 4px; }
        .clr-tab-btn { flex: 1; padding: 12px; border: none; background: transparent; border-radius: 12px; font-family: inherit; font-weight: 700; font-size: 13px; color: var(--c-text-sub); cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .clr-tab-btn.active { background: var(--c-card); color: var(--c-primary); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        /* --- FORM ELEMENTS --- */
        .clr-form-body { padding: 0 40px 40px; }
        .clr-tab-pane { display: none; animation: slideUp 0.4s ease forwards; }
        .clr-tab-pane.active { display: block; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .clr-field-group { margin-bottom: 24px; }
        .clr-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 10px; color: var(--c-text-main); padding-left: 4px; }
        .clr-input-wrapper { position: relative; }
        .clr-input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--c-primary); pointer-events: none; }
        .clr-select, .clr-input-date { width: 100%; padding: 15px 15px 15px 48px; border-radius: 16px; border: 2px solid #f1f5f9; background: #f8fafc; font-family: inherit; font-size: 15px; color: var(--c-text-main); transition: all 0.2s; appearance: none; }
        .clr-select:focus, .clr-input-date:focus { outline: none; border-color: var(--c-primary); background: #fff; box-shadow: 0 0 0 4px rgba(28, 95, 102, 0.1); }
        .clr-date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .clr-divider { height: 1px; background: var(--c-border); margin: 32px 0; border: none; }

        /* --- SUBMIT BUTTON --- */
        .clr-submit-button { width: 100%; background: var(--c-primary); color: #fff; border: none; padding: 20px; border-radius: 18px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; transition: all 0.3s; }
        .clr-submit-button:hover { background: var(--c-primary-light); transform: translateY(-2px); }
        .clr-submit-button.success-btn { background: #16a34a; }
        .clr-submit-button.success-btn:hover { background: #15803d; }

        /* --- MODAL STYLES --- */
        .clr-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .clr-modal-overlay.show { opacity: 1; visibility: visible; }
        .clr-modal-content { background: var(--c-card); width: 90%; max-width: 950px; max-height: 90vh; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(20px) scale(0.95); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; flex-direction: column; overflow: hidden; }
        .clr-modal-overlay.show .clr-modal-content { transform: translateY(0) scale(1); }
        .clr-modal-header { padding: 24px 32px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; background: #fff; }
        .clr-modal-header h2 { margin: 0; font-size: 20px; color: var(--c-primary); display: flex; align-items: center; gap: 10px; }
        .clr-modal-close { background: transparent; border: none; color: var(--c-text-sub); cursor: pointer; padding: 8px; border-radius: 50%; transition: background 0.2s; }
        .clr-modal-close:hover { background: #f1f5f9; color: var(--c-text-main); }
        .clr-modal-body { padding: 32px; overflow-y: auto; background: #f8fafc; flex: 1; }
        .clr-modal-footer { padding: 20px 32px; border-top: 1px solid var(--c-border); background: #fff; display: flex; justify-content: flex-end; }

        /* --- TABLE & BADGES --- */
        .clr-table-container { background: #fff; border-radius: 16px; border: 1px solid var(--c-border); overflow: hidden; }
        .clr-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .clr-table th { background: #f1f5f9; color: var(--c-text-sub); font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; padding: 16px; text-align: left; border-bottom: 1px solid var(--c-border); }
        .clr-table td { padding: 16px; border-bottom: 1px solid var(--c-border); color: var(--c-text-main); vertical-align: top; }
        .clr-table tbody tr:hover { background: #f8fafc; }
        .clr-amount { font-weight: 700; color: var(--c-primary); }
        .clr-badge { display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .clr-badge.released { background: #dcfce7; color: #166534; }
        .clr-badge.pending { background: #fef9c3; color: #854d0e; }
        .clr-empty-state { text-align: center; padding: 40px; color: var(--c-text-sub); }

        /* --- MODAL INNER TABS & FILTERS --- */
        .clr-modal-tabs { display: inline-flex; gap: 8px; margin-bottom: 20px; background: #e2e8f0; padding: 6px; border-radius: 12px; }
        .clr-modal-tab-btn { background: transparent; border: none; font-size: 13px; font-weight: 700; color: var(--c-text-sub); cursor: pointer; padding: 10px 20px; border-radius: 8px; transition: all 0.2s; }
        .clr-modal-tab-btn.active { background: #fff; color: var(--c-primary); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .row-hidden { display: none !important; }
        .modal-inner-empty { text-align: center; padding: 40px; color: var(--c-text-sub); display: none; background: #fff; border-radius: 16px; border: 1px dashed var(--c-border); }

        @media (max-width: 1024px) { .clr-main-content { margin-left: 0; padding: 20px; } }
        @media (max-width: 600px) { .clr-date-row { grid-template-columns: 1fr; } .clr-tab-btn span { display: none; } }
    </style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="clr-layout-wrapper">
    <main class="clr-main-content">
        
        <div class="clr-alert-wrapper">
            <?php if ($success_msg): ?>
                <div class="clr-alert clr-alert-success">
                    <i data-lucide="check-circle"></i> <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="clr-alert clr-alert-error">
                    <i data-lucide="alert-circle"></i> <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>
        </div>

        <form class="clr-form-card" method="POST" action="">
            <input type="hidden" name="action" value="search">
            <div class="clr-brand-accent"></div>
            
            <div class="clr-card-header">
                <div class="clr-icon-circle"><i data-lucide="layers" size="32"></i></div>
                <h1>Commission Release</h1>
                <p>Initialize and update distribution ledger statuses</p>
            </div>

            <div class="clr-tabs-container">
                <div class="clr-tabs-track">
                    <button type="button" class="clr-tab-btn <?= ($filter_used === '' || $filter_used === 'customer') ? 'active' : '' ?>" data-target="tab-cust" data-type="customer">
                        <i data-lucide="user"></i> <span>Customer</span>
                    </button>
                    <button type="button" class="clr-tab-btn <?= ($filter_used === 'broker') ? 'active' : '' ?>" data-target="tab-brok" data-type="broker">
                        <i data-lucide="briefcase"></i> <span>Broker</span>
                    </button>
                    <button type="button" class="clr-tab-btn <?= ($filter_used === 'manager') ? 'active' : '' ?>" data-target="tab-mang" data-type="manager">
                        <i data-lucide="shield"></i> <span>Manager</span>
                    </button>
                    <button type="button" class="clr-tab-btn <?= ($filter_used === 'agent') ? 'active' : '' ?>" data-target="tab-agen" data-type="agent">
                        <i data-lucide="send"></i> <span>Agent</span>
                    </button>
                </div>
            </div>

            <input type="hidden" name="filter_type" id="clr-type-input" value="<?= htmlspecialchars($filter_used !== '' ? $filter_used : 'customer') ?>">

            <div class="clr-form-body">
                <div id="tab-cust" class="clr-tab-pane <?= ($filter_used === '' || $filter_used === 'customer') ? 'active' : '' ?>">
                    <div class="clr-field-group">
                        <label class="clr-label">Search Customer</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="search"></i>
                            <select name="customer_id" class="clr-select">
                                <option value="">Select individual customer...</option>
                                <?php foreach ($customer_list as $customer): ?>
                                    <option value="<?= htmlspecialchars($customer['customer_id']) ?>"><?= htmlspecialchars($customer['customer_fullname']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="tab-brok" class="clr-tab-pane <?= ($filter_used === 'broker') ? 'active' : '' ?>">
                    <div class="clr-field-group">
                        <label class="clr-label">Select Broker</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="briefcase"></i>
                            <select name="broker_id" class="clr-select">
                                <option value="">Select registered broker...</option>
                                <?php foreach ($brokers as $b): ?>
                                    <option value="<?= htmlspecialchars($b['broker_id']) ?>"><?= htmlspecialchars($b['broker_fullname']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="tab-mang" class="clr-tab-pane <?= ($filter_used === 'manager') ? 'active' : '' ?>">
                    <div class="clr-field-group">
                        <label class="clr-label">Unit Manager</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="shield-check"></i>
                            <select name="manager_id" class="clr-select">
                                <option value="">Select manager...</option>
                                <?php foreach ($managers as $m): ?>
                                    <option value="<?= htmlspecialchars($m['um_id']) ?>"><?= htmlspecialchars($m['um_fullname']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="tab-agen" class="clr-tab-pane <?= ($filter_used === 'agent') ? 'active' : '' ?>">
                    <div class="clr-field-group">
                        <label class="clr-label">Sales Agent</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="user-plus"></i>
                            <select name="agent_id" class="clr-select">
                                <option value="">Select agent...</option>
                                <?php foreach ($agents as $a): ?>
                                    <option value="<?= htmlspecialchars($a['agent_id']) ?>"><?= htmlspecialchars($a['agent_fullname']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="clr-divider">

                <div class="clr-date-row">
                    <div class="clr-field-group">
                        <label class="clr-label">Date From</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="calendar"></i>
                            <input type="date" name="date_from" class="clr-input-date" value="<?= htmlspecialchars($_POST['date_from'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="clr-field-group">
                        <label class="clr-label">Date To</label>
                        <div class="clr-input-wrapper">
                            <i data-lucide="calendar-check"></i>
                            <input type="date" name="date_to" class="clr-input-date" value="<?= htmlspecialchars($_POST['date_to'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="clr-submit-button">
                        <i data-lucide="search"></i> <span>Search Ledger</span>
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<?php if ($modal_results !== null): ?>
<div class="clr-modal-overlay show" id="commissionModal">
    <div class="clr-modal-content">
        <div class="clr-modal-header">
            <h2><i data-lucide="file-text"></i> Commission Ledger Result</h2>
            <button class="clr-modal-close" onclick="closeModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div class="clr-modal-body">
            <?php if (empty($modal_results)): ?>
                <div class="clr-empty-state">
                    <i data-lucide="inbox" size="48" style="color: #cbd5e1; margin-bottom: 16px;"></i>
                    <h3>No Data Found</h3>
                    <p>No valid commissions match your selected criteria.</p>
                </div>
            <?php else: ?>
                <div class="clr-modal-tabs">
                    <button class="clr-modal-tab-btn active" onclick="filterModalTable('pending', this)">Unreleased (Pending)</button>
                    <button class="clr-modal-tab-btn" onclick="filterModalTable('released', this)">Released</button>
                </div>

                <div id="modalInnerEmptyState" class="modal-inner-empty">
                    <i data-lucide="inbox" size="32" style="color: #cbd5e1; margin-bottom: 12px;"></i>
                    <p>No commissions found for this status tab.</p>
                </div>

                <div class="clr-table-container" id="modalTableContainer" style="overflow-x: auto;">
                    <table class="clr-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Due Date</th>
                                <?php if ($filter_used === 'customer'): ?>
                                    <th>Agent</th>
                                    <th>Broker</th>
                                    <th>Manager</th>
                                    <th>Broker Com.</th>
                                    <th>UM Com.</th>
                                    <th>Agent Com.</th>
                                <?php elseif ($filter_used === 'agent'): ?>
                                    <th>Agent Name</th>
                                    <th>Agent Com.</th>
                                <?php elseif ($filter_used === 'broker'): ?>
                                    <th>Broker Name</th>
                                    <th>Broker Com.</th>
                                <?php elseif ($filter_used === 'manager'): ?>
                                    <th>Manager Name</th>
                                    <th>UM Com.</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modal_results as $row): 
                                // Determine the row's overall status for the active filter view
                                $row_status = 'released';
                                if ($filter_used === 'customer') {
                                    $bs = $row['broker_commission_status'] ?? '';
                                    $us = $row['um_commission_status'] ?? '';
                                    $as = $row['agent_commission_status'] ?? '';
                                    // If any status exists and is NOT 'Released', the row goes to 'Pending' tab
                                    if ((!empty($bs) && strcasecmp($bs, 'Released') !== 0) ||
                                        (!empty($us) && strcasecmp($us, 'Released') !== 0) ||
                                        (!empty($as) && strcasecmp($as, 'Released') !== 0)) {
                                        $row_status = 'pending';
                                    }
                                } elseif ($filter_used === 'agent') {
                                    $row_status = (strcasecmp($row['agent_commission_status'] ?? '', 'Released') === 0) ? 'released' : 'pending';
                                } elseif ($filter_used === 'broker') {
                                    $row_status = (strcasecmp($row['broker_commission_status'] ?? '', 'Released') === 0) ? 'released' : 'pending';
                                } elseif ($filter_used === 'manager') {
                                    $row_status = (strcasecmp($row['um_commission_status'] ?? '', 'Released') === 0) ? 'released' : 'pending';
                                }
                            ?>
                                <tr data-row-status="<?= $row_status ?>">
                                    <td><?= htmlspecialchars($row['customer_fullname'] ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y', strtotime($row['due_date'])) ?></td>
                                    
                                    <?php if ($filter_used === 'customer'): ?>
                                        <td><?= htmlspecialchars($row['agent_fullname'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['broker_fullname'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['um_fullname'] ?? 'N/A') ?></td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['broker_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['broker_commission_status'] ?? '') ?>
                                        </td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['um_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['um_commission_status'] ?? '') ?>
                                        </td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['agent_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['agent_commission_status'] ?? '') ?>
                                        </td>
                                        
                                    <?php elseif ($filter_used === 'agent'): ?>
                                        <td><?= htmlspecialchars($row['agent_fullname'] ?? 'N/A') ?></td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['agent_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['agent_commission_status'] ?? '') ?>
                                        </td>
                                        
                                    <?php elseif ($filter_used === 'broker'): ?>
                                        <td><?= htmlspecialchars($row['broker_fullname'] ?? 'N/A') ?></td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['broker_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['broker_commission_status'] ?? '') ?>
                                        </td>
                                        
                                    <?php elseif ($filter_used === 'manager'): ?>
                                        <td><?= htmlspecialchars($row['um_fullname'] ?? 'N/A') ?></td>
                                        <td class="clr-amount">
                                            ₱<?= number_format($row['um_commission_amount'] ?? 0, 2) ?>
                                            <?= renderStatusBadge($row['um_commission_status'] ?? '') ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($modal_results)): ?>
        <div class="clr-modal-footer" id="modalReleaseFooter">
            <form method="POST" action="">
                <input type="hidden" name="action" value="release">
                <input type="hidden" name="filter_type" value="<?= htmlspecialchars($filter_used) ?>">
                <input type="hidden" name="target_id" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                <input type="hidden" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                
                <button type="submit" class="clr-submit-button success-btn" style="width: auto; padding: 14px 28px;" onclick="return confirm('Confirm release of these commissions? This will update their status to \'Released\'.');">
                    <i data-lucide="check-circle"></i> Release Commission
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
    lucide.createIcons();

    const tabBtns = document.querySelectorAll('.clr-tab-btn');
    const tabPanes = document.querySelectorAll('.clr-tab-pane');
    const typeInput = document.getElementById('clr-type-input');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            
            const target = btn.getAttribute('data-target');
            document.getElementById(target).classList.add('active');
            typeInput.value = btn.getAttribute('data-type');
        });
    });

    function closeModal() {
        const modal = document.getElementById('commissionModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('commissionModal');
        if (e.target === modal) {
            closeModal();
        }
    });

    // --- NEW LOGIC FOR MODAL INTERNAL STATUS TABS ---
    function filterModalTable(status, btnElement) {
        // Switch Active Class on Modal Tabs
        if (btnElement) {
            document.querySelectorAll('.clr-modal-tab-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
        }

        const rows = document.querySelectorAll('.clr-table tbody tr');
        let hasVisibleRows = false;
        
        // Hide/Show Rows by Status
        rows.forEach(row => {
            if (row.getAttribute('data-row-status') === status) {
                row.classList.remove('row-hidden');
                hasVisibleRows = true;
            } else {
                row.classList.add('row-hidden');
            }
        });

        // Handle Empty Table Visuals
        const emptyState = document.getElementById('modalInnerEmptyState');
        const tableContainer = document.getElementById('modalTableContainer');
        
        if (tableContainer && emptyState) {
            if (hasVisibleRows) {
                tableContainer.style.display = 'block';
                emptyState.style.display = 'none';
            } else {
                tableContainer.style.display = 'none';
                emptyState.style.display = 'block';
            }
        }

        // Hide the Release Submit Button if viewing the "Released" tab
        const releaseBtnDiv = document.getElementById('modalReleaseFooter');
        if (releaseBtnDiv) {
            if (status === 'pending' && hasVisibleRows) {
                releaseBtnDiv.style.display = 'flex';
            } else {
                releaseBtnDiv.style.display = 'none';
            }
        }
    }

    // Initialize modal tab filter automatically if modal opens
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('commissionModal')) {
            const initialTabBtn = document.querySelector('.clr-modal-tab-btn.active');
            if (initialTabBtn) {
                filterModalTable('pending', initialTabBtn);
            }
        }
    });
</script>
</body>
</html>