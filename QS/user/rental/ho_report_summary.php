<?php
session_start();
include '../../config/config.php';
require '../../vendor/autoload.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}
/* ==========================================================
   USER SESSION & CANONICAL MAINZONE MAPPING
========================================================== */
$userRole     = $_SESSION['user_role'] ?? '';
$userMainzone = $_SESSION['mainzone'] ?? '';
$userRegion   = $_SESSION['region'] ?? '';
$userArea     = $_SESSION['area'] ?? '';

/**
 * Categorize regions into distinct LUZON, NCR, VISAYAS, or MINDANAO sub-groups 
 * when mainzone is defined as LNCR or VISMIN.
 */
function getCanonicalMainzone($mz, $region) {
    $mzUpper  = strtoupper(trim($mz ?? ''));
    $regUpper = strtoupper(trim($region ?? ''));

    if ($mzUpper === 'LNCR') {
        return (strpos($regUpper, 'NCR') !== false) ? 'NCR' : 'LUZON';
    }
    if ($mzUpper === 'VISMIN') {
        if (
            strpos($regUpper, 'MIN') !== false || 
            strpos($regUpper, 'MINDANAO') !== false || 
            strpos($regUpper, 'DAVAO') !== false || 
            strpos($regUpper, 'ZAMBOANGA') !== false || 
            strpos($regUpper, 'CARAGA') !== false
        ) {
            return 'MINDANAO';
        }
        return 'VISAYAS';
    }
    return $mzUpper ?: 'UNASSIGNED';
}

/**
 * Classifies one branch's already-grouped rows against criteria 2, 4-9.
 * Used by the per-region summary table.
 *
 *   - 'registered' (bool)   Criterion 2: has >=1 non-void contract with
 *                           contract_start AND contract_end both present.
 *   - 'archived'   (bool)   Criterion 4: same branch-level rule as
 *                           'registered' above. Per the client's note,
 *                           Archived is counted by branch_id, not by
 *                           contract_number, so this is a per-branch flag
 *                           rather than a per-contract tally.
 *   - 'payments'   (array)  Criteria 5-9: count of distinct contract_number
 *                           values per mode_of_payment, matched case-
 *                           insensitively and independent of any date field.
 *
 * A row with no contract_number at all (blank) represents "no contract" and
 * is skipped for the payment tally — there is nothing to count. Note there
 * is no need to separately check for a contract_number of "VOID": the
 * alignment step above already strips VOID contracts out (or blanks the
 * row when every contract on file is VOID), so a real, non-blank
 * contract_number reaching this function is always a valid one.
 */
function classifyBranchRows(array $branchRows, callable $isValidDate): array {
    $hasValidPeriod = false;
    foreach ($branchRows as $r) {
        if ($isValidDate($r['contract_start'] ?? '') && $isValidDate($r['contract_end'] ?? '')) {
            $hasValidPeriod = true;
            break;
        }
    }

    $payments = [
        'CASH (Branch Cash-out)' => 0,
        'RFP (PAYMENT SOLUTION)' => 0,
        'RFP (PDC)'              => 0,
        'RFP (Remit To Account)' => 0,
        'RFP (MCash)'            => 0,
    ];

    $seenContractNumbers = [];
    foreach ($branchRows as $r) {
        $cNum = strtoupper(trim($r['contract_number'] ?? ''));
        if ($cNum === '') continue;
        if (isset($seenContractNumbers[$cNum])) continue;
        $seenContractNumbers[$cNum] = true;

        // Criteria 5-9: mode_of_payment, compared case-insensitively, no date filter
        $mode = strtoupper(trim($r['payment'] ?? ''));
        if ($mode === 'CASH') {
            $payments['CASH (Branch Cash-out)']++;
        } elseif ($mode === 'PAYMENT SOLUTION') {
            $payments['RFP (PAYMENT SOLUTION)']++;
        } elseif ($mode === 'PDC') {
            $payments['RFP (PDC)']++;
        } elseif ($mode === 'RTA') {
            $payments['RFP (Remit To Account)']++;
        } elseif ($mode === 'WALLET' || $mode === 'MCASH') {
            $payments['RFP (MCash)']++;
        }
    }

    return [
        'registered' => $hasValidPeriod,
        'archived'   => $hasValidPeriod, // Criterion 4: branch-level, same rule as 'registered'
        'payments'   => $payments,
    ];
}

/* -----------------------------
   Load Branch Profile
-----------------------------*/
$branchProfile = [];
$sqlAll = "SELECT branch_id, branch_name, region, mainzone, area, ml_matic_status
           FROM branch_insurance
           WHERE region IS NOT NULL AND region != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE'
           ORDER BY mainzone ASC, region ASC, branch_name ASC";
$resultAll = mysqli_query($conn, $sqlAll);
while ($r = mysqli_fetch_assoc($resultAll)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $mz = getCanonicalMainzone($r['mainzone'], $r['region']);
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
// Tracks every ACTIVE branch_id that has at least one row in create_contract,
// regardless of whether that contract is VOID. Used for an exact "Unregistered" count.
$branchHasContractRecord = [];
$sqlML = "SELECT c.branch_id, c.contract_number, c.contract_start, c.contract_end,
                 c.start_date, c.end_date, c.rfp_status, c.request_status, c.mode_of_payment,
                 b.branch_name, b.region, b.mainzone, b.area, b.ml_matic_status
          FROM create_contract c
          INNER JOIN branch_insurance b ON b.branch_id = c.branch_id
          WHERE UPPER(TRIM(b.ml_matic_status)) = 'ACTIVE'";
$resultML = mysqli_query($conn, $sqlML);
while ($r = mysqli_fetch_assoc($resultML)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $branchHasContractRecord[$r['branch_id']] = true;
    $mz = getCanonicalMainzone($r['mainzone'], $r['region']);
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
        'payment'         => strtoupper(trim($r['mode_of_payment'] ?? '')),
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
    sort($regions);

    foreach ($regions as $region) {
        $leftBranches  = $branchProfile[$mz][$region] ?? [];
        $rightBranches = $mlRental[$mz][$region] ?? [];

        $matchedIds = array_intersect(array_keys($leftBranches), array_keys($rightBranches));

        // Matched branches
        foreach ($matchedIds as $id) {
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
        foreach ($rightBranches as $id => $contracts) {
            $validContracts = array_filter($contracts, function($c) {
                return strtoupper(trim($c['contract_number'] ?? '')) !== 'VOID';
            });
            if (!empty($validContracts)) {
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
            } else {
                // Every contract on record for this branch is VOID. Still surface the
                // branch (blank contract fields) instead of silently dropping it from the report.
                $first = reset($contracts);
                $alignedData[$mz][$region][] = [
                    'branch_id'       => $id,
                    'left'            => '',
                    'right'           => $first['name'] ?? '',
                    'contract_number' => '',
                    'contract_start'  => '',
                    'contract_end'    => '',
                    'start_date'      => '',
                    'end_date'        => '',
                    'rfp_status'      => '',
                    'request_status'  => '',
                    'payment'         => '',
                    'match'           => false,
                    'area'            => $first['area'] ?? ''
                ];
            }
        }
    }
}

/* -----------------------------
   Dropdown Data Setup
-----------------------------*/
$allRegions = [];
$res = mysqli_query($conn, "SELECT DISTINCT region FROM branch_insurance WHERE region IS NOT NULL AND region != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC");
while ($r = mysqli_fetch_assoc($res)) $allRegions[] = $r['region'];

// Pre-populate mainzone dropdown with composite and standalone options
$allMainzones = ['LNCR', 'VISMIN', 'LUZON', 'NCR', 'VISAYAS', 'MINDANAO'];
$res = mysqli_query($conn, "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone IS NOT NULL AND mainzone != '' AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY mainzone ASC");
while ($r = mysqli_fetch_assoc($res)) {
    $mzVal = strtoupper(trim($r['mainzone']));
    if (!in_array($mzVal, $allMainzones)) {
        $allMainzones[] = $mzVal;
    }
}

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
   Filter Processing
-----------------------------*/
$selectedNationwide = false;
$selectedRegion     = '';
$selectedMainzone   = '';
$selectedArea       = '';

if (isset($_POST['filter'])) {
    $filter = $_POST['filter_region'] ?? '';
    if ($filter === 'Nationwide') {
        $selectedNationwide = true;
    } elseif ($filter === 'ByRegion') {
        $selectedRegion = $_POST['region'] ?? '';
        $selectedArea   = $_POST['area'] ?? '';
    } elseif ($filter === 'ByMainzone') {
        $selectedMainzone = $_POST['mainzone'] ?? '';
    }
}

/* -----------------------------
   Prepare Display Data with LNCR / VISMIN Grouping
-----------------------------*/
$displayData = [];
if ($selectedNationwide) {
    $displayData = $alignedData;
} elseif ($selectedMainzone) {
    $smz = strtoupper(trim($selectedMainzone));
    if ($smz === 'LNCR') {
        foreach (['LUZON', 'NCR', 'LNCR'] as $mzKey) {
            if (isset($alignedData[$mzKey])) $displayData[$mzKey] = $alignedData[$mzKey];
        }
    } elseif ($smz === 'VISMIN') {
        foreach (['VISAYAS', 'MINDANAO', 'VISMIN'] as $mzKey) {
            if (isset($alignedData[$mzKey])) $displayData[$mzKey] = $alignedData[$mzKey];
        }
    } else {
        if (isset($alignedData[$selectedMainzone])) {
            $displayData[$selectedMainzone] = $alignedData[$selectedMainzone];
        }
    }
} elseif ($selectedRegion) {
    foreach ($alignedData as $mz => $regions) {
        if (isset($regions[$selectedRegion])) {
            $rows = $regions[$selectedRegion];
            if ($selectedArea) $rows = array_filter($rows, fn($row) => ($row['area'] ?? '') === $selectedArea);
            if (!empty($rows)) $displayData[$mz][$selectedRegion] = $rows;
        }
    }
} else {
    // Default to Nationwide baseline view
    $displayData = $alignedData;
}

/* ==========================================================
   FILTER VISIBILITY CONTROL & PERMISSIONS
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
   REGION / AREA / MAINZONE ROLE FILTERING
========================================================== */
$filteredRegions   = $allRegions;
$filteredMainzones = $allMainzones;

if ($userRole === 'Am-Creator') {
    $filteredRegions   = [$userRegion];
    $filteredMainzones = [];
    $selectedRegion    = $userRegion;
    $selectedArea      = $userArea;
} elseif ($userRole === 'Rm-Reviewer') {
    $filteredRegions   = [$userRegion];
    $filteredMainzones = [];
} elseif (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver'])) {
    $filteredRegions = [];
    $uMz = strtoupper(trim($userMainzone));
    
    if ($uMz === 'LNCR') {
        $sql = "SELECT DISTINCT region FROM branch_insurance 
                WHERE UPPER(TRIM(mainzone)) IN ('LNCR','LUZON','NCR') 
                AND region IS NOT NULL AND region != '' 
                AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC";
    } elseif ($uMz === 'VISMIN') {
        $sql = "SELECT DISTINCT region FROM branch_insurance 
                WHERE UPPER(TRIM(mainzone)) IN ('VISMIN','VISAYAS','MINDANAO') 
                AND region IS NOT NULL AND region != '' 
                AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC";
    } else {
        $sql = "SELECT DISTINCT region FROM branch_insurance 
                WHERE UPPER(TRIM(mainzone)) = '".mysqli_real_escape_string($conn, $uMz)."' 
                AND region IS NOT NULL AND region != '' 
                AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE' ORDER BY region ASC";
    }
    
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $filteredRegions[] = $row['region'];
    }
    $filteredMainzones = [$userMainzone];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ML Rental - HO Summary Report</title>
<link rel="shortcut icon" href="../../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
  <!-- Local Google Font -->
  <link href="../../assets/css/poppins.css" rel="stylesheet">

  <!-- Local Bootstrap CSS -->
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- Local Bootstrap Icons -->
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">
  <style>
/* Dashboard and Summary Cards Styling */
.dashboard-card {
    backdrop-filter: blur(6px);
    background: rgba(255,255,255,0.75);
    transition: all 0.25s ease;
    border-radius: 12px;
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

/* Excel Style Summary Report Table */
.excel-report-container {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.excel-report-title-block {
    text-align: center;
    margin-bottom: 20px;
    font-family: Arial, sans-serif;
}
.excel-report-title-block .title-main {
    font-size: 16px;
    font-weight: 800;
    text-transform: uppercase;
    color: #000;
    letter-spacing: 0.5px;
}
.excel-report-title-block .title-sub {
    font-size: 14px;
    font-weight: 800;
    text-transform: uppercase;
    color: #000;
    margin-top: 2px;
}
.excel-report-title-block .title-date {
    font-size: 13px;
    font-weight: 700;
    color: #000;
    margin-top: 2px;
}

.table-excel {
    width: 100%;
    border-collapse: collapse;
    font-family: Calibri, Arial, sans-serif;
    font-size: 13px;
    color: #000;
    border: 2px solid #000000;
}

.table-excel th, 
.table-excel td {
    border: 1px solid #000000;
    padding: 5px 8px;
    vertical-align: middle;
}

/* Header Cells */
.table-excel thead th {
    background-color: #ED7D31 !important;
    color: #000000 !important;
    font-weight: 700;
    text-align: center;
    text-transform: UPPERCASE;
    font-size: 12px;
}

/* Thick Vertical Division Border between Archiving & Payment Methods */
.table-excel .thick-left {
    border-left: 4px solid #000000 !important;
}

/* Alternate row styling */
.table-excel tbody tr:nth-child(even) {
    background-color: #ffffff;
}
.table-excel tbody tr:hover {
    background-color: #f7fafc;
}

/* Numbers alignment */
.table-excel td.text-num {
    text-align: center;
}
.table-excel td.text-seq {
    text-align: center;
    width: 35px;
}

/* Mainzone Subtotal Row */
.table-excel tr.row-subtotal td {
    background-color: #FCE4D6 !important;
    font-weight: 700;
    color: #000000;
}

/* Grand Total Row */
.table-excel tr.row-grandtotal td {
    background-color: #ED7D31 !important;
    font-weight: 800;
    color: #000000;
    font-size: 13px;
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

                <a href="export_summary_report_region.php?filter_region=<?= urlencode($_POST['filter_region'] ?? '') ?>&region=<?= urlencode($selectedRegion) ?>&mainzone=<?= urlencode($selectedMainzone) ?>&area=<?= urlencode($selectedArea) ?>"
                   class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>

                <a href="export_summary_report_pdf_region.php?filter_region=<?= urlencode($_POST['filter_region'] ?? '') ?>&region=<?= urlencode($selectedRegion) ?>&mainzone=<?= urlencode($selectedMainzone) ?>&area=<?= urlencode($selectedArea) ?>"
                   class="btn btn-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </a>

            </form>
        </div>
    </div>

<?php if ($selectedNationwide || $selectedRegion || $selectedMainzone || empty($_POST)): ?>

<?php
/* ================================
   ROLE-BASED DISPLAY ENFORCEMENT
================================= */
$securedDisplayData = [];

if (!empty($displayData)) {
    foreach ($displayData as $mainzone => $regions) {

        // VPO mainzone authorization check (including LNCR / VISMIN sub-groups)
        if (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver'])) {
            $uMz = strtoupper(trim($userMainzone));
            $currMz = strtoupper(trim($mainzone));
            if ($uMz === 'LNCR' && !in_array($currMz, ['LNCR', 'LUZON', 'NCR'])) continue;
            elseif ($uMz === 'VISMIN' && !in_array($currMz, ['VISMIN', 'VISAYAS', 'MINDANAO'])) continue;
            elseif ($uMz !== 'LNCR' && $uMz !== 'VISMIN' && $currMz !== $uMz) continue;
        }

        foreach ($regions as $region => $rows) {

            if (($userRole === 'Am-Creator' || $userRole === 'Rm-Reviewer') && $region !== $userRegion) continue;

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

$isValidDate = function($d) {
    $d = trim($d ?? '');
    return !empty($d) && $d !== '0000-00-00' && $d !== '0000-00-00 00:00:00';
};

?>

<!-- =========================================================
     DYNAMIC EXCEL SUMMARY REPORT TABLE
========================================================= -->
<div class="excel-report-container mb-4">
    <div class="excel-report-title-block">
        <div class="title-main">RENTAL SUMMARY REPORT</div>
        <div class="title-sub">REGION SUMMARY</div>
        <div class="title-date">As of <?= date('F d, Y') ?></div>
    </div>

    <div class="table-responsive">
        <table class="table-excel">
            <thead>
                <tr>
                    <th rowspan="2" colspan="2" style="min-width: 200px;">REGIONS</th>
                    <th colspan="4">COUNT</th>
                    <th colspan="5" class="thick-left">PAYMENT METHOD</th>
                </tr>
                <tr>
                    <th>BRANCHES</th>
                    <th>REGISTERED /ACTIVE</th>
                    <th>UNREGISTERED</th>
                    <th>ARCHIVED</th>
                    <th class="thick-left">BRANCH CASH OUT</th>
                    <th>PAYMENT SOLUTION</th>
                    <th>PDC</th>
                    <th>BANK TRANSFER</th>
                    <th>MCASH</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Grand Total Accumulators
            $gtBranches     = 0;
            $gtRegistered   = 0;
            $gtUnregistered = 0;
            $gtArchived     = 0;
            $gtCashOut      = 0;
            $gtPaymentSol   = 0;
            $gtPdc          = 0;
            $gtBankTrans    = 0;
            $gtMcash        = 0;

            ksort($displayData, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($displayData as $mainzone => $regions):
                ksort($regions, SORT_NATURAL | SORT_FLAG_CASE);

                // Mainzone Subtotal Accumulators
                $mzBranches     = 0;
                $mzRegistered   = 0;
                $mzUnregistered = 0;
                $mzArchived     = 0;
                $mzCashOut      = 0;
                $mzPaymentSol   = 0;
                $mzPdc          = 0;
                $mzBankTrans    = 0;
                $mzMcash        = 0;

                $seqIndex = 1;

                foreach ($regions as $regionName => $rows):
                    $grouped = [];
                    foreach ($rows as $r) {
                        $bid = trim($r['branch_id'] ?? '');
                        if ($bid !== '') {
                            $grouped[$bid][] = $r;
                        }
                    }

                    $regBranches     = count($grouped);
                    $regRegistered   = 0;
                    $regUnregistered = 0;
                    $regArchived     = 0;
                    $regCashOut      = 0;
                    $regPaymentSol   = 0;
                    $regPdc          = 0;
                    $regBankTrans    = 0;
                    $regMcash        = 0;

                    foreach ($grouped as $bid => $bRows) {
                        // Criterion 3: Unregistered (no row at all in create_contract)
                        if (!isset($branchHasContractRecord[$bid])) {
                            $regUnregistered++;
                        }

                        // Criteria 2, 4-9
                        $result = classifyBranchRows($bRows, $isValidDate);

                        if ($result['registered']) {
                            $regRegistered++;
                        }

                        // Criterion 4: Archived is counted per branch_id (boolean), not per contract_number
                        if ($result['archived']) {
                            $regArchived++;
                        }
                        $regCashOut    += $result['payments']['CASH (Branch Cash-out)'];
                        $regPaymentSol += $result['payments']['RFP (PAYMENT SOLUTION)'];
                        $regPdc        += $result['payments']['RFP (PDC)'];
                        $regBankTrans  += $result['payments']['RFP (Remit To Account)'];
                        $regMcash      += $result['payments']['RFP (MCash)'];
                    }

                    // Accumulate into Mainzone Subtotals
                    $mzBranches     += $regBranches;
                    $mzRegistered   += $regRegistered;
                    $mzUnregistered += $regUnregistered;
                    $mzArchived     += $regArchived;
                    $mzCashOut      += $regCashOut;
                    $mzPaymentSol   += $regPaymentSol;
                    $mzPdc          += $regPdc;
                    $mzBankTrans    += $regBankTrans;
                    $mzMcash        += $regMcash;
            ?>
                    <tr>
                        <td class="text-seq"><?= $seqIndex++ ?></td>
                        <td class="text-start"><?= htmlspecialchars($regionName) ?></td>
                        <td class="text-num"><?= $regBranches ?: '' ?></td>
                        <td class="text-num"><?= $regRegistered ?: '' ?></td>
                        <td class="text-num"><?= $regUnregistered ?: '' ?></td>
                        <td class="text-num"><?= $regArchived ?: '' ?></td>
                        <td class="text-num thick-left"><?= $regCashOut ?: '' ?></td>
                        <td class="text-num"><?= $regPaymentSol ?: '' ?></td>
                        <td class="text-num"><?= $regPdc ?: '' ?></td>
                        <td class="text-num"><?= $regBankTrans ?: '' ?></td>
                        <td class="text-num"><?= $regMcash ?: '' ?></td>
                    </tr>
            <?php endforeach; ?>

            <!-- MAINZONE SUBTOTAL ROW -->
            <tr class="row-subtotal">
                <td colspan="2" class="text-start">TOTAL <?= htmlspecialchars(strtoupper($mainzone)) ?></td>
                <td class="text-num"><?= $mzBranches ?></td>
                <td class="text-num"><?= $mzRegistered ?></td>
                <td class="text-num"><?= $mzUnregistered ?></td>
                <td class="text-num"><?= $mzArchived ?></td>
                <td class="text-num thick-left"><?= $mzCashOut ?></td>
                <td class="text-num"><?= $mzPaymentSol ?></td>
                <td class="text-num"><?= $mzPdc ?></td>
                <td class="text-num"><?= $mzBankTrans ?></td>
                <td class="text-num"><?= $mzMcash ?></td>
            </tr>

            <?php
                // Accumulate Grand Totals
                $gtBranches     += $mzBranches;
                $gtRegistered   += $mzRegistered;
                $gtUnregistered += $mzUnregistered;
                $gtArchived     += $mzArchived;
                $gtCashOut      += $mzCashOut;
                $gtPaymentSol   += $mzPaymentSol;
                $gtPdc          += $mzPdc;
                $gtBankTrans    += $mzBankTrans;
                $gtMcash        += $mzMcash;
            endforeach;
            ?>

            <!-- GRAND TOTAL ROW -->
            <tr class="row-grandtotal">
                <td colspan="2" class="text-start">GRAND TOTAL</td>
                <td class="text-num"><?= $gtBranches ?></td>
                <td class="text-num"><?= $gtRegistered ?></td>
                <td class="text-num"><?= $gtUnregistered ?></td>
                <td class="text-num"><?= $gtArchived ?></td>
                <td class="text-num thick-left"><?= $gtCashOut ?></td>
                <td class="text-num"><?= $gtPaymentSol ?></td>
                <td class="text-num"><?= $gtPdc ?></td>
                <td class="text-num"><?= $gtBankTrans ?></td>
                <td class="text-num"><?= $gtMcash ?></td>
            </tr>

            </tbody>
        </table>
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
const mainFilter       = document.getElementById('mainFilter');
const regionDropdown   = document.getElementById('regionDropdown');
const mainzoneDropdown = document.getElementById('mainzoneDropdown');
const areaDropdown     = document.getElementById('areaDropdown');
const areasByRegion    = <?= $areasJson ?>;

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