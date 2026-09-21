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
   HTML GENERATION FOR PDF
=====================================================*/
$html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000000; }
        .title-main { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .title-sub { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-top: 3px; }
        .title-date { text-align: center; font-size: 11px; margin-top: 3px; margin-bottom: 20px; }
        table.excel-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
        table.excel-table th, table.excel-table td { border: 1px solid #000000; padding: 5px 6px; text-align: center; }
        table.excel-table th { background-color: #ED7D31; color: #000000; font-weight: bold; text-transform: uppercase; }
        table.excel-table td.text-left { text-align: left; }
        table.excel-table tr.subtotal td { background-color: #FCE4D6; font-weight: bold; }
        table.excel-table tr.grandtotal td { background-color: #ED7D31; font-weight: bold; }
        .thick-left { border-left: 2px solid #000000 !important; }
    </style>
</head>
<body>';

$html .= '<div class="title-main">RENTAL SUMMARY REPORT</div>';
$html .= '<div class="title-sub">REGION SUMMARY</div>';
$html .= '<div class="title-date">As of ' . date('F d, Y') . '</div>';

$html .= '<table class="excel-table">';
$html .= '<thead>';
$html .= '<tr>';
$html .= '<th rowspan="2" colspan="2">REGIONS</th>';
$html .= '<th colspan="4">COUNT</th>';
$html .= '<th colspan="5" class="thick-left">PAYMENT METHOD</th>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<th>BRANCHES</th><th>REGISTERED / ACTIVE</th><th>UNREGISTERED</th><th>ARCHIVED</th>';
$html .= '<th class="thick-left">BRANCH CASH OUT</th><th>PAYMENT SOLUTION</th><th>PDC</th><th>BANK TRANSFER</th><th>MCASH</th>';
$html .= '</tr>';
$html .= '</thead><tbody>';

$gtBranches = 0; $gtRegistered = 0; $gtUnregistered = 0; $gtArchived = 0;
$gtCashOut = 0; $gtPaymentSol = 0; $gtPdc = 0; $gtBankTrans = 0; $gtMcash = 0;

ksort($displayData, SORT_NATURAL | SORT_FLAG_CASE);

foreach ($displayData as $mainzone => $regions) {
    ksort($regions, SORT_NATURAL | SORT_FLAG_CASE);

    $mzBranches = 0; $mzRegistered = 0; $mzUnregistered = 0; $mzArchived = 0;
    $mzCashOut = 0; $mzPaymentSol = 0; $mzPdc = 0; $mzBankTrans = 0; $mzMcash = 0;

    $seqIndex = 1;

    foreach ($regions as $regionName => $rows) {
        $grouped = [];
        foreach ($rows as $r) {
            $bid = trim($r['branch_id'] ?? '');
            if ($bid !== '') $grouped[$bid][] = $r;
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
            $hasMatch = false;
            foreach ($bRows as $br) {
                $contract = $br['contract'] ?? [];
                $cnum = strtoupper(trim($contract['contract_number'] ?? ''));
                if (!empty($br['match']) && !empty($contract) && $cnum !== '' && $cnum !== 'VOID') {
                    $hasMatch = true;
                    break;
                }
            }

            if ($hasMatch) $regRegistered++;
            else $regUnregistered++;

            $seenContractsInBranch = [];
            foreach ($bRows as $br) {
                $contract = $br['contract'] ?? [];
                $cnum = strtoupper(trim($contract['contract_number'] ?? ''));
                if ($cnum === 'VOID' || empty($cnum)) continue;

                if (isset($seenContractsInBranch[$cnum])) continue;
                $seenContractsInBranch[$cnum] = true;

                $rfpStatus     = $contract['rfp_status'] ?? '';
                $requestStatus = $contract['request_status'] ?? '';
                $isArchived    = (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                                 ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Ready', 'Approved', 'Reviewed']));

                if ($isArchived) $regArchived++;

                $pay = strtoupper(trim($contract['payment'] ?? ''));
                if (in_array($pay, ['CASH', 'BRANCH CASH OUT', 'CASH (BRANCH CASH-OUT)'])) $regCashOut++;
                elseif (in_array($pay, ['PAYMENT SOLUTION', 'RFP (PAYMENT SOLUTION)'])) $regPaymentSol++;
                elseif (in_array($pay, ['PDC', 'RFP (PDC)'])) $regPdc++;
                elseif (in_array($pay, ['BANK TRANSFER', 'RTA', 'RFP (REMIT TO ACCOUNT)'])) $regBankTrans++;
                elseif (in_array($pay, ['MCASH', 'WALLET', 'RFP (MCASH)'])) $regMcash++;
            }
        }

        $mzBranches += $regBranches; $mzRegistered += $regRegistered; $mzUnregistered += $regUnregistered;
        $mzArchived += $regArchived; $mzCashOut += $regCashOut; $mzPaymentSol += $regPaymentSol;
        $mzPdc += $regPdc; $mzBankTrans += $regBankTrans; $mzMcash += $regMcash;

        $html .= '<tr>';
        $html .= '<td>' . $seqIndex++ . '</td>';
        $html .= '<td class="text-left">' . htmlspecialchars($regionName) . '</td>';
        $html .= '<td>' . ($regBranches ?: '') . '</td>';
        $html .= '<td>' . ($regRegistered ?: '') . '</td>';
        $html .= '<td>' . ($regUnregistered ?: '') . '</td>';
        $html .= '<td>' . ($regArchived ?: '') . '</td>';
        $html .= '<td class="thick-left">' . ($regCashOut ?: '') . '</td>';
        $html .= '<td>' . ($regPaymentSol ?: '') . '</td>';
        $html .= '<td>' . ($regPdc ?: '') . '</td>';
        $html .= '<td>' . ($regBankTrans ?: '') . '</td>';
        $html .= '<td>' . ($regMcash ?: '') . '</td>';
        $html .= '</tr>';
    }

    $html .= '<tr class="subtotal">';
    $html .= '<td colspan="2" class="text-left">TOTAL ' . htmlspecialchars(strtoupper($mainzone)) . '</td>';
    $html .= '<td>' . $mzBranches . '</td>';
    $html .= '<td>' . $mzRegistered . '</td>';
    $html .= '<td>' . $mzUnregistered . '</td>';
    $html .= '<td>' . $mzArchived . '</td>';
    $html .= '<td class="thick-left">' . $mzCashOut . '</td>';
    $html .= '<td>' . $mzPaymentSol . '</td>';
    $html .= '<td>' . $mzPdc . '</td>';
    $html .= '<td>' . $mzBankTrans . '</td>';
    $html .= '<td>' . $mzMcash . '</td>';
    $html .= '</tr>';

    $gtBranches += $mzBranches; $gtRegistered += $mzRegistered; $gtUnregistered += $mzUnregistered;
    $gtArchived += $mzArchived; $gtCashOut += $mzCashOut; $gtPaymentSol += $mzPaymentSol;
    $gtPdc += $mzPdc; $gtBankTrans += $mzBankTrans; $gtMcash += $mzMcash;
}

$html .= '<tr class="grandtotal">';
$html .= '<td colspan="2" class="text-left">GRAND TOTAL</td>';
$html .= '<td>' . $gtBranches . '</td>';
$html .= '<td>' . $gtRegistered . '</td>';
$html .= '<td>' . $gtUnregistered . '</td>';
$html .= '<td>' . $gtArchived . '</td>';
$html .= '<td class="thick-left">' . $gtCashOut . '</td>';
$html .= '<td>' . $gtPaymentSol . '</td>';
$html .= '<td>' . $gtPdc . '</td>';
$html .= '<td>' . $gtBankTrans . '</td>';
$html .= '<td>' . $gtMcash . '</td>';
$html .= '</tr>';

$html .= '</tbody></table>';
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

$dompdf->stream('ho_summary_report.pdf', ['Attachment' => true]);
exit;
?>