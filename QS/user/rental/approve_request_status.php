<?php
session_start();
header('Content-Type: application/json');
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('Location: login_form.php');
    exit;
}

if (isset($_POST['selected_id']) && isset($_POST['approved_by'])) {
    $selected_id = mysqli_real_escape_string($conn, $_POST['selected_id']);
    $approved_by = mysqli_real_escape_string($conn, $_POST['approved_by']);

    // Fetch contract details
    $fetchContractQuery = "SELECT * FROM create_contract WHERE id = ?";
    $fetchContractStmt = mysqli_prepare($conn, $fetchContractQuery);
    if (!$fetchContractStmt) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to prepare fetch contract statement: ' . mysqli_error($conn)]));
    }
    mysqli_stmt_bind_param($fetchContractStmt, "s", $selected_id);
    mysqli_stmt_execute($fetchContractStmt);
    $contractResult = mysqli_stmt_get_result($fetchContractStmt);

    if ($contractResult && $contractRow = mysqli_fetch_assoc($contractResult)) {
        // Check if there are unpaid transactional records for the same branch and contract period
        $startDate = $contractRow['start_date'];
        $endDate = $contractRow['end_date'];
        $branchId = $contractRow['branch_id'];
        
        $checkExistingQuery = "
            SELECT COUNT(*) AS count 
            FROM transactional 
            WHERE branch_id = ? 
              AND status = 'Unpaid' 
              AND (
                  (transaction_date BETWEEN ? AND ?) OR 
                  (transaction_date BETWEEN ? AND ?)
              )
        ";
        $checkExistingStmt = mysqli_prepare($conn, $checkExistingQuery);
        if (!$checkExistingStmt) {
            die(json_encode(['status' => 'error', 'message' => 'Failed to prepare check existing statement: ' . mysqli_error($conn)]));
        }
        mysqli_stmt_bind_param($checkExistingStmt, "issss", $branchId, $startDate, $endDate, $startDate, $endDate);
        mysqli_stmt_execute($checkExistingStmt);
        $existingResult = mysqli_stmt_get_result($checkExistingStmt);
        $existingData = mysqli_fetch_assoc($existingResult);

        if ($existingData['count'] == 0) {
            // Update contract status
            $updateQuery = "UPDATE create_contract SET request_status = 'Approved', approved_by = ? WHERE id = ? AND request_status = 'Checked'";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            if (!$updateStmt) {
                die(json_encode(['status' => 'error', 'message' => 'Failed to prepare update statement: ' . mysqli_error($conn)]));
            }
            mysqli_stmt_bind_param($updateStmt, "ss", $approved_by, $selected_id);
            $updateResult = mysqli_stmt_execute($updateStmt);

            if ($updateResult) {
                // Insert records into transactional table
                $insertQuery = "INSERT INTO transactional (
                    transaction_date, kpx_code, gl_code, contract_number, mainzone, zone, region, area, 
                    branch_code, branch_id, branch, start_date, end_date, payment_due_date, l1_firstname, 
                    l1_middlename, l1_lastname, l1_gender, l2_firstname, l2_middlename, l2_lastname, 
                    l2_gender, l3_firstname, l3_middlename, l3_lastname, l3_gender, l4_firstname, 
                    l4_middlename, l4_lastname, l4_gender, l5_firstname, l5_middlename, l5_lastname, 
                    l5_gender, lessor_type, corporate_name, rdo, amount, vat_type, inputted_amount, net_of_vat, vat_amount, wtax, 
                    total_month_rental, amount_lessor, edit_amount_lessor, category, mode_of_payment, 
                    wallet_number, status, authorize_firstname, authorize_middlename, authorize_lastname, 
                    authorize_gender, authorize_mobileNumber, mobile_number_l1, mobile_number_l2, 
                    mobile_number_l3, mobile_number_l4, mobile_number_l5
                ) VALUES ";

                $values = [];
                $currentDate = strtotime($contractRow['start_date']);
                $endDate = strtotime($contractRow['end_date']);
                $paymentDueDay = date('d', strtotime($contractRow['payment_due_date']));

                while ($currentDate <= $endDate) {
                    $currentMonth = date('m', $currentDate);
                    $currentYear = date('Y', $currentDate);

                    // Get the last day of the current month
                    $lastDayOfMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

                    // If the payment due day is greater than the last day of the month, use the last day of the month
                    $transactionDate = ($paymentDueDay > $lastDayOfMonth)
                        ? "$currentYear-$currentMonth-$lastDayOfMonth"
                        : "$currentYear-$currentMonth-$paymentDueDay";

                    // Check if a record with the same transactionDate and kpx_code exists with status 'Paid'
                    $checkPaidQuery = "SELECT COUNT(*) AS count FROM transactional WHERE branch_id = ? AND transaction_date = ? AND status = 'Paid' AND contract_number = ?";
                    $checkPaidStmt = mysqli_prepare($conn, $checkPaidQuery);
                    if (!$checkPaidStmt) {
                        die(json_encode(['status' => 'error', 'message' => 'Failed to prepare check paid statement: ' . mysqli_error($conn)]));
                    }
                    mysqli_stmt_bind_param($checkPaidStmt, "sss", $branchId, $transactionDate, $contractRow['contract_number']);
                    mysqli_stmt_execute($checkPaidStmt);
                    $paidResult = mysqli_stmt_get_result($checkPaidStmt);
                    $paidData = mysqli_fetch_assoc($paidResult);

                    if ($paidData['count'] == 0) {
                        $values[] = "(
                            '$transactionDate', '" . $contractRow['kpx_code'] . "', '5360001', '" . $contractRow['contract_number'] . "', 
                            '" . $contractRow['mainzone'] . "', '" . $contractRow['zone'] . "', '" . $contractRow['region'] . "', 
                            '" . $contractRow['area'] . "', '" . $contractRow['branch_code'] . "', '" . $contractRow['branch_id'] . "', 
                            '" . $contractRow['branch'] . "', '" . $contractRow['start_date'] . "', '" . $contractRow['end_date'] . "', 
                            '" . $contractRow['payment_due_date'] . "', '" . $contractRow['l1_firstname'] . "', 
                            '" . $contractRow['l1_middlename'] . "', '" . $contractRow['l1_lastname'] . "', 
                            '" . $contractRow['l1_gender'] . "', '" . $contractRow['l2_firstname'] . "', 
                            '" . $contractRow['l2_middlename'] . "', '" . $contractRow['l2_lastname'] . "', 
                            '" . $contractRow['l2_gender'] . "', '" . $contractRow['l3_firstname'] . "', 
                            '" . $contractRow['l3_middlename'] . "', '" . $contractRow['l3_lastname'] . "', 
                            '" . $contractRow['l3_gender'] . "', '" . $contractRow['l4_firstname'] . "', 
                            '" . $contractRow['l4_middlename'] . "', '" . $contractRow['l4_lastname'] . "', 
                            '" . $contractRow['l4_gender'] . "', '" . $contractRow['l5_firstname'] . "', 
                            '" . $contractRow['l5_middlename'] . "', '" . $contractRow['l5_lastname'] . "', 
                            '" . $contractRow['l5_gender'] . "', '" . $contractRow['lessor_type'] . "', 
                            '" . $contractRow['corporate_name'] . "', '" . $contractRow['rdo'] . "', '" . $contractRow['amount'] . "', 
                            '" . $contractRow['vat_type'] . "', '" . $contractRow['inputted_amount'] . "', '" . $contractRow['net_of_vat'] . "', '" . $contractRow['vat_amount'] . "', 
                            '" . $contractRow['wtax'] . "', '" . $contractRow['total_month_rental'] . "', 
                            '" . $contractRow['amount_lessor'] . "', '" . $contractRow['edit_amount_lessor'] . "', 
                            'Adjustment', '" . $contractRow['mode_of_payment'] . "', '" . $contractRow['wallet_number'] . "', 
                            'Unpaid', '" . $contractRow['authorize_firstname'] . "', '" . $contractRow['authorize_middlename'] . "', 
                            '" . $contractRow['authorize_lastname'] . "', '" . $contractRow['authorize_gender'] . "', 
                            '" . $contractRow['authorize_mobileNumber'] . "', '" . $contractRow['mobile_number_l1'] . "', 
                            '" . $contractRow['mobile_number_l2'] . "', '" . $contractRow['mobile_number_l3'] . "', 
                            '" . $contractRow['mobile_number_l4'] . "', '" . $contractRow['mobile_number_l5'] . "'
                        )";
                    }

                    // Move to the next month
                    $currentDate = strtotime('first day of next month', $currentDate);
                }

                if (!empty($values)) {
                    $insertQuery .= implode(',', $values);
                    $insertResult = mysqli_query($conn, $insertQuery);

                    if ($insertResult) {
                        echo json_encode(['status' => 'success', 'message' => 'Contract successfully approved.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to insert records into transactional table: ' . mysqli_error($conn)]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No records to insert.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update contract: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unpaid transactional records already exist for the same branch and contract period.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Contract not found.']);
    }
}
?>
