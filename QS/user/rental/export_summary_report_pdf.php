<?php
require '../../config/config.php';
require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* =====================================================
   START SESSION & USER INFO
=====================================================*/
session_start();
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

/* =====================================================
   GET FILTER PARAMETERS
=====================================================*/
$filterType       = $_GET['filter_region'] ?? '';
$selectedRegion   = $_GET['region'] ?? '';
$selectedMainzone = $_GET['mainzone'] ?? '';
$selectedArea     = $_GET['area'] ?? '';

/* =====================================================
   LOAD BRANCH PROFILE
=====================================================*/
$branchProfile = [];
$sqlAll = "SELECT branch_id, branch_name, region, mainzone, area, ml_matic_status
           FROM branch_insurance
           WHERE region IS NOT NULL AND region != ''
           AND UPPER(TRIM(ml_matic_status)) = 'ACTIVE'
           ORDER BY mainzone ASC, region ASC, branch_name ASC";
$resAll = mysqli_query($conn, $sqlAll);

while ($r = mysqli_fetch_assoc($resAll)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $mz = getCanonicalMainzone($r['mainzone'], $r['region']);
    $rg = $r['region'];
    $id = $r['branch_id'];

    $branchProfile[$mz][$rg][$id] = [
        'name' => $r['branch_name'],
        'area' => $r['area'] ?? ''
    ];
}

/* =====================================================
   LOAD CONTRACT DATA
=====================================================*/
$mlRental = [];
$sqlML = "SELECT c.*, b.branch_name, b.region, b.mainzone, b.area, b.ml_matic_status
          FROM create_contract c
          INNER JOIN branch_insurance b ON b.branch_id = c.branch_id
          WHERE UPPER(TRIM(b.ml_matic_status)) = 'ACTIVE'";
$resML = mysqli_query($conn, $sqlML);

while ($r = mysqli_fetch_assoc($resML)) {
    if (strtoupper(trim($r['ml_matic_status'] ?? '')) !== 'ACTIVE') continue;
    $mz = getCanonicalMainzone($r['mainzone'], $r['region']);
    $rg = $r['region'];
    $id = $r['branch_id'];

    $mlRental[$mz][$rg][$id][] = [
        'branch_id'       => $id,
        'name'            => $r['branch_name'],
        'contract_number' => $r['contract_number'] ?? '',
        'contract_start'  => $r['contract_start'] ?? '',
        'contract_end'    => $r['contract_end'] ?? '',
        'start_date'      => $r['start_date'] ?? '',
        'end_date'        => $r['end_date'] ?? '',
        'rfp_status'      => $r['rfp_status'] ?? '',
        'request_status'  => $r['request_status'] ?? '',
        'payment'         => strtoupper($r['mode_of_payment'] ?? ''),
        'area'            => $r['area'] ?? ''
    ];
}

/* =====================================================
   ALIGN DATA (MATCH + UNMATCHED)
=====================================================*/
$alignedData = [];
$mainzones = array_unique(array_merge(array_keys($branchProfile), array_keys($mlRental)));
sort($mainzones);

foreach ($mainzones as $mz) {
    $regions = array_unique(array_merge(array_keys($branchProfile[$mz] ?? []), array_keys($mlRental[$mz] ?? [])));
    sort($regions);

    foreach ($regions as $region) {
        $left  = $branchProfile[$mz][$region] ?? [];
        $right = $mlRental[$mz][$region] ?? [];

        $matchedIds = array_intersect(array_keys($left), array_keys($right));

        // MATCHED
        foreach ($matchedIds as $id) {
            $validContracts = array_filter($right[$id], function($c) {
                return strtoupper(trim($c['contract_number'] ?? '')) !== 'VOID';
            });

            if (!empty($validContracts)) {
                foreach ($validContracts as $contract) {
                    $alignedData[$mz][$region][] = [
                        'branch_id'   => $id,
                        'branch_name' => $left[$id]['name'],
                        'contract'    => $contract,
                        'match'       => true,
                        'area'        => $contract['area']
                    ];
                }
            } else {
                $alignedData[$mz][$region][] = [
                    'branch_id'   => $id,
                    'branch_name' => $left[$id]['name'],
                    'contract'    => null,
                    'match'       => false,
                    'area'        => $left[$id]['area']
                ];
            }
            unset($left[$id], $right[$id]);
        }

        // UNMATCHED LEFT
        foreach ($left as $id => $branch) {
            $alignedData[$mz][$region][] = [
                'branch_id'   => $id,
                'branch_name' => $branch['name'],
                'contract'    => null,
                'match'       => false,
                'area'        => $branch['area']
            ];
        }

        // UNMATCHED RIGHT
        foreach ($right as $contracts) {
            $validContracts = array_filter($contracts, function($c) {
                return strtoupper(trim($c['contract_number'] ?? '')) !== 'VOID';
            });
            foreach ($validContracts as $contract) {
                $alignedData[$mz][$region][] = [
                    'branch_id'   => $contract['branch_id'],
                    'branch_name' => $contract['name'],
                    'contract'    => $contract,
                    'match'       => false,
                    'area'        => $contract['area']
                ];
            }
        }
    }
}

/* =====================================================
   APPLY FILTERS + USER ROLE RESTRICTIONS
=====================================================*/
$displayData = [];
$selectedNationwide = ($filterType === 'Nationwide');

if ($selectedNationwide || empty($filterType)) {
    $displayData = $alignedData;
} elseif ($filterType === 'ByMainzone' && $selectedMainzone) {
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
} elseif ($filterType === 'ByRegion' && $selectedRegion) {
    foreach ($alignedData as $mz => $regions) {
        if (isset($regions[$selectedRegion])) {
            $rows = $regions[$selectedRegion];
            if ($selectedArea) {
                $rows = array_filter($rows, fn($row) => ($row['area'] ?? '') === $selectedArea);
            }
            if (!empty($rows)) $displayData[$mz][$selectedRegion] = array_values($rows);
        }
    }
} else {
    $displayData = $alignedData;
}

// Role Security Enforcement
$securedDisplayData = [];
if (!empty($displayData)) {
    foreach ($displayData as $mainzone => $regions) {
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
                $securedDisplayData[$mainzone][$region] = array_values($rows);
            }
        }
    }
    $displayData = $securedDisplayData;
}

/* =====================================================
   PAYMENT MAPPING & METHODS CONFIGURATION
=====================================================*/
$paymentMapping = [
    'CASH'             => 'CASH (Branch Cash-out)',
    'BRANCH CASH OUT'  => 'CASH (Branch Cash-out)',
    'PAYMENT SOLUTION' => 'RFP (PAYMENT SOLUTION)',
    'PDC'              => 'RFP (PDC)',
    'WALLET'           => 'RFP (MCash)',
    'MCASH'            => 'RFP (MCash)',
    'RTA'              => 'RFP (Remit To Account)',
    'BANK TRANSFER'    => 'RFP (Remit To Account)'
];

$paymentMethods = [
    'CASH (Branch Cash-out)',
    'RFP (PAYMENT SOLUTION)',
    'RFP (PDC)',
    'RFP (MCash)',
    'RFP (Remit To Account)'
];

/* =====================================================
   ACCURATE COUNTERS & PRE-CALCULATION
=====================================================*/
$countRegisteredBranches = 0;
$countUnmatchedBranches  = 0;
$countDataArchiving      = 0;
$countPayments           = array_fill_keys($paymentMethods, 0);

$seenBranches = [];
$seenContracts = [];

foreach ($displayData as $mainzone => $regions) {
    foreach ($regions as $region => $rows) {
        if (empty($rows)) continue;

        $groupedByBranch = [];
        foreach ($rows as $row) {
            $bid = trim($row['branch_id'] ?? '');
            if ($bid !== '') $groupedByBranch[$bid][] = $row;
        }

        foreach ($groupedByBranch as $branchId => $branchRows) {
            $seenBranches[$branchId] = true;

            $hasMatch = false;
            foreach ($branchRows as $r) {
                $contract = $r['contract'] ?? [];
                $cNum = strtoupper(trim($contract['contract_number'] ?? ''));
                if (!empty($r['match']) && !empty($contract) && $cNum !== '' && $cNum !== 'VOID') {
                    $hasMatch = true;
                    break;
                }
            }

            if ($hasMatch) {
                $countRegisteredBranches++;
            } else {
                $countUnmatchedBranches++;
            }

            foreach ($branchRows as $r) {
                $contract = $r['contract'] ?? [];
                $contractKey = trim($contract['contract_number'] ?? '');
                if (strtoupper($contractKey) === 'VOID' || empty($contractKey)) {
                    continue;
                }

                if (!isset($seenContracts[$contractKey])) {
                    $seenContracts[$contractKey] = true;

                    $rfpStatus     = $contract['rfp_status'] ?? '';
                    $requestStatus = $contract['request_status'] ?? '';
                    $isArchived    = (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                                     ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Ready', 'Approved', 'Reviewed']));

                    if ($isArchived) {
                        $countDataArchiving++;
                    }

                    $modeOfPayment = strtoupper(trim($contract['payment'] ?? ''));
                    $mappedMode = $paymentMapping[$modeOfPayment] ?? $modeOfPayment;

                    if (isset($countPayments[$mappedMode])) {
                        $countPayments[$mappedMode]++;
                    }
                }
            }
        }
    }
}

$countBranches = count($seenBranches);

/* =====================================================
   HTML GENERATION FOR PDF
=====================================================*/
$html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #333; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #d2d6dc; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .summary-table { width: 50%; margin: 0 auto 30px auto; }
        .summary-header-main { background-color: #2C3E50; color: #ffffff; text-align: center; font-size: 11px; }
        .summary-header-sub { background-color: #34495E; color: #ffffff; font-size: 10px; }
        .summary-alt-row { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .region-header { background-color: #e2e8f0; font-size: 11px; font-weight: bold; padding: 8px; }
        .row-active { background-color: #c6efce; color: #006100; font-weight: bold; }
        .badge-check { 
            font-family: "DejaVu Sans", sans-serif; 
            color: #006100; 
            font-size: 11px; 
        }
    </style>
</head>
<body>';

// SUMMARY SECTION
$html .= '<div class="title">HO Summary Report</div>';
$html .= '<table class="summary-table">';
$html .= '<tr><th colspan="2" class="summary-header-main">SUMMARY REPORT</th></tr>';
$html .= '<tr><th class="summary-header-sub">Metric</th><th class="summary-header-sub text-right">Count</th></tr>';

$summaryData = [
    'Total Branches'                         => $countBranches,
    'Branch with registered active contract' => $countRegisteredBranches,
    'Branch without registered contracts'    => $countUnmatchedBranches,
    'Rental Archiving'                       => $countDataArchiving,
    'CASH (Branch Cash-out)'                 => $countPayments['CASH (Branch Cash-out)'],
    'RFP (PAYMENT SOLUTION)'                 => $countPayments['RFP (PAYMENT SOLUTION)'],
    'RFP (PDC)'                              => $countPayments['RFP (PDC)'],
    'RFP (MCash)'                            => $countPayments['RFP (MCash)'],
    'RFP (Remit To Account)'                 => $countPayments['RFP (Remit To Account)']
];

$alt = false;
foreach ($summaryData as $label => $value) {
    $rowClass = $alt ? 'summary-alt-row' : '';
    $html .= "<tr class='{$rowClass}'>";
    $html .= "<td>{$label}</td>";
    $html .= "<td class='text-right'><strong>{$value}</strong></td>";
    $html .= "</tr>";
    $alt = !$alt;
}
$html .= '</table>';

// DETAILED REPORT SECTION
$addedRows = [];

foreach ($displayData as $mainzone => $regions) {
    foreach ($regions as $region => $rows) {
        if (empty($rows)) continue;

        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr><th colspan="14" class="region-header">REGION: ' . strtoupper(htmlspecialchars($region)) . '</th></tr>';
        $html .= '<tr>';
        $html .= '<th>Branch ID</th><th>Branch Profile</th><th>Contract Number</th>';
        $html .= '<th>Contract Start</th><th>Contract End</th><th class="text-center">Rental Archiving</th>';
        $html .= '<th>RFP Start</th><th>RFP End</th>';
        foreach ($paymentMethods as $pm) {
            $html .= '<th class="text-center">' . htmlspecialchars($pm) . '</th>';
        }
        $html .= '<th>Match Status</th>';
        $html .= '</tr>';
        $html .= '</thead><tbody>';

        // Group by branch
        $groupedByBranch = [];
        foreach ($rows as $row) {
            $bid = $row['branch_id'] ?? '';
            if (!isset($groupedByBranch[$bid])) $groupedByBranch[$bid] = [];
            $groupedByBranch[$bid][] = $row;
        }

        // Fill grouped rows
        foreach ($groupedByBranch as $branchId => $branchRows) {
            if (empty($branchId)) continue;

            $validBranchRows = [];
            foreach ($branchRows as $r) {
                $contract = $r['contract'] ?? [];
                $cNum = strtoupper(trim($contract['contract_number'] ?? ''));
                if ($cNum === 'VOID') continue;
                $validBranchRows[] = $r;
            }

            if (empty($validBranchRows)) {
                $firstRow = $branchRows[0];
                $firstRow['contract'] = null;
                $firstRow['match'] = false;
                $validBranchRows = [$firstRow];
            }

            foreach ($validBranchRows as $i => $row) {
                $contract = $row['contract'] ?? [];
                $contractNum = $contract['contract_number'] ?? '';
                $isVoid = (strtoupper(trim($contractNum)) === 'VOID');
                
                $uniqueKey = $branchId . '_' . ($contractNum ?: 'NO_CONTRACT');
                if (isset($addedRows[$uniqueKey])) continue;
                $addedRows[$uniqueKey] = true;

                $rfpStatus = $contract['rfp_status'] ?? '';
                $requestStatus = $contract['request_status'] ?? '';
                $isDataArchiving = !$isVoid && !empty($contractNum) && (
                    (empty($rfpStatus) && in_array($requestStatus,['Prepared','Created'])) ||
                    ($rfpStatus === 'Reviewed' && in_array($requestStatus,['Ready','Approved', 'Reviewed']))
                );

                $mode = strtoupper($contract['payment'] ?? '');
                $mappedMode = $paymentMapping[$mode] ?? $mode;
                $status = (!$isVoid && !empty($contractNum) && !empty($row['match'])) ? 'REGISTERED' : 'UNREGISTERED';
                $rowClass = ($status === 'REGISTERED') ? 'row-active' : '';
                
                $cStart = (!$isVoid && !empty($contract['contract_start'])) ? date('M d, Y', strtotime($contract['contract_start'])) : '';
                $cEnd   = (!$isVoid && !empty($contract['contract_end'])) ? date('M d, Y', strtotime($contract['contract_end'])) : '';
                $rStart = (!$isVoid && !empty($contract['start_date'])) ? date('M Y', strtotime($contract['start_date'])) : '';
                $rEnd   = (!$isVoid && !empty($contract['end_date'])) ? date('M Y', strtotime($contract['end_date'])) : '';

                $html .= "<tr class='{$rowClass}'>";
                $html .= "<td>" . ($i === 0 ? htmlspecialchars($branchId) : '') . "</td>";
                $html .= "<td>" . ($i === 0 ? htmlspecialchars($row['branch_name']) : '') . "</td>";
                $html .= "<td>" . (!$isVoid ? htmlspecialchars($contractNum) : '') . "</td>";
                $html .= "<td>{$cStart}</td>";
                $html .= "<td>{$cEnd}</td>";

                $html .= "<td class='text-center'>" . ($isDataArchiving ? '<span class="badge-check">&#10004;</span>' : '') . "</td>";
                $html .= "<td>{$rStart}</td>";
                $html .= "<td>{$rEnd}</td>";

                foreach ($paymentMethods as $method) {
                    $hasPayment = (!$isVoid && !empty($contractNum) && $mappedMode === $method);
                    $html .= "<td class='text-center'>" . ($hasPayment ? '<span class="badge-check">&#10004;</span>' : '') . "</td>";
                }

                $html .= "<td>{$status}</td>";
                $html .= "</tr>";
            }
        }
        $html .= '</tbody></table>';
    }
}

$html .= '</body></html>';

/* =====================================================
   GENERATE & DOWNLOAD PDF
=====================================================*/
if (class_exists('Dompdf\Options')) {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
} else {
    $dompdf = new Dompdf();
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('isRemoteEnabled', true);
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('ho_full_report.pdf', ['Attachment' => true]);
exit;
?>