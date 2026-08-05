<?php
session_start();
include('../../config/config.php');

// Ensure no accidental white space or warnings break the JSON
ob_start(); 
header('Content-Type: application/json');

// Set to 0 to prevent warnings from prepending to your JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $ids = $data['ids'] ?? [];
    $approved_by = $_SESSION['user_name'] ?? 'System';

    if (!empty($ids)) {
        mysqli_begin_transaction($conn);
        try {
            // Prepare reusable statements
            $fetchContractStmt = mysqli_prepare($conn, "
                SELECT cc.*, e.monthly_rental, e.vat, e.net_of_vat, e.wtax, e.amount_to_lessor
                FROM create_contract cc
                LEFT JOIN escalation e 
                    ON cc.contract_number = e.col_number
                    AND YEAR(cc.end_date) = YEAR(e.end_date)
                    AND MONTH(cc.end_date) = MONTH(e.end_date)
                WHERE cc.id = ?
                LIMIT 1
            ");

            $updateEscalationStmt = mysqli_prepare($conn, "
                UPDATE escalation 
                SET status = 'Approved' 
                WHERE col_number = ? 
                AND DATE_FORMAT(end_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
            ");

            $updateContractStmt = mysqli_prepare($conn, "
                UPDATE create_contract 
                SET request_status = 'Approved', approved_by = ? 
                WHERE id = ? AND request_status = 'Checked'
            ");

            // Move this outside the loop so it can be reused multiple times
            $checkSql = "SELECT transaction_date FROM transactional WHERE contract_number = ?";
            $checkStmt = mysqli_prepare($conn, $checkSql);

            foreach ($ids as $selected_id) {
                mysqli_stmt_bind_param($fetchContractStmt, "i", $selected_id);
                mysqli_stmt_execute($fetchContractStmt);
                $contractResult = mysqli_stmt_get_result($fetchContractStmt);

                if (!$contractResult || !($contractRow = mysqli_fetch_assoc($contractResult))) {
                    continue;
                }

                $contract_number = $contractRow['contract_number'];
                $startDate       = strtotime($contractRow['start_date']);
                $endDateUnix     = strtotime($contractRow['end_date']);
                $paymentDueDay   = (int)date('d', strtotime($contractRow['payment_due_date']));
                $currentDate     = $startDate;

                // ✅ Fetch existing transaction_dates
                $existingDates = [];
                mysqli_stmt_bind_param($checkStmt, "s", $contract_number);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                while ($row = mysqli_fetch_assoc($checkResult)) {
                    $existingDates[date('Y-m-d', strtotime($row['transaction_date']))] = true;
                }
                // Do NOT close $checkStmt here; it needs to run for the next ID in the loop.

                $insertValues = [];

                while ($currentDate <= $endDateUnix) {
                    $year  = date('Y', $currentDate);
                    $month = date('m', $currentDate);
                    $lastDayOfMonth = date('t', $currentDate);

                    if (date('Y-m', $currentDate) == date('Y-m', $endDateUnix)) {
                        $endYear  = date('Y', $endDateUnix);
                        $endMonth = date('m', $endDateUnix);
                        $endLastDayOfMonth = date('t', $endDateUnix);
                        $reusedDay = min($paymentDueDay, $endLastDayOfMonth);
                        $transactionDate = "$endYear-$endMonth-" . str_pad($reusedDay, 2, "0", STR_PAD_LEFT);
                    } else {
                        $dueDay = min($paymentDueDay, $lastDayOfMonth);
                        $transactionDate = "$year-$month-" . str_pad($dueDay, 2, "0", STR_PAD_LEFT);
                    }

                    if (isset($existingDates[$transactionDate])) {
                        $currentDate = strtotime("+1 month", $currentDate);
                        continue;
                    }

                    $advanceRentalAmount = 0;
                    $advanceTag = "NULL";

                    if (!empty($contractRow['advanceRental_from']) && !empty($contractRow['advanceRental_to'])) {
                        $advanceFrom = date('Y-m', strtotime($contractRow['advanceRental_from']));
                        $advanceTo   = date('Y-m', strtotime($contractRow['advanceRental_to']));
                        $currentYM   = date('Y-m', strtotime($transactionDate));

                        if ($currentYM >= $advanceFrom && $currentYM <= $advanceTo) {
                            $advanceTag = "'Advance'";
                            $advanceRentalAmount = (float)$contractRow['advanceRental_amount'];
                        }
                    }

                    // Define the conditions before building the string
$prepared_by_val = !empty($contractRow['prepared_by']) 
? "'" . mysqli_real_escape_string($conn, $contractRow['prepared_by']) . "'" 
: "NULL";

$rfp_date_val = !empty($contractRow['rfp_date']) 
? "'" . mysqli_real_escape_string($conn, $contractRow['rfp_date']) . "'" 
: "NULL";

$insertValues[] = "(
                    '" . mysqli_real_escape_string($conn, $transactionDate) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['kpx_code']) . "',
                    '5360001',
                    '" . mysqli_real_escape_string($conn, $contractRow['contract_number']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mainzone']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['zone']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['region']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['area']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['branch_code']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['branch_id']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['branch']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['contract_start']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['contract_end']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['start_date']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['end_date']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['payment_due_date']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l1_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l1_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l1_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l1_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l2_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l2_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l2_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l2_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l3_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l3_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l3_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l3_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l4_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l4_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l4_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l4_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l5_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l5_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l5_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['l5_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['lessor_type']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['corporate_name']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['rdo']) . "',
                    " . (float)$contractRow['monthly_rental'] . ",
                    '" . mysqli_real_escape_string($conn, $contractRow['vat_type']) . "',
                    '',
                    " . (float)$contractRow['net_of_vat'] . ",
                    " . (float)$contractRow['vat'] . ",
                    " . (float)$contractRow['wtax'] . ",
                    " . (float)$contractRow['total_month_rental'] . ",
                    " . (float)$contractRow['amount_to_lessor'] . ",
                    " . (float)$contractRow['amount_to_lessor'] . ",
                    'Adjustment',
                    '" . mysqli_real_escape_string($conn, $contractRow['mode_of_payment']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['wallet_number']) . "',
                    'Unpaid',
                    " . (float)$advanceRentalAmount . ",
                    " . $advanceTag . ",
                    '" . mysqli_real_escape_string($conn, $contractRow['authorize_firstname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['authorize_middlename']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['authorize_lastname']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['authorize_gender']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['authorize_mobileNumber']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mobile_number_l1']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mobile_number_l2']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mobile_number_l3']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mobile_number_l4']) . "',
                    '" . mysqli_real_escape_string($conn, $contractRow['mobile_number_l5']) . "',
                    " . $prepared_by_val . ",
                    " . $rfp_date_val . "
                )";

                    if (date('Y-m', $currentDate) == date('Y-m', $endDateUnix)) break;
                    $currentDate = strtotime("+1 month", $currentDate);
                }

                if (!empty($insertValues)) {
                    $insertQuery = "INSERT INTO transactional (transaction_date, kpx_code, gl_code, contract_number, mainzone, zone, region, area, branch_code, branch_id, branch, contract_start, contract_end, start_date, end_date, payment_due_date, l1_firstname, l1_middlename, l1_lastname, l1_gender, l2_firstname, l2_middlename, l2_lastname, l2_gender, l3_firstname, l3_middlename, l3_lastname, l3_gender, l4_firstname, l4_middlename, l4_lastname, l4_gender, l5_firstname, l5_middlename, l5_lastname, l5_gender, lessor_type, corporate_name, rdo, amount, vat_type, inputted_amount, net_of_vat, vat_amount, wtax, total_month_rental, amount_lessor, edit_amount_lessor, category, mode_of_payment, wallet_number, status, advance_rental_amount, advance_tag, authorize_firstname, authorize_middlename, authorize_lastname, authorize_gender, authorize_mobileNumber, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5, rfp_by, rfp_date) VALUES " . implode(", ", $insertValues);
                    if (!mysqli_query($conn, $insertQuery)) {
                        throw new Exception("Insert failed: " . mysqli_error($conn));
                    }
                }

                mysqli_stmt_bind_param($updateEscalationStmt, "ss", $contract_number, $contractRow['end_date']);
                mysqli_stmt_execute($updateEscalationStmt);

                mysqli_stmt_bind_param($updateContractStmt, "si", $approved_by, $selected_id);
                mysqli_stmt_execute($updateContractStmt);
            }

            mysqli_commit($conn);
            ob_clean(); // Final clear to ensure ONLY JSON is sent
            echo json_encode(["success" => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            ob_clean();
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "No IDs received"]);
    }
}
?>