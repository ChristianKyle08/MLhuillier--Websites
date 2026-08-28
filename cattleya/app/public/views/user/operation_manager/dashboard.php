<?php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id     = $_SESSION['user_id'];
$status_msg  = '';
$status_type = '';

// ==========================================
// 1. DIGITAL SIGNATURE UPLOAD HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_signature') {
    if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['signature_file']['tmp_name'];
        $fileName    = $_FILES['signature_file']['name'];
        $fileSize    = $_FILES['signature_file']['size'];
        $fileType    = $_FILES['signature_file']['type'];

        $allowedMimetypes = ['image/png', 'image/jpeg', 'image/webp'];
        $maxFileSize      = 2 * 1024 * 1024; // 2MB Limit

        if (!in_array($fileType, $allowedMimetypes)) {
            $status_msg  = 'Invalid file format. Only PNG, JPEG, and WEBP images are permitted.';
            $status_type = 'danger';
        } elseif ($fileSize > $maxFileSize) {
            $status_msg  = 'File size exceeds the 2MB limit.';
            $status_type = 'danger';
        } else {
            try {
                $fp = fopen($fileTmpPath, 'rb');
                
                $stmtUpd = $pdo->prepare("
                    UPDATE users 
                    SET signature = :sig, 
                        signature_name = :name, 
                        signature_type = :type 
                    WHERE id = :id
                ");
                
                $stmtUpd->bindParam(':sig', $fp, PDO::PARAM_LOB);
                $stmtUpd->bindParam(':name', $fileName, PDO::PARAM_STR);
                $stmtUpd->bindParam(':type', $fileType, PDO::PARAM_STR);
                $stmtUpd->bindParam(':id', $user_id, PDO::PARAM_INT);
                
                if ($stmtUpd->execute()) {
                    $status_msg  = 'Digital signature successfully synchronized!';
                    $status_type = 'success';
                } else {
                    $status_msg  = 'Database error: Unable to store signature.';
                    $status_type = 'danger';
                }
            } catch (Exception $e) {
                $status_msg  = 'Error processing file upload: ' . $e->getMessage();
                $status_type = 'danger';
            }
        }
    } else {
        $status_msg  = 'Please select a valid image file to upload.';
        $status_type = 'warning';
    }
}

// ==========================================
// 2. DASHBOARD DATA AGGREGATION
// ==========================================

// Fetch User & Signature Details
$stmtUser = $pdo->prepare("SELECT first_name, last_name, email, role, signature, signature_type FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

$user_name = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
if (empty($user_name)) {
    $user_name = $_SESSION['user_name'] ?? 'Executive User';
}
$user_role = strtoupper($currentUser['role'] ?? 'ENCODER');

// Convert LONGBLOB to Base64 Image
$signatureSrc = null;
if (!empty($currentUser['signature'])) {
    $blobData = is_resource($currentUser['signature']) ? stream_get_contents($currentUser['signature']) : $currentUser['signature'];
    $signatureSrc = 'data:' . ($currentUser['signature_type'] ?? 'image/png') . ';base64,' . base64_encode($blobData);
}

// Financial Metrics & Aggregations
try {
    $stmtTcp = $pdo->query("SELECT COUNT(sale_id) as count, SUM(tcp) as total FROM sales WHERE sales_status IN ('sold', 'reserved')");
    $salesSummary = $stmtTcp->fetch(PDO::FETCH_ASSOC);
    $totalSalesCount = (int)($salesSummary['count'] ?? 0);
    $totalTcp = (float)($salesSummary['total'] ?? 0.00);

    $stmtComm = $pdo->query("
        SELECT 
            SUM(agent_commission_amount) as total_agent,
            SUM(um_commission_amount) as total_um,
            SUM(broker_commission_amount) as total_broker
        FROM payments
    ");
    $commissions = $stmtComm->fetch(PDO::FETCH_ASSOC);
    $totalAgentComm  = (float)($commissions['total_agent'] ?? 0);
    $totalUmComm     = (float)($commissions['total_um'] ?? 0);
    $totalBrokerComm = (float)($commissions['total_broker'] ?? 0);
    $totalCommissions = $totalAgentComm + $totalUmComm + $totalBrokerComm;

    $stmtAgents = $pdo->query("SELECT COUNT(id) FROM agents WHERE status = 'Active'");
    $activeAgents = (int)$stmtAgents->fetchColumn();

    $stmtServices = $pdo->query("SELECT SUM(fee) FROM avail_services WHERE status != 'Cancelled'");
    $totalServicesRev = (float)$stmtServices->fetchColumn();

} catch (Exception $e) {
    $totalSalesCount = $totalTcp = $totalCommissions = $totalAgentComm = $totalUmComm = $totalBrokerComm = $activeAgents = $totalServicesRev = 0;
}

// Monthly Sales Trend Data
$currentYear = date('Y');
$stmtChart = $pdo->prepare("
    SELECT MONTH(created_at) AS sale_month, COUNT(sale_id) AS total_orders
    FROM sales
    WHERE YEAR(created_at) = :year AND sales_status IN ('sold', 'reserved')
    GROUP BY MONTH(created_at)
");
$stmtChart->execute(['year' => $currentYear]);
$salesResult = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

$monthlyVolume = array_fill(1, 12, 0);
foreach ($salesResult as $row) {
    $monthlyVolume[(int)$row['sale_month']] = (int)$row['total_orders'];
}
$chartDataJSON = json_encode(array_values($monthlyVolume));

// Commission Rules
$stmtProfiles = $pdo->query("SELECT * FROM commission_profiles WHERE is_active = 1 ORDER BY created_at DESC");
$activeProfiles = $stmtProfiles->fetchAll(PDO::FETCH_ASSOC);

// Recent Sales Logs
$stmtRecent = $pdo->query("
    SELECT s.*, 
           p.agent_commission_amount, p.um_commission_amount, p.broker_commission_amount,
           p.agent_commission_status, p.um_commission_status, p.broker_commission_status
    FROM sales s
    LEFT JOIN payments p ON s.sale_id = p.sale_id
    ORDER BY s.created_at DESC LIMIT 5
");
$recentSales = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cattleya | Executive Analytics Hub</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-050: #f0fdf4;
            --brand-100: #dcfce7;
            --brand-500: #10b981;
            --brand-600: #059669;
            --brand-700: #047857;
            --brand-900: #022c22;
            
            --slate-050: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-600: #475569;
            --slate-800: #1e293b;
            --slate-900: #0f172a;

            --card-radius: 20px;
            --card-shadow: 0 1px 2px 0 rgba(0,0,0,0.03), 0 8px 24px -4px rgba(15,23,42,0.04);
            --card-shadow-hover: 0 12px 32px -4px rgba(15,23,42,0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7fa;
            color: var(--slate-600);
            -webkit-font-smoothing: antialiased;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        .dashboard-container {
            max-width: 1680px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Executive Header Banner */
        .banner-card {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 40%, #059669 100%);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(2, 44, 34, 0.3);
        }

        .banner-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .role-badge {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
        }

        .sig-trigger-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 18px;
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sig-trigger-card:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
        }

        .sig-preview-thumb {
            width: 72px;
            height: 40px;
            background: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        /* Clean Minimal Cards */
        .metric-card {
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: var(--card-radius);
            padding: 1.5rem 1.75rem;
            box-shadow: var(--card-shadow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--slate-300);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .icon-box.emerald { background: var(--brand-050); color: var(--brand-600); }
        .icon-box.blue    { background: #eff6ff; color: #2563eb; }
        .icon-box.purple  { background: #faf5ff; color: #9333ea; }
        .icon-box.amber   { background: #fffbeb; color: #d97706; }

        /* Tables & Typography */
        .section-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.02em;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .table-clean {
            margin-bottom: 0;
            width: 100%;
        }

        .table-clean thead th {
            background-color: var(--slate-050);
            color: var(--slate-400);
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.07em;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--slate-200);
        }

        .table-clean tbody td {
            padding: 1.15rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.875rem;
            color: var(--slate-800);
        }

        .table-clean tbody tr:last-child td {
            border-bottom: none;
        }

        .table-clean tbody tr {
            transition: background 0.15s ease;
        }

        .table-clean tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Pill Badges */
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            font-size: 0.725rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .badge-pill.active { background: var(--brand-050); color: var(--brand-700); }
        .badge-pill.pending { background: #fffbeb; color: #b45309; }
        .badge-pill.neutral { background: var(--slate-100); color: var(--slate-600); }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
        }

        /* Modal & Drag-and-Drop */
        .modal-clean {
            border: none;
            border-radius: 24px;
            padding: 0.5rem;
        }

        .upload-drop-zone {
            border: 2px dashed var(--slate-300);
            border-radius: 16px;
            padding: 2.25rem 1.5rem;
            text-align: center;
            background: var(--slate-050);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .upload-drop-zone:hover, .upload-drop-zone.dragover {
            border-color: var(--brand-500);
            background: var(--brand-050);
        }

        .preview-box {
            width: 100%;
            height: 140px;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .preview-box img {
            max-width: 85%;
            max-height: 80%;
            object-fit: contain;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="dashboard-container">

    <!-- Status Alerts -->
    <?php if (!empty($status_msg)): ?>
        <div class="alert alert-<?= $status_type ?> alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 p-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $status_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($status_msg) ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Banner Header -->
    <div class="banner-card mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="role-badge"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($user_role) ?> CONSOLE</span>
            </div>
            <h2 class="fw-800 text-white mb-1 display-6">Good day, <?= htmlspecialchars($user_name) ?></h2>
            <p class="text-white-50 mb-0 small">Real-time overview of sales pipeline, commissions, and contract approvals.</p>
        </div>

        <div class="sig-trigger-card d-flex align-items-center gap-3" data-bs-toggle="modal" data-bs-target="#uploadSigModal">
            <div class="text-end">
                <div class="text-white-50 text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Digital Signature</div>
                <div class="text-white fw-bold small">
                    <?= $signatureSrc ? '✓ Active & Verified' : '⚠️ Upload Signature' ?>
                </div>
            </div>
            <div class="sig-preview-thumb">
                <?php if ($signatureSrc): ?>
                    <img src="<?= $signatureSrc ?>" alt="Signature" style="max-width: 100%; max-height: 100%;">
                <?php else: ?>
                    <i class="bi bi-pen-fill text-muted"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Portfolio Value</span>
                    <div class="icon-box emerald"><i class="bi bi-wallet2"></i></div>
                </div>
                <h3 class="fw-800 text-dark tabular-nums mb-2">₱<?= number_format($totalTcp, 2) ?></h3>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge-pill active"><span class="pulse-dot"></span> Active</span>
                    <span class="text-muted small fw-semibold"><?= number_format($totalSalesCount) ?> Contracts</span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Commissions</span>
                    <div class="icon-box blue"><i class="bi bi-pie-chart-fill"></i></div>
                </div>
                <h3 class="fw-800 text-dark tabular-nums mb-2">₱<?= number_format($totalCommissions, 2) ?></h3>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-medium">Agent Allocated</span>
                    <span class="fw-bold text-dark small tabular-nums">₱<?= number_format($totalAgentComm, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Services Revenue</span>
                    <div class="icon-box purple"><i class="bi bi-diagram-3-fill"></i></div>
                </div>
                <h3 class="fw-800 text-dark tabular-nums mb-2">₱<?= number_format($totalServicesRev, 2) ?></h3>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge-pill neutral">Ancillary</span>
                    <span class="text-muted small fw-medium">Operations</span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Active Sales Force</span>
                    <div class="icon-box amber"><i class="bi bi-person-vcard-fill"></i></div>
                </div>
                <h3 class="fw-800 text-dark tabular-nums mb-2"><?= number_format($activeAgents) ?></h3>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge-pill pending"><span class="pulse-dot"></span> Verified</span>
                    <span class="text-muted small fw-medium">Agents</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="row g-4 mb-4">
        <!-- Chart -->
        <div class="col-xl-7">
            <div class="metric-card h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="section-title mb-0">Sales Volume Analytics</h4>
                        <span class="text-muted small">Monthly contract velocity for <?= $currentYear ?></span>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold small"><i class="bi bi-calendar3 me-1"></i>FY <?= $currentYear ?></span>
                </div>
                <div class="flex-grow-1" style="min-height: 260px; position: relative;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Commission Profiles -->
        <div class="col-xl-5">
            <div class="table-card h-100 d-flex flex-column">
                <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="section-title mb-0">Commission Matrix</h4>
                        <span class="text-muted small">Configured split rules</span>
                    </div>
                    <span class="badge-pill active"><span class="pulse-dot"></span> <?= count($activeProfiles) ?> Active Tiers</span>
                </div>
                <div class="table-responsive flex-grow-1" style="max-height: 290px; overflow-y: auto;">
                    <table class="table table-clean align-middle">
                        <thead>
                            <tr>
                                <th>Split (Agent / UM / Broker)</th>
                                <th>Release Schedule</th>
                                <th>Division</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activeProfiles)): ?>
                                <?php foreach($activeProfiles as $prof): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark tabular-nums">
                                                <?= number_format($prof['agent_pct'], 1) ?>% / <?= number_format($prof['um_pct'], 1) ?>% / <?= number_format($prof['broker_pct'], 1) ?>%
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border rounded-3 font-monospace px-2 py-1"><?= htmlspecialchars($prof['release_day']) ?></span></td>
                                        <td class="text-muted fw-medium"><?= htmlspecialchars($prof['duration']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No commission tiers configured.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Logs -->
    <div class="table-card">
        <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h4 class="section-title mb-0">Recent Property Acquisitions</h4>
                <span class="text-muted small">Latest sales records and disbursement track</span>
            </div>
            <button class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" onclick="location.reload();">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-clean align-middle">
                <thead>
                    <tr>
                        <th>Property Details</th>
                        <th>Client</th>
                        <th>Contract TCP</th>
                        <th>Agent Comm.</th>
                        <th>UM Comm.</th>
                        <th>Broker Comm.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentSales)): ?>
                        <?php foreach($recentSales as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['product_name'] ?? 'Property Item') ?></div>
                                    <small class="text-muted">Blk <?= htmlspecialchars($row['block_number'] ?? '0') ?> • Lot <?= htmlspecialchars($row['lot_number'] ?? '0') ?></small>
                                </td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['customer_fullname'] ?? 'N/A') ?></td>
                                <td class="fw-800 text-dark tabular-nums">₱<?= number_format($row['tcp'] ?? 0, 2) ?></td>
                                <td>
                                    <div class="fw-bold text-dark tabular-nums">₱<?= number_format($row['agent_commission_amount'] ?? 0, 2) ?></div>
                                    <span class="badge-pill pending py-0 px-2 mt-1" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars($row['agent_commission_status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark tabular-nums">₱<?= number_format($row['um_commission_amount'] ?? 0, 2) ?></div>
                                    <span class="badge-pill pending py-0 px-2 mt-1" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars($row['um_commission_status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark tabular-nums">₱<?= number_format($row['broker_commission_amount'] ?? 0, 2) ?></div>
                                    <span class="badge-pill pending py-0 px-2 mt-1" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars($row['broker_commission_status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-pill active">
                                        <span class="pulse-dot"></span>
                                        <?= strtoupper(htmlspecialchars($row['sales_status'] ?? 'ACTIVE')) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No sales activities recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Upload Digital Signature Modal -->
<div class="modal fade" id="uploadSigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-clean p-3 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="fw-800 text-dark mb-0">Digital Signature</h5>
                    <p class="text-muted small mb-0">Upload a clean PNG, JPEG, or WEBP image.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" id="sigUploadForm">
                <input type="hidden" name="action" value="upload_signature">
                <div class="modal-body py-4">
                    
                    <div class="upload-drop-zone mb-3" id="dropZone">
                        <i class="bi bi-cloud-arrow-up text-success display-6 mb-2 d-block"></i>
                        <span class="fw-bold text-dark d-block mb-1">Click to browse or drop file here</span>
                        <span class="text-muted small">Supported: PNG, JPEG, WEBP (Max 2MB)</span>
                        <input type="file" id="signature_file" name="signature_file" accept="image/png, image/jpeg, image/webp" class="d-none" required>
                    </div>

                    <div>
                        <label class="form-label fw-bold text-dark small">Preview</label>
                        <div class="preview-box" id="previewBox">
                            <?php if ($signatureSrc): ?>
                                <img src="<?= $signatureSrc ?>" id="sigPreviewImage" alt="Current Signature">
                            <?php else: ?>
                                <span class="text-muted small" id="previewPlaceholder"><i class="bi bi-image me-1"></i>No file attached</span>
                                <img src="" id="sigPreviewImage" alt="Signature Preview" style="display:none;">
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold" style="background: var(--brand-600);">Save Signature</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Rendering
const ctx = document.getElementById('salesChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 260);
gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Transactions',
            data: <?= $chartDataJSON ?>,
            borderColor: '#10b981',
            borderWidth: 3,
            backgroundColor: gradient,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#059669',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 12,
                cornerRadius: 10,
                titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                bodyFont: { family: 'Plus Jakarta Sans', size: 12 }
            }
        },
        scales: {
            y: {
                grid: { color: '#f1f5f9' },
                ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
            }
        }
    }
});

// Interactive Drag and Drop Upload
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('signature_file');
const previewImg = document.getElementById('sigPreviewImage');
const placeholder = document.getElementById('previewPlaceholder');

dropZone.addEventListener('click', () => fileInput.click());

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    }, false);
});

dropZone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length) {
        fileInput.files = files;
        handleFilePreview(files[0]);
    }
});

fileInput.addEventListener('change', function(e) {
    if (e.target.files.length) {
        handleFilePreview(e.target.files[0]);
    }
});

function handleFilePreview(file) {
    if (file.size > 2 * 1024 * 1024) {
        alert('File size exceeds 2MB limit.');
        fileInput.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(event) {
        previewImg.src = event.target.result;
        previewImg.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>
</body>
</html>