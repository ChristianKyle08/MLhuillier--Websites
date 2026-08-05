<?php
ob_start(); 
session_start(); 
include '../../config/config.php';

// 1. Session Security: Prevent Session Fixation
if (isset($_SESSION['admin_name'])) {
    session_regenerate_id(true); 
}

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Security Check: Enforce Admin Access
if (!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email']) || $_SESSION['user_type'] !== 'admin') {
    header('location: ../../user/rental/login_form.php');
    exit;
}

// 2. SERVER-SIDE PASSWORD VALIDATION (AJAX Endpoint)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_db_auth') {
    header('Content-Type: application/json');
    $inputPassword = $_POST['password'] ?? '';
    
    $serverSecret = 'CADMLhuillierDB2023'; 

    if ($inputPassword === $serverSecret) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit; 
}

// 2b. AJAX ENDPOINT: Fetch Unique Contracts Associated with a Selected Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_branch_contracts') {
    header('Content-Type: application/json');
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $stmt = $conn->prepare("SELECT DISTINCT contract_number FROM transactional WHERE branch_id = ? AND contract_number IS NOT NULL AND contract_number != ''");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $contracts = [];
    while ($r = $res->fetch_assoc()) {
        $contracts[] = $r['contract_number'];
    }
    echo json_encode(['success' => true, 'contracts' => $contracts]);
    exit;
}

// 2c. AJAX ENDPOINT: Fetch Payment Ledger Details for a Specific Contract
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_contract_ledger') {
    header('Content-Type: application/json');
    $contract_number = $_POST['contract_number'] ?? '';
    // Selected edit_amount_lessor column from transactional table
    $stmt = $conn->prepare("SELECT transaction_date, contract_start, contract_end, payment_due_date, amount, vat_type, net_of_vat, vat_amount, wtax, total_month_rental, amount_lessor, edit_amount_lessor, mode_of_payment, status, kptn, rfp_number, check_number, bank_name FROM transactional WHERE contract_number = ? ORDER BY payment_due_date ASC, id ASC");
    $stmt->bind_param("s", $contract_number);
    $stmt->execute();
    $res = $stmt->get_result();
    $ledger = [];
    while ($r = $res->fetch_assoc()) {
        $ledger[] = $r;
    }
    echo json_encode(['success' => true, 'ledger' => $ledger]);
    exit;
}

// --- Fetch Comprehensive KPIs ---
$kpiSql = "SELECT 
    (SELECT COUNT(*) FROM user_form WHERE status = 'Active') AS active_users,
    (SELECT COUNT(*) FROM lessor_profile) AS total_lessors,
    (SELECT COALESCE(SUM(amount), 0) FROM transactional) AS total_transactions,
    (SELECT COUNT(*) FROM escalation WHERE status = 'Pending' OR status = 'Active') AS active_escalations,
    (SELECT COUNT(*) FROM create_contract) AS total_contracts,
    (SELECT COUNT(*) FROM create_contract WHERE request_status = 'Approved') AS approved_contracts
";
$kpiResult = $conn->query($kpiSql);
$kpi = $kpiResult->fetch_assoc();

// Calculate Approval Rate safely
$approvalRate = ($kpi['total_contracts'] > 0) ? round(($kpi['approved_contracts'] / $kpi['total_contracts']) * 100, 1) : 0;
$pendingContracts = $kpi['total_contracts'] - $kpi['approved_contracts'];

// --- Fetch Granular Pipeline Data Separated by Main Zone & Running Transaction Counts ---
$sql = "
    SELECT 
        mainzone,
        branch_id,
        branch,
        region,
        COUNT(id) AS pipeline_activities,
        SUM(CASE WHEN request_status = 'Created' THEN 1 ELSE 0 END) AS Created,
        SUM(CASE WHEN request_status = 'Prepared' THEN 1 ELSE 0 END) AS Prepared,
        SUM(CASE WHEN request_status = 'Reviewed' THEN 1 ELSE 0 END) AS Reviewed,
        SUM(CASE WHEN request_status = 'Received' THEN 1 ELSE 0 END) AS Received,
        SUM(CASE WHEN request_status = 'Checked' THEN 1 ELSE 0 END) AS Checked,
        SUM(CASE WHEN request_status = 'Approved' THEN 1 ELSE 0 END) AS Approved,
        (SELECT COUNT(DISTINCT contract_number) FROM transactional WHERE transactional.branch_id = create_contract.branch_id) AS running_col_count,
        (SELECT GROUP_CONCAT(DISTINCT contract_number SEPARATOR ', ') FROM transactional WHERE transactional.branch_id = create_contract.branch_id) AS col_details
    FROM create_contract
    GROUP BY mainzone, branch_id, branch, region
    ORDER BY mainzone ASC, region ASC, branch ASC
";

$result = $conn->query($sql);

$totals = [
    'Created' => 0, 'Prepared' => 0, 'Reviewed' => 0,
    'Received' => 0, 'Checked' => 0, 'Approved' => 0
];

// Data arrays for Charts
$chartBranches = [];
$chartApproved = [];
$chartPending = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totals['Created'] += $row['Created'];
        $totals['Prepared'] += $row['Prepared'];
        $totals['Reviewed'] += $row['Reviewed'];
        $totals['Received'] += $row['Received'];
        $totals['Checked'] += $row['Checked'];
        $totals['Approved'] += $row['Approved'];

        if (count($chartBranches) < 10) {
            // Contextual label linking mainzone to branch for clearer chart insights
            $zonePrefix = $row['mainzone'] ? $row['mainzone'] . ' - ' : '';
            $chartBranches[] = $zonePrefix . ($row['branch'] ?: 'BR-'.$row['branch_id']);
            $chartApproved[] = $row['Approved'];
            $chartPending[] = ($row['Created'] + $row['Prepared'] + $row['Reviewed'] + $row['Received'] + $row['Checked']);
        }
    }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ML Rental Admin Dashboard - Clean and Aesthetic Property Ledger Analytics Dashboard Overview">
    <meta name="robots" content="noindex, nofollow">
    
    <title>ML Rental - Admin Dashboard Overview</title>
    <link rel="icon" href="../../assets/images/ml_logo.png">
    
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
    :root {
        --c-primary: #4f46e5;
        --c-secondary: #7c3aed;
        --c-success: #10b981;
        --c-warning: #f59e0b;
        --c-danger: #f43f5e;
        --c-info: #0ea5e9;
        
        --bg-body: #f8fafc;
        --bg-surface: #ffffff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --radius: 12px;
    }
    
    body { font-family: 'Poppins', sans-serif; font-size: 13.5px; background-color: var(--bg-body); color: var(--text-main); animation: fadePage 0.5s ease-in-out; }
    @keyframes fadePage{ from{opacity:0; transform:translateY(8px);} to{opacity:1; transform:translateY(0);} }
    
    .navbar { background: var(--bg-surface) !important; border-bottom: 1px solid var(--border-color); box-shadow: var(--shadow-sm); }
    .navbar .nav-link { color: var(--text-main) !important; font-weight: 500; }
    .navbar .nav-link:hover { color: var(--c-primary) !important; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .kpi-card { background: var(--bg-surface); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-md); display: flex; align-items: flex-start; justify-content: space-between; border: 1px solid var(--border-color); transition: all 0.3s ease; }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--c-primary); }
    .kpi-info h2 { font-size: 28px; font-weight: 700; margin: 5px 0 0 0; color: var(--text-main); }
    .kpi-info p { margin: 0; font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-icon-wrapper { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    .panel { background: var(--bg-surface); border-radius: var(--radius); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); margin-bottom: 2rem; overflow: hidden; }
    .panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(248, 250, 252, 0.5); }
    .panel-title { font-size: 15px; font-weight: 600; margin: 0; color: var(--text-main); }
    .chart-box { position: relative; height: 320px; width: 100%; padding: 1rem; }

    .table-container { max-height: 600px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-modern th { position: sticky; top: 0; z-index: 10; background: rgba(241, 245, 249, 0.95); backdrop-filter: blur(4px); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid var(--border-color); box-shadow: 0 2px 2px -1px rgba(0,0,0,0.05); }
    .table-modern td { padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
    .table-modern tbody tr { content-visibility: auto; contain-intrinsic-size: 60px; transition: background-color 0.2s ease; }
    .table-modern tbody tr:hover { background-color: #f8fafc; }
    
    .badge-modern { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: 0.3px; display: inline-block; }
    .bg-soft-primary { background: #e0e7ff; color: var(--c-primary); }
    .bg-soft-warning { background: #fef3c7; color: #d97706; }
    .bg-soft-info    { background: #e0f2fe; color: var(--c-info); }
    .bg-soft-violet  { background: #ede9fe; color: var(--c-secondary); }
    .bg-soft-success { background: #d1fae5; color: var(--c-success); }
    .bg-soft-danger  { background: #ffe4e6; color: var(--c-danger); }
    .bg-soft-slate   { background: #f1f5f9; color: var(--text-muted); }

    .btn-primary-modern { background: var(--c-primary); color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 500; transition: background 0.3s; }
    .btn-primary-modern:hover { background: #4338ca; color: white; }
    
    .table-container::-webkit-scrollbar { width: 8px; height: 8px; }
    .table-container::-webkit-scrollbar-track { background: transparent; }
    .table-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .table-container::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    
    .zone-separator-row { background-color: #f1f5f9 !important; font-weight: 700; text-transform: uppercase; color: var(--c-primary); font-size: 11px; letter-spacing: 1px; }

    /* Custom styles for Enhanced Aesthetic Ledger Presentation */
    .ledger-table-modern th { background: #f8fafc !important; color: #475569 !important; font-size: 11px !important; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e1 !important; text-transform: uppercase; }
    .ledger-table-modern td { font-size: 13px !important; border-bottom: 1px dashed #e2e8f0 !important; color: #334155; }
    .ledger-table-modern tbody tr:nth-child(even) { background-color: #fdfdfd; }
    </style>
</head>
<body>
    <?php include('navbar_admin.php'); ?>

    <main class="container-fluid px-4 mt-4 mb-5">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1 fw-bold" style="color: var(--text-main);">Dashboard Overview</h1>
                <p class="text-muted mb-0">System performance and contract pipeline analytics.</p>
            </div>
            <div class="bg-white px-3 py-2 rounded-pill shadow-sm border text-muted fw-medium">
                <i class="bi bi-calendar2-event text-primary me-2"></i> <?php echo date('F d, Y'); ?>
            </div>
        </header>

        <section class="kpi-grid" aria-label="Key Performance Indicators">
            <article class="kpi-card">
                <div class="kpi-info">
                    <p>Total Contracts</p>
                    <h2><?= number_format($kpi['total_contracts']) ?></h2>
                </div>
                <div class="kpi-icon-wrapper" style="background: #e0e7ff; color: var(--c-primary);"><i class="bi bi-folder2-open"></i></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-info">
                    <p>Approval Rate</p>
                    <h2><?= $approvalRate ?>%</h2>
                </div>
                <div class="kpi-icon-wrapper" style="background: #d1fae5; color: var(--c-success);"><i class="bi bi-patch-check"></i></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-info">
                    <p>Pending Contracts</p>
                    <h2><?= number_format($pendingContracts) ?></h2>
                </div>
                <div class="kpi-icon-wrapper" style="background: #fef3c7; color: var(--c-warning);"><i class="bi bi-hourglass-split"></i></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-info">
                    <p>Active Users</p>
                    <h2><?= number_format($kpi['active_users']) ?></h2>
                </div>
                <div class="kpi-icon-wrapper" style="background: #ede9fe; color: var(--c-secondary);"><i class="bi bi-people"></i></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-info">
                    <p>Registered Lessors</p>
                    <h2><?= number_format($kpi['total_lessors']) ?></h2>
                </div>
                <div class="kpi-icon-wrapper" style="background: #e0f2fe; color: var(--c-info);"><i class="bi bi-buildings"></i></div>
            </article>
        </section>

        <section class="row g-4 mb-4" aria-label="Analytical Charts">
            <div class="col-xl-4">
                <div class="panel h-100">
                    <header class="panel-header"><h3 class="panel-title">Contract Pipeline Distribution</h3></header>
                    <div class="chart-box d-flex align-items-center justify-content-center">
                        <canvas id="doughnutChart" aria-label="Doughnut chart showing contract statuses" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="panel h-100">
                    <header class="panel-header"><h3 class="panel-title">Top 10 Branches: Pending vs Approved</h3></header>
                    <div class="chart-box">
                        <canvas id="barChart" aria-label="Bar chart showing pending and approved contracts per branch" role="img"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" aria-label="Branch Performance Table">
            <header class="panel-header">
                <h3 class="panel-title">Branch Performance & Contract Status Breakdown</h3>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search ID, Branch, or Region real-time..." style="width: 280px; border-radius: 8px;">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print or Save as PDF">
                        <i class="bi bi-printer me-1"></i> Export PDF
                    </button>
                </div>
            </header>
            
            <div class="table-container">
                <table class="table-modern text-center">
                    <thead>
                        <tr>
                            <th scope="col" class="text-start ps-4">Branch Details</th>
                            <th scope="col">Running Contract of Lease Count</th>
                            <th scope="col" class="text-start ps-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php 
                            $currentZone = null;
                            $grandTotalRunningCols = 0;
                            while ($row = $result->fetch_assoc()): 
                                $zoneValue = $row['mainzone'] ?: 'UNASSIGNED ZONE';
                                $grandTotalRunningCols += $row['running_col_count'];

                                // Visual Section Break whenever Main Zone value changes
                                if ($currentZone !== $zoneValue): 
                                    $currentZone = $zoneValue;
                            ?>
                                <tr class="zone-separator-row">
                                    <td colspan="3" class="text-start ps-4 py-2">
                                        <i class="bi bi-geo-alt-fill me-2"></i>MAIN ZONE: <?= htmlspecialchars($currentZone, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <tr>
                            <td class="text-start ps-4">
                                <span class="fw-bold d-block"><?= htmlspecialchars($row['branch'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-muted" style="font-size: 11px;">ID: <?= htmlspecialchars($row['branch_id'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($row['region'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-link p-0 text-decoration-none launch-ledger-trigger" data-branch-id="<?= $row['branch_id'] ?>" data-branch-name="<?= htmlspecialchars($row['branch'], ENT_QUOTES, 'UTF-8') ?>" title="Click to view payment ledger details">
                                    <span class="badge-modern bg-soft-primary fw-bold" style="cursor: pointer;"><?= number_format($row['running_col_count']) ?></span>
                                </button>
                            </td>
                            <td class="text-start ps-4 d-hidden">
                                <span class="text-secondary d-none"><?= htmlspecialchars($row['col_details'] ?: 'No active running col records found', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                        </tr>
                            <?php endwhile; ?>
                            
                            <tr id="grand-total-row" style="background: #f1f5f9; font-weight: 700; position: sticky; bottom: 0; z-index: 5; box-shadow: 0 -2px 5px rgba(0,0,0,0.05);">
                                <td class="text-start ps-4 text-primary">GRAND TOTAL</td>
                                <td><?= number_format($grandTotalRunningCols) ?></td>
                                <td class="text-start ps-4">-</td>
                            </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-5 text-muted">No contract data available at the branch level.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal fade" id="ledgerViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0" style="border-radius: var(--radius); max-height: 92vh;">
                <div class="modal-header panel-header bg-white border-bottom shadow-sm">
                    <h5 class="modal-title fw-bold text-primary d-flex align-items-center" id="ledgerModalTitle">
                        <i class="bi bi-wallet2 me-2"></i> Payment Ledger Verification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light-subtle">
                    <div class="row g-3 mb-4 align-items-center bg-white p-3 rounded-3 shadow-sm border border-light">
                        <div class="col-md-7">
                            <label for="ledgerContractSelect" class="form-label fw-bold text-muted small text-uppercase mb-1">Active Contract Lookup Selector</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-file-earmark-text"></i></span>
                                <select id="ledgerContractSelect" class="form-select form-select-sm border-start-0" style="border-radius: 0 8px 8px 0; font-weight: 500;">
                                    <option value="">-- Choose target contract reference code --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end ledger-meta-block d-none px-3">
                            <span class="text-muted small d-block text-uppercase fw-semibold tracking-wider">Contract Active Span</span>
                            <span id="ledgerMetaDuration" class="badge bg-soft-primary px-3 py-2 fw-bold text-dark border mt-1" style="font-size:12.5px;">-</span>
                        </div>
                    </div>

                    <div id="ledgerTableContainer" class="table-container border rounded shadow-sm bg-white d-none">
                        <table class="table table-sm table-modern ledger-table-modern align-middle text-center m-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-start">Due Date</th>
                                    <th scope="col" class="text-end">Base Amount</th>
                                    <th scope="col" class="text-end">Net of VAT</th>
                                    <th scope="col" class="text-end">VAT Amt</th>
                                    <th scope="col" class="text-end">W-Tax</th>
                                    <th scope="col" class="text-end pe-3">Amt to Lessor</th>
                                    <th scope="col">Payment Mode</th>
                                    <th scope="col" class="text-start">Kptn</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerTableBody">
                                </tbody>
                        </table>
                    </div>
                    
                    <div id="ledgerFallbackPrompt" class="text-center py-5 bg-white border rounded-3 shadow-sm text-muted">
                        <div class="p-4">
                            <i class="bi bi-journal-check display-5 d-block mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-semibold text-dark mb-1">No Contract Selected</h6>
                            <p class="small text-muted mb-0">Please select an active contract number identifier from the dropdown control menu above to populate the payment history spreadsheet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: var(--radius);">
                <div class="modal-body p-5">
                    <div class="text-center mb-4">
                        <div class="bg-soft-danger d-inline-block p-3 rounded-circle mb-3">
                            <i class="bi bi-shield-lock-fill text-danger fs-1"></i>
                        </div>
                        <h4 class="fw-bold">Database Authentication</h4>
                        <p class="text-muted">Enter administrative password to proceed.</p>
                    </div>
                    <input type="password" class="form-control form-control-lg mb-3" id="passwordInput" placeholder="Password" aria-label="Password">
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="showPassword">
                        <label class="form-check-label text-muted" for="showPassword">Reveal Password</label>
                    </div>
                    <button id="submitPassword" class="btn btn-primary-modern w-100 btn-lg d-flex justify-content-center align-items-center">
                        <span id="btnText">Authenticate Access</span>
                        <div id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 text-center p-5 shadow-lg" style="border-radius: var(--radius);">
                <div class="modal-body">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h4 class="fw-bold">Signing Out</h4>
                    <p class="text-muted">Securely disconnecting your session...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
    
    <script>
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#64748b';
        
        const pipelineData = [
            <?= $totals['Created'] ?>, <?= $totals['Prepared'] ?>, <?= $totals['Reviewed'] ?>, 
            <?= $totals['Checked'] ?>, <?= $totals['Approved'] ?>
        ];

        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Created', 'Prepared', 'Reviewed', 'Checked', 'Approved'],
                datasets: [{
                    data: pipelineData,
                    backgroundColor: ['#94a3b8', '#f59e0b', '#0ea5e9', '#4f46e5', '#10b981'],
                    borderWidth: 0, hoverOffset: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 20, font: {size: 12} } } }, cutout: '75%' }
        });

        const ctxBar = document.getElementById('barChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartBranches); ?>,
                datasets: [
                    { label: 'Pending Operations', data: <?php echo json_encode($chartPending); ?>, backgroundColor: '#e2e8f0', borderRadius: 4 },
                    { label: 'Approved Contracts', data: <?php echo json_encode($chartApproved); ?>, backgroundColor: '#4f46e5', borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, border: {display: false}, grid: { color: '#f1f5f9' } } }, plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } } }
        });

        const logoutLink = document.getElementById('logoutLink');
        if(logoutLink) {
            logoutLink.addEventListener('click', function(e){
                e.preventDefault();
                new bootstrap.Modal(document.getElementById('logoutModal'), {backdrop:'static', keyboard:false}).show();
                setTimeout(() => { window.location.href='../../logout.php' }, 1200);
            });
        }

        const passwordInput = document.getElementById('passwordInput');
        document.getElementById('showPassword').addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });

        document.getElementById('submitPassword').addEventListener('click', async () => {
            const password = passwordInput.value;
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            if(!password) return;

            btnText.innerText = "Verifying...";
            btnSpinner.classList.remove('d-none');
            document.getElementById('submitPassword').disabled = true;

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify_db_auth&password=${encodeURIComponent(password)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'db.php';
                } else {
                    Swal.fire({ icon: 'error', title: 'Authentication Failed', text: 'Incorrect password entered.', confirmButtonColor: '#4f46e5' });
                    passwordInput.value = '';
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Server Error', text: 'Could not communicate with the server.', confirmButtonColor: '#4f46e5' });
            } finally {
                btnText.innerText = "Authenticate Access";
                btnSpinner.classList.add('d-none');
                document.getElementById('submitPassword').disabled = false;
            }
        });

        // Real-Time Table Live Search Functionality
        document.getElementById('tableSearch').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector('.table-modern');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if(row.classList.contains('zone-separator-row') || row.id === 'grand-total-row') return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // --- INTERACTIVE PAYMENT LEDGER HANDLERS ---
        const bBootstrapLedgerModal = new bootstrap.Modal(document.getElementById('ledgerViewModal'));
        let targetSelectedBranchId = null;

        // Formats database dates into structured "F j, Y" strings on client-side
        const fmtDate = (dateStr) => {
            if (!dateStr || dateStr === '0000-00-00' || dateStr === '-') return '-';
            const dateObj = new Date(dateStr);
            if (isNaN(dateObj.getTime())) return dateStr;
            return dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        };

        document.querySelectorAll('.launch-ledger-trigger').forEach(triggerItem => {
            triggerItem.addEventListener('click', async function(e) {
                e.preventDefault();
                targetSelectedBranchId = this.getAttribute('data-branch-id');
                const branchNameStr = this.getAttribute('data-branch-name');
                
                document.getElementById('ledgerModalTitle').innerHTML = `<i class="bi bi-journal-text me-2"></i>Payment Ledger Spreadsheet &bull; ${branchNameStr} <span class="text-muted ms-1">(ID: ${targetSelectedBranchId})</span>`;
                
                const selectElement = document.getElementById('ledgerContractSelect');
                selectElement.innerHTML = '<option value="">-- Gathering associated contracts... --</option>';
                
                document.getElementById('ledgerTableContainer').classList.add('d-none');
                document.getElementById('ledgerFallbackPrompt').classList.remove('d-none');
                document.querySelector('.ledger-meta-block').classList.add('d-none');
                
                bBootstrapLedgerModal.show();

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=get_branch_contracts&branch_id=${targetSelectedBranchId}`
                    });
                    const resData = await response.json();
                    
                    if (resData.success && resData.contracts.length > 0) {
                        selectElement.innerHTML = '<option value="">-- Choose target contract reference code --</option>';
                        resData.contracts.forEach(cNum => {
                            const opt = document.createElement('option');
                            opt.value = cNum;
                            opt.textContent = cNum;
                            selectElement.appendChild(opt);
                        });
                    } else {
                        selectElement.innerHTML = '<option value="">No transactional contracts bound here</option>';
                    }
                } catch (err) {
                    selectElement.innerHTML = '<option value="">Error pulling database mappings</option>';
                }
            }); 
        });

        document.getElementById('ledgerContractSelect').addEventListener('change', async function() {
            const contractKey = this.value;
            const targetContainer = document.getElementById('ledgerTableContainer');
            const targetBody = document.getElementById('ledgerTableBody');
            const targetPrompt = document.getElementById('ledgerFallbackPrompt');
            const metaContainer = document.querySelector('.ledger-meta-block');
            const metaDurationSpan = document.getElementById('ledgerMetaDuration');

            if (!contractKey) {
                targetContainer.classList.add('d-none');
                targetPrompt.classList.remove('d-none');
                metaContainer.classList.add('d-none');
                return;
            }

            targetBody.innerHTML = '<tr><td colspan="10" class="py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Formatting transactional payment records...</td></tr>';
            targetContainer.classList.remove('d-none');
            targetPrompt.classList.add('d-none');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_contract_ledger&contract_number=${encodeURIComponent(contractKey)}`
                });
                const payload = await response.json();

                if (payload.success && payload.ledger.length > 0) {
                    targetBody.innerHTML = '';
                    
                    const firstItem = payload.ledger[0];
                    if (firstItem.contract_start && firstItem.contract_end) {
                        metaDurationSpan.innerText = `${fmtDate(firstItem.contract_start)} - ${fmtDate(firstItem.contract_end)}`;
                        metaContainer.classList.remove('d-none');
                    } else {
                        metaContainer.classList.add('d-none');
                    }

                    payload.ledger.forEach(ledgerRow => {
                        const trElement = document.createElement('tr');
                        
                        let badgeStyle = 'bg-soft-slate';
                        if (['Active', 'Paid', 'Approved', 'Settled'].includes(ledgerRow.status)) {
                            badgeStyle = 'bg-soft-success';
                        } else if (['Pending', 'Processing'].includes(ledgerRow.status)) {
                            badgeStyle = 'bg-soft-warning';
                        } else if (['Cancelled', 'Terminated'].includes(ledgerRow.status)) {
                            badgeStyle = 'bg-soft-danger';
                        }

                        const fmtCurr = (num) => parseFloat(num || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                        // Clean data rows mapping dates to fmtDate and amount tracking specifically to edit_amount_lessor
                        trElement.innerHTML = `
                            <td class="text-start text-muted">${fmtDate(ledgerRow.transaction_date)}</td>
                            <td class="text-end">₱${fmtCurr(ledgerRow.amount)}</td>
                            <td class="text-end text-secondary">₱${fmtCurr(ledgerRow.net_of_vat)}</td>
                            <td class="text-end text-secondary">₱${fmtCurr(ledgerRow.vat_amount)}</td>
                            <td class="text-end text-muted">₱${fmtCurr(ledgerRow.wtax)}</td>
                            <td class="text-end fw-bold text-success pe-3">₱${fmtCurr(ledgerRow.edit_amount_lessor)}</td>
                            <td><span class="badge-modern bg-soft-info" style="font-size:10px;">${ledgerRow.mode_of_payment || '-'}</span></td>
                            <td class="text-start small">${ledgerRow.kptn}</td>
                            <td><span class="badge-modern ${badgeStyle}" style="font-size:10px;">${ledgerRow.status || '-'}</span></td>
                        `;
                        targetBody.appendChild(trElement);
                    });
                } else {
                    targetBody.innerHTML = '<tr><td colspan="10" class="py-5 text-muted">No processing log matrix discovered for this entity code.</td></tr>';
                    metaContainer.classList.add('d-none');
                }
            } catch (err) {
                targetBody.innerHTML = '<tr><td colspan="10" class="py-5 text-danger">Data pipeline failure processing server objects.</td></tr>';
                metaContainer.classList.add('d-none');
            }
        });

        document.addEventListener('contextmenu', (e) => e.preventDefault());
        document.onkeydown = function(e) {
            if (e.keyCode == 123 || 
               (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74 || e.keyCode == 67)) || 
               (e.ctrlKey && e.keyCode == 85)) {
                return false; 
            }
        };
    </script>
</body>
</html>