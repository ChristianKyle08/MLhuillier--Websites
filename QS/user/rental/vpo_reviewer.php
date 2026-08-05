<?php
session_start();
    include '../../config/config.php';
    if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }
    echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
    echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
    echo '<script src="../../jquery-3.7.1.js"></script>';

    if (isset($_POST['review_contract'])) {
        $selected_id = mysqli_real_escape_string($conn, $_POST['selectedID']);
        $reviewed_by = $_SESSION['user_name'];
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch contract details based on selected_id
        $.ajax({
            type: 'POST',
            url: 'fetch_contract_details.php',
            data: {
                selected_id: '<?php echo $selected_id; ?>'
            },
            dataType: 'json',
            success: function(contractDetails) {
                let lessorDetailsHTML = '';
                let lessorDetailsHTML1 = '';
                if (contractDetails.inputted_amount) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Inputted Amount:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.inputted_amount + ' ' +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l1_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l1_firstname + ' ' +
                        (contractDetails.l1_middlename ? contractDetails.l1_middlename : '') + ' ' +
                        (contractDetails.l1_lastname ? contractDetails.l1_lastname : '') +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l2_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">2nd Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l2_firstname + ' ' +
                        (contractDetails.l2_middlename ? contractDetails.l2_middlename : '') + ' ' +
                        (contractDetails.l2_lastname ? contractDetails.l2_lastname : '') +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l3_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">3rd Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l3_firstname + ' ' +
                        (contractDetails.l3_middlename ? contractDetails.l3_middlename : '') + ' ' +
                        (contractDetails.l3_lastname ? contractDetails.l3_lastname : '') +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l4_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">4th Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l4_firstname + ' ' +
                        (contractDetails.l4_middlename ? contractDetails.l4_middlename : '') + ' ' +
                        (contractDetails.l4_lastname ? contractDetails.l4_lastname : '') +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l5_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">5th Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l5_firstname + ' ' +
                        (contractDetails.l5_middlename ? contractDetails.l5_middlename : '') + ' ' +
                        (contractDetails.l5_lastname ? contractDetails.l5_lastname : '') +
                        '</td>' +
                        '</tr>';
                }
        
                if (contractDetails.bank_name) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Bank Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.bank_name + ' ' +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.bank_accNumber) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Bank Account Number:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.bank_accNumber + ' ' +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.created_date) {
                    const createdDate = new Date(contractDetails.created_date);
                    const formattedDate = createdDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit'
                    });

                    lessorDetailsHTML1 += '<tr>' +
                        '<td class="swal_td">Created Date:</td>' +
                        '<td class="swal_td">' + formattedDate + '</td>' +
                        '</tr>';
                }

                function formatMonthYear(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                }
                Swal.fire({
                    title: 'Please review the contract!',
                    html: '<table class="swal_table">' +
                    lessorDetailsHTML1 +
                        '<tr>' +
                        '<td class="swal_td">Contract Number:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.contract_number ? contractDetails.contract_number : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Notarized:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.notarized ? contractDetails.notarized : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">RDO Number:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.rdo ? contractDetails.rdo : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Corporate Name:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.corporate_name ? contractDetails.corporate_name : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Lease Term:</td>' +
                        '<td class="swal_td">' + (contractDetails.contract_start && contractDetails.contract_end ? contractDetails.contract_start + ' to ' + contractDetails.contract_end : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">RFP Period:</td>' +
                        '<td class="swal_td">' + 
                        (contractDetails.start_date && contractDetails.end_date 
                            ? formatMonthYear(contractDetails.start_date) + ' to ' + formatMonthYear(contractDetails.end_date) 
                            : '') + 
                        '</td>' +

                        '</tr>' +
                         '<tr>' +
                        '<td class="swal_td">Monthly Due Date:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.payment_due_date ? contractDetails.payment_due_date : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Region:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.region ? contractDetails.region : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Area:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.area ? contractDetails.area : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Branch:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.branch ? contractDetails.branch : '') + '</td>' +
                        '</tr>' +
                         '<tr>' +
                        '<td class="swal_td">Vat Type:</td>' +
                        '<td class="swal_td">' + (contractDetails.vat_type ? contractDetails.vat_type : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Gross Rental:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.escalated_monthly_rental ? contractDetails.escalated_monthly_rental : '') + '</td>' +
                        '</tr>' +
                        '<tr style="display:none;">' +
                        '<td class="swal_td">Net of Vat:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.escalated_net_of_vat ? contractDetails.escalated_net_of_vat : '') + '</td>' +
                        '</tr>' +
                        '<td class="swal_td">Vat Amount:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.escalated_vat ? contractDetails.escalated_vat : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Withholding Tax:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.escalated_wtax ? contractDetails.escalated_wtax : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Amount to Lessor:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.escalated_amount_to_lessor ? contractDetails.escalated_amount_to_lessor : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Mode of Payment:</td>' +
                        '<td class="swal_td">' + (contractDetails.mode_of_payment ? contractDetails.mode_of_payment : '') + '</td>' +
                        '</tr>' +
                        (contractDetails.mode_of_payment === 'WALLET' ?
                            '<tr>' +
                            '<td class="swal_td">Wallet number:</td>' +
                            '<td class="swal_td">' + (contractDetails.wallet_number ? contractDetails.wallet_number : '') + '</td>' +
                            '</tr>' : ''
                        ) +
                         '</tr>' +
                        '<td class="swal_td">Lessor Type:</td>' +
                        '<td class="swal_td">' + 
                            (contractDetails.lessor_type === "Individual" ? "Sole Proprietorship" : (contractDetails.lessor_type ? contractDetails.lessor_type : '')) + 
                        '</td>' +
                        '</tr>' +
                        lessorDetailsHTML +
                        '<tr>' +
                        '<td class="swal_td">Authorize to claim:</td>' +
                        '<td class="swal_td">' +
                        (
                            contractDetails.authorize_firstname || contractDetails.authorize_middlename || contractDetails.authorize_lastname
                                ? (contractDetails.authorize_firstname ? contractDetails.authorize_firstname : '') + ' ' +
                                (contractDetails.authorize_middlename ? contractDetails.authorize_middlename : '') + ' ' +
                                (contractDetails.authorize_lastname ? contractDetails.authorize_lastname : '')
                                : 'Not Applicable'
                        ) +
                        '</td>' +
                        '</tr>' +
                        '</table>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Close',
                    allowOutsideClick:false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User confirmed, proceed with update
                        $.ajax({
                            type: 'POST',
                            url: 'update_request_status_vporeviewer.php',
                            data: {
                                selected_id: '<?php echo $selected_id; ?>',
                                reviewed_by: '<?php echo $reviewed_by; ?>'
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
                                            window.location.href = 'vpo_reviewer.php';
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
                    } else {
                        // Do nothing if canceled
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
    });
    </script>
    <?php
    }

    if (isset($_POST['send_creator'])) {
        $selected_id = mysqli_real_escape_string($conn, $_POST['selectedID']);
        $disapproved_by = $_SESSION['user_name'];
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch contract details based on selected_id
        $.ajax({
            type: 'POST',
            url: 'fetch_contract_details.php',
            data: {
                selected_id: '<?php echo $selected_id; ?>'
            },
            dataType: 'json',
            success: function(contractDetails) {
                Swal.fire({
                title: 'Remarks',
                html: '<form id="reviewerRemarksForm" method="POST">' +
                        '<textarea id="reviewerRemarks" name="reviewerRemarks" class="swal-input" style="border: 1px solid #ccc; padding: 10px 15px; width: 100%; height: 150px; border-radius: 10px; resize: none;" placeholder="Enter reason here..." required></textarea>' +
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
                        let reviewerRemarks = document.getElementById('reviewerRemarks').value;
                        // User confirmed, proceed with update
                        $.ajax({
                            type: 'POST',
                            url: 'reviewer_disapprove_request_vporeviewer.php',
                            data: {
                                selected_id: '<?php echo $selected_id; ?>',
                                disapproved_by: '<?php echo $disapproved_by; ?>',
                                reviewerRemarks: reviewerRemarks
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
                                            window.location.href = 'vpo_reviewer.php';
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
                    } else {
                        // Do nothing if canceled
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
    });
    </script>
    <?php
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
        <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
        <meta name="description" content="">
        <title>ML Rental - For Review RFP</title>
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

                        // Query to get the user's roles, region, area, and mainzone from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);
                
                        // Specify the request_status value you want to retrieve
                        $requestStatus = 'Created';
                
                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);
                            if ($userRow['roles'] == 'HO') {
                        ?>
                        <?php }else{?>
                     <th style="display:none;">KPX CODE</th>
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
                        <th>CONTRACT FILE</th>
                        <!-- <th>REQUEST STATUS</th> -->
                        <th>REVIEWER NOTE</th>
                        <th>APPROVER NOTE</th>
                       <?php
                        $userName = $_SESSION['user_email'];
                        // Fetch user roles based on user_email
                        $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                        $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                        if (mysqli_num_rows($rolesStmtResult) > 0) {
                            $row = mysqli_fetch_assoc($rolesStmtResult);

                            if ($row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Vpo-Checker') {
                                // 'REVIEWER' role found for the user
                                echo "<th colspan='2'>ACTION</th>";
                            }
                        } else {
                            // No 'REVIEWER' role found or error occurred
                            // Handle the case if needed
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
                $requestStatus = 'Received';
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
                            $rowHtml .= '<td style="display:none;">' . (empty($row['id']) ? '' : htmlspecialchars($row['id'])) . '</td>';
                            $rowHtml .= '<td style="display:none;">' . (empty($row['kpx_code']) ? '' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['contract_number']) ? '' : htmlspecialchars($row['contract_number'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            // Contract Start & End
                            $contractStart = !empty($row['contract_start']) && $row['contract_start'] !== '0000-00-00'
                            ? htmlspecialchars(date('F d, Y', strtotime($row['contract_start'])))
                            : '';

                            $contractEnd = !empty($row['contract_end']) && $row['contract_end'] !== '0000-00-00'
                            ? htmlspecialchars(date('F d, Y', strtotime($row['contract_end'])))
                            : '';

                            $contractPeriod = '';
                            if ($contractStart && $contractEnd) {
                            $contractPeriod = $contractStart . ' to ' . $contractEnd;
                            } elseif ($contractStart) {
                            $contractPeriod = $contractStart;
                            } elseif ($contractEnd) {
                            $contractPeriod = $contractEnd;
                            }
                            $rowHtml .= '<td>' . $contractPeriod . '</td>';

                            // Start Date & End Date
                            $startDate = !empty($row['start_date']) && $row['start_date'] !== '0000-00-00'
                            ? htmlspecialchars(date('F Y', strtotime($row['start_date'])))
                            : '';

                            $endDate = !empty($row['end_date']) && $row['end_date'] !== '0000-00-00'
                            ? htmlspecialchars(date('F Y', strtotime($row['end_date'])))
                            : '';

                            $period = '';
                            if ($startDate && $endDate) {
                            $period = $startDate . ' to ' . $endDate;
                            } elseif ($startDate) {
                            $period = $startDate;
                            } elseif ($endDate) {
                            $period = $endDate;
                            }
                            $rowHtml .= '<td>' . $period . '</td>';
                            $rowHtml .= '<td>' . (empty($row['branch_id']) ? '' : htmlspecialchars($row['branch_id'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['branch']) ? '' : htmlspecialchars($row['branch'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['region']) ? '' : htmlspecialchars($row['region'])) . '</td>';
                            $rowHtml .= '<td>' . (empty($row['area']) ? '' : htmlspecialchars($row['area'])) . '</td>';
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
                        echo '<h3 id="total_transactions">Total For Review: ' . $grandTotal . '</h3>';
                    
                        // Output VISMIN table and count
                        echo '<h3 id="vismin_count">VISMIN (' . $visminCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Created Date</th<th>Lease Term</th><th>Request For Payment</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $visminRows . '</tbody>';
                        echo '</table>';
                    
                        // Output LNCR table and count
                        echo '<h3 id="lncr_count">LNCR (' . $lncrCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Created Date</th<th>Lease Term</th><th>Request For Payment</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $lncrRows . '</tbody>';
                        echo '</table>';
                    }
                    elseif ($userRow['roles'] == 'Am-Creator') {
                        $userRegion = $userRow['region'];
                        $userArea = $userRow['area'];
                        // Prepare and execute the SQL query
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

                        // Fetch and display the data in the table
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                            echo '<td style="display:none;">' . (empty($row['id']) ? '' : htmlspecialchars($row['id'])) . '</td>';
                            echo '<td style="display:none;">' . (empty($row['kpx_code']) ? '' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            echo '<td>' . (empty($row['contract_number']) ? '' : htmlspecialchars($row['contract_number'])) . '</td>';
                            echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            echo '<td>' . (empty($row['rdo']) ? '' : htmlspecialchars($row['rdo'])) . '</td>';
                            echo '<td>' . (empty($row['branch_id']) ? '' : htmlspecialchars($row['branch_id'])) . '</td>';
                            echo '<td>' . (empty($row['branch']) ? '' : htmlspecialchars($row['branch'])) . '</td>';
                            echo '<td>' . (empty($row['region']) ? '' : htmlspecialchars($row['region'])) . '</td>';
                            echo '<td>' . (empty($row['area']) ? '' : htmlspecialchars($row['area'])) . '</td>';
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

                    echo '<td>' . (empty($row['vat_type']) ? '' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                    echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                    // echo '<td>' . (empty($row['total_month_rental']) ? '' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? '' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? '' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? '' : htmlspecialchars($row['notarized'])) . '</td>';

                    echo '<td>';
                    $contractFiles = [];

                    // Collect contract files
                    for ($i = 1; $i <= 5; $i++) {
                        $contractFileKey = 'contract_file' . ($i == 1 ? '' : $i); // Handles contract_file, contract_file2, etc.
                        $fileNameKey = 'contractFilename' . ($i == 1 ? '' : $i); // Handles contractFilename, contractFilename2, etc.
                        $mimeTypeKey = 'mimeType' . ($i == 1 ? '' : $i); // Handles mimeType, mimeType2, etc.
                        
                        if (!empty($row[$contractFileKey])) {
                            $fileContent = $row[$contractFileKey];
                            $mimeType = $row[$mimeTypeKey]; // Assuming mimeType is stored in the same row
                            $fileName = $row[$fileNameKey]; // Assuming contractFilename is stored in the same row

                            // Get file extension
                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                            // Determine the icon
                            $icon = '';
                            switch ($fileExtension) {
                                case 'pdf':
                                    $icon = '<i class="fa fa-file-pdf-o"></i>';
                                    break;
                                case 'jpeg':
                                case 'jpg':
                                case 'png':
                                    $icon = '<i class="fa fa-file-image-o"></i>';
                                    break;
                                default:
                                    $icon = '<i class="fa fa-file-o"></i>';
                                    break;
                            }

                            $contractFiles[] = [
                                'file' => htmlspecialchars($fileName),
                                'icon' => $icon,
                                'content' => base64_encode($fileContent),
                                'mimeType' => $mimeType
                            ];
                        }
                    }

                    if (!empty($contractFiles)) {
                        if (count($contractFiles) === 1) {
                            // Display the single file as a link (not in the modal)
                            $singleFile = $contractFiles[0];
                            echo '<a href="#" class="view-single-file" data-file-content="' . $singleFile['content'] . '" data-mime-type="' . $singleFile['mimeType'] . '" data-file-name="' . $singleFile['file'] . '" style="font-weight:bold;">' . $singleFile['icon'] . ' ' . $singleFile['file'] . '</a>';
                        } else {
                            // Display the "View Files" button that opens the modal
                            echo '<button type="button" class="view-contracts" data-contract-files=\'' . json_encode($contractFiles) . '\'>View Files</button>';
                        }
                    } else {
                        echo '';
                    }
                    echo '</td>';

                            // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                            // echo '<td>' . htmlspecialchars($row['prepared_by']) . '</td>';
                            echo '<td>';
                            if (!empty($row['reviewer_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['reviewer_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';

                            echo '<td>';
                            if (!empty($row['audit_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['audit_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';
                        
                        
                        $userName = $_SESSION['user_email'];
                            // Fetch user roles based on user_email
                            $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                            $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                            if (mysqli_num_rows($rolesStmtResult) > 0) {
                                $row = mysqli_fetch_assoc($rolesStmtResult);

                                if ($row['roles'] == 'Vpo-Reviewer') {
                                    // 'REVIEWER' role found for the user
                                    echo "<td><button type='submit' name='review_contract' id='review_contract'><i class='fa-solid fa-check'></i></button></td>";
                                    echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                                }
                            } else {
                                // No 'REVIEWER' role found or error occurred
                                // Handle the case if needed
                            }
                        
                            echo '</tr>';
                        
                        }
                    }elseif ($userRow['roles'] == 'Rm-Reviewer') {
                        $userRegion = $userRow['region'];
                        // Prepare and execute the SQL query
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

                        // Fetch and display the data in the table
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                            echo '<td style="display:none;">' . (empty($row['id']) ? '' : htmlspecialchars($row['id'])) . '</td>';
                            echo '<td style="display:none;">' . (empty($row['kpx_code']) ? '' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            echo '<td>' . (empty($row['contract_number']) ? '' : htmlspecialchars($row['contract_number'])) . '</td>';
                            echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            echo '<td>' . (empty($row['rdo']) ? '' : htmlspecialchars($row['rdo'])) . '</td>';
                            echo '<td>' . (empty($row['branch_id']) ? '' : htmlspecialchars($row['branch_id'])) . '</td>';
                            echo '<td>' . (empty($row['branch']) ? '' : htmlspecialchars($row['branch'])) . '</td>';
                            echo '<td>' . (empty($row['region']) ? '' : htmlspecialchars($row['region'])) . '</td>';
                            echo '<td>' . (empty($row['area']) ? '' : htmlspecialchars($row['area'])) . '</td>';
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
                    echo '<td>' . (empty($row['vat_type']) ? '' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                    echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                    // echo '<td>' . (empty($row['total_month_rental']) ? '' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? '' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? '' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? '' : htmlspecialchars($row['notarized'])) . '</td>';

                     echo '<td>';
                    $contractFiles = [];

                    // Collect contract files
                    for ($i = 1; $i <= 5; $i++) {
                        $contractFileKey = 'contract_file' . ($i == 1 ? '' : $i); // Handles contract_file, contract_file2, etc.
                        $fileNameKey = 'contractFilename' . ($i == 1 ? '' : $i); // Handles contractFilename, contractFilename2, etc.
                        $mimeTypeKey = 'mimeType' . ($i == 1 ? '' : $i); // Handles mimeType, mimeType2, etc.
                        
                        if (!empty($row[$contractFileKey])) {
                            $fileContent = $row[$contractFileKey];
                            $mimeType = $row[$mimeTypeKey]; // Assuming mimeType is stored in the same row
                            $fileName = $row[$fileNameKey]; // Assuming contractFilename is stored in the same row

                            // Get file extension
                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                            // Determine the icon
                            $icon = '';
                            switch ($fileExtension) {
                                case 'pdf':
                                    $icon = '<i class="fa fa-file-pdf-o"></i>';
                                    break;
                                case 'jpeg':
                                case 'jpg':
                                case 'png':
                                    $icon = '<i class="fa fa-file-image-o"></i>';
                                    break;
                                default:
                                    $icon = '<i class="fa fa-file-o"></i>';
                                    break;
                            }

                            $contractFiles[] = [
                                'file' => htmlspecialchars($fileName),
                                'icon' => $icon,
                                'content' => base64_encode($fileContent),
                                'mimeType' => $mimeType
                            ];
                        }
                    }

                    if (!empty($contractFiles)) {
                        if (count($contractFiles) === 1) {
                            // Display the single file as a link (not in the modal)
                            $singleFile = $contractFiles[0];
                            echo '<a href="#" class="view-single-file" data-file-content="' . $singleFile['content'] . '" data-mime-type="' . $singleFile['mimeType'] . '" data-file-name="' . $singleFile['file'] . '" style="font-weight:bold;">' . $singleFile['icon'] . ' ' . $singleFile['file'] . '</a>';
                        } else {
                            // Display the "View Files" button that opens the modal
                            echo '<button type="button" class="view-contracts" data-contract-files=\'' . json_encode($contractFiles) . '\'>View Files</button>';
                        }
                    } else {
                        echo '';
                    }
                    echo '</td>';

                            // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                            // echo '<td>' . htmlspecialchars($row['prepared_by']) . '</td>';
                            echo '<td>';
                            if (!empty($row['reviewer_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['reviewer_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';

                            echo '<td>';
                            if (!empty($row['audit_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['audit_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';
                        
                        
                        $userName = $_SESSION['user_email'];
                            // Fetch user roles based on user_email
                            $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                            $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                            if (mysqli_num_rows($rolesStmtResult) > 0) {
                                $row = mysqli_fetch_assoc($rolesStmtResult);

                                if ($row['roles'] == 'Vpo-Reviewer') {
                                    // 'REVIEWER' role found for the user
                                    echo "<td><button type='submit' name='review_contract' id='review_contract'><i class='fa-solid fa-check'></i></button></td>";
                                    echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                                }
                            } else {
                                // No 'REVIEWER' role found or error occurred
                                // Handle the case if needed
                            }
                        
                            echo '</tr>';
                        
                        }
                    }elseif ($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                        $userMainzone = $userRow['mainzone'];
                        // Prepare and execute the SQL query
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
                        // Fetch and display the data in the table
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                            echo '<td style="display:none;">' . (empty($row['id']) ? '' : htmlspecialchars($row['id'])) . '</td>';
                            echo '<td style="display:none;">' . (empty($row['kpx_code']) ? '' : htmlspecialchars($row['kpx_code'])) . '</td>';
                            echo '<td>' . (empty($row['contract_number']) ? '' : htmlspecialchars($row['contract_number'])) . '</td>';
                            echo '<td>' . (empty($row['created_date']) ? '' : htmlspecialchars(date('F d, Y', strtotime($row['created_date'])))) . '</td>';
                            echo '<td>' . (empty($row['rdo']) ? '' : htmlspecialchars($row['rdo'])) . '</td>';
                            echo '<td>' . (empty($row['branch_id']) ? '' : htmlspecialchars($row['branch_id'])) . '</td>';
                            echo '<td>' . (empty($row['branch']) ? '' : htmlspecialchars($row['branch'])) . '</td>';
                            echo '<td>' . (empty($row['region']) ? '' : htmlspecialchars($row['region'])) . '</td>';
                            echo '<td>' . (empty($row['area']) ? '' : htmlspecialchars($row['area'])) . '</td>';
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
                        echo '<td>' . (empty($row['vat_type']) ? '' : htmlspecialchars($row['vat_type'])) . '</td>';
                        echo '<td>' . (empty($row['monthly_rental']) ? 'N/A' : number_format($row['monthly_rental'], 2)) . '</td>';
                        echo '<td>' . (empty($row['e_vat']) ? 'N/A' : number_format($row['e_vat'], 2)) . '</td>';
                        echo '<td>' . (empty($row['e_wtax']) ? 'N/A' : number_format($row['e_wtax'], 2)) . '</td>';
                        echo '<td>' . (empty($row['amount_to_lessor']) ? 'N/A' : number_format($row['amount_to_lessor'], 2)) . '</td>';  
                        // echo '<td>' . (empty($row['total_month_rental']) ? '' : number_format($row['total_month_rental'], 2)) . '</td>';
                        echo '<td>' . (empty($row['mode_of_payment']) ? '' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                        echo '<td style="display:none;">' . (empty($row['wallet_number']) ? '' : htmlspecialchars($row['wallet_number'])) . '</td>';

                        echo '<td>' . (empty($row['notarized']) ? '' : htmlspecialchars($row['notarized'])) . '</td>';

                    echo '<td>';
                    $contractFiles = [];

                    // Collect contract files
                    for ($i = 1; $i <= 5; $i++) {
                        $contractFileKey = 'contract_file' . ($i == 1 ? '' : $i); // Handles contract_file, contract_file2, etc.
                        $fileNameKey = 'contractFilename' . ($i == 1 ? '' : $i); // Handles contractFilename, contractFilename2, etc.
                        $mimeTypeKey = 'mimeType' . ($i == 1 ? '' : $i); // Handles mimeType, mimeType2, etc.
                        
                        if (!empty($row[$contractFileKey])) {
                            $fileContent = $row[$contractFileKey];
                            $mimeType = $row[$mimeTypeKey]; // Assuming mimeType is stored in the same row
                            $fileName = $row[$fileNameKey]; // Assuming contractFilename is stored in the same row

                            // Get file extension
                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                            // Determine the icon
                            $icon = '';
                            switch ($fileExtension) {
                                case 'pdf':
                                    $icon = '<i class="fa fa-file-pdf-o"></i>';
                                    break;
                                case 'jpeg':
                                case 'jpg':
                                case 'png':
                                    $icon = '<i class="fa fa-file-image-o"></i>';
                                    break;
                                default:
                                    $icon = '<i class="fa fa-file-o"></i>';
                                    break;
                            }

                            $contractFiles[] = [
                                'file' => htmlspecialchars($fileName),
                                'icon' => $icon,
                                'content' => base64_encode($fileContent),
                                'mimeType' => $mimeType
                            ];
                        }
                    }

                    if (!empty($contractFiles)) {
                        if (count($contractFiles) === 1) {
                            // Display the single file as a link (not in the modal)
                            $singleFile = $contractFiles[0];
                            echo '<a href="#" class="view-single-file" data-file-content="' . $singleFile['content'] . '" data-mime-type="' . $singleFile['mimeType'] . '" data-file-name="' . $singleFile['file'] . '" style="font-weight:bold;">' . $singleFile['icon'] . ' ' . $singleFile['file'] . '</a>';
                        } else {
                            // Display the "View Files" button that opens the modal
                            echo '<button type="button" class="view-contracts" data-contract-files=\'' . json_encode($contractFiles) . '\'>View Files</button>';
                        }
                    } else {
                        echo '';
                    }
                    echo '</td>';

                            // echo '<td>' . htmlspecialchars($row['request_status']) . '</td>';
                            // echo '<td>' . htmlspecialchars($row['prepared_by']) . '</td>';
                            echo '<td>';
                            if (!empty($row['reviewer_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['reviewer_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';

                            echo '<td>';
                            if (!empty($row['audit_note'])) {
                                echo '<button type="button" class="view-note" data-note="' . htmlspecialchars($row['audit_note'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-regular fa-eye"></i></button>';
                            }
                            echo '</td>';
                        
                        
                        $userName = $_SESSION['user_email'];
                            // Fetch user roles based on user_email
                            $rolesQuery = "SELECT roles FROM user_form WHERE username = '$userName'";
                            $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                            if (mysqli_num_rows($rolesStmtResult) > 0) {
                                $row = mysqli_fetch_assoc($rolesStmtResult);

                                if ($row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Vpo-Checker') {
                                    // 'REVIEWER' role found for the user
                                    echo "<td><button type='submit' name='review_contract' id='review_contract'><i class='fa-solid fa-check'></i></button></td>";
                                    echo "<td><button type='submit' name='send_creator' id='send_creator'><i class='fa-solid fa-file-pen'></i></button></td>";
                                }
                            } else {
                                // No 'REVIEWER' role found or error occurred
                                // Handle the case if needed
                            }
                        
                            echo '</tr>';
                        
                        }
                    }
                }
                // Close the database connection
                mysqli_close($conn);
                ?>
            </tbody>
        </table>
    <!-- Modal Structure -->
    <div id="fileModal" class="file-modal" style="display:none;">
        <div class="file-modal-content">
            <span class="file-modal-close">&times;</span>
            <div id="filePreview"></div>
        </div>
    </div>

        <input type="hidden" id="selected_id_display" name="selectedID" value="">
        <div class="modalNote" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
        <div class="modal-dialog-note">
            <div class="modal-content-note">
                <div class="modal-header-note">
                    <h5 class="modal-title" id="noteModalLabel">Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modalNote" aria-label="Close">Close</button>
                </div>
                <div class="modal-body-note">
                    <p id="auditNoteContent"></p>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-note');
            const modalNoteContent = document.getElementById('auditNoteContent');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const note = this.getAttribute('data-note');
                    modalNoteContent.textContent = note;
                    document.getElementById('noteModal').style.display = 'block'; // Show the modal
                });
            });
            
            // Close modal when close button or outside modal is clicked
            document.querySelectorAll('.modalNote .btn-close, .modalNote').forEach(element => {
                element.addEventListener('click', function(event) {
                    if (event.target === this) {
                        document.getElementById('noteModal').style.display = 'none';
                    }
                });
            });
        });
    </script>
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

  document.addEventListener('DOMContentLoaded', function () {
    // Handle single file click (open directly in new tab)
    document.querySelectorAll('.view-single-file').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const fileContent = event.target.dataset.fileContent;
            const mimeType = event.target.dataset.mimeType;
            const fileName = event.target.dataset.fileName;

            // Convert base64 to binary string
            const binaryString = atob(fileContent);

            // Create a Uint8Array from the binary string
            const uint8Array = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }

            // Create a Blob from the Uint8Array
            const blob = new Blob([uint8Array], { type: mimeType });

            // Create a URL for the Blob and use it to open in a new tab
            const blobUrl = URL.createObjectURL(blob);

            // Open the blob URL in a new tab
            window.open(blobUrl, '_blank');
        });
    });

    // Handle multiple files (display in modal)
    document.querySelectorAll('.view-contracts').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const files = JSON.parse(event.target.dataset.contractFiles);
            const filePreview = document.getElementById('filePreview');

            // Clear previous file links
            filePreview.innerHTML = '';

            // Add each file link to the modal
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

                    // Convert base64 to binary string
                    const binaryString = atob(fileContent);

                    // Create a Uint8Array from the binary string
                    const uint8Array = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        uint8Array[i] = binaryString.charCodeAt(i);
                    }

                    // Create a Blob from the Uint8Array
                    const blob = new Blob([uint8Array], { type: mimeType });

                    // Create a URL for the Blob and use it to open in a new tab
                    const blobUrl = URL.createObjectURL(blob);

                    // Open the blob URL in a new tab
                    window.open(blobUrl, '_blank');
                });

                filePreview.appendChild(linkElement);
            });

            // Show the modal
            document.getElementById('fileModal').style.display = 'block';
        });
    });

    // Close modal when the close button is clicked
    document.querySelector('.file-modal-close').addEventListener('click', function () {
        document.getElementById('fileModal').style.display = 'none';
    });

    // Close modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('fileModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});


function highlightRow(row) {


var selectedIdInput = document.getElementById('selected_id_display');

  // Highlight the clicked row
  row.style.backgroundColor = '#f7f0f0';

// Get and display the ID
var selectedId = row.querySelector('td:first-child').innerText;

var currentId = selectedIdInput.value;

var reviewBtn = row.querySelector('button[name="review_contract"]');
var sendBtn = row.querySelector('button[name="send_creator"]');

if(reviewBtn.style.visibility === 'visible') {
  reviewBtn.style.visibility = 'hidden';
  sendBtn.style.visibility = 'hidden';
}else {
  selectedIdInput.value = selectedId;
  reviewBtn.style.visibility = 'visible';
  sendBtn.style.visibility = 'visible';
}

  // Remove any existing highlights and visibility from other rows
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 1; i < rows.length; i++) {
     if (rows[i] !== row) {
         rows[i].style.backgroundColor = '';
         var reviewBtn = rows[i].querySelector('button[name="review_contract"]');
         if (reviewBtn) {
             reviewBtn.style.visibility = 'hidden';
         }
         var sendBtn = rows[i].querySelector('button[name="send_creator"]');
         if (sendBtn) {
             sendBtn.style.visibility = 'hidden';
         }
     }
  }

}

</script>
</body>
</html>
