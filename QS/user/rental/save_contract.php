<?php
// Ensure no whitespace or BOM before this opening tag
ob_start(); // Buffer output to prevent accidental headers/text leakage
session_start();
include('../../config/config.php');

// 1. Check Session
if (!isset($_SESSION['user_name'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
    exit();
}

// 2. Set Header early
header('Content-Type: application/json');

try {
    $conn->begin_transaction();

    // Validate Required Fields
    $requiredFields = ['mainzone', 'region', 'area', 'branch_id', 'contract_number', 'effectivity_date', 'expiry_date'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing field: $field");
        }
    }

    // Helper Functions
    function normalizeStartDate($dateStr, $conn) {
        $ts = strtotime($dateStr);
        return ($ts !== false) ? "'" . date('Y-m-01', $ts) . "'" : 'NULL';
    }
    
    function normalizeEndDate($dateStr, $conn) {
        $ts = strtotime($dateStr);
        return ($ts !== false) ? "'" . date('Y-m-t', $ts) . "'" : 'NULL';
    }
    
    function esc($value, $conn) {
        return "'" . $conn->real_escape_string(trim($value)) . "'";
    }

    $branchId = (int)$_POST['branch_id'];

    // Series Logic
    $result = $conn->query("SELECT MAX(series) AS max_series FROM create_contract WHERE branch_id = $branchId");
    $row = $result->fetch_assoc();
    $nextSeries = ($row['max_series'] ?? 0) + 1;

    $paymentDay = (int) ($_POST['payment_due_date'] ?? 1);
    $paymentDueDate = '2000-01-' . str_pad($paymentDay, 2, '0', STR_PAD_LEFT);

    // Lessor Logic - Updated to rely on $_POST['lessor_type'] from create_contract.php
    $selectedLessorType = $_POST['lessor_type'] ?? '';
    $lessorFields = [];
    $postedLessors = $_POST['lessors'] ?? [];

    for ($i = 1; $i <= 5; $i++) {
        $lessorIndex = $i - 1;
        $lessor = $postedLessors[$lessorIndex] ?? ['firstname' => '', 'middlename' => '', 'lastname' => '', 'gender' => '', 'mobile_number' => ''];

        if (in_array($selectedLessorType, ['Corporate', 'LGU'])) {
            $lessorFields["l{$i}_firstname"]     = "''";
            $lessorFields["l{$i}_middlename"]    = "''";
            $lessorFields["l{$i}_lastname"]      = "''";
            $lessorFields["l{$i}_gender"]        = "''";
            $lessorFields["mobile_number_l{$i}"] = "''";
        } else {
            $lessorFields["l{$i}_firstname"]     = esc($lessor['firstname'] ?? '', $conn);
            $lessorFields["l{$i}_middlename"]    = esc($lessor['middlename'] ?? '', $conn);
            $lessorFields["l{$i}_lastname"]      = esc($lessor['lastname'] ?? '', $conn);
            $lessorFields["l{$i}_gender"]        = esc($lessor['gender'] ?? '', $conn);
            $lessorFields["mobile_number_l{$i}"] = esc($lessor['mobile_number'] ?? '', $conn);
        }
    }

    $corporateLessor = in_array($selectedLessorType, ['Corporate', 'LGU'])
        ? esc($_POST['corporate_lessor'] ?? '', $conn)
        : 'NULL';

    $lessorType = !empty($selectedLessorType) ? esc($selectedLessorType, $conn) : "'Individual'";

    // Authorize Fields
    $authorizeFields = [
        'authorize_firstname'    => !empty($_POST['authorize_firstname']) ? esc($_POST['authorize_firstname'], $conn) : 'NULL',
        'authorize_middlename'   => !empty($_POST['authorize_middlename']) ? esc($_POST['authorize_middlename'], $conn) : 'NULL',
        'authorize_lastname'     => !empty($_POST['authorize_lastname']) ? esc($_POST['authorize_lastname'], $conn) : 'NULL',
        'authorize_gender'       => !empty($_POST['authorize_gender']) ? esc($_POST['authorize_gender'], $conn) : 'NULL',
        'authorize_mobileNumber' => !empty($_POST['authorize_mobileNumber']) ? esc($_POST['authorize_mobileNumber'], $conn) : 'NULL',
    ];

    // File Processing
    $fileFields = [];
    $maxFiles = 15;
    for ($i = 0; $i < $maxFiles; $i++) {
        if (!empty($_FILES['attachments']['name'][$i]) && is_uploaded_file($_FILES['attachments']['tmp_name'][$i])) {
            $fileData = $conn->real_escape_string(file_get_contents($_FILES['attachments']['tmp_name'][$i]));
            $mime = esc($_FILES['attachments']['type'][$i], $conn);
            $name = esc($_FILES['attachments']['name'][$i], $conn);
            $dataVal = "'$fileData'";
        } else {
            $dataVal = $mime = $name = 'NULL';
        }

        if ($i < 5) {
            $suffix = $i == 0 ? '' : $i + 1;
            $fileFields["contract_file$suffix"] = $dataVal;
            $fileFields["mimeType$suffix"]      = $mime;
            $fileFields["contractFilename$suffix"] = $name;
        } else {
            $index = $i + 1;
            $fileFields["attachment_$index"]       = $dataVal;
            $fileFields["mimeType$index"]          = $mime;
            $fileFields["attachment_{$index}_filename"] = $name;
        }
    }

    // Main Fields
    $fields = [
        'contract_number'    => esc($_POST['contract_number'], $conn),
        'mainzone'           => esc($_POST['mainzone'], $conn),
        'region'             => esc($_POST['region'], $conn),
        'area'               => esc($_POST['area'], $conn),
        'corporate_lessor'   => $corporateLessor,
        'lessor_type'        => $lessorType,
        'branch_id'          => $branchId,
        'branch'             => esc($_POST['branch_name'] ?? '', $conn),
        'series'             => $nextSeries,
        'branch_code'        => (int)($_POST['branch_code'] ?? 0),
        'kpx_code'           => esc($_POST['kpx_code'] ?? '', $conn),
        'zone'               => esc($_POST['zone'] ?? '', $conn),
        'corporate_name'     => esc($_POST['corporate_name'] ?? '', $conn),
        'rdo'                => esc($_POST['rdo'] ?? '', $conn),
        'contract_start'     => esc($_POST['effectivity_date'], $conn),
        'contract_end'       => esc($_POST['expiry_date'], $conn),
        'payment_due_date'   => esc($paymentDueDate, $conn),
        'amount'             => !empty($_POST['monthly_rental']) ? esc($_POST['monthly_rental'], $conn) : 'NULL',
        'vat_type'           => esc($_POST['vat_type'] ?? '', $conn),
        'inputted_amount'    => !empty($_POST['inputted_amount']) ? esc($_POST['inputted_amount'], $conn) : 'NULL',
        'net_of_vat'         => !empty($_POST['net_vat_amount']) ? esc($_POST['net_vat_amount'], $conn) : 'NULL',
        'vat_amount'         => !empty($_POST['vat_amount']) ? esc($_POST['vat_amount'], $conn) : 'NULL',
        'wtax_type'          => esc($_POST['wtax_type'] ?? '', $conn),
        'wtax'               => !empty($_POST['wtax_amount']) ? esc($_POST['wtax_amount'], $conn) : 'NULL',
        'total_month_rental' => !empty($_POST['total_amount']) ? esc($_POST['total_amount'], $conn) : 'NULL',
        'amount_lessor'      => !empty($_POST['amount_to_lessor']) ? esc($_POST['amount_to_lessor'], $conn) : 'NULL',
        'mode_of_payment'    => esc($_POST['modeOfPayment'] ?? '', $conn),
        'notarized'          => esc($_POST['notarized'] ?? '', $conn),
        'status'             => "'Active'",
        'rfp_status'         => "NULL",
        'request_status'     => "'Created'",
        'created_by'         => esc($_SESSION['user_name'], $conn),
        'advanceRental_amount' => !empty($_POST['advanceRental']) ? esc($_POST['advanceRental'], $conn) : 'NULL',
        'advanceRental_from' => !empty($_POST['advanceFrom']) ? esc($_POST['advanceFrom'], $conn) : 'NULL',
        'advanceRental_to'   => !empty($_POST['advanceTo']) ? esc($_POST['advanceTo'], $conn) : 'NULL',
        'securityDeposit_amount' => !empty($_POST['securityDeposit']) ? esc($_POST['securityDeposit'], $conn) : 'NULL',
        'security_type'      => esc($_POST['depositType'] ?? '', $conn),
        'consumable_from'    => !empty($_POST['depositFrom']) ? esc($_POST['depositFrom'], $conn) : 'NULL',
        'consumable_to'      => !empty($_POST['depositTo']) ? esc($_POST['depositTo'], $conn) : 'NULL',
        'created_date'       => esc(date('Y-m-d'), $conn)
    ];

    $finalFields = array_merge($fields, $lessorFields, $authorizeFields, $fileFields);
    $columns = implode(", ", array_keys($finalFields));
    $values = implode(", ", array_values($finalFields));

    $sql = "INSERT INTO create_contract ($columns) VALUES ($values)";
    if (!$conn->query($sql)) {
        throw new Exception("SQL Error (Contract): " . $conn->error);
    }

    // Escalation Processing
    if (!empty($_POST['escalations'])) {
        $escalations = json_decode($_POST['escalations'], true);
        if (is_array($escalations)) {
            foreach ($escalations as $eRow) {
                $startDate = !empty($eRow['start_date']) ? normalizeStartDate($eRow['start_date'], $conn) : 'NULL';
                $endDate   = !empty($eRow['end_date'])   ? normalizeEndDate($eRow['end_date'], $conn)   : 'NULL';
                
                $status = '';
                if (!empty($eRow['end_date'])) {
                    $endMonth = date('Y-m', strtotime($eRow['end_date']));
                    $currentMonth = date('Y-m');
                    $status = ($endMonth < $currentMonth) ? 'Approved' : '';
                }

                if (!empty($eRow['id'])) {
                    $sqlE = "UPDATE escalation SET
                            start_date = $startDate,
                            end_date = $endDate,
                            escalation_percent = " . floatval($eRow['escalation']) . ",
                            fixed_amount = " . floatval($eRow['fixed_amount']) . ",
                            increase = " . floatval($eRow['increase']) . ",
                            monthly_rental = " . floatval($eRow['rental']) . ",
                            vat = " . floatval($eRow['vat']) . ",
                            net_of_vat = " . floatval($eRow['net_vat']) . ",
                            wtax_type = " . esc($eRow['wtax_type'], $conn) . ",
                            wtax_percent = " . floatval($eRow['wtax_percent']) . ",
                            wtax = " . floatval($eRow['wtax']) . ",
                            amount_to_lessor = " . floatval($eRow['amount_lessor']) . ",
                            yearly_amount = " . floatval($eRow['yearly']) . ",
                            status = '" . $conn->real_escape_string($status) . "'
                        WHERE id = " . intval($eRow['id']);
                } else {
                    $sqlE = "INSERT INTO escalation (
                            col_number, mainzone, zone, region, area, branch_id, branch,
                            effectivity_date, expiry_date, start_date, end_date, monthly_due_date,
                            escalation_percent, fixed_amount, increase, monthly_rental, vat_type, 
                            vat_percent, vat, net_of_vat, wtax_type, wtax_percent, wtax, 
                            amount_to_lessor, yearly_amount, created_date, created_by, status
                        ) VALUES (
                            " . esc($_POST['contract_number'], $conn) . ",
                            " . esc($_POST['mainzone'], $conn) . ",
                            " . esc($_POST['zone'] ?? '', $conn) . ",
                            " . esc($_POST['region'], $conn) . ",
                            " . esc($_POST['area'], $conn) . ",
                            " . esc($_POST['branch_id'], $conn) . ",
                            " . esc($_POST['branch_name'] ?? '', $conn) . ",
                            " . esc($_POST['effectivity_date'], $conn) . ",
                            " . esc($_POST['expiry_date'], $conn) . ",
                            $startDate, $endDate,
                            " . esc($paymentDueDate, $conn) . ",
                            " . floatval($eRow['escalation']) . ",
                            " . floatval($eRow['fixed_amount']) . ",
                            " . floatval($eRow['increase']) . ",
                            " . floatval($eRow['rental']) . ",
                            " . esc($_POST['vat_type'] ?? '', $conn) . ",
                            12,
                            " . floatval($eRow['vat']) . ",
                            " . floatval($eRow['net_vat']) . ",
                            " . esc($_POST['wtax_type'], $conn) . ",
                            " . floatval($eRow['wtax_percent']) . ",
                            " . floatval($eRow['wtax']) . ",
                            " . floatval($eRow['amount_lessor']) . ",
                            " . floatval($eRow['yearly']) . ",
                            NOW(),
                            " . esc($_SESSION['user_name'], $conn) . ",
                            '" . $conn->real_escape_string($status) . "'
                        )";
                }
                if (!$conn->query($sqlE)) {
                    throw new Exception("Escalation Save Error: " . $conn->error);
                }
            }
        }
    }

    $conn->commit();
    ob_clean(); // Clear buffer before sending JSON
    echo json_encode([
        'success' => true,
        'message' => 'Contract and Escalations saved successfully.',
        'contract_number' => $_POST['contract_number'],
        'series' => $nextSeries
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>