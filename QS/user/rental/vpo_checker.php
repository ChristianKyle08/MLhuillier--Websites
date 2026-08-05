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

                if (contractDetails.start_date && contractDetails.end_date && contractDetails.start_date !== 'N/A' && contractDetails.end_date !== 'N/A') {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">RFP Contract Period:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.start_date + ' to ' + contractDetails.end_date +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.corporate_lessor) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Corporate Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.corporate_lessor +
                        '</td>' +
                        '</tr>';
                }

                if (contractDetails.l1_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Lessor Name:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.l1_firstname + ' ' +
                        (contractDetails.l1_middlename ? contractDetails.l1_middlename : '') + ' ' +
                        (contractDetails.l1_lastname ? contractDetails.l1_lastname : 'N/A') +
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
                if (contractDetails.authorize_firstname) {
                    lessorDetailsHTML += '<tr>' +
                        '<td class="swal_td">Authorize to claim:</td>' +
                        '<td class="swal_td">' +
                        contractDetails.authorize_firstname + ' ' +
                        (contractDetails.authorize_middlename ? contractDetails.authorize_middlename : '') + ' ' +
                        (contractDetails.authorize_lastname ? contractDetails.authorize_lastname : '') +
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

                Swal.fire({
                    title: 'Please review the contract!',
                    html: '<table class="swal_table">' +
                        '<tr>' +
                        '<td class="swal_td">Contract Number:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.contract_number ? contractDetails.contract_number : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Notarized:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.notarized ? contractDetails.notarized : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">RDO Number:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.rdo ? contractDetails.rdo : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Corporate Name:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.corporate_name ? contractDetails.corporate_name : '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Contract Period:</td>' +
                        '<td class="swal_td">' + (contractDetails.contract_start && contractDetails.contract_end ? contractDetails.contract_start + ' to ' + contractDetails.contract_end : 'N/A') + '</td>' +
                        '</tr>' +
                         '<tr>' +
                        '<td class="swal_td">Payment Due Date:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.payment_due_date ? contractDetails.payment_due_date : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Region:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.region ? contractDetails.region : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Area:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.area ? contractDetails.area : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Branch:</strong></td>' +
                        '<td class="swal_td">' + (contractDetails.branch ? contractDetails.branch : 'N/A') + '</td>' +
                        '</tr>' +
                         '<tr>' +
                        '<td class="swal_td">Vat Type:</td>' +
                        '<td class="swal_td">' + (contractDetails.vat_type ? contractDetails.vat_type : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Gross Rental:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.amount ? contractDetails.amount : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Inputted Amount:</td>' +
                        '<td class="swal_td">' + (contractDetails.inputted_amount ? contractDetails.inputted_amount : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr style="display:none;">' +
                        '<td class="swal_td">Net of Vat:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.net_of_vat ? contractDetails.net_of_vat : 'N/A') + '</td>' +
                        '</tr>' +
                        '<td class="swal_td">Vat Amount:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.vat_amount ? contractDetails.vat_amount : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Withholding Tax:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.wtax ? contractDetails.wtax : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Amount to Lessor:</td>' +
                        '<td class="swal_td">₱ ' + (contractDetails.amount_lessor ? contractDetails.edit_amount_lessor : 'N/A') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td class="swal_td">Mode of Payment:</td>' +
                        '<td class="swal_td">' + (contractDetails.mode_of_payment ? contractDetails.mode_of_payment : 'N/A') + '</td>' +
                        '</tr>' +
                        (contractDetails.mode_of_payment === 'WALLET' ?
                            '<tr>' +
                            '<td class="swal_td">Wallet number:</td>' +
                            '<td class="swal_td">' + (contractDetails.wallet_number ? contractDetails.wallet_number : 'N/A') + '</td>' +
                            '</tr>' : ''
                        ) +
                         '<tr>' +
                        '<td class="swal_td">Lessor Type:</td>' +
                        '<td class="swal_td">' + (contractDetails.lessor_type ? contractDetails.lessor_type : 'N/A') + '</td>' +
                        '</tr>' +
                        lessorDetailsHTML +
                        '</table>',
                        icon: 'info',
                    showCancelButton: true,
                    showConfirmButton: contractDetails.mode_of_payment !== 'PDC' && contractDetails.mode_of_payment !== 'RTA',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Close',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User confirmed, proceed with update
                        $.ajax({
                            type: 'POST',
                            url: 'update_request_status_vpochecker.php',
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
                                            window.location.href = 'vpo_checker.php';
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
                            url: 'reviewer_disapprove_request_vpochecker.php',
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
                                            window.location.href = 'vpo_checker.php';
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
        <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
        <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
        <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
        <!-- custom CSS file link  -->
        <link rel="stylesheet" href="../../css/view_contract.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="../../css/prepared_page.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
        <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
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
                        <th>RDO NUMBER</th>
                        <th>BRANCH ID</th>
                        <th>BRANCH</th>
                        <th>REGION</th>
                        <th>AREA</th>
                        <th>AUTHORIZE NAME</th>
                        <th>LESSOR NAME</th>
                        <th>CONTRACT PERIOD</th>
                        <th>RFP CONTRACT PERIOD</th>
                        <th>PAYMENT DUE</th>
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

                            if ($row['roles'] == 'Vpo-Checker') {
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
                $requestStatus = 'Reviewed';
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
                            $rowHtml .= '<td>' . 
                                        (empty($row['contract_start']) ? 'N/A' : htmlspecialchars(date('F d, Y', strtotime($row['contract_start'])))) . 
                                        ' to ' . 
                                        (empty($row['contract_end']) ? 'N/A' : htmlspecialchars(date('F d, Y', strtotime($row['contract_end'])))) . 
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
                        echo '<h3 id="total_transactions">Total For Review: ' . $grandTotal . '</h3>';
                    
                        // Output VISMIN table and count
                        echo '<h3 id="vismin_count">VISMIN (' . $visminCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Contract Period</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $visminRows . '</tbody>';
                        echo '</table>';
                    
                        // Output LNCR table and count
                        echo '<h3 id="lncr_count">LNCR (' . $lncrCount . ')</h3>';
                        echo '<table>';
                        echo '<thead><tr><th>Contract Number</th><th>Contract Period</th><th>Branch ID</th><th>Branch</th><th>Region</th><th>Area</th></tr></thead>';
                        echo '<tbody>' . $lncrRows . '</tbody>';
                        echo '</table>';
                    }
                    // Check if the user's role is 'Am-Creator'
                    elseif ($userRow['roles'] == 'Am-Creator') {
                        $userRegion = $userRow['region'];
                        $userArea = $userRow['area'];

                        // Prepare and execute the SQL query
                        $selectQuery = "SELECT * FROM create_contract WHERE request_status = ? AND rfp_status = ? AND region = ? AND area = ?";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "ssss", $requestStatus, $rfpStatus, $userRegion, $userArea);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                // Fetch and display the data in the table
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                    echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                    echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                    echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                    echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                    echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                    echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                    echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                    echo '<td style="background-color: #d5fab7">' . 
                        (!empty($row['authorize_firstname']) || !empty($row['authorize_middlename']) || !empty($row['authorize_lastname']) || !empty($row['authorize_to_claim'])
                            ? 
                            (!empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '') . " " .
                            (!empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '') . " " .
                            (!empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                        '</td>';

                     echo '<td>' . 
                    (!empty($row['l1_firstname']) || !empty($row['l1_middlename']) || !empty($row['l1_lastname']) || !empty($row['authorize_to_claim'])
                        ? 
                        (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . " " .
                        (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . " " .
                        (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') 
                        : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
                    
                    echo '<td style="display:none;">' . 
                        (!empty($row['l2_firstname']) || !empty($row['l2_middlename']) || !empty($row['l2_lastname'])
                            ? 
                            (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . " " .
                            (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . " " .
                            (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l2']) ? htmlspecialchars($row['mobile_number_l2']) : 'N/A') . '</td>';

                    echo '<td>';
                     $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                         : 'N/A';
                     
                     $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                     echo '</td>';
                     
                     echo '<td>';
                     $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['start_date'])) 
                         : 'N/A';
                     
                     $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['end_date'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($startDate . ' to ' . $endDate);
                     echo '</td>'; 
                    echo '<td>' . (empty($row['payment_due_date']) ? 'N/A' : date('F d, Y', strtotime($row['payment_due_date']))) . '</td>';
                    echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['amount']) ? 'N/A' : number_format($row['amount'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['net_of_vat']) ? 'N/A' : number_format($row['net_of_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['vat_amount']) ? 'N/A' : number_format($row['vat_amount'], 2)) . '</td>';
                    echo '<td>' . (empty($row['wtax']) ? 'N/A' : number_format($row['wtax'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['edit_amount_lessor']) ? 'N/A' : number_format($row['edit_amount_lessor'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';

                    // Display a download link for the file with the appropriate icon
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
                        echo 'N/A';
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

                        if ($row['roles'] == 'Vpo-Checker') {
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
                        $selectQuery = "SELECT * FROM create_contract WHERE request_status = ? AND rfp_status = ? AND region = ?";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "sss", $requestStatus, $rfpStatus, $userRegion);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                // Fetch and display the data in the table
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                    echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                    echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                    echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                    echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                    echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                    echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                    echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                    echo '<td style="background-color: #d5fab7">' . 
                        (!empty($row['authorize_firstname']) || !empty($row['authorize_middlename']) || !empty($row['authorize_lastname']) || !empty($row['authorize_to_claim'])
                            ? 
                            (!empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '') . " " .
                            (!empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '') . " " .
                            (!empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                        '</td>';

                    echo '<td>' . 
                    (!empty($row['l1_firstname']) || !empty($row['l1_middlename']) || !empty($row['l1_lastname']) || !empty($row['authorize_to_claim'])
                        ? 
                        (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . " " .
                        (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . " " .
                        (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') 
                        : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
                    
                    echo '<td style="display:none;">' . 
                        (!empty($row['l2_firstname']) || !empty($row['l2_middlename']) || !empty($row['l2_lastname'])
                            ? 
                            (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . " " .
                            (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . " " .
                            (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l2']) ? htmlspecialchars($row['mobile_number_l2']) : 'N/A') . '</td>';
                     
                    echo '<td>';
                     $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                         : 'N/A';
                     
                     $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                     echo '</td>';
                     
                     echo '<td>';
                     $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['start_date'])) 
                         : 'N/A';
                     
                     $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['end_date'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($startDate . ' to ' . $endDate);
                     echo '</td>'; 
                    echo '<td>' . (empty($row['payment_due_date']) ? 'N/A' : date('F d, Y', strtotime($row['payment_due_date']))) . '</td>';
                    echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['amount']) ? 'N/A' : number_format($row['amount'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['net_of_vat']) ? 'N/A' : number_format($row['net_of_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['vat_amount']) ? 'N/A' : number_format($row['vat_amount'], 2)) . '</td>';
                    echo '<td>' . (empty($row['wtax']) ? 'N/A' : number_format($row['wtax'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['edit_amount_lessor']) ? 'N/A' : number_format($row['edit_amount_lessor'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';

                    // Display a download link for the file with the appropriate icon
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
                        echo 'N/A';
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

                        if ($row['roles'] == 'Vpo-Checker') {
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
                        $selectQuery = "SELECT * FROM create_contract WHERE request_status = ? AND rfp_status = ? AND mainzone = ?";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "sss", $requestStatus, $rfpStatus, $userMainzone);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                // Fetch and display the data in the table
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr data-id="' . htmlspecialchars($row['id']) . '" onclick="highlightRow(this)">';
                    echo '<td style="display:none;">' . (empty($row['id']) ? 'N/A' : htmlspecialchars($row['id'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['kpx_code']) ? 'N/A' : htmlspecialchars($row['kpx_code'])) . '</td>';
                    echo '<td>' . (empty($row['contract_number']) ? 'N/A' : htmlspecialchars($row['contract_number'])) . '</td>';
                    echo '<td>' . (empty($row['rdo']) ? 'N/A' : htmlspecialchars($row['rdo'])) . '</td>';
                    echo '<td>' . (empty($row['branch_id']) ? 'N/A' : htmlspecialchars($row['branch_id'])) . '</td>';
                    echo '<td>' . (empty($row['branch']) ? 'N/A' : htmlspecialchars($row['branch'])) . '</td>';
                    echo '<td>' . (empty($row['region']) ? 'N/A' : htmlspecialchars($row['region'])) . '</td>';
                    echo '<td>' . (empty($row['area']) ? 'N/A' : htmlspecialchars($row['area'])) . '</td>';
                    echo '<td style="background-color: #d5fab7">' . 
                        (!empty($row['authorize_firstname']) || !empty($row['authorize_middlename']) || !empty($row['authorize_lastname']) || !empty($row['authorize_to_claim'])
                            ? 
                            (!empty($row['authorize_firstname']) ? htmlspecialchars($row['authorize_firstname']) : '') . " " .
                            (!empty($row['authorize_middlename']) ? htmlspecialchars($row['authorize_middlename']) : '') . " " .
                            (!empty($row['authorize_lastname']) ? htmlspecialchars($row['authorize_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                        '</td>';

                    echo '<td>' . 
                    (!empty($row['l1_firstname']) || !empty($row['l1_middlename']) || !empty($row['l1_lastname']) || !empty($row['authorize_to_claim'])
                        ? 
                        (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . " " .
                        (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . " " .
                        (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') 
                        : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l1']) ? htmlspecialchars($row['mobile_number_l1']) : '') . '</td>';
                    
                    echo '<td style="display:none;">' . 
                        (!empty($row['l2_firstname']) || !empty($row['l2_middlename']) || !empty($row['l2_lastname'])
                            ? 
                            (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . " " .
                            (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . " " .
                            (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') 
                            : htmlspecialchars($row['corporate_lessor'])) . 
                    '</td>';
                    
                    echo '<td style="display:none;">' . (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') . '</td>';
                    echo '<td style="display:none;">' . (!empty($row['mobile_number_l2']) ? htmlspecialchars($row['mobile_number_l2']) : 'N/A') . '</td>';

                    echo '<td>';
                     $contractStart = (isset($row['contract_start']) && $row['contract_start'] !== '0000-00-00' && $row['contract_start'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_start'])) 
                         : 'N/A';
                     
                     $contractEnd = (isset($row['contract_end']) && $row['contract_end'] !== '0000-00-00' && $row['contract_end'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['contract_end'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($contractStart . ' to ' . $contractEnd);
                     echo '</td>';
                     
                     echo '<td>';
                     $startDate = (isset($row['start_date']) && $row['start_date'] !== '0000-00-00' && $row['start_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['start_date'])) 
                         : 'N/A';
                     
                     $endDate = (isset($row['end_date']) && $row['end_date'] !== '0000-00-00' && $row['end_date'] !== '') 
                         ? date('F d, Y', strtotime((string) $row['end_date'])) 
                         : 'N/A';
                     
                     echo htmlspecialchars($startDate . ' to ' . $endDate);
                     echo '</td>'; 
                    echo '<td>' . (empty($row['payment_due_date']) ? 'N/A' : date('F d, Y', strtotime($row['payment_due_date']))) . '</td>';
                    echo '<td>' . (empty($row['vat_type']) ? 'N/A' : htmlspecialchars($row['vat_type'])) . '</td>';
                    echo '<td>' . (empty($row['amount']) ? 'N/A' : number_format($row['amount'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['net_of_vat']) ? 'N/A' : number_format($row['net_of_vat'], 2)) . '</td>';
                    echo '<td>' . (empty($row['vat_amount']) ? 'N/A' : number_format($row['vat_amount'], 2)) . '</td>';
                    echo '<td>' . (empty($row['wtax']) ? 'N/A' : number_format($row['wtax'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['edit_amount_lessor']) ? 'N/A' : number_format($row['edit_amount_lessor'], 2)) . '</td>';
                    // echo '<td>' . (empty($row['total_month_rental']) ? 'N/A' : number_format($row['total_month_rental'], 2)) . '</td>';
                    echo '<td>' . (empty($row['mode_of_payment']) ? 'N/A' : htmlspecialchars($row['mode_of_payment'])) . '</td>';
                    echo '<td style="display:none;">' . (empty($row['wallet_number']) ? 'N/A' : htmlspecialchars($row['wallet_number'])) . '</td>';

                    echo '<td>' . (empty($row['notarized']) ? 'N/A' : htmlspecialchars($row['notarized'])) . '</td>';

                    // Display a download link for the file with the appropriate icon
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
                        echo 'N/A';
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

                        if ($row['roles'] == 'Vpo-Checker') {
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

        <input type="hidden" id="selected_id_display" name="selectedID" value="">

            <!-- Modal Structure -->
<div id="fileModal" class="file-modal" style="display:none;">
    <div class="file-modal-content">
        <span class="file-modal-close">&times;</span>
        <div id="filePreview"></div>
    </div>
</div>

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
<script>
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
