<?php
session_start();
if (!ob_get_level()) ob_start();

require '../../config/config.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Increase memory & execution time
ini_set('memory_limit', '1024M');
set_time_limit(0);

/**
 * USER CONTEXT
 */
$userRole     = $_SESSION['user_role'] ?? '';
$userRegion   = $_SESSION['region'] ?? '';
$userArea     = $_SESSION['area'] ?? '';
$userMainzone = $_SESSION['mainzone'] ?? '';

$selectedRegion = trim($_POST['selectedRegion'] ?? '');
$selectedType   = trim($_POST['contract_type'] ?? '');

/**
 * BASE SQL
 */
$sql = "
SELECT mainzone, region, branch_id, branch, area, contract_number, status, request_status, contract_end
FROM create_contract
WHERE rfp_status = 'Reviewed'
";

$params = [];
$types  = "";

// ROLE FILTER
if ($userRole === 'Am-Creator') {
    $sql .= " AND region = ? AND area = ?";
    $types .= "ss";
    $params[] = $userRegion;
    $params[] = $userArea;
} elseif ($userRole === 'Rm-Reviewer') {
    $sql .= " AND region = ?";
    $types .= "s";
    $params[] = $userRegion;
} elseif (in_array($userRole, ['Vpo-Reviewer','Vpo-Checker','Vpo-Approver'])) {
    $sql .= " AND mainzone = ?";
    $types .= "s";
    $params[] = $userMainzone;
}

// REGION FILTER
if ($selectedRegion !== '') {
    $sql .= " AND region = ?";
    $types .= "s";
    $params[] = $selectedRegion;
}

// CONTRACT TYPE FILTER
if ($selectedType === 'created') {
    $sql .= " AND request_status = 'Created'";
} elseif ($selectedType === 'active') {
    $sql .= " AND contract_number = (
        SELECT MAX(c2.contract_number)
        FROM create_contract c2
        WHERE c2.branch_id = create_contract.branch_id
    ) AND status = 'Active'";
} elseif ($selectedType === 'stopped') {
    $sql .= " AND contract_number = (
        SELECT MAX(c2.contract_number)
        FROM create_contract c2
        WHERE c2.branch_id = create_contract.branch_id
    ) AND contract_end < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

$sql .= " ORDER BY region ASC";

// EXECUTE QUERY
$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL Prepare Error: " . $conn->error);

if (!empty($params)) $stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

/**
 * SPREADSHEET BUILD
 */
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$currentRegion = null;
$sheet = null;
$rowNum = 2;

$headers = ['Main Zone', 'Region', 'Contract Number', 'Branch ID', 'Branch', 'Area'];

while ($row = $result->fetch_assoc()) {

    // Create new sheet per region
    if ($currentRegion !== $row['region']) {
        $currentRegion = $row['region'];
        $regionTitle = substr(preg_replace('/[\\/:*?[\]]/', '_', $currentRegion), 0, 31);

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($regionTitle);

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $rowNum = 2;
    }

    $sheet->fromArray([
        $row['mainzone'],
        $row['region'],
        $row['contract_number'],
        $row['branch_id'],
        $row['branch'],
        $row['area']
    ], null, "A$rowNum");

    $rowNum++;

    // free memory every 10k rows
    if ($rowNum % 10000 === 0) {
        $sheet->garbageCollect();
    }
}

// CLEAN OUTPUT BUFFER
if (ob_get_length()) ob_end_clean();

// DOWNLOAD FILE
$filename = 'Reviewed_Contracts_'.date('Ymd_His').'.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);
$writer->save('php://output');
exit;
