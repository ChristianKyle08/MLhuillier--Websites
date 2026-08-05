<?php
session_start();
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractId = (int) ($_POST['contract_id'] ?? 0);
    $approved_by = $_SESSION['user_name'] ?? 'System';

    if ($contractId > 0) {
        // Fetch current rfp_status and request_status + needed contract data
        $query = "
            SELECT cc.*, e.monthly_rental, e.vat, e.net_of_vat, e.wtax, e.amount_to_lessor
            FROM create_contract cc
            LEFT JOIN escalation e 
                ON cc.contract_number = e.col_number
                AND YEAR(cc.end_date) = YEAR(e.end_date)
                AND MONTH(cc.end_date) = MONTH(e.end_date)
            WHERE cc.id = $contractId
            LIMIT 1
        ";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $rfp_status = $row['rfp_status'];
            $request_status = $row['request_status'];

            $updateQuery = '';

            // Case 1: rfp_status empty + request_status = Prepared
            if ((empty($rfp_status) || is_null($rfp_status)) && $request_status === 'Prepared') {
                $updateQuery = "UPDATE create_contract 
                                SET rfp_status = 'Reviewed', request_status = 'Ready' 
                                WHERE id = $contractId";
            }

            if ($rfp_status === 'Reviewed' && $request_status === 'Created') {
                $updateQuery = "UPDATE create_contract 
                                SET rfp_status = 'Reviewed', request_status = 'Prepared' 
                                WHERE id = $contractId";
            }

            if ($rfp_status === 'Reviewed' && $request_status === 'Prepared') {
                $updateQuery = "UPDATE create_contract 
                                SET rfp_status = 'Reviewed', request_status = 'Reviewed' 
                                WHERE id = $contractId";
            }

            if ($rfp_status === 'Reviewed' && $request_status === 'Reviewed') {
                $updateQuery = "UPDATE create_contract 
                                SET rfp_status = 'Reviewed', request_status = 'Checked' 
                                WHERE id = $contractId";
            }

            // ✅ Final approval case → generate transactional entries
            if ($rfp_status === 'Reviewed' && $request_status === 'Checked') {
                mysqli_begin_transaction($conn);

                try {
                    // Approve contract
                    $contractUpdate = "
                        UPDATE create_contract 
                        SET request_status = 'Approved', approved_by = '$approved_by'
                        WHERE id = $contractId
                    ";
                    mysqli_query($conn, $contractUpdate);

                    // Approve escalation
                    $escalationUpdate = "
                        UPDATE escalation 
                        SET status = 'Approved' 
                        WHERE col_number = '{$row['contract_number']}' 
                        AND DATE_FORMAT(end_date, '%Y-%m') = DATE_FORMAT('{$row['end_date']}', '%Y-%m')
                    ";
                    mysqli_query($conn, $escalationUpdate);

                    // Build transactional rows
                    $insertValues = [];
                    $paymentDueDay = (int)date('d', strtotime($row['payment_due_date']));
                    $startDate   = strtotime($row['start_date']);
                    $endDateUnix = strtotime($row['end_date']);
                    $firstDay    = $paymentDueDay;
                    $currentDate = $startDate;

                    for ($i = 1; $i <= 12; $i++) {
                        $year  = date('Y', $currentDate);
                        $month = date('m', $currentDate);
                        $lastDayOfMonth = date('t', $currentDate);

                        if (date('Y-m', $currentDate) == date('Y-m', $endDateUnix)) {
                            $endYear  = date('Y', $endDateUnix);
                            $endMonth = date('m', $endDateUnix);
                            $endLastDayOfMonth = date('t', $endDateUnix);

                            $reusedDay = min($firstDay, $endLastDayOfMonth);
                            $transactionDate = "$endYear-$endMonth-" . str_pad($reusedDay, 2, "0", STR_PAD_LEFT);
                        } else {
                            $dueDay = min($paymentDueDay, $lastDayOfMonth);
                            $transactionDate = "$year-$month-" . str_pad($dueDay, 2, "0", STR_PAD_LEFT);
                        }

                        // Advance rental check
                        $advanceRentalAmount = 0;
                        $advanceTag = "NULL";

                        if (!empty($row['advanceRental_from']) && !empty($row['advanceRental_to'])) {
                            $advanceFrom = date('Y-m', strtotime($row['advanceRental_from']));
                            $advanceTo   = date('Y-m', strtotime($row['advanceRental_to']));
                            $currentYM   = date('Y-m', strtotime($transactionDate));

                            if ($currentYM >= $advanceFrom && $currentYM <= $advanceTo) {
                                $advanceTag = "'Advance'";
                                $advanceRentalAmount = (float)$row['advanceRental_amount'];
                            }
                        }

                        $insertValues[] = "(
                           '" . mysqli_real_escape_string($conn, $transactionDate) . "',
                        '" . mysqli_real_escape_string($conn, $row['kpx_code']) . "',
                        '5360001',
                        '" . mysqli_real_escape_string($conn, $row['contract_number']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mainzone']) . "',
                        '" . mysqli_real_escape_string($conn, $row['zone']) . "',
                        '" . mysqli_real_escape_string($conn, $row['region']) . "',
                        '" . mysqli_real_escape_string($conn, $row['area']) . "',
                        '" . mysqli_real_escape_string($conn, $row['branch_code']) . "',
                        '" . mysqli_real_escape_string($conn, $row['branch_id']) . "',
                        '" . mysqli_real_escape_string($conn, $row['branch']) . "',
                        '" . mysqli_real_escape_string($conn, $row['contract_start']) . "',
                        '" . mysqli_real_escape_string($conn, $row['contract_end']) . "',
                        '" . mysqli_real_escape_string($conn, $row['start_date']) . "',
                        '" . mysqli_real_escape_string($conn, $row['end_date']) . "',
                        '" . mysqli_real_escape_string($conn, $row['payment_due_date']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l1_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l1_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l1_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l1_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l2_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l2_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l2_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l2_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l3_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l3_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l3_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l3_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l4_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l4_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l4_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l4_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l5_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l5_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l5_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['l5_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['lessor_type']) . "',
                        '" . mysqli_real_escape_string($conn, $row['corporate_name']) . "',
                        '" . mysqli_real_escape_string($conn, $row['rdo']) . "',
                        " . (float)$row['monthly_rental'] . ",
                        '" . mysqli_real_escape_string($conn, $row['vat_type']) . "',
                        '',
                        " . (float)$row['net_of_vat'] . ",
                        " . (float)$row['vat'] . ",
                        " . (float)$row['wtax'] . ",
                        " . (float)$row['total_month_rental'] . ",
                        " . (float)$row['amount_to_lessor'] . ",
                        " . (float)$row['amount_to_lessor'] . ",
                        'Adjustment',
                        '" . mysqli_real_escape_string($conn, $row['mode_of_payment']) . "',
                        '" . mysqli_real_escape_string($conn, $row['wallet_number']) . "',
                        'Unpaid',
                        " . (float)$advanceRentalAmount . ",
                        " . (int)$advanceTag . ",
                        '" . mysqli_real_escape_string($conn, $row['authorize_firstname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['authorize_middlename']) . "',
                        '" . mysqli_real_escape_string($conn, $row['authorize_lastname']) . "',
                        '" . mysqli_real_escape_string($conn, $row['authorize_gender']) . "',
                        '" . mysqli_real_escape_string($conn, $row['authorize_mobileNumber']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mobile_number_l1']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mobile_number_l2']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mobile_number_l3']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mobile_number_l4']) . "',
                        '" . mysqli_real_escape_string($conn, $row['mobile_number_l5']) . "'                       
                    )";

                        if (date('Y-m', $currentDate) == date('Y-m', $endDateUnix)) {
                            break;
                        }
                        $currentDate = strtotime("+1 month", $currentDate);
                    }

                    if (!empty($insertValues)) {
                        $insertQuery = "
                        INSERT INTO transactional (
                            transaction_date, kpx_code, gl_code, contract_number, mainzone, zone, region, area, 
                            branch_code, branch_id, branch, contract_start, contract_end, start_date, end_date, 
                            payment_due_date, l1_firstname, l1_middlename, l1_lastname, l1_gender, 
                            l2_firstname, l2_middlename, l2_lastname, l2_gender, 
                            l3_firstname, l3_middlename, l3_lastname, l3_gender, 
                            l4_firstname, l4_middlename, l4_lastname, l4_gender, 
                            l5_firstname, l5_middlename, l5_lastname, l5_gender, 
                            lessor_type, corporate_name, rdo, amount, vat_type, 
                            inputted_amount, net_of_vat, vat_amount, wtax, total_month_rental, 
                            amount_lessor, edit_amount_lessor, category, mode_of_payment, 
                            wallet_number, status, advance_rental_amount, advance_tag, 
                            authorize_firstname, authorize_middlename, authorize_lastname, 
                            authorize_gender, authorize_mobileNumber, 
                            mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5
                        ) VALUES " . implode(", ", $insertValues) . "
                        ON DUPLICATE KEY UPDATE status = 'Unpaid'
                    ";
                        mysqli_query($conn, $insertQuery);
                    }

                    mysqli_commit($conn);
                    $_SESSION['success_message'] = "Contract approved and transactions generated successfully.";

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $_SESSION['error_message'] = "Error approving contract: " . $e->getMessage();
                }
            }

            // Case 2: rfp_status empty + request_status = Created
            if ((empty($rfp_status) || is_null($rfp_status)) && $request_status === 'Created') {
                $updateQuery = "UPDATE create_contract SET request_status = 'Prepared' WHERE id = $contractId";
            }

            // Run update if applicable (only for non-final approval cases)
            if (!empty($updateQuery)) {
                $updateResult = mysqli_query($conn, $updateQuery);

                if ($updateResult) {
                    $_SESSION['success_message'] = "The contract request has been successfully sent.";
                } else {
                    $_SESSION['error_message'] = "An error occurred while updating the contract.";
                }
            }
        } else {
            $_SESSION['error_message'] = "Contract not found.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid contract ID.";
    }

    // Redirect back to the user page
    echo '<script>window.location.href="user_page.php";</script>';
    exit;
}
?>
