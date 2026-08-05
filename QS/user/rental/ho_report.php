<?php
session_start();
include '../../config/config.php';
require '../../vendor/autoload.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}
/* ==========================================================
   USER SESSION
========================================================== */
$userRole     = $_SESSION['user_role'] ?? '';
$userMainzone = $_SESSION['mainzone'] ?? '';
$userRegion   = $_SESSION['region'] ?? '';
$userArea     = $_SESSION['area'] ?? '';

/* -----------------------------
   Load Branch Profile
-----------------------------*/
$branchProfile = [];
$sqlAll = "SELECT branch_id, branch_name, region, mainzone, area, ml_matic_status
           FROM branch_insurance
           WHERE region IS NOT NULL AND region != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE'
           ORDER BY branch_name ASC";
$resultAll = mysqli_query($conn, $sqlAll);
while ($r = mysqli_fetch_assoc($resultAll)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $mz = $r['mainzone'] ?: 'UNASSIGNED';
    $rg = $r['region'];
    $branchProfile[$mz][$rg][$r['branch_id']] = [
        'name' => $r['branch_name'],
        'area' => $r['area'] ?? ''
    ];
}

/* -----------------------------
   Load ML Rental Contracts
-----------------------------*/
$mlRental = [];
$sqlML = "SELECT c.branch_id, c.contract_number, c.contract_start, c.contract_end,
                 c.start_date, c.end_date, c.rfp_status, c.request_status, c.mode_of_payment,
                 b.branch_name, b.region, b.mainzone, b.area, b.ml_matic_status
          FROM create_contract c
          INNER JOIN branch_insurance b ON b.branch_id = c.branch_id
          WHERE UPPER(TRIM(b.ml_matic_status)) = 'ACTIVE'";
$resultML = mysqli_query($conn, $sqlML);
while ($r = mysqli_fetch_assoc($resultML)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $mz = $r['mainzone'] ?: 'UNASSIGNED';
    $rg = $r['region'];
    $mlRental[$mz][$rg][$r['branch_id']][] = [
        'branch_id'       => $r['branch_id'],
        'name'            => $r['branch_name'],
        'contract_number' => $r['contract_number'],
        'contract_start'  => $r['contract_start'],
        'contract_end'    => $r['contract_end'],
        'start_date'      => $r['start_date'],
        'end_date'        => $r['end_date'],
        'rfp_status'      => $r['rfp_status'],
        'request_status'  => $r['request_status'],
        'payment'         => strtoupper($r['mode_of_payment'] ?? ''),
        'area'            => $r['area'] ?? ''
    ];
}

/* -----------------------------
   Match + Align Data
-----------------------------*/
$alignedData = [];
$mainzones = array_unique(array_merge(array_keys($branchProfile), array_keys($mlRental)));
sort($mainzones);

foreach ($mainzones as $mz) {
    $regions = array_unique(array_merge(
        array_keys($branchProfile[$mz] ?? []),
        array_keys($mlRental[$mz] ?? [])
    ));

    foreach ($regions as $region) {
        $leftBranches  = $branchProfile[$mz][$region] ?? [];
        $rightBranches = $mlRental[$mz][$region] ?? [];

        $matchedIds = array_intersect(array_keys($leftBranches), array_keys($rightBranches));

        // Matched branches
        foreach ($matchedIds as $id) {
            // Filter out VOID contracts
            $validContracts = array_filter($rightBranches[$id], function($c) {
                return strtoupper(trim($c['contract_number'] ?? '')) !== 'VOID';
            });

            if (!empty($validContracts)) {
                foreach ($validContracts as $contract) {
                    $alignedData[$mz][$region][] = [
                        'branch_id'       => $contract['branch_id'],
                        'left'            => $leftBranches[$id]['name'],
                        'right'           => $contract['name'],
                        'contract_number' => $contract['contract_number'],
                        'contract_start'  => $contract['contract_start'],
                        'contract_end'    => $contract['contract_end'],
                        'start_date'      => $contract['start_date'],
                        'end_date'        => $contract['end_date'],
                        'rfp_status'      => $contract['rfp_status'],
                        'request_status'  => $contract['request_status'],
                        'payment'         => $contract['payment'],
                        'match'           => true,
                        'area'            => $contract['area']
                    ];
                }
            } else {
                // If all contracts are VOID, still display the branch but keep contract fields blank
                $alignedData[$mz][$region][] = [
                    'branch_id'       => $id,
                    'left'            => $leftBranches[$id]['name'],
                    'right'           => '',
                    'contract_number' => '',
                    'contract_start'  => '',
                    'contract_end'    => '',
                    'start_date'      => '',
                    'end_date'        => '',
                    'rfp_status'      => '',
                    'request_status'  => '',
                    'payment'         => '',
                    'match'           => false,
                    'area'            => $leftBranches[$id]['area']
                ];
            }
            unset($leftBranches[$id], $rightBranches[$id]);
        }

        // Unmatched left branches
        foreach ($leftBranches as $id => $branch) {
            $alignedData[$mz][$region][] = [
                'branch_id'       => $id,
                'left'            => $branch['name'],
                'right'           => '',
                'contract_number' => '',
                'contract_start'  => '',
                'contract_end'    => '',
                'start_date'      => '',
                'end_date'        => '',
                'rfp_status'      => '',
                'request_status'  => '',
                'payment'         => '',
                'match'           => false,
                'area'            => $branch['area']
            ];
        }

        // Unmatched right branches
        foreach ($rightBranches as $contracts) {
            $validContracts = array_filter($contracts, function($c) {
                return strtoupper(trim($c['contract_number'] ?? '')) !== 'VOID';
            });
            foreach ($validContracts as $contract) {
                $alignedData[$mz][$region][] = [
                    'branch_id'       => $contract['branch_id'],
                    'left'            => '',
                    'right'           => $contract['name'],
                    'contract_number' => $contract['contract_number'],
                    'contract_start'  => $contract['contract_start'],
                    'contract_end'    => $contract['contract_end'],
                    'start_date'      => $contract['start_date'],
                    'end_date'        => $contract['end_date'],
                    'rfp_status'      => $contract['rfp_status'],
                    'request_status'  => $contract['request_status'],
                    'payment'         => $contract['payment'],
                    'match'           => false,
                    'area'            => $contract['area']
                ];
            }
        }
    }
}

/* -----------------------------
   Dropdown Data
-----------------------------*/
$allRegions = [];
$res = mysqli_query($conn, "SELECT DISTINCT region FROM branch_insurance WHERE region IS NOT NULL AND region != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC");
while ($r = mysqli_fetch_assoc($res)) $allRegions[] = $r['region'];

$allMainzones = [];
$res = mysqli_query($conn, "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone IS NOT NULL AND mainzone != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY mainzone ASC");
while ($r = mysqli_fetch_assoc($res)) $allMainzones[] = $r['mainzone'];

// Fetch distinct areas grouped by region
$areasByRegion = [];
$res = mysqli_query($conn, "SELECT DISTINCT region, area FROM branch_insurance WHERE area IS NOT NULL AND area != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC, area ASC");
while ($r = mysqli_fetch_assoc($res)) {
    $region = $r['region'];
    $area   = $r['area'];
    if (!isset($areasByRegion[$region])) $areasByRegion[$region] = [];
    if (!in_array($area, $areasByRegion[$region])) $areasByRegion[$region][] = $area;
}
$areasJson = json_encode($areasByRegion);

/* -----------------------------
   Filter
-----------------------------*/
$selectedNationwide = false;
$selectedRegion    = '';
$selectedMainzone  = '';
$selectedArea      = '';

if (isset($_POST['filter'])) {
    $filter = $_POST['filter_region'] ?? '';
    if ($filter === 'Nationwide') $selectedNationwide = true;
    elseif ($filter === 'ByRegion') {
        $selectedRegion = $_POST['region'] ?? '';
        $selectedArea   = $_POST['area'] ?? '';
    } elseif ($filter === 'ByMainzone') $selectedMainzone = $_POST['mainzone'] ?? '';
}

/* -----------------------------
   Prepare Display Data
-----------------------------*/
$displayData = [];
if ($selectedNationwide) {
    $displayData = $alignedData;
} elseif ($selectedMainzone) {
    $displayData = [$selectedMainzone => $alignedData[$selectedMainzone] ?? []];
} elseif ($selectedRegion) {
    foreach ($alignedData as $mz => $regions) {
        if (isset($regions[$selectedRegion])) {
            $rows = $regions[$selectedRegion];
            if ($selectedArea) $rows = array_filter($rows, fn($row) => ($row['area'] ?? '') === $selectedArea);
            if (!empty($rows)) $displayData[$mz][$selectedRegion] = $rows;
        }
    }
}
/* ==========================================================
   FILTER VISIBILITY CONTROL
========================================================== */
$hideNationwideRoles = [
    'Am-Creator',
    'Rm-Reviewer',
    'Vpo-Checker',
    'Vpo-Reviewer',
    'Vpo-Approver'
];

$hideMainzoneRoles = [
    'Am-Creator',
    'Rm-Reviewer'
];

$canSeeNationwide = !in_array($userRole, $hideNationwideRoles);
$canSeeMainzone   = !in_array($userRole, $hideMainzoneRoles);


/* ==========================================================
   REGION / AREA / MAINZONE FILTERING
========================================================== */

$filteredRegions   = $allRegions;
$filteredMainzones = $allMainzones;


/* ----------------------------------------------------------
   AM-Creator
   → Only own region
   → Only own area
---------------------------------------------------------- */
if ($userRole === 'Am-Creator') {

    // Only their region
    $filteredRegions = [$userRegion];

    // No mainzone access
    $filteredMainzones = [];

    // Force selection automatically
    $selectedRegion = $userRegion;
    $selectedArea   = $userArea;
}


/* ----------------------------------------------------------
   RM-Reviewer
   → Only own region
---------------------------------------------------------- */
elseif ($userRole === 'Rm-Reviewer') {

    $filteredRegions = [$userRegion];
    $filteredMainzones = [];
}


/* ----------------------------------------------------------
   VPO Roles
   → Regions under their mainzone only
   → Only their mainzone selectable
---------------------------------------------------------- */
elseif (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver'])) {

    $filteredRegions = [];

    $sql = "SELECT DISTINCT region 
            FROM branch_insurance 
            WHERE mainzone = '".mysqli_real_escape_string($conn, $userMainzone)."'
            AND region IS NOT NULL AND region != ''
            AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE'
            ORDER BY region ASC";

    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $filteredRegions[] = $row['region'];
    }

    $filteredMainzones = [$userMainzone];
}

/* ----------------------------------------------------------
   Audit / HO / Finance
   → Full access
---------------------------------------------------------- */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ML Rental - HO Manager Report</title>
<link rel="shortcut icon" href="../../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
  <!-- ✅ Local Google Font -->
  <link href="../../assets/css/poppins.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap CSS -->
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap Icons -->
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- ✅ Your custom CSS should come AFTER font import -->
   <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">
  <style>
/* Matched / Unmatched Rows */
.matched { 
    background-color: #e6ffed !important; /* soft green */ 
    transition: 0.2s;
}
.unmatched { 
    background-color: #ffe6e6 !important; /* soft red */ 
    transition: 0.2s;
}

/* Region header styling */
.region-header {
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    padding: 10px 15px;
    letter-spacing: 0.5px;
    font-size: 0.95rem;
}

/* Alternate region header colors */
.region-header-1 { background-color: #4a5568; } /* dark grey */
.region-header-2 { background-color: #2d3748; } /* darker grey */

/* Make table scrollable container */
.table-responsive {
    max-height: 72vh;
    overflow-y: auto;
    position: relative;
}

/* Sticky table header */
.table thead th {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #f8f9fa; /* important so it doesn't become transparent */
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
}

.table tbody td {
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
    font-size: 0.95rem;
}

/* Hover effect */
.table-hover tbody tr:hover {
    background-color: #f1f3f5 !important;
}

/* Contract info styling */
.contract-badge {
    display: inline-block;
    font-size: 0.85rem;
    padding: 0.35em 0.6em;
    border-radius: 4px;
    background-color: #0d6efd;
    color: #fff;
    margin-bottom: 4px;
}

/* Contract dates icons */
.contract-date i {
    vertical-align: middle;
    margin-right: 4px;
    color: #6c757d;
}

/* Icons alignment */
i.bi { vertical-align: middle; margin-right: 4px; }

/* Responsive table */
.table-responsive { overflow-x: auto; }
.dashboard-card {
    backdrop-filter: blur(6px);
    background: rgba(255,255,255,0.75);
    transition: all 0.25s ease;
    border-radius: 12px;
}

.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.counter {
    font-size: 1.1rem;
    font-weight: 700;
}
.summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}

.summary-icon {
    font-size: 18px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #f1f5f9;
    color: #b02a37;
}

.summary-title {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}
</style>

</head>
<body>
<?php include('navbar.php'); ?>
<div id="mainContent" class="bg-body-tertiary min-vh-100 p-3">
    <!-- Sidebar toggle -->
    <button id="toggleSidebar" class="btn btn-light border text-dark mb-1">
        <i class="bi bi-list me-2 text-danger"></i> Menu
    </button>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex align-items-center gap-3">
            <i class="bi bi-funnel-fill fs-4 text-danger"></i>
            <form method="POST" class="d-flex align-items-center gap-2 w-100">

<!-- MAIN FILTER -->
<select name="filter_region" class="form-select w-25" id="mainFilter">
    <option value="">-- Select Filter --</option>

    <?php if ($canSeeNationwide): ?>
        <option value="Nationwide" <?= $selectedNationwide ? 'selected' : '' ?>>
            🌍 Nationwide
        </option>
    <?php endif; ?>

    <option value="ByRegion" <?= ($selectedRegion && !$selectedNationwide) ? 'selected' : '' ?>>
        By Region
    </option>

    <?php if ($canSeeMainzone): ?>
        <option value="ByMainzone" <?= ($selectedMainzone && !$selectedNationwide) ? 'selected' : '' ?>>
            By Mainzone
        </option>
    <?php endif; ?>
</select>

<!-- REGION -->
<select name="region"
        class="form-select w-25 <?= ($selectedRegion) ? '' : 'd-none' ?>"
        id="regionDropdown">

    <option value="">-- Select Region --</option>

    <?php foreach ($filteredRegions as $region): ?>
        <option value="<?= htmlspecialchars($region) ?>"
            <?= ($region === $selectedRegion) ? 'selected' : '' ?>>
            <?= htmlspecialchars($region) ?>
        </option>
    <?php endforeach; ?>

</select>

<!-- AREA -->
<select name="area"
        class="form-select w-25 <?= ($selectedArea || $userRole === 'Am-Creator') ? '' : 'd-none' ?>"
        id="areaDropdown"
        <?= ($userRole === 'Am-Creator') ? 'readonly disabled' : '' ?>>

    <option value="">-- Select Area (Optional) --</option>

    <?php if ($userRole === 'Am-Creator'): ?>
        <option value="<?= htmlspecialchars($userArea) ?>" selected>
            <?= htmlspecialchars($userArea) ?>
        </option>
    <?php endif; ?>

</select>

<!-- MAINZONE -->
<select name="mainzone"
        class="form-select w-25 <?= ($selectedMainzone) ? '' : 'd-none' ?>"
        id="mainzoneDropdown">

    <option value="">-- Select Mainzone --</option>

    <?php foreach ($filteredMainzones as $mainzone): ?>
        <option value="<?= htmlspecialchars($mainzone) ?>"
            <?= ($mainzone === $selectedMainzone) ? 'selected' : '' ?>>
            <?= htmlspecialchars($mainzone) ?>
        </option>
    <?php endforeach; ?>

</select>

<!-- BUTTONS -->
<button type="submit" name="filter" class="btn btn-danger">
    <i class="bi bi-search me-1"></i> Filter
</button>

<a href="export_summary_report.php?filter_region=<?= urlencode($_POST['filter_region'] ?? '') ?>
&region=<?= urlencode($selectedRegion) ?>
&mainzone=<?= urlencode($selectedMainzone) ?>
&area=<?= urlencode($selectedArea) ?>"
   class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i> Export Excel
</a>

</form>
            </div>
        </div>

<?php if ($selectedNationwide || $selectedRegion || $selectedMainzone): ?>

<?php
/* ================================
   ROLE-BASED DISPLAY ENFORCEMENT
   Filter displayData before rendering
================================= */
$userRole     = $_SESSION['user_role'] ?? '';
$userMainzone = $_SESSION['mainzone'] ?? '';
$userRegion   = $_SESSION['region'] ?? '';
$userArea     = $_SESSION['area'] ?? '';

$securedDisplayData = [];

if (!empty($displayData)) {
    foreach ($displayData as $mainzone => $regions) {

        // VPO → only own mainzone
        if (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver']) && $mainzone !== $userMainzone) continue;

        foreach ($regions as $region => $rows) {

            // AM-Creator & RM-Reviewer → only own region
            if (($userRole === 'Am-Creator' || $userRole === 'Rm-Reviewer') && $region !== $userRegion) continue;

            // Area restriction for AM-Creator
            if ($userRole === 'Am-Creator') {
                $rows = array_filter($rows, fn($row) => ($row['area'] ?? '') === $userArea);
            }

            if (!empty($rows)) {
                $securedDisplayData[$mainzone][$region] = $rows;
            }
        }
    }

    $displayData = $securedDisplayData;
}

// =========================================================
// PRE-CALCULATE SUMMARY METRICS (BEFORE RENDERING SUMMARY CARDS)
// =========================================================
$countBranches = 0;
$seenBranches = [];
$seenUnmatchedBranches = [];
$countUnmatchedBranches = 0;
$countDataArchiving = 0;
$countUndefined = 0;

$paymentMapping = [
    'CASH' => 'CASH (Branch Cash-out)',
    'PAYMENT SOLUTION' => 'RFP (PAYMENT SOLUTION)',
    'PDC' => 'RFP (PDC)',
    'WALLET' => 'RFP (MCash)',
    'RTA' => 'RFP (Remit To Account)'
];

$paymentMethods = [ 'CASH (Branch Cash-out)', 'RFP (PAYMENT SOLUTION)', 'RFP (PDC)', 'RFP (MCash)', 'RFP (Remit To Account)'];
$countPayments = array_fill_keys($paymentMethods, 0);

$seenContracts = [];
$seenBranchMetrics = [];

foreach ($displayData as $mainzone => $regions) {
    foreach ($regions as $region => $rows) {
        $groupedByBranch = [];
        foreach ($rows as $r) {
            $bid = trim($r['branch_id'] ?? '');
            if (!isset($groupedByBranch[$bid])) {
                $groupedByBranch[$bid] = [];
            }
            $groupedByBranch[$bid][] = $r;
        }

        foreach ($groupedByBranch as $branchId => $branchRows) {
            if (!empty($branchId) && !isset($seenBranches[$branchId])) {
                $seenBranches[$branchId] = true;
                $countBranches++;
            }

            $hasMatch = false;
            foreach ($branchRows as $r) {
                $cNum = strtoupper(trim($r['contract_number'] ?? ''));
                if (!empty($r['match']) && $cNum !== '' && $cNum !== 'VOID') {
                    $hasMatch = true;
                    break;
                }
            }
            if (!$hasMatch && !empty($branchId) && !isset($seenUnmatchedBranches[$branchId])) {
                $seenUnmatchedBranches[$branchId] = true;
                $countUnmatchedBranches++;
            }

            foreach ($branchRows as $row) {
                $contractKey = trim($row['contract_number'] ?? '');
                if (strtoupper($contractKey) === 'VOID') continue;

                $rfpStatus = $row['rfp_status'] ?? '';
                $requestStatus = $row['request_status'] ?? '';
                $modeOfPayment = strtoupper(trim($row['payment'] ?? ''));
                $mappedMode = $paymentMapping[$modeOfPayment] ?? $modeOfPayment;

                $isDataArchiving =
                    (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                    ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Ready', 'Approved', 'Reviewed']));

                $isUndefined = (
                    (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                    ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready')
                );

                if (!empty($contractKey) && !isset($seenContracts[$contractKey])) {
                    $seenContracts[$contractKey] = true;

                    if ($isDataArchiving && !isset($seenBranchMetrics[$branchId])) {
                        $seenBranchMetrics[$branchId] = true;
                        $countDataArchiving++;
                    }

                    if ($isUndefined) $countUndefined++;
                    if (isset($countPayments[$mappedMode])) $countPayments[$mappedMode]++;
                }
            }
        }
    }
}
?>

<!-- =============================
     SUMMARY CARDS (ABOVE TABLE)
============================= -->
<div class="row g-2 mb-3">
    <!-- Total Branches -->
    <div class="col-md-3 col-6">
        <div class="summary-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="summary-title">Total Branches</div>
                    <div class="summary-value"><?= $countBranches ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
            </div>
        </div>
    </div>
     <!-- Branch with registered active contracts -->
    <div class="col-md-3 col-6">
        <div class="summary-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="summary-title">Branch with registered active contract</div>
                    <div class="summary-value"><?= $countDataArchiving ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-archive"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Branch with no contracts -->
    <div class="col-md-3 col-6">
        <div class="summary-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="summary-title">Branch without registered contracts</div>
                    <div class="summary-value"><?= $countUnmatchedBranches ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Data Archiving -->
    <div class="col-md-3 col-6">
        <div class="summary-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="summary-title">Rental Archiving</div>
                    <div class="summary-value"><?= $countDataArchiving ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-archive"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Payment cards -->
    <?php foreach ($paymentMethods as $method): ?>
    <div class="col-md-3 col-6">
        <div class="summary-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="summary-title"><?= htmlspecialchars($method) ?></div>
                    <div class="summary-value"><?= $countPayments[$method] ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-credit-card"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- =============================
     TABLE SECTION
============================= -->
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">

                <thead class="table-light sticky-top">
                    <tr class="fw-bold">
                        <th rowspan="2">Branch ID</th>
                        <th rowspan="2">Branch Profile</th>
                        <th rowspan="2">Contract Number</th>
                        <th rowspan="2">Contract Period</th>
                        <th rowspan="2">Data Archiving</th>
                        <th rowspan="2">RFP Period</th>
                        <th colspan="5">RFP</th>
                    </tr>
                    <tr>
                        <?php foreach ($paymentMethods as $method): ?>
                            <th><?= htmlspecialchars($method) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($displayData as $mainzone => $regions): ?>

                    <?php ksort($regions, SORT_NATURAL | SORT_FLAG_CASE); ?>

                    <tr class="table-secondary fw-bold">
                        <td colspan="11"><?= htmlspecialchars($mainzone) ?></td>
                    </tr>

                    <?php foreach ($regions as $region => $rows):

                        usort($rows, fn($a, $b) =>
                            ($a['match'] === $b['match']) ? 0 : ($a['match'] ? -1 : 1)
                        );

                        // Group rows by branch_id
                        $groupedByBranch = [];
                        foreach ($rows as $r) {
                            $bid = trim($r['branch_id'] ?? '');
                            if (!isset($groupedByBranch[$bid])) {
                                $groupedByBranch[$bid] = [];
                            }
                            $groupedByBranch[$bid][] = $r;
                        }
                    ?>

                    <tr class="table-danger text-white fw-bold">
                        <td colspan="11"><?= htmlspecialchars($region) ?></td>
                    </tr>

                    <?php foreach ($groupedByBranch as $branchId => $branchRows):

                        $branchName = $branchRows[0]['left'] ?? $branchRows[0]['right'] ?? '';
                    ?>

                        <?php foreach ($branchRows as $i => $row):

                            $contractKey = trim($row['contract_number'] ?? '');
                            $isVoid = (strtoupper($contractKey) === 'VOID');
                            $rfpStatus = $row['rfp_status'] ?? '';
                            $requestStatus = $row['request_status'] ?? '';
                            
                            $modeOfPayment = strtoupper(trim($row['payment'] ?? ''));
                            $mappedMode = $paymentMapping[$modeOfPayment] ?? $modeOfPayment;

                            // Data Archiving
                            $isDataArchiving = !$isVoid && (
                                (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                                ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Ready', 'Approved', 'Reviewed']))
                            );
                        ?>

                        <tr class="<?= (!empty($row['match']) && !$isVoid) ? 'table-success' : '' ?>">
                            <td><?= ($i === 0) ? htmlspecialchars($branchId) : '' ?></td>
                            <td class="text-start"><?= ($i === 0) ? htmlspecialchars($branchName) : '' ?></td>

                            <td>
                                <?php if (!empty($contractKey) && !$isVoid): ?>
                                    <span class="badge bg-danger"><?= htmlspecialchars($contractKey) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="text-start small">
                                <?php if (!$isVoid && (!empty($row['contract_start']) || !empty($row['contract_end']))): ?>
                                    <div><?= !empty($row['contract_start']) ? date('M d, Y', strtotime($row['contract_start'])) : '' ?></div>
                                    <div><?= !empty($row['contract_end']) ? date('M d, Y', strtotime($row['contract_end'])) : '' ?></div>
                                <?php endif; ?>
                            </td>

                            <td><?= ($isDataArchiving && !$isVoid) ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : '<span class="text-muted">—</span>' ?></td>

                            <td class="text-start small">
                                <?php if (!$isVoid && (!empty($row['start_date']) || !empty($row['end_date']))): ?>
                                    <div><?= !empty($row['start_date']) ? date('M Y', strtotime($row['start_date'])) : '' ?></div>
                                    <div><?= !empty($row['end_date']) ? date('M Y', strtotime($row['end_date'])) : '' ?></div>
                                <?php endif; ?>
                            </td>

                            <?php foreach ($paymentMethods as $method): ?>
                                <td><?= (!$isVoid && $mappedMode === $method) ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : '<span class="text-muted">—</span>' ?></td>
                            <?php endforeach; ?>
                        </tr>

                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-2">Logging Out</h5>
                <p class="text-muted mb-3">Please wait while we securely log you out...</p>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
const mainFilter = document.getElementById('mainFilter');
const regionDropdown = document.getElementById('regionDropdown');
const mainzoneDropdown = document.getElementById('mainzoneDropdown');
const areaDropdown = document.getElementById('areaDropdown');
const areasByRegion = <?= $areasJson ?>;

function toggleDropdowns() {
    regionDropdown.classList.add('d-none');
    mainzoneDropdown.classList.add('d-none');
    areaDropdown.classList.add('d-none');

    if (mainFilter.value === 'ByRegion') {
        regionDropdown.classList.remove('d-none');
        areaDropdown.classList.remove('d-none');
    }
    if (mainFilter.value === 'ByMainzone') mainzoneDropdown.classList.remove('d-none');
}

// Populate Areas on Region change
function populateAreas(region) {
    areaDropdown.innerHTML = '<option value="">-- Select Area (Optional) --</option>';
    if (areasByRegion[region]) {
        areasByRegion[region].forEach(area => {
            const opt = document.createElement('option');
            opt.value = area;
            opt.textContent = area;
            if (area === "<?= $selectedArea ?>") opt.selected = true;
            areaDropdown.appendChild(opt);
        });
    }
}

toggleDropdowns();
mainFilter.addEventListener('change', toggleDropdowns);
regionDropdown.addEventListener('change', e => populateAreas(e.target.value));

// Initial populate if region selected
<?php if ($selectedRegion): ?>
populateAreas("<?= $selectedRegion ?>");
<?php endif; ?>

document.getElementById('toggleSidebar')?.addEventListener('click', () => {
    document.getElementById('sidebarMenu')?.classList.toggle('collapsed');
});
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
  e.preventDefault();
  const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
    backdrop: 'static',
    keyboard: false
  });
  logoutModal.show();
  setTimeout(() => window.location.href = '../../logout.php', 2500);
});
</script>
</body>
</html>