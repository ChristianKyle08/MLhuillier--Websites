<?php
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id     = $_SESSION['user_id'];
$user_name   = $_SESSION['user_name'];
$user_email  = $_SESSION['user_email'];

// Dynamic Greeting Logic
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 17) ? "Good Afternoon" : "Good Evening");

$words = explode(" ", $user_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= $w[0]; }
$user_initials = strtoupper(substr($initials, 0, 2));

// Fetch User Signature
$stmt = $pdo->prepare("SELECT signature, signature_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$signatureSrc = ($user && $user['signature']) 
    ? 'data:' . ($user['signature_type'] ?? 'image/png') . ';base64,' . $user['signature']
    : '/assets/images/signature-placeholder.png';

// 1. Fetch sales volume
$currentYear = date('Y');
$query = "
    SELECT MONTH(created_at) AS sale_month, COUNT(sale_id) AS total_orders
    FROM sales
    WHERE YEAR(created_at) = :year
      AND sales_status IN ('sold', 'reserved') 
    GROUP BY MONTH(created_at)
";
$stmt = $pdo->prepare($query);
$stmt->execute(['year' => $currentYear]);
$salesResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthlyData = array_fill(1, 12, 0);
foreach ($salesResult as $row) {
    $monthlyData[(int)$row['sale_month']] = (int)$row['total_orders'];
}
$chartDataJSON = json_encode(array_values($monthlyData));

// 2. Performance Comparison Logic
try {
    $stmtThis = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sales_status IN ('sold', 'reserved') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmtThis->execute();
    $currentMonthSales = (int)$stmtThis->fetchColumn();

    $stmtLast = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sales_status IN ('sold', 'reserved') AND created_at >= (CURRENT_DATE() - INTERVAL 1 MONTH) AND created_at < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')");
    $stmtLast->execute();
    $previousMonthSales = (int)$stmtLast->fetchColumn();

    $growth = 0;
    if ($previousMonthSales > 0) {
        $growth = (($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100;
    } elseif ($currentMonthSales > 0) {
        $growth = 100; 
    }

    $growthFormatted = ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '%';
    $growthColorClass = ($growth >= 0) ? "text-success" : "text-danger";
    $growthIcon = ($growth >= 0) ? "bi-graph-up-arrow" : "bi-graph-down-arrow";
} catch (Exception $e) {
    $currentMonthSales = $previousMonthSales = 0;
    $growthFormatted = "0.0%";
}

// 3. Inventory Insights (New Addition)
try {
    $stmtAvailable = $pdo->prepare("SELECT COUNT(*) FROM product WHERE LOWER(status) = 'available'");
    $stmtAvailable->execute();
    $availableUnits = (int)$stmtAvailable->fetchColumn();

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM product");
    $stmtTotal->execute();
    $totalUnits = (int)$stmtTotal->fetchColumn();

    $soldReservedUnits = $totalUnits - $availableUnits;
    $occupancyRate = ($totalUnits > 0) ? ($soldReservedUnits / $totalUnits) * 100 : 0;
} catch (Exception $e) {
    $availableUnits = $totalUnits = $soldReservedUnits = $occupancyRate = 0;
}

// 4. Recent Transactions Feed (New Addition)
try {
    $stmtRecent = $pdo->prepare("SELECT * FROM sales ORDER BY created_at DESC LIMIT 5");
    $stmtRecent->execute();
    $recentTransactions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentTransactions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cattleya | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #059669; /* Emerald 600 */
            --primary-soft: rgba(5, 150, 105, 0.08);
            --primary-glow: rgba(5, 150, 105, 0.3);
            --sidebar-width: 280px;
            --bg-canvas: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-canvas);
            color: #1e293b;
            min-height: 100vh;
        }

        .content-area {
            margin-left: 10px;
            padding: 2.5rem 3.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Modern Typography */
        h1 { font-weight: 800; letter-spacing: -0.03em; color: #0f172a; }
        .greeting-tag {
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.15em;
            background: var(--primary-soft);
            padding: 4px 12px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 24px;
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .icon-box {
            width: 52px; height: 52px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        /* Identity & Buttons */
        .identity-badge {
            background: white;
            padding: 6px 16px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: 0.2s;
        }
        .identity-badge:hover { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-soft); }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 8px 20px -6px var(--primary);
            transition: 0.3s;
        }
        .btn-primary-custom:hover {
            background: #047857;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -6px var(--primary);
        }

        /* Dashboard Canvas Card */
        .nexus-card {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
        }

        /* Signature Styling */
        .sig-thumb {
            width: 60px; height: 30px;
            object-fit: contain;
            filter: grayscale(1);
            opacity: 0.7;
        }
        .btn-modern-action {
            background: var(--primary);
            color: white;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .icon-circle {
            background: rgba(255, 255, 255, 0.2);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .btn-modern-action:hover {
            background: #047857;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.5) !important;
        }

        .btn-modern-action:active {
            transform: translateY(0) scale(0.96);
        }

        /* Modern Form Controls */
        .nexus-control {
            background-color: #f8fafc !important;
            border: 2px solid #f1f5f9 !important;
            border-radius: 16px !important;
            padding: 14px 16px 14px 45px !important;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .nexus-control:focus {
            background-color: #ffffff !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1) !important;
            outline: none;
        }

        .input-group-modern {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
            font-size: 1.1rem;
        }

        .btn-nexus-submit {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-nexus-submit:hover {
            background: #047857;
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.4);
            transform: translateY(-2px);
        }

        .brand-icon-circle {
            width: 50px;
            height: 50px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 1.5rem;
        }

        .signature-dropzone {
            border: 2px dashed #e2e8f0;
            background: #f8fafc;
            border-radius: 24px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .signature-dropzone:hover {
            border-color: var(--primary);
            background: rgba(5, 150, 105, 0.02);
        }

        .btn-modern-primary {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .btn-modern-primary:hover {
            background: #047857;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3);
        }

        .btn-modern-secondary {
            background: #ffffff;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-modern-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        @media (max-width: 991px) {
            .content-area { margin-left: 0; padding: 1.5rem; }
        }

        /* Receipt Styling */
        .receipt-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            position: relative;
        }

        @media print {
            body * { visibility: hidden !important; }
            #receiptPreview, #receiptPreview * { visibility: visible !important; }
            #receiptPreview {
                position: absolute;
                left: 0; top: 0; width: 100%;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print, .btn, .btn-close, .modal-header { display: none !important; }
            @page { size: A4; margin: 20mm; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="content-area">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-5 gap-4">
        <div>
            <span class="greeting-tag"><?= $greeting ?></span>
            <h1>Welcome back, <?= $user_initials ?></h1>
        </div>

        <div class="d-flex gap-3">
            <div class="identity-badge d-flex align-items-center gap-3" data-bs-toggle="modal" data-bs-target="#updateSignatureModal">
                <div class="d-none d-md-block text-end">
                    <p class="m-0 text-muted" style="font-size: 0.65rem; font-weight: 800;">SIGNATURESTATUS</p>
                    <p class="m-0 fw-bold" style="font-size: 0.85rem; color: #10b981;">● Verified</p>
                </div>
                <img src="<?= $signatureSrc ?>" class="sig-thumb" alt="ID">
            </div>
            
            <button class="btn btn-modern-action d-inline-flex align-items-center px-4 py-2 border-0 shadow-sm" 
                    data-bs-toggle="modal" 
                    data-bs-target="#productEstimationModal">
                <div class="icon-circle me-2">
                    <i class="bi bi-plus-lg"></i>
                </div>
                <span class="fw-bold">New Estimation</span>
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="icon-box" style="background: #ecfdf5; color: #10b981;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="ms-4">
                        <p class="text-muted fw-bold small text-uppercase mb-1">Current Month</p>
                        <h2 class="mb-0 fw-800"><?= number_format($currentMonthSales) ?> <span class="fs-6 fw-normal">Units</span></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="icon-box" style="background: #f8fafc; color: #64748b;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="ms-4">
                        <p class="text-muted fw-bold small text-uppercase mb-1">Previous Month</p>
                        <h2 class="mb-0 fw-800"><?= number_format($previousMonthSales) ?> <span class="fs-6 fw-normal">Units</span></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="icon-box" style="background: var(--primary-soft); color: var(--primary);">
                        <i class="bi <?= $growthIcon ?>"></i>
                    </div>
                    <div class="ms-4">
                        <p class="text-muted fw-bold small text-uppercase mb-1">Growth Index</p>
                        <h2 class="mb-0 fw-800 <?= $growthColorClass ?>"><?= $growthFormatted ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-8">
            <div class="nexus-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h4 class="fw-800 m-0">Performance Trend</h4>
                        <p class="text-muted small m-0">Yearly volume analysis for <?= $currentYear ?></p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-4 fw-bold small" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-filter me-2" style="color:var(--primary);"></i> Monthly View
                        </button>
                    </div>
                </div>
                <div style="height: 420px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="nexus-card h-100 d-flex flex-column">
                <h4 class="fw-800 m-0 mb-1">Inventory Insights</h4>
                <p class="text-muted small mb-4">Current unit availability overview</p>
                
                <div class="p-4 rounded-4 bg-light border mb-4">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <p class="text-muted fw-bold small text-uppercase mb-1">Available Units</p>
                            <h2 class="mb-0 fw-800 text-dark"><?= number_format($availableUnits) ?> <span class="fs-6 text-muted fw-normal">/ <?= number_format($totalUnits) ?></span></h2>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success-subtle text-success fw-bold rounded-pill px-3 py-2"><?= number_format($occupancyRate, 1) ?>% Sold</span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar" style="background-color: var(--primary); width: <?= $occupancyRate ?>%" role="progressbar" aria-valuenow="<?= $occupancyRate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <h5 class="fw-800 mt-2 mb-3 fs-6 text-uppercase text-muted">Recent Transactions</h5>
                <div class="d-flex flex-column gap-3 flex-grow-1 overflow-auto" style="max-height: 250px;">
                    <?php if(!empty($recentTransactions)): ?>
                        <?php foreach($recentTransactions as $trx): ?>
                            <div class="d-flex align-items-center p-3 rounded-4 border transition-all" style="background: white;">
                                <div class="icon-box me-3 shadow-sm" style="background: var(--primary-soft); color: var(--primary); width: 42px; height: 42px;">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($trx['product_name'] ?? 'N/A') ?> - <i class="text-muted"><?= htmlspecialchars($trx['block_number'] ?? 'N/A')?> (<?= htmlspecialchars($trx['lot_number'] ?? 'N/A') ?>)</i></p>
                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;"><i class="bi bi-person me-1"></i><?= htmlspecialchars($trx['customer_fullname'] ?? 'N/A') ?></p>

                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= date('M d, Y', strtotime($trx['created_at'])) ?></p>
                                </div>
                                <div>
                                    <span class="badge rounded-pill <?= (strtolower($trx['sales_status'] ?? '')) == 'sold' ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 0.7rem;">
                                        <?= strtoupper(htmlspecialchars($trx['sales_status'] ?? 'Pending')) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                            <p class="small mb-0">No recent transactions found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productEstimationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 32px; overflow: hidden;">
            <div class="position-absolute top-0 start-0 w-100" style="height: 6px; background: linear-gradient(90deg, #059669, #10b981);"></div>
            
            <div class="modal-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="fw-800 mb-1" style="color: #0f172a;">Quick Estimation</h3>
                        <p class="text-muted small">Calculate monthly payments instantly.</p>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="estimationForm">
                    <div class="mb-3">
                        <label class="form-label fw-800 text-dark small mb-2 ms-1">PRODUCT NAME</label>
                        <div class="input-group-modern">
                            <i class="bi bi-box-seam input-icon"></i>
                            <select name="product_name" id="estimate_product_name" class="form-select nexus-control" required>
                                <option value="" selected disabled>Select a product...</option>
                                <?php
                                $stmt = $pdo->query("SELECT DISTINCT product_name FROM product ORDER BY product_name ASC");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='".htmlspecialchars($row['product_name'])."'>".htmlspecialchars($row['product_name'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-800 text-dark small mb-2 ms-1">SELECT BLOCK & LOT</label>
                        <div class="input-group-modern">
                            <i class="bi bi-geo-alt input-icon"></i>
                            <select id="estimate_lot_select" class="form-select nexus-control" disabled required>
                                <option value="">Select Product first...</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-800 text-dark small mb-2 ms-1">TERMS (MONTHS)</label>
                        <div class="input-group-modern">
                            <i class="bi bi-calendar-range input-icon"></i>
                            <input type="number" id="estimate_terms" class="form-control nexus-control" placeholder="e.g. 12, 24, 36" min="1" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-nexus-submit w-100 py-3">Generate Quote</button>
                        <button type="button" id="resetBtn" class="btn btn-light px-4 py-3 text-muted"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </form>

                <div id="receiptWrapper" class="d-none mt-4">
                    <div id="receiptPreview" class="receipt-card p-4 border shadow-sm">
                        <div class="text-center border-bottom pb-3 mb-4">
                            <h2 class="fw-bold mb-0">ESTIMATION QUOTE</h2>
                            <p class="text-muted small mb-0" id="r_date"></p>
                        </div>
                        <div class="receipt-body" style="font-size: 1.1rem; line-height: 2;">
                            <div class="d-flex justify-content-between">
                                <span>Product Name:</span>
                                <span id="r_prod" class="fw-bold text-end"></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Block & Lot:</span>
                                <span id="r_bl" class="fw-bold"></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Niche Type:</span>
                                <span id="r_type"></span>
                            </div>
                            <div class="my-4" style="border-top: 2px dashed #000;"></div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Contract Price:</span>
                                <span id="r_tcp" class="fw-bold"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <span>Payment Term:</span>
                                <span id="r_terms" class="fw-bold"></span>
                            </div>
                            <div class="p-4 rounded-3 text-center bg-light border">
                                <span class="text-uppercase small text-muted d-block mb-1">Monthly Amortization</span>
                                <h1 id="r_monthly" class="fw-800 text-success mb-0"></h1>
                            </div>
                        </div>
                        <div class="mt-4 no-print">
                            <button type="button" class="btn btn-dark w-100 py-2" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i> Print to A4
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateSignatureModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 32px; overflow: hidden;">
            <div class="p-4 text-center border-bottom bg-light-subtle">
                <div class="brand-icon-circle mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4 class="fw-800 mb-1" style="color: #0f172a;">Digital Identity</h4>
                <p class="text-muted small mb-0">Verify your documents with a digital signature</p>
            </div>

            <div class="modal-body p-4 p-md-5">
                <form id="updateSignatureForm">
                    <div id="dropZone" class="signature-dropzone">
                        <div class="dropzone-content">
                            <div class="preview-wrapper mb-3">
                                <img id="modalSigPreview" src="<?= $signatureSrc ?>" alt="Signature Preview" style="max-height: 100px;">
                            </div>
                            <div class="upload-text">
                                <span class="d-block fw-800 text-dark small">Drag & Drop or Click</span>
                                <span class="text-muted extra-small">Supports: PNG, JPG (High Resolution)</span>
                            </div>
                        </div>
                        <input type="file" id="newSignature" name="signature" accept="image/png, image/jpeg" hidden>
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-4 mb-4 p-3 rounded-4 bg-light">
                        <i class="bi bi-info-circle-fill text-success"></i>
                        <p class="small text-muted mb-0">For best results, use a transparent background.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <button type="button" class="btn btn-modern-secondary w-100 py-3" data-bs-dismiss="modal">Dismiss</button>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-modern-primary w-100 py-3">Save Identity</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('estimate_product_name');
    const lotSelect = document.getElementById('estimate_lot_select');
    const form = document.getElementById('estimationForm');
    const resetBtn = document.getElementById('resetBtn');
    let productData = [];

    productSelect.addEventListener('change', async function() {
        lotSelect.disabled = true;
        lotSelect.innerHTML = '<option>Loading...</option>';
        try {
            const response = await fetch(`/cattleya/user/encoder/fetch/get-product-details?name=${encodeURIComponent(this.value)}`);
            productData = await response.json();
            
            lotSelect.innerHTML = '<option value="" selected disabled>Choose available Unit...</option>';
            let count = 0;
            productData.forEach((item, index) => {
                if (item.status.toLowerCase() === 'available') {
                    lotSelect.innerHTML += `<option value="${index}">Block ${item.block_number} Lot ${item.lot_number}</option>`;
                    count++;
                }
            });
            lotSelect.disabled = count === 0;
            if(count === 0) lotSelect.innerHTML = '<option>No available units</option>';
        } catch (e) { console.error(e); }
    });

    resetBtn.addEventListener('click', () => {
        form.reset();
        lotSelect.disabled = true;
        document.getElementById('receiptWrapper').classList.add('d-none');
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = productData[lotSelect.value];
        const terms = parseInt(document.getElementById('estimate_terms').value);
        if(!data || !terms) return;

        const tcp = parseFloat(data.tcp);
        const monthly = tcp / terms;

        document.getElementById('r_date').innerText = new Date().toLocaleString();
        document.getElementById('r_prod').innerText = productSelect.value;
        document.getElementById('r_bl').innerText = `B${data.block_number} L${data.lot_number}`;
        document.getElementById('r_type').innerText = data.niche_type || 'N/A';
        document.getElementById('r_tcp').innerText = `₱${tcp.toLocaleString(undefined, {minimumFractionDigits:2})}`;
        document.getElementById('r_terms').innerText = `${terms} Months`;
        document.getElementById('r_monthly').innerText = `₱${monthly.toLocaleString(undefined, {minimumFractionDigits:2})}`;

        document.getElementById('receiptWrapper').classList.remove('d-none');
        document.getElementById('receiptWrapper').scrollIntoView({ behavior: 'smooth' });
    });
});

// --- CHART CONFIGURATION ---
const ctx = document.getElementById('mainChart').getContext('2d');
const primaryColor = '#059669';

const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(5, 150, 105, 0.25)');
gradient.addColorStop(1, 'rgba(5, 150, 105, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
        datasets: [{
            label: 'Sales Volume',
            data: <?= $chartDataJSON ?>,
            borderColor: primaryColor,
            backgroundColor: gradient,
            fill: true,
            tension: 0.45,
            borderWidth: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: primaryColor,
            pointBorderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 15,
                titleFont: { family: 'Plus Jakarta Sans', size: 13 },
                bodyFont: { family: 'Plus Jakarta Sans', size: 14, weight: '700' },
                displayColors: false
            }
        },
        scales: {
            y: { grid: { color: '#f1f5f9', drawBorder: false }, ticks: { font: { weight: '600' } } },
            x: { grid: { display: false }, ticks: { font: { weight: '600' } } }
        }
    }
});

// --- SIGNATURE HANDLERS ---
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('newSignature');
const modalPreview = document.getElementById('modalSigPreview');

dropZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => modalPreview.src = e.target.result;
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('updateSignatureForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData();
    formData.append('signature', fileInput.files[0]);
    fetch('/cattleya/user/save-signature', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Identity Confirmed', timer: 1500, showConfirmButton: false, customClass: { popup: 'rounded-4' } }).then(() => location.reload());
        }
    });
});
</script>
</body>
</html>