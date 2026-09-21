<?php
require '../../config/config.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
    'RFP (Remit To Account)',
    'RFP (MCash)'
];

/* =====================================================
   CREATE EXCEL SPREADSHEET
=====================================================*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rental Summary');

$rowNum = 1;

// Title Block
$sheet->setCellValue("A$rowNum", 'RENTAL SUMMARY REPORT');
$sheet->mergeCells("A$rowNum:K$rowNum");
$sheet->getStyle("A$rowNum")->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$rowNum++;

$sheet->setCellValue("A$rowNum", 'REGION SUMMARY');
$sheet->mergeCells("A$rowNum:K$rowNum");
$sheet->getStyle("A$rowNum")->applyFromArray([
    'font' => ['bold' => true, 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$rowNum++;

$sheet->setCellValue("A$rowNum", 'As of ' . date('F d, Y'));
$sheet->mergeCells("A$rowNum:K$rowNum");
$sheet->getStyle("A$rowNum")->applyFromArray([
    'font' => ['italic' => true, 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$rowNum += 2;

/* =====================================================
   REGIONAL SUMMARY REPORT TABLE (MATCHING WEB DESIGN)
=====================================================*/
// Table Header Line 1
$sheet->setCellValue("A$rowNum", '#');
$sheet->setCellValue("B$rowNum", 'REGIONS');
$sheet->setCellValue("C$rowNum", 'COUNT');
$sheet->mergeCells("C$rowNum:F$rowNum");
$sheet->setCellValue("G$rowNum", 'PAYMENT METHOD');
$sheet->mergeCells("G$rowNum:K$rowNum");

$sheet->getStyle("A$rowNum:K$rowNum")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$rowNum++;

// Table Header Line 2
$sheet->setCellValue("C$rowNum", 'BRANCHES');
$sheet->setCellValue("D$rowNum", 'REGISTERED / ACTIVE');
$sheet->setCellValue("E$rowNum", 'UNREGISTERED');
$sheet->setCellValue("F$rowNum", 'ARCHIVED');
$sheet->setCellValue("G$rowNum", 'BRANCH CASH OUT');
$sheet->setCellValue("H$rowNum", 'PAYMENT SOLUTION');
$sheet->setCellValue("I$rowNum", 'PDC');
$sheet->setCellValue("J$rowNum", 'BANK TRANSFER');
$sheet->setCellValue("K$rowNum", 'MCASH');

$sheet->getStyle("A$rowNum:K$rowNum")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$rowNum++;

// Accumulators for Grand Total
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

        // Subtotals
        $mzBranches += $regBranches; $mzRegistered += $regRegistered; $mzUnregistered += $regUnregistered;
        $mzArchived += $regArchived; $mzCashOut += $regCashOut; $mzPaymentSol += $regPaymentSol;
        $mzPdc += $regPdc; $mzBankTrans += $regBankTrans; $mzMcash += $regMcash;

        // Populate Row
        $sheet->setCellValue("A$rowNum", $seqIndex++);
        $sheet->setCellValue("B$rowNum", $regionName);
        $sheet->setCellValue("C$rowNum", $regBranches ?: '');
        $sheet->setCellValue("D$rowNum", $regRegistered ?: '');
        $sheet->setCellValue("E$rowNum", $regUnregistered ?: '');
        $sheet->setCellValue("F$rowNum", $regArchived ?: '');
        $sheet->setCellValue("G$rowNum", $regCashOut ?: '');
        $sheet->setCellValue("H$rowNum", $regPaymentSol ?: '');
        $sheet->setCellValue("I$rowNum", $regPdc ?: '');
        $sheet->setCellValue("J$rowNum", $regBankTrans ?: '');
        $sheet->setCellValue("K$rowNum", $regMcash ?: '');

        $sheet->getStyle("A$rowNum:K$rowNum")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getStyle("A$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C$rowNum:K$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNum++;
    }

    // MAINZONE SUBTOTAL ROW
    $sheet->setCellValue("A$rowNum", "TOTAL " . strtoupper($mainzone));
    $sheet->mergeCells("A$rowNum:B$rowNum");
    $sheet->setCellValue("C$rowNum", $mzBranches);
    $sheet->setCellValue("D$rowNum", $mzRegistered);
    $sheet->setCellValue("E$rowNum", $mzUnregistered);
    $sheet->setCellValue("F$rowNum", $mzArchived);
    $sheet->setCellValue("G$rowNum", $mzCashOut);
    $sheet->setCellValue("H$rowNum", $mzPaymentSol);
    $sheet->setCellValue("I$rowNum", $mzPdc);
    $sheet->setCellValue("J$rowNum", $mzBankTrans);
    $sheet->setCellValue("K$rowNum", $mzMcash);

    $sheet->getStyle("A$rowNum:K$rowNum")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4D6']],
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $sheet->getStyle("A$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $rowNum++;

    // Grand Totals Accumulation
    $gtBranches += $mzBranches; $gtRegistered += $mzRegistered; $gtUnregistered += $mzUnregistered;
    $gtArchived += $mzArchived; $gtCashOut += $mzCashOut; $gtPaymentSol += $mzPaymentSol;
    $gtPdc += $mzPdc; $gtBankTrans += $mzBankTrans; $gtMcash += $mzMcash;
}

// GRAND TOTAL ROW
$sheet->setCellValue("A$rowNum", "GRAND TOTAL");
$sheet->mergeCells("A$rowNum:B$rowNum");
$sheet->setCellValue("C$rowNum", $gtBranches);
$sheet->setCellValue("D$rowNum", $gtRegistered);
$sheet->setCellValue("E$rowNum", $gtUnregistered);
$sheet->setCellValue("F$rowNum", $gtArchived);
$sheet->setCellValue("G$rowNum", $gtCashOut);
$sheet->setCellValue("H$rowNum", $gtPaymentSol);
$sheet->setCellValue("I$rowNum", $gtBankTrans);
$sheet->setCellValue("K$rowNum", $gtMcash);

$sheet->getStyle("A$rowNum:K$rowNum")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
]);
$sheet->getStyle("A$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Auto-fit column widths
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* =====================================================
   EXPORT FILE
=====================================================*/
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ho_summary_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>