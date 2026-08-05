<?php
session_start();
    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    }
        echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
        echo '<link rel="stylesheet" href="../sweetalert2/dist/sweetalert2.min.css">';
        echo '<script src="../../jquery-3.7.1.js"></script>';
if (isset($_POST['cancel_contract'])) {
    $selected_id = mysqli_real_escape_string($conn, $_POST['selectedID']);
    $cancelled_by = $_SESSION['user_name'];

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
                    title: 'Please review the contract before cancelling!',
                    html: '<table class="swal_table">' +
                                '<tr>' +
                                    '<td class="swal_td">Contract Number:</strong></td>' +
                                    '<td class="swal_td">' + contractDetails.contract_number + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Contract Period</td>' +
                                    '<td class="swal_td">' + contractDetails.start_date + ' to ' + contractDetails.end_date + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Region:</strong></td>' +
                                    '<td class="swal_td">' + contractDetails.region + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Area:</strong></td>' +
                                    '<td class="swal_td">' + contractDetails.area + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Branch:</strong></td>' +
                                    '<td class="swal_td">' + contractDetails.branch + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Amount</td>' +
                                    '<td class="swal_td">₱ ' + contractDetails.amount + '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td class="swal_td">Lessor Name:</strong></td>' +
                                    '<td class="swal_td">' + contractDetails.lessor_name + '</td>' +
                                '</tr>' +
                            '</table>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick:false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User confirmed, proceed with update
                        $.ajax({
                            type: 'POST',
                            url: 'cancel_request_status.php',
                            data: {
                                selected_id: '<?php echo $selected_id; ?>',
                                cancelled_by: '<?php echo $cancelled_by; ?>'
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
                                            window.location.href = 'cancellor_page.php';
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
            <title>ML Rental - Extraction Request</title>

            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/contract_ledger.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        </head>
    <body>
    <?php include ('navbar.php'); ?>
<section class="display-7" style="padding: 0;align-items: center;justify-content: center;flex-wrap: wrap; align-content: center;display: flex;position: relative;height: 4rem;"><a href="" style="flex: 1 1;height: 4rem;position: absolute;width: 100%;z-index: 1;"><img alt="" style="height: 4rem;" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="></a><p style="margin: 0;text-align: center;" class="display-7">&#8204;</p></section><script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>  <script src="../../assets/smoothscroll/smooth-scroll.js"></script>  <script src="../../assets/ytplayer/index.js"></script>  <script src="../../assets/dropdown/js/navbar-dropdown.js"></script>  <script src="../../assets/theme/js/script.js"></script>
  <input name="animation" type="hidden">

<form action="" method="POST">
<div class="prepared_container">
  <div class="container">
		<div class="row justify-content-center request">
			<div class="col-12 content-head">
				<div class="mbr-section-head mb-5">
					<h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
						<strong style="font-size:35px;">DATA EXTRACTION REQUEST</strong>
					</h3>
				</div>
			</div>
		</div>
        <div class="request_date">
            <form action="" method="POST">
                <span><i class="fa-regular fa-building"></i></span>
                <select name="branch" id="branch" class="branch_select" onchange="updateKpxCode(this)" required>
                    <option value=""></option>
                    <?php
                        $transactional = "SELECT DISTINCT branch, kpx_code FROM transactional WHERE branch != '' AND export_status = 'Requested' ORDER BY branch ASC";
                        $resultBranch = mysqli_query($conn, $transactional);
                        if ($resultBranch) {
                            while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                                $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch']) ? 'selected' : '';
                                echo "<option value='" . $rowBranch['branch'] . "' data-kpx-code='" . $rowBranch['kpx_code'] . "' data-lessor-name='" . $rowBranch['lessor_name'] . "' $selected>" . $rowBranch['branch'] . "</option>";
                            }
                        }
                    ?>
                </select>
                <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>">
                <input type="month" name="from" id="from" value="">
                <input type="month" name="to" id="to" value="">
                <button type="submit" name="accept_btn" id="accept_btn">Accept</button>
            </form>
        </div>
	</div>
 <?php 
if(isset($_POST["accept_btn"])) {
    // Check if "from" and "to" keys are set in $_POST
    if(isset($_POST['from']) && isset($_POST['to'])) {
        // Check if a branch is selected
        if(isset($_POST['kpxCode']) && !empty($_POST['kpxCode'])) {
            $branch = $_POST['kpxCode'];
            $branch_condition = "AND kpx_code = '$branch'";
        } else {
            $branch_condition = "";
        }
        
        $start_date = $_POST['from'];
        $end_date = $_POST['to'];

        // Check if branch is empty and date range is not empty
        if(empty($branch_condition) && !empty($start_date) && !empty($end_date)) {
            $date_condition = "AND DATE_FORMAT(transaction_date, '%Y-%m') >= '$start_date' AND DATE_FORMAT(transaction_date, '%Y-%m') <= '$end_date'";
        } else {
            $date_condition = "";
        }

        // Check if there is a request for extraction
        $request_query = "SELECT * FROM transactional WHERE export_status = 'Requested' $branch_condition $date_condition";
        $request_result = mysqli_query($conn, $request_query);
        
        if(mysqli_num_rows($request_result) > 0){
            // There is a request for extraction found
            $update = "UPDATE transactional SET export_status = '' WHERE export_status = 'Requested' $branch_condition $date_condition";
            $res_request = mysqli_query($conn, $update);

            if($res_request !== false){
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>';
                echo '<script>';
                echo 'document.addEventListener("DOMContentLoaded", function() {';
                echo '  Swal.fire({';
                echo "    icon: 'success',";
                echo "    title: 'Request Accepted!',";
                echo "    text: 'Request successfully accepted.',";
                echo '  }).then((result) => {';
                echo "    if (result.isConfirmed) {";
                echo "      window.location.href = 'export_granted.php';";
                echo '    }';
                echo '  });';
                echo '});';
                echo '</script>';
            } else {
                echo "Error occurred while accepting the request.";
            }
        } else {
            // No request for extraction found
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>';
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo '  Swal.fire({';
            echo "    icon: 'warning',";
            echo "    title: 'No Request Found!',";
            echo "    text: 'There is no request for extraction found!',";
            echo '  }).then((result) => {';
            echo "    if (result.isConfirmed) {";
            echo "      window.location.href = 'export_granted.php';";
            echo '    }';
            echo '  });';
            echo '});';
            echo '</script>';
        }
    } else {
        echo "Please select both start and end dates.";
    }
}
?>

    <div class="table_wrap">
    <table class="contract_lg_table" id="contract_lg_table">
       <thead>
            <tr>
                <th>CONTRACT NUMBER</th>
                <th>REGION</th>
                <th>AREA</th>
                <th>BRANCH</th>
                <th>LESSOR NAME</th>
                <?php
                // Check if any rows have non-empty sec_lessor_name based on the selected kpx_code
                if (isset($_POST['kpxCode']) && isset($_POST['contractNumber'])) {
                    $status = ''; // Assuming status check (you might need to adjust this)
                    $kpxCode = $_POST['kpxCode'];
                    $contract_number = $_POST['contractNumber'];

                    // Query to check if l2_firstname, l2_middlename, or l2_lastname are not empty
                    $checkQuery = "SELECT l2_firstname, l2_middlename, l2_lastname FROM transactional WHERE (l2_firstname != '' OR l2_middlename != '' OR l2_lastname != '') AND status != ? AND kpx_code = ? AND contract_number = ? LIMIT 1";

                    // Prepare and execute the query
                    $checkStmt = mysqli_prepare($conn, $checkQuery);
                    mysqli_stmt_bind_param($checkStmt, "sss", $status, $kpxCode, $contract_number);
                    mysqli_stmt_execute($checkStmt);
                    $checkResult = mysqli_stmt_get_result($checkStmt);

                    // Check if there are any rows matching the conditions
                    if (mysqli_num_rows($checkResult) > 0) {
                        echo "<th>2nd LESSOR NAME</th>";
                    }
                }
                ?>
                <th>DATE</th>
                <th>MONTHLY RENTAL</th>
                <th>STATUS</th>
                <th>FILE DOWNLOAD</th>
                <th>REQUESTED BY</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $status = 'Requested';
                // Prepare and execute the SQL query
                $selectQuery = "SELECT * FROM transactional WHERE export_status = ?";
                $stmt = mysqli_prepare($conn, $selectQuery);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $status);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($result) {
                        // Fetch and display the data in the table
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo '<tr onclick="highlightRow(this)">';
                            echo '<td style="display:none;">' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td style="display:none;">' . htmlspecialchars($row['kpx_code']) . '</td>';
                            echo '<td style="display:none;">' . htmlspecialchars($row['contract_number']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['contract_number']) . '</td>';                    
                            echo '<td>' . htmlspecialchars($row['region']) . '</td>';                    
                            echo '<td>' . htmlspecialchars($row['area']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['branch']) . '</td>';
                            echo '<td>' . 
                                (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname']) : 'N/A') . " " . 
                                (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename']) : '') . " " . 
                                (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname']) : '') . 
                            '</td>';

                            if (isset($row['l2_firstname']) && !empty($row['l2_firstname']) || isset($row['l2_middlename']) && !empty($row['l2_middlename']) || isset($row['l2_lastname']) && !empty($row['l2_lastname'])) {
                            echo '<td>' . 
                                    (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname']) : 'N/A') . " " . 
                                    (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename']) : '') . " " . 
                                    (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname']) : '') . 
                                '</td>';
                            }
                            echo '<td>' . date('F j, Y', strtotime($row['transaction_date'])) . '</td>';
                            echo '<td style="text-align:center;">₱ ' . number_format($row['amount'], 2) . '</td>';
                            echo '<td style="font-weight: 700;">' . htmlspecialchars($row['status']) . '</td>';
                            if (isset($row['export_status']) && !empty($row['export_status'])) {
                                echo '<td style="font-weight: 700;">' . htmlspecialchars($row['export_status']) . '</td>';
                            }
                            echo '<td style="font-weight: 700;">' . htmlspecialchars($row['requested_by']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo "Error fetching result: " . mysqli_error($conn);
                    }
                } else {
                    echo "Error preparing statement: " . mysqli_error($conn);
                }
                // Close the database connection
                mysqli_close($conn);
            ?>
        </tbody>
    </table>
    <input type="hidden" id="selected_id_display" name="selectedID" value=""> 
        
    </div>
</div>
</form>
<script>
function updateKpxCode(branchSelect) {
        const selectedBranch = branchSelect.selectedOptions[0];
        const kpxCode = selectedBranch.dataset.kpxCode;
        const lessor_name = selectedBranch.dataset.lessorName; // Corrected the property name here
        const kpxCodeInput = document.getElementById('kpxCode');
        const lessorInput = document.getElementById('lessor_name');
        kpxCodeInput.value = kpxCode;
        lessorInput.value = lessor_name;

}

function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'transparent-*';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'

  
}
    </script>
    </body>
</html>
