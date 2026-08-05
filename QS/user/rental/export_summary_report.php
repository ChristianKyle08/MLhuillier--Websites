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
$sqlAll = "SELECT branch_id, branch_name, region, mainzone, area
           FROM branch_insurance
           WHERE region IS NOT NULL AND region != ''
           AND ml_matic_status = 'Active'
           ORDER BY branch_name ASC";
$resAll = mysqli_query($conn, $sqlAll);

while ($r = mysqli_fetch_assoc($resAll)) {
    $mz = !empty($r['mainzone']) ? $r['mainzone'] : 'UNASSIGNED';
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
$sqlML = "SELECT c.*, b.branch_name, b.region, b.mainzone, b.area
          FROM create_contract c
          INNER JOIN branch_insurance b ON b.branch_id = c.branch_id
          WHERE b.ml_matic_status = 'Active'";
$resML = mysqli_query($conn, $sqlML);

while ($r = mysqli_fetch_assoc($resML)) {
    $mz = !empty($r['mainzone']) ? $r['mainzone'] : 'UNASSIGNED';
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

foreach ($mainzones as $mz) {
    $regions = array_unique(array_merge(array_keys($branchProfile[$mz] ?? []), array_keys($mlRental[$mz] ?? [])));

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
foreach ($alignedData as $mz => $regions) {
    if (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver']) && $mz !== $userMainzone) continue;
    if ($filterType === 'ByMainzone' && $mz !== $selectedMainzone) continue;

    foreach ($regions as $region => $rows) {
        if ($userRole === 'Am-Creator' && $region !== $userRegion) continue;
        if ($userRole === 'Rm-Reviewer' && $region !== $userRegion) continue;
        if ($filterType === 'ByRegion' && $region !== $selectedRegion) continue;

        $rows = array_filter($rows, function($row) use ($selectedArea, $userRole, $userArea) {
            if (!empty($selectedArea) && ($row['area'] ?? '') !== $selectedArea) return false;
            if ($userRole === 'Am-Creator' && ($row['area'] ?? '') !== $userArea) return false;
            return true;
        });

        if (!empty($rows)) {
            $displayData[$mz][$region] = array_values($rows);
        }
    }
}

/* =====================================================
   PAYMENT MAPPING & METHODS CONFIGURATION
=====================================================*/
$paymentMapping = [
    'CASH'             => 'CASH (Branch Cash-out)',
    'PAYMENT SOLUTION' => 'RFP (PAYMENT SOLUTION)',
    'PDC'              => 'RFP (PDC)',
    'WALLET'           => 'RFP (MCash)',
    'RTA'              => 'RFP (Remit To Account)'
];

$paymentMethods = [
    'CASH (Branch Cash-out)',
    'RFP (PAYMENT SOLUTION)',
    'RFP (PDC)',
    'RFP (MCash)',
    'RFP (Remit To Account)'
];

/* =====================================================
   COUNTERS & PRE-CALCULATION (EXCLUDING VOID CONTRACTS)
=====================================================*/
$countDataArchiving = 0;
$countUndefined = 0;
$countPayments = array_fill_keys($paymentMethods, 0);

$seenBranches = [];
$seenUnmatchedBranches = [];
$seenContracts = [];
$seenBranchMetrics = [];

foreach ($displayData as $mainzone => $regions) {
    foreach ($regions as $region => $rows) {
        if (empty($rows)) continue;

        $groupedByBranch = [];
        foreach ($rows as $row) {
            $bid = $row['branch_id'] ?? '';
            if (!isset($groupedByBranch[$bid])) $groupedByBranch[$bid] = [];
            $groupedByBranch[$bid][] = $row;
        }

        foreach ($groupedByBranch as $branchId => $branchRows) {
            if (empty($branchId)) continue;
            if (!isset($seenBranches[$branchId])) {
                $seenBranches[$branchId] = true;
            }

            $hasMatch = false;
            foreach ($branchRows as $r) {
                $contract = $r['contract'] ?? [];
                $cNum = strtoupper(trim($contract['contract_number'] ?? ''));
                if (!empty($r['match']) && !empty($contract) && $cNum !== '' && $cNum !== 'VOID') {
                    $hasMatch = true;
                    break;
                }
            }

            if (!$hasMatch && !isset($seenUnmatchedBranches[$branchId])) {
                $seenUnmatchedBranches[$branchId] = true;
            }

            foreach ($branchRows as $r) {
                $contract = $r['contract'] ?? [];
                $contractKey = trim($contract['contract_number'] ?? '');
                if (strtoupper($contractKey) === 'VOID' || empty($contractKey)) {
                    continue;
                }

                $rfpStatus = $contract['rfp_status'] ?? '';
                $requestStatus = $contract['request_status'] ?? '';
                $modeOfPayment = strtoupper(trim($contract['payment'] ?? ''));
                $mappedMode = $paymentMapping[$modeOfPayment] ?? $modeOfPayment;

                $isDataArchiving = (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                                   ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Ready', 'Approved', 'Reviewed']));

                $isUndefined = (empty($rfpStatus) && in_array($requestStatus, ['Prepared', 'Created'])) ||
                               ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready');

                if (!isset($seenContracts[$contractKey])) {
                    $seenContracts[$contractKey] = true;

                    if ($isDataArchiving && !isset($seenBranchMetrics[$branchId])) {
                        $seenBranchMetrics[$branchId] = true;
                        $countDataArchiving++;
                    }

                    if ($isUndefined) $countUndefined++;
                    if (isset($countPayments[$mappedMode])) {
                        $countPayments[$mappedMode]++;
                    }
                }
            }
        }
    }
}

$countBranches = count($seenBranches);
$countUnmatchedBranches = count($seenUnmatchedBranches);

/* =====================================================
   CREATE EXCEL
=====================================================*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$rowNum = 1;

/* =====================================================
   SUMMARY (PLACED ON TOP WITH MODERN CLEAN STYLING)
=====================================================*/
$sheet->setCellValue("A$rowNum", 'SUMMARY REPORT');
$sheet->mergeCells("A$rowNum:B$rowNum");
$sheet->getStyle("A$rowNum:B$rowNum")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);
$rowNum++;

$sheet->setCellValue("A$rowNum", 'Metric');
$sheet->setCellValue("B$rowNum", 'Count');
$sheet->getStyle("A$rowNum:B$rowNum")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
]);
$rowNum++;

$summary = [
    'Total Branches'                        => $countBranches,
    'Branch with registered active contract' => $countDataArchiving,
    'Branch without registered contracts'   => $countUnmatchedBranches,
    'Rental Archiving'                      => $countDataArchiving,
    'CASH (Branch Cash-out)'                => $countPayments['CASH (Branch Cash-out)'],
    'RFP (PAYMENT SOLUTION)'                => $countPayments['RFP (PAYMENT SOLUTION)'],
    'RFP (PDC)'                             => $countPayments['RFP (PDC)'],
    'RFP (MCash)'                           => $countPayments['RFP (MCash)'],
    'RFP (Remit Total Account)'             => $countPayments['RFP (Remit To Account)']
];

$alt = false;
foreach ($summary as $label => $value) {
    $sheet->setCellValue("A$rowNum", $label);
    $sheet->setCellValue("B$rowNum", $value);
    
    $fillColor = $alt ? 'F8F9FA' : 'FFFFFF';
    $sheet->getStyle("A$rowNum:B$rowNum")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D2D6DC']]
        ],
        'font' => ['size' => 10]
    ]);
    $sheet->getStyle("B$rowNum")->getFont()->setBold(true);
    $sheet->getStyle("B$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    $alt = !$alt;
    $rowNum++;
}

// Spacing between summary and detailed report
$rowNum += 3;

/* =====================================================
   FILL EXCEL ROWS (DETAILED REPORT)
=====================================================*/
$headers = array_merge([
    'Branch ID', 'Branch Profile', 'Contract Number',
    'Contract Start', 'Contract End', 'Data Archiving',
    'RFP Start', 'RFP End'
], $paymentMethods, ['Match Status']);

$addedRows = [];

foreach ($displayData as $mainzone => $regions) {
    foreach ($regions as $region => $rows) {
        if (empty($rows)) continue;

        // REGION HEADER
        $sheet->setCellValue("A$rowNum", "REGION: " . strtoupper($region));
        $sheet->mergeCells("A$rowNum:N$rowNum");
        $sheet->getStyle("A$rowNum")->getFont()->setBold(true)->setSize(14);
        $rowNum++;

        // COLUMN HEADERS
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $rowNum, $header);
            $sheet->getStyle($col . $rowNum)->getFont()->setBold(true);
            $col++;
        }
        $rowNum++;

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
                if ($cNum === 'VOID') {
                    continue;
                }
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

                // Fill row data
                $sheet->setCellValue("A$rowNum", $i === 0 ? $branchId : '');
                $sheet->setCellValue("B$rowNum", $i === 0 ? $row['branch_name'] : '');
                $sheet->setCellValue("C$rowNum", !$isVoid ? $contractNum : '');
                $sheet->setCellValue("D$rowNum", (!$isVoid && !empty($contract['contract_start'])) ? date('M d, Y', strtotime($contract['contract_start'])) : '');
                $sheet->setCellValue("E$rowNum", (!$isVoid && !empty($contract['contract_end'])) ? date('M d, Y', strtotime($contract['contract_end'])) : '');
                $sheet->setCellValue("F$rowNum", $isDataArchiving ? '✓' : '');
                $sheet->setCellValue("G$rowNum", (!$isVoid && !empty($contract['start_date'])) ? date('M Y', strtotime($contract['start_date'])) : '');
                $sheet->setCellValue("H$rowNum", (!$isVoid && !empty($contract['end_date'])) ? date('M Y', strtotime($contract['end_date'])) : '');

                $colLetter = 'I'; 
                foreach ($paymentMethods as $method) {
                    $sheet->setCellValue($colLetter . $rowNum, (!$isVoid && !empty($contractNum) && $mappedMode === $method) ? '✓' : '');
                    $colLetter++; 
                }

                $status = (!$isVoid && !empty($contractNum) && !empty($row['match'])) ? 'ACTIVE' : 'INACTIVE';
                $sheet->setCellValue("N$rowNum", $status);

                if ($status === 'ACTIVE') {
                    $sheet->getStyle("A$rowNum:N$rowNum")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6EFCE']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '006100']]
                    ]);
                }

                $rowNum++;
            }
        }

        $rowNum += 2;
    }
}

/* =====================================================
   EXPORT FILE
=====================================================*/
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ho_full_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>