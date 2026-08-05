<?php
   session_start();
    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
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
            <title>QS - Lease of Contract</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/contract_ledger.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

            <script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>
            <link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">
            <script src="../../jquery-3.7.1.js"></script>
            <style>
                .file-modal {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 850px;
                    max-width: 900px;
                    padding: 20px;
                    background-color: white;
                    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                }

                .file-modal-content {
                    text-align: center;
                }
                /* Style for the unordered list */
                #filePreview ul {
                    list-style-type: none; /* Remove bullet points */
                    padding: 0; /* Remove default padding */
                    margin: 0; /* Remove default margin */
                }

                /* Style for each list item */
                #filePreview ul li {
                    margin-bottom: 10px; /* Add some space between items */
                }

                /* Style for the links */
                #filePreview ul li a {
                    text-decoration: none; /* Remove underline from links */
                    color: #d70c0c; /* Set link color */
                    font-weight: bold; /* Make the text bold */
                }

                /* Hover effect for links */
                #filePreview ul li a:hover {
                    text-decoration: underline; /* Underline on hover */
                    color: #a70909; /* Darker blue on hover */
                }

                .file-modal .file-modal-close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    font-size: 24px;
                    cursor: pointer;
                }
            </style>
        </head>
    <body>

    <nav role="navigation" class="nav">
    <ul class="nav-items">
        <li class="nav-item">
            <a href="user_page.php" class="nav-link"><span>Home</span></a>
        </li> 
         <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <li class="submenu-item"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                        <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">Edit</a></li>
                    </ul>
                </nav>
            </li> 
            <?php
                }else if ($row['roles'] == 'HO') {
                ?>
                <li class="nav-item">
                    <a href="post_payment.php" class="nav-link"><span>Post Payment</span></a>
                </li>
              <li class="nav-item dropdown">
                  <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                  <nav class="submenu">
                      <ul class="submenu-items">
                          <li class="submenu-item" style="display:none;"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                          <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">View Lessor Profile</a></li>
                      </ul>
                  </nav>
              </li> 
               <?php
                    }
                }
               ?>
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'BOOKKEEPER'  || $row['roles'] == 'Finance' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO') { ?>
            <a href="#" style="display:none;" class="nav-link"><span>Contract of Lease</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Contract of Lease</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                 <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
                    <li class="submenu-item"><a href="create_contract.php" class="submenu-link">Create Contract</a></li>
                    <li class="submenu-item"><a href="renew_contract.php" class="submenu-link">Renew Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                     <?php
                     }elseif($row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Vpo-Checker' || $row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer'){
                     ?>
                      <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                     <?php
                     }elseif($row['roles'] == 'HO'){
                        ?>
                        <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                        <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                        <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                        <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                        <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                        <?php
                    }
                  }
                  
               ?>   
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'Finance') { ?>
                <a style="display:none;" href="#" class="nav-link"><span>Reports</span></a>
            <?php }else{ ?>
                <a href="#" class="nav-link"><span>Reports</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="lease_contract.php" class="submenu-link">Contract of Lease</a></li>
                    <li class="submenu-item"><a href="contract_ledger.php" class="submenu-link">COL - Payment Ledger</a></li>
                    <?php
                    if($row['roles'] == 'BOOKKEEPER'){ 
                    ?>
                    <li class="submenu-item"><a href="edi_extraction.php" class="submenu-link">EDI Extraction</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO' || $row['roles'] == 'Vpo-Checker'){ 
                    ?>
                    <li class="submenu-item"><a href="ho_page.php" class="submenu-link">Head Office</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="corporate_report.php" class="submenu-link">By Corporate</a></li>
                    <li class="submenu-item"><a href="payout_report.php" class="submenu-link">Payout Report</a></li>
                    <li class="submenu-item"><a href="sendout_report.php" class="submenu-link">Sendout Report</a></li>
                    <?php } ?>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
            <?php
                if($row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Am-Creator' || $row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO'){ 
            ?>
            <a style="display:none;" href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php } ?>
            <nav class="submenu">
                <ul class="submenu-items">
                    <?php
                       if($row['roles'] == 'Vpo-Checker' || $row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="request_data_extraction.php" class="submenu-link">Create Data</a></li>
                    <?php
                    }
                    ?>
                    <?php
                       if($row['roles'] == 'Finance'){ 
                    ?>
                    <li class="submenu-item"><a href="extract_request_finance.php" class="submenu-link">Batch Upload</a></li>
                     <?php } ?>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
        <?php
            if( $row['roles'] == 'Auditor' || $row['roles'] == 'HO'){ 
            ?>
            <a href="#" style="display:none;" class="nav-link"><span>Terminate COL</span></a>
            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                </ul>
            </nav>
            <?php
            }else{
            ?>
            <a href="#" class="nav-link"><span>Terminate COL</span></a>
            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                </ul>
            </nav>
            <?php } ?>
        </li> 
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link" id="logout"><span>Logout</span>
            <i class='bx bx-log-in'></i>
        </a>
        </li> 
       <b style="font-weight:700; color:#333;"><?php echo strtoupper($_SESSION['user_email']); ?></b>
    </ul>
    
</nav>
<form action="" method="POST" id="ledger_form">
<div class="contract_lg_container">
    <div class="wrapper">
        <div class="search_div">
            <span><i class="fa-regular fa-building"></i></span>
            <select name="branch" id="branch" class="branch_select" required onchange="updateKpxCode(this)">
                <option value=""></option>
                <?php
                    $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                    
                    // Get the user role, area, and region
                    $userQuery = "SELECT roles, mainzone, area, region FROM user_form WHERE email = '$user_email'";
                    $resultUser = mysqli_query($conn, $userQuery);
                    $user = mysqli_fetch_assoc($resultUser);
                    $userRole = $user['roles'];
                    $userMainzone = $user['mainzone'];
                    $userArea = $user['area'];
                    $userRegion = $user['region'];

                    // Construct the query based on the user role
                    if ($userRole == 'HO') {
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id 
                            FROM transactional 
                            WHERE branch != ''
                            AND status != 'Terminated'
                            ORDER BY branch ASC";
                    }else if ($userRole == 'Am-Creator') {
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id 
                            FROM transactional 
                            WHERE branch != ''
                            AND status != 'Terminated'
                            AND region = '$userRegion'
                            AND area = '$userArea'
                            ORDER BY branch ASC";
                    } elseif ($userRole == 'Rm-Reviewer') {
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id
                            FROM transactional 
                            WHERE branch != '' 
                            AND status != 'Terminated'
                            AND region = '$userRegion'
                            ORDER BY branch ASC";
                    }else if($userRole == 'Finance') {
                        // Default query if no specific role is found
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id
                            FROM transactional 
                            WHERE branch != ''
                            AND status != 'Terminated'
                            ORDER BY branch ASC";
                    }else if($userRole == 'Auditor') {
                        // Default query if no specific role is found
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id
                            FROM transactional 
                            WHERE branch != ''
                            AND status != 'Terminated'
                            ORDER BY branch ASC";
                    }
                    else{
                        // Default query if no specific role is found
                        $transactional = "
                            SELECT DISTINCT branch, region, area, kpx_code, branch_id
                            FROM transactional 
                            WHERE branch != ''
                            AND status != 'Terminated'
                            AND mainzone = '$userMainzone'
                            ORDER BY branch ASC";
                    }
                    
                    $resultBranch = mysqli_query($conn, $transactional);
                    
                    if ($resultBranch) {
                        while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                            $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch']) ? 'selected' : '';
                            echo "<option value='" . $rowBranch['branch'] . "' data-kpx-code='" . $rowBranch['kpx_code'] . "' data-branch-id='" . $rowBranch['branch_id'] . "' $selected>" . $rowBranch['branch'] . "(" . $rowBranch['region'] . " ,  Area ". $rowBranch['area'] .")" . "</option>";
                        }
                    }
                ?>
            </select>
            <i class="fa-solid fa-file-signature"></i>
           <select name="contractNumber" id="contractNumber" class="contract_select" required onchange="this.form.submit()">
                <option value=""></option>
                <?php
                    $selected_branch = $_POST['branch'];
                    $contract = "SELECT DISTINCT contract_number FROM transactional WHERE contract_number != '' AND status != 'Terminated' AND branch = '$selected_branch' ORDER BY contract_number DESC";
                    $resultContract = mysqli_query($conn, $contract);
                    if ($resultContract) {
                        while ($rowContract = mysqli_fetch_assoc($resultContract)) {
                            $selected = (isset($_POST['contractNumber']) && $_POST['contractNumber'] == $rowContract['contract_number']) ? 'selected' : '';
                            echo "<option value='" . $rowContract['contract_number'] . "' $selected>" . $rowContract['contract_number'] . "</option>";
                        }
                    }
                ?>
            </select>
            <input style="width:350px;" type="hidden" name="lessor_name" id="lessor_name" value="<?php echo isset($_POST['lessor_name']) ? $_POST['lessor_name'] : '' ?>" readonly>
            <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>">
            <input type="hidden" name="branchId" id="branchId" value="<?php echo isset($_POST['branchId']) ? $_POST['branchId'] : '' ?>">
            <button type="submit" name="proceed_btn" id="proceed_btn">PROCEED</button>
        </div>
<div class="cancel_div">
<?php
  // Check if the user is logged in
    $userName = $_SESSION['user_email'];
    
    // Fetch user roles based on user_email
    $rolesQuery = "SELECT roles FROM user_form WHERE email = ?";
    $rolesStmt = mysqli_prepare($conn, $rolesQuery);
    mysqli_stmt_bind_param($rolesStmt, "s", $userName);
    mysqli_stmt_execute($rolesStmt);
    $rolesStmtResult = mysqli_stmt_get_result($rolesStmt);

    if (mysqli_num_rows($rolesStmtResult) > 0) {
        $row = mysqli_fetch_assoc($rolesStmtResult);

        if ($row['roles'] == 'CANCELLOR') {
            // 'CANCELLOR' role found for the user
            echo "<form action='' method='POST'>";
            echo "<input type='month' name='cancel_startDate' id='cancel_startDate' value=''>";
            echo "<input type='month' name='cancel_endDate' id='cancel_endDate' value=''>";
            echo "<button type='button' name='cancel_contract' id='cancel_contract'>Cancel</button>";

            // Add modal for cancellation reason
            echo "<div id='cancelModal' class='modal'>";
            echo "<div class='modal-content'>";
            echo "<span class='close' onclick='closeModal()'>&times;</span>";
            echo "<center><b>REASON FOR CANCELLATION</b></center><br>";
            echo "<textarea id='cancelReason' name='cancelReason' rows='4' cols='50'></textarea>";
            echo "<center><button type='submit' id='cancel_btn' name='cancel_btn'>Submit</button></center>";
            echo "</div>";
            echo "</div>";
            echo "</form>";

            // JavaScript for modal handling
            echo "<script>
                    function openModal() {
                        document.getElementById('cancelModal').style.display = 'block';
                    }

                    function closeModal() {
                        document.getElementById('cancelModal').style.display = 'none';
                    }

                    document.getElementById('cancel_contract').addEventListener('click', openModal);
                  </script>";
        }
    } 

if (isset($_POST["cancel_btn"])) {
    // Ensure required fields are filled
        $start_date = $_POST['cancel_startDate'];
        $end_date = $_POST['cancel_endDate'];
        $cancel_reason = $_POST['cancelReason'];
        $userName = $_SESSION['user_email'];
        $contract_number = $_POST['contractNumber'];

        // Update the database
        $cancelUpdate = "UPDATE transactional SET reason_cancellation = ?, cancelled_by = ?, status = 'Cancelled' WHERE status = 'Unpaid'AND DATE_FORMAT(transaction_date, '%Y-%m') >= ? AND DATE_FORMAT(transaction_date, '%Y-%m') <= ? AND contract_number = ?";
        $cancelStmt = mysqli_prepare($conn, $cancelUpdate);
        mysqli_stmt_bind_param($cancelStmt, "sssss", $cancel_reason, $userName, $start_date, $end_date, $contract_number);
        mysqli_stmt_execute($cancelStmt);

        // Check if the update was successful
        if(mysqli_stmt_affected_rows($cancelStmt) > 0){
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Cancelled Successfully'
                    });
                </script>";
        } else {
            echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to cancel'
                    });
                </script>";
        }

        mysqli_stmt_close($cancelStmt);
 
}
?>
</div>

    </div>
    <?php
    if(isset($_POST['proceed_btn'])){
    ?>
    <div class="wrap-contract">
     <div class="container">
		<div class="row justify-content-center">
			<div class="col-12 content-head">
				<div class="mbr-section-head mb-5">
					<h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
						<center><strong style="font-size:35px;">CONTRACT OF LEASE</strong></center>
					</h3>
                    <form action="" method="POST">
                        <div id="myModal" class="modal">
                            <div class="extract_modal-content">
                                <span class="close">&times;</span>
                                <center><h5 style="font-weight:500;">SELECT A DATE TO EXRACT</h5></center><br>
                                <h5 style="color:red; font-weight:bold; font-size:18px; text-align:center;">NOTE</h5><span><i style="font-size:14px;">Clicking download button will tag the transaction to "Extracted"! make sure to save the file after clicking download.</i></span>
                                <br><br>
                                <div class="extract_input">
                                    <label for="">Start Date</label><br>
                                    <input type="month" name="extract_startDate" id="extract_startDate" value="" required><br>
                                    <label for="">End Date</label><br>
                                    <input type="month" name="extract_endDate" id="extract_endDate" value="" required><br>
                                    <button type="submit" name="download" id="download"><i class="fa-solid fa-download"></i>&nbsp;Download</button>
                                </div>
                            </div>
                        </div>
                    </form>
				</div>
			</div>
		</div>
	</div>
    
    <div class="table_wrap">

    <?php
// Assuming connection to database is already established
if (isset($_POST['branchId']) && isset($_POST['contractNumber'])) {
    $status = ''; // Assuming status check (you might need to adjust this)
    $kpxCode = $_POST['kpxCode'];
    $branchId = $_POST['branchId'];
    $contract_number = $_POST['contractNumber'];

    // Query to retrieve contract files
    $filequery = "SELECT contract_file, contractFilename, mimeType,
                contract_file2, contractFilename2, mimeType2,
                contract_file3, contractFilename3, mimeType3,
                contract_file4, contractFilename4, mimeType4,
                contract_file5, contractFilename5, mimeType5
                FROM create_contract
                WHERE contract_number = '" . $_POST['contractNumber'] . "'";

    $fileresult = mysqli_query($conn, $filequery);

    if ($row = mysqli_fetch_assoc($fileresult)) {
        echo '<div class="file-section">'; // Container for files
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

        // Display contract files
        if (!empty($contractFiles)) {
            if (count($contractFiles) === 1) {
                // Display the single file as a link (not in the modal)
                $singleFile = $contractFiles[0];
                echo '<a href="#" class="view-single-file" data-file-content="' . $singleFile['content'] . '" data-mime-type="' . $singleFile['mimeType'] . '" data-file-name="' . $singleFile['file'] . '" style="font-weight:bold;">' . $singleFile['icon'] . ' ' . $singleFile['file'] . '</a>';
            } else {
                // Display the "View Files" button that opens the modal
                echo '<button type="button" class="view-contracts" data-contract-files=\'' . json_encode($contractFiles) . '\'>View Files</button>';
            }
        }else {
            echo '<p>No files found</p>';
        }
        echo '</div>'; // Close file-section
    }
}
?>

<!-- Table displaying contract information -->
 <center>
<table class="contract_lg_table" id="contract_lg_table">
    <thead>
        <tr>
            <th>LESSOR NAME</th>
            <?php
            // Query to check if l2_firstname, l2_middlename, or l2_lastname are not empty
            $checkQuery = "SELECT l2_firstname, l2_middlename, l2_lastname FROM transactional WHERE (l2_firstname != '' OR l2_middlename != '' OR l2_lastname != '') AND status != ? AND branch_id = ? AND contract_number = ? LIMIT 1";

            $checkStmt = mysqli_prepare($conn, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, "sss", $status, $branchId, $contract_number);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if (mysqli_num_rows($checkResult) > 0) {
                echo "<th>2nd LESSOR NAME</th>";
            }
            ?>
            <th>DUE DATE</th>
            <th>GROSS RENTAL</th>
            <th>VAT TYPE</th>
            <th>NET OF VAT</th>
            <th>VAT AMOUNT</th>
            <th>W-TAX</th>
            <th>AMOUNT TO LESSOR</th>
            <th>TOTAL MONTHLY RENTAL</th>
            <th>MODE OF PAYMENT</th>
            <th>STATUS</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Prepare and execute the SQL query to fetch contract details
        $selectQuery = "SELECT * FROM transactional WHERE status != ? AND branch_id = ? AND contract_number = ?";
        $stmt = mysqli_prepare($conn, $selectQuery);
        mysqli_stmt_bind_param($stmt, "sss", $status, $branchId, $contract_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch and display the data in the table
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            echo '<tr onclick="highlightRow(this)">';
            echo '<td>' . htmlspecialchars($row['l1_firstname']) . " " . htmlspecialchars($row['l1_middlename']) . " " . htmlspecialchars($row['l1_lastname']) . '</td>';

            if (isset($row['l2_firstname']) && !empty($row['l2_firstname']) || isset($row['l2_middlename']) && !empty($row['l2_middlename']) || isset($row['l2_lastname']) && !empty($row['l2_lastname'])) {
                echo '<td>' . htmlspecialchars($row['l2_firstname']) . " " . htmlspecialchars($row['l2_middlename']) . " " . htmlspecialchars($row['l2_lastname']) . '</td>';
            }

            echo '<td>' . date('F j, Y', strtotime($row['transaction_date'])) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['amount'], 2) . '</td>';
            echo '<td style="text-align:center;">' . htmlspecialchars($row['vat_type']) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['net_of_vat'], 2) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['vat_amount'], 2) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['wtax'], 2) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['edit_amount_lessor'], 2) . '</td>';
            echo '<td style="text-align:center;">₱ ' . number_format($row['total_month_rental'], 2) . '</td>';
            echo '<td style="text-align:center;">' . htmlspecialchars($row['mode_of_payment']) . '</td>';
            echo '<td style="font-weight: 700;">' . htmlspecialchars($row['status']) . '</td>';
            echo '</tr>';
        }

        mysqli_close($conn);
    }
        ?>
    </tbody>
</table>
</center>
    <input type="hidden" id="selected_id_display" name="selectedID" value=""> 
    </div>
</div>
</div>
</form>
        <!-- Modal Structure -->
        <div id="fileModal" class="file-modal" style="display:none;">
    <div class="file-modal-content">
        <span class="file-modal-close">&times;</span>
        <div id="filePreview"></div>
    </div>
</div>
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

document.addEventListener('DOMContentLoaded', function () {
    var dropdownLinks = document.querySelectorAll('.dropdown .nav-link');
    dropdownLinks.forEach(function (el) {
        el.addEventListener('click', onClick, false);
    });

    function onClick(e) {
        e.preventDefault();
        var el = this.parentNode;
        el.classList.contains('show-submenu') ? hideSubMenu(el) : showSubMenu(el);
    }

    function showSubMenu(el) {
        el.classList.add('show-submenu') ;
        document.addEventListener('click', onDocClick);

        function onDocClick(e) {
            if (el.contains(e.target)) {
                return;
            }
            document.removeEventListener('click', onDocClick);
            hideSubMenu(el);
        }
    }

    function hideSubMenu(el) {
        el.classList.remove('show-submenu');
    }
});

// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("export_ledger");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}


function updateKpxCode(branchSelect) {
        const selectedBranch = branchSelect.selectedOptions[0];
        const kpxCode = selectedBranch.dataset.kpxCode;
        const branchId = selectedBranch.dataset.branchId;
        const lessor_name = selectedBranch.dataset.lessorName; // Corrected the property name here
        const kpxCodeInput = document.getElementById('kpxCode');
        const branchIdInput = document.getElementById('branchId');
        const lessorInput = document.getElementById('lessor_name');
        kpxCodeInput.value = kpxCode;
        branchIdInput.value = branchId;
        lessorInput.value = lessor_name;

        // Assuming your form has an ID 'yourFormId', adjust it accordingly
        const form = document.getElementById('ledger_form');

        // Submit the form
        form.submit();
}

function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'transparent';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'
}
   

    </script>
    </body>
</html>
