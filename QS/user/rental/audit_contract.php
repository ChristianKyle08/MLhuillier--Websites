<?php
    session_start();
    include ('../../config/config.php');
    
    if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }
        echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
        echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
        echo '<script src="../../jquery-3.7.1.js"></script>';

        if (isset($_POST['bulkApprove'])) {
            $approved_by = $_SESSION['user_name'];
        
            // Check if 'selectedID' and 'approved_by' are provided
            if (!isset($_POST['selectedID']) || empty($_POST['selectedID'])) {
                echo "Error: Missing or invalid selected_id.";
                exit();
            }
            if (!isset($approved_by) || empty($approved_by)) {
                echo "Error: Missing or invalid approved_by.";
                exit();
            }
        
            // Escape input values
            $selected_ids = array_map('trim', explode(',', $_POST['selectedID'])); // Split and trim IDs
        
            foreach ($selected_ids as $selected_id) {
                $selected_id = mysqli_real_escape_string($conn, $selected_id);
        
                // Fetch contract details
                $fetchContractQuery = "SELECT 
                    cc.*, 
                    e.monthly_rental, 
                    e.vat, 
                    e.net_of_vat, 
                    e.wtax, 
                    e.amount_to_lessor 
                FROM create_contract cc
                LEFT JOIN escalation e 
                    ON cc.contract_number = e.col_number 
                    AND cc.end_date = e.end_date
                WHERE cc.id = ?
                LIMIT 1";
                $fetchContractStmt = mysqli_prepare($conn, $fetchContractQuery);
                if (!$fetchContractStmt) {
                    die('Error: Failed to prepare fetch contract statement: ' . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($fetchContractStmt, "s", $selected_id);
                mysqli_stmt_execute($fetchContractStmt);
                $contractResult = mysqli_stmt_get_result($fetchContractStmt);
        
                if ($contractResult && $contractRow = mysqli_fetch_assoc($contractResult)) {
                    // Check if there are unpaid transactional records for the same branch and contract period
                    $startDate = $contractRow['start_date'];
                    $endDate = $contractRow['end_date'];
                    $contract_number = $contractRow['contract_number'];
        
                    $updateEscalationQuery = "UPDATE escalation 
                              SET status = 'Approved' 
                              WHERE col_number = ? 
                              AND start_date = ? 
                              AND end_date = ?";
                    $updateEscalationStmt = mysqli_prepare($conn, $updateEscalationQuery);
                    if (!$updateEscalationStmt) {
                        die('Error: Failed to prepare escalation update: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($updateEscalationStmt, "sss", $contract_number, $startDate, $endDate);
                    mysqli_stmt_execute($updateEscalationStmt);
                    
                    // ✅ Continue with updating create_contract status and the rest of your logic
                    $updateQuery = "UPDATE create_contract SET request_status = 'Approved', approved_by = ? WHERE id = ? AND request_status = 'Checked'";
                    $updateStmt = mysqli_prepare($conn, $updateQuery);
                    if (!$updateStmt) {
                        die('Error: Failed to prepare update statement: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($updateStmt, "ss", $approved_by, $selected_id);
                    $updateResult = mysqli_stmt_execute($updateStmt);
                    if ($updateResult) {
                            // Prepare to insert multiple records into the transactional table
                            $insertQuery = "INSERT INTO transactional (
                                transaction_date, kpx_code, gl_code, contract_number, mainzone, zone, region, area, 
                                branch_code, branch_id, branch, contract_start, contract_end, start_date, end_date, 
                                payment_due_date, l1_firstname, l1_middlename, l1_lastname, l1_gender, 
                                l2_firstname, l2_middlename, l2_lastname, l2_gender, l3_firstname, 
                                l3_middlename, l3_lastname, l3_gender, l4_firstname, l4_middlename, 
                                l4_lastname, l4_gender, l5_firstname, l5_middlename, l5_lastname, 
                                l5_gender, lessor_type, corporate_name, rdo, amount, vat_type, 
                                inputted_amount, net_of_vat, vat_amount, wtax, total_month_rental, 
                                amount_lessor, edit_amount_lessor, category, mode_of_payment, 
                                wallet_number, status, advance_rental_amount, advance_tag, authorize_firstname, authorize_middlename, 
                                authorize_lastname, authorize_gender, authorize_mobileNumber, 
                                mobile_number_l1, mobile_number_l2, mobile_number_l3, 
                                mobile_number_l4, mobile_number_l5
                            ) VALUES ";
                    
                    $values = [];
                    $currentDate = strtotime($contractRow['start_date']);
                    $endDate = strtotime($contractRow['end_date']);
                    $paymentDueDay = date('d', strtotime($contractRow['payment_due_date']));
                    
                    $monthCount = 0;
                    
                    while (true) { // Loop until we reach the end_date's month
                        $currentMonth = date('m', $currentDate);
                        $currentYear = date('Y', $currentDate);
                    
                        // Get the last day of the current month
                        $lastDayOfMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
                    
                       // Set transaction date based on payment due day or last day of the month
                        $transactionDate = ($paymentDueDay > $lastDayOfMonth)
                        ? "$currentYear-$currentMonth-$lastDayOfMonth"
                        : "$currentYear-$currentMonth-$paymentDueDay";

                        // If next month would go past end date, adjust this to be the final transaction
                        if (strtotime('+1 month', $currentDate) > $endDate) {
                        $finalMonth = date('m', $endDate);
                        $finalYear = date('Y', $endDate);
                        $lastDayFinalMonth = date('t', mktime(0, 0, 0, $finalMonth, 1, $finalYear));
                        $finalDay = ($paymentDueDay > $lastDayFinalMonth) ? $lastDayFinalMonth : $paymentDueDay;

                        $transactionDate = sprintf("%04d-%02d-%02d", $finalYear, $finalMonth, $finalDay);
                        }

                        // Ensure no duplicate 'Paid' records exist for the same contract
                        $checkPaidQuery = "SELECT COUNT(*) AS count FROM transactional WHERE branch_id = ? AND transaction_date = ? AND status = 'Paid' AND contract_number = ?";
                        $checkPaidStmt = mysqli_prepare($conn, $checkPaidQuery);
                        if (!$checkPaidStmt) {
                            die('Error: Failed to prepare check paid statement: ' . mysqli_error($conn));
                        }
                        mysqli_stmt_bind_param($checkPaidStmt, "sss", $branchId, $transactionDate, $contractRow['contract_number']);
                        mysqli_stmt_execute($checkPaidStmt);
                        $paidResult = mysqli_stmt_get_result($checkPaidStmt);
                        $paidData = mysqli_fetch_assoc($paidResult);
                    
                        if ($paidData['count'] == 0) {
                            $advanceTag = NULL; // Default no tag
                            $advanceRentalAmount = 0;
                            // Check and tag Advance rental months
                            if (!empty($contractRow['advanceRental_from']) && !empty($contractRow['advanceRental_to'])) {
                                $advanceFrom = date('Y-m', strtotime($contractRow['advanceRental_from']));
                                $advanceTo = date('Y-m', strtotime($contractRow['advanceRental_to']));
                                $currentYM = date('Y-m', strtotime($transactionDate));
                        
                                if ($currentYM >= $advanceFrom && $currentYM <= $advanceTo) {
                                    $advanceTag = 'Advance';
                                    $advanceRentalAmount = $contractRow['advanceRental_amount'];
                                }
                            }
                        
                            $values[] = "(
                                '$transactionDate', '" . $contractRow['kpx_code'] . "', '5360001', '" . $contractRow['contract_number'] . "', 
                                '" . $contractRow['mainzone'] . "', '" . $contractRow['zone'] . "', '" . $contractRow['region'] . "', 
                                '" . $contractRow['area'] . "', '" . $contractRow['branch_code'] . "', '" . $contractRow['branch_id'] . "', 
                                '" . $contractRow['branch'] . "', '" . $contractRow['contract_start'] . "', '" . $contractRow['contract_end'] . "', 
                                '" . $contractRow['start_date'] . "', '" . $contractRow['end_date'] . "', 
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
                                '" . $contractRow['corporate_name'] . "', '" . $contractRow['rdo'] . "', '" . $contractRow['monthly_rental'] . "', 
                                '" . $contractRow['vat_type'] . "', '" . $contractRow['inputted_amount'] . "', '" . $contractRow['net_of_vat'] . "', '" . $contractRow['vat'] . "', 
                                '" . $contractRow['wtax'] . "', '" . $contractRow['total_month_rental'] . "', 
                                '" . $contractRow['amount_to_lessor'] . "', '" . $contractRow['amount_to_lessor'] . "', 
                                'Adjustment', '" . $contractRow['mode_of_payment'] . "', '" . $contractRow['wallet_number'] . "', 
                                'Unpaid', '$advanceRentalAmount', '$advanceTag', '" . $contractRow['authorize_firstname'] . "', '" . $contractRow['authorize_middlename'] . "', 
                                '" . $contractRow['authorize_lastname'] . "', '" . $contractRow['authorize_gender'] . "', 
                                '" . $contractRow['authorize_mobileNumber'] . "', '" . $contractRow['mobile_number_l1'] . "', 
                                '" . $contractRow['mobile_number_l2'] . "', '" . $contractRow['mobile_number_l3'] . "', 
                                '" . $contractRow['mobile_number_l4'] . "', '" . $contractRow['mobile_number_l5'] . "'
                            )";
                        }
                    
                            // Stop when we reach or exceed end_date
                            if (strtotime($transactionDate) >= $endDate) {
                                break;
                            }

                            // Move to the next month
                            $currentDate = strtotime('+1 month', $currentDate);

                            }
        
                            if (!empty($values)) {
                                $insertQuery .= implode(", ", $values);
                                $insertResult = mysqli_query($conn, $insertQuery);
                                if ($insertResult) {
                                    $updateEscalationStatusQuery = "UPDATE escalation 
                                        SET status = 'Approved' 
                                        WHERE col_number = ? 
                                        AND end_date = ?";

                                    $updateStmt = mysqli_prepare($conn, $updateEscalationStatusQuery);
                                    if (!$updateStmt) {
                                        die('Error: Failed to prepare escalation status update: ' . mysqli_error($conn));
                                    }

                                    $contractNumber = $contractRow['contract_number'];
                                    $escalationEndDate = $contractRow['end_date'];

                                    mysqli_stmt_bind_param($updateStmt, "ss", $contractNumber, $escalationEndDate);
                                    mysqli_stmt_execute($updateStmt);
                                    echo "<script>
                                        window.onload = function() {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Success',
                                                text: 'Contracts approved successfully.',
                                                confirmButtonText: 'OK'
                                            }).then(() => {
                                                window.location = 'audit_contract.php'; // Redirect or refresh the page
                                            });
                                        };
                                    </script>";
                                } else {
                                    echo "<script>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Failed to update record with ID $selected_id.',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            window.location = 'audit_contract.php'; // Redirect or refresh the page
                                        });
                                    </script>";
                                }
                            }
                        } else {
                            echo "<script>
                            window.onload = function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to update record with ID $selected_id.',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location = 'audit_contract.php'; // Redirect or refresh the page
                                });
                            };
                          </script>";
                        }
                } else {
                    echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No contract found with ID $selected_id.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location = 'audit_contract.php'; // Redirect or refresh the page
                        });
                    };
                </script>";
                }
                mysqli_stmt_close($fetchContractStmt);
            }
        
        }
?>       
     
<script>
    function fetchContractDetails(selected_id) {
        // Fetch contract details based on the selected_id
        $.ajax({
            type: 'POST',
            url: 'fetch_contract_details.php',
            data: {
                selected_id: selected_id
            },
            dataType: 'json',
            success: function(contractDetails) {
                // Display the contract details in the modal using SweetAlert2
                Swal.fire({
                    title: 'Remarks',
                    html: '<form id="auditRemarksForm" method="POST" >' +
                          '<textarea id="auditRemarks" name="auditRemarks" class="swal-input" style="border: 1px solid #ccc; padding: 10px 15px; width: 100%; height: 150px; border-radius: 10px; resize: none;" placeholder="Enter reason here..." required></textarea>' +
                          '</form>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Return to Creator',
                    cancelButtonText: 'Close',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        let auditRemarks = document.getElementById('auditRemarks').value;
                        // User confirmed, proceed with update
                        $.ajax({
                            type: 'POST',
                            url: 'disapprove_request_status.php',
                            data: {
                                selected_id: selected_id,
                                disapproved_by: '<?php echo $_SESSION['user_name']; ?>',
                                auditRemarks: auditRemarks
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Success!',
                                        html: response.message,
                                        icon: 'success',
                                        showConfirmButton: true,
                                        allowOutsideClick: false
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // Reload the page if the user clicks "OK"
                                            window.location.href = 'audit_contract.php';
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message,
                                        icon: 'error',
                                        showConfirmButton: true,
                                        allowOutsideClick: false
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to update contract. Please try again.',
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        });
                    }
                });
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to fetch contract details. Please try again.',
                    icon: 'error',
                    showConfirmButton: true,
                    allowOutsideClick: false
                });
            }
        });
    }
</script>

    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental - For Approval RFP</title>
  <!-- ✅ Load Google Font before your custom CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- ✅ Load Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Load Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- ✅ Your custom CSS should come AFTER font import -->
  <link rel="stylesheet" href="../../css/sidebar.css?v=<?php echo time(); ?>">
        </head>
    <body>
    <?php include ('navbar.php'); ?>
<form action="" method="POST">
<div class="prepared_container">
     <div class="container">
		<div class="row justify-content-center">
			<div class="col-12 content-head">
				<div class="mbr-section-head mb-5">
					<h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
						<center><strong style="font-size:35px;">REQUEST FOR PAYMENT</strong></center>
					</h3>
				</div>
			</div>
		</div>
	</div>
    <div class="table_wrap">
        <table class="prepared_table" id="prepared_table">
            <thead>
                <tr>
                <?php 
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);
                
                        $requestStatus = 'Created';

                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);
                            if ($userRow['roles'] == 'HO') {
                        ?>
                        <?php }else{?>
                        <th style="display:none;">KPX CODE</th>
                        <?php
                        $userName = $_SESSION['user_email'];
                        $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                        $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                        if (mysqli_num_rows($rolesStmtResult) > 0) {
                            $row = mysqli_fetch_assoc($rolesStmtResult);

                            if ($row['roles'] == 'Vpo-Approver') {
                                echo "<th><input type='checkbox' name='selectAll' id='selectAll' onclick='toggleSelectAll(this)'></th>";
                            }
                        }
                        ?>
                        
                        <th>CONTRACT NUMBER</th>
                        <th>CREATED DATE</th>
                        <th>RDO NUMBER</th>
                        <th>BRANCH ID</th>
                        <th>BRANCH</th>
                        <th>REGION</th>
                        <th>AREA</th>
                        <th>AUTHORIZE TO CLAIM</th>
                        <th>LESSOR TYPE</th>
                        <th>LESSOR NAME</th>
                        <th>LEASE TERM</th>
                        <th>RFP PERIOD</th>
                        <th>MONTHLY DUE DATE</th>
                         <th>VAT TYPE</th>
                        <th>GROSS RENTAL</th>
                        <!-- <th>NET OF VAT</th> -->
                        <th>VAT AMOUNT</th>
                        <th>WTAX</th>
                        <th>AMOUNT LESSOR</th>
                        <!-- <th>TOTAL MONTHLY EXPENSE</th> -->
                        <th>MODE OF PAYMENT</th>
                        <th>NOTARIZED</th>
                    <!-- <th>REQUEST STATUS</th> -->
                    <th>REVIEWED BY</th>
                   <?php
                        $userName = $_SESSION['user_email'];
                        $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                    $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                    if (mysqli_num_rows($rolesStmtResult) > 0) {
                        $row = mysqli_fetch_assoc($rolesStmtResult);

                        if ($row['roles'] == 'Vpo-Approver') {
                            echo "<th colspan='2'>ACTION</th>";
                        }
                    }
                }
            }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                // Query to get the user's roles, region, area, and mainzone from the database
                $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
                $userResult = mysqli_query($conn, $userQuery);
        
                // Specify the request_status value you want to retrieve
                $requestStatus = 'Checked';
                $rfpStatus = 'Reviewed';
        
                // Check if the query returned any rows
                if (mysqli_num_rows($userResult) > 0) {
                    $userRow = mysqli_fetch_assoc($userResult);
                    if ($userRow['roles'] == 'HO') {
                        // Prepare and execute the SQL query
                        $selectQuery = "SELECT * FROM create_contract WHERE request_status = ? AND rfp_status = ?";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "ss", $requestStatus, $rfpStatus);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                    
                        $visminRows = '';
                        $lncrRows = '';
                    
                        // Initialize counters for VISMIN and LNCR
                        $visminCount = 0;
                        $lncrCount = 0;
                    
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            // Start building the row HTML
                            $rowHtml = '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                            $rowHtml .= '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                            $rowHtml .= '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['created_date']) ? 'N/A' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            $rowHtml .= '<td>' . 
                            (empty($row['contract_start']) ? 'N/A' : htmlspecialchars(date('F d, Y', strtotime($row['contract_start'])))) . 
                            ' to ' . 
                            (empty($row['contract_end']) ? 'N/A' : htmlspecialchars(date('F d, Y', strtotime($row['contract_end'])))) . 
                            '</td>';
                            $rowHtml .= '<td>' . 
                            (empty($row['start_date']) ? 'N/A' : htmlspecialchars(date('F Y', strtotime($row['start_date'])))) . 
                            ' to ' . 
                            (empty($row['end_date']) ? 'N/A' : htmlspecialchars(date('F Y', strtotime($row['end_date'])))) . 
                            '</td>';
                            $rowHtml .= '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                            $rowHtml .= '</tr>';
                    
                            // Separate rows based on 'mainzone' and increment counters
                            if ($row['mainzone'] == 'VISMIN') {
                                $visminRows .= $rowHtml;
                                $visminCount++; // Increment VISMIN counter
                            } elseif ($row['mainzone'] == 'LNCR') {
                                $lncrRows .= $rowHtml;
                                $lncrCount++; // Increment LNCR counter
                            }
                        }
                    
                        // Calculate the grand total of all transactions
                        $grandTotal = $visminCount + $lncrCount;
                    
                        // Output Grand Total
                        echo '<h3 id="total_transactions">Total For Approval: ' . $grandTotal . '</h3>';
                    
                        // Output VISMIN table and count
                        echo '<h3 id="vismin_count">VISMIN (' . $visminCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Created Date</th><th>Lease Term</th><th>Request For Payment</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $visminRows . '</tbody>';
                        echo '</table>';
                    
                        // Output LNCR table and count
                        echo '<h3 id="lncr_count">LNCR (' . $lncrCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Created Date</th><th>Lease Term</th><th>Request For Payment</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $lncrRows . '</tbody>';
                        echo '</table>';
                    }
                    elseif ($userRow['roles'] == 'Am-Creator') {
                        $userRegion = $userRow['region'];
                        $userArea = $userRow['area'];
                        $selectQuery = "
                        SELECT 
                            cc.*, 
                            e.monthly_rental, 
                            e.vat AS e_vat, 
                            e.net_of_vat, 
                            e.wtax AS e_wtax, 
                            e.amount_to_lessor 
                        FROM create_contract cc
                        LEFT JOIN escalation e ON cc.contract_number = e.col_number AND cc.end_date = e.end_date
                        WHERE cc.request_status = ? AND cc.rfp_status = ? AND cc.region = ? AND cc.area = ?
                        ";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "ssss", $requestStatus, $rfpStatus, $userRegion, $userArea);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                    echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                    echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                    echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                    echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                    echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                    echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                    echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                    echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                    echo '<td style="background-color: #d5fab7">';
  
                            $authorizeFirstname = !empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '';
                            $authorizeMiddlename = !empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '';
                            $authorizeLastname = !empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '';
                            $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                            $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
                            
                            // Combine names if at least one is provided, otherwise use corporate lessor
                            $displayName = trim("$authorizeFirstname $authorizeMiddlename $authorizeLastname");
                            
                            echo !empty($displayName) ? $displayName : '';
                            
                            echo '</td>';
                            echo '<td>' . (empty($row['lessor_type']) ? '' : ($row['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($row['lessor_type']))) . '</td>';
                            
                             // Process L1 Name
                             $l1Firstname = !empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '';
                             $l1Middlename = !empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '';
                             $l1Lastname = !empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '';
                             $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                             $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
        
                             // Combine L1 names if available, otherwise use corporate lessor
                             $l1DisplayName = trim("$l1Firstname $l1Middlename $l1Lastname");
        
                             echo '<td>' . (!empty($l1DisplayName) ? $l1DisplayName : $corporateLessor) . '</td>';
        
                             // Hidden L1 fields
                             echo '<td style="display:none;">' . $l1Firstname . '</td>';
                             echo '<td style="display:none;">' . $l1Middlename . '</td>';
                             echo '<td style="display:none;">' . $l1Lastname . '</td>';
                             echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
        
                             // Process L2 Name
                             $l2Firstname = !empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '';
                             $l2Middlename = !empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '';
                             $l2Lastname = !empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '';
        
                             // Combine L2 names if available, otherwise use corporate lessor
                             $l2DisplayName = trim("$l2Firstname $l2Middlename $l2Lastname");
        
                             echo '<td style="display:none;">' . (!empty($l2DisplayName) ? $l2DisplayName : $corporateLessor) . '</td>';  
                             echo '<td>';
                             $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                                 : '';
 
                             $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                                 : '';
 
                             if ($contractStart && $contractEnd) {
                                 echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                             } elseif ($contractStart) {
                                 echo htmlspecialchars($contractStart);
                             } elseif ($contractEnd) {
                                 echo htmlspecialchars($contractEnd);
                             }
                             echo '</td>';
 
                             echo '<td>';
                             $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['start_date'])) 
                                 : '';
 
                             $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['end_date'])) 
                                 : '';
 
                             if ($startDate && $endDate) {
                                 echo htmlspecialchars($startDate . ' to ' . $endDate);
                             } elseif ($startDate) {
                                 echo htmlspecialchars($startDate);
                             } elseif ($endDate) {
                                 echo htmlspecialchars($endDate);
                             }
                             echo '</td>';
                             echo '<td>';
                                if (!empty($row['payment_due_date'])) {
                                    $day = (int)date('j', strtotime($row['payment_due_date']));
                                    // Correct suffix logic
                                    if ($day >= 11 && $day <= 13) {
                                        $suffix = 'th';
                                    } else {
                                        switch ($day % 10) {
                                            case 1: $suffix = 'st'; break;
                                            case 2: $suffix = 'nd'; break;
                                            case 3: $suffix = 'rd'; break;
                                            default: $suffix = 'th'; break;
                                        }
                                    }
                                    echo "Every " . $day . $suffix . " day of the month";
                                } else {
                                    echo ''; // Empty cell if no date
                                }
                            echo '</td>';

                    echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                    echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';
   
                    // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['reviewed_by']) . '</td>';
                    $userName = $_SESSION['user_email'];
               
                    $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                    $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                    if (mysqli_num_rows($rolesStmtResult) > 0) {
                        $row = mysqli_fetch_assoc($rolesStmtResult);

                        if ($row['roles'] == 'Vpo-Approver') {
                          
                            echo "<td><button type='submit' name='audit_contract' id='audit_contract'><i class='fa-solid fa-check'></i></button></td>";
                            echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                        }
                    } 

                    echo '</tr>';
                    }
                }elseif ($userRow['roles'] == 'Rm-Reviewer') {
                        $userRegion = $userRow['region'];
                       
                        $selectQuery = "
                        SELECT 
                        cc.*, 
                        e.monthly_rental, 
                        e.vat AS e_vat, 
                        e.net_of_vat, 
                        e.wtax AS e_wtax, 
                        e.amount_to_lessor
                        FROM create_contract cc
                        LEFT JOIN escalation e ON cc.contract_number = e.col_number AND cc.end_date = e.end_date
                        WHERE cc.request_status = ? AND cc.rfp_status = ? AND cc.region = ?
                        ";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "sss", $requestStatus, $rfpStatus, $userRegion);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                    echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                    echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                    echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                    echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                    echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                    echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                    echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                    echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                    echo '<td style="background-color: #d5fab7">';
  
                    $authorizeFirstname = !empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '';
                    $authorizeMiddlename = !empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '';
                    $authorizeLastname = !empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '';
                    $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                    $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
                    
                    // Combine names if at least one is provided, otherwise use corporate lessor
                    $displayName = trim("$authorizeFirstname $authorizeMiddlename $authorizeLastname");
                    
                    echo !empty($displayName) ? $displayName : '';
                    
                    echo '</td>';
                    echo '<td>' . (empty($row['lessor_type']) ? '' : ($row['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($row['lessor_type']))) . '</td>';
                    
                     // Process L1 Name
                     $l1Firstname = !empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '';
                     $l1Middlename = !empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '';
                     $l1Lastname = !empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '';
                     $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                     $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';

                     // Combine L1 names if available, otherwise use corporate lessor
                     $l1DisplayName = trim("$l1Firstname $l1Middlename $l1Lastname");

                     echo '<td>' . (!empty($l1DisplayName) ? $l1DisplayName : $corporateLessor) . '</td>';

                     // Hidden L1 fields
                     echo '<td style="display:none;">' . $l1Firstname . '</td>';
                     echo '<td style="display:none;">' . $l1Middlename . '</td>';
                     echo '<td style="display:none;">' . $l1Lastname . '</td>';
                     echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';

                     // Process L2 Name
                     $l2Firstname = !empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '';
                     $l2Middlename = !empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '';
                     $l2Lastname = !empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '';

                     // Combine L2 names if available, otherwise use corporate lessor
                     $l2DisplayName = trim("$l2Firstname $l2Middlename $l2Lastname");

                     echo '<td style="display:none;">' . (!empty($l2DisplayName) ? $l2DisplayName : $corporateLessor) . '</td>';  
                     echo '<td>';
                             $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                                 : '';
 
                             $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                                 : '';
 
                             if ($contractStart && $contractEnd) {
                                 echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                             } elseif ($contractStart) {
                                 echo htmlspecialchars($contractStart);
                             } elseif ($contractEnd) {
                                 echo htmlspecialchars($contractEnd);
                             }
                             echo '</td>';
 
                             echo '<td>';
                             $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['start_date'])) 
                                 : '';
 
                             $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['end_date'])) 
                                 : '';
 
                             if ($startDate && $endDate) {
                                 echo htmlspecialchars($startDate . ' to ' . $endDate);
                             } elseif ($startDate) {
                                 echo htmlspecialchars($startDate);
                             } elseif ($endDate) {
                                 echo htmlspecialchars($endDate);
                             }
                             echo '</td>';
                            echo '<td>';
                                if (!empty($row['payment_due_date'])) {
                                    $day = (int)date('j', strtotime($row['payment_due_date']));
                                    // Correct suffix logic
                                    if ($day >= 11 && $day <= 13) {
                                        $suffix = 'th';
                                    } else {
                                        switch ($day % 10) {
                                            case 1: $suffix = 'st'; break;
                                            case 2: $suffix = 'nd'; break;
                                            case 3: $suffix = 'rd'; break;
                                            default: $suffix = 'th'; break;
                                        }
                                    }
                                    echo "Every " . $day . $suffix . " day of the month";
                                } else {
                                    echo ''; // Empty cell if no date
                                }
                            echo '</td>';
                    echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                    echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';
               
                    // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['reviewed_by']) . '</td>';
                    $userName = $_SESSION['user_email'];

                    $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                    $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                    if (mysqli_num_rows($rolesStmtResult) > 0) {
                        $row = mysqli_fetch_assoc($rolesStmtResult);

                        if ($row['roles'] == 'Vpo-Approver') {
            
                            echo "<td><button type='submit' name='audit_contract' id='audit_contract'><i class='fa-solid fa-check'></i></button></td>";
                            echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                        }
                    } 

                    echo '</tr>';
                    }
                }elseif ($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer') {
                        $userMainzone = $userRow['mainzone'];
      
                        $selectQuery = "
                        SELECT 
                        cc.*, 
                        e.monthly_rental, 
                        e.vat AS e_vat, 
                        e.net_of_vat, 
                        e.wtax AS e_wtax, 
                        e.amount_to_lessor 
                        FROM create_contract cc
                        LEFT JOIN escalation e ON cc.contract_number = e.col_number AND cc.end_date = e.end_date
                        WHERE cc.request_status = ? AND cc.rfp_status = ? AND cc.mainzone = ?
                        ";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "sss", $requestStatus, $rfpStatus, $userMainzone);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                            echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                            echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                            echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                            echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                            echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                            echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                            echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                            echo '<td style="background-color: #d5fab7">';
  
                            $authorizeFirstname = !empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '';
                            $authorizeMiddlename = !empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '';
                            $authorizeLastname = !empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '';
                            $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                            $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
                            
                            // Combine names if at least one is provided, otherwise use corporate lessor
                            $displayName = trim("$authorizeFirstname $authorizeMiddlename $authorizeLastname");
                            
                            echo !empty($displayName) ? $displayName : '';
                            
                            echo '</td>';
                            echo '<td>' . (empty($row['lessor_type']) ? '' : ($row['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($row['lessor_type']))) . '</td>';
                            
                             // Process L1 Name
                             $l1Firstname = !empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '';
                             $l1Middlename = !empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '';
                             $l1Lastname = !empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '';
                             $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                             $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
        
                             // Combine L1 names if available, otherwise use corporate lessor
                             $l1DisplayName = trim("$l1Firstname $l1Middlename $l1Lastname");
        
                             echo '<td>' . (!empty($l1DisplayName) ? $l1DisplayName : $corporateLessor) . '</td>';
        
                             // Hidden L1 fields
                             echo '<td style="display:none;">' . $l1Firstname . '</td>';
                             echo '<td style="display:none;">' . $l1Middlename . '</td>';
                             echo '<td style="display:none;">' . $l1Lastname . '</td>';
                             echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
        
                             // Process L2 Name
                             $l2Firstname = !empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '';
                             $l2Middlename = !empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '';
                             $l2Lastname = !empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '';
        
                             // Combine L2 names if available, otherwise use corporate lessor
                             $l2DisplayName = trim("$l2Firstname $l2Middlename $l2Lastname");
        
                             echo '<td style="display:none;">' . (!empty($l2DisplayName) ? $l2DisplayName : $corporateLessor) . '</td>';  
                             echo '<td>';
                             $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                                 : '';
 
                             $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                                 : '';
 
                             if ($contractStart && $contractEnd) {
                                 echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                             } elseif ($contractStart) {
                                 echo htmlspecialchars($contractStart);
                             } elseif ($contractEnd) {
                                 echo htmlspecialchars($contractEnd);
                             }
                             echo '</td>';
 
                             echo '<td>';
                             $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['start_date'])) 
                                 : '';
 
                             $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['end_date'])) 
                                 : '';
 
                             if ($startDate && $endDate) {
                                 echo htmlspecialchars($startDate . ' to ' . $endDate);
                             } elseif ($startDate) {
                                 echo htmlspecialchars($startDate);
                             } elseif ($endDate) {
                                 echo htmlspecialchars($endDate);
                             }
                             echo '</td>';
                             echo '<td>';
                                if (!empty($row['payment_due_date'])) {
                                    $day = (int)date('j', strtotime($row['payment_due_date']));
                                    // Correct suffix logic
                                    if ($day >= 11 && $day <= 13) {
                                        $suffix = 'th';
                                    } else {
                                        switch ($day % 10) {
                                            case 1: $suffix = 'st'; break;
                                            case 2: $suffix = 'nd'; break;
                                            case 3: $suffix = 'rd'; break;
                                            default: $suffix = 'th'; break;
                                        }
                                    }
                                    echo "Every " . $day . $suffix . " day of the month";
                                } else {
                                    echo ''; // Empty cell if no date
                                }
                            echo '</td>';
                            echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                            echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                            echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                            echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                            echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                            // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                            echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                            echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                            echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';

                            // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['reviewed_by']) . '</td>';
                            $userName = $_SESSION['user_email'];
                            $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                            $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                            if (mysqli_num_rows($rolesStmtResult) > 0) {
                                $row = mysqli_fetch_assoc($rolesStmtResult);

                                if ($row['roles'] == 'Vpo-Approver') {
                                    echo "<td><button type='submit' name='audit_contract' id='audit_contract'><i class='fa-solid fa-check'></i></button></td>";
                                    echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                                }
                            } 

                            echo '</tr>';
                            }
                        }else if($userRow['roles'] == 'Vpo-Approver'){
                            $userMainzone = $userRow['mainzone'];
      
                            $selectQuery = "
                            SELECT 
                            cc.*, 
                            e.monthly_rental, 
                            e.vat AS e_vat, 
                            e.net_of_vat, 
                            e.wtax AS e_wtax, 
                            e.amount_to_lessor 
                            FROM create_contract cc
                            LEFT JOIN escalation e ON cc.contract_number = e.col_number AND cc.end_date = e.end_date
                            WHERE cc.request_status = ? AND cc.rfp_status = ? AND cc.mainzone = ?
                            ";
                            $stmt = mysqli_prepare($conn, $selectQuery);
                            mysqli_stmt_bind_param($stmt, "sss", $requestStatus, $rfpStatus, $userMainzone);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                                echo '<td><input type="checkbox" name="selectedContracts[]" value="' . htmlspecialchars($row['id']) . '" class="contract-checkbox" onchange="updateSelectedIDs()"></td>';
                                echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                                echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                                echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                                echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                                echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                                echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                                echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                                echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                                echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                                echo '<td style="background-color: #d5fab7">';
  
                            $authorizeFirstname = !empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '';
                            $authorizeMiddlename = !empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '';
                            $authorizeLastname = !empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '';
                            $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                            $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
                            
                            // Combine names if at least one is provided, otherwise use corporate lessor
                            $displayName = trim("$authorizeFirstname $authorizeMiddlename $authorizeLastname");
                            
                            echo !empty($displayName) ? $displayName : '';
                            
                            echo '</td>';
                            echo '<td>' . (empty($row['lessor_type']) ? '' : ($row['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($row['lessor_type']))) . '</td>';
                            
                             // Process L1 Name
                             $l1Firstname = !empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '';
                             $l1Middlename = !empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '';
                             $l1Lastname = !empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '';
                             $authorizeToClaim = !empty($row['authorize_to_claim']) ? htmlspecialchars($row['authorize_to_claim']) : '';
                             $corporateLessor = !empty($row['corporate_lessor']) ? htmlspecialchars($row['corporate_lessor']) : '';
        
                             // Combine L1 names if available, otherwise use corporate lessor
                             $l1DisplayName = trim("$l1Firstname $l1Middlename $l1Lastname");
        
                             echo '<td>' . (!empty($l1DisplayName) ? $l1DisplayName : $corporateLessor) . '</td>';
        
                             // Hidden L1 fields
                             echo '<td style="display:none;">' . $l1Firstname . '</td>';
                             echo '<td style="display:none;">' . $l1Middlename . '</td>';
                             echo '<td style="display:none;">' . $l1Lastname . '</td>';
                             echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
        
                             // Process L2 Name
                             $l2Firstname = !empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '';
                             $l2Middlename = !empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '';
                             $l2Lastname = !empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '';
        
                             // Combine L2 names if available, otherwise use corporate lessor
                             $l2DisplayName = trim("$l2Firstname $l2Middlename $l2Lastname");
        
                             echo '<td style="display:none;">' . (!empty($l2DisplayName) ? $l2DisplayName : $corporateLessor) . '</td>';  
                             echo '<td>';
                             $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                                 : '';
 
                             $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                                 ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                                 : '';
 
                             if ($contractStart && $contractEnd) {
                                 echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                             } elseif ($contractStart) {
                                 echo htmlspecialchars($contractStart);
                             } elseif ($contractEnd) {
                                 echo htmlspecialchars($contractEnd);
                             }
                             echo '</td>';
 
                             echo '<td>';
                             $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['start_date'])) 
                                 : '';
 
                             $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                                 ? date('F Y', strtotime((string) $row['end_date'])) 
                                 : '';
 
                             if ($startDate && $endDate) {
                                 echo htmlspecialchars($startDate . ' to ' . $endDate);
                             } elseif ($startDate) {
                                 echo htmlspecialchars($startDate);
                             } elseif ($endDate) {
                                 echo htmlspecialchars($endDate);
                             }
                             echo '</td>';
                             echo '<td>';
                             if (!empty($row['payment_due_date'])) {
                                 $day = (int)date('j', strtotime($row['payment_due_date']));
                                 // Correct suffix logic
                                 if ($day >= 11 && $day <= 13) {
                                     $suffix = 'th';
                                 } else {
                                     switch ($day % 10) {
                                         case 1: $suffix = 'st'; break;
                                         case 2: $suffix = 'nd'; break;
                                         case 3: $suffix = 'rd'; break;
                                         default: $suffix = 'th'; break;
                                     }
                                 }
                                 echo "Every " . $day . $suffix . " day of the month";
                             } else {
                                 echo ''; // Empty cell if no date
                             }
                         echo '</td>';
                                echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                                echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                                echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                                echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                                echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                                // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                                echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                                echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                                echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';

                                // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['reviewed_by']) . '</td>';
                                echo "<td><button type='button' name='send_creator' id='send_creator' onclick='fetchContractDetails(" . htmlspecialchars($row['id']) . ")'><i class='fa-solid fa-file-pen'></i></button></td>";

                            echo '</tr>';
                            }
                            echo '<button type="submit" name="bulkApprove" id="bulk_approve_button" class="bulk-approve-button" disabled>Approve</button>';
                            echo "<button type='button' name='view_contract' class='view-contract' id='view_contract'>View Details</button>";
                        }
                    }
                    mysqli_close($conn);
                ?>
            </tbody>
        </table>

        <input type="hidden" id="selected_id_display" name="selectedID" value="<?php echo isset($_POST['selectedID']) ? htmlspecialchars($_POST['selectedID'], ENT_QUOTES) : ''; ?>">

        <!-- Modal Structure for Transaction Details -->
        <div id="transactionDetailsModal" class="transaction-modal">
            <div class="transaction-modal-content">
                <span class="transaction-modal-close">&times;</span>
                <h2>Transaction Details</h2>
                <hr>
                <div id="transactionDetails">
                    <!-- Your table or details go here -->
                </div>
            </div>
        </div>

            <div id="fileModal" class="file-modal" style="display:none;">
                <div class="file-modal-content">
                    <span class="file-modal-close">&times;</span>
                    <div id="filePreview"></div>
                </div>
            </div>
        </div>
    </div>
</form>
<!-- Enhanced Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fefefe;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 text-dark">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('logoutLink').addEventListener('click', function (e) {
    e.preventDefault();

    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
        backdrop: 'static',
        keyboard: false
    });

    logoutModal.show();

    // Simulate logout delay
    setTimeout(() => {
        window.location.href = '../../logout.php';
    }, 2500);
});

document.addEventListener('DOMContentLoaded', () => {
    const viewContractButton = document.getElementById('view_contract');
    const transactionDetailsModal = document.getElementById('transactionDetailsModal');
    const modalClose = transactionDetailsModal.querySelector('.transaction-modal-close');
    const transactionDetailsContainer = document.getElementById('transactionDetails');

    viewContractButton.addEventListener('click', () => {
        const selectedID = document.getElementById('selected_id_display').value;

        if (selectedID) {
            fetchTransactionDetails(selectedID);
            transactionDetailsModal.style.display = 'block';
        }
    });

    modalClose.addEventListener('click', () => {
        transactionDetailsModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === transactionDetailsModal) {
            transactionDetailsModal.style.display = 'none';
        }
    });

    function fetchTransactionDetails(id) {
        // Perform AJAX request to fetch transaction details
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_approver_details.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                transactionDetailsContainer.innerHTML = xhr.responseText;
            } else {
                transactionDetailsContainer.innerHTML = 'Error loading details.';
            }
        };
        xhr.send('id=' + encodeURIComponent(id));
    }
});

// Function to update the hidden input field with selected IDs and show/hide the "View" button and "Approve" button
function updateSelectedIDs() {
    const checkboxes = document.querySelectorAll('.contract-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);

    // Update the hidden input field with selected IDs, separated by commas
    document.getElementById('selected_id_display').value = selectedIds.join(',');

    // Enable or disable the "View" button based on the number of selected checkboxes
    const viewContractButton = document.getElementById('view_contract');
    viewContractButton.disabled = selectedIds.length !== 1;

    // Enable or disable the "Approve" button based on whether any checkboxes are selected
    const approveButton = document.getElementById('bulk_approve_button');
    approveButton.disabled = selectedIds.length === 0;
}

// Function to handle "Select All" checkbox
function toggleSelectAll(selectAllCheckbox) {
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });

    // Update selected IDs based on the new state of all checkboxes
    updateSelectedIDs();
}

// Attach event listeners to all contract checkboxes
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedIDs);
    });

    // Attach event listener to the "Select All" checkbox
    const selectAllCheckbox = document.getElementById('select_all_checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            toggleSelectAll(selectAllCheckbox);
        });
    }
});


document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.view-single-file').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const fileContent = event.target.dataset.fileContent;
            const mimeType = event.target.dataset.mimeType;
            const fileName = event.target.dataset.fileName;

            const binaryString = atob(fileContent);

            const uint8Array = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }

            const blob = new Blob([uint8Array], { type: mimeType });

            const blobUrl = URL.createObjectURL(blob);

            window.open(blobUrl, '_blank');
        });
    });

    document.querySelectorAll('.view-contracts').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const files = JSON.parse(event.target.dataset.contractFiles);
            const filePreview = document.getElementById('filePreview');

            filePreview.innerHTML = '';

            files.forEach(function (file) {
                const linkElement = document.createElement('a');
                linkElement.href = '#';
                linkElement.innerHTML = file.icon + ' ' + file.file;
                linkElement.style.display = 'block';
                linkElement.dataset.fileContent = file.content;
                linkElement.dataset.mimeType = file.mimeType;
                linkElement.dataset.fileName = file.file;

                linkElement.addEventListener('click', function (event) {
                    event.preventDefault();
                    const fileContent = event.target.dataset.fileContent;
                    const mimeType = event.target.dataset.mimeType;
                    const fileName = event.target.dataset.fileName;

                    const binaryString = atob(fileContent);

                    const uint8Array = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        uint8Array[i] = binaryString.charCodeAt(i);
                    }

                    const blob = new Blob([uint8Array], { type: mimeType });

                    const blobUrl = URL.createObjectURL(blob);

                    window.open(blobUrl, '_blank');
                });

                filePreview.appendChild(linkElement);
            });

            document.getElementById('fileModal').style.display = 'block';
        });
    });

    document.querySelector('.file-modal-close').addEventListener('click', function () {
        document.getElementById('fileModal').style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        const modal = document.getElementById('fileModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

function highlightRow(row) {
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  row.style.backgroundColor = '#f7f0f0';

  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; 
}
</script>
</body>
</html>
